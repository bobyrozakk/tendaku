<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Service prakiraan cuaca spot camping (Open-Meteo — gratis, tanpa API key).
 *
 * Kontrak keluaran JSON-nya ada di `docs/contract.md` §7 dan dipakai langsung oleh
 * panel cuaca halaman Booking (`BookingWeather`) + banner peringatan.
 */
class WeatherService
{
    public function __construct(
        protected WeatherFormatter $formatter
    ) {}

    /**
     * Ambil prakiraan cuaca untuk spot dan rentang tanggal tertentu.
     *
     * Tidak pernah melempar exception: bila layanan cuaca gagal, dikembalikan
     * struktur kosong dengan `error = true` supaya UI tetap berjalan.
     *
     * @return array{spot: string, label: string, generated_at: string, error: bool, error_message: ?string, days: array<int, array<string, mixed>>}
     */
    public function forecast(string $spot, Carbon $start, Carbon $end): array
    {
        $spotKey = $this->normalizeSpot($spot);
        $spotConfig = config("weather.spots.{$spotKey}");

        if (! is_array($spotConfig)) {
            return $this->formatter->error($spotKey, $spot, "Spot '{$spot}' tidak terdaftar.");
        }

        $label = (string) ($spotConfig['label'] ?? $spotKey);

        $startDate = $start->copy()->startOfDay();
        $endDate = $end->copy()->startOfDay();

        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $cacheKey = $this->cacheKey($spotKey, $startDate, $endDate);
        $staleKey = $cacheKey.':stale';

        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $result = $this->request($spotKey, $spotConfig, $label, $startDate, $endDate);

        if (! ($result['error'] ?? false)) {
            // Sukses: cache normal + salinan "basi" yang lebih lama sebagai cadangan.
            Cache::put($cacheKey, $result, now()->addMinutes((int) config('weather.cache_ttl_minutes', 30)));
            Cache::put($staleKey, $result, now()->addHours(12));

            return $result;
        }

        // Gagal: JANGAN simpan error selama 30 menit (dulu memakai Cache::remember sehingga
        // satu kegagalan sesaat membuat cuaca "hilang" untuk semua orang). Pakai data lama
        // bila ada; kalau tidak, cache error hanya beberapa detik agar tidak membanjiri API.
        $stale = Cache::get($staleKey);

        if (is_array($stale)) {
            return $stale;
        }

        Cache::put($cacheKey, $result, now()->addSeconds(20));

        return $result;
    }

    /**
     * Buang cache forecast untuk spot + rentang tanggal tertentu.
     *
     * Dipakai tombol "Perbarui" pada UI customer agar data tidak menunggu
     * berakhirnya masa cache.
     */
    public function forget(string $spot, Carbon $start, Carbon $end): void
    {
        $spotKey = $this->normalizeSpot($spot);

        $startDate = $start->copy()->startOfDay();
        $endDate = $end->copy()->startOfDay();

        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        Cache::forget($this->cacheKey($spotKey, $startDate, $endDate));
    }

    /**
     * Daftar spot yang tersedia beserta koordinatnya.
     *
     * @return array<string, array{label: string, latitude: float, longitude: float}>
     */
    public function availableSpots(): array
    {
        return (array) config('weather.spots', []);
    }

    /**
     * Tebak spot cuaca dari nama kota vendor.
     *
     * Dipakai sebagai lokasi default panel cuaca pada halaman Booking.
     * Pelanggan tetap dapat mengganti spot lewat dropdown di panel tersebut.
     */
    public function spotForCity(?string $city): string
    {
        $spots = (array) config('weather.spots', []);
        $default = (string) config('weather.default_spot', 'gunung_bromo');

        if (! array_key_exists($default, $spots)) {
            $default = (string) (array_key_first($spots) ?? 'gunung_bromo');
        }

        $normalizedCity = $this->normalizeLabel((string) $city);

        if ($normalizedCity === '') {
            return $default;
        }

        foreach ($spots as $key => $spot) {
            $label = $this->normalizeLabel((string) ($spot['label'] ?? $key));

            if ($label === $normalizedCity || $this->shortLocationName($label) === $normalizedCity) {
                return (string) $key;
            }
        }

        foreach ($spots as $key => $spot) {
            $label = $this->shortLocationName(
                $this->normalizeLabel((string) ($spot['label'] ?? $key))
            );

            if ($label !== '' && (
                str_contains($normalizedCity, $label)
                || str_contains($label, $normalizedCity)
            )) {
                return (string) $key;
            }
        }

        return $default;
    }

    /**
     * Ambil data mentah Open-Meteo untuk spot + rentang tanggal.
     *
     * @param  array<string, mixed>  $spotConfig
     * @return array<string, mixed>
     */
    protected function request(
        string $spotKey,
        array $spotConfig,
        string $label,
        Carbon $start,
        Carbon $end
    ): array {
        try {
            $response = Http::connectTimeout(4)
                ->timeout((int) config('weather.timeout', 6))
                ->retry(
                    (int) config('weather.retry_times', 2),
                    (int) config('weather.retry_sleep_ms', 200),
                    throw: false,
                )
                ->get((string) config('weather.endpoint'), [
                    'latitude' => $spotConfig['latitude'],
                    'longitude' => $spotConfig['longitude'],
                    'daily' => implode(',', WeatherFormatter::DAILY_FIELDS),
                    'timezone' => (string) config('weather.timezone', 'Asia/Jakarta'),
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ]);
        } catch (Throwable $exception) {
            Log::warning('WeatherService: permintaan ke Open-Meteo gagal.', [
                'spot' => $spotKey,
                'exception' => $exception->getMessage(),
            ]);

            return $this->formatter->error($spotKey, $label, 'Gagal menghubungi layanan cuaca.');
        }

        if (! $response->successful()) {
            Log::warning('WeatherService: Open-Meteo merespons HTTP error.', [
                'spot' => $spotKey,
                'status' => $response->status(),
            ]);

            return $this->formatter->error($spotKey, $label, 'Layanan cuaca merespons error.');
        }

        $payload = $response->json();
        $daily = is_array($payload) ? ($payload['daily'] ?? null) : null;

        if (! is_array($daily)) {
            return $this->formatter->error($spotKey, $label, 'Format respons cuaca tidak dikenali.');
        }

        return $this->formatter->format($spotKey, $label, $daily);
    }

    /**
     * Kunci cache forecast untuk spot + rentang tanggal tertentu.
     */
    protected function cacheKey(string $spotKey, Carbon $start, Carbon $end): string
    {
        return sprintf(
            'weather:forecast:%s:%s:%s',
            $spotKey,
            $start->toDateString(),
            $end->toDateString()
        );
    }

    /**
     * Normalisasi nama spot menjadi kunci config (lowercase, spasi → underscore).
     */
    protected function normalizeSpot(string $spot): string
    {
        return str_replace([' ', '-', '/'], '_', strtolower(trim($spot)));
    }

    /**
     * Normalisasi nama lokasi untuk pencocokan kota vendor.
     */
    protected function normalizeLabel(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', mb_strtolower($value)));
    }

    /**
     * Hapus prefiks "Kota"/"Kabupaten" agar nama lokasi mudah dicocokkan.
     */
    protected function shortLocationName(string $label): string
    {
        return trim((string) preg_replace('/^(kota|kabupaten)\s+/', '', $label));
    }
}
