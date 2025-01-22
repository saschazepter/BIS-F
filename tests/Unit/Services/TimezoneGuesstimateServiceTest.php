<?php

namespace Tests\Unit\Services;

use App\Services\TimezoneGuesstimateService;
use Tests\Unit\UnitTestCase;

class TimezoneGuesstimateServiceTest extends UnitTestCase
{
    /**
     * @dataProvider providerTestFormatDomain
     */
    public function testFormatDomain($latitude, $longitude, $expected, $actual): void {
        $timezoneGuesstimateService = new TimezoneGuesstimateService();
        $result                     = $timezoneGuesstimateService->getTimezoneFromCoordinates($latitude, $longitude);

        $expectedTz = new \DateTimeZone($expected);
        $actualTz   = new \DateTimeZone($result);

        $time = new \DateTime('now', $expectedTz);

        $this->assertEquals($expectedTz->getOffset($time), $actualTz->getOffset($time));
    }

    public function getFromAPI($latitude, $longitude) {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://api.ipgeolocation.io/timezone?apiKey=5c52f98cc2e945e5a4ce532a34e4f923&&lat=' . $latitude . '&long=' . $longitude,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        return $response['timezone'];
    }

    public static function providerTestFormatDomain(): array {
        return
            [
                [
                    'latitude'  => 38.013671769939236,
                    'longitude' => -8.464789514534402,
                    'expected'  => 'Europe/Lisbon',
                    'actual'    => 'Europe/Lisbon',
                ],
                [
                    'latitude'  => 41.433565155156884,
                    'longitude' => -6.617280258883426,
                    'expected'  => 'Europe/Lisbon',
                    'actual'    => 'Europe/Madrid',
                ],
                [
                    'latitude'  => 39.30884153555121,
                    'longitude' => -4.031212830290997,
                    'expected'  => 'Europe/Madrid',
                    'actual'    => 'Europe/Madrid',
                ],
                [
                    'latitude'  => 42.75430585650494,
                    'longitude' => 0.9748757190639594,
                    'expected'  => 'Europe/Madrid',
                    'actual'    => 'Europe/Andorra',
                ],
                [
                    'latitude'  => 47.11825693507558,
                    'longitude' => 3.3198986981237226,
                    'expected'  => 'Europe/Paris',
                    'actual'    => 'Europe/Paris',
                ],
                [
                    'latitude'  => 49.29628372344689,
                    'longitude' => 2.12994595239806,
                    'expected'  => 'Europe/Paris',
                    'actual'    => 'Europe/Paris',
                ],
                [
                    'latitude'  => 51.020688324117856,
                    'longitude' => 0.38374932534637196,
                    'expected'  => 'Europe/London',
                    'actual'    => 'Europe/London',
                ],
                [
                    'latitude'  => 56.72835561101812,
                    'longitude' => -4.336323494696984,
                    'expected'  => 'Europe/London',
                    'actual'    => 'Europe/Isle_of_Man',
                ],
                [
                    'latitude'  => 58.5993965017752,
                    'longitude' => 8.220624440447352,
                    'expected'  => 'Europe/Oslo',
                    'actual'    => 'Europe/Oslo',
                ],
                [
                    'latitude'  => 52.45731625511294,
                    'longitude' => 11.340087647010648,
                    'expected'  => 'Europe/Berlin',
                    'actual'    => 'Europe/Berlin',
                ],
                [
                    'latitude'  => 47.773003203217286,
                    'longitude' => 8.649906030263253,
                    'expected'  => 'Europe/Berlin',
                    'actual'    => 'Europe/Busingen',
                ],
                [
                    'latitude'  => 44.831614791568086,
                    'longitude' => 9.382530464892284,
                    'expected'  => 'Europe/Rome',
                    'actual'    => 'Europe/Monaco',
                ],
                [
                    'latitude'  => 40.630028249895446,
                    'longitude' => 15.359302703413448,
                    'expected'  => 'Europe/Rome',
                    'actual'    => 'Europe/Rome',
                ],
                [
                    'latitude'  => 38.37438151143931,
                    'longitude' => 23.370789295328933,
                    'expected'  => 'Europe/Athens',
                    'actual'    => 'Europe/Athens',
                ],
                [
                    'latitude'  => 45.18054841334876,
                    'longitude' => 23.64620369030996,
                    'expected'  => 'Europe/Bucharest',
                    'actual'    => 'Europe/Bucharest',
                ],
                [
                    'latitude'  => 49.188929684576266,
                    'longitude' => 16.772415060090594,
                    'expected'  => 'Europe/Prague',
                    'actual'    => 'Europe/Vienna',
                ],
                [
                    'latitude'  => 52.04997307392921,
                    'longitude' => 20.23050404648052,
                    'expected'  => 'Europe/Warsaw',
                    'actual'    => 'Europe/Warsaw',
                ],
                [
                    'latitude'  => 67.67605285691795,
                    'longitude' => 27.008526945490473,
                    'expected'  => 'Europe/Helsinki',
                    'actual'    => 'Europe/Helsinki',
                ],
                [
                    'latitude'  => 67.623690209805,
                    'longitude' => 19.444350153086447,
                    'expected'  => 'Europe/Stockholm',
                    'actual'    => 'Europe/Mariehamn',
                ],
                [
                    'latitude'  => 55.87039512803469,
                    'longitude' => 13.60565997744149,
                    'expected'  => 'Europe/Stockholm',
                    'actual'    => 'Europe/Copenhagen',
                ],
                [
                    'latitude'  => 42.005504428444084,
                    'longitude' => 26.373739683722107,
                    'expected'  => 'Europe/Sofia',
                    'actual'    => 'Europe/Istanbul',
                ],
                [
                    'latitude'  => 41.80743887556602,
                    'longitude' => 26.982866652219627,
                    'expected'  => 'Europe/Istanbul',
                    'actual'    => 'Europe/Istanbul',
                ],
                [
                    'latitude'  => 40.89033132174296,
                    'longitude' => 29.4541552334245,
                    'expected'  => 'Europe/Istanbul',
                    'actual'    => 'Europe/Istanbul',
                ],
                [
                    'latitude'  => 37.552093683454245,
                    'longitude' => 35.52667909520608,
                    'expected'  => 'Europe/Istanbul',
                    'actual'    => 'Asia/Famagusta',
                ],
                [
                    'latitude'  => 60.20531311483174,
                    'longitude' => 24.882780299165233,
                    'expected'  => 'Europe/Helsinki',
                    'actual'    => 'Europe/Helsinki',
                ],
                [
                    'latitude'  => 64.15583965968324,
                    'longitude' => 29.783975646023436,
                    'expected'  => 'Europe/Helsinki',
                    'actual'    => 'Europe/Helsinki',
                ],
                [
                    'latitude'  => 68.55137390136994,
                    'longitude' => 25.156071963570184,
                    'expected'  => 'Europe/Helsinki',
                    'actual'    => 'Europe/Helsinki',
                ],
                [
                    'latitude'  => 65.55964887144734,
                    'longitude' => 22.08108280348128,
                    'expected'  => 'Europe/Stockholm',
                    'actual'    => 'Europe/Mariehamn',
                ],
                [
                    'latitude'  => 63.76302187500511,
                    'longitude' => 23.215011565512924,
                    'expected'  => 'Europe/Helsinki',
                    'actual'    => 'Europe/Helsinki',
                ],
            ];
    }
}
