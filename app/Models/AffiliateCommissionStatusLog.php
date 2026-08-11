<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateCommissionStatusLog extends Model
{
    protected $fillable = [
        'affiliate_commission_id',
        'from_status',
        'to_status',
        'changed_by_user_id',
        'note',
    ];

    protected $casts = [
        'affiliate_commission_id' => 'integer',
        'changed_by_user_id' => 'integer',
    ];

    public function commission()
    {
        return $this->belongsTo(AffiliateCommission::class, 'affiliate_commission_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
