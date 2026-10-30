<x-layouts.customer>
    <x-slot name="title">
        Receipt Order #{{ $rental->booking_code }} - Tendaku
    </x-slot>

    <div class="no-print receipt-actions">
        <a
            href="{{ route('booking.status', $rental->booking_code) }}"
            class="receipt-action-btn receipt-action-back"
        >
            <i data-lucide="chevron-left" class="h-4 w-4 mr-1"></i>Kembali
        </a>

        <button
            type="button"
            onclick="window.print()"
            class="receipt-action-btn receipt-action-print"
        >
            <i data-lucide="printer" class="h-5 w-5 mr-1.5"></i>Cetak Receipt
        </button>
        {{-- Tombol vendor tetap sama --}}
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
