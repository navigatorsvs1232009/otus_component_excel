<?php

namespace EventHandlers\IblockElement;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CIBlockElement;
use EventHandlers\IblockElement\Interfaces\OnAfterSetPropertyValuesEventHandlerInterface;
use Models\SCM\CompanyNomenclaturePropertyValuesTable;
use Models\SCM\RfqProductRowsPropertyValuesTable;
use Services\Infrastructure\IblockElementNameHandler;

class RfqProductRowsHandler implements OnAfterSetPropertyValuesEventHandlerInterface
{
    const ENTITY_NAME = 'RFQ product row';

    /**
     * @param $elementId
     * @param $propertyValues
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onAfterSetPropertyValues($elementId, $propertyValues): void
    {
        $rfqProductRow = RfqProductRowsPropertyValuesTable::getById($elementId)->fetch();

        $this->handleCompanyNomenclatureItem($rfqProductRow);

        IblockElementNameHandler::handle($elementId, self::ENTITY_NAME);
    }

    /**
     * @param  array  $rfqProductRow
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function handleCompanyNomenclatureItem(array $rfqProductRow): void
    {
        if (empty($rfqProductRow['PRODUCT_ID'])
            || empty($rfqProductRow['SUPPLIER_ID'])
            || empty($rfqProductRow['OPTIMAL_QUANTITY'])
        ) {
            return;
        }

        $nomenclatureItem = CompanyNomenclaturePropertyValuesTable::getList([
            'filter' => [
                '=PRODUCT_ID' => $rfqProductRow['PRODUCT_ID'],
                '=COMPANY_ID' => $rfqProductRow['SUPPLIER_ID']
            ]
        ])->fetch();

        if (empty($nomenclatureItem)) {
            (new CIBlockElement())->Add([
                'NAME'            => '-',
                'IBLOCK_ID'       => COMPANY_NOMENCLATURE_IBLOCK_ID,
                'PROPERTY_VALUES' => [
                    'PRODUCT_ID'       => $rfqProductRow['PRODUCT_ID'],
                    'COMPANY_ID'       => $rfqProductRow['SUPPLIER_ID'],
                    'OPTIMAL_QUANTITY' => $rfqProductRow['QUANTITY'],
                ],
            ]);

        } else {
            CIBlockElement::SetPropertyValuesEx(
                $nomenclatureItem['IBLOCK_ELEMENT_ID'],
                COMPANY_NOMENCLATURE_IBLOCK_ID,
                ['OPTIMAL_QUANTITY' => $rfqProductRow['QUANTITY']]
            );
        }
    }
}
