<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'campaign_id',
        'order_id',
        'subscription_id',
        'product',
        'order_amount',
        'type',
        'rate',
        'rate_source',
        'amount',
        'currency',
        'status',
        'approved_at',
        'rejected_at',
        'paid_out_at',
        'payout_id',
        'eligible_payout_at',
        'external_payment_id',
    ];

    protected $casts = [
        'affiliate_id' => 'integer',
        'campaign_id' => 'integer',
        'rate' => 'decimal:4',
        'order_amount' => 'decimal:2',
        'amount' => 'decimal:6',
        'payout_id' => 'integer',
        'eligible_payout_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'paid_out_at' => 'datetime',
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function campaign()
    {
        return $this->belongsTo(AffiliateCampaign::class, 'campaign_id');
    }

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(AffiliateCommissionStatusLog::class, 'affiliate_commission_id')->latest();
    }

    public function correctionLogs()
    {
        return $this->hasMany(AffiliateCommissionCorrectionLog::class, 'affiliate_commission_id')->latest('created_at');
    }
}
