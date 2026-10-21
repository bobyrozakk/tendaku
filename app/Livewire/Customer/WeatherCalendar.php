<?php

namespace App\Livewire\Customer;

use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Kalender cuaca spot camping untuk pelanggan.
 *
 * [cross-module] Scope frontend adalah milik Andini; data diambil dari
 * `WeatherService::forecast()` (backend Boby) sesuai kontrak docs/contract.md §7.B.
 */
class WeatherCalendar extends Component
{
    /**
     * Opsi periode prakiraan (jumlah hari ke depan).
     *
     * @var array<int, string>
     */
    public const PERIOD_OPTIONS = [
        3 => '3 Hari',
        7 => '7 Hari',
        14 => '14 Hari',
    ];

    /**
     * Periode default bila parameter URL tidak valid.
     */
    public const DEFAULT_DAYS = 7;

    /**
     * Nama hari dalam Bahasa Indonesia.
     *
     * @var array<string, string>
     */
    public const DAY_NAMES = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
    ];

    /**
     * Kunci spot cuaca aktif (lihat config/weather.php).
     */
    #[Url(as: 'spot', history: true, keep: false)]
    public string $spot = 'bromo';

    /**
     * Panjang periode prakiraan dalam hitungan hari.
     *
     * Disimpan sebagai string agar parameter URL yang tidak valid tidak
     * menggagalkan render halaman.
     */
    #[Url(as: 'periode', history: true, keep: false)]
    public string $days = '7';

    public function mount(): void
    {
        $this->spot = $this->resolveSpot($this->spot);
        $this->days = $this->resolveDays($this->days);
    }

    public function updatedSpot(): void
    {
        $this->spot = $this->resolveSpot($this->spot);
    }

    public function updatedDays(): void
    {
        $this->days = $this->resolveDays($this->days);
    }

    /**
     * Muat ulang prakiraan dengan mengabaikan cache yang tersimpan.
     */
    public function refreshForecast(WeatherService $weatherService): void
    {
        [$start, $end] = $this->period();

        $weatherService->forget($this->spot, $start, $end);
    }

    public function render(WeatherService $weatherService): View
    {
        [$start, $end] = $this->period();

        $forecast = $weatherService->forecast($this->spot, $start, $end);

        return view('livewire.customer.weather-calendar', [
            'forecast' => $this->enrich($forecast),
            'spots' => $weatherService->availableSpots(),
            'periodOptions' => self::PERIOD_OPTIONS,
        ]);
    }

    /**
     * Rentang tanggal periode aktif dalam zona waktu WIB.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function period(): array
    {
        $timezone = (string) config('weather.timezone', 'Asia/Jakarta');

        $start = Carbon::today($timezone);
        $end = $start->copy()->addDays(max((int) $this->days, 1) - 1);

        return [$start, $end];
    }

    /**
     * Tambahkan atribut presentasi (ikon, nama hari, penanda hari ini).
     *
     * Kontrak JSON yang dikembalikan `WeatherService` tidak diubah — atribut
     * tambahan ini hanya dipakai oleh layer tampilan.
     *
     * @param  array<string, mixed>  $forecast
     * @return array<string, mixed>
     */
    protected function enrich(array $forecast): array
    {
        $timezone = (string) config('weather.timezone', 'Asia/Jakarta');

        $today = Carbon::today($timezone);

        $days = [];

        foreach ((array) ($forecast['days'] ?? []) as $day) {
            $date = Carbon::parse((string) ($day['date'] ?? $today->toDateString()));

            $day['icon'] = $this->iconFor((int) ($day['weather_code'] ?? 0));
            $day['day_name'] = self::DAY_NAMES[$date->format('l')] ?? $date->format('l');
            $day['is_today'] = $date->isSameDay($today);

            $days[] = $day;
        }

        $forecast['days'] = $days;

        return $forecast;
    }

    /**
     * Ikon emoji untuk kode cuaca WMO.
     */
    protected function iconFor(int $weatherCode): string
    {
        return match (true) {
            $weatherCode === 0 => '☀️',
            $weatherCode === 1 => '🌤️',
            $weatherCode === 2 => '⛅',
            $weatherCode === 3 => '☁️',
            in_array($weatherCode, [45, 48], true) => '🌫️',
            in_array($weatherCode, [51, 53, 55, 56, 57], true) => '🌦️',
            in_array($weatherCode, [61, 63, 65, 66, 67], true) => '🌧️',
            in_array($weatherCode, [71, 73, 75, 77], true) => '🌨️',
            in_array($weatherCode, [80, 81, 82], true) => '🌧️',
            in_array($weatherCode, [85, 86], true) => '🌨️',
            in_array($weatherCode, [95, 96, 99], true) => '⛈️',
            default => '🌡️',
        };
    }

    /**
     * Pastikan spot yang dipilih terdaftar di config/weather.php.
     */
    protected function resolveSpot(string $spot): string
    {
        $spots = array_keys((array) config('weather.spots', []));

        if (in_array($spot, $spots, true)) {
            return $spot;
        }

        return $spots[0] ?? 'bromo';
    }

    /**
     * Pastikan periode yang dipilih termasuk salah satu opsi yang tersedia.
     */
    protected function resolveDays(string $days): string
    {
        if (ctype_digit($days) && array_key_exists((int) $days, self::PERIOD_OPTIONS)) {
            return (string) (int) $days;
        }

        return (string) self::DEFAULT_DAYS;
    }
}
