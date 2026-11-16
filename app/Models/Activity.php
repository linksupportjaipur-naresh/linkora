<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'type', 'description'];

    /** type => [Font Awesome icon, tone class] */
    public const ICONS = [
        'link.created'      => ['fa-solid fa-plus', ''],
        'link.updated'      => ['fa-solid fa-pen', ''],
        'link.deleted'      => ['fa-regular fa-trash-can', 'pink'],
        'link.enabled'      => ['fa-solid fa-toggle-on', 'green'],
        'link.disabled'     => ['fa-solid fa-toggle-off', 'amber'],
        'links.reordered'   => ['fa-solid fa-arrows-up-down', ''],
        'profile.updated'   => ['fa-regular fa-id-card', ''],
        'profile.published' => ['fa-solid fa-globe', 'green'],
        'profile.unpublished' => ['fa-solid fa-eye-slash', 'amber'],
        'appearance.updated'  => ['fa-solid fa-palette', 'pink'],
        'password.updated'  => ['fa-solid fa-key', 'amber'],
        'account.created'   => ['fa-solid fa-star', 'green'],
        'account.reactivated' => ['fa-solid fa-power-off', 'green'],
        'email.updated'     => ['fa-regular fa-envelope', 'amber'],
        'account.updated'   => ['fa-regular fa-user', ''],
    ];

    public static function log(User $user, string $type, string $description): void
    {
        static::create(['user_id' => $user->id, 'type' => $type, 'description' => $description]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Short third-person wording for the admin feed ("published their page"), falling back to the stored text. */
    public function adminSummary(): string
    {
        $map = [
            'link.created'        => 'added a link',
            'link.updated'        => 'edited a link',
            'link.deleted'        => 'deleted a link',
            'link.enabled'        => 'turned a link on',
            'link.disabled'       => 'turned a link off',
            'links.reordered'     => 'reordered their links',
            'profile.updated'     => 'updated their profile',
            'profile.published'   => 'published their page',
            'profile.unpublished' => 'unpublished their page',
            'appearance.updated'  => 'changed their page design',
            'password.updated'    => 'changed their password',
            'account.created'     => 'joined Linkora',
            'account.reactivated' => 'reactivated their account',
            'email.updated'       => 'changed their email',
            'account.updated'     => 'updated account details',
        ];

        return $map[$this->type] ?? lcfirst($this->description);
    }

    public function icon(): string
    {
        return self::ICONS[$this->type][0] ?? 'fa-solid fa-circle';
    }

    public function tone(): string
    {
        return self::ICONS[$this->type][1] ?? '';
    }
}
