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

    /**
     * Tanggal utama di sisi kanan baris: [label, tanggal].
     *
     * @return array{0: string, 1: string}
     */
    public function dateInfo(Rental $rental): array
    {
        return match ($rental->status) {
            'pending', 'confirmed' => ['Ambil', $this->shortDate($rental->pickup_date)],
            'picked_up', 'returned' => ['Sampai', $this->shortDate($rental->return_date)],
            'completed' => ['Selesai', $this->shortDate($rental->completed_at ?? $rental->updated_at)],
            'cancelled' => ['Dibatalkan', $this->shortDate($rental->cancelled_at ?? $rental->updated_at)],
            'expired' => ['Batas bayar', $this->shortDate($rental->expires_at)],
            default => ['Jadwal', '-'],
        };
    }

    /**
     * Info ringkas untuk baris riwayat: rentang tanggal sewa dan total bayar.
     *
     * @return array{0: string, 1: string}
     */
    public function historyMeta(Rental $rental): array
    {
        $from = $rental->pickup_date ? $this->shortDate($rental->pickup_date) : null;
        $to = $rental->return_date ? $this->shortDate($rental->return_date) : null;

        return [
            $from && $to ? ($from === $to ? $from : "{$from} - {$to}") : '-',
            'Rp '.number_format((float) $rental->grand_total, 0, ',', '.'),
        ];
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
            ->with(['vendor', 'details.masterItem.primaryImage', 'payments'])
            ->whereIn('status', $showHistory ? $historyStatuses : $activeStatuses)
            ->when(
                $showHistory,
                fn ($query) => $query->latest('id'),
                fn ($query) => $query->orderBy('pickup_date')->orderBy('id')
            )
            ->limit(3)
            ->get();

        return [
            'rentals' => $rentals,
            'showHistory' => $showHistory,
            'activeCount' => collect($activeStatuses)->sum(fn ($status) => (int) ($counts[$status] ?? 0)),
            'historyCount' => collect($historyStatuses)->sum(fn ($status) => (int) ($counts[$status] ?? 0)),
        ];
    }
}; ?>

<section wire:poll.30s.visible aria-labelledby="my-rentals-title" class="rent-section">
    <header class="rent-head">
        <div class="flex items-center gap-3 min-w-0">
            <span class="rent-head-icon"><i data-lucide="tent" class="w-5 h-5"></i></span>
            <div class="min-w-0">
                <h2 id="my-rentals-title" class="text-lg font-extrabold text-darkbrown-900 font-heading leading-tight">
                    {{ __('Sewa Saya') }}
                </h2>
                <p class="mt-0.5 text-xs text-darkbrown-500">{{ __('Pantau status sewa Anda.') }}</p>
            </div>
        </div>

        <a href="{{ route('customer.booking.index') }}" class="rent-all-link">
            <span>{{ __('Lihat Semua') }}</span>
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </a>
    </header>

    <div class="rent-tabs" role="tablist">
        <button type="button" role="tab" wire:click="setTab('aktif')" aria-selected="{{ $showHistory ? 'false' : 'true' }}" class="rent-tab">
            <i data-lucide="package"></i>
            <span>{{ __('Aktif') }} ({{ $activeCount }})</span>
        </button>
        <button type="button" role="tab" wire:click="setTab('riwayat')" aria-selected="{{ $showHistory ? 'true' : 'false' }}" class="rent-tab">
            <i data-lucide="history"></i>
            <span>{{ __('Riwayat') }} ({{ $historyCount }})</span>
        </button>
    </div>

    <div class="rent-panel">
        <div class="rent-panel-head">
            <span class="rent-live" aria-hidden="true"></span>
            <span>{{ $showHistory ? __('Riwayat Sewa') : __('Ringkasan Sewa Aktif') }}</span>
        </div>

        <ul class="rent-list">
            @forelse ($rentals as $rental)
                @php
                    [$hintText, $hintTone] = $this->scheduleHint($rental);
                    [$dateLabel, $dateValue] = $this->dateInfo($rental);
                    $steps = $this->completedSteps($rental);
                    $isActive = in_array($rental->status, ['pending', 'confirmed', 'picked_up', 'returned'], true);
                    $firstItem = $rental->details->first()?->masterItem;
                    $extraItems = max(0, $rental->details->count() - 1);
                    $imageUrl = $firstItem?->primaryImage?->image_url ?: $firstItem?->image_url;
                    $stepLabels = ['Pembayaran', 'Pengambilan', 'Penyewaan', 'Pengembalian'];
                @endphp

                <li wire:key="rental-{{ $rental->id }}">
                    <a href="{{ route('booking.status', $rental->booking_code) }}" class="rent-row">
                        <div class="rent-thumb" data-muted="{{ in_array($rental->status, ['cancelled', 'expired'], true) ? 'true' : 'false' }}">
                            <i data-lucide="tent"></i>
                            @if ($imageUrl)
                                <img src="{{ $imageUrl }}" alt="{{ $firstItem?->name ?? 'Foto alat' }}"
                                     width="80" height="80" loading="lazy" decoding="async"
                                     onerror="this.style.display='none'">
                            @endif
                        </div>

                        <div class="rent-body">
                            <p class="rent-title">
                                {{ $firstItem?->name ?? 'Perlengkapan camping' }}@if ($extraItems > 0) <small>dan {{ $extraItems }} item lainnya</small>@endif
                            </p>
                            <p class="rent-meta">
                                <b>#{{ $rental->booking_code }}</b>@if ($rental->vendor?->name) &middot; {{ $rental->vendor->name }}@endif
                            </p>

                            @if ($isActive)
                                <ol class="rent-steps" aria-label="Progres sewa">
                                    @foreach ($stepLabels as $index => $stepLabel)
                                        @php $state = $index < $steps ? 'done' : ($index === $steps ? 'current' : 'todo'); @endphp
                                        <li class="rent-step" data-state="{{ $state }}">
                                            <span class="rent-dot">
                                                @if ($state === 'done')
                                                    <i data-lucide="check"></i>
                                                @endif
                                            </span>
                                            <span class="rent-step-label">{{ $stepLabel }}</span>
                                        </li>
                                    @endforeach
                                </ol>
                            @else
                                @php [$rangeText, $totalText] = $this->historyMeta($rental); @endphp
                                <div class="rent-chips">
                                    <span class="rent-chip"><i data-lucide="calendar-days"></i>{{ $rangeText }}</span>
                                    <span class="rent-chip"><i data-lucide="wallet"></i>{{ $totalText }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="rent-aside">
                            <x-rental-status-badge :status="$rental->status" :label="$rental->displayStatusLabel()" class="self-start" />

                            <div class="rent-date">
                                <span class="rent-date-icon"><i data-lucide="calendar-days"></i></span>
                                <div>
                                    <p class="rent-date-label">{{ $dateLabel }}</p>
                                    <p class="rent-date-value">{{ $dateValue }}</p>
                                </div>
                            </div>

                            @if ($isActive && $hintText !== '')
                                <span class="rent-hint" data-tone="{{ $hintTone }}">{{ $hintText }}</span>
                            @endif
                        </div>
                    </a>
                </li>
            @empty
                <li class="rent-empty space-y-3">
                    <span class="rent-empty-icon"><i data-lucide="tent" class="w-6 h-6"></i></span>
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
    </div>
</section>
