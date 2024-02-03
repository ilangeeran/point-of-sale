<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OnlineOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'online_orders';

    protected $guarded = ['id'];

    public function items()
    {
        return $this->hasMany(OnlineOrderLine::class, 'online_order_id', 'id');
    }

    /**
     * Get the addresses for the order.
     */
    public function addresses()
    {
        return $this->hasMany(OrderAddress::class, 'order_id');
    }

    /**
     * Get the biling address for the cart.
     */
    public function billing_address()
    {
        return $this->addresses()->where('address_type', OrderAddress::ADDRESS_TYPE_BILLING);
    }

    /**
     * Get billing address for the cart.
     */
    public function getBillingAddressAttribute()
    {
        return $this->billing_address()->first();
    }

    /**
     * Get the shipping address for the cart.
     */
    public function shipping_address()
    {
        return $this->addresses()->where('address_type', OrderAddress::ADDRESS_TYPE_SHIPPING);
    }

    /**
     * Get shipping address for the cart.
     */
    public function getShippingAddressAttribute()
    {
        return $this->shipping_address()->first();
    }
}
