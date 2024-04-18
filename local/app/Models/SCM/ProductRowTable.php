<?php

namespace Models\SCM;

use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\StringField;

Loader::includeModule('crm');

class ProductRowTable extends \Bitrix\Crm\ProductRowTable
{
    /**
     * @return array
     */
    public static function getMap(): array
    {
        return parent::getMap() + [
                'MEASURE_CODE' => new StringField('MEASURE_CODE'),
                'MEASURE_NAME' => new StringField('MEASURE_NAME'),
            ];
    }
}
