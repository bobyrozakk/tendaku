<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReverseGeocodingTest extends TestCase
{
    private const API_URL = 'https://us1.locationiq.com/v1/reverse*';

    private const API_KEY = 'synthetic-locationiq-test-key';

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertEmpty(config('database.connections.sqlite.url'));
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('array', config('session.driver'));

        config([
            'database.connections' => ['sqlite' => config('database.connections.sqlite')],
            'services.locationiq.api_key' => self::API_KEY,
        ]);

        DB::connection()->beforeExecuting(static function (): never {
            throw new LogicException('Database queries are forbidden in reverse geocoding tests.');
        });

        Http::preventStrayRequests();
    }

    #[DataProvider('allowedRoles')]
    public function test_returns_normalized_address_for_customer_and_owner(string $role): void
    {
        $user = User::factory()->make(['id' => 1, 'role' => $role]);
        Http::fake([
            self::API_URL => Http::response([
                'display_name' => 'Jalan Ijen 12, Oro-oro Dowo, Klojen, Kota Malang, Jawa Timur, 65119',
                'address' => [
                    'road' => 'Jalan Ijen', 'house_number' => '12',
                    'state' => 'Jawa Timur', 'city' => 'Kota Malang',
                    'city_district' => 'Klojen', 'village' => 'Oro-oro Dowo', 'postcode' => '65119',
                ],
                'licence' => 'provider metadata must not be returned',
            ]),
        ]);

        $response = $this->actingAs($user)->getJson(route('locations.reverse-geocode', [
            'latitude' => -7.9797, 'longitude' => 112.6304, 'key' => 'ignored-client-key',
        ]));

        $response->assertOk()->assertExactJson([
            'success' => true,
            'data' => [
                'full_address' => 'Jalan Ijen 12, Oro-oro Dowo, Klojen, Kota Malang, Jawa Timur, 65119',
                'province' => 'Jawa Timur', 'city' => 'Kota Malang',
                'district' => 'Klojen', 'village' => 'Oro-oro Dowo', 'postal_code' => '65119',
            ],
        ]);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://us1.locationiq.com/v1/reverse?')
            && $request['key'] === self::API_KEY
            && (float) $request['lat'] === -7.9797 && (float) $request['lon'] === 112.6304
            && $request['format'] === 'json' && (int) $request['addressdetails'] === 1
            && $request['accept-language'] === 'id');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function allowedRoles(): array
    {
        return ['customer' => ['customer'], 'owner' => ['vendor_owner']];
    }

    public function test_partial_address_does_not_invent_regions_or_house_number(): void
    {
        $user = User::factory()->make(['id' => 1]);
        Http::fake([
            self::API_URL => Http::response([
                'address' => ['road' => ' Jalan Ijen ', 'county' => 'Kabupaten Malang', 'neighbourhood' => 'Lingkungan A'],
            ]),
        ]);

        $this->actingAs($user)->getJson(route('locations.reverse-geocode', ['latitude' => 0, 'longitude' => 0]))
            ->assertOk()->assertExactJson([
                'success' => true,
                'data' => [
                    'full_address' => 'Jalan Ijen', 'province' => null, 'city' => 'Kabupaten Malang',
                    'district' => null, 'village' => null, 'postal_code' => null,
                ],
            ]);
        Http::assertSent(fn (Request $request): bool => (float) $request['lat'] === 0.0 && (float) $request['lon'] === 0.0);
    }

    public function test_street_address_uses_house_number_only_when_provided(): void
    {
        $user = User::factory()->make(['id' => 1]);
        Http::fake([
            self::API_URL => Http::response(['address' => ['road' => 'Jalan Ijen', 'house_number' => '12A']]),
        ]);

        $this->actingAs($user)->getJson(route('locations.reverse-geocode', ['latitude' => -7.9, 'longitude' => 112.6]))
            ->assertOk()->assertJsonPath('data.full_address', 'Jalan Ijen 12A');
        Http::assertSentCount(1);
    }

    public function test_ambiguous_subdivisions_and_invalid_fields_are_left_null(): void
    {
        $user = User::factory()->make(['id' => 1]);
        Http::fake([
            self::API_URL => Http::response([
                'address' => [
                    'state' => false, 'city' => ['unexpected'], 'county' => ' ', 'municipality' => 'Kota Batu',
                    'district' => 'Bumiaji', 'suburb' => 'Ambiguous subdivision', 'state_district' => 'Ambiguous region',
                    'postcode' => ['unexpected'],
                ],
            ]),
        ]);

        $this->actingAs($user)->getJson(route('locations.reverse-geocode', ['latitude' => -7.9, 'longitude' => 112.6]))
            ->assertOk()->assertExactJson([
                'success' => true,
                'data' => [
                    'full_address' => null, 'province' => null, 'city' => 'Kota Batu',
                    'district' => 'Bumiaji', 'village' => null, 'postal_code' => null,
                ],
            ]);
        Http::assertSentCount(1);
    }

    #[DataProvider('invalidCoordinates')]
    public function test_returns_422_for_invalid_coordinates_without_calling_provider(array $coordinates, array $errors): void
    {
        $user = User::factory()->make(['id' => 1]);
        Http::fake([self::API_URL => Http::response([])]);

        $this->actingAs($user)->getJson(route('locations.reverse-geocode', $coordinates))
            ->assertUnprocessable()->assertJsonValidationErrors($errors);
        Http::assertNothingSent();
    }

    /**
     * @return array<string, array{array<string, mixed>, array<string, string>}>
     */
    public static function invalidCoordinates(): array
    {
        return [
            'missing coordinates' => [[], ['latitude' => 'Latitude wajib diisi.', 'longitude' => 'Longitude wajib diisi.']],
            'non numeric latitude' => [['latitude' => 'abc', 'longitude' => 112], ['latitude' => 'Latitude harus berupa angka.']],
            'non numeric longitude' => [['latitude' => -7, 'longitude' => 'abc'], ['longitude' => 'Longitude harus berupa angka.']],
            'latitude too low' => [['latitude' => -90.1, 'longitude' => 112], ['latitude' => 'Latitude harus berada antara -90 dan 90.']],
            'latitude too high' => [['latitude' => 90.1, 'longitude' => 112], ['latitude' => 'Latitude harus berada antara -90 dan 90.']],
            'longitude too low' => [['latitude' => -7, 'longitude' => -180.1], ['longitude' => 'Longitude harus berada antara -180 dan 180.']],
            'longitude too high' => [['latitude' => -7, 'longitude' => 180.1], ['longitude' => 'Longitude harus berada antara -180 dan 180.']],
        ];
    }

    #[DataProvider('providerFailures')]
    public function test_returns_safe_503_for_provider_errors(int $status, mixed $body): void
    {
        $user = User::factory()->make(['id' => 1]);
        Http::fake([self::API_URL => Http::response($body, $status)]);
        Log::spy();

        $this->actingAs($user)->getJson(route('locations.reverse-geocode', ['latitude' => -7.9, 'longitude' => 112.6]))
            ->assertServiceUnavailable()->assertExactJson([
                'success' => false,
                'message' => 'Alamat belum dapat ditemukan. Silakan isi alamat secara manual.',
                'data' => null,
            ]);
        Http::assertSentCount(1);
        Log::shouldNotHaveReceived('error');
        Log::shouldNotHaveReceived('warning');
    }

    /**
     * @return array<string, array{int, array<string, string>|string}>
     */
    public static function providerFailures(): array
    {
        return [
            'invalid key' => [401, ['error' => 'Rejected '.self::API_KEY]],
            'not found' => [404, ['error' => 'Unable to geocode']],
            'quota exhausted' => [429, ['error' => 'Quota exceeded '.self::API_KEY]],
            'server failure' => [500, ['error' => 'Provider internals '.self::API_KEY]],
            'redirect' => [302, 'Redirect to another host'],
            'invalid json' => [200, '<html>Provider failure</html>'],
            'empty payload' => [200, []],
            'unexpected shape' => [200, ['display_name' => ['unexpected'], 'address' => 'unexpected']],
            'error in successful response' => [200, ['error' => 'Failure '.self::API_KEY]],
        ];
    }

    public function test_returns_safe_503_for_timeout_without_logging_exception_credentials(): void
    {
        $user = User::factory()->make(['id' => 1]);
        Http::fake([self::API_URL => Http::failedConnection('Timeout for key='.self::API_KEY)]);
        Log::spy();

        $this->actingAs($user)->getJson(route('locations.reverse-geocode', ['latitude' => -7.9, 'longitude' => 112.6]))
            ->assertServiceUnavailable()->assertExactJson([
                'success' => false,
                'message' => 'Alamat belum dapat ditemukan. Silakan isi alamat secara manual.',
                'data' => null,
            ]);
        Http::assertSentCount(1);
        Log::shouldNotHaveReceived('error');
        Log::shouldNotHaveReceived('warning');
    }

    public function test_returns_safe_503_for_request_exception_without_logging_credentials(): void
    {
        $user = User::factory()->make(['id' => 1]);
        Http::fake([
            self::API_URL => function (Request $request): never {
                $this->assertSame(self::API_KEY, $request['key']);

                throw Http::failedRequest('Transport failure with key='.self::API_KEY, 502);
            },
        ]);
        Log::spy();

        $this->actingAs($user)->getJson(route('locations.reverse-geocode', ['latitude' => -7.9, 'longitude' => 112.6]))
            ->assertServiceUnavailable()->assertExactJson([
                'success' => false,
                'message' => 'Alamat belum dapat ditemukan. Silakan isi alamat secara manual.',
                'data' => null,
            ]);
        Log::shouldNotHaveReceived('error');
        Log::shouldNotHaveReceived('warning');
    }

    #[DataProvider('missingApiKeys')]
    public function test_returns_503_without_calling_provider_when_key_is_missing(?string $key): void
    {
        $user = User::factory()->make(['id' => 1]);
        config(['services.locationiq.api_key' => $key]);
        Http::fake([self::API_URL => Http::response([])]);

        $this->actingAs($user)->getJson(route('locations.reverse-geocode', ['latitude' => -7.9, 'longitude' => 112.6]))
            ->assertServiceUnavailable()->assertJsonPath('success', false)->assertJsonPath('data', null);
        Http::assertNothingSent();
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function missingApiKeys(): array
    {
        return ['unset' => [null], 'empty' => [''], 'whitespace' => ['   ']];
    }

    public function test_returns_401_for_guest_without_calling_provider(): void
    {
        Http::fake([self::API_URL => Http::response([])]);

        $this->getJson(route('locations.reverse-geocode', ['latitude' => -7.9, 'longitude' => 112.6]))
            ->assertUnauthorized();
        Http::assertNothingSent();
    }

    #[DataProvider('forbiddenRoles')]
    public function test_returns_403_for_other_roles_without_calling_provider(string $role): void
    {
        $user = User::factory()->make(['id' => 1, 'role' => $role]);
        Http::fake([self::API_URL => Http::response([])]);

        $this->actingAs($user)->getJson(route('locations.reverse-geocode', ['latitude' => -7.9, 'longitude' => 112.6]))
            ->assertForbidden();
        Http::assertNothingSent();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function forbiddenRoles(): array
    {
        return ['vendor admin' => ['vendor_admin'], 'super admin' => ['super_admin']];
    }

    public function test_returns_403_for_unverified_user_without_calling_provider(): void
    {
        $user = User::factory()->unverified()->make(['id' => 1]);
        Http::fake([self::API_URL => Http::response([])]);

        $this->actingAs($user)->getJson(route('locations.reverse-geocode', ['latitude' => -7.9, 'longitude' => 112.6]))
            ->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_returns_429_after_thirty_requests_per_account_per_minute(): void
    {
        $this->freezeTime();
        $user = User::factory()->make(['id' => 1]);
        Http::fake([self::API_URL => Http::response(['display_name' => 'Jalan Ijen'])]);
        $url = route('locations.reverse-geocode', ['latitude' => -7.9, 'longitude' => 112.6]);

        $this->actingAs($user);
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->getJson($url)->assertOk();
        }

        $this->getJson($url)->assertTooManyRequests()->assertHeader('Retry-After');
        Http::assertSentCount(30);

        $otherUser = User::factory()->make(['id' => 2]);
        $this->actingAs($otherUser)->getJson($url)->assertOk();
        Http::assertSentCount(31);

        $this->travel(61)->seconds();
        $this->actingAs($user)->getJson($url)->assertOk();
        Http::assertSentCount(32);
    }
}
