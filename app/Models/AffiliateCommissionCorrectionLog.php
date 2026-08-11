<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateCommissionCorrectionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'affiliate_commission_id',
        'affiliate_id',
        'reason',
        'old_product',
        'new_product',
        'old_rate',
        'new_rate',
        'old_order_amount',
        'new_order_amount',
        'old_amount',
        'new_amount',
        'created_at',
    ];

    protected $casts = [
        'affiliate_commission_id' => 'integer',
        'affiliate_id' => 'integer',
        'old_rate' => 'decimal:4',
        'new_rate' => 'decimal:4',
        'old_order_amount' => 'decimal:2',
        'new_order_amount' => 'decimal:2',
        'old_amount' => 'decimal:6',
        'new_amount' => 'decimal:6',
        'created_at' => 'datetime',
    ];

    public function commission()
    {
        return $this->belongsTo(AffiliateCommission::class, 'affiliate_commission_id');
    }

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }
}
