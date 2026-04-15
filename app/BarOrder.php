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

    public function barItem()
{
    return $this->belongsTo(\App\EventBarItem::class, 'bar_item_id');
}

public function event()
{
    return $this->belongsTo(\App\Event::class, 'event_id');
}
}
