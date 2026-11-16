<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Theme extends Model
{
    protected $fillable = ['key', 'name', 'bg', 'text', 'button', 'button_text', 'font', 'button_style', 'is_active', 'is_pro', 'sort'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_pro'    => 'boolean',
    ];

    /** Per-request cache of the catalogue. */
    private static $catalog = [];

    /**
     * Themes as [key => [name, bg, text, button, button_text, font, button_style, is_pro]].
     * Falls back to the config file if the table doesn't exist yet (fresh install).
     */
    public static function catalog(bool $activeOnly = true): array
    {
        $cacheKey = $activeOnly ? 'active' : 'all';
        if (isset(self::$catalog[$cacheKey])) {
            return self::$catalog[$cacheKey];
        }

        if (! Schema::hasTable('themes')) {
            return self::$catalog[$cacheKey] = collect(config('linkora_design.themes'))->map(fn ($t) => $t + ['is_pro' => false])->all();
        }

        return self::$catalog[$cacheKey] = static::query()
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderBy('sort')->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Theme $t) => [$t->key => [
                'name' => $t->name, 'bg' => $t->bg, 'text' => $t->text, 'button' => $t->button,
                'button_text' => $t->button_text, 'font' => $t->font, 'button_style' => $t->button_style, 'is_pro' => $t->is_pro,
            ]])
            ->all();
    }

    public static function flushCatalog(): void
    {
        self::$catalog = [];
    }

    public function isDefault(): bool
    {
        return $this->key === config('linkora_design.default_theme');
    }

    protected static function booted()
    {
        static::saved(fn () => self::flushCatalog());
        static::deleted(fn () => self::flushCatalog());
    }
}
