<?php

namespace App\Dao\Models;

use App\Dao\Models\Core\SystemModel;


/**
 * Class Webhook
 *
 * @property $webhook_id
 * @property $webhook_nama
 * @property $webhook_data
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */

class Webhook extends SystemModel
{
    protected $perPage = 20;
    protected $table = 'webhook';
    protected $primaryKey = 'webhook_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['webhook_id', 'webhook_nama', 'webhook_data'];


}
