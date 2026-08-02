<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'user_type', 'name', 'email', 'phone', 'password', 'avatar_path', 'locale',
        'status', 'bio', 'preferences', 'last_login_at', 'tos_accepted_at', 'tos_version',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'tos_accepted_at' => 'datetime',
            'preferences' => 'array',
            'password' => 'hashed',
        ];
    }

    /* ------------------------------------------------------------------ */

    public function isStaff(): bool
    {
        return $this->user_type === 'staff';
    }

    public function isListener(): bool
    {
        return $this->user_type === 'listener';
    }

    /** An account owns a public artist profile when it holds the Artist role. */
    public function isArtist(): bool
    {
        return $this->hasRole('Artist');
    }

    /**
     * Who may enter the Admin Portal: Bangladesh Betar staff and dual-app
     * ("both") accounts, e.g. artists, who get a profile-focused view.
     * Listeners never do.
     */
    public function canAccessAdmin(): bool
    {
        return in_array($this->user_type, ['staff', 'both'], true);
    }

    /** The artist profile this account owns, if it is an artist account. */
    public function artist(): HasOne
    {
        return $this->hasOne(Artist::class);
    }

    /** URL for the account avatar, or null. */
    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? asset('storage/'.$this->avatar_path) : null;
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['active', 'trialing', 'grace'])
            ->latest('started_at');
    }

    public function isPremium(): bool
    {
        $subscription = $this->activeSubscription;

        return $subscription !== null && $subscription->plan?->code === 'premium';
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function follows(): HasMany
    {
        return $this->hasMany(Follow::class);
    }

    public function playHistories(): HasMany
    {
        return $this->hasMany(PlayHistory::class);
    }

    public function queue(): HasOne
    {
        return $this->hasOne(UserQueue::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }
}
