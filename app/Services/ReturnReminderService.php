<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ReturnReminderService
{
    /**
     * Tipe notifikasi untuk pengingat H-1 (sehari sebelum tanggal kembali).
     */
    public const TYPE_H_MINUS_ONE = 'return_reminder_h1';

    /**
     * Tipe notifikasi untuk pengingat hari-H (tanggal kembali).
     */
    public const TYPE_DUE_TODAY = 'return_reminder_due';

    /**
     * Kanal yang didukung (harus sesuai enum `channel` pada tabel `notifications`).
     */
    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNEL_IN_APP = 'in_app';

    public const CHANNEL_EMAIL = 'email';

    /**
     * @var array<int, string> Nama bulan dalam Bahasa Indonesia.
     */
    protected const MONTHS = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    public function __construct(
        protected WhatsAppService $whatsApp
    ) {}

    /**
     * Daftar pengingat yang perlu dikirim.
     *
     * Hanya rental berstatus `picked_up` (sudah pickup, belum kembali) yang
     * masuk daftar. Pengingat yang sudah pernah dikirim untuk rental + tipe yang
     * sama dilewati agar tidak ada kiriman ganda.
     *
     * @param  bool  $includeNotified  Sertakan rental yang notifikasinya sudah pernah dibuat.
     * @return Collection<int, array{rental_id: int, vendor_id: int, type: string, return_date: string}>
     */
    public function pendingReminders(?Carbon $now = null, bool $includeNotified = false): Collection
    {
        $now ??= Carbon::now($this->timezone());
        $today = $now->copy()->startOfDay();

        $targets = [];

        foreach ($this->reminderDays() as $offset) {
            $targets[] = [
                'date' => $today->copy()->addDays($offset)->toDateString(),
                'type' => $this->typeForOffset($offset),
                'offset' => $offset,
            ];
        }

        $rentals = Rental::withoutGlobalScope('vendor')
            ->where('status', 'picked_up')
            ->where(function (Builder $query) use ($targets) {
                foreach ($targets as $target) {
                    $query->orWhereDate('return_date', $target['date']);
                }
            })
            ->orderBy('return_date')
            ->orderBy('id')
            ->get();

        $pending = new Collection;

        foreach ($rentals as $rental) {
            $returnDate = $this->normalizeDate($rental->return_date);

            foreach ($targets as $target) {
                if ($returnDate !== $target['date']) {
                    continue;
                }

                if ($this->hasNotification($rental, $target['type']) && ! $includeNotified) {
                    continue;
                }

                $pending->push([
                    'rental_id' => $rental->id,
                    'vendor_id' => $rental->vendor_id,
                    'type' => $target['type'],
                    'return_date' => $target['date'],
                ]);
            }
        }

        return $pending;
    }

    /**
     * Kirim satu pengingat pengembalian untuk rental tertentu.
     *
     * Aman dipanggil berulang: notifikasi rental + tipe yang sama hanya
     * terkirim sekali. Gunakan `$force = true` untuk mengirim ulang (mis. setelah
     * kegagalan sebelumnya).
     */
    public function sendReminderForRental(int $rentalId, string $type, bool $force = false): bool
    {
        $rental = Rental::query()->find($rentalId);

        if (! $rental instanceof Rental) {
            Log::warning('Pengingat: rental tidak ditemukan atau di luar vendor aktif.', [
                'rental_id' => $rentalId,
                'type' => $type,
            ]);

            return false;
        }

        if ($rental->status !== 'picked_up') {
            Log::info('Pengingat: rental sudah tidak aktif, pengiriman dibatalkan.', [
                'rental_id' => $rentalId,
                'status' => $rental->status,
                'type' => $type,
            ]);

            return false;
        }

        $recipient = $this->resolveRecipient($rental);

        if ($recipient === null) {
            Log::warning('Pengingat: nomor telepon penerima tidak tersedia.', [
                'rental_id' => $rentalId,
                'type' => $type,
            ]);

            return false;
        }

        $message = $this->buildMessage($rental, $type);

        $notification = $this->reserveNotification($rental, $type, $recipient, $message, $force);

        if (! $notification instanceof NotificationLog) {
            Log::info('Pengingat: notifikasi sudah pernah dikirim, pengiriman dilewati.', [
                'rental_id' => $rentalId,
                'type' => $type,
            ]);

            return false;
        }

        $sent = $this->dispatch($rental, $recipient, $message);

        $notification->update([
            'status' => $sent ? 'sent' : 'failed',
            'sent_at' => $sent ? now() : null,
        ]);

        return $sent;
    }

    /**
     * Pratinjau pengingat tanpa mengirim: nomor tujuan + isi pesan.
     *
     * Dipakai `rentals:send-return-reminders --dry-run` agar operator bisa
     * memverifikasi nomor tujuan & isi pesan sebelum pengiriman sungguhan.
     *
     * @return array{recipient: string|null, message: string|null}
     */
    public function previewReminder(int $rentalId, string $type): array
    {
        $rental = Rental::withoutGlobalScope('vendor')->find($rentalId);

        if (! $rental instanceof Rental) {
            return ['recipient' => null, 'message' => null];
        }

        $recipient = $this->resolveRecipient($rental);

        return [
            'recipient' => $recipient,
            'message' => $recipient === null ? null : $this->buildMessage($rental, $type),
        ];
    }

    /**
     * Susun pesan pengingat dalam Bahasa Indonesia.
     */
    public function buildMessage(Rental $rental, string $type): string
    {
        $name = $rental->customer?->name ?? $rental->guest_name ?? 'Pelanggan';
        $vendorName = $rental->vendor?->name ?? 'TENDAKU';
        $returnDate = $rental->return_date ? $this->formatDate($rental->return_date) : '-';

        $lines = [];
        $lines[] = "Halo {$name},";
        $lines[] = '';
        $lines[] = $this->headline($type);
        $lines[] = '';
        $lines[] = "Kode Booking   : {$rental->booking_code}";
        $lines[] = "Batas Kembali : {$returnDate} (sampai akhir hari)";
        $lines[] = 'Total Biaya   : Rp '.number_format((float) $rental->grand_total, 0, ',', '.');

        if (config('notifications.message.include_item_list', true)) {
            $items = $this->itemLines($rental);

            if ($items !== []) {
                $lines[] = '';
                $lines[] = 'Barang yang disewa:';

                foreach ($items as $item) {
                    $lines[] = '- '.$item;
                }
            }
        }

        if (config('notifications.message.include_late_fee', true)) {
            $lateFeeNote = $this->lateFeeNote($rental);

            if ($lateFeeNote !== null) {
                $lines[] = '';
                $lines[] = $lateFeeNote;
            }
        }

        $lines[] = '';
        $lines[] = "Mohon mengembalikan barang tepat waktu. Jika ada kendala, hubungi {$vendorName} melalui nomor yang Anda gunakan saat booking.";

        return implode("\n", $lines);
    }

    /**
     * Kanal notifikasi aktif (whatsapp | in_app | email).
     */
    public function channel(): string
    {
        $channel = (string) config('notifications.channel', self::CHANNEL_WHATSAPP);

        return in_array($channel, [self::CHANNEL_WHATSAPP, self::CHANNEL_IN_APP, self::CHANNEL_EMAIL], true)
            ? $channel
            : self::CHANNEL_WHATSAPP;
    }

    /**
     * Zona waktu acuan penghitungan "hari ini" dan "H-1".
     */
    public function timezone(): string
    {
        return (string) config('notifications.reminder.timezone', 'Asia/Jakarta');
    }

    /**
     * Daftar offset hari sebelum tanggal kembali yang memicu pengingat.
     *
     * @return array<int, int>
     */
    protected function reminderDays(): array
    {
        $days = config('notifications.reminder.days', [1, 0]);

        return is_array($days) ? array_values(array_map('intval', $days)) : [1, 0];
    }

    /**
     * Pemetaan offset hari ke tipe notifikasi.
     */
    protected function typeForOffset(int $offset): string
    {
        return match ($offset) {
            1 => self::TYPE_H_MINUS_ONE,
            0 => self::TYPE_DUE_TODAY,
            default => 'return_reminder_d'.abs($offset),
        };
    }

    /**
     * Offset hari dari tipe notifikasi (untuk penyusunan kalimat pengingat).
     */
    protected function offsetForType(string $type): int
    {
        return match ($type) {
            self::TYPE_H_MINUS_ONE => 1,
            self::TYPE_DUE_TODAY => 0,
            default => (int) preg_replace('/\D+/', '', substr($type, strlen('return_reminder_d'))) ?: 0,
        };
    }

    /**
     * Kalimat utama sesuai jenis pengingat.
     */
    protected function headline(string $type): string
    {
        $offset = $this->offsetForType($type);

        return match ($offset) {
            1 => 'Pengingat: barang yang Anda sewa harus dikembalikan BESOK.',
            0 => 'Pengingat: barang yang Anda sewa harus dikembalikan HARI INI.',
            default => "Pengingat: barang yang Anda sewa harus dikembalikan dalam {$offset} hari.",
        };
    }

    /**
     * Daftar barang yang disewa (satu baris per unit fisik).
     *
     * @return array<int, string>
     */
    protected function itemLines(Rental $rental): array
    {
        $items = [];

        foreach ($rental->details as $detail) {
            $name = $detail->masterItem?->name ?? 'Barang';

            $items[] = $detail->late_fee_per_day_snapshot
                ? $name.' (denda telat Rp '.number_format((float) $detail->late_fee_per_day_snapshot, 0, ',', '.').'/hari)'
                : $name;
        }

        return $items;
    }

    /**
     * Catatan denda keterlambatan.
     */
    protected function lateFeeNote(Rental $rental): ?string
    {
        $highest = 0.0;

        foreach ($rental->details as $detail) {
            $highest = max($highest, (float) $detail->late_fee_per_day_snapshot);
        }

        if ($highest <= 0) {
            return null;
        }

        return 'Keterlambatan pengembalian dikenakan denda hingga Rp '.number_format($highest, 0, ',', '.').' per hari per barang.';
    }

    /**
     * Nomor telepon penerima (diformat Fonnte) atau null bila tidak tersedia.
     */
    protected function resolveRecipient(Rental $rental): ?string
    {
        $phone = $rental->customer?->phone ?? $rental->guest_phone;

        if (! is_string($phone) || trim($phone) === '') {
            return null;
        }

        return PhoneNumberNormalizer::normalize($phone);
    }

    /**
     * Apakah notifikasi untuk (rental, tipe) sudah pernah dibuat.
     */
    protected function hasNotification(Rental $rental, string $type): bool
    {
        return NotificationLog::withoutGlobalScope('vendor')
            ->where('vendor_id', $rental->vendor_id)
            ->where('rental_id', $rental->id)
            ->where('type', $type)
            ->exists();
    }

    /**
     * Placeholder notifikasi (status `pending`) sebagai kunci idempotensi.
     *
     * Baris dibuat SEBELUM pengiriman supaya dua proses paralel tidak
     * mengirim pesan yang sama dua kali.
     */
    protected function reserveNotification(
        Rental $rental,
        string $type,
        string $phone,
        string $message,
        bool $force
    ): ?NotificationLog {
        return DB::transaction(function () use ($rental, $type, $phone, $message, $force) {
            $existing = NotificationLog::withoutGlobalScope('vendor')
                ->where('vendor_id', $rental->vendor_id)
                ->where('rental_id', $rental->id)
                ->where('type', $type)
                ->lockForUpdate()
                ->first();

            if ($existing && ! $force) {
                return null;
            }

            if ($existing) {
                $existing->update([
                    'recipient_phone' => $phone,
                    'channel' => $this->channel(),
                    'message' => $message,
                    'status' => 'pending',
                    'sent_at' => null,
                ]);

                return $existing;
            }

            return NotificationLog::create([
                'vendor_id' => $rental->vendor_id,
                'user_id' => $rental->user_id,
                'rental_id' => $rental->id,
                'recipient_phone' => $phone,
                'channel' => $this->channel(),
                'type' => $type,
                'message' => $message,
                'status' => 'pending',
            ]);
        });
    }

    /**
     * Kirim pesan lewat kanal aktif.
     */
    protected function dispatch(Rental $rental, string $phone, string $message): bool
    {
        return match ($this->channel()) {
            self::CHANNEL_WHATSAPP => $this->whatsApp->send($phone, $message),
            self::CHANNEL_EMAIL => $this->sendEmail($rental, $message),
            default => true,
        };
    }

    /**
     * Kirim pesan via mailer Laravel.
     */
    protected function sendEmail(Rental $rental, string $message): bool
    {
        $email = $rental->customer?->email;

        if (! is_string($email) || trim($email) === '') {
            Log::warning('Pengingat: email pelanggan tidak tersedia, pengiriman email dilewati.', [
                'rental_id' => $rental->id,
            ]);

            return false;
        }

        try {
            Mail::raw($message, function ($mail) use ($email) {
                $mail->to($email)->subject('Pengingat Pengembalian Barang - TENDAKU');
            });
        } catch (Throwable $exception) {
            Log::error('Pengingat: pengiriman email gagal.', [
                'rental_id' => $rental->id,
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Format tanggal Bahasa Indonesia (contoh: 20 Oktober 2026).
     */
    protected function formatDate(Carbon $date): string
    {
        return $date->day.' '.self::MONTHS[$date->month].' '.$date->year;
    }

    /**
     * Normalisasi nilai tanggal rental menjadi string Y-m-d.
     */
    protected function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->toDateString();
        }

        if (is_string($value) && $value !== '') {
            return Carbon::parse($value)->toDateString();
        }

        return null;
    }
}
