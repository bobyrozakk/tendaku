<?php

namespace App\Livewire\Customer;

use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Panel cuaca pada halaman Booking.
 *
 * Menampilkan prakiraan untuk seluruh masa sewa (tanggal ambil sampai tanggal
 * kembali). Tanggal dikirim dari komponen Alpine halaman checkout setiap kali
 * pelanggan mengubah jadwal sewa.
 */
#[Lazy]
class BookingWeather extends Component
{
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
     * Nama bulan dalam Bahasa Indonesia.
     *
     * @var array<int, string>
     */
    public const MONTH_NAMES = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    /**
     * Kunci spot cuaca aktif (lihat config/weather.php).
     */
    public string $spot = '';

    /**
     * Tanggal mulai sewa (format Y-m-d).
     */
    public string $pickupDate = '';

    /**
     * Tanggal selesai sewa (format Y-m-d).
     */
    public string $returnDate = '';

    /**
     * Kota vendor, dipakai untuk menentukan spot cuaca default.
     */
    public string $vendorCity = '';

    /**
     * Inisialisasi rentang tanggal dari halaman checkout.
     */
    public function mount(
        ?string $vendorCity = null,
        ?string $pickupDate = null,
        ?string $returnDate = null,
    ): void {
        $this->vendorCity = (string) $vendorCity;
        $this->pickupDate = (string) $pickupDate;
        $this->returnDate = (string) $returnDate;
        $this->spot = app(WeatherService::class)->spotForCity($this->vendorCity);

        $this->normalizeDates();
    }

    /**
     * Sinkronkan tanggal ketika jadwal sewa diubah pada halaman checkout.
     */
    #[On('booking-weather-updated')]
    public function syncDates(?string $pickupDate = null, ?string $returnDate = null): void
    {
        $this->pickupDate = (string) $pickupDate;
        $this->returnDate = (string) $returnDate;

        $this->normalizeDates();
    }

    /**
     * Pastikan spot yang dipilih tetap terdaftar di config.
     */
    public function updatedSpot(): void
    {
        $this->spot = $this->resolveSpot($this->spot);
    }

    public function render(WeatherService $weatherService): View
    {
        $timezone = (string) config('weather.timezone', 'Asia/Jakarta');
        $today = Carbon::today($timezone);

        $this->spot = $this->resolveSpot($this->spot);

        $start = $this->parseDate($this->pickupDate) ?? $today->copy();
        $end = $this->parseDate($this->returnDate) ?? $start->copy();

        if ($end->lt($start)) {
            $end = $start->copy();
        }

        $horizonDays = max(1, (int) config('weather.forecast_horizon_days', 16));
        $horizonEnd = $today->copy()->addDays($horizonDays - 1);

        $rangeStart = $start->lt($today) ? $today->copy() : $start->copy();
        $rangeEnd = $end->gt($horizonEnd) ? $horizonEnd->copy() : $end->copy();
        $hasForecast = $rangeStart->lte($rangeEnd);

        $forecast = null;
        $days = [];
        $extremeDays = [];
        $generatedAt = null;
        $noForecastMessage = null;
        $partialMessage = null;

        if ($hasForecast) {
            $forecast = $weatherService->forecast($this->spot, $rangeStart, $rangeEnd);
            $days = $this->enrich((array) ($forecast['days'] ?? []), $today);

            $extremeDays = array_values(array_filter(
                $days,
                fn (array $day): bool => (bool) ($day['is_extreme'] ?? false)
            ));

            if (filled($forecast['generated_at'] ?? null)) {
                $generatedAt = Carbon::parse((string) $forecast['generated_at'])
                    ->timezone($timezone)
                    ->format('d M Y · H:i');
            }

            if ($rangeEnd->lt($end)) {
                $partialMessage = 'Prakiraan baru tersedia sampai '
                    .$this->dateLabel($rangeEnd)
                    .'. Tanggal setelahnya belum bisa diprediksi karena Open-Meteo hanya menyediakan prakiraan '
                    .$horizonDays.' hari ke depan.';
            }
        } elseif ($end->lt($today)) {
            $noForecastMessage = 'Masa sewa yang dipilih sudah berakhir, sehingga prakiraan cuaca tidak lagi ditampilkan.';
        } else {
            $availableFrom = $start->copy()->subDays(max(0, $horizonDays - 1));

            $noForecastMessage = 'Prakiraan cuaca untuk periode ini belum tersedia. '
                .'Open-Meteo menyediakan prakiraan maksimal '.$horizonDays.' hari ke depan, '
                .'jadi data tanggal sewa akan muncul mulai '.$this->dateLabel($availableFrom).'.';
        }

        return view('livewire.customer.booking-weather', [
            'forecast' => $forecast,
            'days' => $days,
            'extremeDays' => $extremeDays,
            'generatedAt' => $generatedAt,
            'noForecastMessage' => $noForecastMessage,
            'partialMessage' => $partialMessage,
            'horizonDays' => $horizonDays,
            'rangeLabel' => $this->rangeLabel($start, $end),
            'locationLabel' => $this->locationLabel(),
            'spotGroups' => $this->spotGroups(),
            'hasSelectedDates' => $this->parseDate($this->pickupDate) !== null,
        ]);
    }

    /**
     * Tampilan ringan sebelum komponen dimuat (lazy loading).
     */
    public function placeholder(): string
    {
        return view('livewire.customer.booking-weather-placeholder')->render();
    }

    /**
     * Rentang tanggal dalam format ringkas Bahasa Indonesia.
     */
    protected function rangeLabel(Carbon $start, Carbon $end): string
    {
        if ($start->isSameDay($end)) {
            return $this->dateLabel($start);
        }

        if ($start->format('Y-m') === $end->format('Y-m')) {
            return $start->day.' – '.$end->day.' '
                .self::MONTH_NAMES[$end->month].' '.$end->year;
        }

        if ($start->year === $end->year) {
            return $start->day.' '.self::MONTH_NAMES[$start->month]
                .' – '.$end->day.' '.self::MONTH_NAMES[$end->month].' '.$end->year;
        }

        return $this->dateLabel($start).' – '.$this->dateLabel($end);
    }

    protected function dateLabel(Carbon $date): string
    {
        return $date->day.' '.self::MONTH_NAMES[$date->month].' '.$date->year;
    }

    /**
     * Tambahkan atribut presentasi (ikon, nama hari, rentang suhu).
     *
     * @param  array<int, array<string, mixed>>  $days
     * @return array<int, array<string, mixed>>
     */
    protected function enrich(array $days, Carbon $today): array
    {
        return array_map(function (array $day) use ($today): array {
            $date = $this->parseDate((string) ($day['date'] ?? '')) ?? $today->copy();

            $day['icon'] = $this->iconFor((int) ($day['weather_code'] ?? 0));
            $day['day_name'] = self::DAY_NAMES[$date->format('l')] ?? $date->format('l');
            $day['date_short'] = $date->format('d M');
            $day['date_full'] = $this->dateLabel($date);
            $day['is_today'] = $date->isSameDay($today);
            $day['severity'] = $this->severityFor($day);
            $day['temp_range'] = number_format((float) ($day['temp_min'] ?? 0), 0)
                .'° – '
                .number_format((float) ($day['temp_max'] ?? 0), 0).'°C';

            return $day;
        }, $days);
    }

    /**
     * Tingkat kewaspadaan harian untuk warna kartu cuaca.
     *
     * `danger` dipakai untuk cuaca ekstrem, `alert` untuk hujan lebat/angin
     * kencang, `warning` untuk hujan ringan-sedang, `watch` untuk berawan atau
     * berkabut, dan `safe` untuk cuaca cerah.
     *
     * @param  array<string, mixed>  $day
     */
    protected function severityFor(array $day): string
    {
        if ((bool) ($day['is_extreme'] ?? false)) {
            return 'danger';
        }

        $code = (int) ($day['weather_code'] ?? 0);
        $precipitation = (float) ($day['precipitation_mm'] ?? 0);
        $wind = (float) ($day['wind_kmh'] ?? 0);

        if (
            in_array($code, [65, 67, 82, 95, 96, 99], true)
            || $precipitation >= 20
            || $wind >= 30
        ) {
            return 'alert';
        }

        if (
            in_array($code, [51, 53, 55, 56, 57, 61, 63, 66, 71, 73, 75, 77, 80, 81, 85, 86], true)
            || $precipitation > 0
        ) {
            return 'warning';
        }

        if (in_array($code, [2, 3, 45, 48], true)) {
            return 'watch';
        }

        return 'safe';
    }

    /**
     * Ikon Lucide untuk kode cuaca WMO (lihat https://lucide.dev/icons/).
     */
    protected function iconFor(int $weatherCode): string
    {
        return match (true) {
            $weatherCode === 0 => 'sun',
            $weatherCode === 1 => 'cloud-sun',
            $weatherCode === 2 => 'cloud-sun',
            $weatherCode === 3 => 'cloud',
            in_array($weatherCode, [45, 48], true) => 'cloud-fog',
            in_array($weatherCode, [51, 53, 55, 56, 57], true) => 'cloud-drizzle',
            in_array($weatherCode, [61, 63, 65, 66, 67], true) => 'cloud-rain',
            in_array($weatherCode, [71, 73, 75, 77], true) => 'cloud-snow',
            in_array($weatherCode, [80, 81, 82], true) => 'cloud-rain-wind',
            in_array($weatherCode, [85, 86], true) => 'cloud-snow',
            in_array($weatherCode, [95, 96, 99], true) => 'cloud-lightning',
            default => 'thermometer',
        };
    }

    /**
     * Pastikan tanggal pada properti berupa Y-m-d yang valid dan berurutan.
     */
    protected function normalizeDates(): void
    {
        $timezone = (string) config('weather.timezone', 'Asia/Jakarta');
        $start = $this->parseDate($this->pickupDate);

        if (! $start) {
            return;
        }

        $end = $this->parseDate($this->returnDate) ?? $start->copy();

        if ($end->lt($start)) {
            $end = $start->copy();
        }

        $this->pickupDate = $start->timezone($timezone)->toDateString();
        $this->returnDate = $end->timezone($timezone)->toDateString();
    }

    /**
     * Parse tanggal Y-m-d; kembalikan null bila tidak valid.
     */
    protected function parseDate(string $value): ?Carbon
    {
        $value = trim($value);

        if ($value === '' || ! Carbon::hasFormat($value, 'Y-m-d')) {
            return null;
        }

        try {
            return Carbon::parse($value, (string) config('weather.timezone', 'Asia/Jakarta'))
                ->startOfDay();
        } catch (\Throwable) {
            return null;
        }
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

        $default = (string) config('weather.default_spot', 'gunung_bromo');

        if (in_array($default, $spots, true)) {
            return $default;
        }

        return (string) ($spots[0] ?? 'gunung_bromo');
    }

    /**
     * Daftar spot dikelompokkan per wilayah untuk dropdown.
     *
     * @return array<string, array<string, string>>
     */
    protected function spotGroups(): array
    {
        $groups = [];

        foreach ((array) config('weather.spots', []) as $key => $spot) {
            $region = (string) ($spot['region'] ?? 'Lainnya');
            $groups[$region][$key] = (string) ($spot['label'] ?? $key);
        }

        return $groups;
    }

    protected function locationLabel(): string
    {
        return (string) config("weather.spots.{$this->spot}.label", $this->spot);
    }
}
