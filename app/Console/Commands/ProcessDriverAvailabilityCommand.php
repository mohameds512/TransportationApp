<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDriverAvailability;
use Illuminate\Console\Command;

class ProcessDriverAvailabilityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drivers:process-availability {--now : Dispatch the job immediately}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process driver availability and update Redis cache';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing driver availability...');

        if ($this->option('now')) {
            // Dispatch the job immediately
            ProcessDriverAvailability::dispatchSync();
            $this->info('Driver availability processed successfully.');
        } else {
            // Dispatch the job to the queue
            ProcessDriverAvailability::dispatch();
            $this->info('Driver availability job dispatched to the queue.');
        }

        return Command::SUCCESS;
    }
}
