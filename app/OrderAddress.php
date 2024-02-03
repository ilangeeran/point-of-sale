<?php

namespace App;

use App\Address;
use Illuminate\Database\Eloquent\Builder;

class OrderAddress extends Address 
{
    public const ADDRESS_TYPE_SHIPPING = 'order_shipping';
    public const ADDRESS_TYPE_BILLING = 'order_billing';

    /**
     * @var array default values
     */
    protected $attributes = [
        'address_type' => self::ADDRESS_TYPE_BILLING,
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        static::addGlobalScope('address_type', static function (Builder $builder) {
            $builder->whereIn('address_type', [
                self::ADDRESS_TYPE_BILLING,
                self::ADDRESS_TYPE_SHIPPING
            ]);
        });

        parent::boot();
    }
}
