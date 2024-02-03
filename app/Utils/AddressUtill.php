<?php
namespace App\Utils;

use App\Address;

class AddressUtill extends Util
{
    public function createOrder(array $data)
    {
        $address = $this->create($data);

        if(isset($data['is_save_or_update']) && $data['is_save_or_update']) {
            $data['address_type'] = 'customer';
            $this->create($data);
        }

        return $address;
    }

    public function create($data)
    {
        return Address::create($data);
    }

}