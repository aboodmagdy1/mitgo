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
        Schema::create('cashback_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->tinyInteger('type')->default(1); // 1: fixed amount, 2: percentage
            $table->decimal('amount', 10, 2);
            $table->decimal('max_cashback_amount', 10, 2)->nullable();
            $table->boolean('can_stack_with_coupon')->default(true);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('max_trips_per_user')->nullable();
            $table->integer('max_trips_global')->nullable();
            $table->integer('used_trips_global')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'start_date', 'end_date'], 'cashback_campaigns_active_window_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashback_campaigns');
    }
};

