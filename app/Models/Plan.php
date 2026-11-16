<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    public const FREE = 'free';

    protected $fillable = ['key', 'name', 'tagline', 'price_monthly', 'price_yearly', 'features', 'cta', 'is_featured', 'is_active', 'sort'];

    protected $casts = [
        'features'    => 'array',
        'is_featured' => 'boolean',
        'is_active'   => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function isFree(): bool
    {
        return $this->key === self::FREE || ($this->price_monthly === 0 && $this->price_yearly === 0);
    }

    /** Shape used by the public pricing page. */
    public function toPricingArray(): array
    {
        return [
            'key'      => $this->key,
            'name'     => $this->name,
            'tagline'  => $this->tagline,
            'monthly'  => $this->price_monthly,
            'yearly'   => $this->price_yearly,
            'cta'      => $this->cta,
            'featured' => $this->is_featured,
            'features' => $this->features ?: [],
        ];
    }

    /** Features as editable text: one per line, "- " prefix means "not included". */
    public function featuresText(): string
    {
        return collect($this->features ?: [])
            ->map(fn ($f) => ($f[1] ? '' : '- ') . $f[0])
            ->implode("\n");
    }

    public static function parseFeatures(?string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn ($l) => trim($l))
            ->filter()
            ->map(fn ($l) => strpos($l, '-') === 0 ? [trim(substr($l, 1)), false] : [$l, true])
            ->filter(fn ($f) => $f[0] !== '')
            ->values()
            ->all();
    }
}
