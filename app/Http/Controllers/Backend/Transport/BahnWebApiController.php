<?php

namespace App\Http\Controllers\Backend\Transport;

use App\Dto\Transport\Departure;
use App\Enum\ReiseloesungCategory;
use App\Enum\TripSource;
use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

abstract class BahnWebApiController extends Controller
{

    public static function searchStation(string $query, int $limit = 10): Collection {
        $url      = "https://www.bahn.de/web/api/reiseloesung/orte?suchbegriff=" . urlencode($query) . "&typ=ALL&limit=" . $limit;
        $response = Http::get($url);
        $json     = $response->json();
        $extIds   = [];
        foreach ($json as $rawStation) {
            if (!isset($rawStation['extId'])) {
                continue;
            }
            $extIds[] = $rawStation['extId'];
        }
        $stationCache = Station::whereIn('ibnr', $extIds)->get();

        $stations = collect();
        foreach ($json as $rawStation) {
            if (!isset($rawStation['extId'])) {
                continue;
            }
            $station = $stationCache->where('ibnr', $rawStation['extId'])->first();
            if ($station === null) {
                $station = Station::create([
                                               'name'      => $rawStation['name'],
                                               'latitude'  => $rawStation['lat'],
                                               'longitude' => $rawStation['lon'],
                                               'ibnr'      => $rawStation['extId'],
                                               'source'    => 'bahn-web-api',
                                           ]);
            }
            $stations->push($station);
        }

        return $stations;
    }

    public static function getDepartures(Station $station, Carbon|null $timestamp = null): Collection {
        if ($timestamp === null) {
            $timestamp = now();
        }
        $response   = Http::get("https://www.bahn.de/web/api/reiseloesung/abfahrten", [
            'ortExtId' => $station->ibnr,
            'datum'    => $timestamp->format('Y-m-d'),
            'zeit'     => $timestamp->format('H:i'),
        ]);
        $departures = collect();
        foreach ($response->json('entries') as $rawDeparture) {
            $journey = Trip::where('trip_id', $rawDeparture['journeyId'])->first();
            if ($journey) {
                $departures->push(new Departure(
                                      station:          $station,
                                      plannedDeparture: Carbon::parse($rawDeparture['zeit']),
                                      realDeparture:    isset($rawDeparture['ezZeit']) ? Carbon::parse($rawDeparture['ezZeit']) : null,
                                      trip:             $journey,
                                  ));
                continue;
            }

            $rawJourney = self::fetchJourney($rawDeparture['journeyId']);
            if ($rawJourney === null) {
                // sorry
                continue;
            }

            $originStation      = self::getStationFromHalt($rawJourney['halte'][0]);
            $destinationStation = self::getStationFromHalt($rawJourney['halte'][count($rawJourney['halte']) - 1]);
            $departure          = isset($rawJourney['halte'][0]['abfahrtsZeitpunkt']) ? Carbon::parse($rawJourney['halte'][0]['abfahrtsZeitpunkt']) : null;
            $arrival            = isset($rawJourney['halte'][count($rawJourney['halte']) - 1]['ankunftsZeitpunkt']) ? Carbon::parse($rawJourney['halte'][count($rawJourney['halte']) - 1]['ankunftsZeitpunkt']) : null;
            $category           = isset($rawDeparture['verkehrmittel']['produktGattung']) ? ReiseloesungCategory::tryFrom($rawDeparture['verkehrmittel']['produktGattung']) : ReiseloesungCategory::UNKNOWN;
            $category           = $category ?? ReiseloesungCategory::UNKNOWN;

            //trip
            $tripLineName      = $rawDeparture['verkehrmittel']['name'] ?? '';
            $tripNumber        = preg_replace('/\s/', '-', strtolower($tripLineName)) ?? '';
            $tripJourneyNumber = preg_replace('/\D/', '', $rawDeparture['verkehrmittel']['name']);

            $journey = Trip::create([
                                        'trip_id'        => $rawDeparture['journeyId'],
                                        'category'       => $category->getHTT(),
                                        'number'         => $tripNumber,
                                        'linename'       => $tripLineName,
                                        'journey_number' => !empty($tripJourneyNumber) ? $tripJourneyNumber : 1337,
                                        'operator_id'    => null, //TODO
                                        'origin_id'      => $originStation->id,
                                        'destination_id' => $destinationStation->id,
                                        'polyline_id'    => null,
                                        'departure'      => $departure,
                                        'arrival'        => $arrival,
                                        'source'         => TripSource::BAHN_WEB_API,
                                    ]);

            $departures->push(new Departure(
                                  station:          $station,
                                  plannedDeparture: Carbon::parse($rawDeparture['zeit']),
                                  realDeparture:    isset($rawDeparture['ezZeit']) ? Carbon::parse($rawDeparture['ezZeit']) : null,
                                  trip:             $journey,
                              ));
        }
        return $departures;
    }

    private static function getStationFromHalt(array $rawHalt) {
        $station = Station::where('ibnr', $rawHalt['extId'])->first();
        if ($station !== null) {
            return $station;
        }

        //urgh, there is no lat/lon - extract it from id
        // example id: A=1@O=Druseltal, Kassel@X=9414484@Y=51301106@U=81@L=714800@
        $matches = [];
        preg_match('/@X=(\d+)@Y=(\d+)/', $rawHalt['id'], $matches);
        $latitude  = $matches[2] / 1000000;
        $longitude = $matches[1] / 1000000;

        return Station::create([
                                   'name'      => $rawHalt['name'],
                                   'latitude'  => $latitude ?? 0, // Hello Null-Island
                                   'longitude' => $longitude ?? 0, // Hello Null-Island
                                   'ibnr'      => $rawHalt['extId'],
                                   'source'    => TripSource::BAHN_WEB_API->value,
                               ]);
    }

    public static function fetchJourney(string $journeyId, bool $poly = false): array|null {
        $response = Http::get("https://www.bahn.de/web/api/reiseloesung/fahrt", [
            'journeyId' => $journeyId,
            'poly'      => $poly ? 'true' : 'false',
        ]);
        return $response->json();
    }
}
