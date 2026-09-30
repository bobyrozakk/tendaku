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
            $table->decimal('grand_total', 12, 2); // subtotal + delivery_fee - discount_amount

            $table->decimal('total_deposit', 12, 2)->default(0);
            $table->decimal('dp_required', 12, 2)->default(0);

            $table->decimal('total_late_fee', 12, 2)->default(0);
            $table->decimal('total_damage_fee', 12, 2)->default(0);
            $table->decimal('final_total', 12, 2)->nullable(); // terisi saat return

            $table->enum('deposit_status', ['pending', 'held', 'partially_refunded', 'refunded', 'forfeited'])
                ->default('pending');
            $table->decimal('deposit_refund_amount', 12, 2)->default(0);

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
