<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Support\Avatar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        return view('dashboard.profile', [
            'user'    => $user,
            'profile' => $user->profileRecord(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $profile = $user->profileRecord();

        $socialKeys = array_keys(config('linkora_design.socials'));
        $request->merge(['username' => strtolower(trim((string) $request->input('username')))]);
        $request->merge(['website' => $this->normalizeUrl($request->input('website'))]);
        $socials = collect((array) $request->input('socials'))
            ->only($socialKeys)
            ->map(fn ($v, $key) => $this->normalizeSocial($key, $v))
            ->all();
        $request->merge(['socials' => $socials]);

        $usernameRules = RegisterController::usernameRules();
        $usernameRules[array_key_last($usernameRules)] = Rule::unique('users', 'username')->ignore($user->id);

        $socialRules = [];
        $socialMessages = [];
        foreach (config('linkora_design.socials') as $key => [, $label, $example]) {
            $socialRules['socials.' . $key] = ['nullable', 'url', 'regex:#^https?://#i', 'max:255', $this->socialDomainRule($key, $label)];
            $socialMessages['socials.' . $key . '.url'] = 'Please enter a full ' . $label . ' link, for example ' . $example . '.';
            $socialMessages['socials.' . $key . '.regex'] = $socialMessages['socials.' . $key . '.url'];
            $socialMessages['socials.' . $key . '.max'] = 'This link is too long (max 255 characters).';
        }

        $data = $request->validate([
            // \S = must contain at least one visible character (not only spaces)
            'display_name'  => ['required', 'string', 'min:2', 'max:60', 'regex:/\S/u', 'not_regex:/[<>]/'],
            'headline'      => ['nullable', 'string', 'max:80', 'not_regex:/[<>\r\n]/'],
            'username'      => $usernameRules,
            'bio'           => ['nullable', 'string', 'max:300', 'not_regex:/[<>]/'],
            'avatar'        => array_merge(['nullable'], Avatar::rules()),
            'remove_avatar' => ['nullable', 'boolean'],
            'contact_email' => ['nullable', 'email:rfc,filter', 'max:150'],
            'phone'         => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-()]+$/', $this->phoneDigitsRule()],
            'website'       => ['nullable', 'url', 'regex:#^https?://#i', 'max:255', $this->publicHostRule()],
            'location'      => ['nullable', 'string', 'max:80'],   // free text; length cap only (the box itself stops at 80)
        ] + $socialRules, RegisterController::usernameMessages() + [
            'display_name.required' => 'Please enter your name — it’s shown at the top of your page.',
            'display_name.min'      => 'Your name must be at least 2 characters.',
            'display_name.max'      => 'Your name can be at most 60 characters.',
            'display_name.regex'    => 'Please enter your name.',
            'display_name.not_regex'=> 'Your name can’t contain < or >.',
            'headline.max'          => 'Your headline can be at most 80 characters.',
            'headline.not_regex'    => 'Your headline must be a single line without < or >.',
            'bio.max'               => 'Your bio can be at most 300 characters.',
            'bio.not_regex'         => 'Your bio can’t contain < or >.',
            'contact_email.email'   => 'Please enter a valid email address, like hello@yourname.com.',
            'contact_email.max'     => 'This email address is too long.',
            'phone.regex'           => 'Use only numbers, spaces, brackets, dashes and an optional + at the start.',
            'phone.max'             => 'This phone number is too long.',
            'website.url'           => 'Please enter a valid website, like yourwebsite.com.',
            'website.regex'         => 'Please enter a valid website, like yourwebsite.com.',
            'website.max'           => 'This link is too long (max 255 characters).',
            'location.max'          => 'Your location can be at most 80 characters.',
        ] + Avatar::messages() + $socialMessages);

        if ($request->hasFile('avatar')) {
            Avatar::replace($profile, $request->file('avatar'));
        } elseif ($request->boolean('remove_avatar')) {
            Avatar::remove($profile);
        }

        $profile->fill([
            'display_name'  => $data['display_name'],
            'headline'      => $data['headline'] ?? null,
            'bio'         => $data['bio'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'website'       => $data['website'] ?? null,
            'location'      => $data['location'] ?? null,
            'socials'       => array_filter($data['socials'] ?? []),
        ])->save();

        $usernameChanged = $user->username !== $data['username'];
        $user->username = $data['username'];
        $user->save();

        Activity::log($user, 'profile.updated', $usernameChanged
            ? 'Changed your username to @' . $user->username
            : 'Updated your profile details');

        $message = 'Profile saved.';
        if ($request->input('action') === 'publish' && ! $profile->is_published) {
            $this->setPublished($profile, true);
            Activity::log($user, 'profile.published', 'Published your page');
            $message = 'Profile saved and published! Your page is live at ' . $user->publicUrl() . '.';
        }

        return redirect()->route('dashboard.profile')->with('status', $message);
    }

    public function publish(Request $request)
    {
        $profile = $request->user()->profileRecord();
        if (! $profile->is_published) {
            $this->setPublished($profile, true);
            Activity::log($request->user(), 'profile.published', 'Published your page');
        }

        return back()->with('status', 'Your page is live at ' . $request->user()->publicUrl() . '.');
    }

    public function unpublish(Request $request)
    {
        $profile = $request->user()->profileRecord();
        if ($profile->is_published) {
            $this->setPublished($profile, false);
            Activity::log($request->user(), 'profile.unpublished', 'Unpublished your page');
        }

        return back()->with('status', 'Your page is now hidden. Only you can see it.');
    }

    /** AJAX: is this username free for the current user? */
    public function checkUsername(Request $request)
    {
        $user = $request->user();
        $username = strtolower(trim((string) $request->query('username')));

        if ($username === $user->username) {
            return response()->json(['available' => true, 'message' => 'This is your current username.']);
        }

        $rules = RegisterController::usernameRules();
        $rules[array_key_last($rules)] = Rule::unique('users', 'username')->ignore($user->id);
        $v = Validator::make(['username' => $username], ['username' => $rules], RegisterController::usernameMessages());

        return response()->json($v->fails()
            ? ['available' => false, 'message' => $v->errors()->first('username')]
            : ['available' => true, 'message' => preg_replace('#[^/]+$#', '', $user->publicUrl()) . $username . ' is available!']);
    }

    private function setPublished($profile, bool $published): void
    {
        $profile->forceFill([
            'is_published' => $published,
            'published_at' => $published ? ($profile->published_at ?? now()) : $profile->published_at,
        ])->save();
    }

    /**
     * Social box input → full URL. Besides adding https://, accepts "@name" for handle-based
     * networks and a plain phone number for WhatsApp (becomes wa.me/<digits>).
     */
    private function normalizeSocial(string $key, $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $handles = config('linkora_design.social_handle_urls');
        if (isset($handles[$key]) && preg_match('/^@([A-Za-z0-9_.\-]{1,60})$/', $value, $m)) {
            return $handles[$key] . $m[1];
        }
        if ($key === 'whatsapp' && preg_match('/^\+?[0-9\s\-()]{8,20}$/', $value)) {
            return 'https://wa.me/' . preg_replace('/\D/', '', $value);
        }

        return $this->normalizeUrl($value);
    }

    /** The link must point to that network (e.g. the Instagram box only takes instagram.com links) and to a profile, not just the home page. */
    private function socialDomainRule(string $key, string $label): \Closure
    {
        $domains = config('linkora_design.social_domains.' . $key, []);

        return function ($attribute, $value, $fail) use ($domains, $label) {
            $host = strtolower((string) parse_url($value, PHP_URL_HOST));
            $path = trim((string) parse_url($value, PHP_URL_PATH), '/');
            $ok = false;
            foreach ($domains as $d) {
                if ($host === $d || Str::endsWith($host, '.' . $d)) {
                    $ok = true;
                    break;
                }
            }
            if ($domains && ! $ok) {
                $fail('This doesn’t look like ' . (preg_match('/^[AEIOU]/i', $label) ? 'an ' : 'a ') . $label . ' link. It should start with https://' . $domains[0] . '/');
            } elseif ($path === '' && ! parse_url($value, PHP_URL_QUERY)) {
                $fail('Please add your ' . $label . ' username or page to the link, not just the home page.');
            }
        };
    }

    /** 7–15 digits, the range used by real phone numbers worldwide (E.164). */
    private function phoneDigitsRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $digits = strlen(preg_replace('/\D/', '', (string) $value));
            if ($digits < 7 || $digits > 15) {
                $fail('A phone number should have 7 to 15 digits.');
            }
        };
    }

    /** Website must be a real public address (has a domain like example.com), not localhost or a bare word. */
    private function publicHostRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $host = strtolower((string) parse_url($value, PHP_URL_HOST));
            if ($host === '' || ! preg_match('/^([a-z0-9\-]+\.)+[a-z]{2,}$/i', $host) || $host === 'localhost') {
                $fail('Please enter a valid website, like yourwebsite.com.');
            }
        };
    }

    private function normalizeUrl($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return preg_match('#^[a-z][a-z0-9+.-]*:#i', $value) ? $value : 'https://' . ltrim($value, '/');
    }

}
