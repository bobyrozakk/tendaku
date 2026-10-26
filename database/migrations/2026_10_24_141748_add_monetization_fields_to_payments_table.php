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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_channel', 30)->nullable()->after('payment_method');
            $table->decimal('gateway_fee_amount', 12, 2)->default(0)->after('amount');
            $table->enum('escrow_status', [
                'not_applicable', 'held', 'released', 'refunded', 'void',
            ])->default('not_applicable')->after('status');

            // Foreign key ditambahkan bersama migrasi settlements (NTN-67).
            $table->unsignedBigInteger('settlement_id')->nullable()->after('escrow_status');
            $table->index('settlement_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['settlement_id']);
            $table->dropColumn([
                'payment_channel',
                'gateway_fee_amount',
                'escrow_status',
                'settlement_id',
            ]);
        });
    }
};
