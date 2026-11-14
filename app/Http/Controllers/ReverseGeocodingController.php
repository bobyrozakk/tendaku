<?php

namespace App\Http\Controllers;

use App\Services\ReverseGeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReverseGeocodingController extends Controller
{
    public function __invoke(Request $request, ReverseGeocodingService $service): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'latitude.required' => 'Latitude wajib diisi.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus berada antara -90 dan 90.',
            'longitude.required' => 'Longitude wajib diisi.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus berada antara -180 dan 180.',
        ]);

        $address = $service->reverse((float) $validated['latitude'], (float) $validated['longitude']);

        if ($address === null) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat belum dapat ditemukan. Silakan isi alamat secara manual.',
                'data' => null,
            ], 503);
        }

        return response()->json([
            'success' => true,
            'data' => $address,
        ]);
    }
}
