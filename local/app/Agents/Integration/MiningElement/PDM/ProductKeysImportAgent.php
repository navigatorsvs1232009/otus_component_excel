<?php

namespace Agents\Integration\MiningElement\PDM;

use Services\DummyLogger;
use Services\Integration\MiningElement\PDM\PDMConnection;
use Services\Integration\MiningElement\PDM\ProductKeysImportService;
use Throwable;

class ProductKeysImportAgent
{
    /**
     * @return string
     */
    public static function run(): string
    {
        try {
            (new ProductKeysImportService(PDMConnection::getInstance()))->run();

        } catch (Throwable $e) {
            echo $e->getMessage().PHP_EOL.$e->getTraceAsString();
        }

        return __METHOD__.'();';
    }
}
