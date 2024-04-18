<?php

namespace Agents\Integration\Me1C;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use DomainException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use Miningelement\Logger;
use Models\SCM\PaymentPropertyValuesTable;
use Services\Integration\Me1C\PaymentExportService;
use Throwable;

Loader::includeModule('crm');

abstract class PaymentExportAgent
{
    use ScheduleRunOnceTrait;

    /**
     * @param  int  $paymentId
     *
     * @return string|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function runOnce(int $paymentId): ?string
    {
        $payment = PaymentPropertyValuesTable::getByPrimary($paymentId, [
            'select' => [
                '*',
                'PURCHASE_ORDER_XML_ID' => 'PURCHASE_ORDER.UF_XML_ID',
                'STATUS_XML_ID'         => 'STATUS_REF.XML_ID',
            ],
        ])->fetch();

        if (empty($payment)
            || empty($payment['PURCHASE_ORDER_ID']
            || empty($payment['APPROVED_BY_FINANCIAL_MANAGER']))
            || $payment['PAYER_COMPANY_ID'] != ME_COMPANY_ID
            || $payment['STATUS_XML'] === 'PAID'
        ) {
            return null;
        }

        try {
            (new PaymentExportService(
                new HttpClient(),
                Option::get('me1c', 'payment_export_url'),
                new Logger('payments_export.log')
            ))->run($paymentId);

            return null;

        } catch (RequestException $e) {
            if ($e->getCode() == 400) {
                return null;
            }

        } catch (DomainException $e) {
            return null;

        } catch (Throwable $e) {
            //
        }

        return __METHOD__ . "({$paymentId});";
    }
}
