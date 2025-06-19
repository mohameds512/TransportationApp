<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'driver_id',
        'vehicle_type_id',
        'license_plate',
        'make',
        'model',
        'year',
        'color',
        'is_active',
        'current_latitude',
        'current_longitude',
        'location_updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'year' => 'integer',
        'is_active' => 'boolean',
        'current_latitude' => 'decimal:7',
        'current_longitude' => 'decimal:7',
        'location_updated_at' => 'datetime',
    ];

    /**
     * Get the driver that owns the vehicle.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the vehicle type that the vehicle belongs to.
     */
    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    /**
     * Get the trips for the vehicle.
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Scope a query to only include active vehicles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to find vehicles near a location.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $latitude
     * @param float $longitude
     * @param float $radius Distance in kilometers
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNearby($query, $latitude, $longitude, $radius = 5)
    {
        // Using the Haversine formula to calculate distance
        $haversine = "(
            6371 * acos(
                cos(radians($latitude))
                * cos(radians(current_latitude))
                * cos(radians(current_longitude) - radians($longitude))
                + sin(radians($latitude))
                * sin(radians(current_latitude))
            )
        )";

        return $query
            ->selectRaw("*, {$haversine} AS distance")
            ->whereRaw("{$haversine} < ?", [$radius])
            ->orderBy('distance');
    }
}
