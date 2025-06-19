<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trip_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // scheduled, in_progress, completed, cancelled
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->foreignId('status_id')->constrained('trip_statuses');

            // Origin details
            $table->string('origin_address');
            $table->decimal('origin_latitude', 10, 7);
            $table->decimal('origin_longitude', 10, 7);

            // Destination details
            $table->string('destination_address');
            $table->decimal('destination_latitude', 10, 7);
            $table->decimal('destination_longitude', 10, 7);

            // Timing details
            $table->timestamp('scheduled_at'); // When the trip is scheduled to start
            $table->timestamp('started_at')->nullable(); // When the trip actually started
            $table->timestamp('completed_at')->nullable(); // When the trip was completed

            // Fare details
            $table->decimal('base_fare', 8, 2);
            $table->decimal('distance_fare', 8, 2)->default(0);
            $table->decimal('time_fare', 8, 2)->default(0);
            $table->decimal('total_fare', 8, 2);
            $table->decimal('distance_km', 8, 2)->nullable(); // Total distance in kilometers
            $table->integer('duration_minutes')->nullable(); // Total duration in minutes

            // Payment details
            $table->string('payment_method')->nullable();
            $table->boolean('is_paid')->default(false);

            // Rating
            $table->decimal('user_rating', 3, 2)->nullable(); // Rating given by user to driver
            $table->decimal('driver_rating', 3, 2)->nullable(); // Rating given by driver to user

            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index('user_id');
            $table->index('driver_id');
            $table->index('status_id');
            $table->index('scheduled_at');

            // Composite index for retrieving trip history for a user sorted by date
            $table->index(['user_id', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
        Schema::dropIfExists('trip_statuses');
    }
};
