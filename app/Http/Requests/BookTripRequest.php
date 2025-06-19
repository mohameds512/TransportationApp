<?php

namespace App\Http\Requests;

use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookTripRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Assuming authentication is handled elsewhere
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'driver_id' => [
                'required',
                'exists:drivers,id',
                function ($attribute, $value, $fail) {
                    // Check if driver is available
                    $driver = Driver::find($value);
                    if (!$driver || !$driver->is_available) {
                        $fail('The selected driver is not available.');
                    }
                }
            ],
            'vehicle_type' => [
                'required',
                'exists:vehicle_types,name',
                function ($attribute, $value, $fail) {
                    // Check if driver has a vehicle of the requested type
                    $driverId = $this->input('driver_id');
                    $hasVehicleType = Vehicle::where('driver_id', $driverId)
                        ->whereHas('vehicleType', function ($query) use ($value) {
                            $query->where('name', $value);
                        })
                        ->where('is_active', true)
                        ->exists();

                    if (!$hasVehicleType) {
                        $fail('The selected driver does not have a vehicle of the requested type.');
                    }
                }
            ],
            'origin_address' => ['required', 'string', 'max:255'],
            'origin_latitude' => ['required', 'numeric', 'between:-90,90'],
            'origin_longitude' => ['required', 'numeric', 'between:-180,180'],
            'destination_address' => ['required', 'string', 'max:255'],
            'destination_latitude' => ['required', 'numeric', 'between:-90,90'],
            'destination_longitude' => ['required', 'numeric', 'between:-180,180'],
            'scheduled_at' => [
                'required',
                'date',
                'after_or_equal:now',
                function ($attribute, $value, $fail) {
                    // Check if driver has overlapping trips
                    $driverId = $this->input('driver_id');
                    $scheduledAt = new \DateTime($value);

                    // Estimate trip duration (30 minutes by default)
                    $estimatedDuration = $this->input('estimated_duration_minutes', 30);
                    $estimatedEndTime = (clone $scheduledAt)->modify("+{$estimatedDuration} minutes");

                    // Check for overlapping trips using a database-agnostic approach
                    $overlappingTrips = Trip::where('driver_id', $driverId)
                        ->where(function ($query) use ($scheduledAt, $estimatedEndTime) {
                            // Get all trips that might overlap
                            $query->where(function ($q) use ($scheduledAt, $estimatedEndTime) {
                                // Trip that starts before our trip starts and ends after our trip starts
                                $q->where('scheduled_at', '<=', $scheduledAt)
                                  ->whereNotNull('duration_minutes')
                                  ->where(function ($innerQ) use ($scheduledAt) {
                                      // Calculate end time in PHP to avoid database-specific date functions
                                      $innerQ->whereRaw('julianday(scheduled_at) + (duration_minutes / 1440.0) >= julianday(?)', [$scheduledAt]);
                                  });
                            })
                            // Trip that starts before our trip ends and ends after our trip ends
                            ->orWhere(function ($q) use ($scheduledAt, $estimatedEndTime) {
                                $q->where('scheduled_at', '<=', $estimatedEndTime)
                                  ->whereNotNull('duration_minutes')
                                  ->where(function ($innerQ) use ($estimatedEndTime) {
                                      $innerQ->whereRaw('julianday(scheduled_at) + (duration_minutes / 1440.0) >= julianday(?)', [$estimatedEndTime]);
                                  });
                            })
                            // Trip that starts after our trip starts and ends before our trip ends
                            ->orWhere(function ($q) use ($scheduledAt, $estimatedEndTime) {
                                $q->where('scheduled_at', '>=', $scheduledAt)
                                  ->whereNotNull('duration_minutes')
                                  ->where(function ($innerQ) use ($estimatedEndTime) {
                                      $innerQ->whereRaw('julianday(scheduled_at) + (duration_minutes / 1440.0) <= julianday(?)', [$estimatedEndTime]);
                                  });
                            });
                        })
                        ->whereHas('status', function ($query) {
                            $query->whereIn('name', ['scheduled', 'in_progress']);
                        })
                        ->count();

                    if ($overlappingTrips > 0) {
                        $fail('The selected driver has overlapping trips at the scheduled time.');
                    }
                }
            ],
            'estimated_duration_minutes' => ['sometimes', 'integer', 'min:1'],
            'payment_method' => ['required', 'string', Rule::in(['cash', 'credit_card', 'paypal'])],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'user',
            'driver_id' => 'driver',
            'vehicle_type' => 'vehicle type',
            'origin_address' => 'pickup address',
            'origin_latitude' => 'pickup latitude',
            'origin_longitude' => 'pickup longitude',
            'destination_address' => 'destination address',
            'destination_latitude' => 'destination latitude',
            'destination_longitude' => 'destination longitude',
            'scheduled_at' => 'scheduled time',
            'estimated_duration_minutes' => 'estimated duration',
            'payment_method' => 'payment method',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vehicle_type.exists' => 'The selected vehicle type is invalid.',
            'scheduled_at.after_or_equal' => 'The scheduled time must be in the future.',
        ];
    }
}
