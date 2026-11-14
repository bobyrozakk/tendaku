@props([
    'eyebrow' => 'Super Admin',
    'title',
    'description' => null,
])

<section {{ $attributes->merge(['class' => 'relative isolate overflow-hidden rounded-3xl border border-darkbrown-700 bg-darkbrown-900 text-wheat-100 shadow-tendaku-lg']) }}>
    {{-- Ornamen latar memakai palet dasar: dark brown, avocado, goldenrod --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0">
        <div class="absolute -right-20 -top-28 h-72 w-72 rounded-full bg-avocado-500/25 blur-3xl"></div>
        <div class="absolute -bottom-28 right-1/4 h-64 w-64 rounded-full bg-goldenrod-500/15 blur-3xl"></div>
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-wheat-300/40 to-transparent"></div>
    </div>

    <div class="relative flex flex-col gap-5 px-5 py-5 sm:px-6 sm:py-6 lg:flex-row lg:items-end lg:justify-between lg:gap-8">
        <div class="min-w-0 max-w-3xl">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-wheat-300/25 bg-white/5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-wheat-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-sunglow-300" aria-hidden="true"></span>
                    {{ $eyebrow }}
                </span>

                {{ $badges ?? '' }}
            </div>

            <h1 class="mt-2.5 font-display text-3xl font-normal tracking-tight text-wheat-50 sm:text-4xl">
                {{ $title }}
            </h1>

            @if ($description)
                <p class="mt-1.5 max-w-xl text-[13px] leading-relaxed text-wheat-200/75">
                    {{ $description }}
                </p>
            @endif

            @isset($meta)
                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-[11px] font-medium text-wheat-200/70">
                    {{ $meta }}
                </div>
            @endisset
        </div>

        @isset($actions)
            <div class="flex flex-wrap items-center gap-2 lg:shrink-0">
                {{ $actions }}
            </div>
        @endisset
    </div>
</section>
