<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-extrabold text-2xl text-darkbrown-800 leading-tight">
                    {{ __('Dashboard Tendaku') }}
                </h2>
                <p class="text-xs text-darkbrown-500 mt-0.5">Selamat datang di sistem manajemen rental perlengkapan camping</p>
            </div>
            <a href="{{ url('/design-system') }}" class="btn-tendaku-accent !py-2 !px-3.5 text-xs font-bold">
                🎨 Lihat Template Desain
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card 
                title="Total Persewaan Aktif"
                value="14 Unit"
                trend="+2 hari ini"
                :trendUp="true"
                description="Status disewa dan di lapangan"
                color="avocado"
            />
            <x-stat-card 
                title="Booking Menunggu Diambil"
                value="6 Pesanan"
                description="Siap diserahterimakan"
                color="goldenrod"
            />
            <x-stat-card 
                title="Stok Unit Siap Sewa"
                value="78 Unit"
                description="Kondisi bersih & kering"
                color="sunglow"
            />
            <x-stat-card 
                title="Jatuh Tempo Pengembalian"
                value="3 Transaksi"
                trend="Perlu cek kondisi"
                :trendUp="false"
                description="Hari ini sebelum 18:00 WIB"
                color="dark"
            />
        </div>

        <!-- Action Quick Links -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-card title="Portal Vendor & POS Kasir" subtitle="Kelola transaksi langsung di toko dan serah terima unit">
                <p class="text-xs text-darkbrown-600 mb-4">
                    Catat persewaan offline baru, cetak invoice nota, dan periksa kondisi tenda saat dikembalikan oleh pelanggan.
                </p>
                <div class="flex items-center gap-2">
                    <x-primary-button variant="avocado" size="sm">
                        Buka POS Kasir
                    </x-primary-button>
                    <x-secondary-button size="sm">
                        Kelola Inventori Alat
                    </x-secondary-button>
                </div>
            </x-card>

            <x-card title="Katalog & Pemesanan Pelanggan" subtitle="Tampilan publik untuk calon penyewa alat camping">
                <p class="text-xs text-darkbrown-600 mb-4">
                    Pratinjau tampilan katalog alat camping, pemilihan tanggal sewa, serta alur checkout dan pembayaran online.
                </p>
                <div class="flex items-center gap-2">
                    <a href="{{ url('/') }}" class="btn-tendaku-primary !py-1.5 !px-3 text-xs">
                        Lihat Beranda Pelanggan
                    </a>
                    <a href="{{ url('/design-system') }}" class="btn-tendaku-secondary !py-1.5 !px-3 text-xs font-bold">
                        Panduan Template Desain
                    </a>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
