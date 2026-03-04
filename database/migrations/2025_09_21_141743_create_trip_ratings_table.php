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
        Schema::create('trip_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->tinyInteger('rating');

            $table->foreignId('rating_comment_id')->nullable()->constrained('rating_comments')->onDelete('cascade');
            $table->timestamps();

            $table->index(['driver_id','trip_id']);
            $table->index(['trip_id', 'user_id', 'driver_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_ratings');
    }
};
