<?php

namespace Database\Seeders;

use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. User
        $user = User::create([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        // 2. Vendor
        $vendor = Vendor::create([
            'name' => 'Tendaku Store Central',
            'slug' => 'tendaku-store-central',
            'description' => 'Penyedia utama alat camping dan outdoor gear terlengkap di Malang.',
            'phone' => '081234567890',
            'email' => 'store@tendaku.com',
            'address' => 'Jl. Outdoor Adventure No. 45, Lowokwaru',
            'city' => 'Malang',
            'logo_url' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=400&q=80',
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);

        // 3. Categories
        $catTenda = ItemCategory::create([
            'vendor_id' => $vendor->id,
            'name' => 'Tenda & Canopy',
            'slug' => 'tenda-canopy',
            'icon' => 'tent',
        ]);

        $catSleeping = ItemCategory::create([
            'vendor_id' => $vendor->id,
            'name' => 'Sleeping Gear',
            'slug' => 'sleeping-gear',
            'icon' => 'moon',
        ]);

        $catCooking = ItemCategory::create([
            'vendor_id' => $vendor->id,
            'name' => 'Cooking Equipment',
            'slug' => 'cooking-equipment',
            'icon' => 'flame',
        ]);

        $catLighting = ItemCategory::create([
            'vendor_id' => $vendor->id,
            'name' => 'Lighting & Power',
            'slug' => 'lighting-power',
            'icon' => 'sun',
        ]);

        $catBackpack = ItemCategory::create([
            'vendor_id' => $vendor->id,
            'name' => 'Backpack & Carrier',
            'slug' => 'backpack-carrier',
            'icon' => 'bag',
        ]);

        // 4. Master Items
        $itemsData = [
            [
                'vendor_id' => $vendor->id,
                'category_id' => $catTenda->id,
                'name' => 'Tenda Consina Magnum 4 Person Ultra',
                'slug' => 'tenda-consina-magnum-4-person-ultra',
                'description' => 'Tenda kemping kapasitas 4 orang dengan spesifikasi tinggi. Double layer Polyester PU 3000mm tahan cuaca ekstrem gunung Indonesia.',
                'daily_rate' => 45000,
                'deposit_amount' => 0,
                'late_fee_per_day' => 25000,
                'image_url' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=1000&q=80',
                'is_active' => true,
            ],
            [
                'vendor_id' => $vendor->id,
                'category_id' => $catTenda->id,
                'name' => 'Tenda Naturehike Mongar 2 Ultralight',
                'slug' => 'tenda-naturehike-mongar-2-ultralight',
                'description' => 'Tenda ultralight kapasitas 2 orang berbahan Silicone Coated 20D Nylon. Bobot sangat ringan hanya 1.8 kg, ideal untuk pendakian cepat.',
                'daily_rate' => 60000,
                'deposit_amount' => 0,
                'late_fee_per_day' => 30000,
                'image_url' => 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?auto=format&fit=crop&w=1000&q=80',
                'is_active' => true,
            ],
            [
                'vendor_id' => $vendor->id,
                'category_id' => $catSleeping->id,
                'name' => 'Sleeping Bag Eiger Compress 800 Warm',
                'slug' => 'sleeping-bag-eiger-compress-800-warm',
                'description' => 'Kantong tidur hangat dilapisi dacron sintetis 300g/m² dengan toleransi suhu nyaman hingga 5°C. Dilengkapi compression sack ringkas.',
                'daily_rate' => 15000,
                'deposit_amount' => 0,
                'late_fee_per_day' => 10000,
                'image_url' => 'https://images.unsplash.com/photo-1526772662000-3f88f10405ff?auto=format&fit=crop&w=1000&q=80',
                'is_active' => true,
            ],
            [
                'vendor_id' => $vendor->id,
                'category_id' => $catCooking->id,
                'name' => 'Kompor Portable Mawar Windproof Kovar',
                'slug' => 'kompor-portable-mawar-windproof-kovar',
                'description' => 'Kompor mawar lipat anti angin dilengkapi pelindung bodi stainles, hemat gas dan cepat mendidihkan air di suhu dingin.',
                'daily_rate' => 12000,
                'deposit_amount' => 0,
                'late_fee_per_day' => 8000,
                'image_url' => 'https://images.unsplash.com/photo-1517824806704-9040b037703b?auto=format&fit=crop&w=1000&q=80',
                'is_active' => true,
            ],
            [
                'vendor_id' => $vendor->id,
                'category_id' => $catCooking->id,
                'name' => 'Nesting Cooking Set DS-308 (4 in 1)',
                'slug' => 'nesting-cooking-set-ds-308-4-in-1',
                'description' => 'Set alat masak lengkap aluminium anodized anti lengket: 2 panci, 1 wajan lipat, 1 teko air hangat, plus spons cuci.',
                'daily_rate' => 20000,
                'deposit_amount' => 0,
                'late_fee_per_day' => 10000,
                'image_url' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=1000&q=80',
                'is_active' => true,
            ],
            [
                'vendor_id' => $vendor->id,
                'category_id' => $catBackpack->id,
                'name' => 'Carrier Osprey Atmosphere AG 65L Premium',
                'slug' => 'carrier-osprey-atmosphere-ag-65l-premium',
                'description' => 'Tas carrier legendaris dengan sistem suspensi Anti-Gravity 3D mesh. Nyaman memuat perlengkapan hingga beban 25kg.',
                'daily_rate' => 50000,
                'deposit_amount' => 0,
                'late_fee_per_day' => 25000,
                'image_url' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=1000&q=80',
                'is_active' => true,
            ],
            [
                'vendor_id' => $vendor->id,
                'category_id' => $catLighting->id,
                'name' => 'Lampu Tenda Gantung LED Solar Rechargeable',
                'slug' => 'lampu-tenda-gantung-led-solar-rechargeable',
                'description' => 'Lampu gantung fleksibel 300 Lumens dengan port pengisi daya USB dan panel surya darurat. Tahan hingga 12 jam.',
                'daily_rate' => 10000,
                'deposit_amount' => 0,
                'late_fee_per_day' => 5000,
                'image_url' => 'https://images.unsplash.com/photo-1508873696983-2df515122519?auto=format&fit=crop&w=1000&q=80',
                'is_active' => true,
            ],
        ];

        foreach ($itemsData as $itemData) {
            $item = MasterItem::create($itemData);

            // Create 3 units per item
            for ($u = 1; $u <= 3; $u++) {
                ItemUnit::create([
                    'vendor_id' => $vendor->id,
                    'master_item_id' => $item->id,
                    'unit_code' => strtoupper(Str::slug($item->name, '-')).'-UNIT-'.$u,
                    'barcode' => 'BC-'.rand(100000, 999999),
                    'condition' => 'good',
                    'status' => 'available',
                    'purchase_date' => now()->subMonths(6),
                    'purchase_price' => $item->daily_rate * 20,
                    'notes' => 'Unit terawat dan steril.',
                ]);
            }
        }
    }
}
