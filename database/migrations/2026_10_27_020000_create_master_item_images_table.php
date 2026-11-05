<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('master_item_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_item_id')->constrained('master_items')->cascadeOnDelete();
            $table->string('image_path', 255);
            $table->string('image_url', 255);
            $table->boolean('is_primary')->default(false);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['master_item_id', 'sort_order']);
            $table->index(['master_item_id', 'is_primary']);
        });

        // Backfill data foto yang sudah ada agar tidak hilang
        $existingItems = DB::table('master_items')
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->get();

        foreach ($existingItems as $item) {
            $path = Str::contains($item->image_url, '/storage/')
                ? Str::after($item->image_url, '/storage/')
                : $item->image_url;

            DB::table('master_item_images')->insert([
                'master_item_id' => $item->id,
                'image_path' => $path,
                'image_url' => $item->image_url,
                'is_primary' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_item_images');
    }
};
