<?php

namespace App\DataProviders;

use App\Enum\TravelType;
use App\Models\Station;
use Carbon\Carbon;

interface DbRestInterface
{

    public static function fetchHafasTrip(string $tripID, string $lineName);

    public static function fetchRawHafasTrip(string $tripId, string $lineName);

    public static function getStations(string $query, int $results);

    public static function getDepartures(Station $station, Carbon $when, int $duration, TravelType $type, bool $localtime);

    public static function fetchDepartures(Station $station, Carbon $when, int $duration, TravelType $type, bool $skipTimeShift);

    public static function getNearbyStations(float $latitude, float $longitude, int $results);

    public static function getStationByRilIdentifier(string $rilIdentifier);

    public static function getStationsByFuzzyRilIdentifier(string $rilIdentifier);
}
