<?php

namespace Tests\Feature\Customer;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavbarAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_navbar_displays_avatar_image_when_user_has_avatar_url(): void
    {
        $user = User::factory()->create([
            'name' => 'Bunga Aulia',
            'email' => 'bunga@example.com',
            'role' => 'customer',
            'avatar_url' => 'https://lh3.googleusercontent.com/a/sample-photo',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('https://lh3.googleusercontent.com/a/sample-photo');
        $response->assertSee('Bunga Aulia (Akun Saya)');
    }

    public function test_navbar_displays_initials_circle_when_user_has_no_avatar(): void
    {
        $user = User::factory()->create([
            'name' => 'Andini Tribuana',
            'email' => 'andini@example.com',
            'role' => 'customer',
            'avatar_url' => null,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('AT');
        $response->assertSee('Andini Tribuana (Akun Saya)');
    }

    public function test_user_initials_attribute(): void
    {
        $user1 = new User(['name' => 'John Doe']);
        $this->assertSame('JD', $user1->initials);

        $user2 = new User(['name' => 'Alexander']);
        $this->assertSame('A', $user2->initials);

        $user3 = new User(['name' => 'Muhammad Budi Santoso']);
        $this->assertSame('MB', $user3->initials);
    }
}
