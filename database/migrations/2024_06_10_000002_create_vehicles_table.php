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
        // First, create a vehicle_types table for better normalization
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Sedan, SUV, etc.
            $table->integer('capacity')->default(4); // Number of passengers
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types');
            $table->string('license_plate')->unique();
            $table->string('make'); // Toyota, Honda, etc.
            $table->string('model'); // Camry, Civic, etc.
            $table->year('year'); // 2020, 2021, etc.
            $table->string('color');
            $table->boolean('is_active')->default(true);
            $table->decimal('current_latitude', 10, 7)->nullable();
            $table->decimal('current_longitude', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->timestamps();

            // Index for finding vehicles by type
            $table->index('vehicle_type_id');

            // Indexes for location-based queries
            if (config('database.default') !== 'sqlite') {
                $table->index('current_latitude');
                $table->index('current_longitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('vehicle_types');
    }
};
