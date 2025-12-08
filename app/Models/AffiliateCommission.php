<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'order_id',
        'subscription_id',
        'type',
        'rate',
        'amount',
        'currency',
        'status',
        'payout_id',
        'eligible_payout_at',
    ];

    protected $casts = [
        'affiliate_id'       => 'integer',
        'order_id'           => 'integer',
        'subscription_id'    => 'integer',
        'rate'               => 'decimal:2',
        'amount'             => 'decimal:2',
        'payout_id'          => 'integer',
        'eligible_payout_at' => 'datetime',
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }
}
