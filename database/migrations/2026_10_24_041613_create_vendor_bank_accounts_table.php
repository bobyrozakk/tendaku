<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel rekening bank vendor untuk keperluan payout (§6.2).
 * Menggantikan vendor_payment_settings sebagai tempat simpan rekening bank.
 * Nomor rekening terenkripsi; tampilkan hanya 4 digit akhir (§11).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();

            $table->string('bank_code', 20);       // kode bank (mis. BCA, BNI, BRI)
            $table->text('account_number');        // terenkripsi di model (§11)
            $table->string('account_name', 150);
            $table->boolean('is_primary')->default(false);   // rekening utama payout
            $table->boolean('is_verified')->default(false);  // wajib verified sebelum payout (§6.2)
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->index(['vendor_id', 'is_verified']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_bank_accounts');
    }
};
