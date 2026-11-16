<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Enquiry;
use App\Models\Link;
use App\Models\LinkClick;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\ProfileView;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Admin overview. Every number here comes from the live database — nothing is estimated or sample data.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $members = User::where('role', User::ROLE_USER);
        $now = now();
        $weekAgo = $now->copy()->subDays(7);
        $twoWeeksAgo = $now->copy()->subDays(14);

        $stats = [
            'users'          => (clone $members)->count(),
            'new_this_week'  => (clone $members)->where('created_at', '>=', $weekAgo)->count(),
            'new_prev_week'  => (clone $members)->whereBetween('created_at', [$twoWeeksAgo, $weekAgo])->count(),
            'blocked'        => (clone $members)->where('status', User::STATUS_SUSPENDED)->count(),
            'active_7d'      => (clone $members)->where('last_login_at', '>=', $weekAgo)->count(),
            'profiles'       => Profile::count(),
            'published'      => Profile::where('is_published', true)->count(),
            'links'          => Link::count(),
            'links_blocked'  => Link::where('is_blocked', true)->count(),
            'views'          => ProfileView::count(),
            'views_7d'       => ProfileView::where('created_at', '>=', $weekAgo)->count(),
            'views_prev_7d'  => ProfileView::whereBetween('created_at', [$twoWeeksAgo, $weekAgo])->count(),
            'clicks'         => LinkClick::count(),
            'clicks_7d'      => LinkClick::where('created_at', '>=', $weekAgo)->count(),
            'clicks_prev_7d' => LinkClick::whereBetween('created_at', [$twoWeeksAgo, $weekAgo])->count(),
            'subscriptions'  => Subscription::current()->count(),
            'revenue'        => (int) Payment::where('status', 'paid')->sum('amount'),
            'revenue_30d'    => (int) Payment::where('status', 'paid')->where('paid_at', '>=', $now->copy()->subDays(30))->sum('amount'),
            'enquiries_new'  => Enquiry::unread()->count(),
        ];
        $stats['ctr'] = $stats['views'] ? round($stats['clicks'] / $stats['views'] * 100, 1) : 0;

        return view('admin.dashboard', [
            'stats'           => $stats,
            'chart'           => $this->dailySeries(30),
            'funnel'          => $this->funnel($members),
            'plans'           => $this->planMix($stats['users']),
            'devices'         => $this->devices(),
            'topProfiles'     => $this->topProfiles(),
            'activity'        => Activity::with('user:id,name,username')->latest('id')->take(8)->get(),
            'recentUsers'     => (clone $members)->with('profile')->latest('id')->take(6)->get(),
            'recentEnquiries' => Enquiry::latest('id')->take(5)->get(),
        ]);
    }

    /** Sign-ups, profile views and link clicks per day for the last $days days. */
    private function dailySeries(int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $range = collect(range(0, $days - 1))->map(fn ($i) => $start->copy()->addDays($i));
        $perDay = function ($query, string $column = 'created_at') use ($start) {
            return $query->where($column, '>=', $start)
                ->selectRaw("DATE($column) as day, COUNT(*) as total")->groupBy('day')->pluck('total', 'day');
        };

        $signups = $perDay(User::where('role', User::ROLE_USER));
        $views = $perDay(ProfileView::query());
        $clicks = $perDay(LinkClick::query());

        $pick = fn ($set) => $range->map(fn (Carbon $d) => (int) ($set[$d->toDateString()] ?? 0))->values();

        return [
            'labels'  => $range->map(fn (Carbon $d) => $d->format('d M'))->values(),
            'signups' => $pick($signups),
            'views'   => $pick($views),
            'clicks'  => $pick($clicks),
        ];
    }

    /** How far members get: signed up → added a link → published their page → got a visit. */
    private function funnel($members): array
    {
        $ids = (clone $members)->pluck('id');
        $total = $ids->count();
        $steps = [
            ['Signed up', $total],
            ['Added a link', Link::whereIn('user_id', $ids)->distinct()->count('user_id')],
            ['Published page', Profile::whereIn('user_id', $ids)->where('is_published', true)->count()],
            ['Got a visit', ProfileView::whereIn('user_id', $ids)->distinct()->count('user_id')],
        ];

        return array_map(fn ($s) => ['label' => $s[0], 'value' => $s[1], 'pct' => $total ? round($s[1] / $total * 100) : 0], $steps);
    }

    /** Members per plan (anyone without a current paid subscription is on Free). */
    private function planMix(int $members): array
    {
        $paid = Subscription::current()
            ->whereIn('user_id', User::where('role', User::ROLE_USER)->select('id'))
            ->select('plan_id', DB::raw('COUNT(DISTINCT user_id) as total'))->groupBy('plan_id')->pluck('total', 'plan_id');

        $rows = [];
        foreach (Plan::orderBy('sort')->get() as $plan) {
            $rows[] = ['name' => $plan->name, 'value' => (int) ($paid[$plan->id] ?? 0), 'free' => $plan->key === Plan::FREE];
        }
        $paidTotal = array_sum(array_column(array_filter($rows, fn ($r) => ! $r['free']), 'value'));
        foreach ($rows as &$r) {
            if ($r['free']) {
                $r['value'] = max(0, $members - $paidTotal);
            }
        }

        return $rows;
    }

    /** Device split of profile views in the last 30 days. */
    private function devices(): array
    {
        $rows = ProfileView::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('device, COUNT(*) as total')->groupBy('device')->pluck('total', 'device');

        return [
            'Mobile'  => (int) ($rows['mobile'] ?? 0),
            'Desktop' => (int) ($rows['desktop'] ?? 0),
            'Tablet'  => (int) ($rows['tablet'] ?? 0),
        ];
    }

    /** Most-viewed profiles in the last 30 days. */
    private function topProfiles()
    {
        return ProfileView::where('profile_views.created_at', '>=', now()->subDays(30))
            ->join('users', 'users.id', '=', 'profile_views.user_id')
            ->select('users.id', 'users.name', 'users.username', DB::raw('COUNT(*) as views'))
            ->groupBy('users.id', 'users.name', 'users.username')
            ->orderByDesc('views')->take(5)->get();
    }
}
