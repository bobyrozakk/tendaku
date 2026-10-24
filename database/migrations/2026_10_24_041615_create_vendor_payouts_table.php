<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel vendor_payouts untuk pencairan dana ke vendor (§6.3, §9).
 * Maker/approver harus orang berbeda (§11). Semua jumlah dalam rupiah (int).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();

            // Nomor referensi unik untuk Midtrans Payouts (Iris)
            $table->string('reference_no', 100)->unique();

            // Jumlah sebelum potongan admin (SUM amount payments terkait)
            $table->unsignedInteger('gross_amount');
            // Total biaya admin dipotong dari payments terkait
            $table->unsignedInteger('platform_fee_total')->default(0);
            // Jumlah yang dikirim ke vendor (gross_amount - platform_fee_total)
            $table->unsignedInteger('net_amount');

            $table->enum('status', [
                'pending_approval',
                'approved',
                'processing',
                'completed',
                'failed',
                'rejected',
            ])->default('pending_approval');

            // Snapshot rekening penerima (agar audit tetap valid jika rekening berubah)
            $table->string('bank_code', 20);
            $table->string('account_name', 150);
            $table->string('account_last4', 4);    // 4 digit akhir nomor rekening (§11)

            // Dua orang berbeda: pembuat & penyetuju payout (§11, §9)
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['vendor_id', 'status']);
        });

        // Tambah FK dari payments.vendor_payout_id ke vendor_payouts setelah tabel dibuat
        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('vendor_payout_id')->references('id')->on('vendor_payouts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['vendor_payout_id']);
        });

        Schema::dropIfExists('vendor_payouts');
    }
};
