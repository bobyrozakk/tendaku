@props(['size' => 'md', 'withText' => true])

@php
    $sizes = [
        'sm' => 'h-7',
        'md' => 'h-9',
        'lg' => 'h-12',
        'xl' => 'h-16',
    ];
    $iconSize = $sizes[$size] ?? 'h-9';
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 font-bold tracking-tight select-none']) }}>
    <!-- Tendaku Camping Tent & Mountain Badge -->
    <svg class="{{ $iconSize }} w-auto aspect-square shrink-0 drop-shadow-sm transition-transform duration-200 hover:scale-105" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
        <!-- Rounded Badge Background -->
        <rect width="48" height="48" rx="13" fill="#2B2202"/>
        
        <!-- Warm Morning Glow / Sun in Background -->
        <circle cx="24" cy="20" r="11" fill="url(#sunGlowGrad)"/>
        
        <!-- Mountain Silhouette in distance -->
        <path d="M10 34L19 22L27 34H10Z" fill="#3C311B" opacity="0.6"/>
        <path d="M22 34L30 20L39 34H22Z" fill="#3C311B" opacity="0.4"/>
        
        <!-- Forest Pine Tree (Avocado) -->
        <path d="M13 35L17 28L15 28L18 23L16 23L19 19L22 23L20 23L23 28L21 28L25 35H13Z" fill="#6C8B08"/>
        
        <!-- Camping Tent Body (Wheat & Goldenrod) -->
        <!-- Tent Left Slant -->
        <path d="M28 17L19 35H32L28 17Z" fill="#F9E3B6"/>
        <!-- Tent Right Shadow Flap (Goldenrod) -->
        <path d="M28 17L32 35H39L33 22L28 17Z" fill="#D5A007"/>
        <!-- Tent Entrance Opening (Drab Dark Brown) -->
        <path d="M26 26L22 35H30L26 26Z" fill="#2B2202"/>
        <!-- Tent Ridge / Guyline (Sunglow) -->
        <path d="M28 17L39 35" stroke="#FBCE6B" stroke-width="1.5" stroke-linecap="round"/>
        <path d="M19 35L17 37" stroke="#FBCE6B" stroke-width="1.5" stroke-linecap="round"/>
        <path d="M39 35L41 37" stroke="#FBCE6B" stroke-width="1.5" stroke-linecap="round"/>
        
        <!-- Ground Turf line (Avocado) -->
        <rect x="7" y="35" width="34" height="2.5" rx="1.25" fill="#6C8B08"/>

        <!-- Gradient Definitions -->
        <defs>
            <linearGradient id="sunGlowGrad" x1="24" y1="9" x2="24" y2="31" gradientUnits="userSpaceOnUse">
                <stop stop-color="#FBCE6B"/>
                <stop offset="1" stop-color="#D5A007" stop-opacity="0.3"/>
            </linearGradient>
        </defs>
    </svg>

    @if ($withText)
        <span class="flex flex-col leading-none">
            <span class="text-lg font-black tracking-tight text-darkbrown-800">
                TENDA<span class="text-avocado-500">KU</span>
            </span>
            <span class="text-[9px] font-semibold uppercase tracking-widest text-goldenrod-700">
                Outdoor Rental
            </span>
        </span>
    @endif
</div>
