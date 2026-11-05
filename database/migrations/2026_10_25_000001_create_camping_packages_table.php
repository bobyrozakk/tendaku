<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camping_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('slug', 160);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->unsignedSmallInteger('duration_days')->default(1);
            $table->unsignedSmallInteger('max_people')->nullable();
            $table->string('image_url', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['vendor_id', 'slug']);
            $table->index(['vendor_id', 'is_active']);
        });

        Schema::create('camping_package_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camping_package_id')->constrained('camping_packages')->cascadeOnDelete();
            $table->foreignId('master_item_id')->constrained('master_items')->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['camping_package_id', 'master_item_id'], 'camping_package_items_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camping_package_items');
        Schema::dropIfExists('camping_packages');
    }
};
