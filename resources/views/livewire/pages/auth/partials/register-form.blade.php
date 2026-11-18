<form wire:submit="register" class="space-y-5 lg:space-y-4" novalidate>
    @csrf

    @if ($form->account_type === 'vendor')
        {{-- Section 1: Data Toko / Rental --}}
        <div class="grid grid-cols-1 gap-x-4 gap-y-5 xl:grid-cols-2 lg:gap-y-4">
            <div class="flex items-center gap-2 text-tendaku-brown pb-1 border-b border-tendaku-brown/10 xl:col-span-2">
                <i data-lucide="building-2" class="w-4 h-4 text-tendaku-moss"></i>
                <h3 class="text-xs font-bold uppercase tracking-wider text-tendaku-brown/80">
                    Informasi Toko / Usaha Rental
                </h3>
            </div>

            <x-auth.field
                name="form.store_name"
                label="Nama Toko / Rental"
                wire:model="form.store_name"
                placeholder="Contoh: Tendaku Outdoor Rental"
                required
                autofocus
            />
            <x-auth.field
                name="form.city"
                label="Kota / Kabupaten"
                wire:model="form.city"
                placeholder="Contoh: Malang"
                required
            />
            <x-auth.field
                name="form.store_address"
                label="Alamat Toko / Titik Pengambilan Alat"
                wire:model="form.store_address"
                placeholder="Alamat lengkap toko"
                required
            />
            <x-auth.field
                name="form.description"
                label="Deskripsi Toko (Opsional)"
                wire:model="form.description"
                placeholder="Contoh: tenda, matras, kompor"
            />
        </div>

        {{-- Section 2: Data Pemilik Toko (Vendor Owner) --}}
        <div class="grid grid-cols-1 gap-x-4 gap-y-5 xl:grid-cols-2 lg:gap-y-4">
            <div class="flex items-center gap-2 text-tendaku-brown pb-1 border-b border-tendaku-brown/10 xl:col-span-2">
                <i data-lucide="user-round" class="w-4 h-4 text-tendaku-moss"></i>
                <h3 class="text-xs font-bold uppercase tracking-wider text-tendaku-brown/80">
                    Informasi Akun Pemilik (Owner)
                </h3>
            </div>

            <x-auth.field
                name="form.name"
                label="Nama Lengkap Pemilik"
                wire:model="form.name"
                placeholder="Nama penanggung jawab"
                autocomplete="name"
                required
            />
            <x-auth.field
                name="form.email"
                label="Email"
                type="email"
                wire:model="form.email"
                placeholder="owner@gmail.com"
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
                label="Ulangi Password"
                type="password"
                wire:model="form.password_confirmation"
                placeholder="Ulangi password"
                autocomplete="new-password"
                required
            />
        </div>

        <x-auth.button class="w-full">
            Daftar Sebagai Mitra Vendor
        </x-auth.button>

    @else
        {{-- Form Penyewa (Customer) --}}
        <div class="grid grid-cols-1 gap-x-4 gap-y-5 xl:grid-cols-2 lg:gap-y-4">
            <x-auth.field
                name="form.name"
                label="Nama lengkap"
                wire:model="form.name"
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
                placeholder="nama@gmail.com"
                autocomplete="username"
                required
            />
            <x-auth.field
                name="form.phone"
                label="Nomor telepon"
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
                label="NIK (Nomor Induk Kependudukan)"
                wire:model="form.nik"
                placeholder="Masukkan NIK 16 digit"
                inputmode="numeric"
                maxlength="16"
                required
            />
            <div class="xl:col-span-2">
                <x-auth.field
                    name="form.address"
                    label="Alamat"
                    wire:model="form.address"
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

        <x-auth.button class="w-full">
            Buat akun
        </x-auth.button>
    @endif
</form>
