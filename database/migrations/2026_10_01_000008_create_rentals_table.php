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
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('returned_at')->nullable();

            $table->enum('pickup_method', ['self_pickup', 'delivery'])->default('self_pickup');
            $table->text('delivery_address')->nullable();

            $table->unsignedInteger('rental_days');

            $table->decimal('subtotal', 12, 2);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2); // subtotal + delivery_fee - discount_amount (langsung lunas)

            // Jaminan Fisik KTP (Tanpa Deposit Uang)
            $table->string('ktp_collateral_photo_url', 255)->nullable(); // Foto penyewa memegang KTP & verifikasi wajah cocok
            $table->enum('ktp_collateral_status', ['pending', 'held', 'returned'])->default('pending'); // pending (sebelum pickup), held (KTP ditahan toko), returned (KTP dikembalikan)
            $table->timestamp('ktp_received_at')->nullable(); // Ditahan saat pickup
            $table->timestamp('ktp_returned_at')->nullable(); // Dikembalikan saat return
            $table->text('ktp_collateral_notes')->nullable();

            $table->decimal('total_late_fee', 12, 2)->default(0);
            $table->decimal('total_damage_fee', 12, 2)->default(0);
            $table->decimal('final_total', 12, 2)->nullable(); // grand_total + late_fee + damage_fee

            $table->enum('status', [
                'pending', 'confirmed', 'picked_up', 'returned', 'completed', 'cancelled', 'expired',
            ])->default('pending');

            $table->timestamp('expires_at')->nullable();
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
