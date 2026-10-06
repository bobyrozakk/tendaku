<?php

namespace App\Services;

use App\Models\ItemUnit;
use App\Models\Rental;
use App\Models\RentalDetail;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class AvailabilityService
{
    /**
     * Hitung jumlah unit barang yang tersedia untuk rentang tanggal sewa tertentu.
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

        // 1. Hitung total unit fisik yang layak sewa (bukan dalam maintenance, retired, atau hilang)
        $totalServiceableUnits = ItemUnit::query()
            ->where('master_item_id', $masterItemId)
            ->whereNotIn('status', ['maintenance', 'retired'])
            ->where('condition', '!=', 'lost')
            ->count();

        if ($totalServiceableUnits === 0) {
            return 0;
        }

        // 2. Hitung jumlah unit yang sedang ter-booking pada rentang tanggal overlap
        $bookedUnitsCount = $this->getOccupiedCount($masterItemId, $start, $end, $excludeRentalId);

        return max(0, $totalServiceableUnits - $bookedUnitsCount);
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

        return ItemUnit::query()
            ->where('master_item_id', $masterItemId)
            ->whereNotIn('status', ['maintenance', 'retired'])
            ->where('condition', '!=', 'lost')
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
     * Ambil daftar tanggal (format Y-m-d) di mana item memiliki reservasi aktif (untuk kalender produk).
     *
     * @param  string|DateTimeInterface|null  $from  Tanggal awal rentang kalender (opsional)
     * @param  string|DateTimeInterface|null  $to  Tanggal akhir rentang kalender (opsional)
     * @return array<int, string> Daftar tanggal unik format 'Y-m-d'
     */
    public function getBookedDates(
        int $masterItemId,
        string|DateTimeInterface|null $from = null,
        string|DateTimeInterface|null $to = null
    ): array {
        $query = RentalDetail::query()
            ->where('master_item_id', $masterItemId)
            ->where('is_returned', false)
            ->whereHas('rental', function ($rentalQuery) use ($from, $to) {
                $rentalQuery->whereIn('status', ['pending', 'confirmed', 'picked_up'])
                    ->where(function ($q) {
                        $q->where('status', '!=', 'pending')
                            ->orWhereNull('expires_at')
                            ->orWhere('expires_at', '>=', now());
                    });

                if ($from !== null) {
                    $fromDate = Carbon::parse($from)->format('Y-m-d');
                    $rentalQuery->where('return_date', '>=', $fromDate);
                }

                if ($to !== null) {
                    $toDate = Carbon::parse($to)->format('Y-m-d');
                    $rentalQuery->where('pickup_date', '<=', $toDate);
                }
            })
            ->with(['rental:id,pickup_date,return_date']);

        $bookedDates = [];

        foreach ($query->get() as $detail) {
            if (! $detail->rental) {
                continue;
            }

            $current = Carbon::parse($detail->rental->pickup_date);
            $end = Carbon::parse($detail->rental->return_date);

            while ($current->lte($end)) {
                $bookedDates[] = $current->format('Y-m-d');
                $current->addDay();
            }
        }

        return array_values(array_unique($bookedDates));
    }

    /**
     * Ambil daftar tanggal di mana stok unit barang habis total (0 unit tersedia).
     *
     * @return array<int, string>
     */
    public function getFullyBookedDates(
        int $masterItemId,
        string|DateTimeInterface $startDate,
        string|DateTimeInterface $endDate
    ): array {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        $fullyBooked = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $dateStr = $cursor->format('Y-m-d');
            if ($this->checkAvailability($masterItemId, $dateStr, $dateStr) <= 0) {
                $fullyBooked[] = $dateStr;
            }
            $cursor->addDay();
        }

        return $fullyBooked;
    }

    /**
     * Hitung total unit yang sedang ter-booking/tersewa pada rentang tanggal tertentu.
     */
    private function getOccupiedCount(
        int $masterItemId,
        Carbon $start,
        Carbon $end,
        ?int $excludeRentalId = null
    ): int {
        return RentalDetail::query()
            ->where('master_item_id', $masterItemId)
            ->where('is_returned', false)
            ->whereHas('rental', function ($rentalQuery) use ($start, $end, $excludeRentalId) {
                $this->applyActiveRentalFilter($rentalQuery, $start, $end, $excludeRentalId);
            })
            ->count();
    }

    /**
     * Filter query untuk rental aktif yang overlap dengan rentang tanggal.
     *
     * @param  Builder<Rental>  $rentalQuery
     */
    private function applyActiveRentalFilter(
        $rentalQuery,
        Carbon $start,
        Carbon $end,
        ?int $excludeRentalId = null
    ): void {
        $rentalQuery->whereIn('status', ['pending', 'confirmed', 'picked_up'])
            ->where('pickup_date', '<=', $end->format('Y-m-d'))
            ->where('return_date', '>=', $start->format('Y-m-d'))
            ->where(function ($q) {
                // Jangan hitung pending booking jika sudah expired
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
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        if ($end->lt($start)) {
            throw new InvalidArgumentException("Tanggal selesai sewa ({$end->format('Y-m-d')}) tidak boleh lebih awal dari tanggal mulai sewa ({$start->format('Y-m-d')}).");
        }

        return [$start, $end];
    }
}
