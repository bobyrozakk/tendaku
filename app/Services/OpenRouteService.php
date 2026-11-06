<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class OpenRouteService
{
    public const DEFAULT_PROFILE = 'driving-car';

    public const STATUS_OK = 'ok';

    public const STATUS_INVALID_COORDINATES = 'invalid_coordinates';

    public const STATUS_UNCONFIGURED_API_KEY = 'unconfigured_api_key';

    public const STATUS_ROUTE_NOT_FOUND = 'route_not_found';

    public const STATUS_API_ERROR = 'api_error';

    /**
     * Hitung jarak perjalanan darat (driving-car) dari koordinat toko Vendor ke lokasi Customer.
     *
     * Koordinat ke OpenRouteService dikirim dalam urutan [longitude, latitude].
     * Jika API gagal atau rute tidak ditemukan, distance_meters dan distance_km bernilai null
     * (tidak pernah menghasilkan jarak palsu).
     *
     * @return array{
     *     success: bool,
     *     status: 'ok'|'invalid_coordinates'|'unconfigured_api_key'|'route_not_found'|'api_error',
     *     profile: string,
     *     distance_meters: float|null,
     *     distance_km: float|null,
     *     duration_seconds: float|null,
     *     coordinates: array{vendor: array{0: float, 1: float}, customer: array{0: float, 1: float}}|null,
     *     from_cache: bool,
     *     error_message: string|null
     * }
     */
    public function getDrivingDistance(
        float $vendorLatitude,
        float $vendorLongitude,
        float $customerLatitude,
        float $customerLongitude
    ): array {
        $profile = $this->getProfile();

        if (
            ! $this->isValidCoordinatePair($vendorLatitude, $vendorLongitude)
            || ! $this->isValidCoordinatePair($customerLatitude, $customerLongitude)
        ) {
            return $this->failureResult(
                status: self::STATUS_INVALID_COORDINATES,
                profile: $profile,
                coordinates: null,
                message: 'Koordinat lokasi vendor atau pelanggan tidak valid.'
            );
        }

        $apiCoordinates = $this->buildApiCoordinates(
            $vendorLatitude,
            $vendorLongitude,
            $customerLatitude,
            $customerLongitude
        );

        $coordinatesMeta = [
            'vendor' => $apiCoordinates[0],
            'customer' => $apiCoordinates[1],
        ];

        $apiKey = $this->getApiKey();
        if ($apiKey === null) {
            Log::warning('OpenRouteService: API key belum dikonfigurasi.');

            return $this->failureResult(
                status: self::STATUS_UNCONFIGURED_API_KEY,
                profile: $profile,
                coordinates: $coordinatesMeta,
                message: 'Layanan perhitungan jarak belum dikonfigurasi.'
            );
        }

        $cacheKey = $this->cacheKey(
            $profile,
            $vendorLatitude,
            $vendorLongitude,
            $customerLatitude,
            $customerLongitude
        );

        /** @var array<string, mixed>|null $cached */
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ($cached['success'] ?? false) === true && isset($cached['distance_km'])) {
            $cached['from_cache'] = true;

            /** @phpstan-ignore-next-line */
            return $cached;
        }

        $result = $this->requestDirections($apiKey, $profile, $apiCoordinates, $coordinatesMeta);

        if ($result['success'] === true) {
            $ttlMinutes = max(1, (int) config('services.openrouteservice.cache_ttl_minutes', 60));
            Cache::put($cacheKey, $result, now()->addMinutes($ttlMinutes));
        }

        return $result;
    }

    /**
     * Ambil jarak perjalanan dalam satuan kilometer (float), atau null bila gagal/rute tidak ditemukan.
     */
    public function getDistanceInKilometers(
        float $vendorLatitude,
        float $vendorLongitude,
        float $customerLatitude,
        float $customerLongitude
    ): ?float {
        $result = $this->getDrivingDistance(
            $vendorLatitude,
            $vendorLongitude,
            $customerLatitude,
            $customerLongitude
        );

        return $result['success'] ? $result['distance_km'] : null;
    }

    /**
     * Hitung jarak dari struktur array koordinat Vendor dan Customer.
     *
     * Mendukung format:
     * - ['latitude' => float, 'longitude' => float]
     * - ['lat' => float, 'lng' => float] atau ['lat' => float, 'lon' => float]
     * - [longitude, latitude] (urutan standar GeoJSON/OpenRouteService)
     *
     * @param  array<int|string, mixed>  $vendorCoordinates
     * @param  array<int|string, mixed>  $customerCoordinates
     * @return array{
     *     success: bool,
     *     status: 'ok'|'invalid_coordinates'|'unconfigured_api_key'|'route_not_found'|'api_error',
     *     profile: string,
     *     distance_meters: float|null,
     *     distance_km: float|null,
     *     duration_seconds: float|null,
     *     coordinates: array{vendor: array{0: float, 1: float}, customer: array{0: float, 1: float}}|null,
     *     from_cache: bool,
     *     error_message: string|null
     * }
     */
    public function getDistanceFromCoordinates(array $vendorCoordinates, array $customerCoordinates): array
    {
        $vendorPair = $this->parseCoordinateArray($vendorCoordinates);
        $customerPair = $this->parseCoordinateArray($customerCoordinates);

        if ($vendorPair === null || $customerPair === null) {
            return $this->failureResult(
                status: self::STATUS_INVALID_COORDINATES,
                profile: $this->getProfile(),
                coordinates: null,
                message: 'Koordinat lokasi vendor atau pelanggan tidak lengkap atau tidak valid.'
            );
        }

        return $this->getDrivingDistance(
            $vendorPair['latitude'],
            $vendorPair['longitude'],
            $customerPair['latitude'],
            $customerPair['longitude']
        );
    }

    /**
     * Bentuk pasangan koordinat untuk payload OpenRouteService Directions API
     * dalam urutan wajib [longitude, latitude].
     *
     * @return array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}
     */
    public function buildApiCoordinates(
        float $vendorLatitude,
        float $vendorLongitude,
        float $customerLatitude,
        float $customerLongitude
    ): array {
        return [
            [$vendorLongitude, $vendorLatitude],
            [$customerLongitude, $customerLatitude],
        ];
    }

    /**
     * Konversi jarak dari meter ke kilometer dengan pembulatan desimal yang konsisten.
     */
    public function convertMetersToKilometers(float $meters, int $precision = 2): float
    {
        if ($meters < 0) {
            throw new InvalidArgumentException('Jarak dalam meter tidak boleh bernilai negatif.');
        }

        return round($meters / 1000, $precision);
    }

    /**
     * Validasi apakah pasangan latitude dan longitude berada pada rentang geografis yang sah.
     */
    public function isValidCoordinatePair(float $latitude, float $longitude): bool
    {
        if (! is_finite($latitude) || ! is_finite($longitude)) {
            return false;
        }

        return $latitude >= -90.0
            && $latitude <= 90.0
            && $longitude >= -180.0
            && $longitude <= 180.0;
    }

    /**
     * Hapus cache jarak untuk pasangan koordinat Vendor dan Customer tertentu.
     */
    public function forgetCache(
        float $vendorLatitude,
        float $vendorLongitude,
        float $customerLatitude,
        float $customerLongitude
    ): void {
        Cache::forget($this->cacheKey(
            $this->getProfile(),
            $vendorLatitude,
            $vendorLongitude,
            $customerLatitude,
            $customerLongitude
        ));
    }

    /**
     * Kirim permintaan HTTP POST ke OpenRouteService Directions API.
     *
     * @param  array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}  $apiCoordinates
     * @param  array{vendor: array{0: float, 1: float}, customer: array{0: float, 1: float}}  $coordinatesMeta
     * @return array{
     *     success: bool,
     *     status: 'ok'|'invalid_coordinates'|'unconfigured_api_key'|'route_not_found'|'api_error',
     *     profile: string,
     *     distance_meters: float|null,
     *     distance_km: float|null,
     *     duration_seconds: float|null,
     *     coordinates: array{vendor: array{0: float, 1: float}, customer: array{0: float, 1: float}}|null,
     *     from_cache: bool,
     *     error_message: string|null
     * }
     */
    protected function requestDirections(
        string $apiKey,
        string $profile,
        array $apiCoordinates,
        array $coordinatesMeta
    ): array {
        $endpoint = $this->buildDirectionsUrl($profile);
        $timeout = max(1, (int) config('services.openrouteservice.timeout', 10));
        $retryTimes = max(0, (int) config('services.openrouteservice.retry_times', 2));
        $retrySleepMs = max(0, (int) config('services.openrouteservice.retry_sleep_ms', 300));

        try {
            $response = Http::withHeaders([
                'Authorization' => $apiKey,
                'Accept' => 'application/json, application/geo+json',
            ])
                ->timeout($timeout)
                ->retry(
                    $retryTimes,
                    $retrySleepMs,
                    throw: false,
                )
                ->post($endpoint, [
                    'coordinates' => $apiCoordinates,
                ]);
        } catch (Throwable $exception) {
            Log::warning('OpenRouteService: gagal menghubungi Directions API.', [
                'profile' => $profile,
                'coordinates' => $apiCoordinates,
                'exception' => $exception->getMessage(),
            ]);

            return $this->failureResult(
                status: self::STATUS_API_ERROR,
                profile: $profile,
                coordinates: $coordinatesMeta,
                message: 'Gagal menghubungi layanan perhitungan jarak pengiriman.'
            );
        }

        $payload = $response->json();

        if (! $response->successful()) {
            if ($this->isRouteNotFoundResponse($response->status(), $payload)) {
                Log::info('OpenRouteService: rute pengiriman tidak ditemukan.', [
                    'status' => $response->status(),
                    'coordinates' => $apiCoordinates,
                ]);

                return $this->failureResult(
                    status: self::STATUS_ROUTE_NOT_FOUND,
                    profile: $profile,
                    coordinates: $coordinatesMeta,
                    message: 'Rute perjalanan menuju alamat tujuan tidak ditemukan.'
                );
            }

            Log::warning('OpenRouteService: Directions API merespons HTTP error.', [
                'status' => $response->status(),
                'coordinates' => $apiCoordinates,
                'body' => $response->body(),
            ]);

            return $this->failureResult(
                status: self::STATUS_API_ERROR,
                profile: $profile,
                coordinates: $coordinatesMeta,
                message: 'Layanan perhitungan jarak merespons error.'
            );
        }

        $extracted = $this->extractDistanceAndDuration($payload, $apiCoordinates);

        if ($extracted === null) {
            Log::warning('OpenRouteService: rute atau jarak tidak ditemukan pada respons API.', [
                'coordinates' => $apiCoordinates,
            ]);

            return $this->failureResult(
                status: self::STATUS_ROUTE_NOT_FOUND,
                profile: $profile,
                coordinates: $coordinatesMeta,
                message: 'Rute perjalanan menuju alamat tujuan tidak ditemukan.'
            );
        }

        $distanceMeters = round($extracted['distance_meters'], 2);
        $distanceKm = $this->convertMetersToKilometers($distanceMeters);

        return [
            'success' => true,
            'status' => self::STATUS_OK,
            'profile' => $profile,
            'distance_meters' => $distanceMeters,
            'distance_km' => $distanceKm,
            'duration_seconds' => $extracted['duration_seconds'],
            'coordinates' => $coordinatesMeta,
            'from_cache' => false,
            'error_message' => null,
        ];
    }

    /**
     * Ekstrak nilai distance (meter) dan duration (detik) dari respons JSON maupun GeoJSON OpenRouteService.
     *
     * @param  array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}  $apiCoordinates
     * @return array{distance_meters: float, duration_seconds: float|null}|null
     */
    protected function extractDistanceAndDuration(mixed $payload, array $apiCoordinates): ?array
    {
        if (! is_array($payload)) {
            return null;
        }

        $summary = null;
        $segment = null;

        if (isset($payload['routes']) && is_array($payload['routes']) && ! empty($payload['routes'][0])) {
            $firstRoute = $payload['routes'][0];
            if (is_array($firstRoute)) {
                $summary = is_array($firstRoute['summary'] ?? null) ? $firstRoute['summary'] : null;
                $segment = is_array($firstRoute['segments'][0] ?? null) ? $firstRoute['segments'][0] : null;
            }
        } elseif (isset($payload['features']) && is_array($payload['features']) && ! empty($payload['features'][0])) {
            $properties = $payload['features'][0]['properties'] ?? null;
            if (is_array($properties)) {
                $summary = is_array($properties['summary'] ?? null) ? $properties['summary'] : null;
                $segment = is_array($properties['segments'][0] ?? null) ? $properties['segments'][0] : null;
            }
        }

        $rawDistance = $summary['distance'] ?? $segment['distance'] ?? null;
        $rawDuration = $summary['duration'] ?? $segment['duration'] ?? null;

        if ($rawDistance === null && $summary === [] && $this->areCoordinatesIdentical($apiCoordinates)) {
            $rawDistance = 0.0;
            $rawDuration = 0.0;
        }

        if (! is_numeric($rawDistance) || (float) $rawDistance < 0) {
            return null;
        }

        return [
            'distance_meters' => (float) $rawDistance,
            'duration_seconds' => is_numeric($rawDuration) ? round((float) $rawDuration, 2) : null,
        ];
    }

    /**
     * Deteksi apakah respons HTTP gagal dari OpenRouteService menandakan rute tidak ditemukan.
     */
    protected function isRouteNotFoundResponse(int $httpStatus, mixed $payload): bool
    {
        if ($httpStatus === 404) {
            return true;
        }

        if (! is_array($payload)) {
            return false;
        }

        $errorCode = $payload['error']['code'] ?? null;
        if (in_array((int) $errorCode, [2009, 2010], true)) {
            return true;
        }

        $errorMessage = is_string($payload['error']['message'] ?? null)
            ? strtolower($payload['error']['message'])
            : (is_string($payload['error'] ?? null) ? strtolower($payload['error']) : '');

        if ($errorMessage !== '' && (
            str_contains($errorMessage, 'route could not be found')
            || str_contains($errorMessage, 'routable point')
        )) {
            return true;
        }

        return false;
    }

    /**
     * Cek apakah titik awal dan tujuan identik.
     *
     * @param  array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}  $apiCoordinates
     */
    protected function areCoordinatesIdentical(array $apiCoordinates): bool
    {
        return abs($apiCoordinates[0][0] - $apiCoordinates[1][0]) < 1e-6
            && abs($apiCoordinates[0][1] - $apiCoordinates[1][1]) < 1e-6;
    }

    /**
     * Parse berbagai format array koordinat menjadi ['latitude' => float, 'longitude' => float].
     *
     * @param  array<int|string, mixed>  $coordinates
     * @return array{latitude: float, longitude: float}|null
     */
    protected function parseCoordinateArray(array $coordinates): ?array
    {
        if (isset($coordinates['latitude'], $coordinates['longitude'])) {
            $lat = $coordinates['latitude'];
            $lng = $coordinates['longitude'];
        } elseif (isset($coordinates['lat'], $coordinates['lng'])) {
            $lat = $coordinates['lat'];
            $lng = $coordinates['lng'];
        } elseif (isset($coordinates['lat'], $coordinates['lon'])) {
            $lat = $coordinates['lat'];
            $lng = $coordinates['lon'];
        } elseif (array_is_list($coordinates) && count($coordinates) === 2) {
            // Format [longitude, latitude]
            $lng = $coordinates[0];
            $lat = $coordinates[1];
        } else {
            return null;
        }

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
        ];
    }

    /**
     * Ambil API key OpenRouteService dari konfigurasi.
     */
    protected function getApiKey(): ?string
    {
        $key = config('services.openrouteservice.api_key') ?? config('services.openrouteservice.key');

        if (! is_string($key) || trim($key) === '') {
            return null;
        }

        return trim($key);
    }

    /**
     * Ambil profil rute dari konfigurasi (default: driving-car).
     */
    protected function getProfile(): string
    {
        $profile = config('services.openrouteservice.profile', self::DEFAULT_PROFILE);

        return is_string($profile) && trim($profile) !== '' ? trim($profile) : self::DEFAULT_PROFILE;
    }

    /**
     * Bangun URL endpoint Directions API.
     */
    protected function buildDirectionsUrl(string $profile): string
    {
        $baseUrl = rtrim((string) config('services.openrouteservice.base_url', 'https://api.openrouteservice.org'), '/');

        return "{$baseUrl}/v2/directions/{$profile}";
    }

    /**
     * Kunci cache unik berdasarkan profil dan pasangan koordinat (dibulatkan 5 desimal ~1.1 meter).
     */
    protected function cacheKey(
        string $profile,
        float $vendorLatitude,
        float $vendorLongitude,
        float $customerLatitude,
        float $customerLongitude
    ): string {
        return sprintf(
            'ors:directions:%s:%.5f,%.5f:%.5f,%.5f',
            $profile,
            $vendorLongitude,
            $vendorLatitude,
            $customerLongitude,
            $customerLatitude
        );
    }

    /**
     * Struktur respons standar ketika pemanggilan gagal atau tidak valid.
     *
     * @param  'invalid_coordinates'|'unconfigured_api_key'|'route_not_found'|'api_error'  $status
     * @param  array{vendor: array{0: float, 1: float}, customer: array{0: float, 1: float}}|null  $coordinates
     * @return array{
     *     success: bool,
     *     status: 'invalid_coordinates'|'unconfigured_api_key'|'route_not_found'|'api_error',
     *     profile: string,
     *     distance_meters: null,
     *     distance_km: null,
     *     duration_seconds: null,
     *     coordinates: array{vendor: array{0: float, 1: float}, customer: array{0: float, 1: float}}|null,
     *     from_cache: bool,
     *     error_message: string
     * }
     */
    protected function failureResult(
        string $status,
        string $profile,
        ?array $coordinates,
        string $message
    ): array {
        return [
            'success' => false,
            'status' => $status,
            'profile' => $profile,
            'distance_meters' => null,
            'distance_km' => null,
            'duration_seconds' => null,
            'coordinates' => $coordinates,
            'from_cache' => false,
            'error_message' => $message,
        ];
    }
}
