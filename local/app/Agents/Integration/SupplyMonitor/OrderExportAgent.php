<?php

namespace Agents\Integration\SupplyMonitor;

use Agents\Integration\Me1C\ScheduleRunOnceTrait;
use Bitrix\Main\Config\Option;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use Miningelement\Logger;
use Services\Integration\SupplyMonitor\OrderExportService;
use Throwable;

abstract class OrderExportAgent
{
    use ScheduleRunOnceTrait;

    /**
     * @param  int  $orderId
     *
     * @return string|null
     */
    public static function runOnce(int $orderId): ?string
    {
        try {
            (new OrderExportService(
                new HttpClient(),
                Option::get('integration', 'order_export_to_supply_monitor_url'),
                new Logger('order_export_to_supply_monitor.log')
            ))->run($orderId);

        } catch (RequestException $exception) {
            return __METHOD__."({$orderId});";

        } catch (Throwable $e) {
            //
        }

        return null;
    }
}
