@if (auth()->user()?->isCustomer())
    @php
        $profileUser = auth()->user();

        // Ringkasan sewa untuk kartu samping (hanya baca, tanpa mengubah data).
        $rentalCounts = $profileUser->rentalsAsCustomer()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $activeRentalCount = collect(['pending', 'confirmed', 'picked_up', 'returned'])
            ->sum(fn ($status) => (int) ($rentalCounts[$status] ?? 0));
        $completedRentalCount = (int) ($rentalCounts['completed'] ?? 0);
        $totalRentalCount = (int) $rentalCounts->sum();

        // Sewa yang melewati jadwal ambil / kembali, untuk pesan status di kartu samping.
        $overdueRentalCount = $profileUser->rentalsAsCustomer()
            ->whereIn('status', ['confirmed', 'picked_up'])
            ->get(['id', 'status', 'pickup_date', 'pickup_time', 'return_date', 'return_time'])
            ->filter(fn ($rental) => $rental->isPickupOverdue()
                || ($rental->status === 'picked_up' && $rental->returnDateTime()->isPast()))
            ->count();

        [$summaryTone, $summaryIcon, $summaryText] = match (true) {
            $overdueRentalCount > 0 => ['warn', 'triangle-alert', "Ada {$overdueRentalCount} sewa yang melewati jadwal. Cek detail sewa Anda."],
            $activeRentalCount === 0 => ['neutral', 'tent', 'Belum ada sewa aktif saat ini.'],
            default => ['ok', 'check', 'Semua sewa Anda dalam keadaan baik.'],
        };
    @endphp

    @push('styles')
        @vite('resources/css/pages/customer/profile-rentals.css')
    @endpush

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

        <div class="pf-page w-full space-y-6 py-2">
            {{-- ===================== Header profil ===================== --}}
            <section class="pf-card pf-hero">
                <div class="pf-hero-inner">
                    <div class="pf-avatar" x-data="{ failed: false }">
                        @if ($profileUser->avatar_url)
                            <img src="{{ $profileUser->avatar_url }}"
                                 alt="Foto profil {{ $profileUser->name }}"
                                 referrerpolicy="no-referrer"
                                 x-show="! failed"
                                 x-on:error="failed = true"
                                 class="w-full h-full rounded-full object-cover border-2 border-white shadow-sm">
                        @endif
                        <span @if ($profileUser->avatar_url) x-show="failed" x-cloak @endif
                              class="w-full h-full rounded-full bg-gradient-to-br from-sunglow-300 to-goldenrod-500 text-darkbrown-900 font-extrabold text-3xl sm:text-4xl font-heading flex items-center justify-center shadow-sm">
                            {{ $profileUser->initials }}
                        </span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <h2 class="text-xl sm:text-2xl font-extrabold text-darkbrown-900 font-heading truncate">
                            {{ $profileUser->name }}
                        </h2>

                        <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs sm:text-sm text-darkbrown-600">
                            <span class="inline-flex items-center gap-1.5"
                                  x-data="{ time: '{{ now()->format('H:i') }}' }"
                                  x-init="const tick = () => { time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false }).replace('.', ':'); }; tick(); setInterval(tick, 30000);">
                                <i data-lucide="clock" class="w-4 h-4 text-darkbrown-400"></i>
                                <span><span x-text="time">{{ now()->format('H:i') }}</span> WIB</span>
                            </span>

                            <span class="inline-flex items-center gap-1.5 min-w-0">
                                <i data-lucide="mail" class="w-4 h-4 text-darkbrown-400 shrink-0"></i>
                                <span class="truncate">{{ $profileUser->email }}</span>
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <div class="pf-layout">
                {{-- ===================== Kolom utama ===================== --}}
                <div class="pf-col">
                    <div class="pf-card pf-card--pad">
                        <livewire:profile.my-rentals />
                    </div>

                    {{-- Biodata Profil + Alamat Pengantaran (berdampingan di layar lebar) --}}
                    <div class="pf-card pf-card--pad pf-duo">
                        <livewire:profile.update-profile-information-form variant="compact" />
                        <livewire:profile.manage-addresses />
                    </div>
                </div>

                {{-- ===================== Kolom samping ===================== --}}
                <aside class="pf-col">
                    <div class="pf-card pf-card--pad">
                        <header class="pf-head">
                            <span class="pf-icon"><i data-lucide="chart-column"></i></span>
                            <div class="min-w-0">
                                <h2 class="pf-title font-heading">Ringkasan Sewa</h2>
                                <p class="pf-sub">Status sewa Anda saat ini</p>
                            </div>
                        </header>

                        <ul class="pf-stats">
                            <li class="pf-stat" data-current="true">
                                <p class="pf-stat-num font-heading">{{ $activeRentalCount }}</p>
                                <p class="pf-stat-label">Aktif</p>
                            </li>
                            <li class="pf-stat">
                                <p class="pf-stat-num font-heading">{{ $completedRentalCount }}</p>
                                <p class="pf-stat-label">Selesai</p>
                            </li>
                            <li class="pf-stat">
                                <p class="pf-stat-num font-heading">{{ $totalRentalCount }}</p>
                                <p class="pf-stat-label">Total</p>
                            </li>
                        </ul>

                        <div class="pf-notice" data-tone="{{ $summaryTone }}">
                            <span class="pf-notice-icon"><i data-lucide="{{ $summaryIcon }}"></i></span>
                            <p>{{ $summaryText }}</p>
                        </div>
                    </div>

                    <div class="pf-card pf-card--pad">
                        <header class="pf-head">
                            <span class="pf-icon"><i data-lucide="shield-check"></i></span>
                            <div class="min-w-0">
                                <h2 class="pf-title font-heading">Keamanan Akun</h2>
                                <p class="pf-sub">Jaga keamanan akun Anda.</p>
                            </div>
                        </header>

                        <div class="pf-sec-list">
                            <livewire:profile.update-password-form variant="row" />
                            <livewire:profile.delete-user-form variant="row" />
                        </div>

                        <p class="pf-note">
                            <i data-lucide="info"></i>
                            <span>Dengan menghapus akun, semua data Anda akan dihapus secara permanen.</span>
                        </p>
                    </div>
                </aside>
            </div>
        </div>
    </x-layouts.customer>
@elseif (auth()->user()?->isVendorStaff())
    <x-layouts.vendor title="Tendaku - Profil Toko">
        <x-slot name="header">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                        {{ auth()->user()->isVendorOwner() ? 'Profil Toko' : 'Profil' }}
                    </h1>
                    <p class="text-xs text-darkbrown-500 mt-0.5">
                        Kelola informasi akun, kata sandi, dan keamanan portal toko Anda.
                    </p>
                </div>
            </div>
        </x-slot>

        <div class="max-w-4xl space-y-6">
            <div class="card-tendaku p-6 sm:p-8 bg-white rounded-2xl shadow-tendaku border border-wheat-200">
                <livewire:profile.update-profile-information-form />
            </div>

            <div class="card-tendaku p-6 sm:p-8 bg-white rounded-2xl shadow-tendaku border border-wheat-200">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <div class="card-tendaku p-6 sm:p-8 bg-white rounded-2xl shadow-tendaku border border-red-200">
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>
    </x-layouts.vendor>
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
