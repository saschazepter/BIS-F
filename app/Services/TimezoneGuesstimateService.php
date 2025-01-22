<?php

namespace App\Services;

use App\Dto\Coordinate;
use App\Objects\LineSegment;
use DateTimeZone;

class TimezoneGuesstimateService
{

    public function getTimezoneFromCoordinates($latitude, $longitude): ?string {
        $timezoneIds = DateTimeZone::listIdentifiers();

        $timeZone   = null;
        $tzDistance = 0;
        foreach ($timezoneIds as $timezone_id) {
            try {
                $timezone = new DateTimeZone($timezone_id);
            } catch (\DateInvalidTimeZoneException $e) {
                return null;
            }
            $location = $timezone->getLocation();
            $tzLat    = $location['latitude'];
            $tzLon    = $location['longitude'];

            $distance = $this->getDistance($latitude, $longitude, $tzLat, $tzLon);

            if (!$timeZone || $tzDistance > $distance) {
                $timeZone   = $timezone_id;
                $tzDistance = $distance;
            }

        }
        return $timeZone;
    }

    private function getDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo): int {
        $segment = new LineSegment(
            new Coordinate($latitudeFrom, $longitudeFrom),
            new Coordinate($latitudeTo, $longitudeTo)
        );

        return $segment->calculateDistance();
    }

}
