<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Logs out a signed-in user whose account was blocked (by an admin) or
 * deactivated after they logged in.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            $message = $user->isBlocked()
                ? 'This account has been blocked. Please contact support.'
                : 'Your account is deactivated. Log in again to reactivate it.';

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', $message);
        }

        return $next($request);
    }
}
