<?php

namespace App\Console\Commands;

use App\Mail\RentalReturnReminderMail;
use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendRentalReturnReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rentals:send-email-reminders {--dry-run : Simulasi pengecekan tanpa mengirim email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim email pengingat pengembalian barang sewa ke customer (H-5 jam sebelum batas waktu)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = Carbon::now();
        $targetHorizon = $now->copy()->addHours(5);

        // Ambil rental aktif (picked_up) yang belum pernah dikirimi reminder
        $rentals = Rental::query()
            ->withoutGlobalScope('vendor')
            ->with(['customer', 'vendor', 'details.masterItem'])
            ->where('status', 'picked_up')
            ->whereNull('return_reminder_sent_at')
            ->whereDate('return_date', '<=', $targetHorizon->toDateString())
            ->get();

        $sentCount = 0;
        $isDryRun = (bool) $this->option('dry-run');

        foreach ($rentals as $rental) {
            $dueDateTime = $rental->returnDateTime();

            // Hanya kirim jika waktu pengembalian berada dalam jendela <= 5 jam ke depan dan belum terlewat lebih dari 1 jam
            if ($dueDateTime->lte($targetHorizon) && $dueDateTime->gte($now->copy()->subHour())) {
                $recipientEmail = $rental->customerEmail();

                if ($recipientEmail) {
                    if (! $isDryRun) {
                        try {
                            Mail::to($recipientEmail)->send(new RentalReturnReminderMail($rental));
                            $rental->forceFill([
                                'return_reminder_sent_at' => Carbon::now(),
                            ])->save();

                            $sentCount++;
                            Log::info("Pengingat pengembalian rental [{$rental->booking_code}] berhasil dikirim ke {$recipientEmail}.");
                        } catch (\Throwable $e) {
                            Log::error("Gagal mengirim email pengingat rental [{$rental->booking_code}] ke {$recipientEmail}: {$e->getMessage()}");
                            $this->error("Gagal kirim ke {$recipientEmail}: {$e->getMessage()}");
                        }
                    } else {
                        $this->info("[Dry-Run] Akan mengirim pengingat ke {$recipientEmail} untuk booking {$rental->booking_code} (Jatuh tempo: {$dueDateTime->toDateTimeString()}).");
                        $sentCount++;
                    }
                } else {
                    // Jika tidak ada email (tamu), tandai agar tidak terus diproses ulang
                    if (! $isDryRun) {
                        $rental->forceFill([
                            'return_reminder_sent_at' => Carbon::now(),
                        ])->save();
                    }
                }
            }
        }

        $this->info("Selesai memproses pengingat pengembalian. Total terkirim: {$sentCount} email.");

        return Command::SUCCESS;
    }
}
