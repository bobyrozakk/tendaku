@if (auth()->user()?->isCustomer())
    @php
        $profileUser = auth()->user();

        // Ringkasan sewa untuk header & kartu samping (hanya baca, tanpa mengubah data).
        $rentalCounts = $profileUser->rentalsAsCustomer()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $activeRentalCount = collect(['pending', 'confirmed', 'picked_up', 'returned'])
            ->sum(fn ($status) => (int) ($rentalCounts[$status] ?? 0));
        $completedRentalCount = (int) ($rentalCounts['completed'] ?? 0);
        $totalRentalCount = (int) $rentalCounts->sum();

        $primaryAddress = $profileUser->defaultAddress();
    @endphp

    <x-layouts.customer>
        <x-slot name="header">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="font-extrabold text-2xl md:text-3xl text-darkbrown-900 tracking-tight font-heading flex items-center gap-2">
                        <i data-lucide="user" class="w-7 h-7 text-darkbrown-800"></i>
                        <span>Akun Saya</span>
                    </h1>
                    <p class="text-sm text-darkbrown-600 mt-1">
                        Kelola biodata, alamat, dan keamanan akun Tendaku Anda, serta pantau status sewa Anda.
                    </p>
                </div>
            </div>
        </x-slot>

        <div class="max-w-6xl mx-auto space-y-6 py-2">
            {{-- ============================================================
                 Kepala profil: avatar, nama, waktu setempat, email, aksi cepat
                 ============================================================ --}}
            <div class="card-tendaku p-6 sm:p-8 bg-white rounded-2xl shadow-tendaku border border-wheat-200">
                <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                    <div class="relative w-20 h-20 sm:w-24 sm:h-24 shrink-0" x-data="{ failed: false }">
                        @if ($profileUser->avatar_url)
                            <img src="{{ $profileUser->avatar_url }}"
                                 alt="Foto profil {{ $profileUser->name }}"
                                 referrerpolicy="no-referrer"
                                 x-show="! failed"
                                 x-on:error="failed = true"
                                 class="w-full h-full rounded-full object-cover border border-wheat-200 shadow-sm">
                        @endif
                        <span @if ($profileUser->avatar_url) x-show="failed" x-cloak @endif
                              class="w-full h-full rounded-full bg-gradient-to-br from-sunglow-300 to-goldenrod-500 text-darkbrown-900 font-extrabold text-3xl sm:text-4xl font-heading flex items-center justify-center shadow-sm">
                            {{ $profileUser->initials }}
                        </span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-darkbrown-900 font-heading truncate">
                            {{ $profileUser->name }}
                        </h2>

                        <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs sm:text-sm text-darkbrown-600">
                            <span class="inline-flex items-center gap-1.5"
                                  x-data="{ time: '{{ now()->format('H:i') }}' }"
                                  x-init="const tick = () => { time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false }).replace('.', ':'); }; tick(); setInterval(tick, 30000);">
                                <i data-lucide="clock" class="w-4 h-4 text-darkbrown-400"></i>
                                <span><span x-text="time">{{ now()->format('H:i') }}</span> waktu setempat</span>
                            </span>

                            <span class="inline-flex items-center gap-1.5 min-w-0">
                                <i data-lucide="mail" class="w-4 h-4 text-darkbrown-400 shrink-0"></i>
                                <span class="truncate">{{ $profileUser->email }}</span>
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 self-start sm:self-center">
                        <a href="{{ route('customer.booking.index') }}"
                           class="btn-tendaku-secondary inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold hover:bg-wheat-100 transition-all">
                            <i data-lucide="history" class="w-4 h-4"></i>
                            <span>Riwayat Sewa</span>
                        </a>
                        <button type="button"
                                x-data
                                x-on:click="$dispatch('edit-biodata')"
                                class="btn-tendaku-primary inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold shadow-md hover:shadow-lg transition-all">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                            <span>Edit Biodata</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                {{-- ===================== Kolom utama ===================== --}}
                <div class="lg:col-span-2 space-y-6 min-w-0">
                    <div class="card-tendaku p-6 sm:p-8 bg-white rounded-2xl shadow-tendaku border border-wheat-200">
                        <livewire:profile.my-rentals />
                    </div>

                    {{-- ============================================================
                         Biodata Profil
                         ============================================================ --}}
                    <div class="card-tendaku p-6 sm:p-8 bg-white rounded-2xl shadow-tendaku border border-wheat-200 divide-y divide-wheat-200 space-y-8">
                        <div>
                            <livewire:profile.update-profile-information-form />
                        </div>

                        <div class="pt-8">
                            <livewire:profile.manage-addresses />
                        </div>
                    </div>
                </div>

                {{-- ===================== Kolom samping ===================== --}}
                <aside class="space-y-6 min-w-0">
                    <div class="card-tendaku p-5 sm:p-6 bg-white rounded-2xl shadow-tendaku border border-wheat-200">
                        <h2 class="text-base font-extrabold text-darkbrown-900 font-heading">Tentang Saya</h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-darkbrown-500">Nomor WhatsApp</dt>
                                <dd class="font-semibold text-darkbrown-900 text-right">{{ $profileUser->phone ?: '-' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-darkbrown-500">Status email</dt>
                                <dd class="font-semibold text-right {{ $profileUser->hasVerifiedEmail() ? 'text-green-700' : 'text-goldenrod-700' }}">
                                    {{ $profileUser->hasVerifiedEmail() ? 'Email Terverifikasi' : 'Belum terverifikasi' }}
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-darkbrown-500">Bergabung sejak</dt>
                                <dd class="font-semibold text-darkbrown-900 text-right">
                                    {{ $profileUser->created_at?->copy()->locale('id')->translatedFormat('F Y') ?? '-' }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="card-tendaku p-5 sm:p-6 bg-white rounded-2xl shadow-tendaku border border-wheat-200">
                        <h2 class="text-base font-extrabold text-darkbrown-900 font-heading">Ringkasan Sewa</h2>
                        <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                            <div class="rounded-xl bg-avocado-50 border border-avocado-200 py-3">
                                <p class="text-xl font-extrabold text-avocado-800 font-heading">{{ $activeRentalCount }}</p>
                                <p class="text-[11px] font-semibold text-darkbrown-600">Aktif</p>
                            </div>
                            <div class="rounded-xl bg-wheat-50 border border-wheat-200 py-3">
                                <p class="text-xl font-extrabold text-darkbrown-900 font-heading">{{ $completedRentalCount }}</p>
                                <p class="text-[11px] font-semibold text-darkbrown-600">Selesai</p>
                            </div>
                            <div class="rounded-xl bg-wheat-50 border border-wheat-200 py-3">
                                <p class="text-xl font-extrabold text-darkbrown-900 font-heading">{{ $totalRentalCount }}</p>
                                <p class="text-[11px] font-semibold text-darkbrown-600">Total</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-tendaku p-5 sm:p-6 bg-white rounded-2xl shadow-tendaku border border-wheat-200">
                        <h2 class="text-base font-extrabold text-darkbrown-900 font-heading">Alamat Utama</h2>
                        @if ($primaryAddress)
                            <div class="mt-3 space-y-1">
                                <p class="text-sm font-bold text-darkbrown-900">
                                    {{ $primaryAddress->label }}
                                    <span class="font-medium text-darkbrown-500">&middot; {{ $primaryAddress->recipient_name }}</span>
                                </p>
                                <p class="text-xs text-darkbrown-700 leading-relaxed">{{ $primaryAddress->formatted_address }}</p>
                            </div>
                        @else
                            <p class="mt-3 text-xs text-darkbrown-500">Belum ada alamat tersimpan.</p>
                        @endif
                        <a href="#alamat" class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-avocado-700 hover:text-avocado-800 underline">
                            Kelola alamat
                        </a>
                    </div>

                    <div class="card-tendaku p-5 sm:p-6 bg-white rounded-2xl shadow-tendaku border border-wheat-200">
                        <livewire:profile.update-password-form />
                    </div>

                    <div class="card-tendaku p-5 sm:p-6 bg-white rounded-2xl shadow-tendaku border border-red-200">
                        <livewire:profile.delete-user-form />
                    </div>
                </aside>
            </div>
        </div>
    </x-layouts.customer>
@else
    <x-app-layout>
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Profil') }}
            </h2>
        </x-slot>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-2xl">
                        <livewire:profile.update-profile-information-form />
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        <livewire:profile.update-password-form />
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        <livewire:profile.delete-user-form />
                    </div>
                </div>
            </div>
        </div>
    </x-app-layout>
@endif
