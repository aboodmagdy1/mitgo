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
        Schema::create('trip_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->tinyInteger('outcome')->nullable()->comment('0=pending, 1=accepted, 2=rejected');
            $table->timestamp('sent_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['trip_id', 'driver_id']);
            $table->index(['driver_id', 'sent_at']);
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_request_logs');
    }
};
