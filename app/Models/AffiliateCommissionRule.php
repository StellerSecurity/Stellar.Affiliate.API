<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateCommissionRule extends Model
{
    protected $fillable = [
        'affiliate_id',
        'product',
        'type',
        'rate',
        'is_active',
        'updated_by_user_id',
    ];

    protected $casts = [
        'affiliate_id' => 'integer',
        'rate' => 'decimal:4',
        'is_active' => 'boolean',
        'updated_by_user_id' => 'integer',
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
