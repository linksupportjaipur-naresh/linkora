<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileView extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'visitor', 'referrer', 'device'];
}
