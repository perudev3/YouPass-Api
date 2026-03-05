<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BarOrder extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'bar_item_id',
        'quantity',
        'price',
        'code',
        'qr_code',
        'status'
    ];
}
