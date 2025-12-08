<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'key_hash',
        'last_used_at',
    ];

    protected $casts = [
        'affiliate_id' => 'integer',
        'last_used_at' => 'datetime',
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }
}
