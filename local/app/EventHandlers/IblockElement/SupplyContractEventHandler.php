<?php

namespace EventHandlers\IblockElement;

use CIBlockElement;
use EventHandlers\IblockElement\Interfaces\OnAfterSetPropertyValuesEventHandlerInterface;

class SupplyContractEventHandler implements OnAfterSetPropertyValuesEventHandlerInterface
{
    /**
     * @param $supplyContractId
     * @param $propertyValues
     */
    public function onAfterSetPropertyValues($supplyContractId, $propertyValues): void
    {
        $supplyContract = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => SUPPLY_CONTRACTS_IBLOCK_ID, 'ID' => $supplyContractId],
            false,
            false,
            ['ID', 'PROPERTY_CURRENCY', 'PROPERTY_SELLER', 'PROPERTY_BUYER', 'PROPERTY_COUNTER']
        )->Fetch();

        if (empty($supplyContract['PROPERTY_COUNTER_VALUE'])) {
            $lastSupplyContract = CIBlockElement::GetList(
                ['ASC' => 'PROPERTY_COUNTER'],
                [
                    'IBLOCK_ID'         => SUPPLY_CONTRACTS_IBLOCK_ID,
                    'PROPERTY_SELLER'   => $supplyContract['PROPERTY_SELLER_VALUE'],
                    'PROPERTY_BUYER'    => $supplyContract['PROPERTY_BUYER_VALUE'],
                    'PROPERTY_CURRENCY' => $supplyContract['PROPERTY_CURRENCY_VALUE'],
                    '!PROPERTY_COUNTER' => false,
                ],
                false,
                ['nTopCount' => 1],
                ['ID', 'PROPERTY_COUNTER']
            )->Fetch();

            if (!empty($lastSupplyContract)) {
                $counter = (int)$lastSupplyContract['PROPERTY_COUNTER_VALUE'];
                $counter++;
            } else {
                $counter = 1;
            }

            CIBlockElement::SetPropertyValuesEx(
                $supplyContract['ID'],
                SUPPLY_CONTRACTS_IBLOCK_ID,
                ['COUNTER' => $counter]
            );
        }
    }
}
