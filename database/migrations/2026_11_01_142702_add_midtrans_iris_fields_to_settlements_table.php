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
        Schema::table('settlements', function (Blueprint $table) {
            $table->string('iris_reference_no', 100)->nullable()->unique();
            $table->string('iris_status', 30)->nullable();
            $table->string('iris_idempotency_key', 100)->nullable()->unique();
            $table->string('iris_bank_code', 20)->nullable();
            $table->text('iris_error')->nullable();
            $table->timestamp('iris_requested_at')->nullable();
            $table->timestamp('iris_approved_at')->nullable();
            $table->timestamp('iris_completed_at')->nullable();

            $table->index('iris_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            $table->dropUnique(['iris_reference_no']);
            $table->dropUnique(['iris_idempotency_key']);
            $table->dropIndex(['iris_status']);
            $table->dropColumn([
                'iris_reference_no',
                'iris_status',
                'iris_idempotency_key',
                'iris_bank_code',
                'iris_error',
                'iris_requested_at',
                'iris_approved_at',
                'iris_completed_at',
            ]);
        });
    }
};
