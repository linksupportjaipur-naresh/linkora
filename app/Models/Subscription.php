<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    public const ACTIVE = 'active';
    public const CANCELLED = 'cancelled';

    protected $fillable = ['user_id', 'plan_id', 'status', 'billing_cycle', 'starts_at', 'ends_at', 'source', 'created_by'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeCurrent($query)
    {
        return $query->where('status', self::ACTIVE)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function isCurrent(): bool
    {
        return $this->status === self::ACTIVE && (! $this->ends_at || $this->ends_at->isFuture());
    }

    public function displayStatus(): string
    {
        if ($this->status === self::ACTIVE && $this->ends_at && $this->ends_at->isPast()) {
            return 'expired';
        }

        return $this->status;
    }
}
