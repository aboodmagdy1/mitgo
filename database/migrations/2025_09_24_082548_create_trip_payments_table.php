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
        Schema::create('trip_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
            $table->foreignId('payment_method_id')->constrained('payment_methods')->onDelete('cascade');
            $table->decimal('commission_rate')->default(0); // commission rate in the time of this trip
            $table->decimal('commission_amount',10,2)->default(0); // commission amount in the time of this trip ( platform earning)
            $table->decimal('total_amount', 10, 2);
            $table->decimal('driver_earning', 10, 2);
            $table->tinyInteger('status')->default(0);
            $table->decimal('coupon_discount',10,2)->nullable();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->onDelete('set null');
            $table->decimal('additional_fees',10,2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_payments');
    }
};
