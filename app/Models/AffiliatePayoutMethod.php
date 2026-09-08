<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliatePayoutMethod extends Model
{
    use HasFactory;

    protected $hidden = ['data', 'encrypted_details'];

    protected $fillable = [
        'affiliate_id',
        'type',
        'data',
        'is_default',
    ];

    protected $casts = [
        'affiliate_id' => 'integer',
        'data'         => 'array',
        'is_default'   => 'boolean',
        'encrypted_details' => 'encrypted:array',
        'verified_at' => 'datetime',
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }
}
