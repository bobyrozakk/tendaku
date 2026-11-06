<?php

namespace Tests\Feature\Customer;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerProfileAddressCoordinateTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_address_with_coordinates_via_profile_volt_component(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Rina Kusuma',
            'phone' => '081234567890',
        ]);

        $this->actingAs($customer);

        Volt::test('profile.manage-addresses')
            ->call('startCreating')
            ->assertSet('isCreating', true)
            ->assertSee('id="customer-address-picker-create"', false)
            ->set('label', 'Rumah Malang')
            ->set('recipient_name', 'Rina Kusuma')
            ->set('phone_number', '081234567890')
            ->set('full_address', 'Jl. Soekarno Hatta Indah No. 15')
            ->set('province', 'Jawa Timur')
            ->set('city', 'Kota Malang')
            ->set('district', 'Lowokwaru')
            ->set('village', 'Mojolangu')
            ->set('postal_code', '65142')
            ->set('notes', 'Rumah pagar hijau')
            ->set('latitude', -7.9432109)
            ->set('longitude', 112.6210987)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('isCreating', false)
            ->assertSee('Titik Lokasi Tersimpan (-7.94321, 112.62110)');

        $address = CustomerAddress::where('user_id', $customer->id)->firstOrFail();

        $this->assertTrue($address->hasCoordinates());
        $this->assertEqualsWithDelta(-7.9432109, $address->latitude, 0.0000001);
        $this->assertEqualsWithDelta(112.6210987, $address->longitude, 0.0000001);
        $this->assertTrue($address->is_default);
    }

    public function test_customer_can_edit_address_coordinates_and_view_previously_saved_coordinates(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $address = CustomerAddress::factory()
            ->for($customer)
            ->withCoordinates(-7.9797000, 112.6304000)
            ->create([
                'label' => 'Kantor',
                'full_address' => 'Jl. Basuki Rahmat No. 20',
                'city' => 'Kota Malang',
                'district' => 'Klojen',
                'province' => 'Jawa Timur',
            ]);

        $this->actingAs($customer);

        Volt::test('profile.manage-addresses')
            ->call('startEditing', $address->id)
            ->assertSet('editingId', $address->id)
            ->assertSet('latitude', -7.9797)
            ->assertSet('longitude', 112.6304)
            ->assertSee('id="customer-address-picker-'.$address->id.'"', false)
            ->assertSee('value="-7.9797000"', false)
            ->assertSee('value="112.6304000"', false)
            ->set('latitude', -7.9551234)
            ->set('longitude', 112.6123456)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('editingId', null);

        $address->refresh();

        $this->assertTrue($address->hasCoordinates());
        $this->assertEqualsWithDelta(-7.9551234, $address->latitude, 0.0000001);
        $this->assertEqualsWithDelta(112.6123456, $address->longitude, 0.0000001);
        $this->assertSame('Jl. Basuki Rahmat No. 20', $address->full_address);
    }

    public function test_legacy_address_without_coordinates_remains_readable_and_valid_without_auto_saving_malang_center(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $legacyAddress = CustomerAddress::factory()->for($customer)->create([
            'label' => 'Rumah Lama',
            'recipient_name' => 'Budi Santoso',
            'phone_number' => '081234567899',
            'full_address' => 'Jl. Kawi Atas No. 11',
            'province' => 'Jawa Timur',
            'city' => 'Kota Malang',
            'district' => 'Klojen',
            'latitude' => null,
            'longitude' => null,
            'is_default' => true,
        ]);

        $this->actingAs($customer);

        // 1. Daftar alamat menampilkan informasi bahwa pin lokasi belum diatur
        $component = Volt::test('profile.manage-addresses')
            ->assertSee('Rumah Lama')
            ->assertSee('Pin lokasi belum diatur');

        // 2. Saat diedit tanpa menyentuh peta, tampil informasi pin belum diatur dan tetap dapat disimpan tanpa menyimpan koordinat default Malang Raya
        $component->call('startEditing', $legacyAddress->id)
            ->assertSet('latitude', null)
            ->assertSet('longitude', null)
            ->assertSee('Pin lokasi belum diatur untuk alamat ini.')
            ->set('notes', 'Dekat perempatan Kawi')
            ->call('save')
            ->assertHasNoErrors();

        $legacyAddress->refresh();

        $this->assertFalse($legacyAddress->hasCoordinates());
        $this->assertNull($legacyAddress->latitude);
        $this->assertNull($legacyAddress->longitude);
        $this->assertSame('Dekat perempatan Kawi', $legacyAddress->notes);
    }

    public function test_customer_cannot_edit_or_delete_another_users_address_via_volt_component(): void
    {
        $customerA = User::factory()->create(['role' => 'customer']);
        $customerB = User::factory()->create(['role' => 'customer']);

        $addressB = CustomerAddress::factory()
            ->for($customerB)
            ->withCoordinates(-7.9797000, 112.6304000)
            ->create(['label' => 'Alamat Milik B']);

        $this->actingAs($customerA);

        try {
            Volt::test('profile.manage-addresses')
                ->call('startEditing', $addressB->id);
            $this->fail('Expected ModelNotFoundException when editing another customer address.');
        } catch (ModelNotFoundException) {
            $addressB->refresh();
            $this->assertEqualsWithDelta(-7.9797000, $addressB->latitude, 0.0000001);
        }

        try {
            Volt::test('profile.manage-addresses')
                ->call('deleteAddress', $addressB->id);
            $this->fail('Expected ModelNotFoundException when deleting another customer address.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseHas('customer_addresses', ['id' => $addressB->id]);
        }
    }

    /**
     * @return array<string, array{mixed, mixed, array<int, string>}>
     */
    public static function invalidCoordinatePairProvider(): array
    {
        return [
            'only latitude provided' => [-7.9797, null, ['longitude']],
            'only longitude provided' => [null, 112.6304, ['latitude']],
            'latitude below -90' => [-91.5, 112.6304, ['latitude']],
            'latitude above 90' => [92.0, 112.6304, ['latitude']],
            'longitude below -180' => [-7.9797, -181.0, ['longitude']],
            'longitude above 180' => [-7.9797, 185.0, ['longitude']],
            'non-numeric coordinates' => ['bukan-angka', 'invalid', ['latitude', 'longitude']],
        ];
    }

    /**
     * @param  array<int, string>  $expectedErrors
     */
    #[DataProvider('invalidCoordinatePairProvider')]
    public function test_coordinate_validation_rejects_invalid_or_incomplete_coordinate_pairs(
        mixed $invalidLatitude,
        mixed $invalidLongitude,
        array $expectedErrors,
    ): void {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer);

        Volt::test('profile.manage-addresses')
            ->call('startCreating')
            ->set('label', 'Rumah')
            ->set('recipient_name', 'Budi')
            ->set('phone_number', '081234567890')
            ->set('full_address', 'Jl. Ijen No. 10')
            ->set('province', 'Jawa Timur')
            ->set('city', 'Kota Malang')
            ->set('district', 'Klojen')
            ->set('latitude', $invalidLatitude)
            ->set('longitude', $invalidLongitude)
            ->call('save')
            ->assertHasErrors($expectedErrors);

        $this->assertDatabaseCount('customer_addresses', 0);
    }

    public function test_zero_coordinates_are_accepted_as_valid_pair(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer);

        Volt::test('profile.manage-addresses')
            ->call('startCreating')
            ->set('label', 'Titik Nol')
            ->set('recipient_name', 'Budi')
            ->set('phone_number', '081234567890')
            ->set('full_address', 'Jl. Khatulistiwa No. 1')
            ->set('province', 'Jawa Timur')
            ->set('city', 'Kota Malang')
            ->set('district', 'Klojen')
            ->set('latitude', 0)
            ->set('longitude', 0)
            ->call('save')
            ->assertHasNoErrors();

        $address = CustomerAddress::where('user_id', $customer->id)->firstOrFail();

        $this->assertTrue($address->hasCoordinates());
        $this->assertSame(0.0, $address->latitude);
        $this->assertSame(0.0, $address->longitude);
    }

    public function test_existing_crud_and_default_address_features_continue_working_properly(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $addr1 = CustomerAddress::factory()
            ->for($customer)
            ->withCoordinates(-7.9700000, 112.6300000)
            ->create(['label' => 'Alamat 1', 'is_default' => true]);

        $addr2 = CustomerAddress::factory()
            ->for($customer)
            ->create(['label' => 'Alamat 2', 'is_default' => false]);

        $this->actingAs($customer);

        $component = Volt::test('profile.manage-addresses');

        // Set Alamat 2 sebagai default
        $component->call('setAsDefault', $addr2->id);
        $this->assertFalse($addr1->fresh()->is_default);
        $this->assertTrue($addr2->fresh()->is_default);

        // Hapus Alamat 2 (default otomatis kembali ke Alamat 1)
        $component->call('deleteAddress', $addr2->id);
        $this->assertDatabaseMissing('customer_addresses', ['id' => $addr2->id]);
        $this->assertTrue($addr1->fresh()->is_default);
    }
}
