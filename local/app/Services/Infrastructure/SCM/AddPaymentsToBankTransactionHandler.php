<?php

namespace Services\Infrastructure\SCM;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CIBlockElement;
use Exception;
use Models\SCM\BankTransactionsPropertyValuesTable;
use Models\SCM\PaymentPropertyValuesTable;
use Services\Infrastructure\IblockElementNameHandler;

Loader::includeModule('crm');

/**
 * Class AddPaymentsToBankTransactionHandler
 *
 * @package Services\Infrastructure\SCM
 */
class AddPaymentsToBankTransactionHandler
{
    private CIBlockElement $iblockElement;

    /**
     * AddPaymentsToBankTransactionHandler constructor.
     */
    public function __construct()
    {
        $this->iblockElement = new CIBlockElement();
    }

    /**
     * @param  array  $paymentIds
     * @param  int|null  $bankTransactionId
     *
     * @return void
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function run(array $paymentIds, ?int $bankTransactionId): void
    {
        $bankTransaction = $this->getBankTransaction($bankTransactionId);

        # все платежи, которые уже есть и должны быть в БТ
        $bankTransactionPayments = $this->getPayments(array_filter($paymentIds), $bankTransactionId);
        if (empty($bankTransactionPayments)) {
            return;
        }

        $this->prepareBankTransaction($bankTransaction, $paymentIds, $bankTransactionPayments);
        $this->saveBankTransaction($bankTransaction);
        $this->updatePayments($bankTransactionPayments, $paymentIds, $bankTransaction['ID']);
    }

    /**
     * @param  array  $bankTransaction
     * @param  array  $paymentIds
     * @param  array  $payments
     *
     * @throws Exception
     */
    private function prepareBankTransaction(array &$bankTransaction, array $paymentIds, array $payments): void
    {
        $bankTransaction['AMOUNT'] = 0;
        foreach ($payments as $payment) {

            if (!in_array($payment['ID'], $paymentIds)) {
                continue;
            }

            if (isset($bankTransaction['ID']) && $payment['BANK_TRANSACTION_ID'] && $payment['BANK_TRANSACTION_ID'] != $bankTransaction['ID']) {
                throw new Exception("Payment ({$payment['ID']}) is already linked to another bank transaction");
            }

            list($paymentAmount, $paymentCurrency) = explode('|', $payment['AMOUNT']);

            if (empty($bankTransaction['CURRENCY'])) {
                $bankTransaction['CURRENCY'] = $paymentCurrency;
            }

            $bankTransaction['AMOUNT'] += (float) str_replace(',', '.', $paymentAmount);

            if (isset($bankTransaction['PAYER_COMPANY_ID']) && $bankTransaction['PAYER_COMPANY_ID'] != $payment['PAYER_COMPANY_ID']) {
                throw new Exception("Payment ({$payment['ID']}) and Bank transaction payer companies are different");
            }
            $bankTransaction['PAYER_COMPANY_ID'] = $payment['PAYER_COMPANY_ID'];

            if (isset($bankTransaction['PAYEE_COMPANY_ID']) && $bankTransaction['PAYEE_COMPANY_ID'] != $payment['PAYEE_COMPANY_ID']) {
                throw new Exception("Payment ({$payment['ID']}) and Bank transaction payee companies are different");
            }
            $bankTransaction['PAYEE_COMPANY_ID'] = $payment['PAYEE_COMPANY_ID'];
        }
    }

    /**
     * @param  array  $bankTransaction
     *
     * @throws Exception
     */
    private function saveBankTransaction(array &$bankTransaction): void
    {
        if (empty($bankTransaction['ID'])) {
            $bankTransaction['ID'] = $this->iblockElement->Add([
                'NAME'      => '-',
                'IBLOCK_ID' => BANK_TRANSACTIONS_IBLOCK_ID,
                'PROPERTY_VALUES' => [
                    'AMOUNT'           => $bankTransaction['AMOUNT'],
                    'CURRENCY'         => $bankTransaction['CURRENCY'],
                    'PAYER_COMPANY_ID' => $bankTransaction['PAYER_COMPANY_ID'],
                    'PAYEE_COMPANY_ID' => $bankTransaction['PAYEE_COMPANY_ID'],
                ]
            ]);
            if (empty($bankTransaction['ID'])) {
                throw new Exception($this->iblockElement->LAST_ERROR);
            }

            IblockElementNameHandler::handle($bankTransaction['ID']);

        } else {
            CIBlockElement::SetPropertyValuesEx(
                $bankTransaction['ID'],
                BANK_TRANSACTIONS_IBLOCK_ID,
                [
                    'AMOUNT' => $bankTransaction['AMOUNT'],
                ]
            );
        }
    }

    /**
     * @param  array  $paymentIds
     * @param  int|null  $bankTransactionId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getPayments(array $paymentIds, ?int $bankTransactionId): array
    {
        if ($bankTransactionId) {
            $filter['=BANK_TRANSACTION_ID'] = $bankTransactionId;
        }

        if ($paymentIds) {
            $filter['LOGIC'] = 'OR';
            $filter['=ID'] = $paymentIds;
        }

        if (empty($filter)) {
            return [];
        }

        return PaymentPropertyValuesTable::getList([
            'select' => ['ID' => 'IBLOCK_ELEMENT_ID', '*'],
            'filter' => $filter,
        ])->fetchAll();
    }

    /**
     * @param  int|null  $bankTransactionId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getBankTransaction(?int $bankTransactionId): array
    {
        if (empty($bankTransactionId)) {
            return [];
        }

        $bankTransaction = BankTransactionsPropertyValuesTable::getList([
            'select' => ['ID' => 'IBLOCK_ELEMENT_ID', 'AMOUNT', 'AMOUNT_PAID', 'CURRENCY', 'PAYER_COMPANY_ID', 'PAYEE_COMPANY_ID'],
            'filter' => ['ID' => $bankTransactionId]
        ])->fetch();
        if (empty($bankTransaction)) {
            throw new Exception("Bank transaction ({$bankTransactionId}) not found");
        }

        return $bankTransaction;
    }

    /**
     * @param  array  $bankTransactionPayments
     * @param  array  $paymentIds
     * @param  int  $bankTransactionId
     */
    private function updatePayments(array $bankTransactionPayments, array $paymentIds, int $bankTransactionId): void
    {
        foreach ($bankTransactionPayments as $payment) {
            $paymentLinkedToBankTransaction = in_array($payment['ID'], $paymentIds);
            CIBlockElement::SetPropertyValuesEx(
                $payment['ID'],
                PAYMENTS_IBLOCK_ID,
                ['BANK_TRANSACTION_ID' => $paymentLinkedToBankTransaction ? $bankTransactionId : null]
            );

            $_ = [];
            $this->iblockElement->Update($payment['ID'], $_);
        }
    }
}
