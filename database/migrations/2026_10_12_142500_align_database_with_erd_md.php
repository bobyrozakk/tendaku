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
        // 1. Tambah description pada item_categories (kategoriBarang di ERD MD)
        Schema::table('item_categories', function (Blueprint $table) {
            $table->text('description')->nullable()->after('slug');
        });

        // 2. Tambah bank_account dan qris_static_url pada vendor_payment_settings sesuai ERD MD
        Schema::table('vendor_payment_settings', function (Blueprint $table) {
            $table->text('bank_account')->nullable()->after('midtrans_client_key');
            $table->string('qris_static_url', 255)->nullable()->after('qris_image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_categories', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('vendor_payment_settings', function (Blueprint $table) {
            $table->dropColumn(['bank_account', 'qris_static_url']);
        });
    }
};
