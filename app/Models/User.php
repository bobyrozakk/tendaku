<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'name',
        'email',
        'password',
        'phone',
        'address',
        'nik',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'nik',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'nik' => 'encrypted',
            'password' => 'hashed',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function rentalsAsCustomer(): HasMany
    {
        return $this->hasMany(Rental::class, 'user_id');
    }

    public function rentalsCreated(): HasMany
    {
        return $this->hasMany(Rental::class, 'created_by');
    }

    public function paymentsReceived(): HasMany
    {
        return $this->hasMany(Payment::class, 'received_by');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isVendorStaff(): bool
    {
        return in_array($this->role, ['vendor_owner', 'vendor_admin']);
    }
}
