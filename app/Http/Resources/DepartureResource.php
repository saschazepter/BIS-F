<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartureResource extends JsonResource
{
    /**
     * $this = \App\Dto\Transport\Departure::class
     *
     */
    public function toArray(Request $request): array {
        return [

            "tripId"              => $this->trip->trip_id,
            "stop"                => [
                "type"     => "stop",
                "id"       => $this->station->ibnr,
                "name"     => $this->station->name,
                "location" => [
                    "type"      => "location",
                    "id"        => $this->station->ibnr,
                    "latitude"  => $this->station->latitude,
                    "longitude" => $this->station->longitude
                ],
                "products" => [
                    "nationalExpress" => true, //TODO
                    "national"        => true, //TODO
                    "regionalExp"     => true, //TODO
                    "regional"        => true, //TODO
                    "suburban"        => true, //TODO
                    "bus"             => true, //TODO
                    "ferry"           => true, //TODO
                    "subway"          => true, //TODO
                    "tram"            => true, //TODO
                    "taxi"            => true, //TODO
                ]
            ],
            "when"                => $this->realDeparture?->toIso8601String(),
            "plannedWhen"         => $this->plannedDeparture->toIso8601String(),
            "delay"               => $this->getDelay(), //TODO: make it deprecated
            "platform"            => null,
            "plannedPlatform"     => null,
            "direction"           => $this->trip->destinationStation->name,
            "provenance"          => null,
            "line"                => [
                "type"        => "line",
                "id"          => $this->trip->linename,
                "fahrtNr"     => $this->trip->number,
                "name"        => $this->trip->linename,
                "public"      => true,
                "adminCode"   => "80____",
                "productName" => $this->trip->linename, //TODO
                "mode"        => "train", //TODO
                "product"     => self::estimateType($this->trip->linename), //not the best, but it works for now
                "operator"    => null,/*[ //TODO
                    "type" => "operator",
                    "id"   => "db-fernverkehr-ag",
                    "name" => "DB Fernverkehr AG"
                ]*/
            ],
            "remarks"             => null,
            "origin"              => null,
            "destination"         => [
                "type"     => "stop",
                "id"       => $this->trip->destinationStation->ibnr,
                "name"     => $this->trip->destinationStation->name,
                "location" => [
                    "type"      => "location",
                    "id"        => $this->trip->destinationStation->ibnr,
                    "latitude"  => $this->trip->destinationStation->latitude,
                    "longitude" => $this->trip->destinationStation->longitude
                ],
                "products" => [
                    "nationalExpress" => true, //TODO
                    "national"        => true, //TODO
                    "regionalExp"     => true, //TODO
                    "regional"        => true, //TODO
                    "suburban"        => true, //TODO
                    "bus"             => true, //TODO
                    "ferry"           => true, //TODO
                    "subway"          => true, //TODO
                    "tram"            => true, //TODO
                    "taxi"            => true, //TODO
                ]
            ],
            "currentTripPosition" => null, //TODO
            /*[
            "type"      => "location",
            "latitude"  => 48.725382,
            "longitude" => 8.142888
        ],*/
            "loadFactor"          => null,
            "station"             => new StationResource($this->station)
        ];
    }

    private static function estimateType(string $linename): string {
        if(str_contains($linename, 'ICE') || str_contains($linename, 'IC') || str_contains($linename, 'EC')) {
            return "nationalExpress";
        }
        if(str_contains($linename, 'RE') || str_contains($linename, 'RB')) {
            return "regional";
        }
        if(str_contains($linename, 'S')) {
            return "suburban";
        }
        if(str_contains($linename, 'Bus')) {
            return "bus";
        }
        if(str_contains($linename, 'Ferry')) {
            return "ferry";
        }
        if(str_contains($linename, 'U')) {
            return "subway";
        }
        if(str_contains($linename, 'Tram') || str_contains($linename, 'STR')) {
            return "tram";
        }
        return "national";
    }
}
