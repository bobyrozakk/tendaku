<x-layouts.customer>
    <x-slot name="title">
        Status Sewa #{{ $rental->booking_code }} - Tendaku
    </x-slot>

    <x-slot name="header">
        <div>
            <nav class="mb-1.5 flex flex-wrap items-center gap-2 text-xs text-darkbrown-500" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="transition hover:text-avocado-600">Beranda</a>
                <span class="text-wheat-400">/</span>
                <a href="{{ route('customer.rental-status.index') }}" class="transition hover:text-avocado-600">Status Sewa</a>
                <span class="text-wheat-400">/</span>
                <span class="font-semibold text-darkbrown-800">#{{ $rental->booking_code }}</span>
            </nav>

            <h1 class="font-heading text-2xl font-extrabold tracking-tight text-darkbrown-900 sm:text-3xl">
                Status Sewa
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
    @endphp

    <div class="mx-auto max-w-5xl space-y-5 py-2 sm:py-4">
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

                <div class="flex flex-wrap items-center gap-2">
                    <x-rental-status-badge
                        :status="$rental->status"
                        :label="$rental->displayStatusLabel()"
                    />
                    <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $paymentTone }}">
                        {{ $paymentStatus['label'] }}
                    </span>
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

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
            <section class="card-tendaku overflow-hidden lg:col-span-7">
                <header class="flex items-center justify-between gap-3 border-b border-wheat-200 p-5">
                    <div>
                        <h2 class="font-heading text-lg font-extrabold text-darkbrown-900">Perkembangan Sewa</h2>
                        <p class="mt-0.5 text-xs text-darkbrown-500">Tahapan mengikuti proses di Tendaku.</p>
                    </div>
                </header>

                <ol class="p-5 sm:p-6">
                    @foreach ($timeline as $step)
                        @php
                            $dotClass = match ($step['state']) {
                                'done' => 'border-avocado-500 bg-avocado-500 text-white',
                                'active' => 'border-goldenrod-400 bg-sunglow-300 text-darkbrown-900 ring-4 ring-sunglow-100',
                                default => 'border-wheat-300 bg-white text-darkbrown-400',
                            };
                            $lineClass = $step['state'] === 'done' ? 'bg-avocado-400' : 'bg-wheat-200';
                        @endphp

                        <li class="relative flex gap-4">
                            <div class="flex flex-col items-center">
                                <span class="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 text-sm font-extrabold transition {{ $dotClass }}">
                                    @if ($step['state'] === 'done')
                                        <i data-lucide="check" class="h-5 w-5" aria-label="Selesai"></i>
                                    @else
                                        {{ $loop->iteration }}
                                    @endif
                                </span>

                                @unless ($loop->last)
                                    <span class="my-1 w-0.5 flex-1 rounded {{ $lineClass }}" aria-hidden="true"></span>
                                @endunless
                            </div>

                            <div class="min-w-0 flex-1 {{ $loop->last ? '' : 'pb-6' }}">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-extrabold text-darkbrown-900">{{ $step['label'] }}</h3>

                                    @if ($step['state'] === 'active')
                                        <span class="rounded-full border border-sunglow-300 bg-sunglow-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-goldenrod-900">
                                            Saat ini
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-0.5 text-sm leading-relaxed text-darkbrown-600">{{ $step['description'] }}</p>

                                @if ($step['at'])
                                    <p class="mt-1 flex items-center gap-1.5 text-xs text-darkbrown-500">
                                        <i data-lucide="clock" class="h-3.5 w-3.5 shrink-0" aria-hidden="true"></i>
                                        <span>{{ $step['at'] }}</span>
                                    </p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            <div class="space-y-5 lg:col-span-5">
                <section class="card-tendaku overflow-hidden">
                    <header class="border-b border-wheat-200 p-5">
                        <h2 class="font-heading text-lg font-extrabold text-darkbrown-900">Detail Pesanan</h2>
                    </header>

                    <dl class="divide-y divide-wheat-100">
                        <div class="flex items-start gap-3 p-4 sm:px-5">
                            <i data-lucide="user-round" class="mt-0.5 h-4 w-4 shrink-0 text-avocado-600" aria-hidden="true"></i>
                            <div class="min-w-0">
                                <dt class="text-xs text-darkbrown-500">Nama Customer</dt>
                                <dd class="text-sm font-bold text-darkbrown-900">{{ $receipt['customerName'] }}</dd>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 p-4 sm:px-5">
                            <i data-lucide="calendar-plus" class="mt-0.5 h-4 w-4 shrink-0 text-avocado-600" aria-hidden="true"></i>
                            <div class="min-w-0">
                                <dt class="text-xs text-darkbrown-500">Tanggal Pemesanan</dt>
                                <dd class="text-sm font-bold text-darkbrown-900">
                                    {{ $rental->created_at?->translatedFormat('d M Y, H:i') }}
                                </dd>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 p-4 sm:px-5">
                            <i data-lucide="calendar-range" class="mt-0.5 h-4 w-4 shrink-0 text-avocado-600" aria-hidden="true"></i>
                            <div class="min-w-0">
                                <dt class="text-xs text-darkbrown-500">Periode Penyewaan</dt>
                                <dd class="text-sm font-bold text-darkbrown-900">
                                    {{ $rental->pickup_date?->translatedFormat('d M Y') }}
                                    @if ($rental->pickup_time)
                                        · {{ substr((string) $rental->pickup_time, 0, 5) }}
                                    @endif
                                    <span class="font-medium text-darkbrown-500">s/d</span>
                                    {{ $rental->return_date?->translatedFormat('d M Y') }}
                                    @if ($rental->return_time)
                                        · {{ substr((string) $rental->return_time, 0, 5) }}
                                    @endif
                                </dd>
                                <dd class="mt-0.5 text-xs text-darkbrown-500">{{ $rental->rental_days }} hari sewa</dd>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 p-4 sm:px-5">
                            <i data-lucide="wallet" class="mt-0.5 h-4 w-4 shrink-0 text-avocado-600" aria-hidden="true"></i>
                            <div class="min-w-0">
                                <dt class="text-xs text-darkbrown-500">Total Pembayaran</dt>
                                <dd class="font-heading text-lg font-extrabold text-darkbrown-900">
                                    Rp {{ number_format($total, 0, ',', '.') }}
                                </dd>
                                @if ($amountPaid > 0)
                                    <dd class="mt-0.5 text-xs text-darkbrown-500">
                                        Sudah dibayar Rp {{ number_format($amountPaid, 0, ',', '.') }}
                                    </dd>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-start gap-3 p-4 sm:px-5">
                            <i data-lucide="credit-card" class="mt-0.5 h-4 w-4 shrink-0 text-avocado-600" aria-hidden="true"></i>
                            <div class="min-w-0">
                                <dt class="text-xs text-darkbrown-500">Status Pembayaran</dt>
                                <dd class="mt-1">
                                    <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $paymentTone }}">
                                        {{ $paymentStatus['label'] }}
                                    </span>
                                </dd>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 p-4 sm:px-5">
                            <i data-lucide="clipboard-list" class="mt-0.5 h-4 w-4 shrink-0 text-avocado-600" aria-hidden="true"></i>
                            <div class="min-w-0">
                                <dt class="text-xs text-darkbrown-500">Status Penyewaan</dt>
                                <dd class="mt-1">
                                    <x-rental-status-badge
                                        :status="$rental->status"
                                        :label="$rental->displayStatusLabel()"
                                    />
                                </dd>
                            </div>
                        </div>
                    </dl>
                </section>

                <section class="card-tendaku overflow-hidden">
                    <header class="flex items-center justify-between gap-3 border-b border-wheat-200 p-5">
                        <h2 class="font-heading text-lg font-extrabold text-darkbrown-900">Barang yang Disewa</h2>
                        <span class="badge-wheat">{{ $receipt['items']->count() }} jenis</span>
                    </header>

                    <ul class="divide-y divide-wheat-100">
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
                </section>
            </div>
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
