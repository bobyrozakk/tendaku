<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_shipping_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->unique()
                ->constrained('vendors')->cascadeOnDelete();
            $table->decimal('rate_per_km', 12, 2);
            $table->decimal('weight_threshold_kg', 8, 2);
            $table->decimal('extra_weight_fee_per_kg', 12, 2);
            $table->timestamps();
        });

        if (in_array(DB::getDriverName(), ['pgsql', 'mysql'], true)) {
            DB::statement(
                'ALTER TABLE vendor_shipping_settings ADD CONSTRAINT vendor_shipping_settings_non_negative_check '
                .'CHECK (rate_per_km >= 0 AND weight_threshold_kg >= 0 AND extra_weight_fee_per_kg >= 0)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_shipping_settings');
    }
};
