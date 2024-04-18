<?php

namespace Repositories;

use Bitrix\Currency\CurrencyTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

Loader::includeModule('currency');

class Currencies
{
    /**
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function all(): array
    {
        static $cache = null;

        return $cache ?: $cache = array_column(CurrencyTable::getList(['select' => ['CURRENCY'], 'order' => ['sort' => 'ASC']])->fetchAll(), 'CURRENCY');
    }
}
