<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\Theme;
use App\Support\ProfileDesign;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Themes and plans.
 */
class CatalogController extends Controller
{
    // A solid hex colour, or a simple linear-gradient made only of angles, hex colours and percentages.
    private const BG_PATTERN = '/^(#[0-9a-fA-F]{3}|#[0-9a-fA-F]{6}|linear-gradient\(\s*\d{1,3}deg(\s*,\s*#[0-9a-fA-F]{6}(\s+\d{1,3}%)?){2,4}\s*\))$/';

    // ---------------- Themes ----------------

    public function themes()
    {
        $themes = Theme::orderBy('sort')->orderBy('id')->get();
        $usage = Profile::selectRaw('theme, COUNT(*) as c')->groupBy('theme')->pluck('c', 'theme');

        return view('admin.themes.index', compact('themes', 'usage'));
    }

    public function createTheme()
    {
        return view('admin.themes.form', ['theme' => new Theme([
            'bg' => '#FFFFFF', 'text' => '#1B1233', 'button' => '#7955E8', 'button_text' => '#FFFFFF',
            'font' => 'instrument', 'button_style' => 'pill', 'is_active' => true,
        ])]);
    }

    public function storeTheme(Request $request)
    {
        $data = $this->validateTheme($request);
        $data['key'] = $this->uniqueKey(Theme::class, $data['name']);
        $data['sort'] = (int) Theme::max('sort') + 1;
        Theme::create($data);

        return redirect()->route('admin.themes.index')->with('status', 'Theme “' . $data['name'] . '” added.');
    }

    public function editTheme(Theme $theme)
    {
        return view('admin.themes.form', compact('theme'));
    }

    public function updateTheme(Request $request, Theme $theme)
    {
        $data = $this->validateTheme($request);
        if ($theme->isDefault()) {
            $data['is_active'] = true;
            $data['is_pro'] = false;
        }
        $theme->update($data);

        return redirect()->route('admin.themes.index')->with('status', 'Theme “' . $theme->name . '” saved.');
    }

    public function toggleTheme(Theme $theme)
    {
        if ($theme->isDefault()) {
            return back()->with('error', 'The default theme must stay available.');
        }
        $theme->update(['is_active' => ! $theme->is_active]);

        return back()->with('status', 'Theme “' . $theme->name . '” is now ' . ($theme->is_active ? 'available' : 'hidden') . '.');
    }

    public function destroyTheme(Theme $theme)
    {
        if ($theme->isDefault()) {
            return back()->with('error', 'The default theme can’t be deleted.');
        }
        // Profiles using it fall back to the default theme automatically.
        Profile::where('theme', $theme->key)->update(['theme' => config('linkora_design.default_theme')]);
        $theme->delete();

        return back()->with('status', 'Theme deleted. Pages that used it now use the default theme.');
    }

    private function validateTheme(Request $request): array
    {
        $hex = ['required', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'];
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:40'],
            'bg'           => ['required', 'string', 'max:200', 'regex:' . self::BG_PATTERN],
            'text'         => $hex,
            'button'       => $hex,
            'button_text'  => $hex,
            'font'         => ['required', Rule::in(array_keys(config('linkora_design.fonts')))],
            'button_style' => ['required', Rule::in(array_keys(config('linkora_design.button_styles')))],
        ], [
            'bg.regex' => 'Use a colour like #F3EEFF or a gradient like linear-gradient(180deg, #F3EEFF 0%, #E4D9FF 100%).',
            '*.regex'  => 'Please use a hex colour like #7955E8.',
        ]);

        foreach (['text', 'button', 'button_text'] as $k) {
            $data[$k] = ProfileDesign::color($data[$k]);
        }
        $data['is_active'] = $request->boolean('is_active');
        $data['is_pro'] = $request->boolean('is_pro');

        return $data;
    }

    // ---------------- Plans ----------------

    public function plans()
    {
        $plans = Plan::withCount(['subscriptions as active_subscriptions_count' => fn ($q) => $q->current()])->orderBy('sort')->get();

        return view('admin.plans.index', compact('plans'));
    }

    public function createPlan()
    {
        return view('admin.plans.form', ['plan' => new Plan(['is_active' => true, 'cta' => 'Get started', 'features' => []])]);
    }

    public function storePlan(Request $request)
    {
        $data = $this->validatePlan($request);
        $data['key'] = $this->uniqueKey(Plan::class, $data['name']);
        $data['sort'] = (int) Plan::max('sort') + 1;
        Plan::create($data);

        return redirect()->route('admin.plans.index')->with('status', 'Plan “' . $data['name'] . '” added.');
    }

    public function editPlan(Plan $plan)
    {
        return view('admin.plans.form', compact('plan'));
    }

    public function updatePlan(Request $request, Plan $plan)
    {
        $data = $this->validatePlan($request);
        if ($plan->key === Plan::FREE) {
            $data['price_monthly'] = 0;
            $data['price_yearly'] = 0;
            $data['is_active'] = true;
        }
        $plan->update($data);

        return redirect()->route('admin.plans.index')->with('status', 'Plan “' . $plan->name . '” saved. The pricing page is updated.');
    }

    public function destroyPlan(Plan $plan)
    {
        if ($plan->key === Plan::FREE) {
            return back()->with('error', 'The Free plan can’t be deleted.');
        }
        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'This plan has subscriptions. Hide it instead of deleting it.');
        }
        $plan->delete();

        return back()->with('status', 'Plan deleted.');
    }

    private function validatePlan(Request $request): array
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:40'],
            'tagline'       => ['nullable', 'string', 'max:120'],
            'price_monthly' => ['required', 'integer', 'min:0', 'max:1000000'],
            'price_yearly'  => ['required', 'integer', 'min:0', 'max:10000000'],
            'features'      => ['nullable', 'string', 'max:3000'],
            'cta'           => ['required', 'string', 'max:40'],
        ]);

        $data['features'] = Plan::parseFeatures($data['features'] ?? '');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function uniqueKey(string $model, string $name): string
    {
        $base = Str::slug($name) ?: 'item';
        $base = substr($base, 0, 26);
        $key = $base;
        $i = 1;
        while ($model::where('key', $key)->exists()) {
            $key = $base . '-' . (++$i);
        }

        return $key;
    }
}
