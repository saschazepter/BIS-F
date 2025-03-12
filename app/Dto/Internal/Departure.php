<?php

namespace App\Dto\Internal;

use App\Models\Station;
use Carbon\Carbon;

readonly class Departure
{

    public Station     $station;
    public Carbon|null $plannedDeparture;
    public Carbon|null $realDeparture;
    public Carbon|null $plannedArrival;
    public Carbon|null $realArrival;
    public BahnTrip    $trip;
    public string|null $plannedPlatform;
    public string|null $realPlatform;

    public function __construct(Station $station, Carbon|null $plannedDeparture, Carbon|null $realDeparture, BahnTrip $trip, string|null $plannedPlatform, string|null $realPlatform, Carbon|null $plannedArrival = null, Carbon|null $realArrival = null) {
        $this->station          = $station;
        $this->plannedDeparture = $plannedDeparture;
        $this->realDeparture    = $realDeparture;
        $this->trip             = $trip;
        $this->plannedPlatform  = $plannedPlatform;
        $this->realPlatform     = $realPlatform;
        $this->plannedArrival   = $plannedArrival;
        $this->realArrival      = $realArrival;
    }

    public function getDelay(): ?int {
        if ($this->realDeparture && $this->plannedDeparture) {
            return $this->plannedDeparture->diffInMinutes($this->realDeparture);
        }

        if ($this->realArrival && $this->plannedArrival) {
            return $this->plannedArrival->diffInMinutes($this->realArrival);
        }

        return null;
    }
}
