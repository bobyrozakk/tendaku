<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('rentals')->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->foreignId('received_by')->nullable()
                ->constrained('users')->nullOnDelete(); // null = otomatis via Midtrans

            $table->enum('payment_type', [
                'dp', 'settlement', 'deposit', 'deposit_refund', 'fine', 'refund',
            ]);
            $table->enum('payment_method', ['cash', 'midtrans_snap', 'transfer']);
            $table->decimal('amount', 12, 2);

            $table->string('midtrans_order_id', 100)->nullable()->unique();
            $table->string('midtrans_transaction_id', 100)->nullable();
            $table->string('midtrans_status', 50)->nullable();

            $table->string('payment_proof_url', 255)->nullable();
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index('rental_id');
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
