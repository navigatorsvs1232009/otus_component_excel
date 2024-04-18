<?php

namespace EventHandlers\IblockElement;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use EventHandlers\IblockElement\Interfaces\OnAfterSetPropertyValuesEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnSetPropertyValuesEventHandlerInterface;
use Models\SCM\CompanyCurrencyRatesPropertyValuesTable;
use Services\Infrastructure\SCM\PricePerKiloHandler;

class CompanyCurrencyRateEventHandler implements OnAfterSetPropertyValuesEventHandlerInterface, OnSetPropertyValuesEventHandlerInterface
{
    private static array $state = [];

    /**
     * @param $elementId
     * @param $propertyValues
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onSetPropertyValues($elementId, $propertyValues): void
    {
        self::$state['onSetPropertyValues'][$elementId] = CompanyCurrencyRatesPropertyValuesTable::getById($elementId)->fetch();
    }

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
        $companyIdBefore = self::$state['onSetPropertyValues'][$elementId]['COMPANY_ID'] ?? null;
        $companyIdAfter = $this->fetchCompanyId($propertyValues);
        $pricePerKiloHandler = new PricePerKiloHandler();

        if ($companyIdBefore) {
            $pricePerKiloHandler->run($companyIdBefore);
        }

        if ($companyIdAfter && $companyIdAfter !== $companyIdBefore) {
            $pricePerKiloHandler->run($companyIdAfter);
        }
    }

    /**
     * @param  array  $propertyValues
     *
     * @return int|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function fetchCompanyId(array $propertyValues): ?int
    {
        $companyIdPropertyId = CompanyCurrencyRatesPropertyValuesTable::getPropertyId('COMPANY_ID');

        if (array_key_exists('COMPANY_ID', $propertyValues)) {
            return $propertyValues['COMPANY_ID'];
        } elseif (array_key_exists($companyIdPropertyId, $propertyValues)) {
            return current($propertyValues[$companyIdPropertyId])['VALUE'] ?? null;
        }

        return null;
    }
}
