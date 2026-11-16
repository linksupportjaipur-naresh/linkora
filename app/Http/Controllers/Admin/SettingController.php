<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        $values = [];
        foreach (Setting::FIELDS as $key => [$configPath]) {
            $values[$key] = config($configPath);
        }

        return view('admin.settings', ['values' => $values]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'support_email'    => ['required', 'email', 'max:150'],
            'support_phone'    => ['nullable', 'string', 'max:30'],
            'address'          => ['nullable', 'string', 'max:150'],
            'social_instagram' => ['nullable', 'url', 'regex:#^https?://#i', 'max:255'],
            'social_x'         => ['nullable', 'url', 'regex:#^https?://#i', 'max:255'],
            'social_linkedin'  => ['nullable', 'url', 'regex:#^https?://#i', 'max:255'],
            'social_youtube'   => ['nullable', 'url', 'regex:#^https?://#i', 'max:255'],
        ], ['*.regex' => 'Links must start with https://']);

        $data['registration_open'] = $request->boolean('registration_open') ? '1' : '0';

        Setting::put($data);

        return back()->with('status', 'Settings saved.');
    }
}
