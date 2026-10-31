<?php

namespace App\Livewire\Forms;

use App\Models\User;
use App\Models\Vendor;
use App\Services\Booking\CartService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Form;

class RegisterForm extends Form
{
    public string $account_type = 'customer';

    // Data Akun (Customer & Vendor Owner)
    public string $name = '';

    public string $email = '';

    public string $phone = '08';

    public string $nik = '';

    public ?string $address = null;

    public string $password = '';

    public string $password_confirmation = '';

    // Data Toko (Khusus Mitra Vendor)
    public string $store_name = '';

    public string $city = '';

    public string $store_address = '';

    public ?string $description = null;

    /**
     * Menjalankan validasi dan menyimpan pendaftaran pengguna baru.
     */
    public function store(): User
    {
        return $this->account_type === 'vendor'
            ? $this->storeVendor()
            : $this->storeCustomer();
    }

    /**
     * Pendaftaran akun customer (Penyewa).
     */
    protected function storeCustomer(): User
    {
        $this->email = Str::lower(trim($this->email));

        $validated = $this->validate([
            'name' => ['bail', 'required', 'string', 'max:255'],
            'email' => [
                'bail',
                'required',
                'string',
                'lowercase',
                'ends_with:@gmail.com',
                'email',
                'max:255',
                'unique:'.User::class,
            ],
            'phone' => ['bail', 'required', 'regex:/^08[0-9]{8,11}$/'],
            'nik' => ['bail', 'required', 'digits:16'],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['bail', 'required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.ends_with' => 'Email harus menggunakan @gmail.com.',
            'form.email.ends_with' => 'Email harus menggunakan @gmail.com.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'form.email.unique' => 'Email ini sudah terdaftar.',
            'phone.regex' => 'Nomor telepon harus diawali 08 dan terdiri dari 10 hingga 13 angka.',
            'form.phone.regex' => 'Nomor telepon harus diawali 08 dan terdiri dari 10 hingga 13 angka.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
            'form.nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'form.password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $validated['address'] = filled($validated['address'] ?? null) ? trim($validated['address']) : null;
        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'customer';

        event(new Registered($user = User::create($validated)));

        Auth::login($user);
        app(CartService::class)->syncOnLogin($user);

        return $user;
    }

    /**
     * Pendaftaran akun mitra rental (Vendor Owner).
     */
    protected function storeVendor(): User
    {
        $this->email = Str::lower(trim($this->email));

        $validated = $this->validate([
            'store_name' => ['bail', 'required', 'string', 'max:150'],
            'city' => ['bail', 'required', 'string', 'max:100'],
            'store_address' => ['bail', 'required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
            'name' => ['bail', 'required', 'string', 'max:255'],
            'email' => [
                'bail',
                'required',
                'string',
                'lowercase',
                'ends_with:@gmail.com',
                'email',
                'max:255',
                'unique:'.User::class,
            ],
            'phone' => ['bail', 'required', 'regex:/^08[0-9]{8,11}$/'],
            'nik' => ['bail', 'required', 'digits:16'],
            'password' => ['bail', 'required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            'store_name.required' => 'Nama toko/rental wajib diisi.',
            'form.store_name.required' => 'Nama toko/rental wajib diisi.',
            'city.required' => 'Kota/kabupaten operasional wajib diisi.',
            'form.city.required' => 'Kota/kabupaten operasional wajib diisi.',
            'store_address.required' => 'Alamat lengkap toko wajib diisi.',
            'form.store_address.required' => 'Alamat lengkap toko wajib diisi.',
            'email.ends_with' => 'Email harus menggunakan @gmail.com.',
            'form.email.ends_with' => 'Email harus menggunakan @gmail.com.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'form.email.unique' => 'Email ini sudah terdaftar.',
            'phone.regex' => 'Nomor telepon harus diawali 08 dan terdiri dari 10 hingga 13 angka.',
            'form.phone.regex' => 'Nomor telepon harus diawali 08 dan terdiri dari 10 hingga 13 angka.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
            'form.nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'form.password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        return DB::transaction(function () use ($validated): User {
            // Generate slug unik toko rental
            $baseSlug = Str::slug($validated['store_name']) ?: 'vendor-'.Str::random(6);
            $slug = $baseSlug;
            $counter = 1;
            while (Vendor::withTrashed()->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$counter++;
            }

            // 1. Buat record Vendor
            $vendor = Vendor::create([
                'name' => $validated['store_name'],
                'slug' => $slug,
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'address' => $validated['store_address'],
                'city' => $validated['city'],
                'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
                'status' => 'pending',
            ]);

            // 2. Buat akun User sebagai vendor_owner
            $user = User::create([
                'vendor_id' => $vendor->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'nik' => $validated['nik'],
                'address' => $validated['store_address'],
                'password' => Hash::make($validated['password']),
                'role' => 'vendor_owner',
                'is_active' => true,
            ]);

            event(new Registered($user));

            Auth::login($user);

            return $user;
        });
    }
}
