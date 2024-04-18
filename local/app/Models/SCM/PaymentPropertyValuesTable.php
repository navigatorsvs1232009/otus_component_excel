<?php

namespace Models\SCM;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\DealTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Models\AbstractIblockPropertyValuesTable;

Loader::includeModule('crm');

class PaymentPropertyValuesTable extends AbstractIblockPropertyValuesTable
{
    const IBLOCK_ID = PAYMENTS_IBLOCK_ID;

    /**
     * @return ReferenceField[]
     * @throws ArgumentException
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return parent::getMap() + [
            'PAYER_COMPANY' => new ReferenceField(
                'PAYER_COMPANY',
                CompanyTable::class,
                ['=this.PAYER_COMPANY_ID' => 'ref.ID']
            ),

            'PAYEE_COMPANY' => new ReferenceField(
                'PAYEE_COMPANY',
                CompanyTable::class,
                ['=this.PAYEE_COMPANY_ID' => 'ref.ID']
            ),

            'ORDER_REF' => new ReferenceField( // todo убрать _REF
                'ORDER_REF',
                OrderPropertyValueTable::class,
                ['=this.ORDER' => 'ref.IBLOCK_ELEMENT_ID']
            ),

            'PURCHASE_ORDER' => new ReferenceField(
                'PURCHASE_ORDER',
                DealTable::class,
                ['=this.PURCHASE_ORDER_ID' => 'ref.ID']
            ),

            'STAGE_REF' => new ReferenceField(
                'STAGE_REF',
                ElementTable::class,
                ['=this.STAGE' => 'ref.ID']
            ),

            'BANK_TRANSACTION' => new ReferenceField(
                'BANK_TRANSACTION',
                BankTransactionsPropertyValuesTable::class,
                ['=this.BANK_TRANSACTION_ID' => 'ref.IBLOCK_ELEMENT_ID']
            ),

            'STATUS_REF' => new ReferenceField(
                'STATUS_REF',
                PropertyEnumerationTable::class,
                ['=this.STATUS' => 'ref.ID']
            )
        ];
    }
}
