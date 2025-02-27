<?php

namespace App\DataProviders;

use App\DataProviders\Repositories\MotisRepository;
use App\Dto\Coordinate;
use App\Dto\Internal\BahnTrip;
use App\Dto\Internal\Departure;
use App\Enum\HafasTravelType;
use App\Enum\MotisCategory;
use App\Enum\TravelType;
use App\Enum\TripSource;
use App\Exceptions\HafasException;
use App\Helpers\CacheKey;
use App\Helpers\HCK;
use App\Http\Controllers\Controller;
use App\Hydrators\DepartureHydrator;
use App\Models\Station;
use App\Models\Stopover;
use App\Models\Trip;
use App\Services\GeoService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;

class Motis extends Controller implements DataProviderInterface
{

    private GeoService $geoService;
    private MotisRepository $motisRepository;
    private TripSource $source;

    private const string API_URL = 'https://api.transitous.org/api/v1';

    public function __construct(TripSource $source, ?MotisRepository $motisRepository = null, ?GeoService $geoService = null) {
        $this->source     = $source;
        $this->motisRepository = $motisRepository ?? new MotisRepository();
        $this->geoService = $geoService ?? new GeoService();
    }

    public function getStationByRilIdentifier(string $rilIdentifier): ?Station {
        $station = Station::where('rilIdentifier', $rilIdentifier)->first();
        if ($station !== null) {
            return $station;
        }
        return null;
    }

    public function getStationsByFuzzyRilIdentifier(string $rilIdentifier): Collection {
        $stations = Station::where('rilIdentifier', 'LIKE', "$rilIdentifier%")
                           ->orderBy('rilIdentifier')
                           ->get();
        if ($stations->count() === 0) {
            $station = $this->getStationByRilIdentifier(rilIdentifier: $rilIdentifier);
            if ($station !== null) {
                $stations->push($station);
            }
        }
        return $stations;
    }

    /**
     * @throws HafasException
     */
    public function getStations(string $query, int $results = 10): Collection {
        try {
            $url      = sprintf(self::API_URL . "/geocode?text=%s&limit=%d", urlencode($query), $results);
            $response = Http::get($url);

            if (!$response->ok()) {
                CacheKey::increment(HCK::LOCATIONS_NOT_OK);
            }

            $stations = $this->filterStopsFromResults($response);

            CacheKey::increment(HCK::LOCATIONS_SUCCESS);
            return $stations;
        } catch (JsonException $exception) {
            throw new HafasException($exception->getMessage());
        } catch (Exception $exception) {
            CacheKey::increment(HCK::LOCATIONS_FAILURE);
            throw new HafasException($exception->getMessage());
        }
    }


    /**
     * @throws HafasException
     */
    public function getNearbyStations(float $latitude, float $longitude, int $results = 8): Collection {
        $center = new Coordinate($latitude, $longitude);
        $bbox   = $this->geoService->getBoundingBox($center, 100);

        $response = Http::get(self::API_URL . '/map/stops', [
            'min' => (string) $bbox->lowerRight,
            'max' => (string) $bbox->upperLeft,
        ]);

        if (!$response->ok()) {
            CacheKey::increment(HCK::LOCATIONS_NOT_OK);
            Log::error('Unknown HAFAS Error (fetchNearbyStations)', [
                'status' => $response->status(),
                'body'   => $response->body()
            ]);
            throw new HafasException(__('messages.exception.generalHafas'));
        }

        $stations = $this->filterStopsFromResults($response, 'stopId');

        $stations = $stations->sortBy(function($station) use ($center) {
            return $this->geoService->getDistance($center, new Coordinate($station->latitude, $station->longitude));
        });

        CacheKey::increment(HCK::LOCATIONS_SUCCESS);
        return $stations;
    }

    /**
     * @param Station         $station
     * @param Carbon          $when
     * @param int             $duration
     * @param TravelType|null $type
     * @param bool            $localtime
     *
     * @return Collection
     * @throws HafasException
     */
    public function getDepartures(
        Station     $station,
        Carbon      $when,
        int         $duration = 15,
        ?TravelType $type = null,
        bool        $localtime = false
    ): Collection {
        try {
            $station->load('stationIdentifiers');
            // get transitous identifier
            $transitousIdentifier = $station->stationIdentifiers->where('type', 'motis')->where('origin', 'transitous')->first();
            $params               = [
                'stopId' => $transitousIdentifier->identifier,
                'radius' => 100,
                'time'   => $when->toIso8601String(),
                'n'      => 50
            ];

            $filterCategory = MotisCategory::fromTravelType($type);
            if (isset($filterCategory)) {
                foreach ($filterCategory as $category) {
                    $params['mode'][] = $category->value;
                }
            }

            $response   = Http::get(self::API_URL . '/stoptimes', $params);

            if (!$response->ok()) {
                CacheKey::increment(HCK::DEPARTURES_NOT_OK);
                Log::error('Unknown HAFAS Error (fetchDepartures)', [
                    'status' => $response->status(),
                    'body'   => $response->body()
                ]);
                throw new HafasException(__('messages.exception.generalHafas'));
            }

            $departures = collect();
            $entries    = $response->json('stopTimes');
            CacheKey::increment(HCK::DEPARTURES_SUCCESS);
            foreach ($entries as $rawDeparture) {
                //trip
                $tripId              = $rawDeparture['tripId'];
                $rawDepartureStation = $rawDeparture['place'];
                $tripLineName        = $rawDeparture['routeShortName'] ?? '';
                $category            = MotisCategory::tryFrom($rawDeparture['mode']);
                $hafasTravelType     = $category->getHTT()->value;

                $platformPlanned = $rawDepartureStation['scheduledTrack'] ?? '';
                $platformReal    = $rawDepartureStation['track'] ?? $platformPlanned;
                try {
                    $departureStation = $this->motisRepository->getStationsFromDb([$rawDepartureStation['stopId']], $this->source)->first();
                    if ($departureStation === null) {
                        // if station does not exist, request it from API
                        $departureStation = $this->motisRepository->createStation($rawDepartureStation, $this->source);
                    }
                } catch (Exception $exception) {
                    Log::error($exception->getMessage());
                    $departureStation = $station;
                }

                $departure = new Departure(
                    station:          $departureStation,
                    plannedDeparture: Carbon::parse($rawDepartureStation['scheduledDeparture']),
                    realDeparture:    isset($rawDepartureStation['departure']) ? Carbon::parse($rawDepartureStation['departure']) : null,
                    trip:             new BahnTrip(
                                          tripId:        $tripId,
                                          direction:     $rawDeparture['headsign'],
                                          lineName:      $tripLineName,
                                          number:        $tripId,
                                          category:      $hafasTravelType,
                                          journeyNumber: $tripId,
                                      ),
                    plannedPlatform:  $platformPlanned,
                    realPlatform:     $platformReal,
                );

                $departures->push($departure);
            }

            return DepartureHydrator::map($departures);

        } catch (JsonException $exception) {
            throw new HafasException($exception->getMessage());
        } catch (Exception $exception) {
            CacheKey::increment(HCK::DEPARTURES_FAILURE);
            throw new HafasException($exception->getMessage());
        }
    }


    /**
     * @throws HafasException
     */
    private function fetchJourney(string $tripId): array|null {
        try {
            $response = Http::get("https://api.transitous.org/api/v1/trip", ['tripId' => $tripId,]);

            if ($response->ok()) {
                CacheKey::increment(HCK::TRIPS_SUCCESS);
                return $response->json();
            }

        } catch (Exception $exception) {
            CacheKey::increment(HCK::TRIPS_FAILURE);
            Log::error('Unknown HAFAS Error (fetchJourney)', [
                'status' => $response->status(),
                'body'   => $response->body()
            ]);
            report($exception);
            throw new HafasException(__('messages.exception.generalHafas'));
        }

        CacheKey::increment(HCK::TRIPS_NOT_OK);
        Log::error('Unknown HAFAS Error (fetchRawHafasTrip)', [
            'status' => $response->status(),
            'body'   => $response->body()
        ]);
        throw new HafasException(__('messages.exception.generalHafas'));
    }

    /**
     * @throws HafasException|JsonException
     */
    public function fetchRawHafasTrip(string $tripId, string $lineName): ?array {
        return $this->fetchJourney($tripId, true);
    }

    /**
     * @param string $tripID
     * @param string $lineName
     *
     * @return Trip
     * @throws HafasException|JsonException
     */
    public function fetchHafasTrip(string $tripID, string $lineName): Trip {
        $rawJourney = $this->fetchJourney($tripID);
        if ($rawJourney === null) {
            // sorry
            throw new HafasException(__('messages.exception.generalHafas'));
        }
        // get cached data from departure board
        $leg                 = $rawJourney['legs'][0];
        $rawStopovers        = $leg['intermediateStops'];
        $stopoverCacheFromDB = $this->motisRepository->getStationsFromDb(array_column($rawStopovers, 'stopId'), $this->source);

        $originStation      = $this->motisRepository->getStationsFromDb($leg['from']['stopId'], $this->source)->first() ?? $this->motisRepository->createStation($leg['from'], $this->source);
        $destinationStation = $this->motisRepository->getStationsFromDb($leg['to']['stopId'], $this->source)->first() ?? $this->motisRepository->createStation($leg['to'], $this->source);
        $departure          = isset($leg['from']['departure']) ? Carbon::parse($leg['from']['departure']) : null;
        $arrival            = isset($leg['to']['arrival']) ? Carbon::parse($leg['to']['arrival']) : null;
        $category           = MotisCategory::tryFrom($leg['mode'])?->getHTT()->value ?? HafasTravelType::REGIONAL;
        $tripLineName       = !empty($leg['routeShortName']) ? $leg['routeShortName'] : $lineName;

        // add origin and destination to stopovers
        $rawStopovers[] = $leg['from'];
        $rawStopovers[] = $leg['to'];

        $stopovers = collect();
        foreach ($rawStopovers as $rawStop) {
            $station = $stopoverCacheFromDB->where('stationIdentifiers', function($query) use ($rawStop) {
                $query->where('identifier', $rawStop['stopId'])
                      ->where('type', 'motis')
                      ->where('origin', 'transitous');
            })->first();
            $station = $station ?? $this->motisRepository->createStation($rawStop, $this->source);

            $departurePlanned = isset($rawStop['scheduledDeparture']) ? Carbon::parse($rawStop['scheduledDeparture']) : null;
            $departureReal    = isset($rawStop['departure']) ? Carbon::parse($rawStop['departure']) : null;
            $arrivalPlanned   = isset($rawStop['scheduledArrival']) ? Carbon::parse($rawStop['scheduledArrival']) : null;
            $arrivalReal      = isset($rawStop['arrival']) ? Carbon::parse($rawStop['arrival']) : null;
            // new API does not differ between departure and arrival platform
            $platformPlanned = $rawStop['scheduledTrack'] ?? null;
            $platformReal    = $rawStop['track'] ?? $platformPlanned;

            $stopover = new Stopover([
                                         'train_station_id'           => $station->id,
                                         'arrival_planned'            => $arrivalPlanned ?? $departurePlanned,
                                         'arrival_real'               => $arrivalReal ?? $departureReal ?? null,
                                         'departure_planned'          => $departurePlanned ?? $arrivalPlanned,
                                         'departure_real'             => $departureReal ?? $arrivalReal ?? null,
                                         'arrival_platform_planned'   => $platformPlanned,
                                         'departure_platform_planned' => $platformPlanned,
                                         'arrival_platform_real'      => $platformReal,
                                         'departure_platform_real'    => $platformReal,
                                     ]);
            $stopovers->push($stopover);
        }

        $journey = Trip::updateOrCreate([
                                            'trip_id' => $tripID,
                                        ], [
                                            'category'       => $category,
                                            'number'         => $tripLineName,
                                            'linename'       => $tripLineName,
                                            'journey_number' => null,
                                            'operator_id'    => null, //TODO
                                            'origin_id'      => $originStation->id,
                                            'destination_id' => $destinationStation->id,
                                            'polyline_id'    => null, //TODO
                                            'departure'      => $departure,
                                            'arrival'        => $arrival,
                                            'source'         => $this->source,
                                        ]);
        $journey->stopovers()->saveMany($stopovers);

        return $journey;
    }

    public function filterStopsFromResults(Response $response, string $identifier = 'id'): Collection {
        $json        = $response->json();
        $rawStations = [];
        foreach ($json as $stationEntry) {
            if (isset($stationEntry['type']) && $stationEntry['type'] !== 'STOP') {
                continue;
            }
            $rawStations[] = $stationEntry;
        }
        $stationIds   = array_column($rawStations, $identifier);
        $stationCache = $this->motisRepository->getStationsFromDb($stationIds, $this->source);

        $stations = collect();
        foreach ($rawStations as $rawStation) {
            $station = $stationCache->where('stationIdentifiers.identifier', $rawStation[$identifier])->first();
            if ($station === null) {
                $rawStation['stopId'] = $rawStation[$identifier];
                $station              = $this->motisRepository->createStation($rawStation, $this->source);
            }
            $stations->push($station);
        }
        return $stations;
    }
}
