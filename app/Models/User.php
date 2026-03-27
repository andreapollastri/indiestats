<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Support\UserPreferences;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'locale', 'timezone', 'role'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

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
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
        ];
    }

    /**
     * Sites created by this user (FK owner on sites.user_id).
     *
     * @return HasMany<Site, $this>
     */
    public function ownedSites(): HasMany
    {
        return $this->hasMany(Site::class, 'user_id');
    }

    /**
     * Sites assigned for base-role UI access (many-to-many).
     *
     * @return BelongsToMany<Site, $this>
     */
    public function assignedSites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class)->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function canAccessSite(Site $site): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->assignedSites()->where('sites.id', $site->id)->exists();
    }

    /**
     * Query for sites visible in the UI (admin: all; base: assigned only).
     *
     * @return Builder<Site>
     */
    public function accessibleSitesQuery(): Builder
    {
        if ($this->isAdmin()) {
            return Site::query();
        }

        return Site::query()->whereHas('assignedUsers', fn ($q) => $q->where('users.id', $this->id));
    }

    /**
     * Locale for notifications and mail (including queued sends).
     */
    public function preferredLocale(): string
    {
        $locale = $this->locale;
        if (is_string($locale) && UserPreferences::isAllowedLocale($locale)) {
            return $locale;
        }

        return 'en';
    }
}
