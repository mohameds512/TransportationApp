<?php

namespace App\Services;

use App\Models\Driver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class DriverService
{
    /**
     * Get available drivers near a location.
     *
     * @param float $latitude
     * @param float $longitude
     * @param float $radius Distance in kilometers
     * @param bool $useCache Whether to use cache
     * @param int $cacheTtl Cache TTL in seconds
     * @return Collection
     */
    public function getAvailableDriversNear(
        float $latitude,
        float $longitude,
        float $radius = 5,
        bool $useCache = true,
        int $cacheTtl = 60
    ): Collection {
        // Create a cache key based on the location and radius
        // Round coordinates to 3 decimal places for better cache hit rate
        // (3 decimal places ≈ 111 meters precision)
        $lat = round($latitude, 3);
        $lng = round($longitude, 3);
        $cacheKey = "drivers:available:near:{$lat}:{$lng}:{$radius}";

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Get available drivers near the location
        $drivers = Driver::with(['user', 'vehicles'])
            ->available()
            ->nearby($latitude, $longitude, $radius)
            ->get();

        if ($useCache) {
            Cache::put($cacheKey, $drivers, $cacheTtl);
        }

        return $drivers;
    }

    /**
     * Get available drivers near a location using Redis geospatial features.
     * This is more efficient for high-frequency location updates.
     *
     * @param float $latitude
     * @param float $longitude
     * @param float $radius Distance in kilometers
     * @return Collection
     */
    public function getAvailableDriversNearRedis(float $latitude, float $longitude, float $radius = 5): Collection
    {
        // Get driver IDs within radius using Redis GEORADIUS
        $driverIds = Redis::command('GEORADIUS', [
            'drivers:locations',
            $longitude,
            $latitude,
            $radius,
            'km',
            'WITHDIST',
            'ASC'
        ]);

        if (empty($driverIds)) {
            return new Collection();
        }

        // Extract driver IDs from Redis response
        $driverIdsWithDistance = [];
        foreach ($driverIds as $driver) {
            $driverId = $driver[0];
            $distance = $driver[1];

            // Check if driver is available using Redis
            $isAvailable = (bool) Redis::hget("driver:{$driverId}:info", 'is_available');

            if ($isAvailable) {
                $driverIdsWithDistance[$driverId] = $distance;
            }
        }

        if (empty($driverIdsWithDistance)) {
            return new Collection();
        }

        // Get driver details from database
        $drivers = Driver::with(['user', 'vehicles'])
            ->whereIn('id', array_keys($driverIdsWithDistance))
            ->get();

        // Add distance to each driver
        foreach ($drivers as $driver) {
            $driver->distance = $driverIdsWithDistance[$driver->id];
        }

        // Sort by distance
        return $drivers->sortBy('distance');
    }

    /**
     * Update driver location in database and Redis.
     *
     * @param int $driverId
     * @param float $latitude
     * @param float $longitude
     * @return bool
     */
    public function updateDriverLocation(int $driverId, float $latitude, float $longitude): bool
    {
        // Update in database
        $updated = Driver::where('id', $driverId)
            ->update([
                'current_latitude' => $latitude,
                'current_longitude' => $longitude,
                'location_updated_at' => now(),
            ]);

        if (!$updated) {
            return false;
        }

        // Update in Redis
        Redis::command('GEOADD', [
            'drivers:locations',
            $longitude,
            $latitude,
            $driverId
        ]);

        // Invalidate nearby drivers cache
        $this->invalidateNearbyDriversCache();

        return true;
    }

    /**
     * Update driver availability in database and Redis.
     *
     * @param int $driverId
     * @param bool $isAvailable
     * @return bool
     */
    public function updateDriverAvailability(int $driverId, bool $isAvailable): bool
    {
        // Update in database
        $updated = Driver::where('id', $driverId)
            ->update([
                'is_available' => $isAvailable,
            ]);

        if (!$updated) {
            return false;
        }

        // Update in Redis
        Redis::hset("driver:{$driverId}:info", 'is_available', (int) $isAvailable);

        // Invalidate nearby drivers cache
        $this->invalidateNearbyDriversCache();

        return true;
    }

    /**
     * Invalidate all nearby drivers cache.
     *
     * @return void
     */
    public function invalidateNearbyDriversCache(): void
    {
        // Use Redis scan to find and delete all nearby drivers cache keys
        $iterator = null;
        do {
            $keys = Redis::scan($iterator, 'MATCH', 'drivers:available:near:*');

            if (!empty($keys)) {
                Redis::del(...$keys);
            }
        } while ($iterator > 0);
    }
}
