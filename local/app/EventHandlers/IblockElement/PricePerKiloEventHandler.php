<?php

namespace EventHandlers\IblockElement;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use CIBlockElement;
use CUser;
use EventHandlers\IblockElement\Interfaces\OnAfterSetPropertyValuesEventHandlerInterface;
use Models\SCM\PricePerKiloHistoryTable;
use Models\SCM\PricePerKiloPropertyValuesTable;
use Repositories\CompanyCurrencyRatesRepository;
use Repositories\PricePerKiloRepository;

class PricePerKiloEventHandler implements OnAfterSetPropertyValuesEventHandlerInterface
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
        $companyId = $this->fetchCompanyId($propertyValues);

        # нет привязки к компании => удаляем
        if (empty($companyId)) {
            CIBlockElement::Delete($elementId);
            return;
        }

        $supplierPrice = $this->fetchSupplierPrice($propertyValues);
        if (empty($companyId) || empty($supplierPrice)) {
            return;
        }

        $actualCurrencyRate = CompanyCurrencyRatesRepository::getActual($companyId, $supplierPrice['currency'], CompanyCurrencyRatesRepository::OPERATING_CURRENCY);
        $newOperatingPrice = number_format($supplierPrice['value'] * $actualCurrencyRate['RATE'], 2, '.', '').'|'.CompanyCurrencyRatesRepository::OPERATING_CURRENCY;
        CIBlockElement::SetPropertyValuesEx(
            $elementId,
            PRICE_PER_KILO_IBLOCK_ID,
            ['OPERATING_PRICE' => ['VALUE' => $newOperatingPrice]]
        );

        # если в истории запись отсутствует или отличается => добавим
        $pricePerKiloHistoryLastEntry = PricePerKiloRepository::getHistoryLastEntry($elementId);
        if (empty($pricePerKiloHistoryLastEntry)
            || $newOperatingPrice !==  $pricePerKiloHistoryLastEntry['OPERATING_PRICE']
            || "{$supplierPrice['value']}|{$supplierPrice['currency']}" !== $pricePerKiloHistoryLastEntry['SUPPLIER_PRICE']
        ) {
            PricePerKiloHistoryTable::add([
                'ID'              => $elementId,
                'MODIFIED_AT'     => new DateTime(),
                'MODIFIED_BY_ID'  => $GLOBALS['USER'] instanceof CUser ? $GLOBALS['USER']->GetID() : null,
                'SUPPLIER_PRICE'  => $supplierPrice['value'].'|'.$supplierPrice['currency'],
                'OPERATING_PRICE' => $newOperatingPrice,
                'CURRENCY_RATE'   => $actualCurrencyRate['RATE'],
            ]);
        }
    }

    /**
     * @param  array  $propertyValues
     *
     * @return array|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function fetchSupplierPrice(array $propertyValues): ?array
    {
        $supplierPricePropertyId = PricePerKiloPropertyValuesTable::getPropertyId('SUPPLIER_PRICE');

        if (array_key_exists('SUPPLIER_PRICE', $propertyValues)) {
            $supplierPrice = $propertyValues['SUPPLIER_PRICE'];
        } elseif (array_key_exists($supplierPricePropertyId, $propertyValues)) {
            $supplierPrice = current($propertyValues[$supplierPricePropertyId])['VALUE'] ?? null;
        }
        if (empty($supplierPrice)) {
            return null;
        }

        list($value, $currency) = explode('|', $supplierPrice);

        return [
            'value'    => (float) str_replace(',', '.', $value),
            'currency' => $currency,
        ];
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
        $companyIdPropertyId = PricePerKiloPropertyValuesTable::getPropertyId('COMPANY_ID');

        if (array_key_exists('COMPANY_ID', $propertyValues)) {
            return $propertyValues['COMPANY_ID'];
        } elseif (array_key_exists($companyIdPropertyId, $propertyValues)) {
            return current($propertyValues[$companyIdPropertyId])['VALUE'] ?? null;
        }

        return null;
    }
}
