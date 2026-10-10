<?php

namespace Tests\Feature\Auth;

use App\Models\User;
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
            ->assertSeeText('Daftar sebagai customer')
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
            ->assertDontSeeText('Daftar sebagai customer')
            ->assertDontSeeText('Belum punya akun?');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    #[DataProvider('vendorRoles')]
    public function test_vendor_users_can_authenticate_using_the_public_login_screen(string $role): void
    {
        $vendorUser = User::factory()->create(['role' => $role]);

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $vendorUser->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($vendorUser);
    }

    public static function vendorRoles(): array
    {
        return [
            'owner' => ['vendor_owner'],
            'admin or cashier' => ['vendor_admin'],
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
            ->assertRedirect(route('dashboard', absolute: false));

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
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/dashboard');

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
}
