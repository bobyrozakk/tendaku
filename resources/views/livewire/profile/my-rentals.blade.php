<?php

use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    /** Tab yang tampil: 'aktif' atau 'riwayat'. */
    public string $tab = 'aktif';

    public function setTab(string $tab): void
    {
        $this->tab = $tab === 'riwayat' ? 'riwayat' : 'aktif';
    }

    /**
     * Jumlah langkah alur sewa yang sudah selesai (0-4).
     * Urutan: Pembayaran > Pengambilan > Penyewaan > Pengembalian.
     */
    public function completedSteps(Rental $rental): int
    {
        return match ($rental->status) {
            'pending' => $rental->hasPaidPayment() ? 1 : 0,
            'confirmed' => 1,
            'picked_up' => 2,
            'returned' => 3,
            'completed' => 4,
            default => 0,
        };
    }

    /**
     * Petunjuk jadwal singkat di sisi kanan baris: [teks, nada].
     * Nada: success | warn | danger | neutral.
     *
     * @return array{0: string, 1: string}
     */
    public function scheduleHint(Rental $rental): array
    {
        return match ($rental->status) {
            'pending' => $rental->hasPaidPayment()
                ? ['Menunggu validasi kasir', 'warn']
                : ($rental->expires_at
                    ? ['Bayar sebelum '.$rental->expires_at->format('H:i'), 'warn']
                    : ['Menunggu pembayaran', 'warn']),
            'confirmed' => $rental->isPickupOverdue()
                ? ['Lewat jadwal pengambilan', 'danger']
                : $this->dayHint('Ambil', $rental->pickup_date, $rental->pickupDateTime()),
            'picked_up' => $rental->returnDateTime()->isPast()
                ? ['Lewat jadwal pengembalian', 'danger']
                : $this->dayHint('Kembali', $rental->return_date, $rental->returnDateTime()),
            'returned' => ['Menunggu penyelesaian', 'neutral'],
            'completed' => ['Selesai '.$this->shortDate($rental->completed_at ?? $rental->updated_at), 'neutral'],
            'cancelled' => ['Dibatalkan '.$this->shortDate($rental->cancelled_at ?? $rental->updated_at), 'neutral'],
            'expired' => ['Batas bayar terlewati', 'neutral'],
            default => ['', 'neutral'],
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function dayHint(string $verb, ?Carbon $date, Carbon $dateTime): array
    {
        if (! $date) {
            return [$verb.' -', 'neutral'];
        }

        $days = (int) now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false);
        $time = $dateTime->format('H:i');

        return match (true) {
            $days === 0 => ["{$verb} hari ini, {$time}", 'success'],
            $days === 1 => ["{$verb} besok, {$time}", 'warn'],
            default => ["{$verb} ".$this->shortDate($date), 'neutral'],
        };
    }

    private function shortDate(?Carbon $date): string
    {
        return $date ? $date->copy()->locale('id')->translatedFormat('d M Y') : '-';
    }

    public function with(): array
    {
        $user = Auth::user();

        // Sama seperti halaman Riwayat Sewa: tandai kedaluwarsa bila lewat batas bayar.
        $user->rentalsAsCustomer()
            ->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get()
            ->each->expireIfPastDue();

        $activeStatuses = ['pending', 'confirmed', 'picked_up', 'returned'];
        $historyStatuses = ['completed', 'cancelled', 'expired'];
        $showHistory = $this->tab === 'riwayat';

        $counts = $user->rentalsAsCustomer()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $rentals = $user->rentalsAsCustomer()
            ->with(['vendor', 'details.masterItem', 'payments'])
            ->whereIn('status', $showHistory ? $historyStatuses : $activeStatuses)
            ->when(
                $showHistory,
                fn ($query) => $query->latest('id'),
                fn ($query) => $query->orderBy('pickup_date')->orderBy('id')
            )
            ->limit(5)
            ->get();

        return [
            'rentals' => $rentals,
            'showHistory' => $showHistory,
            'activeCount' => collect($activeStatuses)->sum(fn ($status) => (int) ($counts[$status] ?? 0)),
            'historyCount' => collect($historyStatuses)->sum(fn ($status) => (int) ($counts[$status] ?? 0)),
        ];
    }
}; ?>

<section wire:poll.30s aria-labelledby="my-rentals-title">
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-wheat-200 pb-4">
        <div>
            <h2 id="my-rentals-title" class="text-lg font-extrabold text-darkbrown-900 font-heading flex items-center gap-2">
                <i data-lucide="tent" class="w-5 h-5 text-avocado-600"></i>
                <span>{{ __('Sewa Saya') }}</span>
            </h2>
            <p class="mt-1 text-xs sm:text-sm text-darkbrown-600">
                {{ __('Pantau status sewa Anda. Halaman ini diperbarui otomatis.') }}
            </p>
        </div>

        <a href="{{ route('customer.booking.index') }}"
           class="btn-tendaku-secondary inline-flex items-center justify-center gap-2 py-2 px-4 rounded-xl text-xs sm:text-sm font-bold hover:bg-wheat-100 transition-all self-start sm:self-auto">
            <span>{{ __('Lihat Semua') }}</span>
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </a>
    </header>

    <div class="mt-4 inline-flex rounded-xl bg-wheat-100 p-1 text-xs font-bold" role="tablist">
        <button type="button" role="tab" wire:click="setTab('aktif')" aria-selected="{{ $showHistory ? 'false' : 'true' }}"
                class="px-3.5 py-1.5 rounded-lg transition {{ $showHistory ? 'text-darkbrown-600 hover:text-darkbrown-900' : 'bg-white text-darkbrown-900 shadow-sm' }}">
            {{ __('Aktif') }} ({{ $activeCount }})
        </button>
        <button type="button" role="tab" wire:click="setTab('riwayat')" aria-selected="{{ $showHistory ? 'true' : 'false' }}"
                class="px-3.5 py-1.5 rounded-lg transition {{ $showHistory ? 'bg-white text-darkbrown-900 shadow-sm' : 'text-darkbrown-600 hover:text-darkbrown-900' }}">
            {{ __('Riwayat') }} ({{ $historyCount }})
        </button>
    </div>

    <ul class="mt-3 divide-y divide-wheat-100">
        @forelse ($rentals as $rental)
            @php
                [$hintText, $hintTone] = $this->scheduleHint($rental);
                $steps = $this->completedSteps($rental);
                $isActive = in_array($rental->status, ['pending', 'confirmed', 'picked_up', 'returned'], true);
                $firstItem = $rental->details->first()?->masterItem;
                $extraItems = max(0, $rental->details->count() - 1);
                $hintClass = match ($hintTone) {
                    'success' => 'text-avocado-700',
                    'warn' => 'text-goldenrod-700',
                    'danger' => 'text-red-600',
                    default => 'text-darkbrown-500',
                };
                $stepLabels = ['Pembayaran', 'Pengambilan', 'Penyewaan', 'Pengembalian'];
            @endphp

            <li wire:key="rental-{{ $rental->id }}">
                <a href="{{ route('booking.status', $rental->booking_code) }}"
                   class="block py-3.5 px-2 -mx-2 rounded-xl hover:bg-wheat-50 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                        <div class="min-w-0 flex-1 basis-56">
                            <p class="text-sm font-bold text-darkbrown-900 truncate">
                                {{ $firstItem?->name ?? 'Perlengkapan camping' }}@if ($extraItems > 0) <span class="font-medium text-darkbrown-500">dan {{ $extraItems }} item lainnya</span>@endif
                            </p>
                            <p class="mt-0.5 text-xs text-darkbrown-500 truncate">
                                <span class="font-mono font-semibold text-darkbrown-700">#{{ $rental->booking_code }}</span>
                                @if ($rental->vendor?->name)
                                    &middot; {{ $rental->vendor->name }}
                                @endif
                            </p>
                        </div>

                        <x-rental-status-badge :status="$rental->status" :label="$rental->displayStatusLabel()" />

                        <span class="w-full sm:w-auto sm:min-w-[11rem] sm:text-right text-xs font-semibold {{ $hintClass }}">
                            {{ $hintText }}
                        </span>
                    </div>

                    @if ($isActive)
                        <ol class="mt-2.5 grid grid-cols-4 gap-1.5" aria-label="Progres sewa">
                            @foreach ($stepLabels as $index => $stepLabel)
                                @php
                                    $isDone = $index < $steps;
                                    $isCurrent = $index === $steps;
                                @endphp
                                <li class="min-w-0">
                                    <span class="block h-1.5 rounded-full {{ $isDone ? 'bg-avocado-500' : ($isCurrent ? 'bg-sunglow-300' : 'bg-wheat-200') }}"></span>
                                    <span class="mt-1 block truncate text-[10px] font-semibold {{ $isDone || $isCurrent ? 'text-darkbrown-700' : 'text-darkbrown-400' }}">
                                        {{ $stepLabel }}
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </a>
            </li>
        @empty
            <li class="py-10 text-center space-y-3">
                <div class="w-12 h-12 mx-auto rounded-xl bg-wheat-100 text-darkbrown-400 flex items-center justify-center">
                    <i data-lucide="tent" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-darkbrown-900">
                        {{ $showHistory ? __('Belum ada riwayat sewa') : __('Belum ada sewa aktif') }}
                    </h3>
                    <p class="text-xs text-darkbrown-500 mt-0.5">
                        {{ $showHistory ? __('Sewa yang sudah selesai atau dibatalkan akan muncul di sini.') : __('Pilih perlengkapan camping untuk mulai menyewa.') }}
                    </p>
                </div>
                @unless ($showHistory)
                    <a href="{{ route('customer.catalog.index') }}"
                       class="btn-tendaku-primary inline-flex items-center gap-1.5 py-2 px-4 rounded-xl text-xs font-bold shadow-sm">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                        <span>{{ __('Cari Perlengkapan') }}</span>
                    </a>
                @endunless
            </li>
        @endforelse
    </ul>
</section>
