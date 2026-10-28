<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE item_units DROP CONSTRAINT IF EXISTS item_units_status_check');
            DB::statement("ALTER TABLE item_units ADD CONSTRAINT item_units_status_check CHECK (status::text = ANY (ARRAY['available'::character varying, 'rented'::character varying, 'maintenance'::character varying, 'retired'::character varying, 'lost'::character varying]::text[]))");
        } else {
            DB::statement("ALTER TABLE item_units MODIFY COLUMN status ENUM('available','rented','maintenance','retired','lost') NOT NULL DEFAULT 'available'");
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("UPDATE item_units SET status = 'retired' WHERE status = 'lost'");
            DB::statement('ALTER TABLE item_units DROP CONSTRAINT IF EXISTS item_units_status_check');
            DB::statement("ALTER TABLE item_units ADD CONSTRAINT item_units_status_check CHECK (status::text = ANY (ARRAY['available'::character varying, 'rented'::character varying, 'maintenance'::character varying, 'retired'::character varying]::text[]))");
        } else {
            DB::statement("UPDATE item_units SET status = 'retired' WHERE status = 'lost'");
            DB::statement("ALTER TABLE item_units MODIFY COLUMN status ENUM('available','rented','maintenance','retired') NOT NULL DEFAULT 'available'");
        }
    }
};
