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
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();

            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['draft', 'approved', 'paid', 'cancelled'])->default('draft');

            $table->decimal('gross_qris', 14, 2)->default(0);
            $table->decimal('gross_fine_qris', 14, 2)->default(0);
            $table->decimal('commission_total', 14, 2)->default(0);
            $table->decimal('gateway_fee_total', 14, 2)->default(0);
            $table->decimal('fine_fee_total', 14, 2)->default(0);
            $table->decimal('cash_commission_offset', 14, 2)->default(0);
            $table->decimal('transfer_fee', 14, 2)->default(0);
            $table->decimal('adjustment_amount', 14, 2)->default(0);
            $table->decimal('net_payout', 14, 2)->default(0);

            // Snapshot rekening vendor agar histori tidak berubah saat data vendor diubah.
            $table->string('bank_name', 50)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_name', 150)->nullable();

            $table->string('reference_number', 100)->nullable();
            $table->string('proof_url', 255)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['vendor_id', 'period_start', 'period_end']);
            $table->index(['status', 'period_start']);
            $table->index(['vendor_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
