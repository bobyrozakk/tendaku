<?php

namespace Tests\Feature\Customer;

use App\Livewire\Customer\BookingWeather;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Booking\CartService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class BookingWeatherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-15 08:00:00');

        config([
            'weather.endpoint' => 'https://api.open-meteo.com/v1/forecast',
            'weather.retry_times' => 1,
            'weather.retry_sleep_ms' => 1,
            'weather.cache_ttl_minutes' => 30,
            'weather.forecast_horizon_days' => 16,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_menampilkan_prakiraan_untuk_seluruh_masa_sewa(): void
    {
        $this->fakeOpenMeteo();

        $this->weatherComponent([
            'vendorCity' => 'Probolinggo',
            'pickupDate' => '2026-11-16',
            'returnDate' => '2026-11-18',
        ])
            ->assertOk()
            ->assertSet('spot', 'kota_probolinggo')
            ->assertSee('Cuaca Masa Sewa')
            ->assertSee('16 – 18 November 2026')
            ->assertSee('Kota Probolinggo')
            ->assertSee('Senin')
            ->assertSee('16 Nov')
            ->assertSee('18 Nov')
            ->assertSee('Hujan sedang')
            ->assertSee('4° – 15°C')
            ->assertSeeText('12.5 mm')
            ->assertSeeText('70%')
            ->assertSeeText('32 km/j')
            ->assertSee('Sumber: Open-Meteo');
    }

    public function test_tanggal_sewa_yang_diubah_memperbarui_prakiraan(): void
    {
        $this->fakeOpenMeteo();

        $this->weatherComponent([
            'vendorCity' => 'Malang',
            'pickupDate' => '2026-11-16',
            'returnDate' => '2026-11-18',
        ])
            ->dispatch('booking-weather-updated', pickupDate: '2026-11-20', returnDate: '2026-11-21')
            ->assertSet('pickupDate', '2026-11-20')
            ->assertSet('returnDate', '2026-11-21')
            ->assertSee('20 – 21 November 2026')
            ->assertSee('Jumat')
            ->assertDontSee('16 – 18 November 2026');

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['start_date'] ?? null) === '2026-11-20'
                && ($query['end_date'] ?? null) === '2026-11-21';
        });
    }

    public function test_spot_default_mengikuti_kota_vendor_dan_bisa_diganti(): void
    {
        $this->fakeOpenMeteo();

        $this->weatherComponent([
            'vendorCity' => 'Batu',
            'pickupDate' => '2026-11-16',
            'returnDate' => '2026-11-17',
        ])
            ->assertSet('spot', 'kota_batu')
            ->assertSee('Kota Batu')
            ->set('spot', 'ranu_kumbolo')
            ->assertSet('spot', 'ranu_kumbolo')
            ->assertSee('Ranu Kumbolo');
    }

    public function test_menampilkan_peringatan_cuaca_ekstrem(): void
    {
        $this->fakeOpenMeteo(precipitation: 60.0);

        $this->weatherComponent([
            'vendorCity' => 'Probolinggo',
            'pickupDate' => '2026-11-16',
            'returnDate' => '2026-11-18',
        ])
            ->assertSee('Peringatan Cuaca Ekstrem')
            ->assertSee('potensi cuaca ekstrem');
    }

    public function test_error_layanan_cuaca_tidak_menghalangi_booking(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response(['error' => true, 'reason' => 'service unavailable'], 503),
        ]);

        $this->weatherComponent([
            'vendorCity' => 'Probolinggo',
            'pickupDate' => '2026-11-16',
            'returnDate' => '2026-11-18',
        ])
            ->assertOk()
            ->assertSee('Prakiraan cuaca belum tersedia')
            ->assertSee('Layanan cuaca merespons error.')
            ->assertSee('Booking tetap bisa dilanjutkan.');
    }

    public function test_tanggal_di_luar_batas_prakiraan_tidak_memanggil_api(): void
    {
        Http::fake();

        $this->weatherComponent([
            'vendorCity' => 'Probolinggo',
            'pickupDate' => '2026-12-20',
            'returnDate' => '2026-12-22',
        ])
            ->assertSee('Prakiraan cuaca untuk periode ini belum tersedia.')
            ->assertSee('5 Desember 2026');

        Http::assertNothingSent();
    }

    public function test_masa_sewa_yang_melewati_batas_prakiraan_menampilkan_sebagian_data(): void
    {
        $this->fakeOpenMeteo();

        $this->weatherComponent([
            'vendorCity' => 'Probolinggo',
            'pickupDate' => '2026-11-15',
            'returnDate' => '2026-12-05',
        ])
            ->assertSee('Prakiraan baru tersedia sampai 30 November 2026')
            ->assertSee('15 November – 5 Desember 2026');

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['start_date'] ?? null) === '2026-11-15'
                && ($query['end_date'] ?? null) === '2026-11-30';
        });
    }

    public function test_kartu_cuaca_memakai_warna_tingkat_kewaspadaan(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'daily' => [
                    'time' => ['2026-11-16', '2026-11-17', '2026-11-18', '2026-11-19', '2026-11-20'],
                    'weather_code' => [0, 3, 61, 65, 95],
                    'temperature_2m_max' => [20.0, 21.0, 22.0, 23.0, 24.0],
                    'temperature_2m_min' => [10.0, 11.0, 12.0, 13.0, 14.0],
                    'precipitation_sum' => [0.0, 0.0, 2.0, 25.0, 60.0],
                    'precipitation_probability_max' => [0, 10, 60, 80, 90],
                    'wind_speed_10m_max' => [5.0, 5.0, 10.0, 20.0, 45.0],
                ],
            ], 200),
        ]);

        $this->weatherComponent([
            'vendorCity' => 'Probolinggo',
            'pickupDate' => '2026-11-16',
            'returnDate' => '2026-11-20',
        ])
            ->assertOk()
            ->assertSee('border-avocado-200 bg-avocado-50/70', false)
            ->assertSee('border-sky-200 bg-sky-50/80', false)
            ->assertSee('border-amber-300 bg-amber-50', false)
            ->assertSee('border-orange-300 bg-orange-50', false)
            ->assertSee('border-red-300 bg-red-50 ring-1 ring-red-100', false)
            ->assertSee('bg-avocado-400', false);
    }

    public function test_halaman_keranjang_menampilkan_panel_cuaca_untuk_tanggal_terpilih(): void
    {
        $vendor = Vendor::create([
            'name' => 'Probolinggo Outdoor',
            'slug' => 'probolinggo-outdoor',
            'phone' => '081234567890',
            'address' => 'Probolinggo',
            'city' => 'Probolinggo',
            'status' => 'active',
        ]);
        $category = ItemCategory::create([
            'vendor_id' => $vendor->id,
            'name' => 'Tenda',
            'slug' => 'tenda',
        ]);
        $item = MasterItem::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Tenda Dome',
            'slug' => 'tenda-dome',
            'daily_rate' => 50000,
            'is_active' => true,
        ]);
        ItemUnit::create([
            'vendor_id' => $vendor->id,
            'master_item_id' => $item->id,
            'unit_code' => 'TENDA-1',
            'status' => 'available',
        ]);

        $user = User::factory()->create(['role' => 'customer']);

        app(CartService::class)->set([
            'master_item_id' => $item->id,
            'vendor_id' => $vendor->id,
            'pickup_date' => '2026-11-16',
            'return_date' => '2026-11-18',
            'pickup_time' => '09:00',
            'return_time' => '09:00',
            'total_days' => 2,
            'quantity' => 1,
            'daily_rate' => 50000,
            'product_name' => 'Tenda Dome',
        ], $user);

        Http::fake();

        $this->actingAs($user)
            ->get(route('customer.cart.index'))
            ->assertOk()
            ->assertSee('Cuaca Masa Sewa')
            ->assertSee('Menyiapkan prakiraan cuaca untuk periode sewa');

        Http::assertNothingSent();
    }

    public function test_halaman_checkout_booking_menampilkan_panel_cuaca_untuk_member(): void
    {
        $vendor = Vendor::create([
            'name' => 'Probolinggo Outdoor',
            'slug' => 'probolinggo-outdoor-checkout',
            'phone' => '081234567890',
            'address' => 'Probolinggo',
            'city' => 'Probolinggo',
            'status' => 'active',
        ]);
        $category = ItemCategory::create([
            'vendor_id' => $vendor->id,
            'name' => 'Tenda',
            'slug' => 'tenda-checkout',
        ]);
        $item = MasterItem::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Tenda Dome',
            'slug' => 'tenda-dome-checkout',
            'daily_rate' => 50000,
            'is_active' => true,
        ]);
        ItemUnit::create([
            'vendor_id' => $vendor->id,
            'master_item_id' => $item->id,
            'unit_code' => 'TENDA-CHK-1',
            'status' => 'available',
        ]);

        $user = User::factory()->create(['role' => 'customer']);

        app(CartService::class)->set([
            'master_item_id' => $item->id,
            'vendor_id' => $vendor->id,
            'pickup_date' => '2026-11-16',
            'return_date' => '2026-11-18',
            'pickup_time' => '09:00',
            'return_time' => '09:00',
            'total_days' => 2,
            'quantity' => 1,
            'daily_rate' => 50000,
            'product_name' => 'Tenda Dome',
        ], $user);

        Http::fake();

        $this->actingAs($user)
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('Menyiapkan prakiraan cuaca untuk periode sewa');

        Http::assertNothingSent();
    }

    public function test_halaman_checkout_booking_menampilkan_banner_kunci_untuk_guest(): void
    {
        $vendor = Vendor::create([
            'name' => 'Malang Outdoor',
            'slug' => 'malang-outdoor-guest',
            'phone' => '081234567891',
            'address' => 'Malang',
            'city' => 'Malang',
            'status' => 'active',
        ]);
        $category = ItemCategory::create([
            'vendor_id' => $vendor->id,
            'name' => 'Tenda',
            'slug' => 'tenda-guest',
        ]);
        $item = MasterItem::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Tenda Dome',
            'slug' => 'tenda-dome-guest',
            'daily_rate' => 50000,
            'is_active' => true,
        ]);
        ItemUnit::create([
            'vendor_id' => $vendor->id,
            'master_item_id' => $item->id,
            'unit_code' => 'TENDA-GST-1',
            'status' => 'available',
        ]);

        app(CartService::class)->set([
            'master_item_id' => $item->id,
            'vendor_id' => $vendor->id,
            'pickup_date' => '2026-11-16',
            'return_date' => '2026-11-18',
            'pickup_time' => '09:00',
            'return_time' => '09:00',
            'total_days' => 2,
            'quantity' => 1,
            'daily_rate' => 50000,
            'product_name' => 'Tenda Dome',
        ]);

        Http::fake();

        // Checkout guest menampilkan banner kunci cuaca tanpa memuat komponen Livewire cuaca.
        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertDontSee('Menyiapkan prakiraan cuaca untuk periode sewa')
            ->assertSee('Prakiraan Cuaca Masa Sewa')
            ->assertSee('Khusus Member')
            ->assertSee('Masuk Akun');

        // Banner kunci cuaca untuk guest ada di halaman keranjang.
        $this->get(route('customer.cart.index'))
            ->assertOk()
            ->assertSee('Prakiraan Cuaca Masa Sewa')
            ->assertSee('Khusus Member')
            ->assertSee('Masuk Akun');

        Http::assertNothingSent();
    }

    /**
     * Render komponen cuaca langsung tanpa placeholder lazy loading.
     *
     * @param  array<string, mixed>  $params
     */
    protected function weatherComponent(array $params): Testable
    {
        Livewire::withoutLazyLoading();

        return Livewire::test(BookingWeather::class, $params);
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
     * Payload contoh respons Open-Meteo untuk sejumlah hari.
     *
     * @return array<string, mixed>
     */
    protected function payload(
        int $days = 7,
        string $startDate = '2026-11-15',
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
