<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class ReverseGeocodingService
{
    /**
     * @return array{full_address: ?string, province: ?string, city: ?string, district: ?string, village: ?string, postal_code: ?string}|null
     */
    public function reverse(float $latitude, float $longitude): ?array
    {
        $apiKey = config('services.locationiq.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(10)
                ->withoutRedirecting()
                ->get('https://us1.locationiq.com/v1/reverse', [
                    'key' => $apiKey,
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'accept-language' => 'id',
                ]);
        } catch (ConnectionException|RequestException) {
            return null;
        }

        $payload = $response->json();

        if (! $response->successful() || ! is_array($payload) || array_key_exists('error', $payload)) {
            return null;
        }

        return $this->normalizeAddress($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{full_address: ?string, province: ?string, city: ?string, district: ?string, village: ?string, postal_code: ?string}|null
     */
    private function normalizeAddress(array $payload): ?array
    {
        $address = is_array($payload['address'] ?? null) ? $payload['address'] : [];
        $road = $this->firstAvailableString($address, 'road');
        $houseNumber = $this->firstAvailableString($address, 'house_number');
        $streetAddress = $road !== null ? trim($road.' '.($houseNumber ?? '')) : null;

        $normalized = [
            'full_address' => $this->firstAvailableString($payload, 'display_name') ?? $streetAddress,
            'province' => $this->firstAvailableString($address, 'state'),
            'city' => $this->firstAvailableString($address, 'city', 'county', 'municipality', 'town'),
            'district' => $this->firstAvailableString($address, 'city_district', 'district'),
            'village' => $this->firstAvailableString($address, 'village'),
            'postal_code' => $this->firstAvailableString($address, 'postcode'),
        ];

        return array_filter($normalized, static fn (?string $value): bool => $value !== null) === []
            ? null
            : $normalized;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function firstAvailableString(array $values, string ...$keys): ?string
    {
        foreach ($keys as $key) {
            $value = $values[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
