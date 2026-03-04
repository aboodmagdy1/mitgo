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
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->onDelete('cascade'); // ربط بجدول الكوبونات
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // ربط بجدول المستخدمين
            $table->foreignId('trip_id')->nullable()->constrained('trips')->onDelete('set null'); // ربط بالرحلة (اختياري)
            $table->decimal('discount_amount', 10, 2); // مبلغ الخصم المطبق
            $table->timestamp('used_at'); // تاريخ ووقت الاستخدام
            $table->timestamps();

            // فهرس مركب لتحسين الأداء عند البحث عن استخدام المستخدم للكوبون
            $table->index(['coupon_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};