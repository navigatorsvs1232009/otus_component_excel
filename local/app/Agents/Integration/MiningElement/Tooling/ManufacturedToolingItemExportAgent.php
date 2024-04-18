<?php

namespace Agents\Integration\MiningElement\Tooling;

use Agents\Integration\Me1C\ScheduleRunOnceTrait;
use Bitrix\Main\Config\Option;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Miningelement\Logger;
use Services\Integration\MiningElement\Tooling\ManufacturedToolingItemExportService;
use Throwable;

class ManufacturedToolingItemExportAgent
{
    use ScheduleRunOnceTrait;

    /**
     * @param  int  $toolingProductId
     *
     * @return string|null
     */
    public static function runOnce(int $toolingProductId): ?string
    {
        $logger = new Logger('manufactured_tooling_item_export.log');

        try {
            (new ManufacturedToolingItemExportService(
                new Client(),
                Option::get('integration', 'manufactured_tooling_item_export_endpoint'),
                Option::get('integration', 'tooling_api_key'),
                $logger
            ))->run($toolingProductId);

            $logger->info("Exported tooling product ID={$toolingProductId}");

        } catch (GuzzleException $exception) {
            $logger->warning($exception->getMessage());

            return __METHOD__."($toolingProductId);";

        } catch (Throwable $throwable) {
            $logger->error($throwable->getMessage());
        }

        return null;
    }
}
