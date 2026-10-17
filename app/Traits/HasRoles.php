<?php

namespace App\Traits;

trait HasRoles
{
    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_VENDOR_OWNER = 'vendor_owner';

    public const ROLE_VENDOR_ADMIN = 'vendor_admin';

    public const ROLE_CUSTOMER = 'customer';

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isVendorOwner(): bool
    {
        return $this->role === self::ROLE_VENDOR_OWNER;
    }

    public function isVendorAdmin(): bool
    {
        return $this->role === self::ROLE_VENDOR_ADMIN;
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function isVendorStaff(): bool
    {
        return in_array($this->role, [self::ROLE_VENDOR_OWNER, self::ROLE_VENDOR_ADMIN], true);
    }

    /**
     * Determine if the user has any of the given roles.
     *
     * @param  string|array<int, string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        if (is_string($roles)) {
            return $this->role === $roles;
        }

        return in_array($this->role, $roles, true);
    }
}
