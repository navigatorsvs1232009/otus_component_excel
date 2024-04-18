<?php

namespace Agents\Integration\CBRF;

use Bitrix\Main\Config\Option;
use GuzzleHttp\Client;
use Miningelement\Logger;
use Services\Integration\CBRF\CurrencyRatesImportService;
use Throwable;

class CurrencyRatesImportAgent
{
    public static function run(): string
    {
        $logger = new Logger('cbr_currency_rates_import.log');

        try {
            (new CurrencyRatesImportService(
                new Client(),
                Option::get('integration', 'cbr_currency_rates_endpoint')
            ))->run();

        } catch (Throwable $throwable) {
            $logger->error($throwable->getMessage());
        }

        return __METHOD__ . "();";
    }
}
