<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class LoginCode extends Model
{
    public const LIFETIME_MINUTES = 10;
    public const MAX_ATTEMPTS = 5;
    public const RESEND_SECONDS = 60;

    protected $fillable = ['email', 'code_hash', 'intent', 'ip_address', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    /**
     * Create a fresh code for an email, cancelling any earlier unused ones.
     * Returns [LoginCode $record, string $plainCode].
     */
    public static function issue(string $email, string $intent, ?string $ip): array
    {
        static::where('email', $email)->whereNull('used_at')->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $record = static::create([
            'email'      => $email,
            'code_hash'  => Hash::make($code),
            'intent'     => $intent,
            'ip_address' => $ip,
            'expires_at' => now()->addMinutes(self::LIFETIME_MINUTES),
        ]);

        return [$record, $code];
    }

    public static function latestFor(string $email): ?self
    {
        return static::where('email', $email)->whereNull('used_at')->latest('id')->first();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasAttemptsLeft(): bool
    {
        return $this->attempts < self::MAX_ATTEMPTS;
    }

    public function matches(string $code): bool
    {
        return Hash::check($code, $this->code_hash);
    }

    public function secondsUntilResend(): int
    {
        return max(0, self::RESEND_SECONDS - $this->created_at->diffInSeconds(now()));
    }
}
