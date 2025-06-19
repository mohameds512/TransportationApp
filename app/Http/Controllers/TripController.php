<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookTripRequest;
use App\Models\Trip;
use App\Models\TripStatus;
use App\Models\Vehicle;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripController extends Controller
{
    protected TripService $tripService;

    public function __construct(TripService $tripService)
    {
        $this->tripService = $tripService;
    }

    /**
     * Book a new trip.
     *
     * @param BookTripRequest $request
     * @return JsonResponse
     */
    public function book(BookTripRequest $request): JsonResponse
    {
        try {
            // Start a database transaction
            DB::beginTransaction();

            // Get validated data
            $data = $request->validated();

            // Get the vehicle of the requested type
            $vehicle = Vehicle::where('driver_id', $data['driver_id'])
                ->whereHas('vehicleType', function ($query) use ($data) {
                    $query->where('name', $data['vehicle_type']);
                })
                ->where('is_active', true)
                ->first();

            if (!$vehicle) {
                return response()->json([
                    'message' => 'No available vehicle of the requested type found for this driver.',
                ], 422);
            }

            // Get the scheduled status
            $scheduledStatus = TripStatus::where('name', 'scheduled')->first();
            if (!$scheduledStatus) {
                return response()->json([
                    'message' => 'Trip status not found.',
                ], 500);
            }

            // Calculate fare (simplified for this example)
            $baseFare = 5.00; // Base fare in dollars
            $totalFare = $baseFare; // In a real app, this would be calculated based on distance, time, etc.

            // Create the trip
            $trip = Trip::create([
                'user_id' => $data['user_id'],
                'driver_id' => $data['driver_id'],
                'vehicle_id' => $vehicle->id,
                'status_id' => $scheduledStatus->id,
                'origin_address' => $data['origin_address'],
                'origin_latitude' => $data['origin_latitude'],
                'origin_longitude' => $data['origin_longitude'],
                'destination_address' => $data['destination_address'],
                'destination_latitude' => $data['destination_latitude'],
                'destination_longitude' => $data['destination_longitude'],
                'scheduled_at' => $data['scheduled_at'],
                'base_fare' => $baseFare,
                'total_fare' => $totalFare,
                'payment_method' => $data['payment_method'],
                'duration_minutes' => $data['estimated_duration_minutes'] ?? 30,
            ]);

            // Commit the transaction
            DB::commit();

            return response()->json([
                'message' => 'Trip booked successfully.',
                'trip' => $trip->load(['user', 'driver', 'vehicle', 'status']),
            ], 201);
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to book trip.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get trip details.
     *
     * @param Trip $trip
     * @return JsonResponse
     */
    public function show(Trip $trip): JsonResponse
    {
        return response()->json([
            'trip' => $trip->load(['user', 'driver', 'vehicle', 'status']),
        ]);
    }

    /**
     * Update trip status.
     *
     * @param Request $request
     * @param Trip $trip
     * @return JsonResponse
     */
    public function updateStatus(Request $request, Trip $trip): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'exists:trip_statuses,name'],
        ]);

        try {
            // Start a database transaction
            DB::beginTransaction();

            // Get the status
            $status = TripStatus::where('name', $request->status)->first();
            if (!$status) {
                return response()->json([
                    'message' => 'Trip status not found.',
                ], 422);
            }

            // Update the trip status
            $trip->status_id = $status->id;

            // Update timestamps based on status
            if ($request->status === 'in_progress') {
                $trip->started_at = now();
            } elseif ($request->status === 'completed') {
                $trip->completed_at = now();

                // Calculate actual duration and distance (simplified)
                if ($trip->started_at) {
                    $trip->duration_minutes = $trip->started_at->diffInMinutes(now());
                }

                // In a real app, you would calculate the actual distance based on GPS data
                // For this example, we'll just set a random value
                $trip->distance_km = rand(1, 20);

                // Recalculate fare based on actual duration and distance
                $distanceFare = $trip->distance_km * 1.5; // $1.50 per km
                $timeFare = $trip->duration_minutes * 0.5; // $0.50 per minute
                $trip->distance_fare = $distanceFare;
                $trip->time_fare = $timeFare;
                $trip->total_fare = $trip->base_fare + $distanceFare + $timeFare;
            }

            $trip->save();

            // Invalidate cache for this driver's active trips
            $this->tripService->invalidateDriverActiveTripsCache($trip->driver_id);

            // Invalidate cache for this user's trip history
            $this->tripService->invalidateUserTripHistoryCache($trip->user_id);

            // Commit the transaction
            DB::commit();

            return response()->json([
                'message' => 'Trip status updated successfully.',
                'trip' => $trip->load(['user', 'driver', 'vehicle', 'status']),
            ]);
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update trip status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get trip history for a user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function userHistory(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $trips = $this->tripService->getUserTripHistory($request->user_id);

        return response()->json([
            'trips' => $trips,
        ]);
    }

    /**
     * Get active trips for a driver.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function driverActiveTrips(Request $request): JsonResponse
    {
        $request->validate([
            'driver_id' => ['required', 'exists:drivers,id'],
        ]);

        $trips = $this->tripService->getDriverActiveTrips($request->driver_id);

        return response()->json([
            'trips' => $trips,
        ]);
    }
}
