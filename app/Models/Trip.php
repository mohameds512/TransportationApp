<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trip extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'driver_id',
        'vehicle_id',
        'status_id',
        'origin_address',
        'origin_latitude',
        'origin_longitude',
        'destination_address',
        'destination_latitude',
        'destination_longitude',
        'scheduled_at',
        'started_at',
        'completed_at',
        'base_fare',
        'distance_fare',
        'time_fare',
        'total_fare',
        'distance_km',
        'duration_minutes',
        'payment_method',
        'is_paid',
        'user_rating',
        'driver_rating',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'origin_latitude' => 'decimal:7',
        'origin_longitude' => 'decimal:7',
        'destination_latitude' => 'decimal:7',
        'destination_longitude' => 'decimal:7',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'base_fare' => 'decimal:2',
        'distance_fare' => 'decimal:2',
        'time_fare' => 'decimal:2',
        'total_fare' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'duration_minutes' => 'integer',
        'is_paid' => 'boolean',
        'user_rating' => 'decimal:2',
        'driver_rating' => 'decimal:2',
    ];

    /**
     * Get the user that owns the trip.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the driver that owns the trip.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the vehicle that owns the trip.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the status that owns the trip.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(TripStatus::class, 'status_id');
    }

    /**
     * Scope a query to only include trips with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->whereHas('status', function ($query) use ($status) {
            $query->where('name', $status);
        });
    }

    /**
     * Scope a query to only include in-progress trips.
     */
    public function scopeInProgress($query)
    {
        return $this->scopeWithStatus($query, 'in_progress');
    }

    /**
     * Scope a query to only include completed trips.
     */
    public function scopeCompleted($query)
    {
        return $this->scopeWithStatus($query, 'completed');
    }

    /**
     * Scope a query to only include scheduled trips.
     */
    public function scopeScheduled($query)
    {
        return $this->scopeWithStatus($query, 'scheduled');
    }

    /**
     * Scope a query to only include cancelled trips.
     */
    public function scopeCancelled($query)
    {
        return $this->scopeWithStatus($query, 'cancelled');
    }

    /**
     * Check if the trip is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status->name === 'in_progress';
    }

    /**
     * Check if the trip is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status->name === 'completed';
    }

    /**
     * Check if the trip is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status->name === 'scheduled';
    }

    /**
     * Check if the trip is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status->name === 'cancelled';
    }
}
