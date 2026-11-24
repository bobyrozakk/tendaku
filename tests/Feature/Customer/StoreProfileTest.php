<?php

namespace Tests\Feature\Customer;

use App\Livewire\Customer\ProductBooking;
use App\Models\CampingPackage;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Payment\MidtransService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class StoreProfileTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor1;

    protected Vendor $vendor2;

    protected Vendor $emptyVendor;

    protected User $customer;

    protected MasterItem $itemVendor1;

    protected MasterItem $itemVendor2;

    protected CampingPackage $packageVendor1;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-10 10:00:00');

        // Mock Midtrans agar tidak menembak endpoint eksternal
        config(['services.midtrans.server_key' => 'test-midtrans-server-key']);
        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createTransaction')->andReturn('snap-token-test');
        });

        // 1. Setup Customer User
        $this->customer = User::create([
            'name' => 'Budi Customer',
            'email' => 'budi@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'phone' => '081234567890',
            'nik' => '3578012345670001',
        ]);

        $this->customer->forceFill(['email_verified_at' => now()])->save();

        // 2. Setup Vendor 1 (Aktif dengan Produk & Paket)
        $this->vendor1 = Vendor::create([
            'name' => 'Tendaku Malang Outdoor',
            'slug' => 'tendaku-malang-outdoor',
            'description' => 'Spesialis rental tenda dan alat pendakian resmi di Malang.',
            'phone' => '081234567801',
            'email' => 'malang@tendaku.com',
            'address' => 'Jl. Soekarno Hatta No. 12',
            'city' => 'Malang',
            'logo_url' => 'https://example.com/logo-vendor-1.jpg',
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $category1 = ItemCategory::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Tenda Gunung',
            'slug' => 'tenda-gunung',
        ]);

        $this->itemVendor1 = MasterItem::create([
            'vendor_id' => $this->vendor1->id,
            'category_id' => $category1->id,
            'name' => 'Tenda Eiger Guardian 4P',
            'slug' => 'tenda-eiger-guardian-4p',
            'description' => 'Tenda dome double layer kapasitas 4 orang terawat dan steril.',
            'daily_rate' => 60000,
            'late_fee_per_day' => 30000,
            'is_active' => true,
        ]);

        ItemUnit::create([
            'vendor_id' => $this->vendor1->id,
            'master_item_id' => $this->itemVendor1->id,
            'unit_code' => 'TND-01',
            'condition' => 'excellent',
            'status' => 'available',
            'purchase_date' => Carbon::now()->subMonths(2),
        ]);

        $this->packageVendor1 = CampingPackage::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Paket Semeru Hemat 2H1M',
            'slug' => 'paket-semeru-hemat-2h1m',
            'description' => 'Paket lengkap pendakian berisi tenda, matras, dan cooking set.',
            'price' => 150000,
            'duration_days' => 2,
            'max_people' => 4,
            'is_active' => true,
        ]);

        // 3. Setup Vendor 2 (Aktif dengan Produk Berbeda)
        $this->vendor2 = Vendor::create([
            'name' => 'Surabaya Camp Station',
            'slug' => 'surabaya-camp-station',
            'description' => 'Peralatan camping pantai dan bukit di Surabaya.',
            'phone' => '089876543210',
            'email' => 'surabaya@tendaku.com',
            'address' => 'Jl. Gubeng Pojok No. 5',
            'city' => 'Surabaya',
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $category2 = ItemCategory::create([
            'vendor_id' => $this->vendor2->id,
            'name' => 'Sleeping Bag & Matras',
            'slug' => 'sleeping-bag-matras',
        ]);

        $this->itemVendor2 = MasterItem::create([
            'vendor_id' => $this->vendor2->id,
            'category_id' => $category2->id,
            'name' => 'Sleeping Bag Bulu Angsa Hangat',
            'slug' => 'sleeping-bag-bulu-angsa',
            'description' => 'Sleeping bag ultra warm untuk suhu ekstrem.',
            'daily_rate' => 25000,
            'is_active' => true,
        ]);

        ItemUnit::create([
            'vendor_id' => $this->vendor2->id,
            'master_item_id' => $this->itemVendor2->id,
            'unit_code' => 'SB-01',
            'condition' => 'good',
            'status' => 'available',
        ]);

        // 4. Setup Toko Tanpa Produk (Empty State)
        $this->emptyVendor = Vendor::create([
            'name' => 'Toko Baru Buka',
            'slug' => 'toko-baru-buka',
            'phone' => '081122334455',
            'address' => 'Jl. Rintisan No. 99',
            'city' => 'Batu',
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Skenario 1: Customer yang belum login melihat pop-up pilihan masuk atau lanjut sebagai tamu.
     */
    public function test_guest_sees_login_popup_modal_when_accessing_store_profile(): void
    {
        $response = $this->get(route('customer.stores.show', $this->vendor1->slug));

        $response->assertOk()
            ->assertSee('Lanjut sebagai Tamu atau Masuk')
            ->assertSee(route('login'))
            ->assertSee('Lanjut Tanpa Akun')
            ->assertSee($this->vendor1->name);
    }

    /**
     * Customer yang sudah login tidak melihat pop-up login tamu saat mengakses profil toko.
     */
    public function test_authenticated_customer_does_not_see_guest_login_popup(): void
    {
        $response = $this->actingAs($this->customer)
            ->get(route('customer.stores.show', $this->vendor1->slug));

        $response->assertOk()
            ->assertDontSee('modal-store-login-title');
    }

    /**
     * Customer yang belum login dapat melihat pop-up pilihan masuk atau lanjut sebagai tamu di halaman detail produk.
     */
    public function test_guest_sees_login_popup_prompt_on_product_page(): void
    {
        $response = $this->get(route('customer.product.show', $this->itemVendor1->id));

        $response->assertOk()
            ->assertSee('modal-guest-store-title')
            ->assertSee('Lanjut sebagai Tamu atau Masuk')
            ->assertSee(route('login'))
            ->assertSee('Lanjut Tanpa Akun');
    }

    /**
     * Skenario 2: Customer yang sudah login dapat mengakses profil toko dengan benar dan melihat header Shopee-like.
     */
    public function test_authenticated_customer_can_view_store_profile_with_header_and_stats(): void
    {
        $response = $this->actingAs($this->customer)
            ->get(route('customer.stores.show', $this->vendor1->slug));

        $response->assertOk()
            ->assertViewIs('customer.store.show')
            ->assertSee($this->vendor1->name)
            ->assertSee($this->vendor1->city)
            ->assertSee($this->vendor1->phone)
            ->assertSee('Total Alat Sewa')
            ->assertSee('1') // 1 item aktif
            ->assertSee('Paket Camping');
    }

    /**
     * Customer dapat mengakses profil toko menggunakan ID numerik vendor.
     */
    public function test_customer_can_access_store_profile_by_id(): void
    {
        $response = $this->actingAs($this->customer)
            ->get(route('customer.stores.show', $this->vendor1->id));

        $response->assertOk()
            ->assertSee($this->vendor1->name);
    }

    /**
     * Skenario 3: Halaman Profil Toko hanya menampilkan alat/paket milik toko tersebut (tidak tercampur vendor lain).
     */
    public function test_store_profile_only_displays_items_belonging_to_the_store(): void
    {
        $response = $this->actingAs($this->customer)
            ->get(route('customer.stores.show', $this->vendor1->slug));

        $response->assertOk();
        // Item milik Vendor 1 HARUS ada
        $response->assertSee($this->itemVendor1->name);
        $response->assertSee('Rp 60.000');

        // Item milik Vendor 2 TIDAK BOLEH muncul di halaman profil Vendor 1
        $response->assertDontSee($this->itemVendor2->name);
    }

    /**
     * Link ke profil toko tersedia pada halaman detail produk customer.
     */
    public function test_product_detail_page_contains_link_to_store_profile(): void
    {
        $response = $this->actingAs($this->customer)
            ->get(route('customer.product.show', $this->itemVendor1->id));

        $response->assertOk()
            ->assertSee(route('customer.stores.show', $this->vendor1->slug))
            ->assertSee('Kunjungi Toko');
    }

    /**
     * Skenario 4: Klik alat atau tombol sewa dari Profil Toko mengarahkan ke halaman booking yang sudah ada dengan parameter produk.
     */
    public function test_product_card_links_directly_to_existing_product_booking_page(): void
    {
        $storeResponse = $this->actingAs($this->customer)
            ->get(route('customer.stores.show', $this->vendor1->slug));

        $storeResponse->assertOk();

        // Di halaman Profil Toko, tombol 'Sewa Sekarang' dan card link mengarah ke route('customer.product.show', $id)
        $expectedBookingUrl = route('customer.product.show', $this->itemVendor1->id);
        $storeResponse->assertSee($expectedBookingUrl);

        // Akses route tujuan dengan membawa parameter produk yang dipilih
        $bookingPageResponse = $this->actingAs($this->customer)
            ->get($expectedBookingUrl);

        $bookingPageResponse->assertOk()
            ->assertSee($this->itemVendor1->name)
            ->assertSee('Tarif Sewa Peralatan')
            ->assertSee('Rp 60.000');
    }

    /**
     * CheckoutController mendukung parameter item_id langsung untuk membuka checkout secara seamless.
     */
    public function test_direct_booking_via_checkout_url_with_item_parameter(): void
    {
        $response = $this->actingAs($this->customer)
            ->get(route('checkout.index', ['item_id' => $this->itemVendor1->id]));

        $response->assertOk()
            ->assertViewIs('customer.booking.create')
            ->assertSee($this->itemVendor1->name)
            ->assertSee('Checkout');
    }

    /**
     * Skenario 5: Alur sewa penuh dari alur profil toko: pemilihan tanggal, perhitungan harga, hingga submit booking berjalan normal.
     */
    public function test_full_booking_flow_from_product_form_to_checkout_and_submit(): void
    {
        // 1. Jalankan komponen Livewire ProductBooking pada produk yang dipilih dari profil toko
        $pickupDate = Carbon::tomorrow()->format('Y-m-d');
        $returnDate = Carbon::tomorrow()->addDays(2)->format('Y-m-d'); // 2 hari sewa

        Livewire::actingAs($this->customer)
            ->test(ProductBooking::class, ['product' => $this->itemVendor1])
            ->set('pickup_date', $pickupDate)
            ->set('pickup_time', '09:00')
            ->set('duration_days', 2)
            ->call('syncReturnFromDuration')
            // Assert perhitungan hari dan harga (2 hari x Rp 60.000 x 1 unit = Rp 120.000)
            ->assertSet('totalDays', 2)
            ->assertSet('totalPrice', 120000)
            // Klik sewa sekarang / proceed to booking
            ->call('proceedToBooking')
            ->assertHasNoErrors()
            ->assertRedirect(route('checkout.index', ['direct' => 1]));

        // 2. Akses halaman checkout yang sudah ada
        $customer = $this->customer->fresh();
        $checkoutResponse = $this->actingAs($customer)
            ->get(route('checkout.index', ['direct' => 1]));

        $checkoutResponse->assertOk()
            ->assertSee($this->itemVendor1->name);

        // 3. Submit booking melalui CheckoutController::store
        $storeResponse = $this->actingAs($customer)
            ->post(route('checkout.store'), [
                'pickup_method' => 'self_pickup',
                'pickup_date' => $pickupDate,
                'pickup_time' => '09:00',
                'return_date' => $returnDate,
                'payment_channel' => 'qris',
                'payment_option' => 'full',
                'notes' => 'Penyewaan dari alur profil toko',
            ]);

        $storeResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rentals', [
            'vendor_id' => $this->vendor1->id,
            'user_id' => $this->customer->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Skenario 6: Toko yang belum memiliki alat yang disewakan menampilkan pesan kosong (empty state) yang rapi tanpa error.
     */
    public function test_empty_store_displays_clean_empty_state_without_error(): void
    {
        $response = $this->actingAs($this->customer)
            ->get(route('customer.stores.show', $this->emptyVendor->slug));

        $response->assertOk()
            ->assertSee($this->emptyVendor->name)
            ->assertSee('Belum Ada Peralatan Disewakan')
            ->assertSee('saat ini belum memiliki peralatan camping aktif untuk disewakan')
            ->assertSee('Jelajahi Katalog Toko Lain');
    }

    /**
     * Filter rentang harga di sidebar hanya menampilkan alat pada rentang tersebut.
     */
    public function test_price_filter_limits_items_to_selected_range(): void
    {
        $this->makeItem($this->vendor1, 'Matras Lipat Murah', 20000, units: 1);

        $this->actingAs($this->customer)
            ->get(route('customer.stores.show', ['vendor' => $this->vendor1->slug, 'price' => 'lt_25']))
            ->assertOk()
            ->assertSee('Matras Lipat Murah')
            ->assertDontSee($this->itemVendor1->name);

        $this->actingAs($this->customer)
            ->get(route('customer.stores.show', ['vendor' => $this->vendor1->slug, 'price' => '50_100']))
            ->assertOk()
            ->assertSee($this->itemVendor1->name)
            ->assertDontSee('Matras Lipat Murah');
    }

    /**
     * Filter stok menyembunyikan alat tanpa unit tersedia; tanpa filter alat tersebut tampil sebagai stok habis.
     */
    public function test_in_stock_filter_hides_items_without_available_units(): void
    {
        $this->makeItem($this->vendor1, 'Kompor Portable Kosong', 35000, units: 0);

        $this->actingAs($this->customer)
            ->get(route('customer.stores.show', $this->vendor1->slug))
            ->assertOk()
            ->assertSee('Kompor Portable Kosong')
            ->assertSee('Stok habis');

        $this->actingAs($this->customer)
            ->get(route('customer.stores.show', ['vendor' => $this->vendor1->slug, 'stock' => 1]))
            ->assertOk()
            ->assertSee($this->itemVendor1->name)
            ->assertDontSee('Kompor Portable Kosong');
    }

    /**
     * Urutan harga terendah menampilkan alat termurah lebih dulu.
     */
    public function test_sort_by_lowest_price_orders_items_ascending(): void
    {
        $this->makeItem($this->vendor1, 'Lampu Tenda Hemat', 10000, units: 1);

        $this->actingAs($this->customer)
            ->get(route('customer.stores.show', ['vendor' => $this->vendor1->slug, 'sort' => 'price_asc']))
            ->assertOk()
            ->assertSeeInOrder(['Lampu Tenda Hemat', $this->itemVendor1->name]);

        $this->actingAs($this->customer)
            ->get(route('customer.stores.show', ['vendor' => $this->vendor1->slug, 'sort' => 'price_desc']))
            ->assertOk()
            ->assertSeeInOrder([$this->itemVendor1->name, 'Lampu Tenda Hemat']);
    }

    /**
     * Parameter filter yang tidak valid diabaikan, bukan menyebabkan error.
     */
    public function test_invalid_filter_parameters_are_ignored(): void
    {
        $this->actingAs($this->customer)
            ->get(route('customer.stores.show', [
                'vendor' => $this->vendor1->slug,
                'price' => 'bukan-rentang',
                'sort' => 'acak',
                'tab' => 'lainnya',
            ]))
            ->assertOk()
            ->assertSee($this->itemVendor1->name);
    }

    /**
     * Tab paket memakai rentang harga paket dan mengabaikan filter kategori/stok.
     */
    public function test_packages_tab_supports_package_price_filter(): void
    {
        $this->actingAs($this->customer)
            ->get(route('customer.stores.show', [
                'vendor' => $this->vendor1->slug,
                'tab' => 'packages',
                'price' => '150_300',
                'stock' => 1,
            ]))
            ->assertOk()
            ->assertSee($this->packageVendor1->name);

        $this->actingAs($this->customer)
            ->get(route('customer.stores.show', [
                'vendor' => $this->vendor1->slug,
                'tab' => 'packages',
                'price' => 'lt_150',
            ]))
            ->assertOk()
            ->assertDontSee($this->packageVendor1->name)
            ->assertSee('Tidak Ada Paket Camping Ditemukan');
    }

    private function makeItem(Vendor $vendor, string $name, int $dailyRate, int $units): MasterItem
    {
        $item = MasterItem::create([
            'vendor_id' => $vendor->id,
            'category_id' => ItemCategory::where('vendor_id', $vendor->id)->value('id'),
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'description' => 'Alat uji filter profil toko.',
            'daily_rate' => $dailyRate,
            'is_active' => true,
        ]);

        for ($i = 1; $i <= $units; $i++) {
            ItemUnit::create([
                'vendor_id' => $vendor->id,
                'master_item_id' => $item->id,
                'unit_code' => strtoupper(substr($item->slug, 0, 3)).'-'.$item->id.'-'.$i,
                'condition' => 'good',
                'status' => 'available',
            ]);
        }

        return $item;
    }
}
