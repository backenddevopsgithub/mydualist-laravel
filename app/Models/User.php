<?php

namespace App\Models;

use App\Domains\Onboarding\Services\OnboardingVerificationService;
use App\Domains\Billing\Services\EntitlementGrantService;
use App\Enums\EntitlementKey;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Impersonate, Notifiable;

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

    public function isSuperAdmin(): bool
    {
        if (! $this->isAdmin() || ! $this->isActive()) {
            return false;
        }

        $superAdminEmails = config('mydualist.super_admin_emails', []);

        if ($superAdminEmails === []) {
            return false;
        }

        return in_array(mb_strtolower($this->email), $superAdminEmails, true);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function canImpersonate(): bool
    {
        return $this->isAdmin() && $this->isActive();
    }

    public function canBeImpersonated(): bool
    {
        return ! $this->isAdmin() && $this->isActive();
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
     * @return HasMany<EntitlementGrant, $this>
     */
    public function entitlementGrants(): HasMany
    {
        return $this->hasMany(EntitlementGrant::class);
    }

    public function hasEntitlement(EntitlementKey|string $key, ?int $duaListId = null): bool
    {
        return app(EntitlementGrantService::class)->hasEntitlement($this, $key, $duaListId);
    }

    public function entitlementQuantity(EntitlementKey|string $key, ?int $duaListId = null): int
    {
        return app(EntitlementGrantService::class)->quantity($this, $key, $duaListId);
    }

    /**
     * @return HasMany<BillingPurchase, $this>
     */
    public function billingPurchases(): HasMany
    {
        return $this->hasMany(BillingPurchase::class);
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
     * @return HasOne<CommunityDuaQueueState, $this>
     */
    public function communityDuaQueueState(): HasOne
    {
        return $this->hasOne(CommunityDuaQueueState::class);
    }

    public function sendEmailVerificationNotification(): void
    {
        if ($this->hasVerifiedEmail()) {
            return;
        }

        app(OnboardingVerificationService::class)->sendIfNeeded($this);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function displayName(): string
    {
        $fromParts = trim(((string) $this->first_name).' '.((string) $this->last_name));

        if ($fromParts !== '') {
            return $fromParts;
        }

        return trim((string) $this->name);
    }
}
