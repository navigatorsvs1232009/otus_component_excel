<?php

namespace Agents\Integration\MiningElement\Tooling;

use Agents\Integration\Me1C\ScheduleRunOnceTrait;
use Bitrix\Main\Config\Option;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Miningelement\Logger;
use Services\Integration\MiningElement\Tooling\ManufacturedToolingItemExportService;
use Services\Integration\MiningElement\Tooling\UsedToolingItemsExportService;
use Throwable;

class UsedToolingItemsExportAgent
{
    use ScheduleRunOnceTrait;

    /**
     * @param  int  $orderId
     *
     * @return string|null
     */
    public static function runOnce(int $orderId): ?string
    {
        $logger = new Logger('used_tooling_items_export.log');

        try {
            (new UsedToolingItemsExportService(
                new Client(),
                Option::get('integration', 'used_tooling_items_export_endpoint'),
                $logger
            ))->run($orderId);

            $logger->info("Exported for Order ID={$orderId}");

        } catch (GuzzleException $exception) {
            $logger->warning($exception->getMessage());

            return __METHOD__."($orderId);";

        } catch (Throwable $throwable) {
            $logger->error($throwable->getMessage());
        }

        return null;
    }
}
