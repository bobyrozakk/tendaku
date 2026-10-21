<?php

namespace Tests\Feature\Weather;

use App\Services\WeatherFormatter;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'weather.endpoint' => 'https://api.open-meteo.com/v1/forecast',
            'weather.retry_times' => 1,
            'weather.retry_sleep_ms' => 1,
            'weather.cache_ttl_minutes' => 30,
        ]);
    }

    public function test_it_returns_formatted_forecast_for_a_known_spot(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->openMeteoPayload(), 200),
        ]);

        $result = app(WeatherService::class)->forecast(
            'bromo',
            Carbon::parse('2026-10-20'),
            Carbon::parse('2026-10-21')
        );

        $this->assertFalse($result['error']);
        $this->assertNull($result['error_message']);
        $this->assertSame('bromo', $result['spot']);
        $this->assertSame('Gunung Bromo', $result['label']);
        $this->assertCount(2, $result['days']);

        $this->assertSame([
            'date' => '2026-10-20',
            'temp_min' => 4.0,
            'temp_max' => 15.0,
            'precipitation_mm' => 12.5,
            'precipitation_probability' => 70,
            'wind_kmh' => 32.0,
            'weather_code' => 63,
            'label' => 'Hujan sedang',
            'is_extreme' => false,
        ], $result['days'][0]);

        $this->assertSame('2026-10-21', $result['days'][1]['date']);
    }

    public function test_it_sends_the_expected_query_parameters_to_open_meteo(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->openMeteoPayload(), 200),
        ]);

        app(WeatherService::class)->forecast(
            'bromo',
            Carbon::parse('2026-10-20'),
            Carbon::parse('2026-10-21')
        );

        Http::assertSent(function (Request $request) {
            $url = $request->url();
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            return str_starts_with($url, 'https://api.open-meteo.com/v1/forecast')
                && (float) $query['latitude'] === -7.9431
                && (float) $query['longitude'] === 112.9538
                && $query['timezone'] === 'Asia/Jakarta'
                && $query['start_date'] === '2026-10-20'
                && $query['end_date'] === '2026-10-21'
                && $query['daily'] === 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,wind_speed_10m_max';
        });
    }

    public function test_it_marks_thunderstorm_as_extreme_weather(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->openMeteoPayload(weatherCode: 95), 200),
        ]);

        $result = app(WeatherService::class)->forecast(
            'bromo',
            Carbon::parse('2026-10-20'),
            Carbon::parse('2026-10-20')
        );

        $this->assertTrue($result['days'][0]['is_extreme']);
        $this->assertSame('Badai petir', $result['days'][0]['label']);
    }

    public function test_it_marks_heavy_rain_and_strong_wind_as_extreme_weather(): void
    {
        config(['weather.extreme.precipitation_mm' => 50.0, 'weather.extreme.wind_kmh' => 40.0]);

        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->openMeteoPayload(
                precipitation: 55.0,
                wind: 45.0
            ), 200),
        ]);

        $result = app(WeatherService::class)->forecast(
            'bromo',
            Carbon::parse('2026-10-20'),
            Carbon::parse('2026-10-20')
        );

        $this->assertTrue($result['days'][0]['is_extreme']);
    }

    public function test_it_uses_the_thresholds_from_config(): void
    {
        config(['weather.extreme.precipitation_mm' => 100.0, 'weather.extreme.wind_kmh' => 100.0]);

        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->openMeteoPayload(
                precipitation: 55.0,
                wind: 45.0
            ), 200),
        ]);

        $result = app(WeatherService::class)->forecast(
            'bromo',
            Carbon::parse('2026-10-20'),
            Carbon::parse('2026-10-20')
        );

        $this->assertFalse($result['days'][0]['is_extreme']);
    }

    public function test_it_caches_the_forecast_per_spot_and_date_range(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response($this->openMeteoPayload(), 200),
        ]);

        $service = app(WeatherService::class);

        $service->forecast('bromo', Carbon::parse('2026-10-20'), Carbon::parse('2026-10-21'));
        $service->forecast('bromo', Carbon::parse('2026-10-20'), Carbon::parse('2026-10-21'));

        Http::assertSentCount(1);

        $service->forecast('bromo', Carbon::parse('2026-10-22'), Carbon::parse('2026-10-23'));

        Http::assertSentCount(2);
    }

    public function test_it_returns_safe_empty_structure_when_api_fails(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response(['error' => true, 'reason' => 'invalid date range'], 400),
        ]);

        $result = app(WeatherService::class)->forecast(
            'bromo',
            Carbon::parse('2027-06-01'),
            Carbon::parse('2027-06-05')
        );

        $this->assertTrue($result['error']);
        $this->assertNotNull($result['error_message']);
        $this->assertSame([], $result['days']);
        $this->assertSame('bromo', $result['spot']);
    }

    public function test_it_returns_safe_empty_structure_on_connection_failure(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::failedConnection(),
        ]);

        $result = app(WeatherService::class)->forecast(
            'bromo',
            Carbon::parse('2026-10-20'),
            Carbon::parse('2026-10-21')
        );

        $this->assertTrue($result['error']);
        $this->assertSame([], $result['days']);
    }

    public function test_it_returns_safe_empty_structure_for_unknown_spot(): void
    {
        Http::fake();

        $result = app(WeatherService::class)->forecast(
            'tidak-ada',
            Carbon::parse('2026-10-20'),
            Carbon::parse('2026-10-21')
        );

        $this->assertTrue($result['error']);
        $this->assertSame([], $result['days']);
        Http::assertNothingSent();
    }

    public function test_it_maps_wmo_codes_to_indonesian_labels(): void
    {
        $formatter = app(WeatherFormatter::class);

        $this->assertSame('Cerah', $formatter->label(0));
        $this->assertSame('Berawan sebagian', $formatter->label(2));
        $this->assertSame('Hujan sedang', $formatter->label(63));
        $this->assertSame('Hujan lebat', $formatter->label(65));
        $this->assertSame('Badai petir', $formatter->label(95));
        $this->assertSame('Tidak diketahui', $formatter->label(999));
    }

    public function test_it_exposes_available_spots(): void
    {
        $spots = app(WeatherService::class)->availableSpots();

        $this->assertArrayHasKey('bromo', $spots);
        $this->assertArrayHasKey('ranu_kumbolo', $spots);
        $this->assertArrayHasKey('batu', $spots);
    }

    /**
     * Payload contoh respons Open-Meteo.
     *
     * @return array<string, mixed>
     */
    protected function openMeteoPayload(
        int $weatherCode = 63,
        float $precipitation = 12.5,
        float $wind = 32.0
    ): array {
        return [
            'latitude' => -7.9789,
            'longitude' => 112.9803,
            'timezone' => 'Asia/Jakarta',
            'daily' => [
                'time' => ['2026-10-20', '2026-10-21'],
                'weather_code' => [$weatherCode, 2],
                'temperature_2m_max' => [15.0, 20.2],
                'temperature_2m_min' => [4.0, 11.8],
                'precipitation_sum' => [$precipitation, 0.0],
                'precipitation_probability_max' => [70, 2],
                'wind_speed_10m_max' => [$wind, 12.1],
            ],
        ];
    }
}
