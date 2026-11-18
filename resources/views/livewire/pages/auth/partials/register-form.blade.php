<form wire:submit="register" class="flex flex-col gap-5 text-white lg:gap-4"
      novalidate>
    @csrf

    {{-- Section 1: Data Toko / Rental --}}
    <div
        x-show="type === 'vendor'"
        x-cloak
        class="grid grid-cols-1 gap-x-4 gap-y-5 xl:grid-cols-2 lg:gap-y-4"
    >
        <div class="flex items-center gap-2 border-b border-white/20 pb-1 xl:col-span-2">
            <i data-lucide="building-2" class="h-4 w-4 text-avocado-300"></i>

            <h3 class="text-xs font-bold uppercase tracking-wider text-white">
                Informasi Toko
            </h3>
        </div>

        <x-auth.field
            name="form.store_name"
            label="Nama Toko"
            wire:model="form.store_name"
            placeholder="Contoh: Tendaku Outdoor"
            required
        />

        <x-auth.field
            name="form.city"
            label="Kota"
            wire:model="form.city"
            placeholder="Contoh: Malang"
            required
        />

        <div class="xl:col-span-2">
            <x-auth.field
                name="form.store_address"
                label="Alamat Toko"
                type="textarea"
                wire:model="form.store_address"
                rows="2"
                placeholder="Alamat lengkap toko"
                required
            />
        </div>

        <div class="xl:col-span-2">
            <x-auth.field
                name="form.description"
                label="Deskripsi Toko (Opsional)"
                type="textarea"
                wire:model="form.description"
                rows="2"
                placeholder="Contoh: tenda, matras, kompor"
            />
        </div>
    </div>

    {{-- Section 2: Data Akun (Customer / Pemilik Toko) --}}
    <div class="grid grid-cols-1 gap-x-4 gap-y-5 xl:grid-cols-2 lg:gap-y-4">
        <div
            x-show="type === 'vendor'"
            x-cloak
            class="flex items-center gap-2 border-b border-white/20 pb-1 xl:col-span-2"
        >
            <i data-lucide="user-round" class="h-4 w-4 text-avocado-300"></i>
            <h3 class="text-xs font-bold uppercase tracking-wider text-white">
                Informasi Owner
            </h3>
        </div>

        <x-auth.field
            name="form.name"
            label="Nama lengkap"
            wire:model="form.name"
            x-bind:placeholder="type === 'vendor' ? 'Nama Lengkap' : 'Nama Lengkap'"
            placeholder="Nama Lengkap"
            autocomplete="name"
            required
            autofocus
        />

        <x-auth.field
            name="form.email"
            label="Email"
            type="email"
            wire:model="form.email"
            x-bind:placeholder="type === 'vendor' ? 'owner@gmail.com' : 'nama@gmail.com'"
            placeholder="nama@gmail.com"
            autocomplete="username"
            required
        />

        <x-auth.field
            name="form.phone"
            label="Nomor HP"
            type="tel"
            inputmode="tel"
            wire:model="form.phone"
            placeholder="081234567890"
            maxlength="13"
            autocomplete="tel"
            required
        />

        <x-auth.field
            name="form.nik"
            label="NIK"
            wire:model="form.nik"
            placeholder="Masukkan NIK 16 digit"
            inputmode="numeric"
            maxlength="16"
            required
        />

        {{-- Alamat pribadi (khusus customer) --}}
        <div x-show="type === 'customer'" class="xl:col-span-2">
            <x-auth.field
                name="form.address"
                label="Alamat"
                type="textarea"
                wire:model="form.address"
                rows="2"
                placeholder="Alamat lengkap (opsional)"
            />
        </div>

        <x-auth.field
            name="form.password"
            label="Password"
            type="password"
            wire:model="form.password"
            placeholder="Password"
            autocomplete="new-password"
            required
        />

        <x-auth.field
            name="form.password_confirmation"
            label="Ulangi password"
            type="password"
            wire:model="form.password_confirmation"
            placeholder="Ulangi password"
            autocomplete="new-password"
            required
        />
    </div>

    <x-auth.button x-show="type === 'customer'" class="w-full">
        Buat akun
    </x-auth.button>

    <x-auth.button x-show="type === 'vendor'" x-cloak class="w-full">
        Daftar Sebagai Mitra Vendor
    </x-auth.button>
</form>
