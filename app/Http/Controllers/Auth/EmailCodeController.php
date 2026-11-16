<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\LoginCodeMail;
use App\Models\LoginCode;
use App\Models\User;
use App\Support\UsernameGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Passwordless sign-up / log-in: the user enters an email, we send a
 * 6-digit code, and entering the code signs them in (creating the
 * account on first use).
 */
class EmailCodeController extends Controller
{
    private const SESSION_EMAIL = 'otp.email';
    private const SESSION_INTENT = 'otp.intent';
    private const SESSION_USERNAME = 'otp.username';

    /** Step 1: email submitted from the register or login page. */
    public function send(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'email'    => ['required', 'email', 'max:150'],
            'intent'   => ['required', 'in:login,register'],
            'username' => ['nullable', 'string', 'max:30'],
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email'    => 'Please enter a valid email address.',
        ]);

        $email = $data['email'];
        $user = User::where('email', $email)->first();

        if ($data['intent'] === 'login' && ! $user) {
            return back()->withInput()->withErrors([
                'email' => 'We couldn’t find an account with that email. Sign up instead?',
            ])->with('no_account', true);
        }

        if ($user && $user->isBlocked()) {
            return back()->withInput()->withErrors(['email' => 'This account has been blocked. Please contact support.']);
        }

        if (! $user && ! config('linkora.registration_open')) {
            return back()->withInput()->withErrors(['email' => 'New sign-ups are paused right now. Please try again later.']);
        }

        // An existing account on the register page simply logs in.
        $intent = $user ? 'login' : 'register';

        $error = $this->issueAndSend($request, $email, $intent);
        if ($error) {
            return back()->withInput()->withErrors(['email' => $error]);
        }

        $request->session()->put([
            self::SESSION_EMAIL    => $email,
            self::SESSION_INTENT   => $intent,
            self::SESSION_USERNAME => $data['username'] ?? null,
        ]);
        $request->session()->put('otp.from', $data['intent']);

        return redirect()->route('otp.show');
    }

    /** Step 2: "Check your inbox" page. */
    public function show(Request $request)
    {
        $email = $request->session()->get(self::SESSION_EMAIL);
        if (! $email) {
            return redirect()->route('login');
        }

        $code = LoginCode::latestFor($email);

        return view('auth.verify-code', [
            'email'        => $email,
            'backUrl'      => $request->session()->get('otp.from') === 'register' ? route('register') : route('login'),
            'resendIn'     => $code ? $code->secondsUntilResend() : 0,
        ]);
    }

    /** Step 3: check the code and sign the user in. */
    public function verify(Request $request)
    {
        $email = $request->session()->get(self::SESSION_EMAIL);
        if (! $email) {
            return redirect()->route('login')->with('error', 'Your session expired. Please enter your email again.');
        }

        $request->merge(['code' => preg_replace('/\D/', '', (string) $request->input('code'))]);
        $request->validate(['code' => ['required', 'digits:6']], [
            'code.required' => 'Please enter the 6-digit code.',
            'code.digits'   => 'Please enter all 6 digits.',
        ]);

        $record = LoginCode::latestFor($email);

        if (! $record || $record->isExpired()) {
            return back()->withErrors(['code' => 'This code has expired. Tap “Resend code” to get a new one.']);
        }

        if (! $record->hasAttemptsLeft()) {
            return back()->withErrors(['code' => 'Too many wrong tries. Tap “Resend code” to get a new one.']);
        }

        if (! $record->matches($request->input('code'))) {
            $record->increment('attempts');
            $left = LoginCode::MAX_ATTEMPTS - $record->attempts;

            return back()->withErrors(['code' => $left > 0
                ? 'That code isn’t right. ' . $left . ' ' . Str::plural('try', $left) . ' left.'
                : 'Too many wrong tries. Tap “Resend code” to get a new one.']);
        }

        $record->forceFill(['used_at' => now()])->save();

        $user = User::where('email', $email)->first();
        $isNew = false;

        if (! $user) {
            $isNew = true;
            $user = new User([
                'name'     => UsernameGenerator::nameFromEmail($email),
                'username' => UsernameGenerator::make($email, $request->session()->get(self::SESSION_USERNAME)),
                'email'    => $email,
                // Random password; the user can set one later via "Forgot password".
                'password' => Hash::make(Str::random(40)),
            ]);
            $user->forceFill([
                'role'   => User::ROLE_USER,
                'status' => User::STATUS_ACTIVE,
            ]);
        }

        if ($user->isBlocked()) {
            return redirect()->route('login')->with('error', 'This account has been blocked. Please contact support.');
        }

        // Entering the emailed code proves the user owns this address.
        $user->forceFill([
            'email_verified_at' => $user->email_verified_at ?? now(),
            'last_login_at'     => now(),
        ])->save();
        $reactivated = $user->reactivateIfDeactivated();

        $request->session()->forget([self::SESSION_EMAIL, self::SESSION_INTENT, self::SESSION_USERNAME, 'otp.from']);

        if ($isNew) {
            \App\Models\Activity::log($user, 'account.created', 'Created your Linkora account');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        if ($isNew) {
            return redirect()->route('dashboard')
                ->with('status', 'Welcome to Linkora! Your link ' . $user->publicUrl() . ' is reserved.');
        }

        if ($reactivated) {
            return redirect()->route('dashboard')->with('status', 'Welcome back! Your account is active again. Publish your page when you’re ready.');
        }

        return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('dashboard'));
    }

    /** Send a new code. */
    public function resend(Request $request)
    {
        $email = $request->session()->get(self::SESSION_EMAIL);
        if (! $email) {
            return redirect()->route('login');
        }

        $latest = LoginCode::latestFor($email);
        if ($latest && $latest->secondsUntilResend() > 0) {
            return back()->withErrors(['code' => 'Please wait ' . $latest->secondsUntilResend() . ' seconds before asking for a new code.']);
        }

        $error = $this->issueAndSend($request, $email, $request->session()->get(self::SESSION_INTENT, 'login'));
        if ($error) {
            return back()->withErrors(['code' => $error]);
        }

        return back()->with('status', 'We sent a new code to ' . $email . '.');
    }

    /**
     * Create a code and email it. Returns an error message, or null on success.
     */
    private function issueAndSend(Request $request, string $email, string $intent): ?string
    {
        $latest = LoginCode::latestFor($email);
        if ($latest && $latest->secondsUntilResend() > 0 && ! $latest->isExpired()) {
            // A code was sent moments ago (e.g. double click / back button): reuse it.
            return null;
        }

        [$record, $code] = LoginCode::issue($email, $intent, $request->ip());

        try {
            Mail::to($email)->send(new LoginCodeMail($code, $intent));
        } catch (\Throwable $e) {
            $record->delete();
            Log::error('Could not send login code: ' . $e->getMessage());

            return 'We couldn’t send the email right now. Please try again in a moment.';
        }

        return null;
    }
}
