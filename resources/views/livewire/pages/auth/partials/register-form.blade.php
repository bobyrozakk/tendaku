<form wire:submit="register" class="space-y-5" novalidate>
    @csrf

    @if ($form->account_type === 'vendor')
        {{-- Section 1: Data Toko / Rental --}}
        <div class="space-y-4 rounded-2xl bg-tendaku-wheat/30 p-4 border border-tendaku-brown/10">
            <div class="flex items-center gap-2 text-tendaku-brown pb-1 border-b border-tendaku-brown/10">
                <svg class="w-4 h-4 text-tendaku-moss" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
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
                autofocus/>
            <x-auth.field
                name="form.city"
                label="Kota / Kabupaten"
                wire:model="form.city"
                placeholder="Contoh: Bandung, Jakarta Selatan, Malang"
                required/>
            <x-auth.field
                name="form.store_address"
                label="Alamat Toko / Titik Pengambilan Alat"
                wire:model="form.store_address"
                placeholder="Alamat lengkap tempat pengambilan alat camping"
                required/>
            <x-auth.field
                name="form.description"
                label="Deskripsi Toko (Opsional)"
                wire:model="form.description"
                placeholder="Spesialis sewa tenda dome, matras, kompor portabel, dll."/>
        </div>

        {{-- Section 2: Data Pemilik Toko (Vendor Owner) --}}
        <div class="space-y-4 pt-1">
            <div class="flex items-center gap-2 text-tendaku-brown px-1 pb-1">
                <svg class="w-4 h-4 text-tendaku-moss" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <h3 class="text-xs font-bold uppercase tracking-wider text-tendaku-brown/80">
                    Informasi Akun Pemilik (Owner)
                </h3>
            </div>

            <x-auth.field
                name="form.name"
                label="Nama Lengkap Pemilik"
                wire:model="form.name"
                placeholder="Nama Lengkap Penanggung Jawab"
                autocomplete="name"
                required/>
            <x-auth.field
                name="form.email"
                label="Email"
                type="email"
                wire:model="form.email"
                placeholder="owner@gmail.com"
                autocomplete="username"
                required/>
            <x-auth.field
                name="form.phone"
                label="Nomor HP"
                type="tel"
                inputmode="tel"
                wire:model="form.phone"
                placeholder="081234567890"
                maxlength="13"
                autocomplete="tel"
                required/>
            <x-auth.field
                name="form.nik"
                label="NIK"
                wire:model="form.nik"
                placeholder="Masukkan NIK 16 digit"
                inputmode="numeric"
                maxlength="16"
                required/>
            <x-auth.field
                name="form.password"
                label="Password"
                type="password"
                wire:model="form.password"
                placeholder="Password"
                autocomplete="new-password"
                required/>
            <x-auth.field
                name="form.password_confirmation"
                label="Ulangi Password"
                type="password"
                wire:model="form.password_confirmation"
                placeholder="Ulangi password"
                autocomplete="new-password"
                required/>
        </div>

        <x-auth.button class="w-full">
            Daftar Sebagai Mitra Vendor
        </x-auth.button>

    @else
        {{-- Form Penyewa (Customer) --}}
        <x-auth.field
            name="form.name"
            label="Nama lengkap"
            wire:model="form.name"
            placeholder="Nama Lengkap"
            autocomplete="name"
            required
            autofocus/>
        <x-auth.field
            name="form.email"
            label="Email"
            type="email"
            wire:model="form.email"
            placeholder="nama@gmail.com"
            autocomplete="username"
            required/>
        <x-auth.field
            name="form.phone"
            label="Nomor telepon"
            type="tel"
            inputmode="tel"
            wire:model="form.phone"
            placeholder="081234567890"
            maxlength="13"
            autocomplete="tel"
            required/>
        <x-auth.field
            name="form.nik"
            label="NIK (Nomor Induk Kependudukan)"
            wire:model="form.nik"
            placeholder="Masukkan NIK 16 digit"
            inputmode="numeric"
            maxlength="16"
            required/>
        <x-auth.field
            name="form.address"
            label="Alamat"
            wire:model="form.address"
            placeholder="Alamat lengkap (opsional)"/>
        <x-auth.field
            name="form.password"
            label="Password"
            type="password"
            wire:model="form.password"
            placeholder="Password"
            autocomplete="new-password"
            required/>
        <x-auth.field
            name="form.password_confirmation"
            label="Ulangi password"
            type="password"
            wire:model="form.password_confirmation"
            placeholder="Ulangi password"
            autocomplete="new-password"
            required/>
        <x-auth.button class="w-full">
            Buat akun
        </x-auth.button>
    @endif
</form>
