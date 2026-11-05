<?php

namespace App\Console\Commands;

use App\Jobs\SendReturnReminder;
use App\Services\ReturnReminderService;
use Illuminate\Console\Command;

class SendReturnReminders extends Command
{
    /**
     * @var string
     */
    protected $signature = 'rentals:send-return-reminders
                            {--force : Kirim ulang pengingat yang sebelumnya sudah pernah dikirim}
                            {--dry-run : Tampilkan daftar pengingat tanpa mengirim}';

    /**
     * @var string
     */
    protected $description = 'Kirim pengingat pengembalian barang (H-1 dan hari-H) ke pelanggan';

    public function handle(ReturnReminderService $reminders): int
    {
        if (! config('notifications.reminder.enabled', true)) {
            $this->warn('Pengingat dinonaktifkan lewat config notifications.reminder.enabled.');

            return self::SUCCESS;
        }

        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        $pending = $reminders->pendingReminders(includeNotified: $force);

        if ($pending->isEmpty()) {
            $this->info('Tidak ada pengingat yang perlu dikirim.');

            return self::SUCCESS;
        }

        foreach ($pending as $reminder) {
            if ($dryRun) {
                $preview = $reminders->previewReminder($reminder['rental_id'], $reminder['type']);

                $this->line(sprintf(
                    '- rental #%d | vendor #%d | %s | batas kembali %s | tujuan: %s',
                    $reminder['rental_id'],
                    $reminder['vendor_id'],
                    $reminder['type'],
                    $reminder['return_date'],
                    $preview['recipient'] ?? 'TIDAK ADA NOMOR — pengingat dilewati'
                ));

                if ($preview['message'] !== null) {
                    foreach (explode("\n", $preview['message']) as $messageLine) {
                        $this->line('    | '.$messageLine);
                    }
                }

                $this->newLine();

                continue;
            }

            SendReturnReminder::dispatch(
                $reminder['rental_id'],
                $reminder['vendor_id'],
                $reminder['type'],
                $force
            );
        }

        if ($dryRun) {
            $this->info(sprintf('%d pengingat akan dikirim (dry-run).', $pending->count()));

            return self::SUCCESS;
        }

        $this->info(sprintf('%d pengingat diproses.', $pending->count()));

        return self::SUCCESS;
    }
}
