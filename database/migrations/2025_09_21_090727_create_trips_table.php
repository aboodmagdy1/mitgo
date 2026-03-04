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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->integer('number')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('zone_id')->nullable()->constrained('zones')->onDelete('set null'); // Service zone      (pickup zone)
            $table->foreignId('vehicle_type_id')->nullable()->constrained('vehicle_types')->onDelete('set null'); // Vehicle type requested      
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->onDelete('set null');
            $table->tinyInteger('type')->default(1);
            $table->tinyInteger('status')->default(0);
            // attributes for scheduled trips
            $table->date('scheduled_date')->nullable(); // When trip is scheduled
            $table->time('scheduled_time')->nullable(); // What time
            $table->boolean('is_scheduled')->default(false);
            $table->timestamp('scheduled_at')->nullable(); // Combined datetime for easier queries
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->onDelete('set null');
           // Pickup location
            $table->decimal('pickup_lat', 10, 8)->nullable(); // Latitude (10 digits, 8 decimal places)
            $table->decimal('pickup_long', 11, 8)->nullable(); // Longitude (11 digits, 8 decimal places)
            $table->text('pickup_address')->nullable(); // Human-readable address


            // Destination location  
            $table->decimal('dropoff_lat', 10, 8)->nullable(); // Destination latitude
            $table->decimal('dropoff_long', 11, 8)->nullable(); // Destination longitude
            $table->text('dropoff_address')->nullable(); // Destination address


            $table->decimal('distance', 10, 2)->nullable(); // Distance in kilometers
            $table->integer('estimated_duration')->nullable();// in minutes
            $table->integer('actual_duration')->nullable();// calculated by timestamps ( )

            $table->foreignId('cancel_reason_id')->nullable()->constrained('cancel_trip_reasons')->onDelete('set null');
            $table->decimal('cancellation_fee', 10, 2)->nullable(); // If cancelled
            $table->decimal('waiting_fee', 10, 2)->default(0); // Driver waiting time fee
            $table->decimal('estimated_fare', 10, 2)->nullable(); // Pre-trip estimate
            $table->decimal('actual_fare', 10, 2)->nullable(); // Final calculated fare       
            $table->timestamps();
            // Indexes
            $table->index(['pickup_lat', 'pickup_long']);
            $table->index(['scheduled_at']);
            $table->index(['status', 'created_at']);
            $table->index(['driver_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
