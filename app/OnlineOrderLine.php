<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OnlineOrderLine extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'online_order_lines';

    protected $guarded = ['id'];

    public function product()
    {
        return $this->belongsTo(\App\Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(\App\Variation::class);
    }
    
    public function onlineOrder()
    {
        $this->belongsTo(OnlineOrder::class, 'online_order_id', 'id');
    }

}
