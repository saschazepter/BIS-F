<?php

namespace Tests\Feature\APIv1;

use App\Models\Trip;
use App\Models\User;
use App\Providers\AuthServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\ApiTestCase;

class CheckinTest extends ApiTestCase
{

    use RefreshDatabase;

    public function testOauthClientIdIsSavedOnApiCheckins(): void {
        $user  = User::factory()->create();
        $token = $user->createToken('token', array_keys(AuthServiceProvider::$scopes));
        $trip  = Trip::factory()->create();

        $response = $this->postJson(
            uri:     '/api/v1/trains/checkin',
            data:    [
                         'tripId'      => $trip->trip_id,
                         'lineName'    => $trip->linename,
                         'start'       => $trip->originStation->id,
                         'departure'   => $trip->departure,
                         'destination' => $trip->destinationStation->id,
                         'arrival'     => $trip->arrival,
                     ],
            headers: ['Authorization' => 'Bearer ' . $token->accessToken],
        );

        //assert that client id is an uuid
        $this->assertMatchesRegularExpression(
            pattern: '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            string:  $response->json('data.status.client.id')
        );
    }
}
