<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function home()
    {
        return view('pages.home', [
            'facts'    => $this->productFacts(),
            'plans'    => \App\Models\Plan::where('is_active', true)->orderBy('sort')->get()->map->toPricingArray()->values()->all(),
            'showcase' => $this->showcase(),
        ]);
    }

    /** Up to $count real themes from the live catalogue, with their font details, for preview mock-ups. */
    private function showcase(int $count = 5)
    {
        $fonts = config('linkora_design.fonts');

        return collect(\App\Models\Theme::catalog())->take($count)->map(function ($t, $key) use ($fonts) {
            $font = $fonts[$t['font']] ?? reset($fonts);

            return $t + ['key' => $key, 'font_family' => $font[1], 'font_param' => $font[2]];
        })->values();
    }

    /**
     * Honest numbers about the product itself (no usage claims) for the home/about pages.
     * Theme and font counts come from the live catalogue, so they stay correct as admins add more.
     */
    private function productFacts(): array
    {
        return [
            'themes' => count(\App\Models\Theme::catalog()),
            'fonts'  => count(config('linkora_design.fonts')),
            'icons'  => count(config('linkora_design.link_icons')) + count(config('linkora_design.socials')),
        ];
    }

    public function features()
    {
        // Three real themes for the "A look that feels like you" preview (side, centre, side)
        $themes = $this->showcase(5);
        $picks = $themes->count() >= 4 ? collect([$themes[1], $themes[2], $themes[3]]) : $themes->take(3);

        return view('pages.features', ['themePreviews' => $picks->values()]);
    }

    public function pricing()
    {
        $plans = \App\Models\Plan::where('is_active', true)->orderBy('sort')->get()->map->toPricingArray()->values()->all();

        return view('pages.pricing', ['plans' => $plans]);
    }

    public function about()
    {
        return view('pages.about', ['facts' => $this->productFacts()]);
    }

    public function contact()
    {
        return view('pages.contact');
    }
}
