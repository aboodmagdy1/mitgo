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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->text('content');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('last_message_id')->nullable()->constrained('messages')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['last_message_id']);
            $table->dropColumn('last_message_id');
        });
        
        Schema::dropIfExists('messages');
    }
};
