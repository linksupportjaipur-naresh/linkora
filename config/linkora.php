<?php

/*
|--------------------------------------------------------------------------
| Linkora product settings
|--------------------------------------------------------------------------
| Plans live here until the admin "Plans" module moves them into the
| database. Prices are in INR.
*/

return [

    'domain' => env('LINKORA_DOMAIN', 'linkora.in'),

    // Read here (not with env() elsewhere) so they still work after `php artisan config:cache`.
    'trusted_proxies' => env('TRUSTED_PROXIES'),
    'backup' => [
        'mysqldump' => env('MYSQLDUMP_PATH', 'mysqldump'),
        'keep'      => (int) env('BACKUP_KEEP', 14),
    ],
    'admin' => [
        'email'    => env('ADMIN_EMAIL', 'admin@linkora.test'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    // Admin can switch this off in Settings to pause new sign-ups.
    'registration_open' => true,

    'support_email' => env('LINKORA_SUPPORT_EMAIL', 'link.supportjaipur@gmail.com'),
    'support_phone' => env('LINKORA_SUPPORT_PHONE', '+91 98765 43210'),
    'address'       => 'Jaipur, Rajasthan, India',

    'socials' => [
        'instagram' => 'https://instagram.com',
        'x-twitter' => 'https://x.com',
        'linkedin'  => 'https://linkedin.com',
        'youtube'   => 'https://youtube.com',
    ],

    // Usernames that cannot be claimed because they clash with app routes.
    'reserved_usernames' => [
        'admin', 'administrator', 'api', 'about', 'app', 'assets', 'auth', 'blog', 'contact',
        'dashboard', 'features', 'forgot-password', 'help', 'home', 'linkora', 'login', 'logout',
        'pricing', 'privacy', 'register', 'reset-password', 'root', 'settings', 'signup',
        'storage', 'support', 'terms', 'user', 'users', 'www',
        'verify', 'go', 'l', 'p', 'qr', 'qr-code', 'links', 'profile', 'appearance', 'analytics', 'explore', 'static', 'img', 'css', 'js',
    ],

    'plans' => [
        [
            'key'      => 'free',
            'name'     => 'Free',
            'tagline'  => 'For getting started',
            'monthly'  => 0,
            'yearly'   => 0,
            'cta'      => 'Start for free',
            'featured' => false,
            'features' => [
                ['Unlimited links', true],
                ['5 free themes', true],
                ['Basic click stats (7 days)', true],
                ['QR code for your page', true],
                ['Custom colours & fonts', false],
                ['Remove Linkora branding', false],
                ['Analytics exports & priority support', false],
            ],
        ],
        [
            'key'      => 'pro',
            'name'     => 'Pro',
            'tagline'  => 'For creators who are growing',
            'monthly'  => 149,
            'yearly'   => 1490,
            'cta'      => 'Go Pro',
            'featured' => true,
            'features' => [
                ['Unlimited links', true],
                ['All premium themes', true],
                ['Analytics for 12 months', true],
                ['QR code for your page', true],
                ['Custom colours & fonts', true],
                ['Remove Linkora branding', true],
                ['Analytics exports & priority support', false],
            ],
        ],
        [
            'key'      => 'business',
            'name'     => 'Business',
            'tagline'  => 'For brands and small teams',
            'monthly'  => 499,
            'yearly'   => 4990,
            'cta'      => 'Choose Business',
            'featured' => false,
            'features' => [
                ['Unlimited links', true],
                ['All premium themes', true],
                ['Unlimited analytics history', true],
                ['QR code for your page', true],
                ['Custom colours & fonts', true],
                ['Remove Linkora branding', true],
                ['Analytics exports & priority support', true],
            ],
        ],
    ],

    // Rows for the pricing comparison table: [label, free, pro, business]
    // true = included, false = not included, string = shown as-is.
    'comparison' => [
        ['Links', 'Unlimited', 'Unlimited', 'Unlimited'],
        ['Themes', '5 free', 'All premium', 'All premium'],
        ['Analytics history', '7 days', '12 months', 'Unlimited'],
        ['QR code', true, true, true],
        ['Custom colours & fonts', false, true, true],
        ['Remove Linkora branding', false, true, true],
        ['Traffic sources & devices', false, true, true],
        ['Export analytics (CSV)', false, false, true],
        ['Priority support', false, false, true],
    ],
];
