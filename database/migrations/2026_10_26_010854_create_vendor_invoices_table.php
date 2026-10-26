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
        Schema::create('vendor_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->foreignId('settlement_id')->nullable()->constrained('settlements')->nullOnDelete();

            $table->string('number', 60)->unique();
            $table->enum('type', ['commission', 'settlement'])->default('settlement');
            $table->date('period_start');
            $table->date('period_end');

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('commission_amount', 14, 2)->default(0);
            $table->decimal('fee_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            $table->enum('status', ['draft', 'issued', 'paid', 'void'])->default('draft');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index(['period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_invoices');
    }
};
