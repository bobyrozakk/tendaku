<?php

namespace Tests\Unit;

use App\Models\Vendor;
use App\Models\VendorShippingSetting;
use App\Services\OpenRouteService;
use App\Services\ShippingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OpenRouteServiceTest extends TestCase
{
    use RefreshDatabase;

    private OpenRouteService $service;

    public function test_http_error_does_not_log_or_return_raw_response_credentials(): void
    {
        $credential = 'synthetic-ors-credential-for-test';
        config(['services.openrouteservice.api_key' => $credential]);
        Http::preventStrayRequests();
        Http::fake([
            'https://api.openrouteservice.org/v2/directions/driving-car' => Http::response(['error' => 'Rejected '.$credential], 403),
        ]);
        Log::shouldReceive('warning')->once()->withArgs(fn (string $message, array $context): bool => ! array_key_exists('body', $context) && ! str_contains(json_encode([$message, $context]), $credential));

        $result = $this->service->getDrivingDistance(-7.9, 112.6, -7.8, 112.7);

        $this->assertFalse($result['success']);
        $this->assertNull($result['distance_km']);
        $this->assertStringNotContainsString($credential, json_encode($result));
        Http::assertSentCount(1);
    }

    public function test_connection_error_does_not_log_or_return_exception_credentials(): void
    {
        $credential = 'synthetic-ors-credential-for-test';
        config(['services.openrouteservice.api_key' => $credential]);
        Http::preventStrayRequests();
        Http::fake([
            'https://api.openrouteservice.org/v2/directions/driving-car' => Http::failedConnection('Connection failed: '.$credential),
        ]);
        Log::shouldReceive('warning')->once()->withArgs(fn (string $message, array $context): bool => ($context['exception'] ?? null) === ConnectionException::class
            && ! str_contains(json_encode([$message, $context]), $credential));

        $result = $this->service->getDrivingDistance(-7.9, 112.6, -7.8, 112.7);

        $this->assertFalse($result['success']);
        $this->assertNull($result['distance_km']);
        $this->assertStringNotContainsString($credential, json_encode($result));
        Http::assertSentCount(1);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'services.openrouteservice.api_key' => 'test-ors-api-key-12345',
            'services.openrouteservice.key' => 'test-ors-api-key-12345',
            'services.openrouteservice.base_url' => 'https://api.openrouteservice.org',
            'services.openrouteservice.profile' => 'driving-car',
            'services.openrouteservice.timeout' => 5,
            'services.openrouteservice.retry_times' => 0,
            'services.openrouteservice.retry_sleep_ms' => 10,
            'services.openrouteservice.cache_ttl_minutes' => 60,
        ]);

        $this->service = new OpenRouteService;
    }

    public function test_builds_api_coordinates_in_longitude_latitude_order(): void
    {
        $vendorLat = -7.96662;
        $vendorLng = 112.63263;
        $customerLat = -7.87121;
        $customerLng = 112.52691;

        $coordinates = $this->service->buildApiCoordinates(
            $vendorLat,
            $vendorLng,
            $customerLat,
            $customerLng
        );

        $this->assertSame([
            [112.63263, -7.96662],
            [112.52691, -7.87121],
        ], $coordinates);
    }

    public function test_converts_meters_to_kilometers_accurately(): void
    {
        $this->assertSame(6.54, $this->service->convertMetersToKilometers(6540));
        $this->assertSame(12.35, $this->service->convertMetersToKilometers(12345.6));
        $this->assertSame(0.0, $this->service->convertMetersToKilometers(0));
    }

    public function test_rejects_negative_meters_conversion(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->convertMetersToKilometers(-100);
    }

    public function test_fetches_driving_distance_and_converts_meters_to_kilometers(): void
    {
        Http::fake([
            'https://api.openrouteservice.org/v2/directions/driving-car' => Http::response([
                'routes' => [
                    [
                        'summary' => [
                            'distance' => 6540.0,
                            'duration' => 780.5,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getDrivingDistance(
            vendorLatitude: -7.96662,
            vendorLongitude: 112.63263,
            customerLatitude: -7.87121,
            customerLongitude: 112.52691
        );

        $this->assertTrue($result['success']);
        $this->assertSame(OpenRouteService::STATUS_OK, $result['status']);
        $this->assertSame('driving-car', $result['profile']);
        $this->assertSame(6540.0, $result['distance_meters']);
        $this->assertSame(6.54, $result['distance_km']);
        $this->assertSame(780.5, $result['duration_seconds']);
        $this->assertFalse($result['from_cache']);
        $this->assertNull($result['error_message']);
        $this->assertSame([
            'vendor' => [112.63263, -7.96662],
            'customer' => [112.52691, -7.87121],
        ], $result['coordinates']);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.openrouteservice.org/v2/directions/driving-car'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'test-ors-api-key-12345')
                && $request['coordinates'] === [
                    [112.63263, -7.96662],
                    [112.52691, -7.87121],
                ];
        });
    }

    public function test_extracts_distance_from_geojson_response_format(): void
    {
        Http::fake([
            'https://api.openrouteservice.org/v2/directions/driving-car' => Http::response([
                'type' => 'FeatureCollection',
                'features' => [
                    [
                        'type' => 'Feature',
                        'properties' => [
                            'summary' => [
                                'distance' => 12345.6,
                                'duration' => 1420.0,
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $distanceKm = $this->service->getDistanceInKilometers(
            vendorLatitude: -7.96662,
            vendorLongitude: 112.63263,
            customerLatitude: -7.87121,
            customerLongitude: 112.52691
        );

        $this->assertSame(12.35, $distanceKm);
    }

    public function test_supports_coordinate_arrays_in_multiple_formats(): void
    {
        Http::fake([
            'https://api.openrouteservice.org/v2/directions/driving-car' => Http::response([
                'routes' => [
                    [
                        'summary' => [
                            'distance' => 4500.0,
                            'duration' => 540.0,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $resultAssoc = $this->service->getDistanceFromCoordinates(
            ['latitude' => -7.96662, 'longitude' => 112.63263],
            ['lat' => -7.87121, 'lng' => 112.52691]
        );

        $this->assertTrue($resultAssoc['success']);
        $this->assertSame(4.5, $resultAssoc['distance_km']);

        $resultTuple = $this->service->getDistanceFromCoordinates(
            [112.63263, -7.96662],
            [112.52691, -7.87121]
        );

        $this->assertTrue($resultTuple['success']);
        $this->assertSame(4.5, $resultTuple['distance_km']);
    }

    public function test_caches_successful_distance_lookup_to_avoid_repeated_api_calls(): void
    {
        Http::fake([
            'https://api.openrouteservice.org/v2/directions/driving-car' => Http::response([
                'routes' => [
                    [
                        'summary' => [
                            'distance' => 8200.0,
                            'duration' => 910.0,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $firstCall = $this->service->getDrivingDistance(-7.96662, 112.63263, -7.87121, 112.52691);
        $secondCall = $this->service->getDrivingDistance(-7.96662, 112.63263, -7.87121, 112.52691);

        $this->assertFalse($firstCall['from_cache']);
        $this->assertTrue($secondCall['from_cache']);
        $this->assertSame(8.2, $secondCall['distance_km']);

        Http::assertSentCount(1);

        $this->service->forgetCache(-7.96662, 112.63263, -7.87121, 112.52691);

        $thirdCall = $this->service->getDrivingDistance(-7.96662, 112.63263, -7.87121, 112.52691);
        $this->assertFalse($thirdCall['from_cache']);
        Http::assertSentCount(2);
    }

    /**
     * @return array<string, array{0: float, 1: float, 2: float, 3: float}>
     */
    public static function invalidCoordinatesProvider(): array
    {
        return [
            'vendor latitude lebih dari 90' => [95.0, 112.63263, -7.87121, 112.52691],
            'vendor latitude kurang dari -90' => [-91.5, 112.63263, -7.87121, 112.52691],
            'vendor longitude lebih dari 180' => [-7.96662, 181.0, -7.87121, 112.52691],
            'customer latitude lebih dari 90' => [-7.96662, 112.63263, 100.0, 112.52691],
            'customer longitude kurang dari -180' => [-7.96662, 112.63263, -7.87121, -185.0],
        ];
    }

    #[DataProvider('invalidCoordinatesProvider')]
    public function test_rejects_invalid_coordinates_without_calling_api_or_returning_fake_distance(
        float $vendorLat,
        float $vendorLng,
        float $customerLat,
        float $customerLng
    ): void {
        Http::fake();

        $result = $this->service->getDrivingDistance($vendorLat, $vendorLng, $customerLat, $customerLng);

        $this->assertFalse($result['success']);
        $this->assertSame(OpenRouteService::STATUS_INVALID_COORDINATES, $result['status']);
        $this->assertNull($result['distance_meters']);
        $this->assertNull($result['distance_km']);
        $this->assertNotEmpty($result['error_message']);

        Http::assertNothingSent();
    }

    public function test_returns_unconfigured_api_key_when_key_is_missing(): void
    {
        Http::fake();

        config([
            'services.openrouteservice.api_key' => '',
            'services.openrouteservice.key' => '',
        ]);

        $result = $this->service->getDrivingDistance(-7.96662, 112.63263, -7.87121, 112.52691);

        $this->assertFalse($result['success']);
        $this->assertSame(OpenRouteService::STATUS_UNCONFIGURED_API_KEY, $result['status']);
        $this->assertNull($result['distance_meters']);
        $this->assertNull($result['distance_km']);
        $this->assertNull($this->service->getDistanceInKilometers(-7.96662, 112.63263, -7.87121, 112.52691));

        Http::assertNothingSent();
    }

    public function test_handles_route_not_found_error_from_openrouteservice_without_fake_distance(): void
    {
        Http::fake([
            'https://api.openrouteservice.org/v2/directions/driving-car' => Http::response([
                'error' => [
                    'code' => 2009,
                    'message' => 'Route could not be found - Unable to find a route between points.',
                ],
            ], 404),
        ]);

        $result = $this->service->getDrivingDistance(-7.96662, 112.63263, -8.40951, 115.18891);

        $this->assertFalse($result['success']);
        $this->assertSame(OpenRouteService::STATUS_ROUTE_NOT_FOUND, $result['status']);
        $this->assertNull($result['distance_meters']);
        $this->assertNull($result['distance_km']);
        $this->assertNull($this->service->getDistanceInKilometers(-7.96662, 112.63263, -8.40951, 115.18891));
    }

    public function test_handles_empty_routes_payload_as_route_not_found(): void
    {
        Http::fake([
            'https://api.openrouteservice.org/v2/directions/driving-car' => Http::response([
                'routes' => [],
            ], 200),
        ]);

        $result = $this->service->getDrivingDistance(-7.96662, 112.63263, -7.87121, 112.52691);

        $this->assertFalse($result['success']);
        $this->assertSame(OpenRouteService::STATUS_ROUTE_NOT_FOUND, $result['status']);
        $this->assertNull($result['distance_meters']);
        $this->assertNull($result['distance_km']);
    }

    public function test_handles_http_server_error_and_does_not_cache_failed_response(): void
    {
        Http::fakeSequence('https://api.openrouteservice.org/v2/directions/driving-car')
            ->push(['error' => 'Internal Server Error'], 500)
            ->push([
                'routes' => [
                    [
                        'summary' => [
                            'distance' => 5000.0,
                            'duration' => 600.0,
                        ],
                    ],
                ],
            ], 200);

        $failedResult = $this->service->getDrivingDistance(-7.96662, 112.63263, -7.87121, 112.52691);

        $this->assertFalse($failedResult['success']);
        $this->assertSame(OpenRouteService::STATUS_API_ERROR, $failedResult['status']);
        $this->assertNull($failedResult['distance_meters']);
        $this->assertNull($failedResult['distance_km']);

        // Karena respons gagal tidak di-cache, pemanggilan berikutnya akan memanggil API kembali dan berhasil
        $recoveredResult = $this->service->getDrivingDistance(-7.96662, 112.63263, -7.87121, 112.52691);

        $this->assertTrue($recoveredResult['success']);
        $this->assertSame(5.0, $recoveredResult['distance_km']);
    }

    public function test_handles_network_connection_exception_gracefully(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $result = $this->service->getDrivingDistance(-7.96662, 112.63263, -7.87121, 112.52691);

        $this->assertFalse($result['success']);
        $this->assertSame(OpenRouteService::STATUS_API_ERROR, $result['status']);
        $this->assertNull($result['distance_meters']);
        $this->assertNull($result['distance_km']);
    }

    public function test_integrates_distance_from_openrouteservice_with_shipping_calculator(): void
    {
        Http::fake([
            'https://api.openrouteservice.org/v2/directions/driving-car' => Http::response([
                'routes' => [
                    [
                        'summary' => [
                            'distance' => 6500.0, // 6.5 km
                            'duration' => 750.0,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $vendor = Vendor::create([
            'name' => 'Tendaku Malang Basecamp',
            'slug' => 'tendaku-malang-basecamp',
            'phone' => '081234567890',
            'address' => 'Jl. Soekarno Hatta No. 9, Malang',
            'status' => 'active',
        ]);

        VendorShippingSetting::withoutGlobalScope('vendor')->create([
            'vendor_id' => $vendor->id,
            'rate_per_km' => 2000,
            'weight_threshold_kg' => 10,
            'extra_weight_fee_per_kg' => 1000,
        ]);

        $distanceKm = $this->service->getDistanceInKilometers(
            vendorLatitude: -7.94671,
            vendorLongitude: 112.61562,
            customerLatitude: -7.88214,
            customerLongitude: 112.53341
        );

        $this->assertSame(6.5, $distanceKm);

        $calculator = new ShippingCalculator;
        $shipping = $calculator->calculateForVendor(
            vendor: $vendor,
            distanceKm: $distanceKm,
            itemsOrTotalWeightKg: [
                ['weight_kg' => 4.2, 'qty' => 3], // 12.6 kg -> kelebihan 2.6 kg -> ceil 3 kg
            ]
        );

        // Biaya jarak: 6.5 km * Rp2.000 = Rp13.000
        // Biaya berat: 3 kg * Rp1.000 = Rp3.000
        // Total ongkir: Rp16.000
        $this->assertTrue($shipping['is_configured']);
        $this->assertSame(13000, $shipping['distance_fee']);
        $this->assertSame(3000, $shipping['weight_fee']);
        $this->assertSame(16000, $shipping['total_fee']);
    }
}
