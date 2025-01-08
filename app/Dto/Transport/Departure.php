<?php

namespace App\Dto\Transport;

use App\Models\Trip;
use Carbon\Carbon;

readonly class Departure {

    public \App\Models\Station $station;
    public Carbon              $plannedDeparture;
    public Carbon|null         $realDeparture;
    public Trip                $trip;

    public function __construct(\App\Models\Station $station, Carbon $plannedDeparture, Carbon|null $realDeparture, Trip $trip) {
        $this->station          = $station;
        $this->plannedDeparture = $plannedDeparture;
        $this->realDeparture    = $realDeparture;
        $this->trip             = $trip;
    }

    public function getDelay(): ?int {
        if($this->realDeparture === null) {
            return null;
        }
        return $this->plannedDeparture->diffInMinutes($this->realDeparture);
    }
}
