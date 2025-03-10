<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetTripRequest extends FormRequest
{
    public function rules(): array {
        return [
            'tripId'      => ['string'],
            'hafasTripId' => ['required_without:tripId', 'string'],
            'lineName'    => ['required_without:tripId', 'string'],
            'start'       => ['required_without:tripId', 'numeric', 'gt:0'],
        ];
    }
}
