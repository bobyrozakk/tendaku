<div x-show="needsAddressLocation()" x-cloak role="status"
     class="rounded-xl border border-sunglow-300 bg-sunglow-50 p-3 space-y-2">
    <p class="text-xs text-darkbrown-800 leading-relaxed">
        Alamat ini belum memiliki titik lokasi yang valid. Lengkapi pin terlebih dahulu agar ongkir dapat dihitung.
    </p>
    <button type="button" @click="openEditAddressModal()"
            class="inline-flex items-center gap-1.5 rounded-lg bg-avocado-700 px-3 py-2 text-xs font-bold text-white hover:bg-avocado-800 transition">
        <i data-lucide="map-pin" class="w-3.5 h-3.5" aria-hidden="true"></i>
        Lengkapi Titik Lokasi
    </button>
</div>
