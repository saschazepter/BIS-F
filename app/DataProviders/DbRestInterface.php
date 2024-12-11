<?php

namespace App\DataProviders;

use App\Models\Stopover;

interface DbRestInterface
{
    public static function refreshStopover(Stopover $stopover): void;

    public static function fetchHafasTrip(string $tripID, string $lineName);

    public static function fetchRawHafasTrip(string $tripId, string $lineName);

    public static function getStations(string $query, int $results);
}
