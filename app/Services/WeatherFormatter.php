<?php

namespace App\Services;

/**
 * Formatter keluaran WeatherService.
 *
 * Mengubah payload harian Open-Meteo menjadi struktur JSON yang stabil —
 * inilah kontrak resmi ke Andini untuk `WeatherCalendar` & banner peringatan.
 */
class WeatherFormatter
{
    /**
     * Parameter harian yang diminta ke Open-Meteo.
     */
    public const DAILY_FIELDS = [
        'weather_code',
        'temperature_2m_max',
        'temperature_2m_min',
        'precipitation_sum',
        'precipitation_probability_max',
        'wind_speed_10m_max',
    ];

    /**
     * Label Bahasa Indonesia untuk kode cuaca WMO.
     *
     * @var array<int, string>
     */
    public const WMO_LABELS = [
        0 => 'Cerah',
        1 => 'Cerah berawan',
        2 => 'Berawan sebagian',
        3 => 'Berawan',
        45 => 'Berkabut',
        48 => 'Kabut beku',
        51 => 'Gerimis ringan',
        53 => 'Gerimis sedang',
        55 => 'Gerimis lebat',
        56 => 'Gerimis dingin ringan',
        57 => 'Gerimis dingin lebat',
        61 => 'Hujan ringan',
        63 => 'Hujan sedang',
        65 => 'Hujan lebat',
        66 => 'Hujan dingin ringan',
        67 => 'Hujan dingin lebat',
        71 => 'Salju ringan',
        73 => 'Salju sedang',
        75 => 'Salju lebat',
        77 => 'Butiran salju',
        80 => 'Hujan lokal ringan',
        81 => 'Hujan lokal sedang',
        82 => 'Hujan lokal deras',
        85 => 'Salju lokal ringan',
        86 => 'Salju lokal berat',
        95 => 'Badai petir',
        96 => 'Badai petir dan es ringan',
        99 => 'Badai petir dan es berat',
    ];

    /**
     * Bangun struktur keluaran akhir dari payload `daily` Open-Meteo.
     *
     * @param  array<string, mixed>  $daily  Isi `daily` pada respons Open-Meteo.
     * @return array<string, mixed>
     */
    public function format(string $spot, string $label, array $daily): array
    {
        $times = is_array($daily['time'] ?? null) ? $daily['time'] : [];
        $days = [];

        foreach (array_values($times) as $index => $date) {
            $code = (int) ($daily['weather_code'][$index] ?? 0);
            $precipitation = (float) ($daily['precipitation_sum'][$index] ?? 0);
            $wind = (float) ($daily['wind_speed_10m_max'][$index] ?? 0);

            $days[] = [
                'date' => (string) $date,
                'temp_min' => (float) ($daily['temperature_2m_min'][$index] ?? 0),
                'temp_max' => (float) ($daily['temperature_2m_max'][$index] ?? 0),
                'precipitation_mm' => $precipitation,
                'precipitation_probability' => (int) ($daily['precipitation_probability_max'][$index] ?? 0),
                'wind_kmh' => $wind,
                'weather_code' => $code,
                'label' => $this->label($code),
                'is_extreme' => $this->isExtreme($code, $precipitation, $wind),
            ];
        }

        return [
            'spot' => $spot,
            'label' => $label,
            'generated_at' => now(config('weather.timezone', 'Asia/Jakarta'))->toIso8601String(),
            'error' => false,
            'error_message' => null,
            'days' => $days,
        ];
    }

    /**
     * Bangun struktur keluaran kosong saat terjadi kegagalan.
     *
     * @return array<string, mixed>
     */
    public function error(string $spot, string $label, string $message): array
    {
        return [
            'spot' => $spot,
            'label' => $label,
            'generated_at' => now(config('weather.timezone', 'Asia/Jakarta'))->toIso8601String(),
            'error' => true,
            'error_message' => $message,
            'days' => [],
        ];
    }

    /**
     * Label Bahasa Indonesia untuk kode cuaca WMO.
     */
    public function label(int $weatherCode): string
    {
        return self::WMO_LABELS[$weatherCode] ?? 'Tidak diketahui';
    }

    /**
     * Deteksi cuaca ekstrem berdasarkan ambang di `config/weather.php`.
     */
    public function isExtreme(int $weatherCode, float $precipitationMm, float $windKmh): bool
    {
        $extreme = config('weather.extreme', []);

        $thunderstormCodes = (array) ($extreme['thunderstorm_codes'] ?? []);
        $precipitationLimit = (float) ($extreme['precipitation_mm'] ?? 0);
        $windLimit = (float) ($extreme['wind_kmh'] ?? 0);

        if (in_array($weatherCode, array_map('intval', $thunderstormCodes), true)) {
            return true;
        }

        if ($precipitationLimit > 0 && $precipitationMm >= $precipitationLimit) {
            return true;
        }

        return $windLimit > 0 && $windKmh >= $windLimit;
    }
}
