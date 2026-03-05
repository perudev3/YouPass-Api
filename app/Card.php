<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    protected $fillable = [
        'user_id',
        'brand',
        'last4',
        'expiry',
        'holder',
        'is_default'
    ];

    public function cards()
    {
        return $this->hasMany(Card::class);
    }
}
