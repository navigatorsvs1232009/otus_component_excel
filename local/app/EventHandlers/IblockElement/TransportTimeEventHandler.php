<?php

namespace EventHandlers\IblockElement;

use Agents\Integration\Me1C\TransportTimeExportAgent;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use EventHandlers\IblockElement\Interfaces\OnAfterAddEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterUpdateEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeAddEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeDeleteEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeUpdateEventHandlerInterface;
use Models\SCM\TransportTimeRefPropertyValuesTable;
use Throwable;

class TransportTimeEventHandler implements OnAfterUpdateEventHandlerInterface, OnAfterAddEventHandlerInterface, OnBeforeDeleteEventHandlerInterface,
                                           OnBeforeAddEventHandlerInterface, OnBeforeUpdateEventHandlerInterface
{
    /**
     * @param $elementId
     *
     * @return bool|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onBeforeDelete($elementId): ?bool
    {
        $this->handleExport($elementId);

        return null;
    }

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

        $this->handleExport($element['ID']);
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
        $this->handleExport($element['ID']);
    }

    /**
     * @param  int  $transportTimeId
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function handleExport(int $transportTimeId): void
    {
        $transportTime = TransportTimeRefPropertyValuesTable::getRowById($transportTimeId);
        try {
            TransportTimeExportAgent::scheduleRunOnce(
                $transportTime['INCOTERMS_PLACE_ID'],
                $transportTime['TRANSPORT_TYPE_ID'],
                $transportTime['SUPPLY_AREA_ID']
            );

        } catch (Throwable $e) {
            // что-то не так
        }
    }

    /**
     * @param  array  $transportTime
     *
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function hasDuplicate(array $transportTime): bool
    {
        return (bool) TransportTimeRefPropertyValuesTable::getList([
            'select' => ['IBLOCK_ELEMENT_ID'],
            'filter' => [
                '=INCOTERMS_PLACE_ID' => $transportTime['INCOTERMS_PLACE_ID'],
                '=TRANSPORT_TYPE_ID'  => $transportTime['TRANSPORT_TYPE_ID'],
                '=SUPPLY_AREA_ID'     => $transportTime['SUPPLY_AREA_ID'],
                '!=IBLOCK_ELEMENT_ID' => $transportTime['IBLOCK_ELEMENT_ID'],
            ],
            'limit'  => 1,
        ])->fetch();
    }

    /**
     * @param $element
     *
     * @return bool|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onBeforeAdd(&$element): ?bool
    {
        $properties = TransportTimeRefPropertyValuesTable::getProperties();
        $transportTime = [
            'IBLOCK_ELEMENT_ID'  => $element['ID'] ?? 0,
            'INCOTERMS_PLACE_ID' => (int) $element['PROPERTY_VALUES'][$properties['INCOTERMS_PLACE_ID']['ID']],
            'TRANSPORT_TYPE_ID'  => (int) current($element['PROPERTY_VALUES'][$properties['TRANSPORT_TYPE_ID']['ID']])['VALUE'],
            'SUPPLY_AREA_ID'     => (int) current($element['PROPERTY_VALUES'][$properties['SUPPLY_AREA_ID']['ID']])['VALUE'],
        ];

        if ($this->hasDuplicate($transportTime)) {
            $GLOBALS['APPLICATION']->ThrowException('Duplicate error: such entry already exists');
            return false;
        }

        return null;
    }

    /**
     * @param $element
     *
     * @return bool|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onBeforeUpdate(&$element): ?bool
    {
        return $this->onBeforeAdd($element);
    }
}
