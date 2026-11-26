@props([
    // Nama file di public/images, mis. 'forest_spring.jpeg'
    'image',
])

{{-- Gambar dekoratif di sisi kanan header halaman. Pakai lewat slot "headerBg". --}}
<div
    aria-hidden="true"
    class="pointer-events-none absolute inset-y-0 right-0 hidden w-[55%] md:block lg:w-[48%] [-webkit-mask-image:linear-gradient(to_right,transparent,#000_70%)] [mask-image:linear-gradient(to_right,transparent,#000_70%)]"
>
    <img
        src="{{ asset('images/' . $image) }}"
        alt=""
        width="800"
        height="448"
        decoding="async"
        onerror="this.parentElement.style.display='none'"
        class="h-full w-full object-cover object-[center_62%] opacity-50"
    />
    <div class="absolute inset-0 bg-gradient-to-t from-[#FDF6E3]/70 via-transparent to-transparent"></div>
</div>
