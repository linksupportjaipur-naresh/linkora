<?php

namespace App\Support;

use App\Http\Controllers\Auth\RegisterController;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UsernameGenerator
{
    /**
     * Use the wished-for username if it is valid and free, otherwise build one
     * from the email address, e.g. "aanya.sharma" or "aanya.sharma2".
     */
    public static function make(string $email, ?string $wish = null): string
    {
        if ($wish && Validator::make(['username' => $wish], ['username' => RegisterController::usernameRules()])->passes()) {
            return $wish;
        }

        $base = strtolower(Str::before($email, '@'));
        $base = preg_replace('/[^a-z0-9_.]/', '', $base);
        $base = preg_replace('/[_.]{2,}/', '.', $base);
        $base = trim(substr($base, 0, 26), '._');

        if (strlen($base) < 3 || in_array($base, config('linkora.reserved_usernames'), true)) {
            $base = trim(substr('user' . ($base !== '' ? '.' . $base : ''), 0, 26), '._');
        }

        $candidate = $base;
        $i = 1;
        while (User::where('username', $candidate)->exists()) {
            $i++;
            $candidate = $base . $i;
        }

        return $candidate;
    }

    /**
     * A friendly display name from an email: "aanya.sharma@x.com" → "Aanya Sharma".
     */
    public static function nameFromEmail(string $email): string
    {
        $local = preg_replace('/[^a-zA-Z]+/', ' ', Str::before($email, '@'));
        $name = trim(Str::title($local));

        return $name !== '' ? Str::limit($name, 100, '') : 'Linkora User';
    }
}
