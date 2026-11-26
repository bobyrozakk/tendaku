<x-layouts.customer>
    <x-slot name="title">
        Status Sewa #{{ $rental->booking_code }} - Tendaku
    </x-slot>

    <x-slot name="header">
        <div>
            <x-breadcrumb :items="[
                ['label' => 'Beranda', 'url' => route('home')],
                ['label' => 'Status Sewa', 'url' => route('customer.rental-status.index')],
                ['label' => '#'.$rental->booking_code],
            ]" />

            <h1 class="font-display font-normal tracking-tight flex items-center gap-2.5 text-3xl text-darkbrown-900 sm:text-4xl">
                <i data-lucide="package-search" class="h-7 w-7 shrink-0" aria-hidden="true"></i>
                <span>Status Sewa</span>
            </h1>
            <p class="mt-1 max-w-2xl text-xs text-darkbrown-600 sm:text-sm">
                Detail pesanan dan perkembangan penyewaan untuk kode sewa di bawah ini.
            </p>
        </div>
    </x-slot>

    @php
        $paymentTone = match ($paymentStatus['tone']) {
            'success' => 'bg-avocado-100 text-avocado-800 border-avocado-300',
            'warning' => 'bg-sunglow-100 text-goldenrod-900 border-sunglow-300',
            'danger' => 'bg-red-100 text-red-800 border-red-300',
            default => 'bg-wheat-100 text-darkbrown-700 border-wheat-300',
        };
        $isStopped = in_array($rental->status, ['cancelled', 'expired'], true);
        $total = (float) $receipt['grandTotal'];
        $amountPaid = (float) $receipt['amountPaid'];
        $balance = (float) $receipt['balance'];
    @endphp

    <div class="space-y-5">
        {{-- Ringkasan kode sewa --}}
        <section class="overflow-hidden rounded-2xl border border-wheat-200 bg-gradient-to-r from-avocado-50 via-wheat-50 to-sunglow-50 p-5 shadow-tendaku-sm sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-darkbrown-500">Kode Sewa</p>
                    <p class="truncate font-mono text-xl font-extrabold tracking-tight text-darkbrown-900 sm:text-2xl">
                        #{{ $rental->booking_code }}
                    </p>
                    <p class="mt-1 text-xs text-darkbrown-600">
                        Dipesan {{ $rental->created_at?->translatedFormat('d M Y, H:i') }}
                        @if ($rental->vendor?->name)
                            · {{ $rental->vendor->name }}
                        @endif
                    </p>
                </div>
            </div>
        </section>

        @if ($isStopped)
            <x-alert type="danger" :title="$rental->status === 'cancelled' ? 'Pesanan dibatalkan' : 'Pesanan kedaluwarsa'">
                {{ $rental->status === 'cancelled'
                    ? 'Pesanan ini telah dibatalkan. Hubungi vendor jika Anda membutuhkan informasi lebih lanjut.'
                    : 'Batas waktu pembayaran telah lewat sehingga pesanan tidak diproses. Silakan buat pesanan baru jika masih dibutuhkan.' }}
            </x-alert>
        @endif

        {{-- Perkembangan sewa: horizontal di desktop, vertikal di mobile --}}
        <section class="card-tendaku overflow-hidden">
            <header class="border-b border-wheat-200 p-5">
                <h2 class="font-display font-normal text-xl text-darkbrown-900">Perkembangan Sewa</h2>
                <p class="mt-0.5 text-xs text-darkbrown-500">Tahapan mengikuti proses di Tendaku.</p>
            </header>

            <ol class="p-5 sm:p-6 md:grid md:grid-cols-5">
                @foreach ($timeline as $step)
                    @php
                        $dotClass = match ($step['state']) {
                            'done' => 'border-avocado-500 bg-avocado-500 text-white',
                            'active' => 'border-goldenrod-400 bg-sunglow-300 text-darkbrown-900 ring-4 ring-sunglow-100',
                            default => 'border-wheat-300 bg-white text-darkbrown-400',
                        };
                        $lineClass = $step['state'] === 'done' ? 'bg-avocado-400' : 'bg-wheat-200';
                        $isTodo = $step['state'] === 'todo';
                    @endphp

                    <li class="flex gap-4 md:block">
                        <div class="flex flex-col items-center md:flex-row">
                            <span class="relative z-10 flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 text-sm font-extrabold {{ $dotClass }}">
                                @if ($step['state'] === 'done')
                                    <i data-lucide="check" class="h-4 w-4" aria-label="Selesai"></i>
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </span>

                            @unless ($loop->last)
                                <span class="my-1 w-0.5 flex-1 rounded md:hidden {{ $lineClass }}" aria-hidden="true"></span>
                                <span class="mx-3 hidden h-0.5 flex-1 rounded md:block {{ $lineClass }}" aria-hidden="true"></span>
                            @endunless
                        </div>

                        <div class="min-w-0 flex-1 {{ $loop->last ? '' : 'pb-6' }} md:mt-3 md:flex-none md:pb-0 md:pr-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-sm font-extrabold {{ $isTodo ? 'text-darkbrown-500' : 'text-darkbrown-900' }}">{{ $step['label'] }}</h3>
                            </div>

                            <p class="mt-0.5 text-xs leading-relaxed {{ $isTodo ? 'text-darkbrown-400' : 'text-darkbrown-600' }}">{{ $step['description'] }}</p>

                            @if ($step['at'])
                                <p class="mt-1.5 flex items-center gap-1.5 text-[11px] text-darkbrown-500">
                                    <i data-lucide="clock" class="h-3 w-3 shrink-0" aria-hidden="true"></i>
                                    <span>{{ $step['at'] }}</span>
                                </p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Detail pesanan & barang: dua kartu sama tinggi --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <section class="card-tendaku flex flex-col overflow-hidden">
                <header class="flex items-center justify-between gap-3 border-b border-wheat-200 p-5">
                    <h2 class="font-display font-normal text-xl text-darkbrown-900">Detail Pesanan</h2>
                    <span class="badge-wheat">{{ $rental->rental_days }} hari sewa</span>
                </header>

                <dl class="grid flex-1 grid-cols-1 content-start gap-x-6 gap-y-5 p-5 sm:grid-cols-2">
                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-avocado-50 text-avocado-700">
                            <i data-lucide="user-round" class="h-4 w-4" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0">
                            <dt class="text-xs text-darkbrown-500">Nama Customer</dt>
                            <dd class="truncate text-sm font-bold text-darkbrown-900">{{ $receipt['customerName'] }}</dd>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-avocado-50 text-avocado-700">
                            <i data-lucide="calendar-plus" class="h-4 w-4" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0">
                            <dt class="text-xs text-darkbrown-500">Tanggal Pemesanan</dt>
                            <dd class="text-sm font-bold text-darkbrown-900">{{ $rental->created_at?->translatedFormat('d M Y') }}</dd>
                            <dd class="text-xs text-darkbrown-500">Pukul {{ $rental->created_at?->format('H:i') }}</dd>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sunglow-100 text-goldenrod-900">
                            <i data-lucide="package" class="h-4 w-4" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0">
                            <dt class="text-xs text-darkbrown-500">Ambil</dt>
                            <dd class="text-sm font-bold text-darkbrown-900">{{ $rental->pickup_date?->translatedFormat('d M Y') }}</dd>
                            @if ($rental->pickup_time)
                                <dd class="text-xs text-darkbrown-500">Pukul {{ substr((string) $rental->pickup_time, 0, 5) }}</dd>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sunglow-100 text-goldenrod-900">
                            <i data-lucide="package-check" class="h-4 w-4" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0">
                            <dt class="text-xs text-darkbrown-500">Kembali</dt>
                            <dd class="text-sm font-bold text-darkbrown-900">{{ $rental->return_date?->translatedFormat('d M Y') }}</dd>
                            @if ($rental->return_time)
                                <dd class="text-xs text-darkbrown-500">Pukul {{ substr((string) $rental->return_time, 0, 5) }}</dd>
                            @endif
                        </div>
                    </div>
                </dl>

                <div class="grid grid-cols-2 gap-4 border-t border-wheat-200 bg-wheat-50/60 px-5 py-4">
                    <div>
                        <p class="text-xs text-darkbrown-500">Sudah dibayar</p>
                        <p class="font-heading text-base font-extrabold text-darkbrown-900">Rp {{ number_format($amountPaid, 0, ',', '.') }}</p>
                    </div>
                    @unless ($isStopped)
                        <div>
                            <p class="text-xs text-darkbrown-500">Sisa tagihan</p>
                            <p class="font-heading text-base font-extrabold {{ $balance > 0 ? 'text-goldenrod-900' : 'text-avocado-700' }}">
                                Rp {{ number_format($balance, 0, ',', '.') }}
                            </p>
                        </div>
                    @endunless
                </div>
            </section>

            <section class="card-tendaku flex flex-col overflow-hidden">
                <header class="flex items-center justify-between gap-3 border-b border-wheat-200 p-5">
                    <h2 class="font-display font-normal text-xl text-darkbrown-900">Barang yang Disewa</h2>
                    <span class="badge-wheat">{{ $receipt['items']->count() }} jenis</span>
                </header>

                <ul class="flex-1 divide-y divide-wheat-100">
                    @forelse ($receipt['items'] as $item)
                        <li class="flex items-start justify-between gap-3 p-4 sm:px-5">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-darkbrown-900">{{ $item['name'] }}</p>

                                @if (! empty($item['category']))
                                    <p class="mt-0.5 text-xs text-darkbrown-500">{{ $item['category'] }}</p>
                                @endif

                                <p class="mt-1 text-xs text-darkbrown-500">
                                    {{ $item['quantity'] }} unit × {{ $item['rental_days'] }} hari ×
                                    Rp {{ number_format($item['daily_rate'], 0, ',', '.') }}
                                </p>
                            </div>

                            <p class="shrink-0 text-sm font-extrabold text-darkbrown-900">
                                Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                            </p>
                        </li>
                    @empty
                        <li class="p-5 text-sm text-darkbrown-500">Rincian barang belum tersedia.</li>
                    @endforelse
                </ul>

                <dl class="space-y-1.5 border-t border-wheat-200 bg-wheat-50/60 px-5 py-4 text-xs text-darkbrown-600">
                    <div class="flex justify-between gap-3">
                        <dt>Subtotal sewa</dt>
                        <dd class="font-semibold text-darkbrown-800">Rp {{ number_format((float) $receipt['subtotal'], 0, ',', '.') }}</dd>
                    </div>
                    @if ((float) $receipt['deliveryFee'] > 0)
                        <div class="flex justify-between gap-3">
                            <dt>Ongkos kirim</dt>
                            <dd class="font-semibold text-darkbrown-800">Rp {{ number_format((float) $receipt['deliveryFee'], 0, ',', '.') }}</dd>
                        </div>
                    @endif
                    @if ((float) $receipt['adminFee'] > 0)
                        <div class="flex justify-between gap-3">
                            <dt>Biaya admin</dt>
                            <dd class="font-semibold text-darkbrown-800">Rp {{ number_format((float) $receipt['adminFee'], 0, ',', '.') }}</dd>
                        </div>
                    @endif
                    @if ((float) $receipt['discount'] > 0)
                        <div class="flex justify-between gap-3">
                            <dt>Diskon</dt>
                            <dd class="font-semibold text-avocado-700">− Rp {{ number_format((float) $receipt['discount'], 0, ',', '.') }}</dd>
                        </div>
                    @endif
                    @if ((float) $receipt['totalFine'] > 0)
                        <div class="flex justify-between gap-3">
                            <dt>Denda</dt>
                            <dd class="font-semibold text-red-700">Rp {{ number_format((float) $receipt['totalFine'], 0, ',', '.') }}</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between gap-3 border-t border-wheat-200 pt-2 text-sm">
                        <dt class="font-bold text-darkbrown-900">Total</dt>
                        <dd class="font-heading text-base font-extrabold text-darkbrown-900">Rp {{ number_format($total, 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        <section class="card-tendaku-warm p-4 sm:p-5">
            <div class="mb-3">
                <h2 class="text-sm font-extrabold text-darkbrown-900">Cek kode sewa lain</h2>
                <p class="mt-0.5 text-xs text-darkbrown-500">Gunakan kode pada receipt pesanan lainnya.</p>
            </div>

            @include('customer.rental-status.partials.search-form', ['compact' => true])
        </section>

        <p class="text-center text-xs leading-relaxed text-darkbrown-500">
            Simpan kode sewa ini. Anda dapat membuka halaman ini kembali kapan saja untuk melihat status terbaru.
        </p>
    </div>
</x-layouts.customer>
