<?php

namespace App\Console\Commands;

use App\Jobs\GetPositionsForActiveShipments;
use Illuminate\Console\Command;

class FetchGpspositions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gpspositions:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches the current position of every active shipment';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            dispatch(new GetPositionsForActiveShipments());
            $this->info('Starting to retrieve positions for every active shipment!');
        }
        catch (\Exception $e) {
            $this->error('Error dispatching job: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
