<?php

namespace Tests\Feature\Customer;

use App\Models\CampingPackage;
use App\Models\CampingPackageItem;
use App\Models\CustomerAddress;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAddressAndRentalDataTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;

    private CampingPackage $package;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-28 09:00:00');

        $this->vendor = Vendor::create([
            'name' => 'Tendaku Vendor Outdoor',
            'slug' => 'tendaku-vendor-outdoor',
            'email' => 'vendor@tendaku.test',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 10',
            'city' => 'Bandung',
            'status' => 'active',
        ]);

        $category = ItemCategory::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Tenda Dome',
            'slug' => 'tenda-dome',
        ]);

        $item = MasterItem::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $category->id,
            'name' => 'Tenda Dome 4P',
            'slug' => 'tenda-dome-4p',
            'daily_rate' => 50000,
            'is_active' => true,
        ]);

        foreach (['TD-101', 'TD-102'] as $unitCode) {
            ItemUnit::create([
                'vendor_id' => $this->vendor->id,
                'master_item_id' => $item->id,
                'unit_code' => $unitCode,
                'condition' => 'good',
                'status' => 'available',
                'purchase_date' => Carbon::now()->subMonths(3),
            ]);
        }

        $this->package = CampingPackage::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Paket Semeru Komplit',
            'slug' => 'paket-semeru-komplit',
            'description' => 'Paket hemat untuk camping ceria.',
            'price' => 120000,
            'capacity' => 4,
            'duration_days' => 1,
            'is_active' => true,
        ]);

        CampingPackageItem::create([
            'camping_package_id' => $this->package->id,
            'master_item_id' => $item->id,
            'quantity' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Skenario 1: Customer yang sudah login dapat melihat data profilnya secara otomatis pada proses penyewaan.
     */
    public function test_scenario_1_logged_in_customer_sees_profile_data_automatically(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Budi Santoso',
            'email' => 'budi.santoso@tendaku.test',
            'phone' => '081234567899',
            'nik' => '3273012345670001',
        ]);

        $response = $this->actingAs($customer)
            ->get(route('customer.packages.checkout', $this->package));

        $response->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('budi.santoso@tendaku.test')
            ->assertSee('081234567899')
            ->assertSee('Akun Terhubung');
    }

    /**
     * Skenario 2: Customer dapat menambahkan lebih dari satu alamat.
     */
    public function test_scenario_2_customer_can_add_multiple_addresses(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        // Tambah alamat ke-1 (otomatis jadi default)
        $res1 = $this->actingAs($customer)->postJson(route('customer.addresses.store'), [
            'label' => 'Rumah',
            'recipient_name' => 'Budi Santoso',
            'phone_number' => '081234567890',
            'full_address' => 'Jl. Dago Asri No. 12',
            'province' => 'Jawa Barat',
            'city' => 'Bandung',
            'district' => 'Coblong',
            'village' => 'Dago',
            'postal_code' => '40135',
            'notes' => 'Pagar abu-abu',
        ]);

        $res1->assertStatus(201);
        $this->assertDatabaseHas('customer_addresses', [
            'user_id' => $customer->id,
            'label' => 'Rumah',
            'is_default' => true,
        ]);

        // Tambah alamat ke-2
        $res2 = $this->actingAs($customer)->postJson(route('customer.addresses.store'), [
            'label' => 'Kantor',
            'recipient_name' => 'Budi Office',
            'phone_number' => '081234567891',
            'full_address' => 'Jl. Asia Afrika No. 88',
            'province' => 'Jawa Barat',
            'city' => 'Bandung',
            'district' => 'Sumur Bandung',
            'postal_code' => '40111',
            'is_default' => false,
        ]);

        $res2->assertStatus(201);
        $this->assertSame(2, $customer->addresses()->count());
    }

    /**
     * Skenario 3: Customer dapat mengedit dan menghapus alamat miliknya.
     */
    public function test_scenario_3_customer_can_edit_and_delete_own_address(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $address = CustomerAddress::factory()->create([
            'user_id' => $customer->id,
            'label' => 'Kos Lama',
            'full_address' => 'Jl. Cisitu No. 5',
        ]);

        // Edit alamat
        $updateRes = $this->actingAs($customer)->putJson(route('customer.addresses.update', $address), [
            'label' => 'Kos Baru',
            'recipient_name' => $address->recipient_name,
            'phone_number' => $address->phone_number,
            'full_address' => 'Jl. Cisitu Indah No. 10',
            'province' => $address->province,
            'city' => $address->city,
            'district' => $address->district,
        ]);

        $updateRes->assertOk();
        $this->assertDatabaseHas('customer_addresses', [
            'id' => $address->id,
            'label' => 'Kos Baru',
            'full_address' => 'Jl. Cisitu Indah No. 10',
        ]);

        // Hapus alamat
        $deleteRes = $this->actingAs($customer)->deleteJson(route('customer.addresses.destroy', $address));
        $deleteRes->assertOk();
        $this->assertDatabaseMissing('customer_addresses', ['id' => $address->id]);
    }

    /**
     * Skenario 4: Customer dapat menentukan alamat utama.
     */
    public function test_scenario_4_customer_can_set_default_address(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $addr1 = CustomerAddress::factory()->create([
            'user_id' => $customer->id,
            'label' => 'Alamat A',
            'is_default' => true,
        ]);
        $addr2 = CustomerAddress::factory()->create([
            'user_id' => $customer->id,
            'label' => 'Alamat B',
            'is_default' => false,
        ]);

        $response = $this->actingAs($customer)->postJson(route('customer.addresses.default', $addr2));
        $response->assertOk();

        $this->assertFalse($addr1->fresh()->is_default);
        $this->assertTrue($addr2->fresh()->is_default);
    }

    /**
     * Skenario 5: Customer dapat memilih alamat yang berbeda ketika melakukan booking.
     */
    public function test_scenario_5_customer_can_choose_different_address_when_booking(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $addr1 = CustomerAddress::factory()->create([
            'user_id' => $customer->id,
            'label' => 'Rumah',
            'recipient_name' => 'Budi Rumah',
            'full_address' => 'Jl. Rumah No. 1',
            'is_default' => true,
        ]);
        $addr2 = CustomerAddress::factory()->create([
            'user_id' => $customer->id,
            'label' => 'Kantor',
            'recipient_name' => 'Budi Kantor',
            'full_address' => 'Jl. Kantor No. 2',
            'is_default' => false,
        ]);

        $response = $this->actingAs($customer)->post(route('customer.packages.checkout.store', $this->package), [
            'package_id' => $this->package->id,
            'pickup_date' => '2026-10-29',
            'pickup_time' => '09:00',
            'return_date' => '2026-10-30',
            'return_time' => '09:00',
            'pickup_method' => 'delivery',
            'customer_address_id' => $addr2->id,
            'payment_channel' => 'bca_va',
        ]);

        $rental = Rental::withoutGlobalScopes()->latest('id')->first();
        $this->assertNotNull($rental);
        $response->assertRedirect(route('booking.status', $rental->booking_code));

        $this->assertSame($addr2->id, (int) $rental->customer_address_id);
        $this->assertStringContainsString('Budi Kantor', $rental->delivery_address);
        $this->assertStringContainsString('Jl. Kantor No. 2', $rental->delivery_address);
    }

    /**
     * Skenario 6: Booking menyimpan alamat yang benar dan tetap konsisten meskipun alamat profil diubah setelah transaksi.
     */
    public function test_scenario_6_booking_snapshot_persists_even_after_address_is_modified_or_deleted(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $address = CustomerAddress::factory()->create([
            'user_id' => $customer->id,
            'label' => 'Rumah Asli',
            'recipient_name' => 'Budi Penerima',
            'phone_number' => '081234567890',
            'full_address' => 'Jl. Asli No. 99',
            'is_default' => true,
        ]);

        $this->actingAs($customer)->post(route('customer.packages.checkout.store', $this->package), [
            'package_id' => $this->package->id,
            'pickup_date' => '2026-10-29',
            'pickup_time' => '09:00',
            'return_date' => '2026-10-30',
            'return_time' => '09:00',
            'pickup_method' => 'delivery',
            'customer_address_id' => $address->id,
        ]);

        $rental = Rental::withoutGlobalScopes()->latest('id')->first();
        $originalSnapshot = $rental->delivery_address;
        $this->assertStringContainsString('Jl. Asli No. 99', $originalSnapshot);

        // Customer mengubah alamat di profil
        $address->update(['full_address' => 'Jl. Berubah Total No. 777']);

        // Data pada transaksi booking historis TIDAK boleh berubah
        $this->assertSame($originalSnapshot, $rental->fresh()->delivery_address);

        // Bahkan jika alamat dihapus dari profil customer
        $address->delete();
        $this->assertSame($originalSnapshot, $rental->fresh()->delivery_address);
    }

    /**
     * Skenario 7: Customer tidak dapat melihat, mengubah, atau menghapus alamat milik customer lain.
     */
    public function test_scenario_7_customer_cannot_access_or_manipulate_another_customers_address(): void
    {
        $customerA = User::factory()->create(['role' => 'customer']);
        $customerB = User::factory()->create(['role' => 'customer']);

        $addressB = CustomerAddress::factory()->create([
            'user_id' => $customerB->id,
            'label' => 'Rahasia B',
            'full_address' => 'Jl. Rahasia B No. 1',
        ]);

        // Customer A mencoba mengedit alamat milik Customer B
        $this->actingAs($customerA)->putJson(route('customer.addresses.update', $addressB), [
            'label' => 'Dibajak A',
            'recipient_name' => 'Hacker',
            'phone_number' => '081299999999',
            'full_address' => 'Jl. Hacker No. 1',
            'province' => 'Jawa Barat',
            'city' => 'Bandung',
            'district' => 'Coblong',
        ])->assertStatus(403);

        // Customer A mencoba menghapus alamat milik Customer B
        $this->actingAs($customerA)->deleteJson(route('customer.addresses.destroy', $addressB))
            ->assertStatus(403);

        // Customer A mencoba menjadikan alamat Customer B sebagai default
        $this->actingAs($customerA)->postJson(route('customer.addresses.default', $addressB))
            ->assertStatus(403);

        // Pastikan alamat B tetap utuh
        $this->assertDatabaseHas('customer_addresses', [
            'id' => $addressB->id,
            'label' => 'Rahasia B',
        ]);
    }

    /**
     * Skenario 8: Fitur booking guest dan alur profil yang sudah ada tetap berjalan normal.
     */
    public function test_scenario_8_guest_booking_and_profile_flow_remain_functional(): void
    {
        // 1. Booking flow remains functional
        $customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Alam Bebas',
            'phone' => '081288888888',
            'nik' => '3273012345670002',
        ]);

        $response = $this->actingAs($customer)->post(route('customer.packages.checkout.store', $this->package), [
            'package_id' => $this->package->id,
            'pickup_date' => '2026-10-29',
            'pickup_time' => '09:00',
            'return_date' => '2026-10-30',
            'return_time' => '09:00',
            'pickup_method' => 'self_pickup',
            'payment_channel' => 'bca_va',
        ]);

        $rental = Rental::withoutGlobalScopes()->latest('id')->first();
        $this->assertNotNull($rental);
        $this->assertSame($customer->id, $rental->user_id);
        $this->assertNull($rental->delivery_address);

        // 2. Profile customer page renders correctly
        $this->actingAs($customer)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Daftar Alamat Pengantaran');
    }

    /**
     * Skenario 9: Validasi menangani data kosong, format tidak valid, dan nomor telepon tidak sesuai regex.
     */
    public function test_scenario_9_validation_handles_empty_and_invalid_data(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        // Data kosong
        $resEmpty = $this->actingAs($customer)->postJson(route('customer.addresses.store'), []);
        $resEmpty->assertStatus(422)
            ->assertJsonValidationErrors(['label', 'recipient_name', 'phone_number', 'full_address', 'province', 'city', 'district']);

        // Format nomor telepon tidak diawali 08 atau bukan angka
        $resPhone = $this->actingAs($customer)->postJson(route('customer.addresses.store'), [
            'label' => 'Rumah',
            'recipient_name' => 'Budi',
            'phone_number' => '123456',
            'full_address' => 'Jl. Merdeka No. 1',
            'province' => 'Jawa Barat',
            'city' => 'Bandung',
            'district' => 'Coblong',
        ]);
        $resPhone->assertStatus(422)
            ->assertJsonValidationErrors(['phone_number']);
    }
}
