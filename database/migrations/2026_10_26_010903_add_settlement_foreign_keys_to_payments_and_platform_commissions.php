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
        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('settlement_id')
                ->references('id')
                ->on('settlements')
                ->nullOnDelete();
        });

        Schema::table('platform_commissions', function (Blueprint $table) {
            $table->foreign('settlement_id')
                ->references('id')
                ->on('settlements')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_commissions', function (Blueprint $table) {
            $table->dropForeign(['settlement_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['settlement_id']);
        });
    }
};
