<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Link extends Model
{
    protected $fillable = ['title', 'subtitle', 'url', 'icon', 'is_active', 'position'];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_blocked' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clickEvents()
    {
        return $this->hasMany(LinkClick::class);
    }

    /** Visible on the public page: switched on by the user and not blocked by an admin. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_blocked', false);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('id');
    }

    /** Chosen icon key, or one guessed from the URL's domain, or null. */
    public function iconKey(): ?string
    {
        if ($this->icon && array_key_exists($this->icon, config('linkora_design.link_icons'))) {
            return $this->icon;
        }

        $host = Str::lower(preg_replace('/^www\./', '', (string) parse_url($this->url, PHP_URL_HOST)));
        foreach (config('linkora_design.icon_domains') as $domain => $key) {
            if ($host === $domain || Str::endsWith($host, '.' . $domain)) {
                return $key;
            }
        }

        return null;
    }

    public function iconClass(): ?string
    {
        $key = $this->iconKey();

        return $key ? config('linkora_design.link_icons.' . $key . '.0') : null;
    }

    public function host(): string
    {
        return preg_replace('/^www\./', '', (string) parse_url($this->url, PHP_URL_HOST));
    }

    public function toDashboardArray(): array
    {
        return [
            'id'        => $this->id,
            'title'     => $this->title,
            'subtitle'  => $this->subtitle,
            'url'       => $this->url,
            'host'      => $this->host(),
            'icon'      => $this->icon,
            'iconClass' => $this->iconClass() ?: 'fa-solid fa-link',
            'is_active' => $this->is_active,
            'is_blocked' => (bool) $this->is_blocked,
            'blocked_reason' => $this->blocked_reason,
            'clicks'    => $this->clicks,
        ];
    }
}
