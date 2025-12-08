<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'campaign_id',
        'source',
        'session_token',
        'browser_fingerprint',
        'expires_at',
    ];

    protected $casts = [
        'affiliate_id' => 'integer',
        'campaign_id'  => 'integer',
        'expires_at'   => 'datetime',
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function campaign()
    {
        return $this->belongsTo(AffiliateCampaign::class);
    }
}
