<?php

namespace App\DataProviders\Repositories;

use App\Models\Station;
use Illuminate\Support\Collection;
use PDOException;
use stdClass;

class TriasStationRepository
{

    /**
     * @param stdClass $hafasStop
     *
     * @return Station
     * @throws PDOException
     */
    public static function parseHafasStopObject(stdClass $hafasStop): Station {

        $data = [
            'name'      => $hafasStop->name,
            'latitude'  => $hafasStop->location?->latitude,
            'longitude' => $hafasStop->location?->longitude,
        ];

        if (isset($hafasStop->ril100)) {
            $data['rilIdentifier'] = $hafasStop->ril100;
        }

        return Station::updateOrCreate(
            ['ibnr' => $hafasStop->id],
            $data
        );
    }

    public static function parseHafasStops(array $hafasResponse): Collection {
        $payload = [];
        foreach ($hafasResponse as $hafasStation) {
            $payload[] = [
                'ibnr'      => $hafasStation->id,
                'name'      => $hafasStation->name,
                'latitude'  => $hafasStation?->location?->latitude,
                'longitude' => $hafasStation?->location?->longitude,
            ];
        }
        return self::upsertStations($payload);
    }

    public static function upsertStations(array $payload) {
        $ibnrs = array_column($payload, 'ibnr');
        if (empty($ibnrs)) {
            return new Collection();
        }
        Station::upsert($payload, ['ifopt_a', 'ifopt_b', 'ifopt_c', 'ifopt_d', 'ifopt_e'], ['name', 'latitude', 'longitude']);
        return Station::whereIn('ibnr', $ibnrs)->get()
                      ->sortBy(function(Station $station) use ($ibnrs) {
                          return array_search($station->ibnr, $ibnrs);
                      })
                      ->values();
    }


    public static function upsertIfoptStations(array $payload) {
        $ifopt = array_column($payload, 'stellwerk_id');
        if (empty($ifopt)) {
            return new Collection();
        }
        Station::upsert($payload, ['stellwerk_id'], ['name', 'ifopt_a', 'ifopt_b', 'ifopt_c', 'ifopt_d', 'ifopt_e', 'latitude', 'longitude']);
        return Station::whereIn('stellwerk_id', $ifopt)->get()
                      ->sortBy(function(Station $station) use ($ifopt) {
                          return array_search($station->stellwerk_id, $ifopt);
                      })
                      ->values();
    }
}
