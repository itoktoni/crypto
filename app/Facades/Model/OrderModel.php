<?php

namespace App\Facades\Model;

use Illuminate\Support\Facades\Facade;

class OrderModel extends \App\Dao\Models\Order
{
    protected static function getFacadeAccessor()
    {
        return getClass(__CLASS__);
    }
}