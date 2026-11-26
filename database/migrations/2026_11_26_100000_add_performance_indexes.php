<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index tambahan untuk query yang paling sering dipakai halaman customer & kasir.
     *
     * - rentals(user_id, status): Riwayat Sewa (filter per user + status, urut id). Kolom FK user_id
     *   tidak otomatis ber-index di PostgreSQL.
     * - rentals(status, pickup_date, return_date): hitung ketersediaan stok (AvailabilityService)
     *   yang memfilter status + overlap tanggal tanpa menyertakan vendor_id.
     * - rentals(status, created_at): agregasi GMV dashboard admin (filter status + rentang created_at).
     * - rental_details(master_item_id, is_returned): join ketersediaan per master item.
     * - item_units(master_item_id, status): hitung unit kandidat per master item (index lama diawali vendor_id).
     * - vendors(status): filter vendor aktif di katalog publik (whereHas vendor).
     */
    private const INDEXES = [
        'rentals' => [
            'rentals_user_id_status_index' => ['user_id', 'status'],
            'rentals_status_pickup_return_index' => ['status', 'pickup_date', 'return_date'],
            'rentals_status_created_at_index' => ['status', 'created_at'],
        ],
        'rental_details' => [
            'rental_details_master_item_returned_index' => ['master_item_id', 'is_returned'],
        ],
        'item_units' => [
            'item_units_master_item_status_index' => ['master_item_id', 'status'],
        ],
        'vendors' => [
            'vendors_status_index' => ['status'],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                if (Schema::hasIndex($table, $name)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
                    $blueprint->index($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach (array_keys($indexes) as $name) {
                if (! Schema::hasIndex($table, $name)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($name) {
                    $blueprint->dropIndex($name);
                });
            }
        }
    }
};
