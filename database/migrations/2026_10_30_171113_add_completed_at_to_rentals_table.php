<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Waktu rental benar-benar selesai. Dipakai sebagai dasar periode batch
     * settlement agar pembayaran tidak hilang ketika rental melewati batas
     * minggu atau selesai setelah batch dibuat.
     */
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('returned_at');
            $table->index(['status', 'completed_at']);
        });

        // Backfill data lama: rental selesai memakai returned_at, atau
        // updated_at bila returned_at tidak tersedia.
        DB::table('rentals')
            ->where('status', 'completed')
            ->whereNull('completed_at')
            ->update([
                'completed_at' => DB::raw('COALESCE(returned_at, updated_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropIndex(['status', 'completed_at']);
            $table->dropColumn('completed_at');
        });
    }
};
