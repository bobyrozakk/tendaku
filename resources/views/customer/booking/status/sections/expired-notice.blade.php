        @if($hasRental && $isExpiredOrCancelled)
            <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-sm text-red-800 flex items-start gap-2.5">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>Transaksi ini <strong>{{ strtolower($statusLabels[$status]) }}</strong>. Unit alat sudah dilepas kembali ke katalog.</span>
            </div>
        @endif
