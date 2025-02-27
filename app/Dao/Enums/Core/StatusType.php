<?php

namespace App\Dao\Enums\Core;

use App\Dao\Traits\StatusTrait;
use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum as Enum;

class StatusType extends Enum implements LocalizedEnum
{
    use StatusTrait;

    public const Unset = null;

    public const Buy = 'buy';

    public const Sell = 'sell';

    public const Open = 'open';

    public const Close = 'close';

    public const Draft = 'draft';

}
