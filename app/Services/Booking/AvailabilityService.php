<?php

namespace App\Services;

use App\Models\ItemUnit;
use App\Models\Rental;
use App\Models\RentalDetail;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AvailabilityService
{
    /**
     * Hitung jumlah unit barang yang tersedia untuk rentang tanggal sewa tertentu.
     * Sisa stok adalah unit yang bisa disewa pada seluruh hari di rentang tanggal (inklusif pickup_date..return_date).
     *
     * @param  int  $masterItemId  ID master item perlengkapan camping
     * @param  string|DateTimeInterface  $startDate  Tanggal mulai sewa (pickup_date)
     * @param  string|DateTimeInterface  $endDate  Tanggal selesai sewa (return_date)
     * @param  int|null  $excludeRentalId  Opsional: ID rental yang dikecualikan (misal saat reschedule/edit booking)
     * @return int Jumlah unit fisik yang siap disewa
     */
    public function checkAvailability(
        int $masterItemId,
        string|DateTimeInterface $startDate,
        string|DateTimeInterface $endDate,
        ?int $excludeRentalId = null
    ): int {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        // 1. Stok kandidat = item_units milik master item dengan status != 'retired',
        //    condition NOT IN ('damaged', 'lost'), dan status bukan 'maintenance'.
        $totalCandidateUnits = $this->getCandidateUnitsQuery($masterItemId)->count();

        if ($totalCandidateUnits === 0) {
            return 0;
        }

        // 2. Cari pemakaian booking maksimum harian pada rentang [start, end] menggunakan query teragregasi.
        $maxOccupiedUnits = $this->getMaxOccupiedUnitsInRange($masterItemId, $start, $end, $excludeRentalId);

        return max(0, $totalCandidateUnits - $maxOccupiedUnits);
    }

    /**
     * Alias helper method untuk available units konkret saat assignment pickup.
     *
     * @return Collection<int, ItemUnit>
     */
    public function availableUnits(
        int $masterItemId,
        string|DateTimeInterface $startDate,
        string|DateTimeInterface $endDate,
        ?int $excludeRentalId = null
    ): Collection {
        return $this->getAvailableUnits($masterItemId, $startDate, $endDate, $excludeRentalId);
    }

    /**
     * Alias helper method untuk booked dates yang mengembalikan HANYA tanggal di mana sisa stok = 0.
     *
     * @return array<int, string>
     */
    public function bookedDates(
        int $masterItemId,
        string|DateTimeInterface|null $from = null,
        string|DateTimeInterface|null $to = null
    ): array {
        return $this->getBookedDates($masterItemId, $from, $to);
    }

    /**
     * Cek apakah kuantitas barang yang diinginkan tersedia untuk disewa.
     *
     * @param  int  $quantity  Jumlah unit yang ingin disewa
     * @return bool True jika stok mencukupi
     */
    public function isAvailable(
        int $masterItemId,
        int $quantity,
        string|DateTimeInterface $startDate,
        string|DateTimeInterface $endDate,
        ?int $excludeRentalId = null
    ): bool {
        if ($quantity <= 0) {
            return false;
        }

        return $this->checkAvailability($masterItemId, $startDate, $endDate, $excludeRentalId) >= $quantity;
    }

    /**
     * Ambil daftar unit fisik spesifik (ItemUnit) yang saat ini belum dialokasikan untuk rentang tanggal sewa.
     * Sangat berguna untuk admin vendor saat melakukan assign unit fisik pada proses pickup.
     *
     * @return Collection<int, ItemUnit>
     */
    public function getAvailableUnits(
        int $masterItemId,
        string|DateTimeInterface $startDate,
        string|DateTimeInterface $endDate,
        ?int $excludeRentalId = null
    ): Collection {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        return $this->getCandidateUnitsQuery($masterItemId)
            ->whereDoesntHave('rentalDetails', function ($query) use ($start, $end, $excludeRentalId) {
                $query->where('is_returned', false)
                    ->whereHas('rental', function ($rentalQuery) use ($start, $end, $excludeRentalId) {
                        $this->applyActiveRentalFilter($rentalQuery, $start, $end, $excludeRentalId);
                    });
            })
            ->get();
    }

    /**
     * Cek ketersediaan untuk banyak item sekaligus (misal keranjang checkout).
     *
     * @param  array<int, array{master_item_id: int, quantity: int}>  $items
     * @return array{all_available: bool, items: array<int, array{master_item_id: int, requested: int, available: int, is_available: bool}>}
     */
    public function checkBatchAvailability(
        array $items,
        string|DateTimeInterface $startDate,
        string|DateTimeInterface $endDate
    ): array {
        $allAvailable = true;
        $details = [];

        foreach ($items as $item) {
            $masterItemId = (int) $item['master_item_id'];
            $requested = (int) ($item['quantity'] ?? 1);
            $available = $this->checkAvailability($masterItemId, $startDate, $endDate);
            $isAvailable = $available >= $requested;

            if (! $isAvailable) {
                $allAvailable = false;
            }

            $details[] = [
                'master_item_id' => $masterItemId,
                'requested' => $requested,
                'available' => $available,
                'is_available' => $isAvailable,
            ];
        }

        return [
            'all_available' => $allAvailable,
            'items' => $details,
        ];
    }

    /**
     * Ambil daftar tanggal di mana sisa stok = 0 (bukan tanggal yang punya booking apa pun).
     *
     * @param  string|DateTimeInterface|null  $from  Tanggal awal rentang kalender (opsional, default hari ini)
     * @param  string|DateTimeInterface|null  $to  Tanggal akhir rentang kalender (opsional, default 30 hari ke depan)
     * @return array<int, string> Daftar tanggal unik format 'Y-m-d'
     */
    public function getBookedDates(
        int $masterItemId,
        string|DateTimeInterface|null $from = null,
        string|DateTimeInterface|null $to = null
    ): array {
        $today = Carbon::today();
        $startDate = $from !== null ? Carbon::parse($from)->startOfDay() : $today;
        $endDate = $to !== null ? Carbon::parse($to)->startOfDay() : $startDate->copy()->addDays(30);

        if ($endDate->lt($startDate)) {
            throw new InvalidArgumentException("Tanggal akhir ({$endDate->format('Y-m-d')}) tidak boleh lebih awal dari tanggal awal ({$startDate->format('Y-m-d')}).");
        }

        if ($startDate->lt($today)) {
            throw new InvalidArgumentException('Tanggal pencarian tidak boleh di masa lampau.');
        }

        $totalCandidateUnits = $this->getCandidateUnitsQuery($masterItemId)->count();
        if ($totalCandidateUnits === 0) {
            // Jika tidak ada unit yang dapat disewa sama sekali, semua tanggal habis stoknya
            $allDates = [];
            $period = CarbonPeriod::create($startDate, $endDate);
            foreach ($period as $date) {
                $allDates[] = $date->format('Y-m-d');
            }

            return $allDates;
        }

        // Ambil semua rental aktif yang overlap dengan rentang [startDate, endDate] dalam 1 query teragregasi
        $activeRentals = RentalDetail::query()
            ->join('rentals', 'rental_details.rental_id', '=', 'rentals.id')
            ->where('rental_details.master_item_id', $masterItemId)
            ->where('rental_details.is_returned', false)
            ->whereIn('rentals.status', ['confirmed', 'picked_up', 'pending'])
            ->where(function ($q) {
                $q->where('rentals.status', '!=', 'pending')
                    ->orWhereNull('rentals.expires_at')
                    ->orWhere('rentals.expires_at', '>=', now());
            })
            ->where('rentals.pickup_date', '<=', $endDate->format('Y-m-d'))
            ->where('rentals.return_date', '>=', $startDate->format('Y-m-d'))
            ->select([
                'rentals.pickup_date',
                'rentals.return_date',
                DB::raw('COUNT(rental_details.id) as unit_count'),
            ])
            ->groupBy('rentals.id', 'rentals.pickup_date', 'rentals.return_date')
            ->get();

        if ($activeRentals->isEmpty()) {
            return [];
        }

        // Hitung pemakaian unit per tanggal dalam rentang
        $dailyBookedCounts = [];
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            $dailyBookedCounts[$date->format('Y-m-d')] = 0;
        }

        foreach ($activeRentals as $rental) {
            $rentalStart = Carbon::parse($rental->pickup_date)->startOfDay();
            $rentalEnd = Carbon::parse($rental->return_date)->startOfDay();

            // Batasi dengan rentang [startDate, endDate]
            $overlapStart = $rentalStart->gt($startDate) ? $rentalStart : $startDate;
            $overlapEnd = $rentalEnd->lt($endDate) ? $rentalEnd : $endDate;

            $rentalPeriod = CarbonPeriod::create($overlapStart, $overlapEnd);
            foreach ($rentalPeriod as $date) {
                $dateKey = $date->format('Y-m-d');
                if (isset($dailyBookedCounts[$dateKey])) {
                    $dailyBookedCounts[$dateKey] += (int) $rental->unit_count;
                }
            }
        }

        $exhaustedDates = [];
        foreach ($dailyBookedCounts as $dateStr => $count) {
            if ($count >= $totalCandidateUnits) {
                $exhaustedDates[] = $dateStr;
            }
        }

        return $exhaustedDates;
    }

    /**
     * Ambil query dasar untuk kandidat unit yang layak sewa:
     * item_units milik master item dengan status != 'retired',
     * dan status bukan 'maintenance'.
     *
     * @return Builder<ItemUnit>
     */
    public function getCandidateUnitsQuery(int $masterItemId): Builder
    {
        return ItemUnit::query()
            ->withoutGlobalScope('vendor')
            ->where('master_item_id', $masterItemId)
            ->whereNotIn('status', ['maintenance', 'retired'])
            ->where('condition', '!=', 'lost');
    }

    /**
     * Hitung jumlah unit maksimum yang terpakai pada satu hari dalam rentang tanggal.
     * Menggunakan satu query teragregasi untuk mengambil data pemesanan, kemudian dianalisis per hari overlap.
     */
    private function getMaxOccupiedUnitsInRange(
        int $masterItemId,
        Carbon $start,
        Carbon $end,
        ?int $excludeRentalId = null
    ): int {
        $query = RentalDetail::query()
            ->join('rentals', 'rental_details.rental_id', '=', 'rentals.id')
            ->where('rental_details.master_item_id', $masterItemId)
            ->where('rental_details.is_returned', false)
            ->whereIn('rentals.status', ['confirmed', 'picked_up', 'pending'])
            ->where(function ($q) {
                $q->where('rentals.status', '!=', 'pending')
                    ->orWhereNull('rentals.expires_at')
                    ->orWhere('rentals.expires_at', '>=', now());
            })
            ->where('rentals.pickup_date', '<=', $end->format('Y-m-d'))
            ->where('rentals.return_date', '>=', $start->format('Y-m-d'));

        if ($excludeRentalId !== null) {
            $query->where('rentals.id', '!=', $excludeRentalId);
        }

        $activeRentals = $query
            ->select([
                'rentals.pickup_date',
                'rentals.return_date',
                DB::raw('COUNT(rental_details.id) as unit_count'),
            ])
            ->groupBy('rentals.id', 'rentals.pickup_date', 'rentals.return_date')
            ->get();

        if ($activeRentals->isEmpty()) {
            return 0;
        }

        // Hitung beban okupansi per hari dalam rentang [start, end]
        $dailyLoads = [];
        $period = CarbonPeriod::create($start, $end);
        foreach ($period as $date) {
            $dailyLoads[$date->format('Y-m-d')] = 0;
        }

        foreach ($activeRentals as $rental) {
            $rentalStart = Carbon::parse($rental->pickup_date)->startOfDay();
            $rentalEnd = Carbon::parse($rental->return_date)->startOfDay();

            $overlapStart = $rentalStart->gt($start) ? $rentalStart : $start;
            $overlapEnd = $rentalEnd->lt($end) ? $rentalEnd : $end;

            $rentalPeriod = CarbonPeriod::create($overlapStart, $overlapEnd);
            foreach ($rentalPeriod as $date) {
                $dateKey = $date->format('Y-m-d');
                if (isset($dailyLoads[$dateKey])) {
                    $dailyLoads[$dateKey] += (int) $rental->unit_count;
                }
            }
        }

        return empty($dailyLoads) ? 0 : max($dailyLoads);
    }

    /**
     * Filter query untuk rental aktif yang overlap dengan rentang tanggal.
     *
     * @param  Builder<Rental>  $rentalQuery
     */
    private function applyActiveRentalFilter(
        Builder $rentalQuery,
        Carbon $start,
        Carbon $end,
        ?int $excludeRentalId = null
    ): void {
        $rentalQuery->whereIn('status', ['pending', 'confirmed', 'picked_up'])
            ->where('pickup_date', '<=', $end->format('Y-m-d'))
            ->where('return_date', '>=', $start->format('Y-m-d'))
            ->where(function ($q) {
                $q->where('status', '!=', 'pending')
                    ->orWhereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });

        if ($excludeRentalId !== null) {
            $rentalQuery->where('id', '!=', $excludeRentalId);
        }
    }

    /**
     * Parse and validate date range.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function parseDateRange(string|DateTimeInterface $startDate, string|DateTimeInterface $endDate): array
    {
        $today = Carbon::today();
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            throw new InvalidArgumentException("Tanggal selesai sewa ({$end->format('Y-m-d')}) tidak boleh lebih awal dari tanggal mulai sewa ({$start->format('Y-m-d')}).");
        }

        if ($start->lt($today)) {
            throw new InvalidArgumentException("Tanggal mulai sewa ({$start->format('Y-m-d')}) tidak boleh merupakan tanggal lampau.");
        }

        return [$start, $end];
    }
}
