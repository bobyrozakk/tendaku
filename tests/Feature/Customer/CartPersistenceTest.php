<?php

namespace Tests\Feature\Customer;

use App\Models\ItemCategory;
use App\Models\MasterItem;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Booking\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class CartPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected MasterItem $itemA;

    protected MasterItem $itemB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::create([
            'name' => 'Tendaku Vendor Uji',
            'slug' => 'tendaku-vendor-uji',
            'email' => 'vendor@tendaku.test',
            'phone' => '081234567890',
            'address' => 'Jl. Uji No. 1',
            'city' => 'Malang',
            'status' => 'active',
        ]);

        $category = ItemCategory::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Tenda Dome',
            'slug' => 'tenda-dome',
        ]);

        $this->itemA = MasterItem::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $category->id,
            'name' => 'Tenda Dome Arpenaz 4.1',
            'slug' => 'tenda-dome-arpenaz-41',
            'daily_rate' => 50000,
            'deposit_amount' => 0,
            'is_active' => true,
        ]);

        $this->itemB = MasterItem::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $category->id,
            'name' => 'Kompor Portable Camping',
            'slug' => 'kompor-portable-camping',
            'daily_rate' => 20000,
            'deposit_amount' => 0,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1 - Guest: Cart tersimpan di sesi tapi hilang saat sesi berakhir (tidak permanen).
     */
    public function test_guest_cart_persists_in_session_but_is_not_stored_permanently(): void
    {
        $cartService = app(CartService::class);

        $cartData = [
            'master_item_id' => $this->itemA->id,
            'product_id' => $this->itemA->id,
            'product_name' => $this->itemA->name,
            'daily_rate' => 50000,
            'quantity' => 1,
            'qty' => 1,
            'pickup_date' => now()->addDay()->toDateString(),
            'return_date' => now()->addDays(2)->toDateString(),
            'total_days' => 1,
            'total_price' => 50000,
            'vendor_id' => $this->vendor->id,
        ];

        // 1. Guest menambahkan produk ke cart
        $cartService->set($cartData, null);

        // 2. Pastikan cart dapat digunakan selama sesi aktif
        $retrievedCart = $cartService->get(null);
        $this->assertNotNull($retrievedCart);
        $this->assertSame($this->itemA->id, $retrievedCart['master_item_id']);

        // 3. Pastikan tidak ada data cart yang tersimpan di tabel users
        $this->assertSame(0, User::whereNotNull('cart')->count());

        // 4. Tutup / akhiri sesi penggunaan
        Session::flush();

        // 5. Buka website kembali sebagai guest
        $afterSessionEnded = $cartService->get(null);
        $this->assertNull($afterSessionEnded, 'Cart guest sebelumnya tidak boleh tersimpan setelah sesi berakhir.');
    }

    /**
     * Test 2 - Customer Login: Cart tersimpan permanen setelah logout dan login kembali.
     */
    public function test_customer_cart_is_persisted_permanently_across_login_sessions(): void
    {
        $cartService = app(CartService::class);

        /** @var User $customerA */
        $customerA = User::create([
            'name' => 'Customer A',
            'email' => 'customer.a@gmail.com',
            'password' => bcrypt('password123'),
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $cartDataA = [
            'master_item_id' => $this->itemA->id,
            'product_id' => $this->itemA->id,
            'product_name' => $this->itemA->name,
            'daily_rate' => 50000,
            'quantity' => 2,
            'qty' => 2,
            'pickup_date' => now()->addDay()->toDateString(),
            'return_date' => now()->addDays(3)->toDateString(),
            'total_days' => 2,
            'total_price' => 200000,
            'vendor_id' => $this->vendor->id,
        ];

        // 1. Customer A menambahkan produk ke cart saat login
        $this->actingAs($customerA);
        $cartService->set($cartDataA, $customerA);

        // 2. Pastikan tersimpan di DB Customer A
        $customerA->refresh();
        $this->assertNotNull($customerA->cart);
        $this->assertSame($this->itemA->id, $customerA->cart['master_item_id']);

        // 3. Customer A logout
        $cartService->syncOnLogout($customerA);
        auth()->logout();
        Session::flush();

        // 4. Sesi kosong setelah logout
        $this->assertNull(Session::get(CartService::SESSION_KEY));

        // 5. Customer A login kembali
        $this->actingAs($customerA);
        $cartService->syncOnLogin($customerA);

        // 6. Pastikan produk sebelumnya masih ada
        $restoredCart = $cartService->get($customerA);
        $this->assertNotNull($restoredCart);
        $this->assertSame($this->itemA->id, $restoredCart['master_item_id']);
        $this->assertSame(2, $restoredCart['quantity']);
    }

    /**
     * Test 3 - Isolasi: Cart Customer A tidak bisa dilihat oleh Customer B dan Guest tidak mendapatkan cart Customer A.
     */
    public function test_cart_isolation_between_customers_and_guests(): void
    {
        $cartService = app(CartService::class);

        /** @var User $customerA */
        $customerA = User::create([
            'name' => 'Customer A',
            'email' => 'customer.a@gmail.com',
            'password' => bcrypt('password123'),
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        /** @var User $customerB */
        $customerB = User::create([
            'name' => 'Customer B',
            'email' => 'customer.b@gmail.com',
            'password' => bcrypt('password123'),
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        // Customer A mengisi cart
        $cartDataA = [
            'master_item_id' => $this->itemA->id,
            'product_id' => $this->itemA->id,
            'product_name' => $this->itemA->name,
            'quantity' => 1,
            'vendor_id' => $this->vendor->id,
        ];
        $this->actingAs($customerA);
        $cartService->set($cartDataA, $customerA);

        // Customer A logout
        $cartService->syncOnLogout($customerA);
        auth()->logout();
        Session::flush();

        // Verifikasi Guest: Guest tidak boleh mendapatkan cart milik Customer A
        $guestCart = $cartService->get(null);
        $this->assertNull($guestCart, 'Guest tidak boleh mewarisi cart milik Customer A yang baru saja logout.');

        // Customer B login (Customer B belum pernah memasukkan item ke cart)
        $this->actingAs($customerB);
        $cartService->syncOnLogin($customerB);

        // Verifikasi Customer B: Cart Customer A tidak boleh muncul pada Customer B
        $cartB = $cartService->get($customerB);
        $this->assertNull($cartB, 'Cart Customer A tidak boleh terlihat oleh Customer B.');

        // Sekarang Customer B menambahkan item berbeda ke cart
        $cartDataB = [
            'master_item_id' => $this->itemB->id,
            'product_id' => $this->itemB->id,
            'product_name' => $this->itemB->name,
            'quantity' => 3,
            'vendor_id' => $this->vendor->id,
        ];
        $cartService->set($cartDataB, $customerB);

        // Customer B logout
        $cartService->syncOnLogout($customerB);
        auth()->logout();
        Session::flush();

        // Customer A login kembali
        $this->actingAs($customerA);
        $cartService->syncOnLogin($customerA);

        // Pastikan Customer A tetap melihat cart miliknya sendiri (Item A), bukan cart Customer B (Item B)
        $restoredCartA = $cartService->get($customerA);
        $this->assertNotNull($restoredCartA);
        $this->assertSame($this->itemA->id, $restoredCartA['master_item_id']);
        $this->assertNotSame($this->itemB->id, $restoredCartA['master_item_id']);
    }
}
