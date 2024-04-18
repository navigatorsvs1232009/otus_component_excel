<?php

namespace EventHandlers\IblockElement;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CIBlockElement;
use EventHandlers\IblockElement\Interfaces\OnAfterSetPropertyValuesEventHandlerInterface;
use Models\ProductPropertyValueTable;
use Models\SCM\CompanyNomenclaturePropertyValuesTable;

class NomenclatureEventHandler implements OnAfterSetPropertyValuesEventHandlerInterface
{
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
        # найдём тек. связь. Если нет привязки к компании -> удалим
        $nomenclatureItem = CompanyNomenclaturePropertyValuesTable::query()
            ->setSelect(['ID' => 'IBLOCK_ELEMENT_ID', 'COMPANY_ID', 'PRODUCT_ID', 'COMPANY_STATUS_ID' => 'COMPANY_STATUS.ID'])
            ->where('ID', $elementId)
            ->registerRuntimeField('COMPANY_STATUS',
                new ReferenceField(
                    'COMPANY_STATUS',
                    ElementTable::class,
                    ['=this.COMPANY.UF_STATUS' => 'ref.ID']
                )
            )
            ->fetch();
        if (empty($nomenclatureItem['COMPANY_ID'])) {
            CIBlockElement::Delete($nomenclatureItem['ID']);
            return;
        }

        $product = ProductPropertyValueTable::query()
            ->setSelect(['*'])
            ->where('IBLOCK_ELEMENT_ID', $nomenclatureItem['PRODUCT_ID'])
            ->fetch();
        if (empty($product)) {
            return;
        }

        $product['EQUIPMENT'] = $product['EQUIPMENT_TYPE'] . ' ' . $product['OEM_EQUIPMENT_MODEL'];
        # заполним статус поставщика
        $product['SUPPLIER_STATUS_ID'] = $nomenclatureItem['COMPANY_STATUS_ID'];
        CIBlockElement::SetPropertyValuesEx(
            $elementId,
            COMPANY_NOMENCLATURE_IBLOCK_ID,
            $product
        );
    }
}
