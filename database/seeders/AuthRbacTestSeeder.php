<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use LogicException;

class AuthRbacTestSeeder extends Seeder
{
    private const PASSWORD = 'password';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('AuthRbacTestSeeder may only be run in local or testing environments.');
        }

        $superAdmin = $this->upsertUser('super-admin@tendaku.test', [
            'name' => 'Demo Super Admin',
            'vendor_id' => null,
            'role' => 'super_admin',
            'email_verified_at' => now(),
        ]);

        $vendor = Vendor::withTrashed()->updateOrCreate(
            ['slug' => 'auth-rbac-demo'],
            [
                'name' => 'Demo Auth RBAC Vendor',
                'phone' => '081234567890',
                'email' => 'vendor@tendaku.test',
                'address' => 'Demo address for local RBAC testing',
                'city' => 'Malang',
                'status' => 'active',
                'approved_at' => now(),
                'approved_by' => $superAdmin->id,
            ],
        );

        if ($vendor->trashed()) {
            $vendor->restore();
        }

        $this->upsertUser('vendor-owner@tendaku.test', [
            'name' => 'Demo Vendor Owner',
            'vendor_id' => $vendor->id,
            'role' => 'vendor_owner',
            'email_verified_at' => now(),
        ]);

        $this->upsertUser('vendor-admin@tendaku.test', [
            'name' => 'Demo Vendor Admin',
            'vendor_id' => $vendor->id,
            'role' => 'vendor_admin',
            'email_verified_at' => now(),
        ]);

        $this->upsertUser('customer@tendaku.test', [
            'name' => 'Demo Customer',
            'vendor_id' => null,
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $this->upsertUser('unverified-customer@tendaku.test', [
            'name' => 'Demo Unverified Customer',
            'vendor_id' => null,
            'role' => 'customer',
            'email_verified_at' => null,
        ]);
    }

    /**
     * @param  array{name: string, vendor_id: int|null, role: string, email_verified_at: Carbon|null}  $attributes
     */
    private function upsertUser(string $email, array $attributes): User
    {
        $user = User::withTrashed()->updateOrCreate(
            ['email' => $email],
            [...$attributes, 'password' => self::PASSWORD],
        );

        if ($user->trashed()) {
            $user->restore();
        }

        return $user;
    }
}
