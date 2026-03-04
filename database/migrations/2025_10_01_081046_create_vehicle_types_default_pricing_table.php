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
        Schema::create('vehicle_types_default_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types');
            $table->decimal('base_fare', 10, 2);
            $table->decimal('fare_per_km', 10, 2);
            $table->decimal('fare_per_minute', 10, 2);
            $table->decimal('cancellation_fee', 10, 2);
            $table->decimal('waiting_fee', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_types_default_pricing');
    }
};
