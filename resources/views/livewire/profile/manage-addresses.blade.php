
<?php

use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public const MAX_ADDRESSES = 4;

    /** Properti isian form: dipakai untuk reset ke nilai awal. */
    private const FORM_FIELDS = [
        'label', 'recipient_name', 'phone_number', 'full_address', 'province',
        'city', 'district', 'village', 'postal_code', 'notes',
        'latitude', 'longitude', 'is_default',
    ];

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

    public function startCreating(): void
    {
        $this->resetErrorBag();
        $this->resetForm();

        $user = Auth::user();
        $addressCount = $user->addresses()->count();

        if ($addressCount >= self::MAX_ADDRESSES) {
            $this->addError('limit', $this->limitMessage());

            return;
        }

        // Data penerima diambil otomatis dari biodata profil.
        $this->recipient_name = $user->name ?? '';
        $this->phone_number = $user->phone ?? '';
        $this->is_default = $addressCount === 0;
        $this->isCreating = true;
    }

    public function startEditing(int $id): void
    {
        $this->resetErrorBag();
        $address = Auth::user()->addresses()->findOrFail($id);

        $this->isCreating = false;
        $this->editingId = $address->id;
        $this->selectedAddressId = $address->id;

        $this->fill([
            'label' => $address->label,
            'recipient_name' => $address->recipient_name,
            'phone_number' => $address->phone_number,
            'full_address' => $address->full_address,
            'province' => $address->province,
            'city' => $address->city,
            'district' => $address->district,
            'village' => $address->village ?? '',
            'postal_code' => $address->postal_code ?? '',
            'notes' => $address->notes ?? '',
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'is_default' => (bool) $address->is_default,
        ]);
    }

    public function selectAddress(int $id): void
    {
        $address = Auth::user()->addresses()->find($id);

        if (! $address) {
            return;
        }

        // Alamat yang dipilih otomatis menjadi alamat utama.
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
        $this->reset(['isCreating', 'editingId', ...self::FORM_FIELDS]);
    }

    public function save(): void
    {
        $user = Auth::user();

        $this->latitude = $this->normalizeCoordinateInput($this->latitude);
        $this->longitude = $this->normalizeCoordinateInput($this->longitude);

        $validated = $this->validate();

        $validated['latitude'] = $validated['latitude'] !== null
            ? round((float) $validated['latitude'], 7)
            : null;

        $validated['longitude'] = $validated['longitude'] !== null
            ? round((float) $validated['longitude'], 7)
            : null;

        $isDefault = (bool) ($validated['is_default'] ?? false);
        unset($validated['is_default']);

        // Satu query hitung dipakai untuk semua pengecekan di bawah.
        $addressCount = $user->addresses()->count();

        // Jika alamat pertama, otomatis jadikan alamat utama.
        if ($addressCount === 0) {
            $isDefault = true;
        }

        if (! $this->editingId && $addressCount >= self::MAX_ADDRESSES) {
            $this->addError('limit', $this->limitMessage());

            return;
        }

        if ($this->editingId) {
            $address = $user->addresses()->findOrFail($this->editingId);
            $address->update($validated);

            // Lewati transaksi setAsDefault bila alamat ini memang sudah utama.
            if ($isDefault && ! $address->is_default) {
                $address->setAsDefault();
            }

            session()->flash('status', 'address-updated');
        } else {
            $address = new CustomerAddress($validated);
            $address->user_id = $user->id;
            // Alamat pertama tidak punya "alamat lain" untuk diturunkan statusnya.
            $address->is_default = $isDefault && $addressCount === 0;
            $address->save();

            if ($isDefault && ! $address->is_default) {
                $address->setAsDefault();
            }

            session()->flash('status', 'address-created');
        }

        $this->selectedAddressId = $address->id;
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

        $addresses = $user
            ? $user->addresses()->orderByDesc('is_default')->latest('id')->get()
            : collect();

        $defaultAddress = $addresses->firstWhere('is_default', true) ?? $addresses->first();

        if ($this->selectedAddressId && ! $addresses->contains('id', $this->selectedAddressId)) {
            $this->selectedAddressId = null;
        }

        if (! $this->selectedAddressId && $defaultAddress) {
            $this->selectedAddressId = $defaultAddress->id;
        }

        $selectedAddress = $addresses->firstWhere('id', $this->selectedAddressId) ?? $defaultAddress;

        $otherAddresses = $addresses
            ->filter(fn ($addr) => $selectedAddress && $addr->id !== $selectedAddress->id)
            ->values();

        return [
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
            'selectedAddress' => $selectedAddress,
            'otherAddresses' => $otherAddresses,
            'canAddMore' => $addresses->count() < self::MAX_ADDRESSES,
            'maxAddresses' => self::MAX_ADDRESSES,
        ];
    }

    protected function rules(): array
    {
        return [
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
        ];
    }

    protected function messages(): array
    {
        return [
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
        ];
    }

    private function limitMessage(): string
    {
        return 'Maksimal '.self::MAX_ADDRESSES.' alamat per akun. Hapus salah satu alamat untuk menambah yang baru.';
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

        @if (! $isCreating && ! $editingId)
            <div class="flex items-center gap-2 flex-wrap self-start sm:self-auto">
                @if ($canAddMore)
                    <button
                        type="button"
                        wire:click="startCreating"
                        data-preload-leaflet
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-60 cursor-wait"
                        wire:target="startCreating"
                        class="btn-tendaku-primary inline-flex items-center justify-center gap-2 py-2 px-4 rounded-xl text-xs sm:text-sm font-bold shadow-sm transition-all self-start sm:self-auto"
                    >
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>{{ __('Tambah Alamat') }}</span>
                    </button>
                @else
                    <span class="text-xs font-semibold text-darkbrown-500">
                        Batas {{ $maxAddresses }} alamat tercapai
                    </span>
                @endif
            </div>
        @endif
    </header>

    @error('limit')
        <div class="mt-4 p-4 rounded-xl bg-red-50 border border-red-200 text-xs sm:text-sm font-semibold text-red-800">
            {{ $message }}
        </div>
    @enderror

    @include('livewire.profile.partials.address-flash')

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

            {{-- Label Alamat --}}
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

            {{-- Detail Wilayah --}}
            <div class="space-y-3">
                <h4 class="text-xs font-extrabold text-darkbrown-800">
                    Detail Wilayah
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Provinsi --}}
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

                    {{-- Kota / Kabupaten --}}
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

                    {{-- Kecamatan --}}
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

                    {{-- Kelurahan / Desa --}}
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

                    {{-- Kode Pos --}}
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
                </div>
            </div>

            {{-- Detail Alamat --}}
            <div class="space-y-3">
                <h4 class="text-xs font-extrabold text-darkbrown-800">
                    Detail Alamat
                </h4>

                {{-- Alamat Lengkap --}}
                <div>
                    <label for="addr_full" class="block text-xs font-bold uppercase text-darkbrown-700 mb-1 tracking-wider">
                        Alamat Lengkap (Nama Jalan, No. Rumah, RT/RW) <span class="text-red-500">*</span>
                    </label>

                    <textarea
                        wire:model="full_address"
                        data-address-field="full_address"
                        id="addr_full"
                        rows="3"
                        placeholder="Tuliskan nama jalan, nomor rumah, blok, RT/RW, dan detail alamat lainnya..."
                        class="input-tendaku w-full py-2 px-3 rounded-xl text-xs font-medium border-wheat-300"
                        required
                    ></textarea>

                    <x-input-error class="mt-1" :messages="$errors->get('full_address')" />
                </div>

                {{-- Catatan Lokasi --}}
                <div>
                    <label for="addr_notes" class="block text-xs font-bold uppercase text-darkbrown-700 mb-1 tracking-wider">
                        Catatan Lokasi <span class="text-darkbrown-400 font-normal">(Opsional)</span>
                    </label>

                    <input
                        wire:model="notes"
                        id="addr_notes"
                        type="text"
                        placeholder="Contoh: Pagar hitam, dekat pos satpam, atau samping minimarket"
                        class="input-tendaku w-full py-2 px-3 rounded-xl text-xs font-medium border-wheat-300"
                    />

                    <x-input-error class="mt-1" :messages="$errors->get('notes')" />
                </div>
            </div>

            {{-- Pemilih Titik Koordinat Peta --}}
            <div class="pt-1" wire:key="customer-address-picker-{{ $editingId ?? 'create' }}">
                @if ($editingId && ($latitude === null || $longitude === null || $latitude === '' || $longitude === ''))
                    <div class="mb-2.5 px-3 py-2 rounded-lg border border-wheat-300 bg-white text-xs text-darkbrown-700 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-goldenrod-500 shrink-0" aria-hidden="true"></span>
                        <span>
                            Pin lokasi belum diatur untuk alamat ini. Klik peta di bawah untuk menambahkan titik lokasi pengantaran.
                        </span>
                    </div>
                @endif

                <x-location-picker
                    id="customer-address-picker-{{ $editingId ?? 'create' }}"
                    :latitude="$latitude"
                    :longitude="$longitude"
                    wire-latitude-model="latitude"
                    wire-longitude-model="longitude"
                    label="Titik Lokasi Pengantaran"
                    help-text="Pilih titik pada peta untuk mengisi alamat secara otomatis. Semua kolom alamat tetap dapat Anda ubah."
                />
            </div>

            {{-- Jadikan Alamat Utama --}}
            <div class="flex items-center gap-2 pt-1">
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

            {{-- Tombol Aksi Form --}}
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

    @include('livewire.profile.partials.address-list')
</section>
