<?php

namespace App\Support;

use App\Models\Profile;

/**
 * Turns a profile's theme + custom overrides into the values the public page needs.
 */
class ProfileDesign
{
    public const COLOR_KEYS = ['bg_color', 'text_color', 'button_color', 'button_text_color'];

    // 'theme' = the theme's own background laid over the photo (default), so themes stay visible.
    public const OVERLAYS = ['theme', 'light', 'dark', 'none'];

    public static function resolve(Profile $profile): array
    {
        $design = self::build($profile->themeKey(), $profile->design ?: []);

        // Optional background photo with a tint so text stays readable.
        $design['bg_image'] = $profile->bgImageUrl();
        $overlay = ($profile->design ?: [])['bg_overlay'] ?? null;
        $design['bg_overlay'] = in_array($overlay, self::OVERLAYS, true) ? $overlay : 'theme';

        return $design;
    }

    public static function build(string $themeKey, array $overrides = []): array
    {
        $themes = \App\Models\Theme::catalog(false);
        $theme = $themes[$themeKey] ?? $themes[config('linkora_design.default_theme')];
        $fonts = config('linkora_design.fonts');

        $font = $overrides['font'] ?? $theme['font'];
        $font = array_key_exists($font, $fonts) ? $font : $theme['font'];

        $style = $overrides['button_style'] ?? $theme['button_style'];
        $style = array_key_exists($style, config('linkora_design.button_styles')) ? $style : $theme['button_style'];

        $button = self::color($overrides['button_color'] ?? null) ?? $theme['button'];
        $buttonText = self::color($overrides['button_text_color'] ?? null) ?? $theme['button_text'];
        $text = self::color($overrides['text_color'] ?? null) ?? $theme['text'];

        // Very light buttons (e.g. white) can't be used as an accent on white cards,
        // so the button-text colour becomes the accent instead.
        $lightButton = self::luminance($button) > 0.8;
        $accent = $lightButton ? $buttonText : $button;

        return [
            'theme'        => $themeKey,
            'bg'           => self::color($overrides['bg_color'] ?? null) ?? $theme['bg'],
            'text'         => $text,
            'button'       => $button,
            'button_text'  => $buttonText,
            'accent'       => $accent,
            // Divider/heart sit on the page background: if the accent is as dark as a dark page, use the text colour.
            'mark'         => $lightButton && self::luminance($text) > 0.6 && self::luminance($accent) < 0.2 ? $text : $accent,
            'light_button' => $lightButton,
            'font'         => $font,
            'font_family'  => $fonts[$font][1],
            'font_url'     => 'https://fonts.googleapis.com/css2?family=' . $fonts[$font][2] . '&display=swap',
            'button_style' => $style,
        ];
    }

    /** CSS custom properties for the page wrapper (values are validated hex colors / theme strings). */
    public static function cssVars(array $d): string
    {
        return '--lp-bg:' . $d['bg'] . ';--lp-text:' . $d['text'] . ';--lp-btn:' . $d['button']
            . ';--lp-btn-text:' . $d['button_text'] . ';--lp-accent:' . ($d['accent'] ?? $d['button'])
            . ';--lp-mark:' . ($d['mark'] ?? $d['button']) . ';--lp-font:' . $d['font_family'] . ';';
    }

    /** Relative luminance (0 = black, 1 = white) of a #RGB / #RRGGBB colour. */
    public static function luminance(?string $hex): float
    {
        $hex = ltrim((string) self::color($hex), '#');
        if ($hex === '') {
            return 0.5;
        }
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        [$r, $g, $b] = array_map(function ($c) {
            $c = hexdec($c) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split($hex, 2));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /** Accept only #RGB / #RRGGBB so nothing else can reach the style attribute. */
    public static function color(?string $value): ?string
    {
        return $value && preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value) ? strtoupper($value) : null;
    }
}
