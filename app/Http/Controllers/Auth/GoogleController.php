<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\UsernameGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleController extends Controller
{
    /**
     * Send the visitor to Google's sign-in screen.
     */
    public function redirect(Request $request)
    {
        if (! $this->isConfigured()) {
            return redirect()->route('login')->with('error', 'Google sign-in isn’t set up yet. Please use your email and password.');
        }

        // Google sends the user back to GOOGLE_REDIRECT_URI. Sessions are tied to the
        // host name, so start the flow on that same host (e.g. localhost vs 127.0.0.1),
        // otherwise the state check fails on the way back.
        $callbackOrigin = $this->origin(config('services.google.redirect'));
        if ($callbackOrigin && $callbackOrigin !== $request->getSchemeAndHttpHost()) {
            return redirect()->away($callbackOrigin . $request->getRequestUri());
        }

        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    /**
     * Google sends the user back here.
     */
    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('login')->with('error', 'Google sign-in was cancelled.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            return redirect()->route('login')->with('error', 'Your Google sign-in session expired. Please try again.');
        } catch (\Throwable $e) {
            Log::warning('Google sign-in failed: ' . $e->getMessage());

            return redirect()->route('login')->with('error', 'We couldn’t sign you in with Google. Please try again.');
        }

        $email = strtolower((string) $googleUser->getEmail());
        if ($email === '') {
            return redirect()->route('login')->with('error', 'Your Google account didn’t share an email address.');
        }

        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $email)->first();

        $isNew = false;

        if ($user) {
            // Existing account: link Google to it on first use.
            $user->forceFill([
                'google_id'         => $user->google_id ?: $googleUser->getId(),
                'avatar'            => $googleUser->getAvatar() ?: $user->avatar,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        } else {
            if (! config('linkora.registration_open')) {
                return redirect()->route('login')->with('error', 'New sign-ups are paused right now. Please try again later.');
            }
            $isNew = true;
            $user = new User([
                'name'     => $googleUser->getName() ?: Str::before($email, '@'),
                'username' => UsernameGenerator::make($email),
                'email'    => $email,
                // Random password: Google users can set their own via "Forgot password".
                'password' => Hash::make(Str::random(40)),
            ]);
            $user->forceFill([
                'role'              => User::ROLE_USER,
                'status'            => User::STATUS_ACTIVE,
                'google_id'         => $googleUser->getId(),
                'avatar'            => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ])->save();
        }

        if ($user->isBlocked()) {
            return redirect()->route('login')->with('error', 'This account has been blocked. Please contact support.');
        }
        $user->reactivateIfDeactivated();

        if ($isNew) {
            \App\Models\Activity::log($user, 'account.created', 'Created your Linkora account');
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        if ($isNew) {
            return redirect()->route('dashboard')
                ->with('status', 'Welcome to Linkora! Your link ' . $user->publicUrl() . ' is reserved. You can change it later in settings.');
        }

        return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('dashboard'));
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function origin(?string $url): ?string
    {
        $parts = parse_url((string) $url);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }
}
