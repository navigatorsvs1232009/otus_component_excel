<?php

namespace EventHandlers\IblockElement;

use Agents\Integration\Me1C\RPriceExportAgent;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\Extension;
use CBitrixComponent;
use CIBlockElement;
use EventHandlers\IblockElement\Interfaces\OnAfterAddEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterUpdateEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeListElementEditFormFieldsPreparedEventHandlerInterface;
use Models\SCM\RPRiceProductRowsPropertyValuesTable;
use Services\Infrastructure\SCM\RpriceProductRowHistoryHandler;
use Services\Infrastructure\SCM\RpriceProductRowResponsibilityHandler;
use Bitrix\Main\LoaderException;

class RpriceProductRowEventHandler implements OnAfterAddEventHandlerInterface, OnBeforeListElementEditFormFieldsPreparedEventHandlerInterface, OnAfterUpdateEventHandlerInterface
{
    /**
     * @param $element
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onAfterAdd($element): void
    {
        if (empty($element['ID'])) {
            return;
        }

        $rpriceProductRow = RPRiceProductRowsPropertyValuesTable::getById($element['ID'])->fetch();

        (new RpriceProductRowResponsibilityHandler())->run($rpriceProductRow);
        (new RpriceProductRowHistoryHandler())->run($rpriceProductRow);
    }

    /**
     * @param  CBitrixComponent  $component
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws LoaderException
     */
    public function onBeforeListElementEditFormFieldsPrepared(CBitrixComponent $component): void
    {
        $statusEnumOptionsRef = RPRiceProductRowsPropertyValuesTable::getEnumPropertyOptions('STATUS_ENUM_ID', 'XML_ID');

        $component->arResult['CAN_DELETE_ELEMENT'] = false;
        $component->arResult['CAN_ADD_ELEMENT'] = false;
        $isBlocked = false;

        foreach ($component->arResult['ELEMENT_PROPS'] as $property) {

            # не даём сохранить заблокированный элемент
            if ($property['CODE'] === 'BLOCKED' && ((bool) $property['VALUE']) === true) {
                $isBlocked = true;
                $component->arParams['CAN_EDIT'] = false;
            }

            # не даём сохранить элемент не взятый в работу
            if ($property['CODE'] === 'STATUS_ENUM_ID' && $property['VALUE'] == $statusEnumOptionsRef['new']['ID']) {
                $component->arParams['CAN_EDIT'] = false;
            }
        }

        if (!$isBlocked) {
            Extension::load('element.lists.rprice_by_product.element_edit.engage');
        }
    }

    /**
     * @param $element
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onAfterUpdate($element): void
    {
        $rpriceProductRow = RPRiceProductRowsPropertyValuesTable::getByPrimary($element['ID'], ['select' => ['*', 'RFQ_DATA']])->fetch();

        if ($rpriceProductRow['BLOCKED']) {
            return;
        }

        $rpriceProductRowStatusEnumRef = RPRiceProductRowsPropertyValuesTable::getEnumPropertyOptions('STATUS_ENUM_ID', 'XML_ID');

        if ($rpriceProductRow['RFQ_DATA'] && $rpriceProductRow['STATUS_ENUM_ID'] != $rpriceProductRowStatusEnumRef['cost_processed']['ID']) {
            $rpriceProductRowNewStatusEnumId = $rpriceProductRowStatusEnumRef['cost_processed']['ID'];
        }

        if (empty($rpriceProductRow['RFQ_DATA']) && $rpriceProductRow['STATUS_ENUM_ID'] == $rpriceProductRowStatusEnumRef['cost_processed']['ID']) {
            $rpriceProductRowNewStatusEnumId = $rpriceProductRowStatusEnumRef['cost_required']['ID'];
        }

        if (isset($rpriceProductRowNewStatusEnumId)) {
            CIBlockElement::SetPropertyValuesEx(
                $element['ID'],
                RPRICE_PRODUCT_ROWS_IBLOCK_ID,
                ['STATUS_ENUM_ID' => $rpriceProductRowNewStatusEnumId]
            );

            $rpriceProductRow['STATUS_ENUM_ID'] = $rpriceProductRowNewStatusEnumId;
        }

        (new RpriceProductRowHistoryHandler())->run($rpriceProductRow);

        RPriceExportAgent::scheduleRunOnce($rpriceProductRow['RPRICE_ID']);
    }
}
