<?php

namespace Models;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Models\SCM\CompanyTable;

Loader::includeModule('crm');

class DeliveryTransportExpensesPropertyValuesTable extends AbstractIblockPropertyValuesTable
{
    const IBLOCK_ID = DELIVERY_TRANSPORT_EXPENSES_IBLOCK_ID;

    /**
     * @return ReferenceField[]
     * @throws ArgumentException
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return parent::getMap() + [
            'COUNTERAGENT_COMPANY' => new ReferenceField(
                'COUNTERAGENT_COMPANY',
                CompanyTable::class,
                ['=this.COUNTERAGENT_COMPANY_ID' => 'ref.ID'],
            ),
        ];
    }
}
