<?php

namespace Tests\Support;

use App\Models\Shipment;

class MockPositionData
{
    public function getMockData(int $minPositions = 0, int $maxPositions = 5, int $chanceOfGettingExistingDeviceId = 10): array
    {

        $data = [
            "positions" => [],
            "tempZ1" => [
                "tSet" => fake()->randomFloat(1, -20, 30),
                "tDch" => fake()->randomFloat(2, -10, 40),
                "tRet" => fake()->optional()->randomFloat(2, -10, 40)
            ],
            "driver1ID" => "dr" . fake()->numberBetween(1, 99),
            "driver2ID" => "dr" . fake()->numberBetween(1, 99),
            "fuelLevel" => fake()->numberBetween(0, 100),
            "fuelLevelTank1" => fake()->numberBetween(0, 50),
            "fuelLevelTank2" => fake()->numberBetween(0, 50),
            "canFuelCounter" => fake()->numberBetween(10000, 50000),
            "axis1Weight" => fake()->numberBetween(20000, 30000),
            "axis2Weight" => fake()->numberBetween(20, 50)
        ];

        $positionsCount = fake()->numberBetween($minPositions, $maxPositions);
        for($i = 0; $i < $positionsCount; $i++) {
            $data['positions'][] = $this->createPosition($chanceOfGettingExistingDeviceId);
        }

        return $data;
    }
    
    public function createPosition(int $chanceOfGettingTrue = 10): array {

        //Getting a shipment gpsdevice_id randomly with at least 10% chance to get an existing device ID.
        $deviceId = fake()->boolean($chanceOfGettingTrue) ?
            Shipment::inRandomOrder()->first()?->gpsdevice_id :
            null;

        $deviceId ??= "dev" . fake()->numberBetween(1, 50);

        return [
            "engineRPM" => fake()->optional()->numberBetween(0, 2500),
            "odometerValue" => fake()->randomFloat(1, 100000, 500000),
            "odometerValueRaw" => fake()->numberBetween(10000000, 50000000),
            "lng" => fake()->longitude,
            "heading" => fake()->numberBetween(0, 359),
            "time" => now()->timestamp * 1000,
            "deviceId" => $deviceId,
            "vehRegNumber" => strtoupper(fake()->bothify('??-####')),
            "ignitionState" => fake()->randomElement(["ON", "OFF"]),
            "speed" => fake()->numberBetween(0, 130),
            "lat" => fake()->latitude
        ];
    }
}