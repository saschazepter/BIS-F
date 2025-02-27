<?php

namespace App\DataProviders;

use App\Enum\TripSource;
use App\Exceptions\UnknownDataProvider;

class DataProviderBuilder
{
    public function build(?bool $cache = null): DataProviderInterface {
        return new Motis(TripSource::TRANSITOUS);
        $dp = match (config('trwl.data_provider')) {
            'hafas' => new Hafas(),
            'bahn'  => new Bahn(),
            default => throw new UnknownDataProvider('No valid data provider configured'),
        };

        if ($cache === true || ($cache === null && config('trwl.cache.data_provider'))) {
            return new CachedDataProvider($dp);
        }

        return $dp;
    }
}
