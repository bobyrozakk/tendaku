<?php

namespace Tests\Feature\Customer;

use App\Livewire\Customer\WeatherCalendar;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class WeatherCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-21 08:00:00');

        config([
            'weather.endpoint' => 'https://api.open-meteo.com/v1/forecast',
            'weather.retry_times' => 1,
            'weather.retry_sleep_ms' => 1,
            'weather.cache_ttl_minutes' => 30,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_halaman_cuaca_dapat_diakses_tanpa_login(): void
    {
        $this->fakeOpenMeteo();

        $this->get(route('customer.weather.index'))
            ->assertOk()
            ->assertSee('Prakiraan Cuaca Spot Camping')
            ->assertSee('Gunung Bromo');
    }

    public function test_menampilkan_kartu_prakiraan_harian(): void
    {
        $this->fakeOpenMeteo();

        Livewire::test(WeatherCalendar::class)
            ->assertOk()
            ->assertSee('Hujan sedang')
            ->assertSee('4° – 15°C')
            ->assertSeeText('12.5 mm')
            ->assertSeeText('70%')
            ->assertSeeText('32 km/j');
    }

    public function test_menampilkan_banner_peringatan_saat_cuaca_ekstrem(): void
    {
        $this->fakeOpenMeteo(precipitation: 60.0);

        Livewire::test(WeatherCalendar::class)
            ->assertOk()
            ->assertSee('Peringatan Cuaca Ekstrem')
            ->assertSee('Cuaca ekstrem — siapkan proteksi ekstra.');
    }

    public function test_menampilkan_banner_error_tanpa_melempar_exception(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response(['error' => true, 'reason' => 'invalid date range'], 400),
        ]);

        $this->get(route('customer.weather.index'))
            ->assertOk()
            ->assertSee('Prakiraan cuaca belum tersedia')
            ->assertSee('Layanan cuaca merespons error.');
    }

    public function test_ganti_spot_memperbarui_prakiraan(): void
    {
        $this->fakeOpenMeteo();

        Livewire::test(WeatherCalendar::class)
            ->set('spot', 'ranu_kumbolo')
            ->assertSet('spot', 'ranu_kumbolo')
            ->assertSee('Ranu Kumbolo');
    }

    public function test_periode_menentukan_jumlah_hari_yang_ditampilkan(): void
    {
        $this->fakeOpenMeteo();

        Livewire::test(WeatherCalendar::class)
            ->set('days', '3')
            ->assertSet('days', '3')
            ->assertSee('21 Oktober 2026')
            ->assertSee('23 Oktober 2026')
            ->assertDontSee('24 Oktober 2026');
    }

    public function test_parameter_url_tidak_valid_tidak_menggagalkan_halaman(): void
    {
        $this->fakeOpenMeteo();

        $this->get(route('customer.weather.index', ['spot' => 'entah', 'periode' => 'abc']))
            ->assertOk()
            ->assertSee('Kota Malang');
    }

    /**
     * Fake respons Open-Meteo yang mengikuti rentang tanggal yang diminta.
     */
    protected function fakeOpenMeteo(
        int $weatherCode = 63,
        float $precipitation = 12.5,
        float $wind = 32.0
    ): void {
        Http::fake(function (Request $request) use ($weatherCode, $precipitation, $wind) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            $start = (string) ($query['start_date'] ?? Carbon::today(config('weather.timezone'))->toDateString());
            $end = (string) ($query['end_date'] ?? $start);

            $days = (int) max(1, abs(Carbon::parse($start)->diffInDays(Carbon::parse($end))) + 1);

            return Http::response($this->payload($days, $start, $weatherCode, $precipitation, $wind), 200);
        });
    }

    /**
     * Payload contoh respons Open-Meteo untuk sejumlah hari ke depan.
     *
     * @return array<string, mixed>
     */
    protected function payload(
        int $days = 7,
        string $startDate = '2026-10-21',
        int $weatherCode = 63,
        float $precipitation = 12.5,
        float $wind = 32.0
    ): array {
        $start = Carbon::parse($startDate);

        $dates = [];
        $codes = [];
        $max = [];
        $min = [];
        $precipitationSum = [];
        $probability = [];
        $windSpeeds = [];

        for ($index = 0; $index < $days; $index++) {
            $dates[] = $start->copy()->addDays($index)->toDateString();
            $codes[] = $weatherCode;
            $max[] = 15.0 + $index;
            $min[] = 4.0 + $index;
            $precipitationSum[] = $precipitation;
            $probability[] = 70;
            $windSpeeds[] = $wind;
        }

        return [
            'daily' => [
                'time' => $dates,
                'weather_code' => $codes,
                'temperature_2m_max' => $max,
                'temperature_2m_min' => $min,
                'precipitation_sum' => $precipitationSum,
                'precipitation_probability_max' => $probability,
                'wind_speed_10m_max' => $windSpeeds,
            ],
        ];
    }
}
