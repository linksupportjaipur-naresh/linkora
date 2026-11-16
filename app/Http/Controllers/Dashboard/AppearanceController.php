<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Theme;
use App\Support\ImageStore;
use Illuminate\Support\Facades\Storage;
use App\Support\ProfileDesign;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppearanceController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        $profile = $user->profileRecord();

        return view('dashboard.appearance', [
            'user'    => $user,
            'profile' => $profile,
            'links'   => $user->links()->active()->ordered()->get(),
            'design'  => ProfileDesign::resolve($profile),
            'custom'  => $profile->design ?: [],
            'isPro'   => $user->isPro(),
            'themes'  => $this->visibleThemes($profile),
        ]);
    }

    public function update(Request $request)
    {
        $hex = ['nullable', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'];

        $data = $request->validate([
            'theme'             => ['required', Rule::in($this->allowedThemes($request))],
            'font'              => ['required', Rule::in(array_keys(config('linkora_design.fonts')))],
            'button_style'      => ['required', Rule::in(array_keys(config('linkora_design.button_styles')))],
            'bg_color'          => $hex,
            'text_color'        => $hex,
            'button_color'      => $hex,
            'button_text_color' => $hex,
            'bg_image'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=300,min_height=300'],
            'remove_bg_image'   => ['nullable', 'boolean'],
            'bg_overlay'        => ['nullable', Rule::in(ProfileDesign::OVERLAYS)],
        ], [
            '*.regex'             => 'Please pick a valid colour.',
            'theme.in'            => 'That theme isn’t available on your plan.',
            'bg_image.image'      => 'The background must be an image (JPG, PNG or WebP).',
            'bg_image.mimes'      => 'The background must be a JPG, PNG or WebP image.',
            'bg_image.max'        => 'The background image must be smaller than 5 MB.',
            'bg_image.dimensions' => 'Please use an image at least 300 × 300 pixels.',
        ]);

        $theme = Theme::catalog(false)[$data['theme']];

        // Store only what differs from the chosen theme, so switching themes later stays predictable.
        $design = [];
        if ($data['font'] !== $theme['font']) {
            $design['font'] = $data['font'];
        }
        if ($data['button_style'] !== $theme['button_style']) {
            $design['button_style'] = $data['button_style'];
        }
        $themeColors = ['bg_color' => $theme['bg'], 'text_color' => $theme['text'], 'button_color' => $theme['button'], 'button_text_color' => $theme['button_text']];
        foreach (ProfileDesign::COLOR_KEYS as $key) {
            $value = ProfileDesign::color($data[$key] ?? null);
            if ($value && strcasecmp($value, $themeColors[$key]) !== 0) {
                $design[$key] = $value;
            }
        }

        $profile = $request->user()->profileRecord();

        // Background image: upload replaces, "remove" deletes, otherwise keep the current one.
        $bgPath = $profile->bg_image_path;
        if ($request->hasFile('bg_image')) {
            $newPath = ImageStore::background($request->file('bg_image'), $request->user()->id);
            if ($bgPath) {
                Storage::disk('public')->delete($bgPath);
            }
            $bgPath = $newPath;
        } elseif ($request->boolean('remove_bg_image') && $bgPath) {
            Storage::disk('public')->delete($bgPath);
            $bgPath = null;
        }
        if ($bgPath && ! empty($data['bg_overlay'])) {
            $design['bg_overlay'] = $data['bg_overlay'];
        }

        $profile->forceFill(['theme' => $data['theme'], 'design' => $design ?: null, 'bg_image_path' => $bgPath])->save();

        Activity::log($request->user(), 'appearance.updated', 'Changed your page design to “' . $theme['name'] . '”' . ($design ? ' (customised)' : ''));

        // "Save changes" from the leave-page dialog: continue to where the user was going (same site only).
        $next = (string) $request->input('next');
        // Only plain same-site paths: no "//host", no "/\host" (browsers treat both as another site).
        if ($next !== '' && preg_match('#^/(?![/\\\\])[^\s\\\\]*$#', $next)) {
            return redirect($next)->with('status', 'Design saved.');
        }

        return redirect()->route('dashboard.appearance')->with('status', 'Design saved. Your page has the new look.');
    }

    /** Active themes, plus the user's current one if it has since been hidden. */
    private function visibleThemes($profile): array
    {
        $themes = Theme::catalog();
        $current = $profile->themeKey();
        if (! isset($themes[$current])) {
            $themes = [$current => Theme::catalog(false)[$current]] + $themes;
        }

        return $themes;
    }

    /**
     * Active themes the user may pick: Pro themes need a paid plan. The theme the
     * user already has always stays allowed, even if it was hidden later.
     */
    private function allowedThemes(Request $request): array
    {
        $isPro = $request->user()->isPro();
        $keys = collect(Theme::catalog())->filter(fn ($t) => $isPro || empty($t['is_pro']))->keys()->all();
        $keys[] = $request->user()->profileRecord()->themeKey();

        return array_values(array_unique($keys));
    }
}
