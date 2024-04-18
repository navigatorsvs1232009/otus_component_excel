<?php

namespace EventHandlers\IblockElement;

use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CIBlockElement;
use EventHandlers\IblockElement\Interfaces\OnBeforeAddEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeUpdateEventHandlerInterface;

class EmployeeResponsibilitiesHandler implements OnBeforeUpdateEventHandlerInterface, OnBeforeAddEventHandlerInterface
{
    /**
     * @param $element
     *
     * @return false
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onBeforeUpdate(&$element): ?bool
    {
        if ($this->isEmployeeResponsibilityExists($element['ID'] ?? 0, $this->fetchEmployeeId($element))) {
            $GLOBALS['APPLICATION']->ThrowException('Entry with the same employee already exists');
            return false;
        }

        return null;
    }

    /**
     * @param $element
     *
     * @return false
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onBeforeAdd(&$element): ?bool
    {
        return $this->onBeforeUpdate($element);
    }

    /**
     * @param  int  $employeeResponsibilityId
     * @param  int|null  $employeeId
     *
     * @return bool
     */
    private function isEmployeeResponsibilityExists(int $employeeResponsibilityId, int $employeeId): bool
    {
        return !empty(CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => EMPLOYEE_RESPONSIBILITIES_IBLOCK_ID, '!ID' => $employeeResponsibilityId, 'PROPERTY_EMPLOYEE_ID' => $employeeId],
            false,
            ['nTopCount' => 1],
            ['ID']
        )->Fetch());
    }

    /**
     * @param $element
     *
     * @return int
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function fetchEmployeeId($element): int
    {
        $employeeIdPropertyId = PropertyTable::getList([
                'select' => ['ID'],
                'filter' => ['IBLOCK_ID' => EMPLOYEE_RESPONSIBILITIES_IBLOCK_ID, 'CODE' => 'EMPLOYEE_ID'],
            ])->fetch()['ID'] ?? null;

        $employeeId = $element['PROPERTY_VALUES']['EMPLOYEE_ID'] ?? $element['PROPERTY_VALUES'][$employeeIdPropertyId] ?? null;

        return is_array($employeeId) ? current($employeeId)['VALUE'] : $employeeId;
    }
}
