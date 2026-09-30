@props(['role' => 'customer'])

@if ($role === 'vendor')
    <aside class="w-full sm:w-64 bg-slate-900 text-white flex-shrink-0 p-4">
        <div class="text-xl font-bold mb-6 tracking-wide">Tendaku Vendor</div>
        <nav class="space-y-2">
            <a href="#" class="block py-2 px-3 rounded hover:bg-slate-800">Dashboard & POS</a>
            <a href="#" class="block py-2 px-3 rounded hover:bg-slate-800">Kelola Produk & Unit</a>
            <a href="#" class="block py-2 px-3 rounded hover:bg-slate-800">Serah Terima & Pengembalian</a>
        </nav>
    </aside>
@else
    <nav class="bg-white border-b border-gray-200 px-4 py-3 sm:px-6 flex justify-between items-center">
        <div class="text-xl font-bold text-emerald-600">Tendaku</div>
        <div class="space-x-4 text-sm font-medium">
            <a href="#" class="text-gray-600 hover:text-gray-900">Katalog</a>
            <a href="#" class="text-gray-600 hover:text-gray-900">Pesanan Saya</a>
        </div>
    </nav>
@endif
