<div x-data="{ show: false }" class="relative {{ $attributes->get('wrapper-class') }}">
    <input
        type="password"
        x-bind:type="show ? 'text' : 'password'"
        {{ $attributes->except(['type', 'wrapper-class'])->merge([
            'class' => 'block w-full rounded-xl border-wheat-300 bg-white/90 pr-12 text-darkbrown-800 shadow-sm focus:border-avocado-500 focus:ring-2 focus:ring-avocado-400/40 text-sm placeholder:text-darkbrown-400 transition duration-150 disabled:bg-wheat-100 disabled:opacity-60',
        ]) }}
    >

    <button
        type="button"
        x-on:click="show = !show"
        x-bind:aria-pressed="show"
        x-bind:aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
        class="absolute inset-y-0 right-0 flex items-center px-3 text-darkbrown-400 transition-colors hover:text-darkbrown-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500"
    >
        <i
            x-show="!show"
            data-lucide="eye-closed"
            class="h-5 w-5"
        ></i>

        <i
            x-show="show"
            x-cloak
            data-lucide="eye"
            class="h-5 w-5"
        ></i>
    </button>
</div>
