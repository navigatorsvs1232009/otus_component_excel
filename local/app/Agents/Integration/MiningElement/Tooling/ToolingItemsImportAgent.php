<?php

namespace Agents\Integration\MiningElement\Tooling;

use Bitrix\Main\Config\Option;
use GuzzleHttp\Client;
use Miningelement\Logger;
use Services\Integration\MiningElement\Tooling\ToolingItemsImportService;
use Throwable;

class ToolingItemsImportAgent
{
    /**
     * @return void
     */
    public static function run(): string
    {
        $logger = new Logger('tooling_items_import.log');

        try {
            (new ToolingItemsImportService(
                new Client(),
                Option::get('integration','tooling_items_import_endpoint'),
                Option::get('integration','tooling_api_key'),
                $logger
            ))->run();

        } catch (Throwable $exception) {
            $logger->error($exception->getMessage());
        }

        return __METHOD__.'();';
    }
}
