<?php

namespace Tests\Feature;

use App\Jobs\GetPositionsForActiveShipments;
use App\Models\Gpsposition;
use App\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Support\MockPositionData;
use Tests\TestCase;


class GetPositionsForActiveShipmentsTest extends TestCase
{
    use RefreshDatabase;


    /**
     * Test that the job fetches GPS positions and processes active shipments correctly.
     *
     * This test:
     * - Mocks an API response to simulate fetching GPS positions.
     * - Fakes the queue to prevent actual job execution.
     * - Seeds the database to ensure test data is available.
     * - Dispatches and runs the `GetPositionsForActiveShipments` job.
     * - Asserts that active shipments exist in the database.
     * - Verifies that GPS positions are only associated with active shipments.
     *
     * @return void
     */
    public function test_job_fetches_positions_and_processes_shipments()
    {

        Http::fake(function(Request $request) {

            $mockPositions = resolve(MockPositionData::class);

            return Http::response([
                ...$mockPositions->getMockData(1, 100, 70),
                'key' => $request->data()['key'],
                'status' => 200
            ]);
        });

        Queue::fake();

        $this->seed();
       $job = new GetPositionsForActiveShipments();
       $job->handle();

       $activeShipmentIds = Shipment::where('status', Shipment::SHIPMENT_STATUS_ACTIVE)
           ->pluck('id')
           ->toArray();

       $gpsShipmentsIds = Gpsposition::pluck('shipment_id')
           ->unique()
           ->toArray();


        $this->assertDatabaseHas('shipments', [
            'status' => Shipment::SHIPMENT_STATUS_ACTIVE
        ]);
        $this->assertEmpty(array_diff($gpsShipmentsIds, $activeShipmentIds));

    }

    /**
     * Test that the job does not process shipments that are not active.
     *
     * This test:
     * - Mocks an API response to simulate GPS position fetching.
     * - Fakes the queue to prevent actual job execution.
     * - Creates a shipment with the status "delivered" (non-active).
     * - Dispatches and runs the `GetPositionsForActiveShipments` job.
     * - Ensures no GPS positions are assigned to the delivered shipment.
     * - Asserts that the database does not contain non-active shipments.
     *
     * @return void
     */
    public function test_job_does_not_process_non_active_shipments()
    {
        Http::fake(function(Request $request) {
            $mockPositions = resolve(MockPositionData::class);
            return Http::response([
                ...$mockPositions->getMockData(1, 100, 100),
                'key' => $request->data()['key'],
                'status' => 200
            ]);
        });

        Queue::fake();

        $deliveredShipment = Shipment::factory()->create(['status' => Shipment::SHIPMENT_STATUS_DELIVERED]);

        $job = new GetPositionsForActiveShipments();
        $job->handle();

        $positionsForDeliveredShipment = $deliveredShipment->gpspositions;

        $this->assertDatabaseMissing('shipments', [
            'status' => [Shipment::SHIPMENT_STATUS_ACTIVE, Shipment::SHIPMENT_STATUS_DELIVERED]
        ]);

        $this->assertEmpty($positionsForDeliveredShipment);
    }

}
