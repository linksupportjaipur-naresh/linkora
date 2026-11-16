<?php

namespace App\Support;

use App\Models\LinkClick;
use App\Models\ProfileView;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Read-side analytics for one user's page.
 */
class Stats
{
    private $user;
    private $from;
    private $days;

    public function __construct(User $user, int $days)
    {
        $this->user = $user;
        $this->days = $days;
        $this->from = now()->subDays($days - 1)->startOfDay();
    }

    public function views(): int
    {
        return ProfileView::where('user_id', $this->user->id)->where('created_at', '>=', $this->from)->count();
    }

    public function uniqueVisitors(): int
    {
        return ProfileView::where('user_id', $this->user->id)->where('created_at', '>=', $this->from)->distinct()->count('visitor');
    }

    public function clicks(): int
    {
        return LinkClick::where('user_id', $this->user->id)->where('created_at', '>=', $this->from)->count();
    }

    /** ['labels' => [...], 'views' => [...], 'clicks' => [...]] one entry per day. */
    public function daily(): array
    {
        $views = $this->perDay(ProfileView::class);
        $clicks = $this->perDay(LinkClick::class);
        $days = collect(range(0, $this->days - 1))->map(fn ($i) => $this->from->copy()->addDays($i));

        return [
            'labels' => $days->map(fn (Carbon $d) => $this->days <= 7 ? $d->format('D') : $d->format('d M'))->values(),
            'views'  => $days->map(fn (Carbon $d) => (int) ($views[$d->toDateString()] ?? 0))->values(),
            'clicks' => $days->map(fn (Carbon $d) => (int) ($clicks[$d->toDateString()] ?? 0))->values(),
        ];
    }

    /** Links sorted by clicks in the period (includes links with zero clicks). */
    public function topLinks(int $limit = 10): Collection
    {
        $counts = LinkClick::where('user_id', $this->user->id)
            ->where('created_at', '>=', $this->from)
            ->selectRaw('link_id, COUNT(*) as total')
            ->groupBy('link_id')
            ->pluck('total', 'link_id');

        return $this->user->links()->get()
            ->map(function ($link) use ($counts) {
                $link->period_clicks = (int) ($counts[$link->id] ?? 0);

                return $link;
            })
            ->sortByDesc(fn ($l) => [$l->period_clicks, $l->clicks])
            ->take($limit)
            ->values();
    }

    public function referrers(int $limit = 6): Collection
    {
        return ProfileView::where('user_id', $this->user->id)
            ->where('created_at', '>=', $this->from)
            ->selectRaw("COALESCE(referrer, 'Direct') as source, COUNT(*) as total")
            ->groupBy('source')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    public function devices(): array
    {
        $rows = ProfileView::where('user_id', $this->user->id)
            ->where('created_at', '>=', $this->from)
            ->selectRaw('device, COUNT(*) as total')
            ->groupBy('device')
            ->pluck('total', 'device');

        return [
            'mobile'  => (int) ($rows['mobile'] ?? 0),
            'desktop' => (int) ($rows['desktop'] ?? 0),
            'tablet'  => (int) ($rows['tablet'] ?? 0),
        ];
    }

    private function perDay(string $model)
    {
        return $model::where('user_id', $this->user->id)
            ->where('created_at', '>=', $this->from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');
    }
}
