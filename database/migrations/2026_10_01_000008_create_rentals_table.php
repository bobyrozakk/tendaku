<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete(); // customer, null jika guest

            // Jejak audit staf
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('picked_up_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('booking_code', 20)->unique();

            // Data guest (tanpa akun)
            $table->string('guest_name', 150)->nullable();
            $table->string('guest_phone', 20)->nullable();
            $table->text('guest_id_number')->nullable(); // terenkripsi

            $table->date('pickup_date');
            $table->date('return_date');
            $table->date('actual_return_date')->nullable(); // tanggal aktual pengembalian (dari ERD)
            $table->timestamp('picked_up_at')->nullable();  // BARU: waktu presisi pickup
            $table->timestamp('returned_at')->nullable();   // BARU: waktu presisi return

            $table->enum('pickup_method', ['self_pickup', 'delivery'])->default('self_pickup');
            $table->text('delivery_address')->nullable();

            $table->unsignedInteger('rental_days');

            $table->decimal('subtotal', 12, 2);
            $table->decimal('delivery_fee', 12, 2)->default(0);       // BARU: biaya ongkir
            $table->decimal('discount_amount', 12, 2)->default(0);    // BARU: diskon
            $table->decimal('total_deposit', 12, 2)->default(0);      // total uang jaminan (dari ERD)
            $table->decimal('dp_amount', 12, 2)->default(0);          // uang muka DP (dari ERD)
            $table->decimal('grand_total', 12, 2);                    // total tagihan akhir

            // BARU: Jaminan Fisik KTP (tambahan codebase)
            $table->string('ktp_collateral_photo_url', 255)->nullable();
            $table->enum('ktp_collateral_status', ['pending', 'held', 'returned'])->default('pending');
            $table->timestamp('ktp_received_at')->nullable();
            $table->timestamp('ktp_returned_at')->nullable();
            $table->text('ktp_collateral_notes')->nullable();

            $table->decimal('total_late_fee', 12, 2)->default(0);
            $table->decimal('total_damage_fee', 12, 2)->default(0);
            $table->decimal('final_total', 12, 2)->nullable();        // BARU: grand_total + denda

            $table->enum('status', [
                'pending', 'confirmed', 'picked_up', 'returned', 'completed', 'cancelled', 'expired',
            ])->default('pending');

            $table->timestamp('expires_at')->nullable();              // BARU: batas waktu bayar
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['vendor_id', 'status', 'pickup_date']);
            $table->index(['vendor_id', 'return_date']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
