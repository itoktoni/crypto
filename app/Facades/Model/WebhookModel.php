<?php

namespace App\Facades\Model;

use Illuminate\Support\Facades\Facade;

class WebhookModel extends \App\Dao\Models\Webhook
{
    protected static function getFacadeAccessor()
    {
        return getClass(__CLASS__);
    }
}