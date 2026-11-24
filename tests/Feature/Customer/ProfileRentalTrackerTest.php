<?php

namespace Tests\Feature\Customer;

use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProfileRentalTrackerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_profile_page_uses_indonesian_labels_and_shows_rental_tracker(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Akun Saya')
            ->assertSee('Sewa Saya')
            ->assertSee('Biodata Profil')
            ->assertSee('Edit Biodata')
            ->assertSee('Perbarui Kata Sandi')
            ->assertSee('Kata Sandi Saat Ini')
            ->assertSee('Kata Sandi Baru')
            ->assertSee('Konfirmasi Kata Sandi Baru')
            ->assertSee('Hapus Akun')
            ->assertSee('Alamat Utama')
            ->assertSee('Email Terverifikasi')
            ->assertDontSee('Update Password')
            ->assertDontSee('Delete Account')
            ->assertDontSee('Current Password');
    }

    public function test_tracker_lists_only_the_logged_in_customers_rentals_by_status(): void
    {
        $vendor = Vendor::factory()->create();
        $customer = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);

        Rental::factory()->create([
            'vendor_id' => $vendor->id,
            'user_id' => $customer->id,
            'booking_code' => 'TDK-AKTIF001',
            'status' => 'picked_up',
        ]);
        Rental::factory()->create([
            'vendor_id' => $vendor->id,
            'user_id' => $customer->id,
            'booking_code' => 'TDK-SELESAI01',
            'status' => 'completed',
        ]);
        Rental::factory()->create([
            'vendor_id' => $vendor->id,
            'user_id' => $otherCustomer->id,
            'booking_code' => 'TDK-ORANGLAIN',
            'status' => 'confirmed',
        ]);

        $this->actingAs($customer);

        Volt::test('profile.my-rentals')
            ->assertSee('TDK-AKTIF001')
            ->assertSee('Sedang Disewa')
            ->assertDontSee('TDK-SELESAI01')
            ->assertDontSee('TDK-ORANGLAIN')
            ->call('setTab', 'riwayat')
            ->assertSee('TDK-SELESAI01')
            ->assertDontSee('TDK-AKTIF001')
            ->assertDontSee('TDK-ORANGLAIN');
    }

    public function test_tracker_shows_empty_state_when_customer_has_no_active_rentals(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer);

        Volt::test('profile.my-rentals')
            ->assertSee('Belum ada sewa aktif')
            ->assertSee('Cari Perlengkapan');
    }

    public function test_password_update_shows_indonesian_validation_message(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer);

        Volt::test('profile.update-password-form')
            ->set('current_password', 'salah-total')
            ->set('password', 'password-baru-123')
            ->set('password_confirmation', 'password-baru-123')
            ->call('updatePassword')
            ->assertHasErrors(['current_password'])
            ->assertSee('Kata sandi saat ini tidak sesuai.');
    }
}
