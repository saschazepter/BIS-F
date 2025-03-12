<?php

namespace App\DataProviders;

use App\Enum\DataProvider;
use App\Exceptions\UnknownDataProvider;

class DataProviderBuilder
{
    public function build(?bool $cache = null, $user = null): DataProviderInterface {
        if ($user?->data_provider instanceof DataProvider && $user?->data_provider->isMotis() && $user?->hasPermissionTo('use-transitous')) {
            return new Motis($user?->data_provider);
        }
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
