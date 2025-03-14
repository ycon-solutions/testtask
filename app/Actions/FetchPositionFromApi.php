<?php

namespace App\Actions;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class FetchPositionFromApi
{
    /**
     * @throws RequestException
     */
    public function __invoke()
    {
        $apiEndpoint = config('gpspositions.api_base_url') . 'positions';

        $response = Http::get($apiEndpoint, [
            'key' => config('gpspositions.api_key'),
        ]);

        // Throw an exception unless the response has a specific status code.
        $response->throwUnlessStatus(200);

        return $response->json();

    }


}