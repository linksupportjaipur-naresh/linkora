<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public const METHODS = ['manual' => 'Manual', 'upi' => 'UPI', 'card' => 'Card', 'netbanking' => 'Net banking', 'cash' => 'Cash', 'bank_transfer' => 'Bank transfer'];
    public const STATUSES = ['paid', 'pending', 'refunded', 'failed'];

    protected $fillable = ['user_id', 'subscription_id', 'amount', 'currency', 'method', 'status', 'reference', 'notes', 'paid_at'];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
