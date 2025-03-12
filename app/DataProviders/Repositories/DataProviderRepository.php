<?php

declare(strict_types=1);

namespace App\DataProviders\Repositories;

use App\Enum\DataProvider;

class DataProviderRepository
{
    public function getUrlFromDataProvider(DataProvider $dataProvider): string {
        switch ($dataProvider) {
            case DataProvider::TRANSITOUS:
                return 'https://api.transitous.org/api/v1';
            case DataProvider::FAHRPLANBUERO:
                return 'http://localhost:8081/api/motis';
            default:
                return '';
        }
    }
}
