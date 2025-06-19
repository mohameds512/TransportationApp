<?php

namespace App\Services;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TripService
{
    /**
     * Get active trips for a driver with user details.
     *
     * Original slow query:
     * $driverTrips = Trip::where('driver_id', $driverId)
     *     ->where('status', 'in_progress')
     *     ->with('user')
     *     ->orderBy('created_at', 'desc')
     *     ->get();
     *
     * Optimized version using eager loading, query builder, and proper indexing.
     *
     * @param int $driverId
     * @param bool $useCache Whether to use cache
     * @param int $cacheTtl Cache TTL in seconds
     * @return Collection
     */
    public function getDriverActiveTrips(int $driverId, bool $useCache = true, int $cacheTtl = 300): Collection
    {
        $cacheKey = "driver:{$driverId}:active_trips";

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Using query builder for better performance
        // Join with trip_statuses to avoid N+1 query
        // Join with users to avoid N+1 query
        $trips = DB::table('trips')
            ->join('trip_statuses', 'trips.status_id', '=', 'trip_statuses.id')
            ->join('users', 'trips.user_id', '=', 'users.id')
            ->select([
                'trips.*',
                'users.name as user_name',
                'users.email as user_email',
                'trip_statuses.name as status_name'
            ])
            ->where('trips.driver_id', $driverId)
            ->where('trip_statuses.name', 'in_progress')
            ->orderBy('trips.created_at', 'desc')
            ->get();

        // Convert to Trip model collection for consistency with the rest of the application
        $tripModels = Trip::hydrate($trips->toArray());

        // Add user data to each trip
        foreach ($tripModels as $index => $trip) {
            $userData = (object) [
                'id' => $trips[$index]->user_id,
                'name' => $trips[$index]->user_name,
                'email' => $trips[$index]->user_email,
            ];

            $trip->setRelation('user', $userData);
        }

        if ($useCache) {
            Cache::put($cacheKey, $tripModels, $cacheTtl);
        }

        return $tripModels;
    }

    /**
     * Alternative implementation using Eloquent with optimized eager loading.
     * This is more readable but might be slightly slower than the raw query builder approach.
     *
     * @param int $driverId
     * @param bool $useCache Whether to use cache
     * @param int $cacheTtl Cache TTL in seconds
     * @return Collection
     */
    public function getDriverActiveTripsEloquent(int $driverId, bool $useCache = true, int $cacheTtl = 300): Collection
    {
        $cacheKey = "driver:{$driverId}:active_trips:eloquent";

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Using Eloquent with optimized eager loading
        $trips = Trip::with(['user' => function ($query) {
                // Select only the fields we need
                $query->select('id', 'name', 'email');
            }])
            ->select('trips.*')
            ->join('trip_statuses', 'trips.status_id', '=', 'trip_statuses.id')
            ->where('trips.driver_id', $driverId)
            ->where('trip_statuses.name', 'in_progress')
            ->orderBy('trips.created_at', 'desc')
            ->get();

        if ($useCache) {
            Cache::put($cacheKey, $trips, $cacheTtl);
        }

        return $trips;
    }

    /**
     * Get trip history for a user sorted by date.
     *
     * @param int $userId
     * @param bool $useCache Whether to use cache
     * @param int $cacheTtl Cache TTL in seconds
     * @return Collection
     */
    public function getUserTripHistory(int $userId, bool $useCache = true, int $cacheTtl = 300): Collection
    {
        $cacheKey = "user:{$userId}:trip_history";

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Using the composite index on user_id and scheduled_at
        $trips = Trip::with(['driver.user', 'vehicle', 'status'])
            ->where('user_id', $userId)
            ->orderBy('scheduled_at', 'desc')
            ->get();

        if ($useCache) {
            Cache::put($cacheKey, $trips, $cacheTtl);
        }

        return $trips;
    }

    /**
     * Invalidate cache for a driver's active trips.
     *
     * @param int $driverId
     * @return void
     */
    public function invalidateDriverActiveTripsCache(int $driverId): void
    {
        Cache::forget("driver:{$driverId}:active_trips");
        Cache::forget("driver:{$driverId}:active_trips:eloquent");
    }

    /**
     * Invalidate cache for a user's trip history.
     *
     * @param int $userId
     * @return void
     */
    public function invalidateUserTripHistoryCache(int $userId): void
    {
        Cache::forget("user:{$userId}:trip_history");
    }
}
