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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // كود الكوبون - فريد
            $table->string('name'); // اسم الكوبون
            $table->tinyInteger('type')->default(1); // نوع الكوبون: 1: نسبة مئوية, 2: مبلغ ثابت
            $table->decimal('amount', 10, 2); // قيمة الخصم (نسبة أو مبلغ ثابت)
            $table->decimal('max_discount_amount', 10, 2)->nullable(); // الحد الأقصى للخصم (مهم للنسبة المئوية)
            $table->date('start_date')->nullable(); // تاريخ البداية - اختياري
            $table->date('end_date')->nullable(); // تاريخ النهاية - اختياري
            $table->integer('total_usage_limit')->nullable(); // عدد مرات الاستخدام الكلي - اختياري
            $table->integer('usage_limit_per_user')->nullable(); // عدد مرات الاستخدام لكل مستخدم - اختياري
            $table->integer('used_count')->default(0); // عدد مرات الاستخدام الفعلي
            $table->boolean('is_active')->default(true); // حالة تفعيل الكوبون
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
