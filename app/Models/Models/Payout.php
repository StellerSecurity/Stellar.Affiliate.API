<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'amount',
        'currency',
        'status',
        'method_type',
        'method_details_snapshot',
        'external_reference',
        'paid_at',
    ];

    protected $casts = [
        'affiliate_id'            => 'integer',
        'amount'                  => 'decimal:2',
        'method_details_snapshot' => 'array',
        'paid_at'                 => 'datetime',
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function commissions()
    {
        return $this->hasMany(AffiliateCommission::class);
    }
}
