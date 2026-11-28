{{-- Header section konsisten. Variabel: $step, $title, $subtitle, $aside (opsional, HTML) --}}
<div class="flex {{ isset($aside) ? 'flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-3' : 'items-start justify-between gap-3' }} px-5 sm:px-6 pt-5 sm:pt-6">
    <div class="flex items-start gap-3 min-w-0">
        <span class="w-8 h-8 rounded-full bg-avocado-900 text-white text-sm font-bold flex items-center justify-center shrink-0">{{ $step }}</span>
        <div class="min-w-0">
            <h2 class="text-lg font-bold text-darkbrown-900 font-heading leading-tight">{{ $title }}</h2>
            @isset($subtitle)
                <p class="mt-0.5 text-sm text-darkbrown-500">{{ $subtitle }}</p>
            @endisset
        </div>
    </div>
    @isset($aside)
        <div class="shrink-0 text-sm [&_a]:inline-flex [&_a]:items-center [&_a]:min-h-[44px] sm:[&_a]:min-h-0">{!! $aside !!}</div>
    @endisset
</div>
