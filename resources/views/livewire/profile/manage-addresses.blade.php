<?php

use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public bool $isCreating = false;
    public ?int $editingId = null;

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
        $this->is_default = (bool) $address->is_default;
        $this->isCreating = false;
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
        $this->is_default = false;
    }

    public function save(): void
    {
        $user = Auth::user();

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
            'is_default' => ['boolean'],
        ], [
            'label.required' => 'Label alamat (cth: Rumah, Kantor, Kos) wajib diisi.',
            'recipient_name.required' => 'Nama penerima wajib diisi.',
            'phone_number.required' => 'Nomor WhatsApp / telepon wajib diisi.',
            'phone_number.regex' => 'Nomor telepon harus diawali 08 dan terdiri dari 10 hingga 13 angka.',
            'full_address.required' => 'Alamat lengkap wajib diisi.',
            'province.required' => 'Provinsi wajib diisi.',
            'city.required' => 'Kota atau kabupaten wajib diisi.',
            'district.required' => 'Kecamatan wajib diisi.',
        ]);

        $isDefault = $validated['is_default'] ?? false;
        unset($validated['is_default']);

        // Jika alamat pertama, otomatis set default
        if (! $user->addresses()->exists()) {
            $isDefault = true;
        }

        if ($this->editingId) {
            $address = $user->addresses()->findOrFail($this->editingId);
            $address->update($validated);
            if ($isDefault) {
                $address->setAsDefault();
            }
            session()->flash('status', 'address-updated');
        } else {
            $address = new CustomerAddress($validated);
            $address->user_id = $user->id;
            $address->is_default = false;
            $address->save();

            if ($isDefault) {
                $address->setAsDefault();
            }
            session()->flash('status', 'address-created');
        }

        $this->resetForm();
    }

    public function setAsDefault(int $id): void
    {
        $address = Auth::user()->addresses()->findOrFail($id);
        $address->setAsDefault();
        session()->flash('status', 'address-default-set');
    }

    public function deleteAddress(int $id): void
    {
        $address = Auth::user()->addresses()->findOrFail($id);
        $wasDefault = $address->is_default;
        $userId = $address->user_id;

        $address->delete();

        if ($wasDefault) {
            $next = CustomerAddress::where('user_id', $userId)->latest('id')->first();
            if ($next) {
                $next->setAsDefault();
            }
        }

        session()->flash('status', 'address-deleted');
    }

    public function with(): array
    {
        return [
            'addresses' => Auth::user()->addresses()->orderByDesc('is_default')->latest('id')->get(),
        ];
    }
}; ?>

<section>
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-wheat-200 pb-4">
        <div>
            <h2 class="text-lg font-extrabold text-darkbrown-900 font-heading flex items-center gap-2">
                <i data-lucide="map-pin" class="w-5 h-5 text-avocado-600"></i>
                <span>{{ __('Daftar Alamat Pengantaran') }}</span>
            </h2>
            <p class="mt-1 text-xs sm:text-sm text-darkbrown-600">
                {{ __('Kelola banyak alamat domisili atau titik serah terima pengantaran kurir vendor.') }}
            </p>
        </div>

        @if(! $isCreating && ! $editingId)
            <button 
                type="button" 
                wire:click="startCreating"
                class="btn-tendaku-primary inline-flex items-center justify-center gap-2 py-2 px-4 rounded-xl text-xs sm:text-sm font-bold shadow-md hover:shadow-lg transition-all self-start sm:self-auto"
            >
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>{{ __('Tambah Alamat Baru') }}</span>
            </button>
        @endif
    </header>

    {{-- Alert Notifikasi Status --}}
    @if (session('status') === 'address-created')
        <div class="mt-4 p-4 rounded-xl bg-avocado-50 border border-avocado-200 text-xs sm:text-sm font-semibold text-avocado-900 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 text-avocado-600 shrink-0"></i>
            <span>Alamat baru berhasil ditambahkan!</span>
        </div>
    @elseif (session('status') === 'address-updated')
        <div class="mt-4 p-4 rounded-xl bg-avocado-50 border border-avocado-200 text-xs sm:text-sm font-semibold text-avocado-900 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 text-avocado-600 shrink-0"></i>
            <span>Alamat berhasil diperbarui!</span>
        </div>
    @elseif (session('status') === 'address-deleted')
        <div class="mt-4 p-4 rounded-xl bg-wheat-100 border border-wheat-300 text-xs sm:text-sm font-semibold text-darkbrown-800 flex items-center gap-2">
            <i data-lucide="trash-2" class="w-4 h-4 text-darkbrown-600 shrink-0"></i>
            <span>Alamat telah dihapus.</span>
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
        <form wire:submit="save" class="mt-6 space-y-4 p-5 rounded-2xl bg-wheat-50 border border-wheat-200 shadow-sm">
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
                        Catatan Tambahan / Patokan Lokasi <span class="text-darkbrown-400 font-normal">(Opsional)</span>
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

                <!-- Jadikan Alamat Utama Checkbox -->
                <div class="sm:col-span-2 flex items-center gap-2 pt-1">
                    <input 
                        wire:model="is_default" 
                        id="addr_is_default" 
                        type="checkbox" 
                        class="rounded border-wheat-300 text-avocado-600 focus:ring-avocado-500" 
                    />
                    <label for="addr_is_default" class="text-xs font-semibold text-darkbrown-800 cursor-pointer">
                        Jadikan sebagai alamat utama (default saat checkout)
                    </label>
                </div>
            </div>

            <!-- Tombol Aksi Form -->
            <div class="flex items-center gap-3 pt-3 border-t border-wheat-200">
                <button 
                    type="submit" 
                    class="btn-tendaku-primary inline-flex items-center gap-2 py-2 px-5 rounded-xl text-xs sm:text-sm font-bold shadow-md hover:shadow-lg transition-all"
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
        </form>
    @endif

    {{-- ============================================================
         2. DAFTAR ALAMAT TERSIMPAN
         ============================================================ --}}
    <div class="mt-6 space-y-4">
        @forelse ($addresses as $addr)
            <div 
                wire:key="addr-{{ $addr->id }}"
                class="p-4 sm:p-5 rounded-2xl border transition-all {{ $addr->is_default ? 'bg-white border-avocado-400 shadow-tendaku ring-1 ring-avocado-400/30' : 'bg-white border-wheat-200 hover:border-wheat-300 shadow-sm' }}"
            >
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                    <div class="space-y-1.5 min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-wheat-100 text-darkbrown-800 border border-wheat-300">
                                {{ $addr->label }}
                            </span>

                            @if ($addr->is_default)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-avocado-100 text-avocado-800 border border-avocado-300 inline-flex items-center gap-1">
                                    <i data-lucide="check" class="w-3 h-3 text-avocado-700"></i>
                                    Alamat Utama
                                </span>
                            @endif

                            <span class="text-xs font-bold text-darkbrown-900">
                                {{ $addr->recipient_name }}
                            </span>
                            <span class="text-xs text-darkbrown-500 font-medium">
                                &middot; {{ $addr->phone_number }}
                            </span>
                        </div>

                        <p class="text-xs sm:text-sm text-darkbrown-800 leading-relaxed">
                            {{ $addr->formatted_address }}
                        </p>
                    </div>

                    <!-- Tombol Aksi per Alamat -->
                    <div class="flex items-center gap-2 shrink-0 pt-2 sm:pt-0 border-t sm:border-0 border-wheat-100">
                        @if (! $addr->is_default)
                            <button 
                                type="button" 
                                wire:click="setAsDefault({{ $addr->id }})"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-avocado-800 bg-avocado-50 border border-avocado-200 hover:bg-avocado-100 transition"
                            >
                                Jadikan Utama
                            </button>
                        @endif

                        <button 
                            type="button" 
                            wire:click="startEditing({{ $addr->id }})"
                            class="p-1.5 rounded-lg text-darkbrown-600 hover:text-darkbrown-900 hover:bg-wheat-100 transition"
                            title="Edit Alamat"
                        >
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </button>

                        <button 
                            type="button" 
                            wire:confirm="Yakin ingin menghapus alamat ini?"
                            wire:click="deleteAddress({{ $addr->id }})"
                            class="p-1.5 rounded-lg text-red-500 hover:text-red-700 hover:bg-red-50 transition"
                            title="Hapus Alamat"
                        >
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-8 px-4 rounded-2xl bg-wheat-50 border border-wheat-200 space-y-3">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-wheat-100 text-darkbrown-400 flex items-center justify-center">
                    <i data-lucide="map-pin-off" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-darkbrown-900">Belum ada alamat tersimpan</h3>
                    <p class="text-xs text-darkbrown-500 mt-0.5">Tambahkan alamat agar Anda dapat memilih opsi pengantaran (delivery) saat booking alat camping.</p>
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
        @endforelse
    </div>
</section>
