<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateInstallToken extends Model
{
    protected $fillable = [
        'install_token',
        'affiliate_id',
        'campaign_id',
        'source',
        'sub_id1',
        'sub_id2',
        'product',
        'user_id',
        'subscription_id',
        'platform',
        'device_id',
        'claimed_at',
        'expires_at',
    ];

    protected $casts = [
        'claimed_at'  => 'datetime',
        'expires_at'  => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function campaign()
    {
        return $this->belongsTo(AffiliateCampaign::class, 'campaign_id');
    }
}
