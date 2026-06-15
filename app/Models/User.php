<?php

namespace App\Models;

use App\Domains\Auth\Notifications\ResetPasswordNotification;
use App\Domains\Auth\Notifications\VerifyEmailNotification;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'gender',
        'email',
        'password',
        'role',
        'status',
        'avatar',
        'wp_legacy_id',
        'wp_password_hash',
        'email_verified_at',
        'welcome_email_sent_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'wp_password_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'welcome_email_sent_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'wp_legacy_id' => 'integer',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin'
            && $this->isAdmin()
            && $this->isActive();
    }

    /**
     * @return HasMany<DuaList, $this>
     */
    public function duaLists(): HasMany
    {
        return $this->hasMany(DuaList::class);
    }

    /**
     * @return HasMany<DuaSubmission, $this>
     */
    public function duaSubmissions(): HasMany
    {
        return $this->hasMany(DuaSubmission::class);
    }

    /**
     * @return HasMany<UserEntitlement, $this>
     */
    public function entitlements(): HasMany
    {
        return $this->hasMany(UserEntitlement::class);
    }

    /**
     * @return HasMany<StripePayment, $this>
     */
    public function stripePayments(): HasMany
    {
        return $this->hasMany(StripePayment::class);
    }

    /**
     * @return HasMany<CommunityDuaCompletion, $this>
     */
    public function communityDuaCompletions(): HasMany
    {
        return $this->hasMany(CommunityDuaCompletion::class);
    }

    /**
     * @return HasMany<CommunityDuaSkip, $this>
     */
    public function communityDuaSkips(): HasMany
    {
        return $this->hasMany(CommunityDuaSkip::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<CommunityDuaQueueState, $this>
     */
    public function communityDuaQueueState(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CommunityDuaQueueState::class);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
