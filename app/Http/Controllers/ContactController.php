<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    public const SUBJECTS = [
        'General question',
        'Pricing & plans',
        'Technical support',
        'Partnership',
        'Feedback',
    ];

    public function store(Request $request)
    {
        $message = 'Thanks for reaching out! We’ve received your message and will reply within one working day.';

        // Honeypot: real people never fill the hidden "website" field. Pretend it worked.
        if (filled($request->input('website'))) {
            return $request->expectsJson()
                ? response()->json(['message' => $message])
                : back()->with('status', $message);
        }

        $data = $request->validate([
            'name'    => ['required', 'string', 'min:2', 'max:100'],
            'email'   => ['required', 'email', 'max:150'],
            'phone'   => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'subject' => ['required', 'string', 'in:' . implode(',', self::SUBJECTS)],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'phone.regex'  => 'Please enter a valid phone number.',
            'subject.in'   => 'Please choose a topic from the list.',
            'message.min'  => 'Please tell us a little more (at least 10 characters).',
        ]);

        Enquiry::create($data + [
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }
}
