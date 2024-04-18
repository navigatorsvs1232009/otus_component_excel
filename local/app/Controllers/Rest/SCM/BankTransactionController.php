<?php

namespace Controllers\Rest\SCM;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;
use Bitrix\Rest\RestException;
use CIBlockElement;
use Controllers\AbstractController;
use CRestServer;
use Exception;
use Miningelement\Logger;
use Models\SCM\BankTransactionsPropertyValuesTable;
use Models\SCM\PaymentPropertyValuesTable;
use Throwable;

class BankTransactionController extends AbstractController
{
    const IMPORT_MANDATORY_PARAMS = [
            'documentNumber' => FILTER_SANITIZE_STRING,
            'paymentIds'     => ['flags' => FILTER_FORCE_ARRAY],
        ];

    /**
     * @param  array  $params
     *
     * @return array
     * @throws RestException
     */
    public static function import(array $params): array
    {
        self::checkParams($params, self::IMPORT_MANDATORY_PARAMS);

        $logger = new Logger('bank_transaction_import.log');
        $logger->debug("Incoming: \n".json_encode($params, JSON_UNESCAPED_UNICODE));
        $iblockElement = new CIBlockElement();

        try {

            Application::getConnection()->startTransaction();

            $date = $params['date'] ? Date::createFromPhp(date_create($params['date'])) : null;
            $bankTransaction = self::getBankTransaction($params['documentNumber'], $params['id']);

            if ($bankTransaction) {
                # сохраним все виртуальные платежи
                $payments = self::getPayments($bankTransaction['ID']);
                foreach ($payments as $payment) {
                    if (empty($payment['APPROVED_BY_FINANCIAL_MANAGER']) && !in_array($payment['ID'], $params['paymentIds'])) {
                        $params['paymentIds'][] = $payment['ID'];
                    }
                }
            }

            $bankTransactionFieldValues = [
                'NAME'            => $params['documentNumber'],
                'XML_ID'          => $params['documentNumber'],
                'IBLOCK_ID'       => BANK_TRANSACTIONS_IBLOCK_ID,
                'ACTIVE_FROM'     => $date,
                'PREVIEW_TEXT'    => $params['comment'],
                'PROPERTY_VALUES' => [
                    'PAYER_COMPANY_ID' => $params['payerCompanyId'],
                    'PAYEE_COMPANY_ID' => $params['payeeCompanyId'],
                    'PAYMENT_IDS'      => ['VALUE' => $params['paymentIds']],
                    'AMOUNT_PAID'      => $params['amountPaid'],
                    'CURRENCY'         => $params['currency']
                ],
            ];

            if (empty($bankTransaction)) {
                $bankTransactionId = $iblockElement->Add($bankTransactionFieldValues);
            } else {
                $bankTransactionId = $bankTransaction['ID'];
                $iblockElement->Update($bankTransaction['ID'], $bankTransactionFieldValues);
            }

            if ($iblockElement->LAST_ERROR) {
                throw new Exception($iblockElement->LAST_ERROR);
            }

            Application::getConnection()->commitTransaction();

            return [
                'bankTransactionId' => $bankTransactionId,
            ];

        } catch (Exception $e) {
            throw new RestException($e->getMessage(), 400, CRestServer::STATUS_WRONG_REQUEST);
            $logger->error($e->getMessage());
        } catch (Throwable $e) {
            throw new RestException('Internal server error', 500, CRestServer::STATUS_INTERNAL);
            $logger->error($e->getMessage());
        }
    }

    /**
     * @param  string  $xmlId
     * @param  int|null  $bankTransactionId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function getBankTransaction(string $xmlId, ?int $bankTransactionId): array
    {
        $filter['=XML_ID'] = $xmlId;
        if (isset($bankTransactionId)) {
            $filter['LOGIC'] = 'OR';
            $filter['=ID'] = $bankTransactionId;
        }

        return BankTransactionsPropertyValuesTable::getList([
            'select' => ['ID' => 'IBLOCK_ELEMENT_ID', 'XML_ID' => 'ELEMENT.XML_ID'],
            'filter' => $filter,
        ])->fetch() ?: [];
    }

    /**
     * @param  int  $bankTransactionId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function getPayments(int $bankTransactionId): array
    {
        return PaymentPropertyValuesTable::getList([
            'select' => [
                'ID' => 'IBLOCK_ELEMENT_ID',
                'PAYER_COMPANY_ID',
                'APPROVED_BY_FINANCIAL_MANAGER',
            ],
            'filter' => ['=BANK_TRANSACTION_ID' => $bankTransactionId],
        ])->fetchAll();
    }
}
