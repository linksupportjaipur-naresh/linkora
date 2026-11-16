<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    /**
     * Username rules, shared by account creation and (later) profile settings.
     */
    public static function usernameRules(): array
    {
        return [
            'required', 'string', 'min:3', 'max:30',
            'regex:/^[a-z0-9](?:[a-z0-9_.]*[a-z0-9])?$/',
            'not_regex:/[_.]{2}/',
            Rule::notIn(config('linkora.reserved_usernames')),
            Rule::unique('users', 'username'),
        ];
    }

    public static function usernameMessages(): array
    {
        return [
            'username.regex'     => 'Use lowercase letters, numbers, dots or underscores, starting and ending with a letter or number.',
            'username.not_regex' => 'Dots and underscores can’t be next to each other.',
            'username.not_in'    => 'That username is reserved. Please pick another one.',
            'username.unique'    => 'That username is already taken.',
        ];
    }

    /**
     * Sign-up page: email only. A username typed in the home page "Claim my link"
     * box is carried through and reserved if it is still free.
     */
    public function create(Request $request)
    {
        $username = strtolower((string) $request->query('username', ''));
        $username = substr(preg_replace('/[^a-z0-9_.]/', '', $username), 0, 30);

        return view('auth.register', ['claimUsername' => $username]);
    }
}
