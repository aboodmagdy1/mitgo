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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->longText('page_layout_ar');
            $table->longText('page_layout_en');
            $table->json('title');
            $table->string('slug');
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('enable_content_sync')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
