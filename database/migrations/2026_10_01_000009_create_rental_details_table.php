<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('rentals')->restrictOnDelete();
            $table->foreignId('master_item_id')->constrained('master_items')->restrictOnDelete();

            // Nullable: unit fisik baru ditetapkan saat pickup, bukan saat booking online
            $table->foreignId('item_unit_id')->nullable()
                ->constrained('item_units')->nullOnDelete();

            $table->decimal('daily_rate_snapshot', 10, 2);
            $table->decimal('late_fee_per_day_snapshot', 10, 2)->default(0);

            $table->enum('condition_before', ['excellent', 'good', 'fair', 'damaged'])->nullable();
            $table->enum('condition_after', ['excellent', 'good', 'fair', 'damaged', 'lost'])->nullable();
            $table->text('checklist_notes')->nullable();

            $table->timestamp('returned_at')->nullable();
            $table->unsignedInteger('late_days')->default(0);
            $table->decimal('late_fee', 10, 2)->default(0);
            $table->decimal('damage_fee', 10, 2)->default(0);

            $table->timestamps();

            $table->index('rental_id');
            $table->index('master_item_id');
            $table->index('item_unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_details');
    }
};
