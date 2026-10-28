<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    /**
     * Daftar alamat customer yang sedang login.
     */
    public function index(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        $addresses = $user->addresses()
            ->orderByDesc('is_default')
            ->latest('id')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $addresses,
            ]);
        }

        return redirect()->route('profile');
    }

    /**
     * Simpan alamat baru untuk customer yang sedang login.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        $validated = $this->validateAddress($request);

        $hasAddresses = $user->addresses()->exists();
        $isDefault = (bool) ($validated['is_default'] ?? false);

        // Jika alamat pertama, otomatis jadikan alamat utama
        if (! $hasAddresses) {
            $isDefault = true;
        }

        $address = new CustomerAddress($validated);
        $address->user_id = $user->id;
        $address->is_default = false;
        $address->save();

        if ($isDefault) {
            $address->setAsDefault();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Alamat berhasil ditambahkan.',
                'data' => $address->fresh(),
            ], 201);
        }

        return back()->with('success', 'Alamat berhasil ditambahkan.');
    }

    /**
     * Perbarui alamat customer.
     */
    public function update(Request $request, CustomerAddress $address): JsonResponse|RedirectResponse
    {
        $this->authorizeOwner($address);

        $validated = $this->validateAddress($request);

        $isDefault = (bool) ($validated['is_default'] ?? false);
        unset($validated['is_default']);

        $address->update($validated);

        if ($isDefault) {
            $address->setAsDefault();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Alamat berhasil diperbarui.',
                'data' => $address->fresh(),
            ]);
        }

        return back()->with('success', 'Alamat berhasil diperbarui.');
    }

    /**
     * Hapus alamat customer.
     */
    public function destroy(Request $request, CustomerAddress $address): JsonResponse|RedirectResponse
    {
        $this->authorizeOwner($address);

        $wasDefault = $address->is_default;
        $userId = $address->user_id;

        $address->delete();

        // Jika alamat yang dihapus adalah default, jadikan alamat tersisa yang terbaru sebagai default
        if ($wasDefault) {
            $nextDefault = CustomerAddress::where('user_id', $userId)->latest('id')->first();
            if ($nextDefault) {
                $nextDefault->setAsDefault();
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Alamat berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Alamat berhasil dihapus.');
    }

    /**
     * Tandai alamat sebagai alamat utama.
     */
    public function setDefault(Request $request, CustomerAddress $address): JsonResponse|RedirectResponse
    {
        $this->authorizeOwner($address);

        $address->setAsDefault();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Alamat utama berhasil diperbarui.',
                'data' => $address->fresh(),
            ]);
        }

        return back()->with('success', 'Alamat utama berhasil diubah.');
    }

    /**
     * Validasi input data alamat lengkap.
     */
    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:150'],
            'phone_number' => ['required', 'regex:/^08[0-9]{8,11}$/'],
            'full_address' => ['required', 'string', 'max:500'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_default' => ['nullable', 'boolean'],
        ], [
            'label.required' => 'Label alamat (cth: Rumah, Kantor, Kos) wajib diisi.',
            'recipient_name.required' => 'Nama penerima wajib diisi.',
            'phone_number.required' => 'Nomor telepon penerima wajib diisi.',
            'phone_number.regex' => 'Nomor telepon harus diawali 08 dan terdiri dari 10 hingga 13 angka.',
            'full_address.required' => 'Alamat lengkap wajib diisi.',
            'province.required' => 'Provinsi wajib diisi.',
            'city.required' => 'Kota atau kabupaten wajib diisi.',
            'district.required' => 'Kecamatan wajib diisi.',
        ]);
    }

    /**
     * Pastikan customer hanya mengakses alamat miliknya sendiri.
     */
    private function authorizeOwner(CustomerAddress $address): void
    {
        if ((int) $address->user_id !== (int) Auth::id()) {
            abort(403, 'Anda tidak memiliki hak akses ke alamat ini.');
        }
    }
}
