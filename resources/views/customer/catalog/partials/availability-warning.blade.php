@if ($onlyAvailable && in_array(null, $dateStockMap, true))
    <div class="flex items-start gap-2.5 rounded-xl border border-goldenrod-200 bg-goldenrod-50 px-3.5 py-3 text-xs text-goldenrod-900" role="status">
        <i data-lucide="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0 text-goldenrod-600" aria-hidden="true"></i>
        <p>Ketersediaan belum dapat diperiksa untuk sebagian peralatan. Nonaktifkan filter untuk memilih jadwal lain.</p>
    </div>
@endif
