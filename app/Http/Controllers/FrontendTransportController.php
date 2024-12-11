<?php

namespace App\Http\Controllers;

use App\DataProviders\Hafas;
use App\Exceptions\HafasException;
use App\Http\Controllers\TransportController as TransportBackend;
use Illuminate\Http\JsonResponse;

/**
 * @deprecated Content will be moved to the backend/frontend/API packages soon, please don't add new functions here!
 */
class FrontendTransportController extends Controller
{
    public function TrainAutocomplete(string $station): JsonResponse {
        try {
            //todo: adapt data provider to users preferences
            $provider                  = new TransportBackend(Hafas::class);
            $trainAutocompleteResponse = $provider->getTrainStationAutocomplete($station);
            return response()->json($trainAutocompleteResponse);
        } catch (HafasException $e) {
            abort(503, $e->getMessage());
        }
    }
}
