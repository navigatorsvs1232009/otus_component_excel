<?php

namespace Agents\Integration\MiningElement\PDM;

use Services\Integration\MiningElement\PDM\PDMConnection;
use Services\Integration\MiningElement\PDM\UnitDesignWorkImportService;
use Throwable;

class UnitDesignWorkImportAgent
{
    /**
     * @return string
     */
    public static function run(): string
    {
        try {
            (new UnitDesignWorkImportService(PDMConnection::getInstance()))->run();

        } catch (Throwable $e) {
            echo $e->getMessage().PHP_EOL.$e->getTraceAsString();
        }

        return __METHOD__.'();';
    }
}
