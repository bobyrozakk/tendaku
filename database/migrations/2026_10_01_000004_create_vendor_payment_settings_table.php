<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_payment_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->unique()
                ->constrained('vendors')->cascadeOnDelete();
            $table->string('midtrans_merchant_id', 50)->nullable();
            $table->text('midtrans_server_key')->nullable();   // terenkripsi
            $table->string('midtrans_client_key', 100)->nullable();
            $table->boolean('is_midtrans_active')->default(false);
            $table->string('bank_name', 50)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_name', 150)->nullable();
            $table->string('qris_image_url', 255)->nullable();
            $table->text('wa_api_token')->nullable();           // terenkripsi
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payment_settings');
    }
};
