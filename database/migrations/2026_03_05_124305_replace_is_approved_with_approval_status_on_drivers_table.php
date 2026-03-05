<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            // 0=pending, 1=in_progress, 2=approved, 3=rejected
            $table->unsignedTinyInteger('approval_status')->default(0)->after('is_approved');
            $table->index('approval_status');
        });

        // Migrate existing data: is_approved=true -> 2 (APPROVED), is_approved=false -> 0 (PENDING)
        DB::table('drivers')->where('is_approved', true)->update(['approval_status' => 2]);
        DB::table('drivers')->where('is_approved', false)->update(['approval_status' => 0]);

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn('is_approved');
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->boolean('is_approved')->default(false)->after('approval_status');
        });

        // Restore boolean from approval_status: 2 (APPROVED) -> true, everything else -> false
        DB::table('drivers')->where('approval_status', 2)->update(['is_approved' => true]);
        DB::table('drivers')->whereIn('approval_status', [0, 1, 3])->update(['is_approved' => false]);

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropIndex(['approval_status']);
            $table->dropColumn('approval_status');
        });
    }
};
