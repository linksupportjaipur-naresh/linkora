<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    /** Log-in page: email + one-time code (default). */
    public function create()
    {
        return view('auth.login');
    }

    /** Alternative log-in with email/username and password. */
    public function createWithPassword()
    {
        return view('auth.login-password');
    }

    public function store(Request $request)
    {
        $request->validate([
            'login'    => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Please enter your email or username.',
        ]);

        $login = strtolower(trim($request->input('login')));
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $throttleKey = Str::lower($login) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            event(new Lockout($request));
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'login' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minute(s).',
            ]);
        }

        if (! Auth::attempt([$field => $login, 'password' => $request->input('password')], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60 * 5);

            throw ValidationException::withMessages([
                'login' => 'These details don’t match our records.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        /** @var User $user */
        $user = Auth::user();

        if ($user->isBlocked()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'login' => 'This account has been blocked. Please contact support.',
            ]);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        if ($user->reactivateIfDeactivated()) {
            return redirect()->route('dashboard')->with('status', 'Welcome back! Your account is active again. Publish your page when you’re ready.');
        }

        $default = $user->isAdmin() ? route('admin.dashboard') : route('dashboard');

        return redirect()->intended($default);
    }

    public function destroy(Request $request)
    {
        $wasAdmin = $request->user() && $request->user()->isAdmin();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Admins go back to their own sign-in page at /admin
        return redirect()->route($wasAdmin ? 'admin.dashboard' : 'login')->with('status', 'You have been logged out.');
    }
}
