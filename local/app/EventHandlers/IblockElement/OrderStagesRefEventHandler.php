<?php

namespace EventHandlers\IblockElement;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CIBlockElement;
use EventHandlers\IblockElement\Interfaces\OnAfterSetPropertyValuesEventHandlerInterface;

class OrderStagesRefEventHandler implements OnAfterSetPropertyValuesEventHandlerInterface
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
        $orderStageRefItem = $this->getOrderStageRefItem($elementId);
        if (empty($orderStageRefItem)) {
            return;
        }

        (new CIBlockElement())->Update($elementId, ['CODE' => $orderStageRefItem['SHORT_NAME']]);
    }

    /**
     * @param  int  $id
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getOrderStageRefItem(int $id): array
    {
        return ElementTable::getList([
            'select' => ['ID', 'SHORT_NAME' => 'PREVIEW_TEXT'],
            'filter' => ['IBLOCK_ID' => ORDER_STAGES_REF_IBLOCK_ID, 'ID' => $id]
        ])->fetch() ?: [];
    }
}
