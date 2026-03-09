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
        Schema::create('cashback_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashback_campaign_id')
                ->constrained('cashback_campaigns')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('trip_id')
                ->nullable()
                ->constrained('trips')
                ->onDelete('set null');
            $table->decimal('cashback_amount', 10, 2);
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->dateTime('awarded_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'cashback_campaign_id'], 'cashback_usages_user_campaign_index');
            $table->index('cashback_campaign_id', 'cashback_usages_campaign_index');
            $table->index('trip_id', 'cashback_usages_trip_index');
            $table->unique('trip_id', 'cashback_usages_unique_trip'); // ensure at most one cashback per trip
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashback_usages');
    }
};

