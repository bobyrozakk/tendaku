<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index untuk query dashboard pendapatan (NTN-64): filter status/paid_at,
     * escrow, channel, dan agregasi komisi berdasarkan recognized_at.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['status', 'paid_at']);
            $table->index(['vendor_id', 'status', 'paid_at']);
            $table->index(['status', 'escrow_status']);
            $table->index('payment_channel');
        });

        Schema::table('platform_commissions', function (Blueprint $table) {
            $table->index(['status', 'recognized_at']);
            $table->index(['vendor_id', 'status', 'recognized_at']);
        });
    }

    public function down(): void
    {
        Schema::table('platform_commissions', function (Blueprint $table) {
            $table->dropIndex(['status', 'recognized_at']);
            $table->dropIndex(['vendor_id', 'status', 'recognized_at']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status', 'paid_at']);
            $table->dropIndex(['vendor_id', 'status', 'paid_at']);
            $table->dropIndex(['status', 'escrow_status']);
            $table->dropIndex(['payment_channel']);
        });
    }
};
