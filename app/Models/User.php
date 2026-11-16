<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_USER = 'user';
    public const ROLE_ADMIN = 'admin';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';     // blocked by an admin
    public const STATUS_DEACTIVATED = 'deactivated'; // paused by the user; logging in again reactivates

    /**
     * Role and status are deliberately not mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password_set_at' => 'datetime',
    ];

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /** Blocked by an admin: cannot log in at all. */
    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isDeactivated(): bool
    {
        return $this->status === self::STATUS_DEACTIVATED;
    }

    /** Called on a successful login: a self-deactivated account comes back to life. */
    public function reactivateIfDeactivated(): bool
    {
        if (! $this->isDeactivated()) {
            return false;
        }
        $this->forceFill(['status' => self::STATUS_ACTIVE])->save();
        Activity::log($this, 'account.reactivated', 'Reactivated your account');

        return true;
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function currentSubscription(): ?Subscription
    {
        return $this->subscriptions()->current()->with('plan')->latest('id')->first();
    }

    /** The plan in effect: the current subscription's plan, else the Free plan. */
    public function currentPlan(): ?Plan
    {
        $sub = $this->currentSubscription();

        return $sub ? $sub->plan : Plan::where('key', Plan::FREE)->first();
    }

    public function isPro(): bool
    {
        $plan = optional($this->currentSubscription())->plan;

        return $plan !== null && ! $plan->isFree();
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $initials = collect($parts)->filter()->take(2)->map(fn ($p) => Str::upper(Str::substr($p, 0, 1)))->implode('');

        return $initials ?: Str::upper(Str::substr($this->email, 0, 1));
    }

    public function firstName(): string
    {
        return Str::before(trim($this->name), ' ') ?: $this->name;
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function links()
    {
        return $this->hasMany(Link::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    /** The user's profile, created with sensible defaults on first use. */
    public function profileRecord(): Profile
    {
        if ($this->profile) {
            return $this->profile;
        }

        $profile = $this->profile()->firstOrCreate([], [
            'display_name' => Str::limit($this->name, 60, ''),
            'theme'        => config('linkora_design.default_theme'),
        ]);
        $this->setRelation('profile', $profile);

        return $profile;
    }

    public function hasOwnPassword(): bool
    {
        return $this->password_set_at !== null;
    }

    /** Full public page URL, e.g. https://linkora.in/naresh (or http://127.0.0.1:8000/naresh locally). */
    public function publicLink(): string
    {
        return route('profile.public', $this->username);
    }

    /** The public URL without the scheme, for display: "linkora.in/naresh". */
    public function publicUrl(): string
    {
        return preg_replace('#^https?://#', '', $this->publicLink());
    }
}
