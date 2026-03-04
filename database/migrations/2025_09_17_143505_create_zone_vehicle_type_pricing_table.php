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
        Schema::create('zone_vehicle_type_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('zones')->onDelete('cascade');
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->onDelete('cascade');
            $table->decimal('base_fare', 10, 2)->default(0);// رسوم فتح عداد 
            $table->decimal('fare_per_km', 10, 2)->default(0);
            $table->decimal('fare_per_minute', 10, 2)->default(0);
            $table->decimal('cancellation_fee', 10, 2)->default(0);
            $table->decimal('waiting_fee', 10, 2)->default(0);
            $table->decimal('extra_fare', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zone_vehicle_type_pricing');
    }
};
