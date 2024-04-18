<?php

namespace Agents\Integration\SupplyMonitor;

use Bitrix\Main\Config\Option;
use GuzzleHttp\Client as HttpClient;
use Miningelement\Logger;
use Services\Integration\SupplyMonitor\DemandProductRowsExportService;
use Throwable;

abstract class DemandProductRowsExportAgent
{
    /**
     * @return string
     */
    public static function run(): string
    {
        try {
            (new DemandProductRowsExportService(
                new HttpClient(),
                Option::get('integration', 'demand_product_rows_export_to_supply_monitor_url'),
                new Logger('demand_product_rows_export_to_supply_monitor.log')
            ))->run();

        } catch (Throwable $e) {
            //
        }

        return __METHOD__."();";
    }
}
