<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Open-Meteo Forecast API
    |--------------------------------------------------------------------------
    |
    | Open-Meteo gratis dan tanpa API key. Endpoint resmi:
    | https://api.open-meteo.com/v1/forecast
    |
    */

    'endpoint' => env('WEATHER_API_URL', 'https://api.open-meteo.com/v1/forecast'),

    'timezone' => env('WEATHER_TIMEZONE', 'Asia/Jakarta'),

    'timeout' => (int) env('WEATHER_TIMEOUT', 10),

    'retry_times' => (int) env('WEATHER_RETRY_TIMES', 2),

    'retry_sleep_ms' => (int) env('WEATHER_RETRY_SLEEP_MS', 300),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Lama cache forecast per spot + rentang tanggal (menit).
    |
    */

    'cache_ttl_minutes' => (int) env('WEATHER_CACHE_TTL', 30),

    /*
    |--------------------------------------------------------------------------
    | Spot Camping
    |--------------------------------------------------------------------------
    |
    | Koordinat sudah diverifikasi (lihat docs/contract.md §7):
    |   - Gunung Bromo  : Open-Meteo Geocoding (-7.9431, 112.9538, elev 2329 m)
    |   - Ranu Kumbolo  : Wikipedia/Wikidata (-8.050, 112.917, elev 2400 m)
    |   - Kota Batu     : Open-Meteo Geocoding (-7.87, 112.5283, elev 881 m)
    |
    | Kunci spot dipakai sebagai parameter `spot` pada WeatherService::forecast().
    |
    */

    'spots' => [
        'bromo' => [
            'label' => 'Gunung Bromo',
            'latitude' => -7.9431,
            'longitude' => 112.9538,
        ],
        'ranu_kumbolo' => [
            'label' => 'Ranu Kumbolo',
            'latitude' => -8.05,
            'longitude' => 112.917,
        ],
        'batu' => [
            'label' => 'Kota Batu',
            'latitude' => -7.87,
            'longitude' => 112.5283,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ambang Cuaca Ekstrem
    |--------------------------------------------------------------------------
    |
    | ⚠️ NILAI AWAL — menunggu persetujuan Boby (PRD §6 "Keputusan Manusia").
    | Semua ambang di sini (bukan di dalam kode) supaya bisa diubah tanpa
    | menyentuh service.
    |
    |   - thunderstorm_codes : kode WMO petir (95/96/97/99)
    |   - precipitation_mm   : curah hujan harian (mm) yang dianggap ekstrem
    |   - wind_kmh           : kecepatan angin maks (km/h) yang dianggap ekstrem
    |
    */

    'extreme' => [
        'thunderstorm_codes' => [95, 96, 97, 99],
        'precipitation_mm' => 50.0,
        'wind_kmh' => 40.0,
    ],

];
