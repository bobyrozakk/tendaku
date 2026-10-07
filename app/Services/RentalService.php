<?php

namespace App\Services;

use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Payment;
use App\Models\Rental;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
     *     expires_in_hours?: int,
     *     notes?: string|null,
     *     items: array<int, array{master_item_id: int, quantity?: int}>
     * } $data
     *
     * @throws InvalidArgumentException Jika data tidak lengkap atau rentang tanggal salah
     * @throws RuntimeException Jika stok unit fisik tidak mencukupi
     */
    public function createBooking(array $data): Rental
    {
        if (empty($data['vendor_id'])) {
            throw new InvalidArgumentException('Vendor ID wajib diisi.');
        }

        if (empty($data['items']) || ! is_array($data['items'])) {
            throw new InvalidArgumentException('Pilihan perlengkapan camping (items) tidak boleh kosong.');
        }

        [$pickupDate, $returnDate] = $this->parseRentalDates($data['pickup_date'], $data['return_date']);
        $rentalDays = (int) $pickupDate->diffInDays($returnDate) + 1;

        return DB::transaction(function () use ($data, $pickupDate, $returnDate, $rentalDays) {
            $subtotal = 0.0;
            $detailsToCreate = [];

            // 1. Validasi ketersediaan dan kalkulasi subtotal per item
            foreach ($data['items'] as $itemData) {
                $masterItemId = (int) ($itemData['master_item_id'] ?? 0);
                $quantity = max(1, (int) ($itemData['quantity'] ?? 1));

                $masterItem = MasterItem::query()->findOrFail($masterItemId);

                // Cek ketersediaan via AvailabilityService
                if (! $this->availabilityService->isAvailable($masterItemId, $quantity, $pickupDate, $returnDate)) {
                    throw new RuntimeException("Stok alat camping '{$masterItem->name}' tidak mencukupi untuk tanggal {$pickupDate->format('d M Y')} s/d {$returnDate->format('d M Y')}.");
                }

                $dailyRate = (float) $masterItem->daily_rate;
                $lateFeePerDay = (float) ($masterItem->late_fee_per_day ?? 0);

                $itemTotal = $dailyRate * $rentalDays * $quantity;
                $subtotal += $itemTotal;

                // Masukkan tiap unit individual ke daftar rental_details
                for ($i = 0; $i < $quantity; $i++) {
                    $detailsToCreate[] = [
                        'master_item_id' => $masterItem->id,
                        'item_unit_id' => null, // Unit fisik ditetapkan saat pickup di toko
                        'daily_rate_snapshot' => $dailyRate,
                        'deposit_snapshot' => 0.0, // Tanpa deposit uang tunai
                        'late_fee_per_day_snapshot' => $lateFeePerDay,
                        'is_returned' => false,
                    ];
                }
            }

            // 2. Kalkulasi grand total (Full Payment)
            $deliveryFee = (float) ($data['delivery_fee'] ?? 0.0);
            $discountAmount = (float) ($data['discount_amount'] ?? 0.0);
            $grandTotal = max(0.0, $subtotal + $deliveryFee - $discountAmount);

            $expiresInHours = (int) ($data['expires_in_hours'] ?? 2);
            $bookingCode = $this->generateBookingCode();

            // 3. Simpan record Rental (Header)
            $rental = Rental::query()->create([
                'vendor_id' => (int) $data['vendor_id'],
                'user_id' => ! empty($data['user_id']) ? (int) $data['user_id'] : null,
                'created_by' => ! empty($data['created_by']) ? (int) $data['created_by'] : null,
                'booking_code' => $bookingCode,
                'guest_name' => $data['guest_name'] ?? null,
                'guest_phone' => $data['guest_phone'] ?? null,
                'guest_id_number' => $data['guest_id_number'] ?? null,
                'pickup_date' => $pickupDate->format('Y-m-d'),
                'return_date' => $returnDate->format('Y-m-d'),
                'pickup_method' => $data['pickup_method'] ?? 'self_pickup',
                'delivery_address' => $data['delivery_address'] ?? null,
                'rental_days' => $rentalDays,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'discount_amount' => $discountAmount,
                'total_deposit' => 0.0,
                'dp_amount' => 0.0,
                'grand_total' => $grandTotal,
                'ktp_collateral_status' => 'pending',
                'status' => 'pending',
                'expires_at' => now()->addHours($expiresInHours),
                'notes' => $data['notes'] ?? null,
            ]);

            // 4. Simpan record RentalDetail (Line Items)
            foreach ($detailsToCreate as $detail) {
                $rental->details()->create($detail);
            }

            // save record payment untuk proses pembayaran via Midtrans SNAP
            $rental->payments()->create([
                'vendor_id' => $rental->vendor_id,
                'payment_type' => 'settlement',
                'payment_method' => 'midtrans_snap',
                'amount' => $grandTotal,
                'status' => 'pending',
            ]);

            return $rental->load(['details.masterItem', 'vendor']);
        });
    }

    /**
     * Generate kode booking unik dengan format TDK-YYYYMMDD-XXXXX.
     */
    public function generateBookingCode(): string
    {
        do {
            $code = 'TDK-'.date('Ymd').'-'.strtoupper(Str::random(5));
        } while (Rental::query()->where('booking_code', $code)->exists());

        return $code;
    }

    /**
     * Proses serah terima / pickup barang oleh penyewa.
     * Mengalokasikan unit fisik ber-barcode, mencatat kondisi awal, menyimpan foto jaminan KTP, dan jejak audit staf.
     *
     * @param  Rental  $rental  Transaksi rental yang akan di-pickup
     * @param  array<int|string, array{item_unit_id: int, condition_before?: string|null, checklist_notes?: string|null}|int>  $unitAssignments
     *                                                                                                                                           Daftar alokasi unit fisik, format: [rental_detail_id => ['item_unit_id' => 10, 'condition_before' => 'good']] atau [rental_detail_id => 10]
     * @param  string  $ktpPhotoUrl  URL/path foto verifikasi wajah penyewa memegang KTP asli fisik
     * @param  int|null  $staffUserId  ID user staf vendor yang menyerahkan barang
     * @param  string|null  $ktpNotes  Catatan penahanan KTP (misal: nomor loker penyimpanan)
     *
     * @throws InvalidArgumentException Jika foto KTP kosong atau unit tidak cocok
     * @throws RuntimeException Jika status rental tidak valid atau unit tidak tersedia
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
     */
    public function processReturn(Rental $rental, array $returnDetails): void
    {
        // Logika pengembalian & hitung denda (akan dikerjakan pada sprint selanjutnya)
    }

    /**
     * Parse dan validasi rentang tanggal sewa.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function parseRentalDates(string|DateTimeInterface $pickupDate, string|DateTimeInterface $returnDate): array
    {
        $start = Carbon::parse($pickupDate)->startOfDay();
        $end = Carbon::parse($returnDate)->startOfDay();

        if ($end->lt($start)) {
            throw new InvalidArgumentException("Tanggal pengembalian ({$end->format('Y-m-d')}) tidak boleh lebih awal dari tanggal pengambilan ({$start->format('Y-m-d')}).");
        }

        return [$start, $end];
    }
}
