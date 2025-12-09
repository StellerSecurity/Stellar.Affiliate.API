<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'name',
        'source',
        'sub_id1',
        'sub_id2',
        'country_focus',
    ];

    protected $casts = [
        'affiliate_id' => 'integer',
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }


    public function clicks()
    {
        return $this->hasMany(AffiliateClick::class, 'campaign_id');
    }

    public function sessions()
    {
        return $this->hasMany(AffiliateSession::class, 'campaign_id');
    }
}
