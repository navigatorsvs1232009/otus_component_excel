<?php

namespace EventHandlers\IblockElement;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use EventHandlers\IblockElement\Interfaces\OnAfterSetPropertyValuesEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeDeleteEventHandlerInterface;
use Models\SCM\CompanySupplyAreaPropertyValuesTable;
use Models\SCM\CompanyTable;

class CompanySupplyAreasEventHandler implements OnAfterSetPropertyValuesEventHandlerInterface, OnBeforeDeleteEventHandlerInterface
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
        $companySupplyAreas = CompanySupplyAreaPropertyValuesTable::getById($elementId)->fetch();
        CompanyTable::touch($companySupplyAreas['COMPANY_ID']);
    }

    /**
     * @param  int  $elementId
     *
     * @return bool|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onBeforeDelete(int $elementId): ?bool
    {
        $companySupplyAreas = CompanySupplyAreaPropertyValuesTable::getById($elementId)->fetch();

        if ($companySupplyAreas['COMPANY_ID']) {
            CompanyTable::touch($companySupplyAreas['COMPANY_ID']);
        }

        return null;
    }
}
