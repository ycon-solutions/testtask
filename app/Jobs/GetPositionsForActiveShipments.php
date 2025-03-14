<?php

namespace App\Jobs;

use App\Actions\FetchPositionFromApi;
use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GetPositionsForActiveShipments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /***
     * @return void
     */
    public function handle()
    {
        $fetchPosition = resolve(FetchPositionFromApi::class);
        try {

            $data = $fetchPosition();
            $positions = collect($data['positions']);
            $devices = $positions->pluck('deviceId')->unique();

            Shipment::where('status', Shipment::SHIPMENT_STATUS_ACTIVE)
                ->whereIn('gpsdevice_id', $devices)
                ->lazy(100)
                ->each(function (Shipment $shipment) use ($positions) {
                    $filteredPositions = $positions->where('deviceId', $shipment->gpsdevice_id)
                        ->map(fn ($position) => [
                            'longitude' => $position['lng'],
                            'latitude' => $position['lat'],
                            'utc_timestamp' => $position['time'],
                        ]);

                    $shipment->gpspositions()->createMany($filteredPositions);
                    Log::info("Successfully retrieved ". $filteredPositions->count() ." of ". $positions->count() ." positions for shipment $shipment->id");
                });

        } catch (RequestException $e) {
            Log::error("Failed to fetch positions, getting error: " . $e->getMessage());
        }

    }
}
