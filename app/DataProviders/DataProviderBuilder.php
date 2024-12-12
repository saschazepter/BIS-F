<?php

namespace App\DataProviders;

class DataProviderBuilder
{
    public function build(): DataProviderInterface {
        return new Hafas();
    }
}
