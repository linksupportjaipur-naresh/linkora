<?php

namespace App\Support;

use App\Models\Link;
use App\Models\LinkClick;
use App\Models\ProfileView;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Records profile views and link clicks without storing raw IP addresses.
 */
class VisitTracker
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegrambot|embedly|curl|wget|python|headless|lighthouse/i';

    public static function isBot(Request $request): bool
    {
        $ua = (string) $request->userAgent();

        return $ua === '' || preg_match(self::BOT_PATTERN, $ua) === 1;
    }

    /** Counts a view once per visitor per profile every 30 minutes; owners and bots are skipped. */
    public static function recordView(Request $request, User $owner): bool
    {
        if (self::isBot($request) || optional($request->user())->id === $owner->id) {
            return false;
        }

        $key = 'viewed_profile.' . $owner->id;
        $last = $request->session()->get($key);
        if ($last && now()->timestamp - $last < 1800) {
            return false;
        }
        $request->session()->put($key, now()->timestamp);

        ProfileView::create([
            'user_id'  => $owner->id,
            'visitor'  => self::visitorHash($request),
            'referrer' => self::referrerHost($request),
            'device'   => self::device($request),
        ]);

        return true;
    }

    public static function recordClick(Request $request, Link $link): bool
    {
        if (self::isBot($request) || optional($request->user())->id === $link->user_id) {
            return false;
        }

        LinkClick::create([
            'link_id'  => $link->id,
            'user_id'  => $link->user_id,
            'visitor'  => self::visitorHash($request),
            'referrer' => $request->session()->get('profile_referrer.' . $link->user_id) ?? self::referrerHost($request),
            'device'   => self::device($request),
        ]);
        $link->increment('clicks');

        return true;
    }

    /** Daily-rotating hash: lets us count unique visitors without keeping IPs. */
    public static function visitorHash(Request $request): string
    {
        return sha1($request->ip() . '|' . $request->userAgent() . '|' . now()->toDateString() . '|' . config('app.key'));
    }

    public static function referrerHost(Request $request): ?string
    {
        $ref = (string) $request->headers->get('referer');
        $host = strtolower((string) parse_url($ref, PHP_URL_HOST));
        $host = preg_replace('/^(www|m|l|lm)\./', '', $host);

        if ($host === '' || $host === $request->getHost()) {
            return null;
        }

        return substr($host, 0, 120);
    }

    public static function device(Request $request): string
    {
        $ua = (string) $request->userAgent();
        if (preg_match('/ipad|tablet|kindle|playbook|(android(?!.*mobile))/i', $ua)) {
            return 'tablet';
        }

        return preg_match('/mobi|iphone|ipod|android|blackberry|opera mini|iemobile/i', $ua) ? 'mobile' : 'desktop';
    }
}
