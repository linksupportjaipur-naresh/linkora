<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinkClick extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['link_id', 'user_id', 'visitor', 'referrer', 'device'];

    public function link()
    {
        return $this->belongsTo(Link::class);
    }
}
