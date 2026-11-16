<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    /** Forgot-password form. */
    public function request()
    {
        return view('auth.forgot-password');
    }

    /** Email a reset link. */
    public function email(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $request->validate(['email' => ['required', 'email', 'max:150']]);

        $status = Password::sendResetLink($request->only('email'));

        // Throttled requests get a real error; every other outcome shows the same
        // message so the form can't be used to find out which emails are registered.
        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors(['email' => __($status)]);
        }

        return back()->with('status', 'If an account exists for that email, we’ve sent a link to reset your password. Please check your inbox.');
    }

    /** Reset-password form (from the emailed link). */
    public function edit(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /** Save the new password. */
    public function update(Request $request)
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Your password has been reset. You can log in now.');
        }

        return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
