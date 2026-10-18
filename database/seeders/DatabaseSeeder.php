<?php

namespace Database\Seeders;

use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalDetail;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with realistic multi-vendor data.
     */
    public function run(): void
    {
        // 1. USERS
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@tendaku.com'],
            [
                'name' => 'Super Administrator Tendaku',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ]
        );

        // Pelanggan 1 & 2
        $customer1 = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test Customer',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'phone' => '081234567800',
                'email_verified_at' => now(),
            ]
        );

        $customer2 = User::updateOrCreate(
            ['email' => 'byan@example.com'],
            [
                'name' => 'Byan Customer',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'phone' => '089876543210',
                'email_verified_at' => now(),
            ]
        );

        // -------------------------------------------------------------
        // 2. VENDOR 1 (Tendaku Central Camp - Malang)
        // -------------------------------------------------------------
        $vendor1 = Vendor::updateOrCreate(
            ['slug' => 'tendaku-store-central'],
            [
                'name' => 'Tendaku Store Central',
                'description' => 'Penyedia utama alat camping dan outdoor gear terlengkap di Malang.',
                'phone' => '081234567890',
                'email' => 'store@tendaku.com',
                'address' => 'Jl. Outdoor Adventure No. 45, Lowokwaru',
                'city' => 'Malang',
                'logo_url' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=400&q=80',
                'status' => 'active',
                'approved_at' => now(),
                'approved_by' => $superAdmin->id,
            ]
        );

        // Owner Vendor 1
        $owner1 = User::updateOrCreate(
            ['email' => 'owner@tendaku.com'],
            [
                'name' => 'Arsya Owner Tendaku',
                'password' => Hash::make('password'),
                'role' => 'vendor_owner',
                'vendor_id' => $vendor1->id,
                'phone' => '081122334455',
                'email_verified_at' => now(),
            ]
        );

        // Admin / Kasir Vendor 1
        $kasir1 = User::updateOrCreate(
            ['email' => 'kasir@tendaku.com'],
            [
                'name' => 'Kasir Tendaku',
                'password' => Hash::make('password'),
                'role' => 'vendor_admin',
                'vendor_id' => $vendor1->id,
                'phone' => '081122334466',
                'email_verified_at' => now(),
            ]
        );

        // Akun Vendor bawaan develop (agar rekan tim yang login pakai vendor@tendaku.com tetap bisa masuk)
        User::updateOrCreate(
            ['email' => 'vendor@tendaku.com'],
            [
                'name' => 'Vendor Admin',
                'password' => Hash::make('password'),
                'role' => 'vendor_owner',
                'vendor_id' => $vendor1->id,
                'email_verified_at' => now(),
            ]
        );

        // -------------------------------------------------------------
        // 3. VENDOR 2 (Mountaineer Outdoor Gear - Batu)
        // -------------------------------------------------------------
        $vendor2 = Vendor::updateOrCreate(
            ['slug' => 'mountaineer-outdoor-gear'],
            [
                'name' => 'Mountaineer Outdoor Gear',
                'description' => 'Spesialis sewa perlengkapan pendakian gunung dan ekspedisi di Kota Batu.',
                'phone' => '082233445566',
                'email' => 'contact@mountaineer.id',
                'address' => 'Jl. Panglima Sudirman No. 88',
                'city' => 'Batu',
                'logo_url' => 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?auto=format&fit=crop&w=400&q=80',
                'status' => 'active',
                'approved_at' => now(),
                'approved_by' => $superAdmin->id,
            ]
        );

        // Owner Vendor 2
        $owner2 = User::updateOrCreate(
            ['email' => 'owner2@tendaku.com'],
            [
                'name' => 'Boby Owner Mountaineer',
                'password' => Hash::make('password'),
                'role' => 'vendor_owner',
                'vendor_id' => $vendor2->id,
                'phone' => '082233445577',
                'email_verified_at' => now(),
            ]
        );

        // -------------------------------------------------------------
        // 4. KATEGORI ALAT VENDOR 1
        // -------------------------------------------------------------
        $v1Categories = [
            'tenda-canopy' => ['name' => 'Tenda & Canopy', 'icon' => 'tent', 'description' => 'Tenda dome, ultralight, dan flysheet perlindungan cuaca.'],
            'sleeping-gear' => ['name' => 'Sleeping Gear', 'icon' => 'moon', 'description' => 'Kantong tidur hangat, matras angin, dan alas tidur.'],
            'cooking-equipment' => ['name' => 'Cooking Equipment', 'icon' => 'flame', 'description' => 'Kompor lapangan, nesting set, dan alat makan outdoor.'],
            'lighting-power' => ['name' => 'Lighting & Power', 'icon' => 'sun', 'description' => 'Lampu tenda LED, headlamp, dan powerbank cadangan.'],
            'backpack-carrier' => ['name' => 'Backpack & Carrier', 'icon' => 'bag', 'description' => 'Tas carrier ergonomis kapasitas 45L hingga 80L.'],
        ];

        $v1CategoryModels = [];
        foreach ($v1Categories as $slug => $data) {
            $v1CategoryModels[$slug] = ItemCategory::withoutGlobalScope('vendor')->updateOrCreate(
                ['vendor_id' => $vendor1->id, 'slug' => $slug],
                [
                    'name' => $data['name'],
                    'icon' => $data['icon'],
                    'description' => $data['description'],
                ]
            );
        }

        // Kategori untuk Vendor 2
        $v2CategoryModels = [
            'tenda-gunung' => ItemCategory::withoutGlobalScope('vendor')->updateOrCreate(
                ['vendor_id' => $vendor2->id, 'slug' => 'tenda-gunung'],
                ['name' => 'Tenda Ekspedisi', 'icon' => 'tent', 'description' => 'Tenda 4-musim badai gunung.']
            ),
            'sleeping-bag' => ItemCategory::withoutGlobalScope('vendor')->updateOrCreate(
                ['vendor_id' => $vendor2->id, 'slug' => 'sleeping-bag'],
                ['name' => 'Sleeping Gear Pro', 'icon' => 'moon', 'description' => 'Sleeping bag bulu angsa tahan minus 5 derajat.']
            ),
        ];

        // -------------------------------------------------------------
        // 5. MASTER PRODUK VENDOR 1
        // -------------------------------------------------------------
        $v1ItemsData = [
            [
                'category_slug' => 'tenda-canopy',
                'name' => 'Tenda Consina Magnum 4 Person Ultra',
                'slug' => 'tenda-consina-magnum-4-person-ultra',
                'description' => 'Tenda kemping kapasitas 4 orang dengan spesifikasi tinggi. Double layer Polyester PU 3000mm tahan cuaca ekstrem.',
                'daily_rate' => 45000,
                'late_fee_per_day' => 25000,
                'image_url' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=1000&q=80',
                'is_active' => true,
            ],
            [
                'category_slug' => 'tenda-canopy',
                'name' => 'Tenda Naturehike Mongar 2 Ultralight',
                'slug' => 'tenda-naturehike-mongar-2-ultralight',
                'description' => 'Tenda ultralight kapasitas 2 orang berbahan Silicone Coated 20D Nylon. Bobot sangat ringan hanya 1.8 kg.',
                'daily_rate' => 60000,
                'late_fee_per_day' => 30000,
                'image_url' => 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?auto=format&fit=crop&w=1000&q=80',
                'is_active' => true,
            ],
            [
                'category_slug' => 'sleeping-gear',
                'name' => 'Sleeping Bag Eiger Compress 800 Warm',
                'slug' => 'sleeping-bag-eiger-compress-800-warm',
                'description' => 'Kantong tidur hangat dilapisi dacron sintetis 300g/m² dengan toleransi suhu nyaman hingga 5°C.',
                'daily_rate' => 15000,
                'late_fee_per_day' => 10000,
                'image_url' => 'https://images.unsplash.com/photo-1526772662000-3f88f10405ff?auto=format&fit=crop&w=1000&q=80',
                'is_active' => true,
            ],
            [
                'category_slug' => 'cooking-equipment',
                'name' => 'Kompor Portable Mawar Windproof Kovar',
                'slug' => 'kompor-portable-mawar-windproof-kovar',
                'description' => 'Kompor mawar lipat anti angin dilengkapi pelindung bodi stainless, hemat gas dan cepat mendidihkan air.',
                'daily_rate' => 12000,
                'late_fee_per_day' => 8000,
                'image_url' => 'https://images.unsplash.com/photo-1517824806704-9040b037703b?auto=format&fit=crop&w=1000&q=80',
                'is_active' => true,
            ],
            [
                'category_slug' => 'cooking-equipment',
                'name' => 'Nesting Cooking Set DS-308 (4 in 1)',
                'slug' => 'nesting-cooking-set-ds-308-4-in-1',
                'description' => 'Set alat masak lengkap aluminium anodized: 2 panci, 1 wajan lipat, 1 teko air hangat, plus spons cuci.',
                'daily_rate' => 20000,
                'late_fee_per_day' => 10000,
                'image_url' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=1000&q=80',
                'is_active' => true,
            ],
            [
                'category_slug' => 'backpack-carrier',
                'name' => 'Carrier Osprey Atmosphere AG 65L Premium',
                'slug' => 'carrier-osprey-atmosphere-ag-65l-premium',
                'description' => 'Tas carrier legendaris dengan sistem suspensi Anti-Gravity 3D mesh. Nyaman memuat perlengkapan hingga 25kg.',
                'daily_rate' => 50000,
                'late_fee_per_day' => 25000,
                'image_url' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=1000&q=80',
                'is_active' => true,
            ],
            [
                'category_slug' => 'lighting-power',
                'name' => 'Lampu Tenda Gantung LED Solar Rechargeable',
                'slug' => 'lampu-tenda-gantung-led-solar-rechargeable',
                'description' => 'Lampu gantung fleksibel 300 Lumens dengan port pengisi daya USB dan panel surya darurat. Tahan hingga 12 jam.',
                'daily_rate' => 10000,
                'late_fee_per_day' => 5000,
                'image_url' => 'https://images.unsplash.com/photo-1508873696983-2df515122519?auto=format&fit=crop&w=1000&q=80',
                'is_active' => true,
            ],
        ];

        $v1CreatedItems = [];
        foreach ($v1ItemsData as $itemData) {
            $category = $v1CategoryModels[$itemData['category_slug']];

            $item = MasterItem::withoutGlobalScope('vendor')->updateOrCreate(
                [
                    'vendor_id' => $vendor1->id,
                    'slug' => $itemData['slug'],
                ],
                [
                    'category_id' => $category->id,
                    'name' => $itemData['name'],
                    'description' => $itemData['description'],
                    'daily_rate' => $itemData['daily_rate'],
                    'deposit_amount' => 0,
                    'late_fee_per_day' => $itemData['late_fee_per_day'],
                    'image_url' => $itemData['image_url'],
                    'is_active' => true,
                ]
            );

            $v1CreatedItems[] = $item;
        }

        // 6. MASTER ITEM VENDOR 2
        // Produk untuk Vendor 2 (isolasi multi-tenant)
        $v2Item = MasterItem::withoutGlobalScope('vendor')->updateOrCreate(
            ['vendor_id' => $vendor2->id, 'slug' => 'tenda-arei-merapi-4p'],
            [
                'category_id' => $v2CategoryModels['tenda-gunung']->id,
                'name' => 'Tenda Arei Merapi 4P Expedition',
                'description' => 'Tenda kokoh dengan frame alloy tahan badai.',
                'daily_rate' => 55000,
                'deposit_amount' => 0,
                'late_fee_per_day' => 25000,
                'image_url' => 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?auto=format&fit=crop&w=1000&q=80',
                'is_active' => true,
            ]
        );

        // -------------------------------------------------------------
        // 6. UNIT FISIK SERIALIZED DENGAN VARIASI STATUS & KONDISI
        // -------------------------------------------------------------
        $unitPresets = [
            ['code_suffix' => '01', 'barcode' => 'BC-100001', 'status' => 'available', 'condition' => 'excellent', 'age_months' => 4],
            ['code_suffix' => '02', 'barcode' => 'BC-100002', 'status' => 'available', 'condition' => 'good', 'age_months' => 8],
            ['code_suffix' => '03', 'barcode' => 'BC-100003', 'status' => 'rented', 'condition' => 'good', 'age_months' => 12],
            ['code_suffix' => '04', 'barcode' => 'BC-100004', 'status' => 'maintenance', 'condition' => 'fair', 'age_months' => 15],
            ['code_suffix' => '05', 'barcode' => 'BC-100005', 'status' => 'available', 'condition' => 'damaged', 'age_months' => 18], // aset rusak
        ];

        $allV1Units = [];
        foreach ($v1CreatedItems as $idx => $item) {
            $prefix = strtoupper(substr(Str::slug($item->name, ''), 0, 4));

            foreach ($unitPresets as $pIdx => $preset) {
                $unitCode = "{$prefix}-".str_pad((string) ($pIdx + 1), 3, '0', STR_PAD_LEFT);
                $barcode = 'BC-'.($vendor1->id * 100000 + $idx * 10 + $pIdx + 1);

                $unit = ItemUnit::withoutGlobalScope('vendor')->updateOrCreate(
                    ['vendor_id' => $vendor1->id, 'unit_code' => $unitCode],
                    [
                        'master_item_id' => $item->id,
                        'barcode' => $barcode,
                        'status' => $preset['status'],
                        'condition' => $preset['condition'],
                        'purchase_date' => Carbon::now()->subMonths($preset['age_months'])->format('Y-m-d'),
                        'purchase_price' => $item->daily_rate * 25,
                        'notes' => 'Unit resmi Tendaku Store.',
                    ]
                );

                $allV1Units[] = $unit;
            }
        }

        // Tambah 1 unit dengan kondisi lost (aset hilang untuk testing)
        $lostUnit = ItemUnit::withoutGlobalScope('vendor')->updateOrCreate(
            ['vendor_id' => $vendor1->id, 'unit_code' => 'KMP-LOST'],
            [
                'master_item_id' => $v1CreatedItems[3]->id, // Kompor
                'barcode' => 'BC-999999',
                'status' => 'retired',
                'condition' => 'lost',
                'purchase_date' => Carbon::now()->subMonths(10)->format('Y-m-d'),
                'purchase_price' => 150000,
                'notes' => 'Hilang saat kegiatan camping di Ranu Kumbolo.',
            ]
        );

        // Unit untuk Vendor 2
        ItemUnit::withoutGlobalScope('vendor')->updateOrCreate(
            ['vendor_id' => $vendor2->id, 'unit_code' => 'AREI-001'],
            [
                'master_item_id' => $v2Item->id,
                'barcode' => 'BC-200001',
                'status' => 'available',
                'condition' => 'excellent',
                'purchase_date' => Carbon::now()->subMonths(2)->format('Y-m-d'),
                'purchase_price' => 1200000,
                'notes' => 'Unit eksklusif vendor 2.',
            ]
        );

        // -------------------------------------------------------------
        // 7. CONTOH TRANSAKSI REALISTIS UNTUK ANALITIK & KETERLAMBATAN
        // -------------------------------------------------------------

        // Transaksi 1: SELESAI HARI INI (Omzet Harian Terhitung)
        $rentalToday = Rental::withoutGlobalScope('vendor')->updateOrCreate(
            ['booking_code' => 'TND-TODAY-001'],
            [
                'vendor_id' => $vendor1->id,
                'user_id' => $customer1->id,
                'created_by' => $owner1->id,
                'picked_up_by' => $kasir1->id,
                'returned_by' => $kasir1->id,
                'pickup_date' => Carbon::today()->subDays(2),
                'return_date' => Carbon::today(),
                'actual_return_date' => Carbon::today(),
                'picked_up_at' => Carbon::today()->subDays(2)->setHour(10),
                'returned_at' => Carbon::today()->setHour(15),
                'pickup_method' => 'self_pickup',
                'rental_days' => 2,
                'subtotal' => 90000,
                'delivery_fee' => 0,
                'discount_amount' => 0,
                'total_deposit' => 0,
                'grand_total' => 90000,
                'total_late_fee' => 0,
                'total_damage_fee' => 0,
                'final_total' => 90000,
                'status' => 'completed',
                'created_at' => Carbon::today(),
            ]
        );

        RentalDetail::updateOrCreate(
            ['rental_id' => $rentalToday->id, 'master_item_id' => $v1CreatedItems[0]->id],
            [
                'item_unit_id' => $allV1Units[0]->id,
                'daily_rate_snapshot' => 45000,
                'late_fee_per_day_snapshot' => 25000,
                'condition_before' => 'excellent',
                'condition_after' => 'excellent',
                'returned_at' => Carbon::today(),
                'late_days' => 0,
                'late_fee' => 0,
                'damage_fee' => 0,
                'is_returned' => true,
            ]
        );

        Payment::withoutGlobalScope('vendor')->updateOrCreate(
            ['rental_id' => $rentalToday->id, 'payment_type' => 'settlement'],
            [
                'vendor_id' => $vendor1->id,
                'received_by' => $kasir1->id,
                'payment_method' => 'cash',
                'amount' => 90000,
                'status' => 'paid',
                'paid_at' => Carbon::today(),
            ]
        );

        // Transaksi 2: SELESAI BULAN INI (Historis 7 Hari Lalu)
        $rentalPast = Rental::withoutGlobalScope('vendor')->updateOrCreate(
            ['booking_code' => 'TND-HIST-002'],
            [
                'vendor_id' => $vendor1->id,
                'user_id' => $customer2->id,
                'created_by' => $owner1->id,
                'picked_up_by' => $kasir1->id,
                'returned_by' => $kasir1->id,
                'pickup_date' => Carbon::now()->subDays(7),
                'return_date' => Carbon::now()->subDays(5),
                'actual_return_date' => Carbon::now()->subDays(5),
                'picked_up_at' => Carbon::now()->subDays(7)->setHour(9),
                'returned_at' => Carbon::now()->subDays(5)->setHour(14),
                'pickup_method' => 'self_pickup',
                'rental_days' => 2,
                'subtotal' => 120000,
                'grand_total' => 120000,
                'final_total' => 120000,
                'status' => 'completed',
                'created_at' => Carbon::now()->subDays(7),
            ]
        );

        RentalDetail::updateOrCreate(
            ['rental_id' => $rentalPast->id, 'master_item_id' => $v1CreatedItems[1]->id],
            [
                'item_unit_id' => $allV1Units[5]->id,
                'daily_rate_snapshot' => 60000,
                'late_fee_per_day_snapshot' => 30000,
                'condition_before' => 'good',
                'condition_after' => 'good',
                'returned_at' => Carbon::now()->subDays(5),
                'is_returned' => true,
            ]
        );

        Payment::withoutGlobalScope('vendor')->updateOrCreate(
            ['rental_id' => $rentalPast->id, 'payment_type' => 'settlement'],
            [
                'vendor_id' => $vendor1->id,
                'received_by' => $kasir1->id,
                'payment_method' => 'midtrans_snap',
                'amount' => 120000,
                'status' => 'paid',
                'paid_at' => Carbon::now()->subDays(7),
            ]
        );

        // Transaksi 3: AKTIF TERLAMBAT PENGEMBALIAN (Overdue Monitoring)
        $rentalLate = Rental::withoutGlobalScope('vendor')->updateOrCreate(
            ['booking_code' => 'TND-LATE-003'],
            [
                'vendor_id' => $vendor1->id,
                'user_id' => $customer1->id,
                'created_by' => $kasir1->id,
                'picked_up_by' => $kasir1->id,
                'pickup_date' => Carbon::today()->subDays(5),
                'return_date' => Carbon::today()->subDays(2), // Harusnya kembali 2 hari lalu
                'picked_up_at' => Carbon::today()->subDays(5)->setHour(11),
                'pickup_method' => 'self_pickup',
                'rental_days' => 3,
                'subtotal' => 135000,
                'grand_total' => 135000,
                'total_late_fee' => 50000, // Denda 2 hari x 25.000
                'final_total' => 185000,
                'status' => 'picked_up', // Masih belum kembali
                'created_at' => Carbon::today()->subDays(5),
            ]
        );

        RentalDetail::updateOrCreate(
            ['rental_id' => $rentalLate->id, 'master_item_id' => $v1CreatedItems[0]->id],
            [
                'item_unit_id' => $allV1Units[2]->id, // Unit status rented
                'daily_rate_snapshot' => 45000,
                'late_fee_per_day_snapshot' => 25000,
                'condition_before' => 'good',
                'late_days' => 2,
                'late_fee' => 50000,
                'is_returned' => false,
            ]
        );

        // Transaksi 4: SIAP PICKUP HARI INI (Untuk Kasir Pickup & POS Persiapan)
        $rentalReadyPickup = Rental::withoutGlobalScope('vendor')->updateOrCreate(
            ['booking_code' => 'TND-PCK-004'],
            [
                'vendor_id' => $vendor1->id,
                'user_id' => $customer1->id,
                'created_by' => $customer1->id,
                'pickup_date' => Carbon::today(),
                'return_date' => Carbon::today()->addDays(2),
                'pickup_method' => 'self_pickup',
                'rental_days' => 2,
                'subtotal' => 120000,
                'grand_total' => 120000,
                'final_total' => 120000,
                'status' => 'confirmed',
                'ktp_collateral_status' => 'pending',
                'created_at' => Carbon::today(),
            ]
        );

        RentalDetail::updateOrCreate(
            ['rental_id' => $rentalReadyPickup->id, 'master_item_id' => $v1CreatedItems[1]->id],
            [
                'item_unit_id' => $allV1Units[3]->id,
                'daily_rate_snapshot' => 60000,
                'late_fee_per_day_snapshot' => 30000,
                'condition_before' => 'excellent',
                'is_returned' => false,
            ]
        );

        Payment::withoutGlobalScope('vendor')->updateOrCreate(
            ['rental_id' => $rentalReadyPickup->id, 'payment_type' => 'settlement'],
            [
                'vendor_id' => $vendor1->id,
                'payment_method' => 'midtrans_snap',
                'amount' => 120000,
                'status' => 'paid',
                'paid_at' => Carbon::today(),
            ]
        );

        // Transaksi 5: SEDANG DISEWA & HARUS KEMBALI HARI INI (Untuk Return & Inspeksi Kasir)
        $rentalReturnToday = Rental::withoutGlobalScope('vendor')->updateOrCreate(
            ['booking_code' => 'TND-RTN-005'],
            [
                'vendor_id' => $vendor1->id,
                'user_id' => $customer2->id,
                'created_by' => $kasir1->id,
                'picked_up_by' => $kasir1->id,
                'pickup_date' => Carbon::today()->subDays(2),
                'return_date' => Carbon::today(),
                'picked_up_at' => Carbon::today()->subDays(2)->setHour(10),
                'pickup_method' => 'self_pickup',
                'rental_days' => 2,
                'subtotal' => 100000,
                'grand_total' => 100000,
                'final_total' => 100000,
                'status' => 'picked_up',
                'ktp_collateral_status' => 'held',
                'ktp_received_at' => Carbon::today()->subDays(2)->setHour(10),
                'ktp_collateral_notes' => 'KTP Bunga Tersimpan di Loker Jaminan Kasir #02',
                'created_at' => Carbon::today()->subDays(2),
            ]
        );

        RentalDetail::updateOrCreate(
            ['rental_id' => $rentalReturnToday->id, 'master_item_id' => $v1CreatedItems[4]->id],
            [
                'item_unit_id' => $allV1Units[8]->id,
                'daily_rate_snapshot' => 50000,
                'late_fee_per_day_snapshot' => 25000,
                'condition_before' => 'good',
                'is_returned' => false,
            ]
        );

        // Transaksi 6: PENDING VALIDASI (Customer Baru Checkout, Perlu Validasi Barcode Kasir Terlebih Dahulu)
        $rentalPending = Rental::withoutGlobalScope('vendor')->updateOrCreate(
            ['booking_code' => 'TND-VAL-006'],
            [
                'vendor_id' => $vendor1->id,
                'user_id' => $customer1->id,
                'created_by' => $customer1->id,
                'pickup_date' => Carbon::today(),
                'return_date' => Carbon::today()->addDays(2),
                'pickup_method' => 'self_pickup',
                'rental_days' => 2,
                'subtotal' => 90000,
                'grand_total' => 90000,
                'final_total' => 90000,
                'status' => 'pending', // Pending validasi kasir
                'ktp_collateral_status' => 'pending',
                'created_at' => Carbon::today(),
            ]
        );

        RentalDetail::updateOrCreate(
            ['rental_id' => $rentalPending->id, 'master_item_id' => $v1CreatedItems[0]->id],
            [
                'item_unit_id' => $allV1Units[1]->id, // Unit sudah dialokasikan ke pesanan (misal unit 002 / BC-100002)
                'daily_rate_snapshot' => 45000,
                'late_fee_per_day_snapshot' => 25000,
                'condition_before' => $allV1Units[1]->condition,
                'is_returned' => false,
            ]
        );

        // Transaksi Vendor 2 (untuk membuktikan isolasi vendor_id)
        $rentalV2 = Rental::withoutGlobalScope('vendor')->updateOrCreate(
            ['booking_code' => 'MNT-ISO-001'],
            [
                'vendor_id' => $vendor2->id,
                'user_id' => $customer2->id,
                'pickup_date' => Carbon::today(),
                'return_date' => Carbon::today()->addDays(2),
                'rental_days' => 2,
                'subtotal' => 110000,
                'grand_total' => 110000,
                'final_total' => 110000,
                'status' => 'confirmed',
                'created_at' => Carbon::today(),
            ]
        );
    }
}
