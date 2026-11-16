<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Bootstrap pagination markup (the v4 template works with Bootstrap 5).
        Paginator::useBootstrap();

        // Behind a proxy / load balancer in production, always generate https:// links.
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        $this->applySettings();
    }

    /**
     * Copy admin-edited settings over the config defaults, e.g. linkora.support_email.
     */
    private function applySettings(): void
    {
        $values = Setting::all_values();

        foreach (Setting::FIELDS as $key => [$configPath, $label, $type]) {
            if (! array_key_exists($key, $values) || $values[$key] === null || $values[$key] === '') {
                continue;
            }
            config([$configPath => $type === 'bool' ? (bool) $values[$key] : $values[$key]]);
        }
    }
}
