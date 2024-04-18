<?php

namespace Agents\Integration\MiningElement\PDM;

use Services\Integration\MiningElement\PDM\PDMConnection;
use Services\Integration\MiningElement\PDM\ProductSubOemImportService;
use Throwable;

class ProductSubOemImportAgent
{
    /**
     * @return string
     */
    public static function run(): string
    {
        try {
            (new ProductSubOemImportService(PDMConnection::getInstance()))->run();

        } catch (Throwable $e) {
            echo $e->getMessage().PHP_EOL.$e->getTraceAsString();
        }

        return __METHOD__.'();';
    }
}
