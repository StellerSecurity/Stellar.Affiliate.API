<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'campaign_id',
        'source',
        'session_id',
        'ip_hash',
        'user_agent',
        'landing_url',
        'referrer',
    ];

    protected $casts = [
        'affiliate_id' => 'integer',
        'campaign_id'  => 'integer',
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
