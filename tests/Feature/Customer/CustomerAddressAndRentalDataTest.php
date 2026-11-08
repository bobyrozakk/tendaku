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
use App\Models\VendorShippingSetting;
use App\Services\OpenRouteService;
use App\Services\ShippingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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
        $this->prepareDeliveryShipping();
        Http::preventStrayRequests();
        Http::fake([
            'https://api.openrouteservice.org/v2/directions/driving-car' => Http::response(['routes' => [['summary' => ['distance' => 12340, 'duration' => 900]]]]),
        ]);

        $customer = User::factory()->create(['role' => 'customer']);
        $addr1 = CustomerAddress::factory()->withCoordinates(-7.95, 112.62)->create([
            'user_id' => $customer->id,
            'label' => 'Rumah',
            'recipient_name' => 'Budi Rumah',
            'full_address' => 'Jl. Rumah No. 1',
            'is_default' => true,
        ]);
        $addr2 = CustomerAddress::factory()->withCoordinates(-7.96, 112.65)->create([
            'user_id' => $customer->id,
            'label' => 'Kantor',
            'recipient_name' => 'Budi Kantor',
            'full_address' => 'Jl. Kantor No. 2',
            'is_default' => false,
        ]);

        $this->actingAs($customer);
        $payload = [
            'package_id' => $this->package->id,
            'pickup_date' => '2026-10-29',
            'pickup_time' => '09:00',
            'return_date' => '2026-10-30',
            'return_time' => '09:00',
            'pickup_method' => 'delivery',
            'customer_address_id' => $addr2->id,
            'payment_channel' => 'bca_va',
        ];
        $confirmed = $this->confirmDelivery($payload);
        $response = $this->post(route('customer.packages.checkout.store', $this->package), $confirmed);

        $rental = Rental::withoutGlobalScopes()->latest('id')->first();
        $this->assertNotNull($rental);
        $response->assertRedirect(route('booking.status', $rental->booking_code));

        $this->assertSame($addr2->id, (int) $rental->customer_address_id);
        $this->assertStringContainsString('Budi Kantor', $rental->delivery_address);
        $this->assertStringContainsString('Jl. Kantor No. 2', $rental->delivery_address);
        $this->assertSame('24680.00', $rental->fresh()->delivery_fee);
        $this->assertDatabaseCount('rentals', 1);
        $this->assertDatabaseCount('payments', 1);
        Http::assertSent(fn (ClientRequest $request): bool => $request['coordinates'] === [
            [(float) $this->vendor->longitude, (float) $this->vendor->latitude],
            [(float) $addr2->longitude, (float) $addr2->latitude],
        ]);

    }

    /**
     * Skenario 6: Booking menyimpan alamat yang benar dan tetap konsisten meskipun alamat profil diubah setelah transaksi.
     */
    public function test_scenario_6_booking_snapshot_persists_even_after_address_is_modified_or_deleted(): void
    {
        $this->prepareDeliveryShipping();
        Http::preventStrayRequests();
        Http::fake([
            'https://api.openrouteservice.org/v2/directions/driving-car' => Http::response(['routes' => [['summary' => ['distance' => 12340, 'duration' => 900]]]]),
        ]);

        $customer = User::factory()->create(['role' => 'customer']);
        $address = CustomerAddress::factory()->withCoordinates()->create([
            'user_id' => $customer->id,
            'label' => 'Rumah Asli',
            'recipient_name' => 'Budi Penerima',
            'phone_number' => '081234567890',
            'full_address' => 'Jl. Asli No. 99',
            'is_default' => true,
        ]);

        $this->actingAs($customer);
        $payload = [
            'package_id' => $this->package->id,
            'pickup_date' => '2026-10-29',
            'pickup_time' => '09:00',
            'return_date' => '2026-10-30',
            'return_time' => '09:00',
            'pickup_method' => 'delivery',
            'customer_address_id' => $address->id,
        ];
        $confirmed = $this->confirmDelivery($payload);
        $response = $this->post(route('customer.packages.checkout.store', $this->package), $confirmed);

        $response->assertRedirect();
        $rental = Rental::withoutGlobalScopes()->latest('id')->firstOrFail();
        $originalSnapshot = $rental->delivery_address;
        $this->assertStringContainsString('Jl. Asli No. 99', $originalSnapshot);

        // Customer mengubah alamat di profil
        $address->update(['full_address' => 'Jl. Berubah Total No. 777']);

        // Data pada transaksi booking historis TIDAK boleh berubah
        $this->assertSame($originalSnapshot, $rental->fresh()->delivery_address);

        // Bahkan jika alamat dihapus dari profil customer
        $address->delete();
        $this->assertSame($originalSnapshot, $rental->fresh()->delivery_address);
        $this->assertSame('24680.00', $rental->fresh()->delivery_fee);
        $this->assertDatabaseCount('rentals', 1);
        $this->assertDatabaseCount('payments', 1);
        Http::assertSentCount(1);

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

    /**
     * Skenario 10: Checkout Reguler dan Checkout Paket Camping merender reusable modal Tambah Alamat tanpa nested form.
     */
    public function test_scenario_10_both_checkouts_render_shared_add_address_modal_without_nested_form(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'nik' => '3273012345670001',
        ]);

        $item = $this->package->packageItems()->first()->masterItem;

        $regularResponse = $this->actingAs($customer)
            ->withSession([
                'booking_cart' => [
                    'master_item_id' => $item->id,
                    'product_id' => $item->id,
                    'product_name' => $item->name,
                    'daily_rate' => 50000,
                    'quantity' => 1,
                    'qty' => 1,
                    'pickup_date' => '2026-10-29',
                    'return_date' => '2026-10-30',
                    'total_days' => 1,
                    'total_price' => 50000,
                    'vendor_id' => $this->vendor->id,
                    'category_name' => 'Tenda Dome',
                ],
            ])
            ->get(route('checkout.index'));

        $packageResponse = $this->actingAs($customer)
            ->get(route('customer.packages.checkout', $this->package));

        foreach ([$regularResponse, $packageResponse] as $response) {
            $response->assertOk()
                ->assertSee('id="modal-title"', false)
                ->assertSee('Tambah Alamat Pengantaran')
                ->assertSee('Alamat otomatis tersimpan ke akun Anda untuk sewa berikutnya.')
                ->assertSee('x-model="newAddressForm.label"', false)
                ->assertSee('x-model="newAddressForm.recipient_name"', false)
                ->assertSee('x-model="newAddressForm.phone_number"', false)
                ->assertSee('x-model="newAddressForm.full_address"', false)
                ->assertSee('x-model="newAddressForm.province"', false)
                ->assertSee('x-model="newAddressForm.city"', false)
                ->assertSee('x-model="newAddressForm.district"', false)
                ->assertSee('x-model="newAddressForm.village"', false)
                ->assertSee('x-model="newAddressForm.postal_code"', false)
                ->assertSee('x-model="newAddressForm.notes"', false)
                ->assertSee('id="checkout-add-address-location-picker"', false)
                ->assertSee('Titik Lokasi Pengantaran')
                ->assertSee('Pilih titik lokasi pada peta agar pengantaran lebih akurat.')
                ->assertSee('Gunakan Lokasi Saya')
                ->assertSee('syncNewAddressCoordinates($event.detail)', false)
                ->assertSee('newAddressErrors.latitude?.[0]', false)
                ->assertSee('newAddressErrors.longitude?.[0]', false)
                ->assertSee('id="modal_is_default"', false)
                ->assertSee('type="button" @click="saveNewAddress()"', false)
                ->assertDontSee('<form @submit.prevent="saveNewAddress()"', false);

            $dom = new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);

            // Pastikan modal dan Location Picker berada di dalam scope x-data yang sama dengan section-identity checkout
            $modalInsideXData = $xpath->query('//*[@x-data][.//*[@id="section-identity"]]//*[@id="modal-title"]');
            $this->assertSame(1, $modalInsideXData->length, 'Modal Tambah Alamat harus berada di dalam scope x-data yang sama dengan form checkout.');

            $pickerInsideModal = $xpath->query('//*[@x-data][.//*[@id="section-identity"]]//*[@id="checkout-add-address-location-picker"]');
            $this->assertSame(1, $pickerInsideModal->length, 'Location Picker harus berada di dalam modal Tambah Alamat pada scope x-data checkout.');

            // Pastikan tidak ada nested <form> di seluruh halaman checkout
            $nestedForms = $xpath->query('//form//form');
            $this->assertSame(0, $nestedForms->length, 'Tidak boleh ada nested <form> pada halaman checkout.');
        }
    }

    /**
     * Skenario 11: Penyimpanan alamat dari modal checkout menyimpan koordinat ke database, mengembalikan koordinat di JSON, serta memvalidasi pasangan koordinat.
     */
    public function test_scenario_11_store_address_from_checkout_modal_saves_coordinates_and_validates_coordinate_pairs(): void
    {
        $this->prepareDeliveryShipping();
        Http::preventStrayRequests();
        Http::fake([
            'https://api.openrouteservice.org/v2/directions/driving-car' => Http::response(['routes' => [['summary' => ['distance' => 12340, 'duration' => 900]]]]),
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Rina Checkout',
            'phone' => '081234567890',
            'nik' => '3273012345670009',
        ]);

        // 1. Validasi gagal jika hanya salah satu koordinat dikirim atau di luar rentang
        $invalidCoordRes = $this->actingAs($customer)->postJson(route('customer.addresses.store'), [
            'label' => 'Rumah',
            'recipient_name' => 'Rina Checkout',
            'phone_number' => '081234567890',
            'full_address' => 'Jl. Soekarno Hatta No. 9',
            'province' => 'Jawa Timur',
            'city' => 'Malang',
            'district' => 'Lowokwaru',
            'latitude' => -7.9432109,
            'longitude' => null,
        ]);

        $invalidCoordRes->assertStatus(422)
            ->assertJsonValidationErrors(['longitude']);
        $this->assertDatabaseCount('customer_addresses', 0);

        // 2. Simpan alamat lengkap beserta titik lokasi dari modal checkout
        $storeRes = $this->actingAs($customer)->postJson(route('customer.addresses.store'), [
            'label' => 'Basecamp Pendakian',
            'recipient_name' => 'Rina Checkout',
            'phone_number' => '081234567890',
            'full_address' => 'Jl. Soekarno Hatta No. 9',
            'province' => 'Jawa Timur',
            'city' => 'Malang',
            'district' => 'Lowokwaru',
            'village' => 'Mojolangu',
            'postal_code' => '65142',
            'notes' => 'Sebelah minimarket',
            'latitude' => -7.9432109,
            'longitude' => 112.6210987,
            'is_default' => true,
        ]);

        $storeRes->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.label', 'Basecamp Pendakian')
            ->assertJsonPath('data.is_default', true);

        $createdId = (int) $storeRes->json('data.id');
        $address = CustomerAddress::findOrFail($createdId);

        $this->assertTrue($address->hasCoordinates());
        $this->assertEqualsWithDelta(-7.9432109, (float) $address->latitude, 0.0000001);
        $this->assertEqualsWithDelta(112.6210987, (float) $address->longitude, 0.0000001);

        // 3. Alamat baru yang terpilih dapat langsung dipakai pada checkout paket camping
        $this->actingAs($customer);
        $payload = [
            'package_id' => $this->package->id,
            'pickup_date' => '2026-10-29',
            'pickup_time' => '09:00',
            'return_date' => '2026-10-30',
            'return_time' => '09:00',
            'pickup_method' => 'delivery',
            'customer_address_id' => $address->id,
            'payment_channel' => 'bca_va',
        ];
        $confirmed = $this->confirmDelivery($payload);
        $checkoutRes = $this->post(route('customer.packages.checkout.store', $this->package), $confirmed);

        $rental = Rental::withoutGlobalScopes()->latest('id')->firstOrFail();
        $checkoutRes->assertRedirect(route('booking.status', $rental->booking_code));
        $this->assertSame($address->id, (int) $rental->customer_address_id);
        $this->assertStringContainsString('Jl. Soekarno Hatta No. 9', (string) $rental->delivery_address);
        $this->assertSame('24680.00', $rental->fresh()->delivery_fee);
        $this->assertDatabaseCount('rentals', 1);
        $this->assertDatabaseCount('payments', 1);
        Http::assertSentCount(1);

    }

    /**
     * Skenario 12: Integrasi menyeluruh keamanan tenant, penanganan null vs 0, batas koordinat, dan kesiapan kalkulasi ongkir (Vendor + CustomerAddress).
     */
    public function test_scenario_12_coordinate_security_null_handling_and_shipping_readiness_integration(): void
    {
        $owner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $this->vendor->id,
        ]);

        $customerA = User::factory()->create(['role' => 'customer']);
        $customerB = User::factory()->create(['role' => 'customer']);

        // 1. Mass Assignment / Cross-Tenant Protection pada store alamat customer
        $storeEmptyCoordRes = $this->actingAs($customerA)->postJson(route('customer.addresses.store'), [
            'user_id' => $customerB->id,
            'label' => 'Alamat Tanpa Titik',
            'recipient_name' => 'Customer A',
            'phone_number' => '081234567890',
            'full_address' => 'Jl. Veteran No. 12',
            'province' => 'Jawa Timur',
            'city' => 'Malang',
            'district' => 'Klojen',
            'latitude' => '',
            'longitude' => '',
        ]);

        $storeEmptyCoordRes->assertCreated();
        $addressWithoutCoords = CustomerAddress::findOrFail((int) $storeEmptyCoordRes->json('data.id'));

        // Pastikan user_id tidak bisa di-spoof ke customerB dan koordinat kosong tetap null (bukan 0)
        $this->assertSame($customerA->id, (int) $addressWithoutCoords->user_id);
        $this->assertNull($addressWithoutCoords->latitude);
        $this->assertNull($addressWithoutCoords->longitude);
        $this->assertFalse($addressWithoutCoords->hasCoordinates());

        // 2. Koordinat bernilai 0 valid (tidak dianggap null)
        $zeroCoordRes = $this->actingAs($customerA)->postJson(route('customer.addresses.store'), [
            'label' => 'Titik Nol',
            'recipient_name' => 'Customer A',
            'phone_number' => '081234567890',
            'full_address' => 'Jl. Khatulistiwa No. 1',
            'province' => 'Kalimantan Barat',
            'city' => 'Pontianak',
            'district' => 'Pontianak Utara',
            'latitude' => 0,
            'longitude' => 0,
        ]);

        $zeroCoordRes->assertCreated();
        $zeroAddress = CustomerAddress::findOrFail((int) $zeroCoordRes->json('data.id'));
        $this->assertTrue($zeroAddress->hasCoordinates());
        $this->assertSame(0.0, $zeroAddress->latitude);
        $this->assertSame(0.0, $zeroAddress->longitude);

        // 3. Validasi batas geografis pada endpoint AddressController (-90..90 dan -180..180)
        $outOfBoundsRes = $this->actingAs($customerA)->postJson(route('customer.addresses.store'), [
            'label' => 'Invalid Bounds',
            'recipient_name' => 'Customer A',
            'phone_number' => '081234567890',
            'full_address' => 'Jl. Invalid No. 99',
            'province' => 'Jawa Timur',
            'city' => 'Malang',
            'district' => 'Lowokwaru',
            'latitude' => -91.5,
            'longitude' => 181.2,
        ]);

        $outOfBoundsRes->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);

        // 4. End-to-End: Owner menyimpan koordinat toko, Customer menyimpan koordinat alamat, siap dihitung ShippingCalculator
        $this->actingAs($owner)
            ->put(route('vendor.shipping.location.update'), [
                'address' => 'Jl. Soekarno Hatta No. 10, Malang',
                'city' => 'Kota Malang',
                'latitude' => -7.9467123,
                'longitude' => 112.6156789,
            ])
            ->assertRedirect(route('vendor.shipping.index'));

        VendorShippingSetting::factory()->for($this->vendor)->create([
            'rate_per_km' => 4000,
            'weight_threshold_kg' => 10,
            'extra_weight_fee_per_kg' => 1500,
        ]);

        $deliveryAddressRes = $this->actingAs($customerA)->postJson(route('customer.addresses.store'), [
            'label' => 'Basecamp Bromo',
            'recipient_name' => 'Customer A',
            'phone_number' => '081234567890',
            'full_address' => 'Jl. Raya Tumpang No. 45',
            'province' => 'Jawa Timur',
            'city' => 'Malang',
            'district' => 'Tumpang',
            'latitude' => -7.9825123,
            'longitude' => 112.7512345,
        ]);

        $deliveryAddressRes->assertCreated();
        $deliveryAddress = CustomerAddress::findOrFail((int) $deliveryAddressRes->json('data.id'));
        $freshVendor = $this->vendor->fresh();

        $this->assertTrue($freshVendor->hasCoordinates());
        $this->assertTrue($deliveryAddress->hasCoordinates());

        config()->set('services.openrouteservice.api_key', 'test-ors-key');
        config()->set('services.openrouteservice.base_url', 'https://api.openrouteservice.org');

        Http::fake([
            'https://api.openrouteservice.org/*' => Http::response([
                'routes' => [
                    [
                        'summary' => [
                            'distance' => 12500.0,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $masterItem = MasterItem::withoutGlobalScopes()->where('vendor_id', $this->vendor->id)->firstOrFail();
        $masterItem->update(['weight_kg' => 3.0]);

        $routeResult = app(OpenRouteService::class)->getDrivingDistance(
            (float) $freshVendor->latitude,
            (float) $freshVendor->longitude,
            (float) $deliveryAddress->latitude,
            (float) $deliveryAddress->longitude
        );

        $this->assertTrue($routeResult['success']);
        $this->assertSame(12.5, $routeResult['distance_km']);

        $shippingResult = app(ShippingCalculator::class)->calculateForVendor(
            $freshVendor,
            (float) $routeResult['distance_km'],
            [['item' => $masterItem, 'quantity' => 2]]
        );

        $this->assertSame(12.5, $shippingResult['distance_km']);
        $this->assertSame(50000, $shippingResult['distance_fee']);
        $this->assertSame(0, $shippingResult['weight_fee']);
        $this->assertSame(50000, $shippingResult['total_fee']);
    }

    private function prepareDeliveryShipping(): void
    {
        $this->vendor->update(['latitude' => -7.94, 'longitude' => 112.61]);
        VendorShippingSetting::factory()->for($this->vendor)->create([
            'rate_per_km' => 2000, 'weight_threshold_kg' => 5, 'extra_weight_fee_per_kg' => 1000,
        ]);
        $this->package->packageItems()->first()->masterItem->update(['weight_kg' => 3.0]);
        config(['services.openrouteservice.api_key' => 'test-ors-key', 'services.openrouteservice.base_url' => 'https://api.openrouteservice.org', 'services.openrouteservice.profile' => 'driving-car']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function confirmDelivery(array $payload): array
    {
        $preview = $this->postJson(route('checkout.shipping.preview'), $payload + ['checkout_type' => 'package'])
            ->assertOk()->assertJsonPath('distance_km', 12.34)->assertJsonPath('total_weight_kg', 3)
            ->assertJsonPath('delivery_fee', 24680)->json();
        $this->assertDatabaseCount('rentals', 0);
        $this->assertDatabaseCount('payments', 0);

        return $payload + ['expected_delivery_fee' => $preview['delivery_fee'], 'expected_shipping_context' => $preview['shipping_context'], 'checkout_attempt_id' => (string) Str::uuid()];
    }
}
