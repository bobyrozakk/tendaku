@php
    $isDashboardActive = request()->routeIs('vendor.owner.dashboard');

    $catalogItems = [
        [
            'label' => 'Produk',
            'icon' => 'package',
            'href' => route('vendor.products.index'),
            'active' => request()->routeIs('vendor.products.*'),
        ],
        [
            'label' => 'Paket Camping',
            'icon' => 'tent',
            'href' => route('vendor.packages.index'),
            'active' => request()->routeIs('vendor.packages.*'),
        ],
        [
            'label' => 'Kategori',
            'icon' => 'tags',
            'href' => route('vendor.categories.index'),
            'active' => request()->routeIs('vendor.categories.*'),
        ],
        [
            'label' => 'Unit',
            'icon' => 'boxes',
            'href' => route('vendor.units'),
            'active' => request()->routeIs('vendor.units'),
        ],
    ];

    $operationalItems = [
        [
            'label' => 'Manajemen Kasir',
            'icon' => 'receipt',
            'href' => route('vendor.cashiers.index'),
            'active' => request()->routeIs('vendor.cashiers.*'),
        ],
        [
            'label' => 'Pendapatan',
            'icon' => 'wallet',
            'href' => route('vendor.pendapatan'),
            'active' => request()->routeIs('vendor.pendapatan*'),
        ],
        [
            'label' => 'Pengaturan Pengiriman',
            'icon' => 'truck',
            'href' => route('vendor.shipping.index'),
            'active' => request()->routeIs('vendor.shipping.*'),
        ],
    ];

    $settingsItems = [
        [
            'label' => 'Profil Toko',
            'icon' => 'store',
            'href' => route('profile'),
            'active' => request()->routeIs('profile'),
        ],
    ];

    $menuGroups = [
        [
            'id' => 'catalog',
            'label' => 'Manajemen Katalog',
            'icon' => 'package',
            'active' => collect($catalogItems)->contains('active', true),
            'items' => $catalogItems,
        ],
        [
            'id' => 'operational',
            'label' => 'Operasional',
            'icon' => 'clipboard-list',
            'active' => collect($operationalItems)->contains('active', true),
            'items' => $operationalItems,
        ],
        [
            'id' => 'settings',
            'label' => 'Pengaturan',
            'icon' => 'settings',
            'active' => collect($settingsItems)->contains('active', true),
            'items' => $settingsItems,
        ],
    ];

    $initialOpenGroups = [
        'catalog' => $menuGroups[0]['active'],
        'operational' => $menuGroups[1]['active'],
        'settings' => $menuGroups[2]['active'],
    ];

    if (! in_array(true, $initialOpenGroups, true)) {
        $initialOpenGroups['catalog'] = true;
    }
@endphp

<div
    x-data="{
        collapsed: false,
        mobileOpen: false,
        openGroups: @js($initialOpenGroups),
        init() {
            try {
                this.collapsed = window.localStorage.getItem('tendaku_owner_sidebar_collapsed') === 'true';
            } catch (e) {
                this.collapsed = false;
            }
        },
        get isExpanded() {
            return this.mobileOpen || !this.collapsed;
        },
        toggleCollapse() {
            this.collapsed = !this.collapsed;
            try {
                window.localStorage.setItem('tendaku_owner_sidebar_collapsed', String(this.collapsed));
            } catch (e) {}
        },
        toggleGroup(id) {
            if (!this.isExpanded) {
                this.collapsed = false;
                this.openGroups[id] = true;
                try {
                    window.localStorage.setItem('tendaku_owner_sidebar_collapsed', 'false');
                } catch (e) {}
                return;
            }
            this.openGroups[id] = !this.openGroups[id];
        }
    }"
    @toggle-kasir-sidebar.window="mobileOpen = !mobileOpen"
    @keydown.escape.window="mobileOpen = false"
    class="contents"
>
    <!-- Mobile Backdrop -->
    <div
        x-show="mobileOpen"
        x-cloak
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="mobileOpen = false"
        class="fixed inset-0 z-30 bg-darkbrown-950/60 lg:hidden"
        aria-hidden="true"
    ></div>

    <!-- Owner Sidebar -->
    <aside
        aria-label="Navigasi Portal Owner"
        :class="{
            '-translate-x-full lg:translate-x-0': !mobileOpen,
            'translate-x-0': mobileOpen,
            'lg:w-64': !collapsed,
            'lg:w-[72px]': collapsed,
        }"
        class="fixed inset-y-0 left-0 lg:sticky lg:top-0 z-40 h-dvh w-72 max-w-[85vw] lg:max-w-none shrink-0 flex flex-col bg-[#1E1A10] text-wheat-300 border-r border-darkbrown-700/70 transition-[width,transform] duration-200 ease-out"
    >
        <!-- Brand Header -->
        <div
            class="h-16 px-3 flex items-center border-b border-darkbrown-700/70 shrink-0"
            :class="isExpanded ? 'justify-between gap-2' : 'justify-center'"
        >
            <a
                href="{{ route('vendor.owner.dashboard') }}"
                wire:navigate
                @click="if (window.innerWidth < 1024) mobileOpen = false"
                class="flex items-center gap-2.5 min-w-0 rounded-lg p-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sunglow-300"
                title="Tendaku — Portal Owner"
            >
                <span class="w-9 h-9 rounded-lg bg-darkbrown-700/60 border border-darkbrown-700 flex items-center justify-center shrink-0">
                    <img
                        src="{{ asset('images/Logo.svg') }}"
                        alt="Tendaku"
                        width="28"
                        height="28"
                        class="w-7 h-7 object-contain block brightness-150"
                    >
                </span>

                <span x-show="isExpanded" class="min-w-0 flex flex-col">
                    <span class="text-sm font-bold tracking-tight text-wheat-100 leading-tight truncate">
                        Tendaku
                    </span>
                    <span class="text-[11px] font-medium text-goldenrod-500 leading-tight truncate mt-0.5">
                        Portal Owner
                    </span>
                </span>
            </a>

            <!-- Desktop Collapse Button (Shown when expanded) -->
            <button
                type="button"
                x-show="!collapsed"
                @click="toggleCollapse()"
                class="hidden lg:inline-flex items-center justify-center w-8 h-8 rounded-lg text-darkbrown-300 hover:bg-darkbrown-700 hover:text-wheat-100 transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sunglow-300 shrink-0"
                title="Ciutkan sidebar"
                aria-label="Ciutkan sidebar"
            >
                <x-icon name="panel-left-close" stroke-width="1.75" class="w-4 h-4" />
            </button>

            <!-- Mobile Close Button -->
            <button
                type="button"
                @click="mobileOpen = false"
                class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg text-wheat-300 hover:bg-darkbrown-700 hover:text-wheat-100 transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sunglow-300 shrink-0"
                aria-label="Tutup menu sidebar"
            >
                <x-icon name="x" stroke-width="1.75" class="w-5 h-5" />
            </button>
        </div>

        <!-- Navigation -->
        <nav
            :class="isExpanded ? 'overflow-y-auto overflow-x-hidden' : 'overflow-visible'"
            class="flex-1 py-4 px-2.5 space-y-1.5 scrollbar-none"
            aria-label="Menu Utama Owner"
        >
            <!-- 1. Dashboard -->
            <x-vendor.sidebar-item
                label="Dashboard"
                icon="layout-dashboard"
                :href="route('vendor.owner.dashboard')"
                :active="$isDashboardActive"
            />

            <!-- 2-4. Collapsible Menu Groups -->
            @foreach ($menuGroups as $group)
                <x-vendor.sidebar-group
                    :id="$group['id']"
                    :label="$group['label']"
                    :icon="$group['icon']"
                    :active="$group['active']"
                    :items="$group['items']"
                />
            @endforeach
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-2.5 border-t border-darkbrown-700/70 shrink-0 space-y-1">
            <!-- Desktop Expand Button (Shown only when collapsed) -->
            <button
                type="button"
                x-show="collapsed"
                x-cloak
                @click="toggleCollapse()"
                class="hidden lg:flex w-full min-h-[44px] items-center justify-center rounded-lg text-wheat-300 hover:bg-darkbrown-700 hover:text-wheat-100 transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sunglow-300"
                title="Perluas sidebar"
                aria-label="Perluas sidebar"
            >
                <x-icon name="panel-left-open" stroke-width="1.75" class="w-[18px] h-[18px]" />
            </button>

            <!-- Keluar Akun Button -->
            <div class="relative group/logout">
                <button
                    type="button"
                    @click="if (window.innerWidth < 1024) mobileOpen = false; $dispatch('open-logout-modal')"
                    class="w-full flex items-center min-h-[44px] py-2.5 rounded-lg text-[13.5px] leading-5 font-medium text-wheat-300 hover:bg-red-950/60 hover:text-red-200 transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sunglow-300 focus-visible:ring-offset-1 focus-visible:ring-offset-[#1E1A10] cursor-pointer"
                    :class="isExpanded ? 'px-3 gap-3 justify-start' : 'px-0 justify-center'"
                    title="Keluar Akun"
                    aria-label="Keluar Akun"
                >
                    <x-icon name="log-out" stroke-width="1.75" class="w-[18px] h-[18px] text-wheat-300/80 group-hover/logout:text-red-300 shrink-0" />
                    <span
                        x-show="isExpanded"
                        class="truncate whitespace-nowrap"
                    >
                        Keluar Akun
                    </span>
                </button>

                <div
                    x-show="!isExpanded"
                    x-cloak
                    role="tooltip"
                    class="hidden lg:group-hover/logout:block lg:group-focus-within/logout:block absolute left-full top-1/2 -translate-y-1/2 ml-2.5 px-2.5 py-1.5 rounded-md bg-darkbrown-900 text-wheat-100 text-xs font-medium whitespace-nowrap border border-darkbrown-700 z-50 pointer-events-none"
                >
                    Keluar Akun
                </div>
            </div>
        </div>
    </aside>
</div>
