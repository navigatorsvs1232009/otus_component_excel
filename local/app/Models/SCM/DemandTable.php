<?php

namespace Models\SCM;

use Bitrix\Main\Loader;
use CCrmLead;

Loader::includeModule('crm');

class DemandTable extends \Bitrix\Crm\LeadTable
{
    /**
     * @param  int  $demandId
     */
    public static function touch(int $demandId): void
    {
        static $demand = null;
        if (is_null($demand)) {
            $demand = new CCrmLead();
        }

        $_ = [];
        $demand->Update($demandId, $_);
    }
}
