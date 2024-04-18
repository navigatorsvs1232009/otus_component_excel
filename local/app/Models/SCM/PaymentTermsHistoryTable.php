<?php

namespace Models\SCM;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;

class PaymentTermsHistoryTable extends DataManager
{
    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'e_scm_payment_terms_history';
    }

    /**
     * @return array
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return [
            'ID'               => new IntegerField('ID', ['primary' => true, 'autocomplete' => true]),
            'PAYMENT_TERMS_ID' => new IntegerField('PAYMENT_TERMS_ID'),
            'MODIFIED_AT'      => new DateTimeField('MODIFIED_AT'),
            'MODIFIED_BY_ID'   => new IntegerField('MODIFIED_BY_ID'),
            'MODIFIED_BY'      => new ReferenceField(
                'MODIFIED_BY',
                UserTable::class,
                ['=this.MODIFIED_BY_ID' => 'ref.ID']
            ),
            'DATA'             => new TextField('DATA', [
                'fetch_data_modification' => fn () => [fn ($value) => json_decode($value, true)]
            ]),
            'PAYMENT_TERMS'    => new ReferenceField(
                'PAYMENT_TERMS',
                PaymentTermsPropertyValuesTable::class,
                ['=this.PAYMENT_TERMS_ID' => 'ref.IBLOCK_ELEMENT_ID']
            ),
            'SUPPLIER_COMPANY' => new ReferenceField(
                'SUPPLIER_COMPANY',
                CompanyTable::class,
                ['=this.PAYMENT_TERMS.SUPPLIER_COMPANY_ID' => 'ref.ID']
            ),
            'BUYER_COMPANY'    => new ReferenceField(
                'BUYER_COMPANY',
                CompanyTable::class,
                ['=this.PAYMENT_TERMS.BUYER_COMPANY_ID' => 'ref.ID']
            ),
        ];
    }
}
