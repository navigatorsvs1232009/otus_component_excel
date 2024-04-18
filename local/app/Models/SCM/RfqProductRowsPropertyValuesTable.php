<?php

namespace Models\SCM;

use Bitrix\Crm\CompanyTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\SystemException;
use Models\AbstractIblockPropertyValuesTable;
use Models\ProductPropertyValueTable;

class RfqProductRowsPropertyValuesTable extends AbstractIblockPropertyValuesTable
{
    const IBLOCK_ID = RFQ_PRODUCT_ROWS_IBLOCK_ID;

    /**
     * @return array
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return parent::getMap() + [
            'RFQ' => new ReferenceField(
                'RFQ',
                RfqPropertyValuesTable::class,
                ['=this.RFQ_ID' => 'ref.IBLOCK_ELEMENT_ID']
            ),

            'PRODUCT' => new ReferenceField(
                'PRODUCT',
                ProductPropertyValueTable::class,
                ['=this.PRODUCT_ID' => 'ref.IBLOCK_ELEMENT_ID']
            ),

            'ELEMENT' => new ReferenceField(
                'ELEMENT',
                ElementTable::class,
                ['=this.IBLOCK_ELEMENT_ID' => 'ref.ID']
            ),

            'SUPPLIER' => new ReferenceField(
                'SUPPLIER',
                CompanyTable::class,
                ['=this.SUPPLIER_ID' => 'ref.ID']
            ),

            'STATUS' => new ReferenceField(
                'STATUS',
                ElementTable::class,
                ['=this.STATUS_ID' => 'ref.ID']
            ),

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

            'COMMENT' => new ExpressionField('COMMENT', 'ELEMENT.PREVIEW_TEXT')
        ];
    }

    /**
     * @param  array  $rfqProductRowsIds
     *
     * @return array
     * @throws SystemException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     */
    public static function getRfqProductRows(array $rfqProductRowsIds): array
    {
        if (empty($rfqProductRowsIds)) {
            return [];
        }

        $dbResult = self::getList([
            'select' => [
                'id'                    => 'IBLOCK_ELEMENT_ID',
                'rfqId'                 => 'RFQ_ID',
                'rfqName'               => 'RFQ.ELEMENT.NAME',
                'moldCost'              => 'MOLD_COST',
                'moldCostCurrency'      => 'MOLD_COST_CURRENCY',
                'purchasePrice'         => 'PRICE',
                'purchasePriceCurrency' => 'PRICE_CURRENCY',
                'productionTime'        => 'PRODUCTION_TIME',
                'supplierId'            => 'RFQ.SUPPLIER_ID',
                'supplierTitle'         => 'RFQ.SUPPLIER.TITLE',
                'supplierShortTitle'    => 'RFQ.SUPPLIER.UF_SHORT_TITLE',
                'incotermsName'         => 'RFQ.INCOTERMS.NAME',
                'incotermsPlaceName'    => 'RFQ.INCOTERMS_PLACE.NAME',
                'incotermsId'           => 'RFQ.INCOTERMS_ID',
                'incotermsPlaceId'      => 'RFQ.INCOTERMS_PLACE_ID',
            ],
            'filter' => ['id' => $rfqProductRowsIds],
        ]);
        while ($row = $dbResult->fetch()) {
            if (!empty($row['supplierId'])) {
                $row['supplier'] = "<a href='/crm/company/details/{$row['supplierId']}/'>".($row['supplierShortTitle'] ?: $row['supplierTitle'])."</a>";
            }

            $row['incoterms'] = $row['incotermsName'];
            $row['incotermsPlace'] = $row['incotermsPlaceName'];
            $row['rfqName'] = "<a href='/crm/rfq/?mode=edit&list_id=".RFQ_IBLOCK_ID."&element_id={$row['rfqId']}' target='_blank'>{$row['rfqName']}</a>";
            $row['purchasePrice'] = number_format((float) $row['purchasePrice'], 2, ',', ' ');
            $row['productionTime'] = (int) $row['productionTime'];

            if (empty($row['moldCost'])) {
                $row['moldCostCurrency'] = '';
            }

            $productRows[$row['id']] = $row;
        }

        return $productRows ?? [];
    }
}
