<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Profile extends Model
{
    protected $fillable = [
        'display_name', 'headline', 'bio', 'contact_email', 'phone', 'website', 'location', 'socials', 'theme', 'design',
    ];

    protected $casts = [
        'socials'      => 'array',
        'design'       => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function name(): string
    {
        return $this->display_name ?: $this->user->name;
    }

    /** Uploaded photo, else the Google photo, else null (initials are shown). */
    public function avatarUrl(): ?string
    {
        if ($this->avatar_path) {
            return Storage::disk('public')->url($this->avatar_path);
        }

        return $this->user->avatar ?: null;
    }

    public function bgImageUrl(): ?string
    {
        return $this->bg_image_path ? Storage::disk('public')->url($this->bg_image_path) : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name())) ?: [];
        $i = collect($parts)->filter()->take(2)->map(fn ($p) => Str::upper(Str::substr($p, 0, 1)))->implode('');

        return $i ?: 'L';
    }

    /** Only social links that are filled in, in config order. */
    public function socialLinks(): array
    {
        $saved = $this->socials ?: [];
        $out = [];
        foreach (config('linkora_design.socials') as $key => [$icon, $label]) {
            if (! empty($saved[$key])) {
                $out[] = ['key' => $key, 'url' => $saved[$key], 'icon' => $icon, 'label' => $label];
            }
        }

        return $out;
    }

    public function themeKey(): string
    {
        return array_key_exists($this->theme, Theme::catalog(false)) ? $this->theme : config('linkora_design.default_theme');
    }
}
