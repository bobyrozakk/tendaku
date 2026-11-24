@php
$iconByCategoryIcon = [
    'tent' => 'tent',
    'moon' => 'moon',
    'flame' => 'flame',
    'sun' => 'flashlight',
    'flashlight' => 'flashlight',
    'bag' => 'backpack',
    'backpack' => 'backpack',
];

$iconByCategoryName = [
    'tenda' => 'tent',
    'sleep' => 'moon',
    'tidur' => 'moon',
    'masak' => 'flame',
    'cook' => 'flame',
    'lampu' => 'flashlight',
    'light' => 'flashlight',
    'tas' => 'backpack',
    'carrier' => 'backpack',
    'bag' => 'backpack',
];

$categoryIcon = static function ($category) use ($iconByCategoryIcon, $iconByCategoryName): string {
    $icon = $iconByCategoryIcon[strtolower($category->icon ?? '')] ?? null;

    if ($icon) {
        return $icon;
    }

    $name = strtolower($category->name);

    foreach ($iconByCategoryName as $needle => $mapped) {
        if (str_contains($name, $needle)) {
            return $mapped;
        }
    }

    return 'tag';
};

$categoryButtonClass = static fn (bool $active): string => 'inline-flex min-h-[2.5rem] shrink-0 items-center gap-1.5 rounded-xl border px-3.5 py-2 text-xs font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 '.($active
    ? 'border-avocado-700 bg-avocado-600 text-white shadow-sm'
    : 'border-wheat-300 bg-white text-darkbrown-700 hover:border-avocado-400 hover:bg-wheat-50');
@endphp

<div class="space-y-2">
    <div class="flex items-center justify-between gap-3">
        <span class="text-[11px] font-bold uppercase tracking-[.18em] text-darkbrown-500">Kategori</span>
        <span class="hidden text-[11px] font-medium text-darkbrown-400 sm:inline">
            Pilih satu kategori untuk mempersempit hasil
        </span>
    </div>

    <div class="flex flex-wrap gap-2">
        <button
            type="button"
            wire:click="selectCategory(null)"
            aria-pressed="{{ is_null($categoryId) ? 'true' : 'false' }}"
            class="{{ $categoryButtonClass(is_null($categoryId)) }}"
        >
            <i data-lucide="sparkles" class="h-3.5 w-3.5" aria-hidden="true"></i>
            <span>Semua Kategori</span>
        </button>

        @foreach ($categories as $cat)
        @php
            $isActive = $categoryId === $cat->id;
        @endphp

            <button
                type="button"
                wire:click="selectCategory({{ $cat->id }})"
                aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                class="{{ $categoryButtonClass($isActive) }}"
            >
                <i data-lucide="{{ $categoryIcon($cat) }}" class="h-3.5 w-3.5" aria-hidden="true"></i>
                <span>{{ $cat->name }}</span>
                @if (isset($cat->items_count))
                    <span class="ml-0.5 rounded-full px-1.5 py-0.5 text-[10px] {{ $isActive ? 'bg-avocado-800 text-white' : 'bg-wheat-200 text-darkbrown-800' }}">
                        {{ $cat->items_count }}
                    </span>
                @endif
            </button>
        @endforeach
    </div>
</div>
