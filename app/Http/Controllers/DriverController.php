<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Services\DriverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    protected DriverService $driverService;

    public function __construct(DriverService $driverService)
    {
        $this->driverService = $driverService;
    }

    /**
     * Get available drivers near a location.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function availableDriversNear(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['sometimes', 'numeric', 'min:0.1', 'max:50'],
            'vehicle_type' => ['sometimes', 'exists:vehicle_types,name'],
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->radius ?? 5;
        $vehicleType = $request->vehicle_type;

        // Use Redis for better performance if available and the Redis extension is installed
        if (config('database.redis.client') !== 'predis' && extension_loaded('redis')) {
            $drivers = $this->driverService->getAvailableDriversNearRedis($latitude, $longitude, $radius);
        } else {
            $drivers = $this->driverService->getAvailableDriversNear($latitude, $longitude, $radius);
        }

//        dd($drivers);
        // Filter by vehicle type if specified
        if ($vehicleType) {
            $drivers = $drivers->filter(function ($driver) use ($vehicleType) {
                return $driver->vehicles->contains(function ($vehicle) use ($vehicleType) {
                    return $vehicle->vehicleType->name === $vehicleType && $vehicle->is_active;
                });
            });
        }

        return response()->json([
            'drivers' => $drivers->values(),
            'count' => $drivers->count(),
        ]);
    }

    /**
     * Update driver location.
     *
     * @param Request $request
     * @param Driver $driver
     * @return JsonResponse
     */
    public function updateLocation(Request $request, Driver $driver): JsonResponse
    {
        $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $updated = $this->driverService->updateDriverLocation(
            $driver->id,
            $request->latitude,
            $request->longitude
        );

        if (!$updated) {
            return response()->json([
                'message' => 'Failed to update driver location.',
            ], 500);
        }

        return response()->json([
            'message' => 'Driver location updated successfully.',
            'driver' => $driver->fresh(),
        ]);
    }

    
    public function updateAvailability(Request $request, Driver $driver): JsonResponse
    {
        $request->validate([
            'is_available' => ['required', 'boolean'],
        ]);

        $updated = $this->driverService->updateDriverAvailability(
            $driver->id,
            $request->is_available
        );

        if (!$updated) {
            return response()->json([
                'message' => 'Failed to update driver availability.',
            ], 500);
        }

        return response()->json([
            'message' => 'Driver availability updated successfully.',
            'driver' => $driver->fresh(),
        ]);
    }

    /**
     * Get driver details.
     *
     * @param Driver $driver
     * @return JsonResponse
     */
    public function show(Driver $driver): JsonResponse
    {
        return response()->json([
            'driver' => $driver->load(['user', 'vehicles.vehicleType']),
        ]);
    }
}
