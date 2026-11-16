<?php

namespace App\Http\Controllers;

use App\Models\LinkClick;
use App\Models\ProfileView;
use App\Support\Stats;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $profile = $user->profileRecord();
        $week = new Stats($user, 7);

        $linkCount = $user->links()->count();
        $activeLinks = $user->links()->active()->count();

        $stats = [
            'views'        => ProfileView::where('user_id', $user->id)->count(),
            'views_week'   => $week->views(),
            'clicks'       => LinkClick::where('user_id', $user->id)->count(),
            'clicks_week'  => $week->clicks(),
            'links'        => $linkCount,
            'active_links' => $activeLinks,
        ];

        $checklist = [
            ['Claim your username', true, route('dashboard.profile')],
            ['Add a profile photo and bio', (bool) ($profile->avatarUrl() && $profile->bio), route('dashboard.profile')],
            ['Add your first link', $linkCount > 0, route('dashboard.links')],
            ['Pick a theme', $profile->theme !== config('linkora_design.default_theme') || ! empty($profile->design), route('dashboard.appearance')],
            ['Publish your page', $profile->is_published, route('dashboard.profile')],
        ];

        return view('dashboard.index', [
            'user'       => $user,
            'profile'    => $profile,
            'stats'      => $stats,
            'chart'      => $week->daily(),
            'checklist'  => $checklist,
            'activities' => $user->activities()->latest('id')->take(8)->get(),
            'topLinks'   => $week->topLinks(3),
        ]);
    }
}
