<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sesuaikan tabel payments dengan spesifikasi TENDAKU_payment_spec.md v1.0.
 *
 * Di Postgres, enum menggunakan CHECK constraint sehingga perlu di-drop sebelum update data.
 * Di SQLite (lingkungan testing), sintaks DROP/ADD CONSTRAINT tidak didukung.
 */
return new class extends Migration
{
    public function up(): void
    {
        $isPgsql = DB::getDriverName() === 'pgsql';

        if ($isPgsql) {
            // 1. Hapus CHECK constraint lama untuk payment_type agar bisa UPDATE data di Postgres
            DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_payment_type_check');

            // 2. Ubah data lama: 'settlement' → 'remaining' (D9)
            DB::statement("UPDATE payments SET payment_type = 'remaining' WHERE payment_type = 'settlement'");

            // 3. Tambah CHECK constraint baru untuk payment_type di Postgres
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_payment_type_check CHECK (payment_type IN ('full', 'dp', 'remaining', 'fine'))");

            // 4. Hapus CHECK constraint lama untuk status di Postgres
            DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check');

            // 5. Tambah CHECK constraint baru untuk status (tambah expired & cancelled)
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ('pending', 'paid', 'expired', 'cancelled', 'failed', 'refunded'))");
        } else {
            // Untuk SQLite (testing in-memory)
            DB::statement("UPDATE payments SET payment_type = 'remaining' WHERE payment_type = 'settlement'");
        }

        // 6. Tambah kolom-kolom baru
        Schema::table('payments', function (Blueprint $table) {
            // channel: midtrans / cash
            $table->string('channel', 20)->default('midtrans')->after('payment_method');

            // method spesifik dari webhook Midtrans atau 'cash'
            $table->string('method', 50)->nullable()->after('channel');

            // order_id: alias semantis untuk midtrans_order_id
            $table->string('order_id', 100)->nullable()->unique()->after('method');

            // snap_token: simpan untuk retry
            $table->string('snap_token', 255)->nullable()->after('midtrans_status');

            // platform_fee: biaya admin per pembayaran (int rupiah)
            $table->unsignedInteger('platform_fee')->default(0)->after('snap_token');

            // vendor_payout_id: FK ke vendor_payouts (dibuat setelah tabel vendor_payouts)
            $table->unsignedBigInteger('vendor_payout_id')->nullable()->after('platform_fee');

            // raw_notification: salinan mentah webhook Midtrans untuk audit
            $table->json('raw_notification')->nullable()->after('payment_proof_url');

            // expires_at: batas bayar (copy dari rentals.expires_at)
            $table->timestamp('expires_at')->nullable()->after('paid_at');

            $table->index('vendor_payout_id');
        });

        // 7. Salin data midtrans_order_id yang sudah ada ke order_id
        DB::statement('UPDATE payments SET order_id = midtrans_order_id WHERE midtrans_order_id IS NOT NULL AND order_id IS NULL');

        // 8. Ubah tipe amount menjadi integer (dari decimal) di Postgres
        if ($isPgsql) {
            DB::statement('ALTER TABLE payments ALTER COLUMN amount TYPE integer USING ROUND(amount)::integer');
            DB::statement('ALTER TABLE payments ALTER COLUMN amount SET NOT NULL');
        }
    }

    public function down(): void
    {
        $isPgsql = DB::getDriverName() === 'pgsql';

        if ($isPgsql) {
            DB::statement('ALTER TABLE payments ALTER COLUMN amount TYPE decimal(12,2) USING amount::decimal');
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['vendor_payout_id']);
            $table->dropColumn(['channel', 'method', 'order_id', 'snap_token', 'platform_fee', 'vendor_payout_id', 'raw_notification', 'expires_at']);
        });

        if ($isPgsql) {
            DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_payment_type_check');
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_payment_type_check CHECK (payment_type IN ('dp', 'settlement', 'deposit', 'deposit_refund', 'fine', 'refund'))");

            DB::statement("UPDATE payments SET payment_type = 'settlement' WHERE payment_type = 'remaining'");

            DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check');
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ('pending', 'paid', 'failed', 'refunded'))");
        }
    }
};
