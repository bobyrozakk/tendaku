@php($compact = $compact ?? false)

<form method="POST" action="{{ route('customer.rental-status.lookup') }}"
      class="{{ $compact ? 'flex flex-col gap-3 sm:flex-row sm:items-start' : 'space-y-4' }}">
    @csrf

    <div class="{{ $compact ? 'min-w-0 flex-1' : '' }}">
        @unless ($compact)
            <label for="booking_code" class="mb-1.5 block text-sm font-bold text-darkbrown-900">
                Kode Sewa / Order
            </label>
        @endunless

        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-darkbrown-400">
                <i data-lucide="ticket" class="h-5 w-5" aria-hidden="true"></i>
            </span>

            <input type="text"
                   id="booking_code"
                   name="booking_code"
                   value="{{ old('booking_code') }}"
                   placeholder="{{ $compact ? 'Masukkan kode sewa lain...' : 'Contoh: TDK202610100101001' }}"
                   maxlength="50"
                   required
                   autocomplete="off"
                   spellcheck="false"
                   @if (! $compact) aria-describedby="booking-code-help" @endif
                   @class([
                       'input-tendaku py-3 pl-11 pr-4 font-mono uppercase tracking-wide',
                       'border-red-400 focus:border-red-500 focus:ring-red-300' => $errors->has('booking_code'),
                   ])>
        </div>

        @error('booking_code')
            <p class="mt-2 flex items-start gap-1.5 text-sm text-red-600">
                <i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true"></i>
                <span>{{ $message }}</span>
            </p>
        @enderror
    </div>

    <button type="submit"
            class="{{ $compact ? 'btn-tendaku-primary shrink-0' : 'btn-tendaku-primary w-full py-3' }}">
        <i data-lucide="search" class="h-4 w-4" aria-hidden="true"></i>
        <span>Cek Status</span>
    </button>

    @unless ($compact)
        <p id="booking-code-help" class="flex items-start gap-2 text-xs leading-relaxed text-darkbrown-500">
            <i data-lucide="receipt-text" class="mt-0.5 h-4 w-4 shrink-0 text-goldenrod-600" aria-hidden="true"></i>
            <span>Kode sewa tercantum pada receipt pemesanan yang Anda terima setelah checkout.</span>
        </p>
    @endunless
</form>
