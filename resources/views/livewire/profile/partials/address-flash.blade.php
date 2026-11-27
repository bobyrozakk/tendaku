{{-- Notifikasi status manage-addresses. Dipakai lewat @include dari komponen Volt. --}}
@if (session('status') === 'address-created')
    <div class="pf-rise mt-4 p-4 rounded-xl bg-avocado-50 border border-avocado-200 text-xs sm:text-sm font-semibold text-avocado-900 flex items-center gap-2">
        <i data-lucide="check-circle" class="w-4 h-4 text-avocado-600 shrink-0"></i>
        <span>Alamat baru berhasil ditambahkan.</span>
    </div>
@elseif (session('status') === 'address-updated')
    <div class="pf-rise mt-4 p-4 rounded-xl bg-avocado-50 border border-avocado-200 text-xs sm:text-sm font-semibold text-avocado-900 flex items-center gap-2">
        <i data-lucide="check-circle" class="w-4 h-4 text-avocado-600 shrink-0"></i>
        <span>Alamat berhasil diperbarui.</span>
    </div>
@elseif (session('status') === 'address-deleted')
    <div class="pf-rise mt-4 p-4 rounded-xl bg-wheat-100 border border-wheat-300 text-xs sm:text-sm font-semibold text-darkbrown-800 flex items-center gap-2">
        <i data-lucide="trash-2" class="w-4 h-4 text-darkbrown-600 shrink-0"></i>
        <span>Alamat berhasil dihapus.</span>
    </div>
@elseif (session('status') === 'address-default-set')
    <div class="pf-rise mt-4 p-4 rounded-xl bg-avocado-50 border border-avocado-200 text-xs sm:text-sm font-semibold text-avocado-900 flex items-center gap-2">
        <i data-lucide="check-circle" class="w-4 h-4 text-avocado-600 shrink-0"></i>
        <span>Alamat utama berhasil diubah.</span>
    </div>
@endif
