<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite di-bypass saat running phpunit / php artisan test
            return;
        }

        if ($driver === 'pgsql') {
            // PostgreSQL / Supabase
            DB::statement("ALTER TABLE item_units DROP CONSTRAINT IF EXISTS item_units_status_check");
            DB::statement("ALTER TABLE item_units ADD CONSTRAINT item_units_status_check CHECK (status IN ('available', 'rented', 'maintenance', 'retired', 'lost'))");
        } else {
            // MySQL / MariaDB
            DB::statement("ALTER TABLE item_units MODIFY COLUMN status ENUM('available','rented','maintenance','retired','lost') NOT NULL DEFAULT 'available'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE item_units DROP CONSTRAINT IF EXISTS item_units_status_check");
            DB::statement("ALTER TABLE item_units ADD CONSTRAINT item_units_status_check CHECK (status IN ('available', 'rented', 'maintenance', 'retired'))");
        } else {
            DB::statement("ALTER TABLE item_units MODIFY COLUMN status ENUM('available','rented','maintenance','retired') NOT NULL DEFAULT 'available'");
        }
    }
};