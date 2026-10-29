<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Rental;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class RentalService
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {}

    /**
     * Buat transaksi pemesanan / booking rental baru dengan pembayaran lunas & validasi ketersediaan unit.
     *
     * @param array{
     *     vendor_id: int,
     *     user_id?: int|null,
     *     created_by?: int|null,
     *     guest_name?: string|null,
     *     guest_phone?: string|null,
     *     guest_id_number?: string|null,
     *     pickup_date: string|DateTimeInterface,
     *     return_date: string|DateTimeInterface,
     *     pickup_method?: 'self_pickup'|'delivery',
     *     delivery_address?: string|null,
     *     delivery_fee?: float|int,
     *     discount_amount?: float|int,
     *     subtotal_override?: float|int|null,
     *     expires_in_hours?: int,
     *     notes?: string|null,
     *     items: array<int, array{master_item_id: int, quantity?: int, qty?: int}>
     * } $data
     *
     * @throws InvalidArgumentException Jika data tidak valid atau guest delivery
     * @throws InsufficientStockException Jika stok unit fisik tidak mencukupi
     */
    public function createBooking(array $data): Rental
    {
        if (empty($data['vendor_id'])) {
            throw new InvalidArgumentException('Vendor ID wajib diisi.');
        }

        $vendorId = (int) $data['vendor_id'];

        if (empty($data['items']) || ! is_array($data['items'])) {
            throw new InvalidArgumentException('Pilihan perlengkapan camping (items) tidak boleh kosong.');
        }

        // Cek apakah user adalah guest
        $isGuest = empty($data['user_id']);
        if ($isGuest) {
            if (empty($data['guest_name']) || empty($data['guest_phone']) || empty($data['guest_id_number'])) {
                throw new InvalidArgumentException('Data guest (nama, nomor telepon, dan nomor identitas/KTP) wajib diisi untuk pemesanan tanpa akun.');
            }
        }

        $pickupMethod = $data['pickup_method'] ?? 'self_pickup';
        if ($pickupMethod === 'delivery') {
            if ($isGuest) {
                throw new InvalidArgumentException('Metode pengantaran (delivery) hanya diperbolehkan untuk pengguna yang telah masuk (login). Guest hanya boleh self pickup.');
            }
            if (empty($data['delivery_address'])) {
                throw new InvalidArgumentException('Alamat pengantaran (delivery_address) wajib diisi jika memilih metode delivery.');
            }
        }

        [$pickupDate, $returnDate] = $this->parseRentalDateTime(
            $data['pickup_date'],
            $data['return_date'],
            $data['pickup_time'] ?? null,
            $data['return_time'] ?? null
        );

        $timesSpecified = ! empty($data['pickup_time'])
            || ! empty($data['return_time'])
            || (preg_match('/\d{1,2}:\d{2}/', (string) $data['pickup_date']) === 1)
            || (preg_match('/\d{1,2}:\d{2}/', (string) $data['return_date']) === 1);

        $hours = $pickupDate->floatDiffInHours($returnDate, false);
        if (($timesSpecified && $hours <= 0) || (! $timesSpecified && $hours < 0)) {
            throw new InvalidArgumentException("Waktu pengembalian ({$returnDate->format('Y-m-d H:i')}) harus setelah waktu pengambilan ({$pickupDate->format('Y-m-d H:i')}).");
        }

        // Durasi sewa 1 hari = 24 jam penuh sejak waktu pengambilan barang.
        // Jika <= 24 jam (misal 24 jam pas atau kurang), dihitung 1 hari.
        // Jika > 24 jam, dihitung pembulatan ke atas (ceil) per 24 jam.
        $rentalDays = isset($data['rental_days']) && (int) $data['rental_days'] > 0
            ? (int) $data['rental_days']
            : max(1, (int) ceil($hours / 24));

        // Normalize dan ekstrak item list
        $itemRequirements = [];
        foreach ($data['items'] as $itemData) {
            $masterItemId = (int) ($itemData['master_item_id'] ?? 0);
            $qty = (int) ($itemData['quantity'] ?? $itemData['qty'] ?? 1);
            if ($masterItemId <= 0 || $qty <= 0) {
                throw new InvalidArgumentException('ID item dan kuantitas sewa harus berupa bilangan bulat positif.');
            }
            $itemRequirements[$masterItemId] = ($itemRequirements[$masterItemId] ?? 0) + $qty;
        }

        // Urutkan id master item ascending untuk mencegah deadlock pada DB transaction locking
        $sortedItemIds = array_keys($itemRequirements);
        sort($sortedItemIds);

        return DB::transaction(function () use (
            $data,
            $vendorId,
            $isGuest,
            $pickupDate,
            $returnDate,
            $rentalDays,
            $pickupMethod,
            $itemRequirements,
            $sortedItemIds
        ) {
            // Kunci baris master_items terkait dengan lockForUpdate() urut berdasarkan ID
            /** @var Collection<int, MasterItem> $masterItems */
            $masterItems = MasterItem::query()
                ->whereIn('id', $sortedItemIds)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Pastikan semua item ditemukan dan milik vendor yang sama
            foreach ($sortedItemIds as $masterItemId) {
                if (! $masterItems->has($masterItemId)) {
                    throw new InvalidArgumentException("Master item dengan ID #{$masterItemId} tidak ditemukan.");
                }

                $masterItem = $masterItems->get($masterItemId);
                if ((int) $masterItem->vendor_id !== $vendorId) {
                    throw new InvalidArgumentException("Item '{$masterItem->name}' berasal dari vendor yang berbeda. Satu transaksi rental hanya boleh untuk satu vendor.");
                }
            }

            // Cek ulang AvailabilityService di dalam transaksi
            $subtotal = 0.0;
            $detailsToCreate = [];

            foreach ($itemRequirements as $masterItemId => $quantity) {
                $masterItem = $masterItems->get($masterItemId);

                if (! $this->availabilityService->isAvailable($masterItemId, $quantity, $pickupDate->toDateString(), $returnDate->toDateString())) {
                    throw new InsufficientStockException(
                        $masterItem,
                        "Stok alat camping '{$masterItem->name}' tidak mencukupi untuk tanggal {$pickupDate->format('d M Y')} s/d {$returnDate->format('d M Y')}."
                    );
                }

                $dailyRate = (float) $masterItem->daily_rate;
                $lateFeePerDay = (float) ($masterItem->late_fee_per_day ?? 0);

                $itemTotal = $dailyRate * $rentalDays * $quantity;
                $subtotal += $itemTotal;

                for ($i = 0; $i < $quantity; $i++) {
                    $detailsToCreate[] = [
                        'master_item_id' => $masterItem->id,
                        'item_unit_id' => null, // null sebelum pickup
                        'daily_rate_snapshot' => $dailyRate,
                        'deposit_snapshot' => 0.0,
                        'late_fee_per_day_snapshot' => $lateFeePerDay,
                        'is_returned' => false,
                    ];
                }
            }

            // Hitung grand total (subtotal + delivery_fee - discount_amount)
            // subtotal_override dipakai saat harga sudah ditetapkan di muka,
            // contohnya paket camping yang harganya bukan akumulasi tarif harian alat.
            $subtotal = isset($data['subtotal_override'])
                ? max(0.0, (float) $data['subtotal_override'])
                : $subtotal;
            $deliveryFee = (float) ($data['delivery_fee'] ?? 0.0);
            $discountAmount = (float) ($data['discount_amount'] ?? 0.0);
            $adminFee = (float) round($subtotal * 0.05); // Tambahkan biaya admin 5%
            $grandTotal = max(0.0, $subtotal + $deliveryFee + $adminFee - $discountAmount);

            if (isset($data['expires_at'])) {
                $expiresAt = Carbon::parse($data['expires_at']);
            } elseif (isset($data['expires_in_minutes'])) {
                $expiresAt = Carbon::now()->addMinutes(
                    (int) $data['expires_in_minutes']
                );
            } else {
                $expiresInHours = (int) (
                    $data['expires_in_hours']
                    ?? config('rental.payment_expiry_hours')
                );

                $expiresAt = Carbon::now()->addHours($expiresInHours);
            }

            $firstMasterItemId = (int) (array_key_first($itemRequirements) ?? 1);
            $bookingCode = $this->generateBookingCode($vendorId, $firstMasterItemId);

            $rental = Rental::query()->create([
                'vendor_id' => $vendorId,
                'user_id' => ! $isGuest ? (int) $data['user_id'] : null,
                'created_by' => ! empty($data['created_by']) ? (int) $data['created_by'] : null,
                'booking_code' => $bookingCode,
                'guest_name' => $isGuest ? ($data['guest_name'] ?? null) : null,
                'guest_phone' => $isGuest ? ($data['guest_phone'] ?? null) : null,
                'guest_id_number' => $isGuest ? ($data['guest_id_number'] ?? null) : null,
                'pickup_date' => $pickupDate->format('Y-m-d'),
                'pickup_time' => $pickupDate->format('H:i:s'),
                'return_date' => $returnDate->format('Y-m-d'),
                'return_time' => $returnDate->format('H:i:s'),
                'pickup_method' => $pickupMethod,
                'delivery_address' => $pickupMethod === 'delivery' ? ($data['delivery_address'] ?? null) : null,
                'customer_address_id' => $pickupMethod === 'delivery' && ! empty($data['customer_address_id']) ? (int) $data['customer_address_id'] : null,
                'rental_days' => $rentalDays,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'discount_amount' => $discountAmount,
                'total_deposit' => 0.0,
                'dp_amount' => 0.0,
                'grand_total' => $grandTotal,
                'ktp_collateral_status' => 'pending',
                'status' => 'pending',
                'expires_at' => $expiresAt,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($detailsToCreate as $detail) {
                $rental->details()->create($detail);
            }

            return $rental->load(['details.masterItem', 'vendor']);
        });
    }

    //  Generate kode booking: TDK + Ymd + [ID Toko 2 digit] + [ID Barang 2 digit] + [No Urut 3 digit]
    public function generateBookingCode(int $vendorId = 1, int $masterItemId = 1): string
    {
        $date = date('Ymd');
        $vendorPart = str_pad((string) $vendorId, 2, '0', STR_PAD_LEFT);
        $itemPart   = str_pad((string) $masterItemId, 2, '0', STR_PAD_LEFT);

        $prefix = "TDK{$date}{$vendorPart}{$itemPart}";

        $lastBooking = Rental::withoutGlobalScopes()
            ->where('booking_code', 'like', "{$prefix}%")
            ->orderBy('booking_code', 'desc')
            ->value('booking_code');

        $nextNumber = $lastBooking ? ((int) substr($lastBooking, -3) + 1) : 1;

        do {
            $code = $prefix . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (Rental::withoutGlobalScopes()->where('booking_code', $code)->exists());

        return $code;
    }

    /**
     * Proses serah terima / pickup barang oleh penyewa.
     */
    public function processPickup(
        Rental $rental,
        array $unitAssignments,
        string $ktpPhotoUrl,
        ?int $staffUserId = null,
        ?string $ktpNotes = null
    ): Rental {
        if (in_array($rental->status, ['picked_up', 'returned', 'completed', 'cancelled', 'expired'], true)) {
            throw new RuntimeException("Rental dengan status '{$rental->status}' tidak dapat diproses serah-terima/pickup.");
        }

        if (trim($ktpPhotoUrl) === '') {
            throw new InvalidArgumentException('Foto verifikasi wajah penyewa memegang KTP fisik wajib disertakan saat serah terima barang.');
        }

        $rental->loadMissing('details');

        if ($rental->details->isEmpty()) {
            throw new RuntimeException('Transaksi sewa ini tidak memiliki rincian barang.');
        }

        return DB::transaction(function () use ($rental, $unitAssignments, $ktpPhotoUrl, $staffUserId, $ktpNotes) {
            $assignedUnitIds = [];

            foreach ($rental->details as $detail) {
                $assignment = $unitAssignments[$detail->id] ?? null;

                if ($assignment === null) {
                    throw new InvalidArgumentException("Alokasi unit fisik untuk baris rincian ID #{$detail->id} belum ditentukan.");
                }

                $itemUnitId = is_array($assignment) ? (int) ($assignment['item_unit_id'] ?? 0) : (int) $assignment;
                $conditionBefore = is_array($assignment) ? ($assignment['condition_before'] ?? null) : null;
                $checklistNotes = is_array($assignment) ? ($assignment['checklist_notes'] ?? null) : null;

                if ($itemUnitId <= 0) {
                    throw new InvalidArgumentException("ID unit fisik tidak valid untuk baris rincian ID #{$detail->id}.");
                }

                if (in_array($itemUnitId, $assignedUnitIds, true)) {
                    throw new InvalidArgumentException("Unit fisik ID #{$itemUnitId} tidak boleh dialokasikan lebih dari satu kali dalam transaksi yang sama.");
                }

                $assignedUnitIds[] = $itemUnitId;

                /** @var ItemUnit $itemUnit */
                $itemUnit = ItemUnit::query()
                    ->where('vendor_id', $rental->vendor_id)
                    ->findOrFail($itemUnitId);

                if ((int) $itemUnit->master_item_id !== (int) $detail->master_item_id) {
                    throw new InvalidArgumentException("Unit fisik '{$itemUnit->unit_code}' bukan merupakan jenis alat yang sesuai dengan pesanan.");
                }

                if (in_array($itemUnit->status, ['maintenance', 'retired'], true) || $itemUnit->condition === 'lost') {
                    throw new RuntimeException("Unit fisik '{$itemUnit->unit_code}' tidak siap pakai (status: {$itemUnit->status}, kondisi: {$itemUnit->condition}).");
                }

                // Update detail dengan unit fisik dan kondisi awal
                $detail->update([
                    'item_unit_id' => $itemUnit->id,
                    'condition_before' => $conditionBefore ?? $itemUnit->condition ?? 'good',
                    'checklist_notes' => $checklistNotes,
                ]);

                // Update status unit menjadi rented
                $itemUnit->update(['status' => 'rented']);
            }

            // Update header rental
            $rental->update([
                'status' => 'picked_up',
                'picked_up_at' => now(),
                'picked_up_by' => $staffUserId,
                'ktp_collateral_photo_url' => $ktpPhotoUrl,
                'ktp_collateral_status' => 'held',
                'ktp_received_at' => now(),
                'ktp_collateral_notes' => $ktpNotes,
            ]);

            return $rental->fresh(['details.itemUnit', 'details.masterItem', 'pickedUpBy', 'vendor']);
        });
    }

    /**
     * Proses pengembalian barang (return) dan kalkulasi denda (keterlambatan/kerusakan).
     * Inspeksi kondisi fisik barang, hitung denda, kembalikan unit fisik, dan kembalikan KTP fisik kepada penyewa.
     *
     * @param  Rental  $rental  Transaksi rental yang akan di-return
     * @param  array<int|string, array{condition_after: string, damage_fee?: float|int, checklist_notes?: string|null}>  $returnDetails
     *                                                                                                                                   Format: [rental_detail_id => ['condition_after' => 'good', 'damage_fee' => 0, 'checklist_notes' => '...']]
     * @param  int|null  $staffUserId  ID user staf vendor yang memproses pengembalian
     * @param  string|null  $ktpReturnNotes  Catatan pengembalian KTP (misal: 'KTP dikembalikan langsung ke penyewa')
     *
     * @throws RuntimeException Jika status rental tidak valid untuk pengembalian
     * @throws InvalidArgumentException Jika data inspeksi tidak lengkap
     */
    public function processReturn(
        Rental $rental,
        array $returnDetails,
        ?int $staffUserId = null,
        ?string $ktpReturnNotes = null
    ): Rental {
        if ($rental->status !== 'picked_up') {
            throw new RuntimeException("Rental dengan status '{$rental->status}' tidak dapat diproses pengembalian. Hanya rental dengan status 'picked_up' yang dapat dikembalikan.");
        }

        $rental->loadMissing('details.itemUnit');

        if ($rental->details->isEmpty()) {
            throw new RuntimeException('Transaksi sewa ini tidak memiliki rincian barang.');
        }

        return DB::transaction(function () use ($rental, $returnDetails, $staffUserId, $ktpReturnNotes) {
            $totalLateFee = 0.0;
            $totalDamageFee = 0.0;
            $actualReturnDate = now();

            // Hitung hari keterlambatan berdasarkan tanggal kontrak vs tanggal aktual pengembalian
            $contractReturnDate = Carbon::parse($rental->return_date)->endOfDay();
            $lateDays = 0;

            if ($actualReturnDate->gt($contractReturnDate)) {
                $lateDays = (int) $contractReturnDate->startOfDay()->diffInDays($actualReturnDate->startOfDay());
            }

            foreach ($rental->details as $detail) {
                $inspection = $returnDetails[$detail->id] ?? null;

                if ($inspection === null) {
                    throw new InvalidArgumentException("Data inspeksi pengembalian untuk baris rincian ID #{$detail->id} belum diisi.");
                }

                $conditionAfter = $inspection['condition_after'] ?? null;

                if (empty($conditionAfter)) {
                    throw new InvalidArgumentException("Kondisi barang setelah pengembalian (condition_after) wajib diisi untuk baris rincian ID #{$detail->id}.");
                }

                $damageFee = (float) ($inspection['damage_fee'] ?? 0.0);
                $checklistNotes = $inspection['checklist_notes'] ?? $detail->checklist_notes;

                // Kalkulasi denda keterlambatan per unit
                $unitLateFee = 0.0;
                if ($lateDays > 0) {
                    $lateFeePerDay = (float) ($detail->late_fee_per_day_snapshot ?? 0);
                    $unitLateFee = $lateFeePerDay * $lateDays;
                }

                $totalLateFee += $unitLateFee;
                $totalDamageFee += $damageFee;

                // Update detail dengan hasil inspeksi pengembalian
                $detail->update([
                    'condition_after' => $conditionAfter,
                    'late_days' => $lateDays,
                    'late_fee' => $unitLateFee,
                    'damage_fee' => $damageFee,
                    'checklist_notes' => $checklistNotes,
                    'returned_at' => $actualReturnDate,
                    'is_returned' => true,
                ]);

                // Kembalikan status unit fisik berdasarkan kondisi pengembalian
                if ($detail->itemUnit) {
                    $newUnitStatus = match ($conditionAfter) {
                        'lost' => 'retired',
                        'damaged' => 'maintenance',
                        default => 'available',
                    };

                    $detail->itemUnit->update([
                        'status' => $newUnitStatus,
                        'condition' => $conditionAfter === 'lost' ? 'lost' : $conditionAfter,
                    ]);
                }
            }

            // Kalkulasi final total (grand_total + denda)
            $finalTotal = (float) $rental->grand_total + $totalLateFee + $totalDamageFee;

            // Update KTP notes: gabungkan catatan penahanan dan pengembalian
            $combinedKtpNotes = $rental->ktp_collateral_notes;
            if ($ktpReturnNotes) {
                $combinedKtpNotes = trim(($combinedKtpNotes ? $combinedKtpNotes.' | ' : '').$ktpReturnNotes);
            }

            // Update header rental
            $rental->update([
                'status' => 'completed',
                'actual_return_date' => $actualReturnDate->format('Y-m-d'),
                'returned_at' => $actualReturnDate,
                'returned_by' => $staffUserId,
                'total_late_fee' => $totalLateFee,
                'total_damage_fee' => $totalDamageFee,
                'final_total' => $finalTotal,
                'ktp_collateral_status' => 'returned',
                'ktp_returned_at' => $actualReturnDate,
                'ktp_collateral_notes' => $combinedKtpNotes,
            ]);

            return $rental->fresh(['details.itemUnit', 'details.masterItem', 'returnedBy', 'vendor']);
        });
    }

    /**
     * Parse dan validasi rentang tanggal dan waktu sewa lengkap.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function parseRentalDateTime(
        string|DateTimeInterface $pickupDate,
        string|DateTimeInterface $returnDate,
        ?string $pickupTime = null,
        ?string $returnTime = null
    ): array {
        $pickupStr = $pickupDate instanceof DateTimeInterface ? $pickupDate->format('Y-m-d H:i:s') : (string) $pickupDate;
        $returnStr = $returnDate instanceof DateTimeInterface ? $returnDate->format('Y-m-d H:i:s') : (string) $returnDate;

        $hasPickupTimeInStr = preg_match('/\d{1,2}:\d{2}/', $pickupStr) === 1;
        $hasReturnTimeInStr = preg_match('/\d{1,2}:\d{2}/', $returnStr) === 1;

        if ($hasPickupTimeInStr) {
            $start = Carbon::parse($pickupStr);
        } elseif ($pickupTime !== null && trim($pickupTime) !== '') {
            $start = Carbon::parse(explode(' ', $pickupStr)[0].' '.trim($pickupTime));
        } else {
            $start = Carbon::parse($pickupStr)->startOfDay();
        }

        if ($hasReturnTimeInStr) {
            $end = Carbon::parse($returnStr);
        } elseif ($returnTime !== null && trim($returnTime) !== '') {
            $end = Carbon::parse(explode(' ', $returnStr)[0].' '.trim($returnTime));
        } else {
            if ($hasPickupTimeInStr || ($pickupTime !== null && trim($pickupTime) !== '')) {
                $end = Carbon::parse(explode(' ', $returnStr)[0].' '.$start->format('H:i:s'));
            } else {
                $end = Carbon::parse($returnStr)->startOfDay();
            }
        }

        $timesSpecified = $hasPickupTimeInStr
            || $hasReturnTimeInStr
            || ($pickupTime !== null && trim($pickupTime) !== '')
            || ($returnTime !== null && trim($returnTime) !== '');

        if ($timesSpecified && $end->lte($start)) {
            throw new InvalidArgumentException("Waktu pengembalian ({$end->format('Y-m-d H:i')}) harus setelah waktu pengambilan ({$start->format('Y-m-d H:i')}).");
        }

        if (! $timesSpecified && $end->lt($start)) {
            throw new InvalidArgumentException("Tanggal pengembalian ({$end->format('Y-m-d')}) tidak boleh lebih awal dari tanggal pengambilan ({$start->format('Y-m-d')}).");
        }

        return [$start, $end];
    }

    /**
     * Parse dan validasi rentang tanggal sewa (kompatibilitas backward).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function parseRentalDates(string|DateTimeInterface $pickupDate, string|DateTimeInterface $returnDate): array
    {
        return $this->parseRentalDateTime($pickupDate, $returnDate);
    }

    public function calculateRentalDays(
        string|DateTimeInterface $pickupDate,
        string|DateTimeInterface $returnDate,
        ?string $pickupTime = null,
        ?string $returnTime = null
    ): int {
        [$start, $end] = $this->parseRentalDateTime($pickupDate, $returnDate, $pickupTime, $returnTime);

        return max(1, (int) ceil($start->floatDiffInHours($end, false) / 24));
    }
}
