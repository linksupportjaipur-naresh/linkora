<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * /admin is both the admin console and its sign-in page:
 * guests see the admin login form, admins see the dashboard, members get 403.
 */
class AdminLoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 600;   // 10 minutes lock after 5 wrong tries

    public function entry(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->view('admin.auth.login')->header('Cache-Control', 'no-store');
        }
        if (! $user->isAdmin()) {
            abort(403, 'This area is for administrators only.');
        }

        return app()->call([app(DashboardController::class), 'index']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'login'    => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'login.required'    => 'Please enter your admin email or username.',
            'password.required' => 'Please enter your password.',
        ]);

        $login = strtolower(trim($request->input('login')));
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $key = 'admin-login|' . $login . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            event(new Lockout($request));

            throw ValidationException::withMessages([
                'login' => 'Too many attempts. Please try again in ' . ceil(RateLimiter::availableIn($key) / 60) . ' minute(s).',
            ]);
        }

        // Only administrator accounts may sign in here. Members get the same message as a wrong
        // password, so this page never reveals which emails exist or who is an admin.
        $candidate = User::where($field, $login)->first();
        $valid = $candidate && $candidate->isAdmin()
            && Auth::validate([$field => $login, 'password' => (string) $request->input('password')]);

        if (! $valid) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'login' => 'These details don’t match an administrator account.',
            ]);
        }

        if (! $candidate->isActive()) {
            throw ValidationException::withMessages([
                'login' => 'This administrator account is not active.',
            ]);
        }

        RateLimiter::clear($key);
        Auth::login($candidate, $request->boolean('remember'));
        $request->session()->regenerate();
        $candidate->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('admin.dashboard'))->with('status', 'Welcome back, ' . Str::of($candidate->name)->explode(' ')->first() . '.');
    }
}
