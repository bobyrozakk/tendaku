<?php

use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public const MAX_ADDRESSES = 4;

    public bool $isCreating = false;

    public ?int $editingId = null;

    public ?int $selectedAddressId = null;

    // Form fields
    public string $label = 'Rumah';

    public string $recipient_name = '';

    public string $phone_number = '';

    public string $full_address = '';

    public string $province = '';

    public string $city = '';

    public string $district = '';

    public string $village = '';

    public string $postal_code = '';

    public string $notes = '';

    public mixed $latitude = null;

    public mixed $longitude = null;

    public bool $is_default = false;

    public function mount(): void
    {
        $this->resetForm();
    }

    public function startCreating(): void
    {
        $this->resetErrorBag();
        $this->resetForm();
        $user = Auth::user();

        if ($user->addresses()->count() >= self::MAX_ADDRESSES) {
            $this->addError('limit', 'Maksimal '.self::MAX_ADDRESSES.' alamat per akun. Hapus salah satu alamat untuk menambah yang baru.');

            return;
        }

        $this->recipient_name = $user->name ?? '';
        $this->phone_number = $user->phone ?? '';
        $this->is_default = ! $user->addresses()->exists();
        $this->isCreating = true;
        $this->editingId = null;
    }

    public function startEditing(int $id): void
    {
        $this->resetErrorBag();
        $address = Auth::user()->addresses()->findOrFail($id);

        $this->editingId = $address->id;
        $this->selectedAddressId = $address->id;
        $this->label = $address->label;
        $this->recipient_name = $address->recipient_name;
        $this->phone_number = $address->phone_number;
        $this->full_address = $address->full_address;
        $this->province = $address->province;
        $this->city = $address->city;
        $this->district = $address->district;
        $this->village = $address->village ?? '';
        $this->postal_code = $address->postal_code ?? '';
        $this->notes = $address->notes ?? '';
        $this->latitude = $address->latitude;
        $this->longitude = $address->longitude;
        $this->is_default = (bool) $address->is_default;
        $this->isCreating = false;
    }

    public function selectAddress(int $id): void
    {
        $address = Auth::user()->addresses()->find($id);

        if (! $address) {
            return;
        }

        // Alamat yang dipilih otomatis menjadi alamat utama
        if (! $address->is_default) {
            $address->setAsDefault();
            session()->flash('status', 'address-default-set');
        }

        $this->selectedAddressId = $address->id;
    }

    public function cancelForm(): void
    {
        $this->resetErrorBag();
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->isCreating = false;
        $this->editingId = null;
        $this->label = 'Rumah';
        $this->recipient_name = '';
        $this->phone_number = '';
        $this->full_address = '';
        $this->province = '';
        $this->city = '';
        $this->district = '';
        $this->village = '';
        $this->postal_code = '';
        $this->notes = '';
        $this->latitude = null;
        $this->longitude = null;
        $this->is_default = false;
    }

    private function normalizeCoordinateInput(mixed $value): mixed
    {
        if ($value === null || is_bool($value)) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed === '' ? null : $trimmed;
        }

        return $value;
    }

    public function save(): void
    {
        $user = Auth::user();

        $this->latitude = $this->normalizeCoordinateInput($this->latitude);
        $this->longitude = $this->normalizeCoordinateInput($this->longitude);

        $validated = $this->validate([
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
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'is_default' => ['boolean'],
        ], [
            'label.required' => 'Label alamat (misalnya Rumah, Kantor, atau Kos) wajib diisi.',
            'recipient_name.required' => 'Nama penerima wajib diisi.',
            'phone_number.required' => 'Nomor WhatsApp / telepon wajib diisi.',
            'phone_number.regex' => 'Nomor telepon harus diawali 08 dan terdiri dari 10 hingga 13 angka.',
            'full_address.required' => 'Alamat lengkap wajib diisi.',
            'province.required' => 'Provinsi wajib diisi.',
            'city.required' => 'Kota atau kabupaten wajib diisi.',
            'district.required' => 'Kecamatan wajib diisi.',
            'latitude.required_with' => 'Koordinat lintang (latitude) wajib diisi jika koordinat bujur diisi.',
            'latitude.numeric' => 'Koordinat lintang harus berupa angka yang valid.',
            'latitude.between' => 'Koordinat lintang harus berada antara -90 dan 90.',
            'longitude.required_with' => 'Koordinat bujur (longitude) wajib diisi jika koordinat lintang diisi.',
            'longitude.numeric' => 'Koordinat bujur harus berupa angka yang valid.',
            'longitude.between' => 'Koordinat bujur harus berada antara -180 dan 180.',
        ]);

        $validated['latitude'] = $validated['latitude'] !== null
            ? round((float) $validated['latitude'], 7)
            : null;
        $validated['longitude'] = $validated['longitude'] !== null
            ? round((float) $validated['longitude'], 7)
            : null;

        $isDefault = $validated['is_default'] ?? false;
        unset($validated['is_default']);

        // Jika alamat pertama, otomatis jadikan alamat utama
        if (! $user->addresses()->exists()) {
            $isDefault = true;
        }

        if (! $this->editingId && $user->addresses()->count() >= self::MAX_ADDRESSES) {
            $this->addError('limit', 'Maksimal '.self::MAX_ADDRESSES.' alamat per akun. Hapus salah satu alamat untuk menambah yang baru.');

            return;
        }

        if ($this->editingId) {
            $address = $user->addresses()->findOrFail($this->editingId);
            $address->update($validated);
            if ($isDefault) {
                $address->setAsDefault();
            }
            $this->selectedAddressId = $address->id;
            session()->flash('status', 'address-updated');
        } else {
            $address = new CustomerAddress($validated);
            $address->user_id = $user->id;
            $address->is_default = false;
            $address->save();

            if ($isDefault) {
                $address->setAsDefault();
            }
            $this->selectedAddressId = $address->id;
            session()->flash('status', 'address-created');
        }

        $this->resetForm();
    }

    public function setAsDefault(int $id): void
    {
        $address = Auth::user()->addresses()->findOrFail($id);
        $address->setAsDefault();
        $this->selectedAddressId = $id;
        session()->flash('status', 'address-default-set');
    }

    public function deleteAddress(int $id): void
    {
        $address = Auth::user()->addresses()->findOrFail($id);
        $wasDefault = $address->is_default;
        $userId = $address->user_id;

        $address->delete();

        if ($this->selectedAddressId === $id) {
            $this->selectedAddressId = null;
        }

        if ($wasDefault) {
            $next = CustomerAddress::where('user_id', $userId)->latest('id')->first();
            if ($next) {
                $next->setAsDefault();
                $this->selectedAddressId = $next->id;
            }
        }

        session()->flash('status', 'address-deleted');
    }

    public function with(): array
    {
        $user = Auth::user();
        $addresses = $user ? $user->addresses()->orderByDesc('is_default')->latest('id')->get() : collect();

        $defaultAddress = $addresses->firstWhere('is_default', true) ?? $addresses->first();

        if ($this->selectedAddressId && ! $addresses->contains('id', $this->selectedAddressId)) {
            $this->selectedAddressId = null;
        }

        if (! $this->selectedAddressId && $defaultAddress) {
            $this->selectedAddressId = $defaultAddress->id;
        }

        $selectedAddress = $addresses->firstWhere('id', $this->selectedAddressId) ?? $defaultAddress;
        $otherAddresses = $addresses->filter(fn ($addr) => $selectedAddress && $addr->id !== $selectedAddress->id)->values();

        return [
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
            'selectedAddress' => $selectedAddress,
            'otherAddresses' => $otherAddresses,
            'canAddMore' => $addresses->count() < self::MAX_ADDRESSES,
            'maxAddresses' => self::MAX_ADDRESSES,
        ];
    }
}; ?>

<section id="alamat">
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-wheat-200 pb-4">
        <div>
            <h2 class="text-lg font-extrabold text-darkbrown-900 font-heading flex items-center gap-2">
                <i data-lucide="map-pin" class="w-5 h-5 text-avocado-600"></i>
                <span>{{ __('Data Alamat Pengantaran') }}</span>
            </h2>
            <p class="mt-1 text-xs sm:text-sm text-darkbrown-600">
                {{ __('Kelola alamat domisili atau titik serah terima untuk pengantaran dari vendor.') }}
            </p>
        </div>

        @if(! $isCreating && ! $editingId)
            <div class="flex items-center gap-2 flex-wrap self-start sm:self-auto">
                @if ($canAddMore)
                    <button
                        type="button"
                        wire:click="startCreating"
                        class="btn-tendaku-primary inline-flex items-center justify-center gap-2 py-2 px-4 rounded-xl text-xs sm:text-sm font-bold shadow-sm transition-all self-start sm:self-auto"
                    >
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>{{ __('Tambah Alamat') }}</span>
                    </button>
                @else
                    <span class="text-xs font-semibold text-darkbrown-500">Batas {{ $maxAddresses }} alamat tercapai</span>
                @endif
            </div>
        @endif
    </header>

    @error('limit')
        <div class="mt-4 p-4 rounded-xl bg-red-50 border border-red-200 text-xs sm:text-sm font-semibold text-red-800">
            {{ $message }}
        </div>
    @enderror

    {{-- Alert Notifikasi Status --}}
    @if (session('status') === 'address-created')
        <div class="mt-4 p-4 rounded-xl bg-avocado-50 border border-avocado-200 text-xs sm:text-sm font-semibold text-avocado-900 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 text-avocado-600 shrink-0"></i>
            <span>Alamat baru berhasil ditambahkan.</span>
        </div>
    @elseif (session('status') === 'address-updated')
        <div class="mt-4 p-4 rounded-xl bg-avocado-50 border border-avocado-200 text-xs sm:text-sm font-semibold text-avocado-900 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 text-avocado-600 shrink-0"></i>
            <span>Alamat berhasil diperbarui.</span>
        </div>
    @elseif (session('status') === 'address-deleted')
        <div class="mt-4 p-4 rounded-xl bg-wheat-100 border border-wheat-300 text-xs sm:text-sm font-semibold text-darkbrown-800 flex items-center gap-2">
            <i data-lucide="trash-2" class="w-4 h-4 text-darkbrown-600 shrink-0"></i>
            <span>Alamat berhasil dihapus.</span>
        </div>
    @elseif (session('status') === 'address-default-set')
        <div class="mt-4 p-4 rounded-xl bg-avocado-50 border border-avocado-200 text-xs sm:text-sm font-semibold text-avocado-900 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 text-avocado-600 shrink-0"></i>
            <span>Alamat utama berhasil diubah.</span>
        </div>
    @endif

    {{-- ============================================================
         1. FORM TAMBAH / EDIT ALAMAT
         ============================================================ --}}
    @if ($isCreating || $editingId)
        <x-address-autofill
            :picker-id="'customer-address-picker-'.($editingId ?? 'create')"
            :as-form="true"
            wire:submit="save"
            wire:key="customer-address-autofill-{{ $editingId ?? 'create' }}"
            class="mt-6 space-y-4 p-5 rounded-xl bg-wheat-50/70 border border-wheat-200"
        >
            <div class="flex items-center justify-between border-b border-wheat-200 pb-3">
                <h3 class="text-sm font-extrabold text-darkbrown-900 font-heading">
                    {{ $editingId ? __('Ubah Alamat') : __('Tambah Alamat Baru') }}
                </h3>
                <span class="text-xs text-darkbrown-500 font-medium">* Wajib diisi</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Label Alamat -->
                <div>
                    <label for="addr_label" class="block text-xs font-bold uppercase text-darkbrown-700 mb-1 tracking-wider">
                        Label Alamat <span class="text-red-500">*</span>
                    </label>
                    <input
                        wire:model="label"
                        id="addr_label"
                        type="text"
                        placeholder="Contoh: Rumah, Kos, Kantor, Titik Kumpul"
                        class="input-tendaku w-full py-2 px-3 rounded-xl text-xs font-medium border-wheat-300"
                        required
                    />
                    <x-input-error class="mt-1" :messages="$errors->get('label')" />
                </div>

                <!-- Nama Penerima -->
                <div>
                    <label for="addr_recipient" class="block text-xs font-bold uppercase text-darkbrown-700 mb-1 tracking-wider">
                        Nama Penerima <span class="text-red-500">*</span>
                    </label>
                    <input
                        wire:model="recipient_name"
                        id="addr_recipient"
                        type="text"
                        placeholder="Nama lengkap penerima"
                        class="input-tendaku w-full py-2 px-3 rounded-xl text-xs font-medium border-wheat-300"
                        required
                    />
                    <x-input-error class="mt-1" :messages="$errors->get('recipient_name')" />
                </div>

                <!-- Nomor Telepon -->
                <div>
                    <label for="addr_phone" class="block text-xs font-bold uppercase text-darkbrown-700 mb-1 tracking-wider">
                        Nomor WhatsApp / Telepon <span class="text-red-500">*</span>
                    </label>
                    <input
                        wire:model="phone_number"
                        id="addr_phone"
                        type="tel"
                        placeholder="08xxxxxxxxxx"
                        class="input-tendaku w-full py-2 px-3 rounded-xl text-xs font-medium border-wheat-300"
                        required
                    />
                    <x-input-error class="mt-1" :messages="$errors->get('phone_number')" />
                </div>

                <!-- Kota / Kabupaten -->
                <div>
                    <label for="addr_city" class="block text-xs font-bold uppercase text-darkbrown-700 mb-1 tracking-wider">
                        Kota / Kabupaten <span class="text-red-500">*</span>
                    </label>
                    <input
                        wire:model="city"
                        data-address-field="city"
                        id="addr_city"
                        type="text"
                        placeholder="Contoh: Kota Malang"
                        class="input-tendaku w-full py-2 px-3 rounded-xl text-xs font-medium border-wheat-300"
                        required
                    />
                    <x-input-error class="mt-1" :messages="$errors->get('city')" />
                </div>

                <!-- Provinsi -->
                <div>
                    <label for="addr_province" class="block text-xs font-bold uppercase text-darkbrown-700 mb-1 tracking-wider">
                        Provinsi <span class="text-red-500">*</span>
                    </label>
                    <input
                        wire:model="province"
                        data-address-field="province"
                        id="addr_province"
                        type="text"
                        placeholder="Contoh: Jawa Timur"
                        class="input-tendaku w-full py-2 px-3 rounded-xl text-xs font-medium border-wheat-300"
                        required
                    />
                    <x-input-error class="mt-1" :messages="$errors->get('province')" />
                </div>

                <!-- Kecamatan -->
                <div>
                    <label for="addr_district" class="block text-xs font-bold uppercase text-darkbrown-700 mb-1 tracking-wider">
                        Kecamatan <span class="text-red-500">*</span>
                    </label>
                    <input
                        wire:model="district"
                        data-address-field="district"
                        id="addr_district"
                        type="text"
                        placeholder="Contoh: Lowokwaru"
                        class="input-tendaku w-full py-2 px-3 rounded-xl text-xs font-medium border-wheat-300"
                        required
                    />
                    <x-input-error class="mt-1" :messages="$errors->get('district')" />
                </div>

                <!-- Kelurahan / Desa -->
                <div>
                    <label for="addr_village" class="block text-xs font-bold uppercase text-darkbrown-700 mb-1 tracking-wider">
                        Kelurahan / Desa <span class="text-darkbrown-400 font-normal">(Opsional)</span>
                    </label>
                    <input
                        wire:model="village"
                        data-address-field="village"
                        id="addr_village"
                        type="text"
                        placeholder="Contoh: Ketawanggede"
                        class="input-tendaku w-full py-2 px-3 rounded-xl text-xs font-medium border-wheat-300"
                    />
                    <x-input-error class="mt-1" :messages="$errors->get('village')" />
                </div>

                <!-- Kode Pos -->
                <div>
                    <label for="addr_postal" class="block text-xs font-bold uppercase text-darkbrown-700 mb-1 tracking-wider">
                        Kode Pos <span class="text-darkbrown-400 font-normal">(Opsional)</span>
                    </label>
                    <input
                        wire:model="postal_code"
                        data-address-field="postal_code"
                        id="addr_postal"
                        type="text"
                        placeholder="65145"
                        class="input-tendaku w-full py-2 px-3 rounded-xl text-xs font-medium border-wheat-300"
                    />
                    <x-input-error class="mt-1" :messages="$errors->get('postal_code')" />
                </div>

                <!-- Alamat Lengkap (Jalan, RT/RW, No Rumah) -->
                <div class="sm:col-span-2">
                    <label for="addr_full" class="block text-xs font-bold uppercase text-darkbrown-700 mb-1 tracking-wider">
                        Alamat Lengkap (Nama Jalan, No. Rumah, RT/RW) <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        wire:model="full_address"
                        data-address-field="full_address"
                        id="addr_full"
                        rows="2"
                        placeholder="Tuliskan nama jalan lengkap, nomor rumah, nomor blok, RT/RW..."
                        class="input-tendaku w-full py-2 px-3 rounded-xl text-xs font-medium border-wheat-300"
                        required
                    ></textarea>
                    <x-input-error class="mt-1" :messages="$errors->get('full_address')" />
                </div>

                <!-- Catatan / Patokan Lokasi -->
                <div class="sm:col-span-2">
                    <label for="addr_notes" class="block text-xs font-bold uppercase text-darkbrown-700 mb-1 tracking-wider">
                        Catatan / Patokan Lokasi <span class="text-darkbrown-400 font-normal">(Opsional)</span>
                    </label>
                    <input
                        wire:model="notes"
                        id="addr_notes"
                        type="text"
                        placeholder="Contoh: Pagar hitam depan warung madura, dekat pos satpam"
                        class="input-tendaku w-full py-2 px-3 rounded-xl text-xs font-medium border-wheat-300"
                    />
                    <x-input-error class="mt-1" :messages="$errors->get('notes')" />
                </div>

                <!-- Pemilih Titik Koordinat Peta -->
                <div class="sm:col-span-2 pt-1" wire:key="customer-address-picker-{{ $editingId ?? 'create' }}">
                    @if ($editingId && ($latitude === null || $longitude === null || $latitude === '' || $longitude === ''))
                        <div class="mb-2.5 px-3 py-2 rounded-lg border border-wheat-300 bg-white text-xs text-darkbrown-700 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-goldenrod-500 shrink-0" aria-hidden="true"></span>
                            <span>Pin lokasi belum diatur untuk alamat ini. Klik peta di bawah untuk menambahkan titik lokasi pengantaran.</span>
                        </div>
                    @endif

                    <x-location-picker
                        id="customer-address-picker-{{ $editingId ?? 'create' }}"
                        :latitude="$latitude"
                        :longitude="$longitude"
                        wire-latitude-model="latitude"
                        wire-longitude-model="longitude"
                        label="Titik Lokasi Pengantaran (Peta)"
                        help-text="Pilih titik pada peta untuk mengisi alamat secara otomatis. Semua kolom alamat tetap dapat Anda ubah."
                    />
                </div>

                <!-- Jadikan Alamat Utama Checkbox -->
                <div class="sm:col-span-2 flex items-center gap-2 pt-1">
                    <input
                        wire:model="is_default"
                        id="addr_is_default"
                        type="checkbox"
                        class="rounded border-wheat-300 text-avocado-600 focus:ring-avocado-500"
                    />
                    <label for="addr_is_default" class="text-xs font-semibold text-darkbrown-800 cursor-pointer">
                        Jadikan alamat utama (dipilih otomatis saat pemesanan)
                    </label>
                </div>
            </div>

            <!-- Tombol Aksi Form -->
            <div class="flex items-center gap-3 pt-3 border-t border-wheat-200">
                <button
                    type="submit"
                    class="btn-tendaku-primary inline-flex items-center gap-2 py-2 px-5 rounded-xl text-xs sm:text-sm font-bold shadow-sm transition-all"
                >
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>{{ __('Simpan Alamat') }}</span>
                </button>

                <button
                    type="button"
                    wire:click="cancelForm"
                    class="btn-tendaku-secondary inline-flex items-center gap-2 py-2 px-4 rounded-xl text-xs sm:text-sm font-bold hover:bg-wheat-100 transition-all"
                >
                    <i data-lucide="x" class="w-4 h-4"></i>
                    <span>{{ __('Batal') }}</span>
                </button>
            </div>
        </x-address-autofill>
    @endif

    {{-- ============================================================
         2. DAFTAR ALAMAT TERSIMPAN (ALAMAT UTAMA/TERPILIH & DROPDOWN ALAMAT LAINNYA)
         ============================================================ --}}
    <div class="mt-6 space-y-4">
        @if ($addresses->isNotEmpty())
            {{-- Kartu Alamat Terpilih --}}
            @if ($selectedAddress)
                <div
                    wire:key="addr-{{ $selectedAddress->id }}"
                    class="p-4 sm:p-5 rounded-xl border transition-all {{ $selectedAddress->is_default ? 'bg-white border-avocado-400 ring-1 ring-avocado-400/30' : 'bg-white border-wheat-200 hover:border-wheat-300' }}"
                >
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                        <div class="space-y-1.5 min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-wheat-100 text-darkbrown-800 border border-wheat-300">
                                    {{ $selectedAddress->label }}
                                </span>

                                <i
                                    data-lucide="star"
                                    class="w-4 h-4 shrink-0 {{ $selectedAddress->is_default ? 'text-goldenrod-500 fill-current' : 'text-darkbrown-400' }}"
                                    title="{{ $selectedAddress->is_default ? 'Alamat Utama' : 'Bukan alamat utama' }}"
                                    aria-label="{{ $selectedAddress->is_default ? 'Alamat Utama' : 'Bukan alamat utama' }}"
                                ></i>
                            </div>

                            <p class="text-xs sm:text-sm text-darkbrown-800 leading-relaxed">
                                {{ $selectedAddress->formatted_address }}
                            </p>

                            @if ($selectedAddress->notes)
                                <p class="text-xs text-darkbrown-600 italic">
                                    Patokan: {{ $selectedAddress->notes }}
                                </p>
                            @endif
                        </div>

                        <!-- Tombol Aksi per Alamat -->
                        <div class="flex items-center gap-2 shrink-0 pt-2 sm:pt-0 border-t sm:border-0 border-wheat-100">
                            <button
                                type="button"
                                wire:click="startEditing({{ $selectedAddress->id }})"
                                class="p-1.5 rounded-lg text-darkbrown-600 hover:text-darkbrown-900 hover:bg-wheat-100 transition"
                                title="Ubah Alamat"
                            >
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>

                            <button
                                type="button"
                                wire:confirm="Yakin ingin menghapus alamat ini?"
                                wire:click="deleteAddress({{ $selectedAddress->id }})"
                                class="p-1.5 rounded-lg text-red-500 hover:text-red-700 hover:bg-red-50 transition"
                                title="Hapus Alamat"
                            >
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                </div>

                {{-- Alamat lainnya: di luar outline card, membuka ke bawah --}}
                @if ($otherAddresses->isNotEmpty())
                    <div class="px-1" x-data="{ open: false }">
                        <button
                            type="button"
                            id="btn-dropdown-alamat-lainnya"
                            @click="open = ! open"
                            :aria-expanded="open"
                            aria-controls="daftar-alamat-lainnya"
                            class="flex w-full items-center justify-between gap-3 text-left cursor-pointer text-xs sm:text-sm font-semibold text-darkbrown-800"
                        >
                            <span>Alamat lainnya ({{ $otherAddresses->count() }})</span>
                            <span class="shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open }">
                                <i data-lucide="chevron-down" class="w-4 h-4 text-darkbrown-500"></i>
                            </span>
                        </button>

                        <ul id="daftar-alamat-lainnya" x-show="open" x-cloak class="mt-2 divide-y divide-wheat-100">
                            @foreach ($otherAddresses as $other)
                                <li wire:key="menu-addr-{{ $other->id }}">
                                    <button
                                        type="button"
                                        wire:click="selectAddress({{ $other->id }})"
                                        class="w-full text-left py-2.5 flex items-start gap-2.5 hover:bg-wheat-50 transition rounded-lg px-1"
                                    >
                                        <i data-lucide="star" class="w-4 h-4 shrink-0 mt-0.5 {{ $other->is_default ? 'text-goldenrod-500 fill-current' : 'text-darkbrown-400' }}"></i>
                                        <span class="min-w-0">
                                            <span class="block text-xs font-bold text-darkbrown-900">
                                                {{ $other->label }} &middot; {{ $other->recipient_name }}
                                            </span>
                                            <span class="block text-[11px] sm:text-xs text-darkbrown-600 leading-snug">
                                                {{ $other->formatted_address }}
                                            </span>
                                        </span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endif
        @else
            <div class="text-center py-8 px-4 rounded-xl bg-wheat-50 border border-wheat-200 space-y-3">
                <div class="w-12 h-12 mx-auto rounded-xl bg-wheat-100 text-darkbrown-400 flex items-center justify-center">
                    <i data-lucide="map-pin-off" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-darkbrown-900">Belum ada alamat tersimpan</h3>
                    <p class="text-xs text-darkbrown-500 mt-0.5">Tambahkan alamat agar Anda dapat memilih pengantaran saat memesan perlengkapan camping.</p>
                </div>
                @if (! $isCreating)
                    <button
                        type="button"
                        wire:click="startCreating"
                        class="btn-tendaku-primary inline-flex items-center gap-1.5 py-2 px-4 rounded-xl text-xs font-bold shadow-sm"
                    >
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Tambah Alamat Pertama</span>
                    </button>
                @endif
            </div>
        @endif
    </div>
</section>
