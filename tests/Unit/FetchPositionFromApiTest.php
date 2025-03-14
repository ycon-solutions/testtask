<?php

namespace Tests\Unit;

use App\Actions\FetchPositionFromApi;
use App\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Tests\Support\MockPositionData;
use Tests\TestCase;

class FetchPositionFromApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the FetchPositionFromApi action correctly fetches positions from the API.
     *
     * @return void
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function test_action_fetches_positions_from_api()
    {
        Http::fake(function(Request $request) {
            return Http::response([
                'positions' => [],
                'key' => $request->data()['key'],
                'status' => 200
            ]);
        });

        $getPositions = new FetchPositionFromApi();
        $data = $getPositions();

        $this->assertIsArray($data);
        $this->assertEquals([
            'positions' => [],
            'key' => config('gpspositions.api_key'),
            'status' => 200
        ], $data);
    }

    /**
     * Test that an exception is thrown when the API request fails.
     *
     * @return void
     */
    public function test_action_throws_exception_when_api_request_fails() {
        Http::fake(function(Request $request) {
            return Http::response([
                'positions' => [],
                'key' => $request->data()['key'],
                'status' => 403
            ], 403);
        });

        $this->assertThrows(function() {
            $getPositions = new FetchPositionFromApi();
            $getPositions();
        }, RequestException::class);
    }

    /**
     * Test that the API returns a valid position structure.
     * Ensures that the response contains the required position attributes.
     *
     * @return void
     */
    public function test_action_fetches_valid_positions_structure_from_api() {
        Http::fake(function(Request $request) {
            $mockPositions = resolve(MockPositionData::class);

            return Http::response([
                ...$mockPositions->getMockData(minPositions:1),
                'key' => $request->data()['key'],
                'status' => 200
            ]);
        });

        $getPositions = new FetchPositionFromApi();
        $data = $getPositions();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('positions', $data);
        $this->assertNotEmpty($data['positions']);

        $this->assertArrayHasKey('lng', $data['positions'][0]);
        $this->assertArrayHasKey('lat', $data['positions'][0]);
        $this->assertArrayHasKey('time', $data['positions'][0]);
        $this->assertArrayHasKey('deviceId', $data['positions'][0]);
    }

    /**
     * Test that the fetched positions match those in shipments.
     * Ensures that positions returned from the API exist in the database shipments.
     *
     * @return void
     */
    public function test_action_fetches_positions_that_match_in_shipments() {
        $this->seed();

        Http::fake(function(Request $request) {
            $mockPositions = resolve(MockPositionData::class);

            return Http::response([
                ...$mockPositions->getMockData(5, 10, 100),
                'key' => $request->data()['key'],
                'status' => 200
            ]);
        });

        $getPositions = new FetchPositionFromApi();
        $data = $getPositions();

        $positions = collect($data['positions'])->pluck('deviceId')->unique();
        $shipmentsCount = Shipment::whereIn('gpsdevice_id', $positions)->count();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('positions', $data);
        $this->assertNotEmpty($data['positions']);
        $this->assertGreaterThanOrEqual(5, count($data['positions']));
        $this->assertLessThanOrEqual(10, count($data['positions']));

        $this->assertInstanceOf(Collection::class, $positions);
        $this->assertGreaterThan(0, $positions->count());
        $this->assertLessThanOrEqual(count($data['positions']), $positions->count());

        $this->assertEquals($positions->count(), $shipmentsCount);
    }
}