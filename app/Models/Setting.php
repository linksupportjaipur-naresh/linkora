<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-editable site settings (key/value). Loaded into config at boot,
 * see AppServiceProvider::applySettings().
 */
class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    private const CACHE_KEY = 'linkora.settings';

    /** key => [config path, label, type] */
    public const FIELDS = [
        'support_email'     => ['linkora.support_email', 'Support email', 'email'],
        'support_phone'     => ['linkora.support_phone', 'Support phone', 'text'],
        'address'           => ['linkora.address', 'Address', 'text'],
        'registration_open' => ['linkora.registration_open', 'Allow new sign-ups', 'bool'],
        'social_instagram'  => ['linkora.socials.instagram', 'Instagram URL', 'url'],
        'social_x'          => ['linkora.socials.x-twitter', 'X (Twitter) URL', 'url'],
        'social_linkedin'   => ['linkora.socials.linkedin', 'LinkedIn URL', 'url'],
        'social_youtube'    => ['linkora.socials.youtube', 'YouTube URL', 'url'],
    ];

    public static function all_values(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, function () {
                return Schema::hasTable('settings') ? static::query()->pluck('value', 'key')->all() : [];
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget(self::CACHE_KEY);
    }
}
