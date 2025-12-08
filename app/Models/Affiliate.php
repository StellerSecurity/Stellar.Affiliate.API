<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_user_id',
        'public_code',
        'status',
        'country',
        'payout_currency',
        'base_redirect_url'
    ];

    protected $casts = [
        'external_user_id' => 'integer',
    ];

    // Relationships

    public function campaigns()
    {
        return $this->hasMany(AffiliateCampaign::class);
    }

    public function clicks()
    {
        return $this->hasMany(AffiliateClick::class);
    }

    public function sessions()
    {
        return $this->hasMany(AffiliateSession::class);
    }

    public function commissions()
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }

    public function payoutMethods()
    {
        return $this->hasMany(AffiliatePayoutMethod::class);
    }

    public function apiKeys()
    {
        return $this->hasMany(AffiliateApiKey::class);
    }
}
