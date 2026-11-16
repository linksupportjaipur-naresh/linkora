<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    public const STATUS_NEW = 'new';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'ip_address',
        'user_agent',
    ];

    public function scopeUnread($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }
}
