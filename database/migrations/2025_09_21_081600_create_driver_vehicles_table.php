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
        Schema::create('driver_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->integer('seats');
            $table->string('color');
            $table->string('license_number');
            $table->string('plate_number');
            $table->foreignId('vehicle_type_id')->nullable()->constrained('vehicle_types')->onDelete('cascade');
            $table->foreignId('vehicle_brand_model_id')->constrained('vehicle_brand_models')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_vehicles');
    }
};
