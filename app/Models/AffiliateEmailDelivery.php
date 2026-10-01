<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateEmailDelivery extends Model
{
    protected $fillable = [
        'affiliate_id',
        'campaign',
        'status',
        'attempted_at',
        'sent_at',
        'error_code',
    ];

    protected $casts = [
        'affiliate_id' => 'integer',
        'attempted_at' => 'datetime',
        'sent_at' => 'datetime',
    ];
}
