<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\Stats;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public const RANGES = [7, 30];

    public function index(Request $request)
    {
        $range = (int) $request->query('range', 7);
        $range = in_array($range, self::RANGES, true) ? $range : 7;

        $stats = new Stats($request->user(), $range);
        $views = $stats->views();
        $clicks = $stats->clicks();

        return view('dashboard.analytics', [
            'range'     => $range,
            'views'     => $views,
            'unique'    => $stats->uniqueVisitors(),
            'clicks'    => $clicks,
            'ctr'       => $views > 0 ? round($clicks / $views * 100, 1) : 0,
            'daily'     => $stats->daily(),
            'topLinks'  => $stats->topLinks(),
            'referrers' => $stats->referrers(),
            'devices'   => $stats->devices(),
        ]);
    }
}
