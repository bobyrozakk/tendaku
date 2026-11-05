<?php

namespace App\Jobs;

use App\Services\CurrentVendor;
use App\Services\ReturnReminderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Kirim satu pengingat pengembalian barang ke pelanggan.
 *
 * Job membawa `vendor_id` secara eksplisit karena konteks tenant hilang saat
 * job diproses oleh worker queue. Tanpa itu, data antar vendor bisa tertukar.
 */
class SendReturnReminder implements ShouldQueue
{
    use Queueable;

    /**
     * @param  int  $rentalId  Rental yang dikirimkan pengingatnya.
     * @param  int  $vendorId  Vendor pemilik rental (konteks tenant).
     * @param  string  $type  Tipe notifikasi (lihat ReturnReminderService::TYPE_*).
     * @param  bool  $force  Kirim ulang walau notifikasi sebelumnya sudah ada.
     */
    public function __construct(
        public int $rentalId,
        public int $vendorId,
        public string $type,
        public bool $force = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ReturnReminderService $reminders): void
    {
        CurrentVendor::runAs($this->vendorId, function () use ($reminders): void {
            $reminders->sendReminderForRental($this->rentalId, $this->type, $this->force);
        });
    }
}
