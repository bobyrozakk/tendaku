<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('returned_at');
            $table->string('cancel_reason', 100)->nullable()->after('cancelled_at');
            $table->text('cancel_note')->nullable()->after('cancel_reason');
        });
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn(['cancelled_at', 'cancel_reason', 'cancel_note']);
        });
    }
};
