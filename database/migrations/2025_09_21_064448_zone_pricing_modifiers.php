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
        // fixed times each day 
        Schema::create('zone_pricing_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('zones')->onDelete('cascade');
            $table->decimal('multiplier',4,2)->default(0); // 20 % , 10 % , 5 % , 0 %
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zone_pricing_modifiers');
    }
};
