<?php

namespace App\Dao\Models;

use App\Dao\Models\Core\SystemModel;


/**
 * Class Order
 *
 * @property $order_id
 * @property $order_coin
 * @property $order_category
 * @property $order_side
 * @property $order_type
 * @property $order_qty
 * @property $order_price
 * @property $order_reference
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */

class Order extends SystemModel
{
    protected $perPage = 20;
    protected $table = 'order';
    protected $primaryKey = 'order_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['order_id', 'order_code', 'order_name', 'order_coin', 'order_category', 'order_side', 'order_type', 'order_qty', 'order_open', 'order_close', 'order_reference', 'order_date', 'order_status'];


}
