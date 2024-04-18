<?php

namespace EventHandlers\IblockElement;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use EventHandlers\IblockElement\Interfaces\OnAfterSetPropertyValuesEventHandlerInterface;
use Models\SCM\BankTransactionsPropertyValuesTable;
use Services\Infrastructure\SCM\AddPaymentsToBankTransactionHandler;

class BankTransactionEventHandler implements OnAfterSetPropertyValuesEventHandlerInterface
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
        $paymentsPropertyId = BankTransactionsPropertyValuesTable::getPropertyId('PAYMENT_IDS');
        if (array_key_exists('PAYMENT_IDS', $propertyValues) || array_key_exists($paymentsPropertyId, $propertyValues)) {
            $bankTransaction = BankTransactionsPropertyValuesTable::getByPrimary($elementId, ['select' => ['IBLOCK_ELEMENT_ID', 'PAYMENT_IDS']])->fetch();
            (new AddPaymentsToBankTransactionHandler())->run($bankTransaction['PAYMENT_IDS'], $bankTransaction['IBLOCK_ELEMENT_ID']);
        }
    }
}
