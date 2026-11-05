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
        Schema::create('platform_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->unique()
                ->constrained('payments')->restrictOnDelete();
            $table->foreignId('rental_id')->constrained('rentals')->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();

            $table->decimal('base_amount', 14, 2);
            $table->decimal('rate', 5, 4);
            $table->decimal('amount', 14, 2);
            $table->enum('status', ['accrued', 'settled', 'void'])->default('accrued');

            // Foreign key ditambahkan bersama migrasi settlements (NTN-67).
            $table->unsignedBigInteger('settlement_id')->nullable();

            $table->timestamp('recognized_at')->useCurrent();
            $table->timestamp('voided_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index('rental_id');
            $table->index('settlement_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_commissions');
    }
};
