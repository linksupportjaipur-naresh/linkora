<?php

/*
|--------------------------------------------------------------------------
| Public page design: themes, fonts, button styles, icons
|--------------------------------------------------------------------------
*/

return [

    'default_theme' => 'lavender',

    'themes' => [
        'lavender' => ['name' => 'Lavender', 'bg' => 'linear-gradient(180deg,#F3EEFF 0%,#E4D9FF 100%)', 'text' => '#1B1233', 'button' => '#7955E8', 'button_text' => '#FFFFFF', 'font' => 'instrument', 'button_style' => 'pill'],
        'midnight' => ['name' => 'Midnight', 'bg' => 'linear-gradient(180deg,#1B1233 0%,#0E0920 100%)', 'text' => '#F3EFFF', 'button' => '#B79CFF', 'button_text' => '#1B1233', 'font' => 'space', 'button_style' => 'rounded'],
        'sunset'   => ['name' => 'Sunset', 'bg' => 'linear-gradient(160deg,#FF9A8B 0%,#FF6A88 55%,#FF99AC 100%)', 'text' => '#FFFFFF', 'button' => '#FFFFFF', 'button_text' => '#D63A62', 'font' => 'poppins', 'button_style' => 'pill'],
        'ocean'    => ['name' => 'Ocean', 'bg' => 'linear-gradient(170deg,#0F4C75 0%,#3282B8 100%)', 'text' => '#FFFFFF', 'button' => '#BBE1FA', 'button_text' => '#0F4C75', 'font' => 'nunito', 'button_style' => 'rounded'],
        'mint'     => ['name' => 'Mint', 'bg' => '#E6FAF5', 'text' => '#0B3B33', 'button' => '#1F9E85', 'button_text' => '#FFFFFF', 'font' => 'nunito', 'button_style' => 'rounded'],
        'paper'    => ['name' => 'Paper', 'bg' => '#FFFFFF', 'text' => '#111111', 'button' => '#111111', 'button_text' => '#111111', 'font' => 'instrument', 'button_style' => 'outline'],
        'blush'    => ['name' => 'Blush', 'bg' => '#FFE3F1', 'text' => '#4A1030', 'button' => '#C2185B', 'button_text' => '#FFFFFF', 'font' => 'playfair', 'button_style' => 'pill'],
        'noir'     => ['name' => 'Noir', 'bg' => '#0B0B0F', 'text' => '#FFFFFF', 'button' => '#FFFFFF', 'button_text' => '#0B0B0F', 'font' => 'space', 'button_style' => 'shadow'],
    ],

    // key => [label, CSS family, Google Fonts family param, group]
    // Keys are stored in profiles/themes — never rename an existing key.
    'fonts' => [
        // Clean & modern
        'instrument' => ['Instrument Sans', '"Instrument Sans", system-ui, sans-serif', 'Instrument+Sans:wght@400;600;700', 'Modern'],
        'poppins'    => ['Poppins', '"Poppins", system-ui, sans-serif', 'Poppins:wght@400;600;700', 'Modern'],
        'montserrat' => ['Montserrat', '"Montserrat", system-ui, sans-serif', 'Montserrat:wght@400;600;700', 'Modern'],
        'dmsans'     => ['DM Sans', '"DM Sans", system-ui, sans-serif', 'DM+Sans:wght@400;600;700', 'Modern'],
        'raleway'    => ['Raleway', '"Raleway", system-ui, sans-serif', 'Raleway:wght@400;600;700', 'Modern'],
        'space'      => ['Space Grotesk', '"Space Grotesk", system-ui, sans-serif', 'Space+Grotesk:wght@400;600;700', 'Modern'],
        // Soft & friendly
        'nunito'     => ['Nunito', '"Nunito", system-ui, sans-serif', 'Nunito:wght@400;700;800', 'Friendly'],
        'quicksand'  => ['Quicksand', '"Quicksand", system-ui, sans-serif', 'Quicksand:wght@400;600;700', 'Friendly'],
        'josefin'    => ['Josefin Sans', '"Josefin Sans", system-ui, sans-serif', 'Josefin+Sans:wght@400;600;700', 'Friendly'],
        // Elegant serif
        'playfair'   => ['Playfair Display', '"Playfair Display", Georgia, serif', 'Playfair+Display:wght@400;600;700', 'Elegant'],
        'lora'       => ['Lora', '"Lora", Georgia, serif', 'Lora:wght@400;600;700', 'Elegant'],
        'merriweather' => ['Merriweather', '"Merriweather", Georgia, serif', 'Merriweather:wght@400;700', 'Elegant'],
        'dmserif'    => ['DM Serif Display', '"DM Serif Display", Georgia, serif', 'DM+Serif+Display', 'Elegant'],
        // Bold display
        'oswald'     => ['Oswald', '"Oswald", Impact, sans-serif', 'Oswald:wght@400;600;700', 'Bold'],
        'bebas'      => ['Bebas Neue', '"Bebas Neue", Impact, sans-serif', 'Bebas+Neue', 'Bold'],
        'righteous'  => ['Righteous', '"Righteous", system-ui, sans-serif', 'Righteous', 'Bold'],
        // Handwritten
        'pacifico'   => ['Pacifico', '"Pacifico", cursive', 'Pacifico', 'Handwritten'],
        'dancing'    => ['Dancing Script', '"Dancing Script", cursive', 'Dancing+Script:wght@400;600;700', 'Handwritten'],
        'caveat'     => ['Caveat', '"Caveat", cursive', 'Caveat:wght@400;600;700', 'Handwritten'],
        // Hindi + English (Devanagari)
        'mukta'      => ['Mukta', '"Mukta", system-ui, sans-serif', 'Mukta:wght@400;600;700', 'Hindi'],
        'baloo'      => ['Baloo 2', '"Baloo 2", system-ui, sans-serif', 'Baloo+2:wght@400;600;700', 'Hindi'],
        'hind'       => ['Hind', '"Hind", system-ui, sans-serif', 'Hind:wght@400;600;700', 'Hindi'],
    ],

    // Display names for the font groups on the Appearance page (in this order)
    'font_groups' => [
        'Modern'      => 'Clean & modern',
        'Friendly'    => 'Soft & friendly',
        'Elegant'     => 'Elegant serif',
        'Bold'        => 'Bold display',
        'Handwritten' => 'Handwritten',
        'Hindi'       => 'Hindi + English',
    ],

    'button_styles' => [
        'pill'    => 'Pill',
        'rounded' => 'Rounded',
        'square'  => 'Square',
        'outline' => 'Outline',
        'shadow'  => 'Hard shadow',
    ],

    // Optional icon for a link. key => [Font Awesome class, label]
    'link_icons' => [
        'link'      => ['fa-solid fa-link', 'Link'],
        'globe'     => ['fa-solid fa-globe', 'Website'],
        'instagram' => ['fa-brands fa-instagram', 'Instagram'],
        'youtube'   => ['fa-brands fa-youtube', 'YouTube'],
        'facebook'  => ['fa-brands fa-facebook', 'Facebook'],
        'x-twitter' => ['fa-brands fa-x-twitter', 'X (Twitter)'],
        'linkedin'  => ['fa-brands fa-linkedin', 'LinkedIn'],
        'whatsapp'  => ['fa-brands fa-whatsapp', 'WhatsApp'],
        'telegram'  => ['fa-brands fa-telegram', 'Telegram'],
        'spotify'   => ['fa-brands fa-spotify', 'Spotify'],
        'github'    => ['fa-brands fa-github', 'GitHub'],
        'shop'      => ['fa-solid fa-bag-shopping', 'Shop'],
        'calendar'  => ['fa-regular fa-calendar', 'Booking'],
        'email'     => ['fa-regular fa-envelope', 'Email'],
        'phone'     => ['fa-solid fa-phone', 'Phone'],
        'video'     => ['fa-solid fa-play', 'Video'],
        'music'     => ['fa-solid fa-music', 'Music'],
        'file'      => ['fa-regular fa-file-lines', 'Document'],
        'payment'   => ['fa-solid fa-indian-rupee-sign', 'Payment'],
        'heart'     => ['fa-solid fa-heart', 'Support'],
    ],

    // Icon tile colours on the public page (white link cards). Anything missing uses the theme button colour.
    'icon_colors' => [
        'instagram' => 'linear-gradient(45deg,#F58529,#DD2A7B 55%,#8134AF)',
        'youtube' => '#FF0000', 'facebook' => '#1877F2', 'x-twitter' => '#111111', 'linkedin' => '#0A66C2',
        'whatsapp' => '#25D366', 'telegram' => '#229ED9', 'spotify' => '#1DB954', 'github' => '#24292F',
        'shop' => '#F59E0B', 'calendar' => '#0EA5E9', 'email' => '#7C3AED', 'phone' => '#10B981',
        'video' => '#EF4444', 'music' => '#EC4899', 'file' => '#EF4444', 'payment' => '#16A34A', 'heart' => '#E11D48',
    ],

    // Domains → icon, used when a link has no icon chosen.
    'icon_domains' => [
        'instagram.com' => 'instagram', 'youtube.com' => 'youtube', 'youtu.be' => 'youtube',
        'facebook.com' => 'facebook', 'fb.com' => 'facebook', 'x.com' => 'x-twitter', 'twitter.com' => 'x-twitter',
        'linkedin.com' => 'linkedin', 'wa.me' => 'whatsapp', 'whatsapp.com' => 'whatsapp',
        't.me' => 'telegram', 'telegram.me' => 'telegram', 'spotify.com' => 'spotify', 'github.com' => 'github',
    ],

    // Brand colours for the round social buttons.
    'social_colors' => [
        'instagram' => 'radial-gradient(circle at 30% 107%,#FDF497 0%,#FDF497 5%,#FD5949 45%,#D6249F 60%,#285AEB 90%)',
        'youtube' => '#FF0000', 'facebook' => '#1877F2', 'x-twitter' => '#000000', 'linkedin' => '#0A66C2',
        'whatsapp' => '#25D366', 'telegram' => '#229ED9', 'spotify' => '#1DB954', 'github' => '#24292F',
    ],

    // Social icons shown under the bio. key => [Font Awesome class, label, placeholder]
    'socials' => [
        'instagram' => ['fa-brands fa-instagram', 'Instagram', 'https://instagram.com/yourname'],
        'youtube'   => ['fa-brands fa-youtube', 'YouTube', 'https://youtube.com/@yourchannel'],
        'facebook'  => ['fa-brands fa-facebook', 'Facebook', 'https://facebook.com/yourpage'],
        'x-twitter' => ['fa-brands fa-x-twitter', 'X (Twitter)', 'https://x.com/yourname'],
        'linkedin'  => ['fa-brands fa-linkedin', 'LinkedIn', 'https://linkedin.com/in/yourname'],
        'whatsapp'  => ['fa-brands fa-whatsapp', 'WhatsApp', 'https://wa.me/919876543210'],
        'telegram'  => ['fa-brands fa-telegram', 'Telegram', 'https://t.me/yourname'],
        'spotify'   => ['fa-brands fa-spotify', 'Spotify', 'https://open.spotify.com/artist/…'],
        'github'    => ['fa-brands fa-github', 'GitHub', 'https://github.com/yourname'],
    ],

    // Which websites each social icon may link to (subdomains like www./m. are allowed too).
    'social_domains' => [
        'instagram' => ['instagram.com', 'instagr.am'],
        'youtube'   => ['youtube.com', 'youtu.be'],
        'facebook'  => ['facebook.com', 'fb.com', 'fb.me'],
        'x-twitter' => ['x.com', 'twitter.com'],
        'linkedin'  => ['linkedin.com', 'lnkd.in'],
        'whatsapp'  => ['wa.me', 'whatsapp.com'],
        'telegram'  => ['t.me', 'telegram.me'],
        'spotify'   => ['spotify.com', 'spotify.link'],
        'github'    => ['github.com'],
    ],

    // Typing just "@name" in these boxes becomes a full profile link.
    'social_handle_urls' => [
        'instagram' => 'https://instagram.com/',
        'youtube'   => 'https://youtube.com/@',
        'x-twitter' => 'https://x.com/',
        'telegram'  => 'https://t.me/',
        'github'    => 'https://github.com/',
    ],
];
