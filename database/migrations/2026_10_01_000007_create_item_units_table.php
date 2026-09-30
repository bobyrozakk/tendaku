<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_item_id')->constrained('master_items')->restrictOnDelete();
            // vendor_id didenormalisasi dari master_item agar query scoping tidak perlu join
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('unit_code', 50);
            $table->string('barcode', 100)->nullable();
            $table->enum('condition', ['excellent', 'good', 'fair', 'damaged', 'lost'])
                ->default('excellent');
            $table->enum('status', ['available', 'rented', 'maintenance', 'retired'])
                ->default('available');
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['vendor_id', 'unit_code']);
            $table->unique(['vendor_id', 'barcode']);
            $table->index(['vendor_id', 'master_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_units');
    }
};
