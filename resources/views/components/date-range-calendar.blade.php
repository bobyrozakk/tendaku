{{-- Dropdown kalender rentang (Senin - Minggu) --}}
<div x-show="calOpen" x-cloak x-transition.opacity.duration.150ms role="dialog" aria-label="Pilih rentang tanggal sewa"
     class="absolute top-full left-0 mt-2 z-40 w-[21rem] max-w-[calc(100vw-2.5rem)] rounded-2xl bg-white border border-wheat-200 shadow-tendaku-lg overflow-hidden">
    <div class="bg-white px-4 pt-3.5 pb-2.5 border-b border-gray-200/80">
        <div class="flex items-center justify-between mb-2">
            <button type="button" @click="prevMonth()" aria-label="Bulan sebelumnya" class="w-11 h-11 sm:w-9 sm:h-9 rounded-full hover:bg-gray-100 flex items-center justify-center text-darkbrown-800 transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <span class="text-sm font-extrabold text-darkbrown-900" x-text="calendarTitle"></span>
            <button type="button" @click="nextMonth()" aria-label="Bulan berikutnya" class="w-11 h-11 sm:w-9 sm:h-9 rounded-full hover:bg-gray-100 flex items-center justify-center text-darkbrown-800 transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>
        <div class="grid grid-cols-7 text-center text-[11px] font-semibold text-darkbrown-500">
            <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span>
        </div>
    </div>
    <div class="bg-gray-100 p-3 sm:p-3.5">
        <div class="grid grid-cols-7 gap-y-1 text-center">
            <template x-for="(cell, idx) in calendarDays" :key="idx">
                <div class="h-11 sm:h-10 flex items-center justify-center"
                     :class="[cell.inRange ? 'bg-avocado-100' : '', cell.start ? 'rounded-l-full' : '', cell.end ? 'rounded-r-full' : '']">
                    <template x-if="cell.empty"><span class="block h-9"></span></template>
                    <template x-if="!cell.empty">
                        <button type="button" :disabled="cell.disabled" @click="selectCalDate(cell.iso)"
                                :class="[
                                    (cell.start || cell.end) ? 'bg-avocado-900 text-white font-bold shadow-xs' :
                                    (cell.unavailable ? 'bg-gray-200/70 text-gray-400 cursor-not-allowed' :
                                    (cell.disabled ? 'text-gray-400 opacity-50 cursor-not-allowed' :
                                    (cell.inRange ? 'text-avocado-900 font-semibold' : 'text-darkbrown-800 hover:bg-white font-medium')))
                                    , (cell.outside && !cell.start && !cell.end) ? 'opacity-50' : ''
                                ]"
                                class="w-full max-w-[2.75rem] h-11 sm:w-9 sm:h-9 rounded-full inline-flex items-center justify-center transition text-sm"
                                x-text="cell.day">
                        </button>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>
