<?php

namespace Tests;

use App\Models\User;
use App\Models\Vendor;
use App\Services\CurrentVendor;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Login sebagai user vendor tertentu dan set CurrentVendor aktif.
     */
    public function actingAsVendor(User|Vendor $vendorOrUser, string $role = 'vendor_owner'): static
    {
        if ($vendorOrUser instanceof Vendor) {
            $user = User::factory()->create([
                'vendor_id' => $vendorOrUser->id,
                'role' => $role,
                'email_verified_at' => now(),
            ]);
            $vendorId = $vendorOrUser->id;
        } else {
            $user = $vendorOrUser;
            $vendorId = $user->vendor_id;
        }

        CurrentVendor::set($vendorId);

        return $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        CurrentVendor::clear();

        parent::tearDown();
    }
}
