<?php

namespace App\Jobs;

use App\Models\Driver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProcessDriverAvailability implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Processing driver availability');

        try {
            // Get all drivers with their current location
            $drivers = Driver::select(['id', 'is_available', 'current_latitude', 'current_longitude', 'location_updated_at'])
                ->whereNotNull('current_latitude')
                ->whereNotNull('current_longitude')
                ->get();

            // Process each driver
            foreach ($drivers as $driver) {
                // Skip drivers with outdated locations (more than 5 minutes old)
                if ($driver->location_updated_at && $driver->location_updated_at->diffInMinutes(now()) > 5) {
                    continue;
                }

                // Update driver location in Redis
                Redis::command('GEOADD', [
                    'drivers:locations',
                    $driver->current_longitude,
                    $driver->current_latitude,
                    $driver->id
                ]);

                // Update driver availability in Redis
                Redis::hset("driver:{$driver->id}:info", 'is_available', (int) $driver->is_available);
            }

            // Pre-calculate available drivers for popular locations
            $this->preCalculatePopularLocations();

            Log::info('Driver availability processed successfully');
        } catch (\Exception $e) {
            Log::error('Error processing driver availability: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Pre-calculate available drivers for popular locations.
     */
    private function preCalculatePopularLocations(): void
    {
        // This would typically come from a database or configuration
        $popularLocations = [
            // City center
            ['lat' => 40.7128, 'lng' => -74.0060],
            // Airport
            ['lat' => 40.6413, 'lng' => -73.7781],
            // Train station
            ['lat' => 40.7506, 'lng' => -73.9939],
            // Shopping mall
            ['lat' => 40.7516, 'lng' => -73.9755],
        ];

        foreach ($popularLocations as $location) {
            // Get driver IDs within 5km radius using Redis GEORADIUS
            $driverIds = Redis::command('GEORADIUS', [
                'drivers:locations',
                $location['lng'],
                $location['lat'],
                5,
                'km',
                'WITHDIST',
                'ASC'
            ]);

            if (empty($driverIds)) {
                continue;
            }

            // Extract available driver IDs from Redis response
            $availableDriverIds = [];
            foreach ($driverIds as $driver) {
                $driverId = $driver[0];
                $distance = $driver[1];

                // Check if driver is available using Redis
                $isAvailable = (bool) Redis::hget("driver:{$driverId}:info", 'is_available');

                if ($isAvailable) {
                    $availableDriverIds[$driverId] = $distance;
                }
            }

            // Cache the result for 1 minute
            $lat = round($location['lat'], 3);
            $lng = round($location['lng'], 3);
            $cacheKey = "drivers:available:near:{$lat}:{$lng}:5";

            if (!empty($availableDriverIds)) {
                // Get driver details from database
                $drivers = Driver::with(['user', 'vehicles'])
                    ->whereIn('id', array_keys($availableDriverIds))
                    ->get();

                // Add distance to each driver
                foreach ($drivers as $driver) {
                    $driver->distance = $availableDriverIds[$driver->id];
                }

                // Sort by distance
                $sortedDrivers = $drivers->sortBy('distance');

                // Cache the result
                Redis::setex($cacheKey, 60, serialize($sortedDrivers));
            }
        }
    }
}
