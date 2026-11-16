<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\LoginCodeMail;
use App\Models\Activity;
use App\Models\LoginCode;
use App\Models\User;
use App\Support\Avatar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Account settings. Every action works on $request->user() only, so a user can
 * never change another account from here.
 */
class SettingsController extends Controller
{
    private const PENDING_EMAIL = 'settings.pending_email';

    public function qr(Request $request)
    {
        $user = $request->user();

        return view('dashboard.qr', ['user' => $user, 'profile' => $user->profileRecord()]);
    }

    public function edit(Request $request)
    {
        return view('dashboard.settings', [
            'user'         => $request->user(),
            'profile'      => $request->user()->profile,   // may be null (not created yet)
            'pendingEmail' => $request->session()->get(self::PENDING_EMAIL),
            'plan'         => $request->user()->currentPlan(),
            'subscription' => $request->user()->currentSubscription(),
        ]);
    }

    public function updateAccount(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $request->user()->update(['name' => $data['name']]);
        Activity::log($request->user(), 'account.updated', 'Updated your account name');

        return back()->with('status', 'Account details saved.');
    }

    /** Upload / replace the profile photo (same photo as on My Profile and the public page). */
    public function updatePhoto(Request $request)
    {
        $request->validate(['avatar' => array_merge(['required'], Avatar::rules())], Avatar::messages());

        Avatar::replace($request->user()->profileRecord(), $request->file('avatar'));
        Activity::log($request->user(), 'profile.updated', 'Changed your profile photo');

        return back()->with('status', 'Photo updated.');
    }

    public function removePhoto(Request $request)
    {
        $profile = $request->user()->profileRecord();
        if ($profile->avatar_path) {
            Avatar::remove($profile);
            Activity::log($request->user(), 'profile.updated', 'Removed your profile photo');
        }

        return back()->with('status', 'Photo removed.');
    }

    /** Step 1 of an email change: email a code to the NEW address. */
    public function requestEmailChange(Request $request)
    {
        $user = $request->user();
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $request->validate([
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'email.unique' => 'Another account already uses this email.',
        ]);

        $email = $request->input('email');
        if ($email === $user->email) {
            return back()->withErrors(['email' => 'That’s already your email.']);
        }

        $latest = LoginCode::latestFor($email);
        if (! $latest || $latest->secondsUntilResend() === 0 || $latest->intent !== 'email_change') {
            [$record, $code] = LoginCode::issue($email, 'email_change', $request->ip());
            try {
                Mail::to($email)->send(new LoginCodeMail($code, 'email_change'));
            } catch (\Throwable $e) {
                $record->delete();
                Log::error('Could not send email-change code: ' . $e->getMessage());

                return back()->withInput()->withErrors(['email' => 'We couldn’t send the email right now. Please try again in a moment.']);
            }
        }

        $request->session()->put(self::PENDING_EMAIL, $email);

        return back()->with('status', 'We sent a 6-digit code to ' . $email . '. Enter it below to confirm the change.');
    }

    /** Step 2: confirm with the code; only then is the email changed. */
    public function confirmEmailChange(Request $request)
    {
        $user = $request->user();
        $email = $request->session()->get(self::PENDING_EMAIL);
        if (! $email) {
            return back()->withErrors(['email_code' => 'Please enter your new email first.']);
        }

        $request->merge(['email_code' => preg_replace('/\D/', '', (string) $request->input('email_code'))]);
        $request->validate(['email_code' => ['required', 'digits:6']], ['email_code.*' => 'Please enter the 6-digit code.']);

        $record = LoginCode::latestFor($email);
        if (! $record || $record->intent !== 'email_change' || $record->isExpired() || ! $record->hasAttemptsLeft()) {
            return back()->withErrors(['email_code' => 'This code has expired. Please request a new one.']);
        }
        if (! $record->matches($request->input('email_code'))) {
            $record->increment('attempts');

            return back()->withErrors(['email_code' => 'That code isn’t right. Please check the email and try again.']);
        }

        if (User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
            return back()->withErrors(['email_code' => 'Another account already uses this email.']);
        }

        $record->forceFill(['used_at' => now()])->save();
        $old = $user->email;
        $user->forceFill(['email' => $email, 'email_verified_at' => now()])->save();
        $request->session()->forget(self::PENDING_EMAIL);

        Activity::log($user, 'email.updated', 'Changed your email from ' . $old . ' to ' . $email);

        return back()->with('status', 'Your email is now ' . $email . '.');
    }

    public function cancelEmailChange(Request $request)
    {
        $request->session()->forget(self::PENDING_EMAIL);

        return back();
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $rules = ['password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()]];
        if ($user->hasOwnPassword()) {
            $rules['current_password'] = ['required', 'string'];
        }
        $request->validate($rules);

        $hadPassword = $user->hasOwnPassword();
        if ($hadPassword && ! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Your current password is not correct.']);
        }

        $user->forceFill([
            'password'        => Hash::make($request->input('password')),
            'password_set_at' => now(),
        ])->save();

        Activity::log($user, 'password.updated', $hadPassword ? 'Changed your password' : 'Set a password');

        return back()->with('status', 'Password saved. You can now also log in with your email or username and password.');
    }

    /** Pause the account: page goes offline until the user logs in again. */
    public function deactivate(Request $request)
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            return back()->withErrors(['deactivate' => 'Administrator accounts can’t be deactivated here.']);
        }
        $this->confirmIdentity($request, $user, 'deactivate');

        $user->forceFill(['status' => User::STATUS_DEACTIVATED])->save();
        $user->profileRecord()->forceFill(['is_published' => false])->save();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Your account is deactivated and your page is offline. Log in any time to reactivate it.');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            return back()->withErrors(['confirm_username' => 'Administrator accounts can’t be deleted here.']);
        }
        $this->confirmIdentity($request, $user, 'confirm_username');

        $files = array_filter([optional($user->profile)->avatar_path, optional($user->profile)->bg_image_path]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->delete(); // profile, links and analytics are removed by cascading foreign keys
        if ($files) {
            Storage::disk('public')->delete($files);
        }

        return redirect()->route('home')->with('status', 'Your account has been deleted.');
    }

    /**
     * Destructive actions need the username typed out, plus the current password
     * if the user has set one.
     */
    private function confirmIdentity(Request $request, User $user, string $errorKey): void
    {
        $request->validate([
            'confirm_username' => ['required', 'string'],
            'current_password' => [$user->hasOwnPassword() ? 'required' : 'nullable', 'string'],
        ], ['confirm_username.required' => 'Type your username to confirm.']);

        if (strtolower(trim($request->input('confirm_username'))) !== $user->username) {
            throw \Illuminate\Validation\ValidationException::withMessages([$errorKey => 'Type your username exactly to confirm.']);
        }

        if ($user->hasOwnPassword() && ! Hash::check((string) $request->input('current_password'), $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['current_password_confirm' => 'Your password is not correct.']);
        }
    }
}
