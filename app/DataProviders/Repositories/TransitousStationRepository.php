<?php

namespace App\DataProviders\Repositories;

use App\Models\Station;
use Illuminate\Support\Collection;
use PDOException;
use stdClass;

class TransitousStationRepository
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

    public static function parseTransitousStops(array $transitousResponse): Collection {
        /**
         * "id": "de-DELFI_de:08128:13805_G",
         * "name": "Lauda, Bahnhof",
         * "pos": {
         * "lat": 49.565385,
         * "lng": 9.709512
         * }
         */
        $payload = [];
        foreach ($transitousResponse as $transitousStation) {
            $payload[] = [
                'transitous_id' => $transitousStation->id,
                'name'          => $transitousStation->name,
                'latitude'      => $transitousStation?->pos?->lat ?? 0,
                'longitude'     => $transitousStation?->pos?->lon ?? 0,
            ];
        }
        return self::upsertStations($payload);
    }

    public static function upsertStations(array $payload) {
        $ibnrs = array_column($payload, 'transitous_id');
        if (empty($ibnrs)) {
            return new Collection();
        }
        Station::upsert($payload, ['transitous_id'], ['name', 'latitude', 'longitude']);
        return Station::whereIn('transitous_id', $ibnrs)->get()
                      ->sortBy(function(Station $station) use ($ibnrs) {
                          return array_search($station->ibnr, $ibnrs);
                      })
                      ->values();
    }
}
