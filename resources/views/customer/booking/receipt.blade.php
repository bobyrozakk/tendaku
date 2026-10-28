<x-layouts.customer>
    <x-slot name="title">
        Receipt Order #{{ $rental->booking_code }} - Tendaku
    </x-slot>

    <x-slot name="header">
        {{-- Breadcrumb dan judul halaman --}}
    </x-slot>

    <div class="no-print receipt-actions">
        <a
            href="{{ url()->previous() }}"
            class="receipt-action-btn receipt-action-back"
        >
            <i data-lucide="chevron-left" class="h-4 w-4 mr-1"></i>Kembali
        </a>

        <button
            type="button"
            onclick="window.print()"
            class="receipt-action-btn receipt-action-print"
        >
            <i data-lucide="printer" class="h-5 w-5"></i>
        </button>

        @if ($rental->vendor)
            @php
                $vendorPhone = preg_replace(
                    '/\D+/',
                    '',
                    $rental->vendor->phone ?? ''
                );

                if (str_starts_with($vendorPhone, '0')) {
                    $vendorPhone = '62' . substr($vendorPhone, 1);
                }
            @endphp

            @if ($vendorPhone)
                <a
                    href="https://wa.me/{{ $vendorPhone }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="receipt-action-btn receipt-action-contact"
                >
                    Hubungi Vendor
                </a>
            @endif
        @endif
    </div>

    <main class="receipt-page mx-auto px-4 py-5">
        <article id="order-receipt" class="receipt-paper">
            @include('customer.booking.partials.receipt-header')
            @include('customer.booking.partials.receipt-customer')
            @include('customer.booking.partials.receipt-items')
            @include('customer.booking.partials.receipt-payment')
            @include('customer.booking.partials.receipt-footer')
        </article>
    </main>
</x-layouts.customer>
