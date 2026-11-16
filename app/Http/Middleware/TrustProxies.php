<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Set TRUSTED_PROXIES in .env when the app runs behind a load balancer / Cloudflare,
     * e.g. TRUSTED_PROXIES=* or a comma-separated list of IPs, so HTTPS is detected correctly.
     */
    public function __construct()
    {
        $value = config('linkora.trusted_proxies');
        if ($value) {
            $this->proxies = $value === '*' ? '*' : array_map('trim', explode(',', $value));
        }
    }
}
