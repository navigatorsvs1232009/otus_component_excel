<?php

namespace Models\SCM;

use Bitrix\Crm\CompanyTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\SystemException;
use Models\AbstractIblockPropertyValuesTable;
use Models\ProductPropertyValueTable;

class ProductReferencePriceTable extends AbstractIblockPropertyValuesTable
{
    public const IBLOCK_ID = PRODUCTS_REFERENCE_PRICE_IBLOCK_ID;

    /**
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return parent::getMap() + [
                'INCOTERMS' => new ReferenceField(
                    'INCOTERMS',
                    ElementTable::class,
                    ['=this.INCOTERMS_ID' => 'ref.ID']
                ),

                'INCOTERMS_PLACE' => new ReferenceField(
                    'INCOTERMS_PLACE',
                    ElementTable::class,
                    ['=this.INCOTERMS_PLACE_ID' => 'ref.ID']
                ),

                'SUPPLIER' => new ReferenceField(
                    'SUPPLIER',
                    CompanyTable::class,
                    ['=this.SUPPLIER_ID' => 'ref.ID']
                ),

                'PRODUCT' => new ReferenceField(
                    'PRODUCT',
                    ProductPropertyValueTable::class,
                    ['=this.PRODUCT_ID' => 'ref.IBLOCK_ELEMENT_ID']
                ),
            ];
    }
}
