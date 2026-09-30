<?php

namespace App\Services;

use App\Models\Rental;

class RentalService
{
    /**
     * Buat transaksi pemesanan / booking rental baru.
     */
    public function createBooking(array $data): Rental
    {
        return new Rental;
    }

    /**
     * Proses serah terima / pickup barang oleh penyewa.
     */
    public function processPickup(Rental $rental, array $unitAssignments): void
    {
        // Logika serah terima barang
    }

    /**
     * Proses pengembalian barang (return) dan kalkulasi denda (keterlambatan/kerusakan).
     */
    public function processReturn(Rental $rental, array $returnDetails): void
    {
        // Logika pengembalian & hitung denda
    }
}
