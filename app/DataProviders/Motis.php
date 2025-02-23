<?php

namespace App\DataProviders;

use App\Dto\Coordinate;
use App\Dto\Internal\BahnTrip;
use App\Dto\Internal\Departure;
use App\Enum\HafasTravelType;
use App\Enum\MotisCategory;
use App\Enum\ReiseloesungCategory;
use App\Enum\TravelType;
use App\Enum\TripSource;
use App\Exceptions\HafasException;
use App\Helpers\CacheKey;
use App\Helpers\HCK;
use App\Http\Controllers\Controller;
use App\Hydrators\DepartureHydrator;
use App\Models\PolyLine;
use App\Models\Station;
use App\Models\Stopover;
use App\Models\Trip;
use App\Objects\LineSegment;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;

class Motis extends Controller implements DataProviderInterface
{
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
            $url      = "https://www.bahn.de/web/api/reiseloesung/orte?suchbegriff=" . urlencode($query) . "&typ=ALL&limit=" . $results;
            $url      = sprintf("https://api.transitous.org/api/v1/geocode?text=%s&limit=%d", urlencode($query), $results);
            $response = Http::get($url);

            if (!$response->ok()) {
                CacheKey::increment(HCK::LOCATIONS_NOT_OK);
            }

            $json        = $response->json();
            $rawStations = [];
            foreach ($json as $stationEntry) {
                if ($stationEntry['type'] !== 'STOP') {
                    continue;
                }
                $rawStations[] = $stationEntry;
            }
            $stationIds   = array_column($rawStations, 'id');
            $stationCache = $this->getStationsFromDb($stationIds);

            $stations = collect();
            foreach ($rawStations as $rawStation) {
                $station = $stationCache->where('stationIdentifiers.identifier', $rawStation['id'])->first();
                if ($station === null) {
                    $station = $this->createStation($rawStation);
                }
                $stations->push($station);
            }

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
        throw new HafasException("Method currently not supported");
    }

    private function getStationFromHalt(array $rawHalt) {
        //$station = Station::where('ibnr', $rawHalt['extId'])->first();
        //if($station !== null) {
        //    return $station;
        // }

        //urgh, there is no lat/lon - extract it from id
        // example id: A=1@O=Druseltal, Kassel@X=9414484@Y=51301106@U=81@L=714800@
        $matches = [];
        preg_match('/@X=(-?\d+)@Y=(-?\d+)/', $rawHalt['id'], $matches);
        $latitude  = $matches[2] / 1000000;
        $longitude = $matches[1] / 1000000;

        return Station::updateOrCreate([
                                           'ibnr' => $rawHalt['extId'],
                                       ], [
                                           'name'      => $rawHalt['name'],
                                           'latitude'  => $latitude ?? 0, // Hello Null-Island
                                           'longitude' => $longitude ?? 0, // Hello Null-Island
                                           'source'    => TripSource::BAHN_WEB_API->value,
                                       ]);
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
    ) {
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

            $requestUrl = "https://api.transitous.org/api/v1/stoptimes" . '?' . http_build_query($params);
            $response   = Http::get($requestUrl);

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
                    $departureStation = $this->getStationsFromDb([$rawDepartureStation['stopId']])->first();
                    if ($departureStation === null) {
                        // if station does not exist, request it from API
                        $departureStation = $this->createStation([
                                                                     'id'   => $rawDepartureStation['stopId'],
                                                                     'name' => $rawDepartureStation['name'],
                                                                     'lat'  => $rawDepartureStation['lat'],
                                                                     'lon'  => $rawDepartureStation['lon'],
                                                                 ]);
                    }
                } catch (Exception $exception) {
                    Log::error($exception->getMessage());
                    $departureStation = $station;
                }

                // Cache data used for trip creation since another endpoints do not provide them
                // todo: check if this is needed in transitous
                Cache::add(
                    $tripId,
                    ['category' => $hafasTravelType, 'lineName' => $tripLineName],
                    now()->addMinutes(30)
                );

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
    private function fetchJourney(string $journeyId, bool $poly = false): array|null {
        try {
            $response = Http::get("https://www.bahn.de/web/api/reiseloesung/fahrt", [
                'journeyId' => $journeyId,
                'poly'      => $poly ? 'true' : 'false',
            ]);

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
    public function fetchRawHafasTrip(string $tripId, string $lineName) {
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
        $timezone = "Europe/Berlin";

        $rawJourney = $this->fetchJourney($tripID, true);
        if ($rawJourney === null) {
            // sorry
            throw new HafasException(__('messages.exception.generalHafas'));
        }
        // get cached data from departure board
        $cachedData          = Cache::get($tripID);
        $stopoverCacheFromDB = Station::whereIn('ibnr', collect($rawJourney['halte'])->pluck('extId'))->get();

        $originStation      = $stopoverCacheFromDB->where('ibnr', $rawJourney['halte'][0]['extId'])->first() ?? self::getStationFromHalt($rawJourney['halte'][0]);
        $destinationStation = $stopoverCacheFromDB->where('ibnr', $rawJourney['halte'][count($rawJourney['halte']) - 1]['extId'])->first() ?? self::getStationFromHalt($rawJourney['halte'][count($rawJourney['halte']) - 1]);
        $departure          = isset($rawJourney['halte'][0]['abfahrtsZeitpunkt']) ? Carbon::parse($rawJourney['halte'][0]['abfahrtsZeitpunkt'], $timezone) : null;
        $arrival            = isset($rawJourney['halte'][count($rawJourney['halte']) - 1]['ankunftsZeitpunkt']) ? Carbon::parse($rawJourney['halte'][count($rawJourney['halte']) - 1]['ankunftsZeitpunkt'], $timezone) : null;

        foreach ($rawJourney['halte'] as $halt) {
            if (!empty($halt['kategorie'])) {
                $category = ReiseloesungCategory::tryFrom($halt['kategorie']);
                break;
            }
        }
        if (empty($category)) {
            // get cached category since Bahn API does not reveal that on the journey endpoint?!
            $category = $cachedData['category'] ?? HafasTravelType::REGIONAL->value;
        } else {
            $category = $category->getHTT()->value;
        }

        $tripLineName = $cachedData['lineName'] ?? $lineName ?? '';

        // get trip number from first stop
        $tripNumber = isset($rawJourney['halte'][0]['nummer']) ? (int) $rawJourney['halte'][0]['nummer'] : 0;
        if ($tripNumber === 0) {
            preg_match('/#ZE#(\d+)/', $tripID, $matches);
            if (count($matches) > 1) {
                $tripNumber = $matches[1];
            }
        }

        $stopovers = collect();
        foreach ($rawJourney['halte'] as $rawHalt) {
            $station = $stopoverCacheFromDB->where('ibnr', $rawHalt['extId'])->first() ?? self::getStationFromHalt($rawHalt);

            $departurePlanned = isset($rawHalt['abfahrtsZeitpunkt']) ? Carbon::parse($rawHalt['abfahrtsZeitpunkt'], $timezone) : null;
            $departureReal    = isset($rawHalt['ezAbfahrtsZeitpunkt']) ? Carbon::parse($rawHalt['ezAbfahrtsZeitpunkt'], $timezone) : null;
            $arrivalPlanned   = isset($rawHalt['ankunftsZeitpunkt']) ? Carbon::parse($rawHalt['ankunftsZeitpunkt'], $timezone) : null;
            $arrivalReal      = isset($rawHalt['ezAnkunftsZeitpunkt']) ? Carbon::parse($rawHalt['ezAnkunftsZeitpunkt'], $timezone) : null;
            // new API does not differ between departure and arrival platform
            $platformPlanned = $rawHalt['gleis'] ?? null;
            $platformReal    = $rawHalt['ezGleis'] ?? $platformPlanned;

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

        $polyLine = isset($rawJourney['polylineGroup']) ? $this->getPolyLineFromTrip($rawJourney, $stopovers) : null;

        $journey = Trip::updateOrCreate([
                                            'trip_id' => $tripID,
                                        ], [
                                            'category'       => $category,
                                            'number'         => $tripNumber,
                                            'linename'       => $tripLineName,
                                            'journey_number' => $tripNumber,
                                            'operator_id'    => null, //TODO
                                            'origin_id'      => $originStation->id,
                                            'destination_id' => $destinationStation->id,
                                            'polyline_id'    => $polyLine?->id,
                                            'departure'      => $departure,
                                            'arrival'        => $arrival,
                                            'source'         => TripSource::BAHN_WEB_API,
                                        ]);
        $journey->stopovers()->saveMany($stopovers);

        return $journey;
    }

    private function getPolyLineFromTrip($journey, Collection $stopovers): PolyLine {
        $polyLine = $journey['polylineGroup'];
        $features = [];
        foreach ($polyLine['polylineDescriptions'] as $description) {
            foreach ($description['coordinates'] as $coordinate) {
                $feature    = [
                    'type'       => 'Feature',
                    'geometry'   => [
                        'type'        => 'Point',
                        'coordinates' => [
                            $coordinate['lng'],
                            $coordinate['lat']
                        ]
                    ],
                    'properties' => new \stdclass()
                ];
                $features[] = $feature;
            }
        }
        $geoJson = ['type' => 'FeatureCollection', 'features' => $features];

        // TODO DUPLICATED FROM BROUTERCONTROLLER
        $highestMappedKey = null;
        foreach ($stopovers as $stopover) {
            $properties = [
                'id'   => $stopover->station->ibnr,
                'name' => $stopover->station->name,
            ];

            //Get feature with the lowest distance to station
            $minDistance       = null;
            $closestFeatureKey = null;
            foreach ($geoJson['features'] as $key => $feature) {
                if (($highestMappedKey !== null && $key <= $highestMappedKey) || !isset($feature['geometry']['coordinates'])) {
                    //Don't look again at the same stations.
                    //This is required and very important to prevent bugs for ring lines!
                    continue;
                }
                $distance = (new LineSegment(
                    new Coordinate($feature['geometry']['coordinates'][1], $feature['geometry']['coordinates'][0]),
                    new Coordinate($stopover->station->latitude, $stopover->station->longitude)
                ))->calculateDistance();

                if ($minDistance === null || $distance < $minDistance) {
                    $minDistance       = $distance;
                    $closestFeatureKey = $key;
                }
            }
            $highestMappedKey                                      = $closestFeatureKey;
            $geoJson['features'][$closestFeatureKey]['properties'] = $properties;
        }

        // Make features to array again, if they get broken by the code above
        $geoJson['features'] = array_values($geoJson['features']);

        $geoJsonString = json_encode($geoJson);
        $polyline      = PolyLine::create([
                                              'hash'      => md5($geoJsonString),
                                              'polyline'  => $geoJsonString,
                                              'source'    => 'hafas', // maybe add a new one?
                                              'parent_id' => null
                                          ]);
        return $polyline;
    }

    /**
     * @return mixed
     * @todo move to repository
     */
    public function createStation(mixed $rawStation): Station {
        $station = Station::create([
                                       'name'      => $rawStation['name'],
                                       'latitude'  => $rawStation['lat'],
                                       'longitude' => $rawStation['lon'],
                                       'source'    => 'transitous',
                                   ]);
        $station->stationIdentifiers()->create([
                                                   'type'       => 'motis',
                                                   'origin'     => 'transitous',
                                                   'identifier' => $rawStation['id']
                                               ]);
        return $station;
    }

    /**
     * @param array $stationIds
     *
     * @return Collection<Station>
     */
    public function getStationsFromDb(array $stationIds): Collection {
        return Station::whereRelation('stationIdentifiers', function($query) use ($stationIds) {
            $query->whereIn('identifier', $stationIds)
                  ->where('type', 'motis')
                  ->where('origin', 'transitous');
        })->get();
    }
}
