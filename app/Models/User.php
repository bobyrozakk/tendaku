<?php

namespace App\Models;

use App\Traits\HasRoles;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasRoles, MustVerifyEmailTrait, Notifiable, SoftDeletes;
    // pengecekan role ada di trait HasRoles

    protected $fillable = [
        'vendor_id',
        'name',
        'email',
        'password',
        'phone',
        'address',
        'nik',
        'role',
        'is_active',
        'cart',
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
            'is_active' => 'boolean',
            'cart' => 'array',
        ];
    }

    public function isActive(): bool
    {
        return (bool) ($this->is_active ?? true);
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

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'user_id');
    }

    public function defaultAddress(): ?CustomerAddress
    {
        return $this->addresses()->where('is_default', true)->first()
            ?? $this->addresses()->latest('id')->first();
    }
}
