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
    | Seluruh lokasi berada di Jawa Timur. Koordinat diambil dari Open-Meteo
    | Geocoding API (https://geocoding-api.open-meteo.com) dan elevasi dicatat
    | apa adanya sebagai informasi tampilan — tidak dikirim ke Open-Meteo.
    |
    | Atribut tiap spot:
    |   - label     : nama tampilan
    |   - region    : wilayah administratif untuk pengelompokan pada dropdown
    |   - latitude  : lintang (derajat desimal)
    |   - longitude : bujur (derajat desimal)
    |   - elevation : elevasi perkiraan (meter)
    |
    | Kunci spot dipakai sebagai parameter `spot` pada WeatherService::forecast().
    | Menambah spot cukup menambah baris di sini — tidak ada perubahan kode.
    |
    */

    'spots' => [
        // Wilayah Malang Raya
        'kota_malang' => [
            'label' => 'Kota Malang',
            'region' => 'Malang Raya',
            'latitude' => -7.9797,
            'longitude' => 112.6304,
            'elevation' => 445,
        ],
        'kabupaten_malang' => [
            'label' => 'Kabupaten Malang',
            'region' => 'Malang Raya',
            'latitude' => -8.1667,
            'longitude' => 112.6667,
            'elevation' => 500,
        ],
        'kota_batu' => [
            'label' => 'Kota Batu',
            'region' => 'Malang Raya',
            'latitude' => -7.87,
            'longitude' => 112.5283,
            'elevation' => 881,
        ],

        // Pegunungan & taman nasional
        'gunung_bromo' => [
            'label' => 'Gunung Bromo',
            'region' => 'Pegunungan',
            'latitude' => -7.9431,
            'longitude' => 112.9538,
            'elevation' => 2329,
        ],
        'ranu_kumbolo' => [
            'label' => 'Ranu Kumbolo',
            'region' => 'Pegunungan',
            'latitude' => -8.05,
            'longitude' => 112.917,
            'elevation' => 2400,
        ],
        'gunung_semeru' => [
            'label' => 'Gunung Semeru',
            'region' => 'Pegunungan',
            'latitude' => -8.1077,
            'longitude' => 112.9222,
            'elevation' => 3676,
        ],
        'gunung_arjuno' => [
            'label' => 'Gunung Arjuno',
            'region' => 'Pegunungan',
            'latitude' => -7.7646,
            'longitude' => 112.5898,
            'elevation' => 3339,
        ],
        'kawah_ijen' => [
            'label' => 'Kawah Ijen',
            'region' => 'Pegunungan',
            'latitude' => -8.0583,
            'longitude' => 114.2422,
            'elevation' => 2386,
        ],
        'tumpak_sewu' => [
            'label' => 'Tumpak Sewu',
            'region' => 'Pegunungan',
            'latitude' => -8.2308,
            'longitude' => 112.9181,
            'elevation' => 500,
        ],
        'air_terjun_madakaripura' => [
            'label' => 'Air Terjun Madakaripura',
            'region' => 'Pegunungan',
            'latitude' => -7.8489,
            'longitude' => 113.0292,
            'elevation' => 600,
        ],
        'gunung_kelud' => [
            'label' => 'Gunung Kelud',
            'region' => 'Pegunungan',
            'latitude' => -7.9301,
            'longitude' => 112.3089,
            'elevation' => 1731,
        ],

        // Pantai & pesisir
        'pantai_balekambang' => [
            'label' => 'Pantai Balekambang',
            'region' => 'Pantai & Pesisir',
            'latitude' => -8.4025,
            'longitude' => 112.5378,
            'elevation' => 5,
        ],
        'pantai_tiga_warna' => [
            'label' => 'Pantai Tiga Warna',
            'region' => 'Pantai & Pesisir',
            'latitude' => -8.4297,
            'longitude' => 112.5969,
            'elevation' => 10,
        ],
        'pantai_papuma' => [
            'label' => 'Pantai Papuma',
            'region' => 'Pantai & Pesisir',
            'latitude' => -8.4422,
            'longitude' => 113.5303,
            'elevation' => 5,
        ],
        'pantai_pulau_merah' => [
            'label' => 'Pantai Pulau Merah',
            'region' => 'Pantai & Pesisir',
            'latitude' => -8.5967,
            'longitude' => 114.0225,
            'elevation' => 5,
        ],
        'pantai_sukamade' => [
            'label' => 'Pantai Sukamade',
            'region' => 'Pantai & Pesisir',
            'latitude' => -8.5464,
            'longitude' => 113.8906,
            'elevation' => 5,
        ],

        // Perkotaan
        'kota_surabaya' => [
            'label' => 'Kota Surabaya',
            'region' => 'Perkotaan',
            'latitude' => -7.2575,
            'longitude' => 112.7521,
            'elevation' => 5,
        ],
        'kota_kediri' => [
            'label' => 'Kota Kediri',
            'region' => 'Perkotaan',
            'latitude' => -7.848,
            'longitude' => 112.0178,
            'elevation' => 67,
        ],
        'kota_madiun' => [
            'label' => 'Kota Madiun',
            'region' => 'Perkotaan',
            'latitude' => -7.6298,
            'longitude' => 111.5239,
            'elevation' => 63,
        ],
        'kota_jember' => [
            'label' => 'Kota Jember',
            'region' => 'Perkotaan',
            'latitude' => -8.1689,
            'longitude' => 113.7021,
            'elevation' => 89,
        ],
        'kota_banyuwangi' => [
            'label' => 'Kota Banyuwangi',
            'region' => 'Perkotaan',
            'latitude' => -8.2192,
            'longitude' => 114.3692,
            'elevation' => 20,
        ],
        'kota_probolinggo' => [
            'label' => 'Kota Probolinggo',
            'region' => 'Perkotaan',
            'latitude' => -7.7543,
            'longitude' => 113.2159,
            'elevation' => 10,
        ],
        'kota_pasuruan' => [
            'label' => 'Kota Pasuruan',
            'region' => 'Perkotaan',
            'latitude' => -7.6469,
            'longitude' => 112.9075,
            'elevation' => 9,
        ],
        'kota_mojokerto' => [
            'label' => 'Kota Mojokerto',
            'region' => 'Perkotaan',
            'latitude' => -7.4708,
            'longitude' => 112.4338,
            'elevation' => 22,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ambang Cuaca Ekstrem
    |--------------------------------------------------------------------------
    |
    | ✅ Disetujui Boby (2026-10-21) — nilai final:
    |   - thunderstorm_codes : kode WMO petir (95/96/97/99)
    |   - precipitation_mm   : curah hujan harian (mm) yang dianggap ekstrem
    |   - wind_kmh           : kecepatan angin maks (km/h) yang dianggap ekstrem
    |
    | Ambang tetap disimpan di config (bukan di dalam kode) supaya bisa diubah
    | tanpa menyentuh service.
    |
    */

    'extreme' => [
        'thunderstorm_codes' => [95, 96, 97, 99],
        'precipitation_mm' => 50.0,
        'wind_kmh' => 40.0,
    ],

];
