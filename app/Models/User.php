<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Models\Wallet;

#[Fillable(['name', 'name_ur', 'email', 'address', 'address_ur', 'password', 'phone_numbers', 'bank_accounts', 'profile_photo_path', 'cnic_front_path', 'cnic_back_path', 'status', 'role_id', 'rejection_reason', 'rejected_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REJECTED = 'rejected';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isSuperAdmin(): bool
    {
        $this->loadMissing('role');

        return $this->role?->name === 'Super admin';
    }

    /**
     * @return HasOne<ClientAccount, $this>
     */
    public function clientAccount(): HasOne
    {
        return $this->hasOne(ClientAccount::class);
    }

    /**
     * @return HasOne<Wallet, $this>
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'phone_numbers' => 'array',
            'bank_accounts' => 'array',
            'rejected_at' => 'datetime',
        ];
    }
}
