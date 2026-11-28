@props([
    'options' => [],
    'grouped' => false,
    'name' => null,
    'value' => null,
    'placeholder' => 'Pilih…',
    'icon' => null,
    'variant' => 'field',
    'size' => 'md',
    'mode' => 'value',
    'required' => false,
    'label' => null,
    'align' => 'left',
])

@php
    $normalize = fn ($items) => collect($items)
        ->map(fn ($text, $key) => ['value' => (string) $key, 'label' => (string) $text])
        ->values()
        ->all();

    $groups = $grouped
        ? collect($options)->map(fn ($items, $group) => ['label' => (string) $group, 'items' => $normalize($items)])->values()->all()
        : [['label' => null, 'items' => $normalize($options)]];

    $wireProp = collect($attributes->getAttributes())
        ->first(fn ($attrValue, $attrName) => str_starts_with((string) $attrName, 'wire:model'));

    $sizes = [
        'sm' => 'h-11 sm:h-10 px-3 text-xs font-semibold',
        'md' => 'h-11 sm:h-10 px-3.5 text-sm font-semibold',
        'lg' => 'h-11 px-3.5 text-sm font-semibold',
        'xl' => 'min-h-[44px] px-4 py-3 text-sm font-bold',
    ];

    $triggerClass = $variant === 'inline'
        ? 'inline-flex min-h-[44px] items-center gap-1 rounded-full px-2.5 py-1.5 text-xs font-bold text-darkbrown-900 hover:bg-wheat-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500/40 transition'
        : 'w-full flex items-center justify-between gap-2 rounded-xl border border-wheat-300 bg-white text-left text-darkbrown-900 shadow-2xs hover:border-avocado-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500/30 transition '.($sizes[$size] ?? $sizes['md']);

    $panelClass = $variant === 'inline'
        ? 'right-0 w-52 max-w-[calc(100vw-2rem)]'
        : ($align === 'right' ? 'right-0' : 'left-0 right-0 sm:right-auto').' sm:w-max min-w-full max-w-[min(20rem,calc(100vw-2rem))]';
@endphp

<div
    x-data="{
        open: false,
        value: {{ \Illuminate\Support\Js::from((string) ($value ?? '')) }},
        groups: {{ \Illuminate\Support\Js::from($groups) }},
        wireProp: {{ \Illuminate\Support\Js::from($wireProp) }},
        mode: {{ \Illuminate\Support\Js::from($mode) }},
        placeholder: {{ \Illuminate\Support\Js::from($placeholder) }},
        get current() {
            const key = String(this.value ?? '');
            for (const group of this.groups) {
                const found = group.items.find((item) => item.value === key);
                if (found) return found;
            }
            return null;
        },
        pick(next) {
            this.value = next;
            this.open = false;
            this.$dispatch('dropdown-change', { name: {{ \Illuminate\Support\Js::from($name) }}, value: next });
            if (this.wireProp) {
                this.$wire.set(this.wireProp, next);
            } else if (this.mode === 'navigate') {
                window.location.href = next;
            } else if (this.mode === 'submit') {
                this.$nextTick(() => this.$root.closest('form')?.requestSubmit());
            }
        },
    }"
    x-effect="if (wireProp) value = $wire[wireProp] ?? ''"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'relative']) }}
>
    @if ($name)
        @if ($required)
            <input type="text" name="{{ $name }}" :value="value" required tabindex="-1" aria-hidden="true" @focus="$el.nextElementSibling?.focus()" @keydown.prevent class="sr-only">
        @else
            <input type="hidden" name="{{ $name }}" :value="value">
        @endif
    @endif

    <button type="button" @click="open = !open" :aria-expanded="open ? 'true' : 'false'" aria-haspopup="listbox"
            :class="open ? '!border-avocado-600' : ''"
            @if ($label) aria-label="{{ $label }}" @endif
            class="{{ $triggerClass }}">
        <span class="flex items-center gap-2.5 min-w-0">
            @if ($icon)
                <i data-lucide="{{ $icon }}" class="w-4 h-4 text-avocado-800 shrink-0" aria-hidden="true"></i>
            @endif
            <span class="truncate" :class="current ? '' : 'text-darkbrown-400 font-medium'" x-text="current ? current.label : placeholder"></span>
        </span>
        <svg class="w-4 h-4 shrink-0 text-darkbrown-600 transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.150ms role="listbox"
         class="absolute top-full z-40 mt-2 max-h-64 overflow-y-auto overscroll-contain rounded-2xl bg-white border border-wheat-200 shadow-tendaku-lg p-2 {{ $panelClass }}">
        <template x-for="(group, gi) in groups" :key="gi">
            <div>
                <p x-show="group.label" x-text="group.label" class="px-3 pt-2 pb-1 text-[11px] font-bold uppercase tracking-wider text-darkbrown-500"></p>
                <template x-for="item in group.items" :key="item.value">
                    <button type="button" role="option" @click="pick(item.value)"
                            :aria-selected="String(value ?? '') === item.value ? 'true' : 'false'"
                            :class="String(value ?? '') === item.value ? 'bg-avocado-100 text-avocado-900 font-bold' : 'text-darkbrown-800 hover:bg-wheat-100'"
                            class="w-full min-h-[44px] flex items-center justify-between gap-2 rounded-lg px-3 py-2.5 text-left text-sm transition">
                        <span class="truncate" x-text="item.label"></span>
                        <svg x-show="String(value ?? '') === item.value" class="w-4 h-4 shrink-0 text-avocado-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    </button>
                </template>
            </div>
        </template>
    </div>
</div>
