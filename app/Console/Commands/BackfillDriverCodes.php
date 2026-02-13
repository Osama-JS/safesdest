<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Driver;
use Illuminate\Support\Str;

class BackfillDriverCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drivers:backfill-codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate driver_code for existing drivers who do not have one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting driver code backfill...');

        $drivers = Driver::whereNull('driver_code')->get();

        $count = 0;
        foreach ($drivers as $driver) {
            $code = 'S' . str_pad($driver->id, 5, '0', STR_PAD_LEFT);

            // Bypass observer if needed, or just update directly
            // Using update() triggers saved events, but that's fine as the observer created() only runs on creation
            $driver->update(['driver_code' => $code]);

            $this->line("Updated Driver #{$driver->id}: {$driver->name} -> {$code}");
            $count++;
        }

        $this->info("Completed! Updated {$count} drivers.");
    }
}
