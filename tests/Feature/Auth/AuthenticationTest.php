<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.login')
            ->assertSeeText('Belum punya akun?')
            ->assertSeeText('Daftar')
            ->assertSee(route('register'));
    }

    public function test_superadmin_login_screen_can_be_rendered_separately(): void
    {
        $response = $this->get('/login/supadmin');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.superadmin-login')
            ->assertSeeText('Super Admin Login')
            ->assertDontSeeText('Forgot your password?')
            ->assertDontSeeText('Login customer/vendor')
            ->assertDontSeeText('Daftar')
            ->assertDontSeeText('Belum punya akun?');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('home', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_unverified_customer_is_redirected_to_verification_notice(): void
    {
        $user = User::factory()->unverified()->create(['role' => 'customer']);

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('verification.notice', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    #[DataProvider('vendorRoles')]
    public function test_vendor_users_can_authenticate_using_the_public_login_screen(string $role, string $expectedRoute): void
    {
        $vendorUser = User::factory()->create(['role' => $role]);

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $vendorUser->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route($expectedRoute, absolute: false));

        $this->assertAuthenticatedAs($vendorUser);
    }

    public static function vendorRoles(): array
    {
        return [
            'owner' => ['vendor_owner', 'vendor.owner.dashboard'],
            'admin or cashier' => ['vendor_admin', 'kasir.dashboard'],
        ];
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');

        $component->call('login');

        $component
            ->assertHasErrors()
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_superadmins_can_authenticate_using_the_separate_login_screen(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $component = Volt::test('pages.auth.superadmin-login')
            ->set('form.email', $superAdmin->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('success', "Selamat datang Superadmin {$superAdmin->name}!");

        $this->assertAuthenticatedAs($superAdmin);
    }

    public function test_superadmins_cannot_authenticate_using_the_public_login_screen(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $superAdmin->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasErrors('form.email')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_non_superadmins_cannot_authenticate_using_the_superadmin_login_screen(): void
    {
        $vendorAdmin = User::factory()->create(['role' => 'vendor_admin']);

        $component = Volt::test('pages.auth.superadmin-login')
            ->set('form.email', $vendorAdmin->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasErrors('form.email')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_navigation_menu_can_be_rendered(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($user);

        $response = $this->get('/profile');

        $response
            ->assertOk()
            ->assertSeeVolt('layout.navigation');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('layout.navigation');

        $component->call('logout');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_config_files_do_not_produce_output_when_included(): void
    {
        $configFiles = glob(config_path('*.php'));

        foreach ($configFiles as $file) {
            ob_start();
            require $file;
            $output = ob_get_clean();

            $this->assertSame('', $output, "Config file [{$file}] produced unexpected output when included. Ensure no leading or trailing whitespace exists outside <?php tags.");
        }
    }

    public function test_all_roles_can_login_and_access_their_respective_dashboards(): void
    {
        $vendor = Vendor::create([
            'name' => 'Tendaku Vendor Store',
            'slug' => 'tendaku-vendor-store',
            'phone' => '081234567890',
            'address' => 'Jl. Outdoor No. 1',
            'status' => 'active',
        ]);

        // 1. Customer
        $customer = User::factory()->create(['role' => 'customer']);
        $comp = Volt::test('pages.auth.login')
            ->set('form.email', $customer->email)
            ->set('form.password', 'password');
        $comp->call('login');
        $comp->assertRedirect(route('home', absolute: false));
        $this->actingAs($customer)->get(route('customer.booking.index'))->assertOk();

        // 2. Vendor Owner
        $owner = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $vendor->id,
        ]);
        $comp = Volt::test('pages.auth.login')
            ->set('form.email', $owner->email)
            ->set('form.password', 'password');
        $comp->call('login');
        $comp->assertRedirect(route('vendor.owner.dashboard', absolute: false));
        $this->actingAs($owner)->get(route('vendor.owner.dashboard'))->assertOk();

        // 3. Vendor Admin (Kasir)
        $admin = User::factory()->create([
            'role' => 'vendor_admin',
            'vendor_id' => $vendor->id,
        ]);
        $comp = Volt::test('pages.auth.login')
            ->set('form.email', $admin->email)
            ->set('form.password', 'password');
        $comp->call('login');
        $comp->assertRedirect(route('kasir.dashboard', absolute: false));
        $this->actingAs($admin)->get(route('kasir.dashboard'))->assertOk();

        // 4. Super Admin
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $comp = Volt::test('pages.auth.superadmin-login')
            ->set('form.email', $superAdmin->email)
            ->set('form.password', 'password');
        $comp->call('login');
        $comp->assertRedirect(route('dashboard', absolute: false));
        $this->actingAs($superAdmin)->get(route('dashboard'))->assertOk();
    }
}
