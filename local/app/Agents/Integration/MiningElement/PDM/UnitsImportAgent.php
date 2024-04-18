<?php

namespace Agents\Integration\MiningElement\PDM;

use Miningelement\Logger;
use Services\Infrastructure\ProductGroupsRefUpdateService;
use Services\Integration\MiningElement\PDM\ProductMaterialsImportService;
use Services\Integration\MiningElement\PDM\UnitAliasesImportService;
use Services\Integration\MiningElement\PDM\PDMConnection;
use Services\Integration\MiningElement\PDM\ProductKeysImportService;
use Services\Integration\MiningElement\PDM\UnitAssemblyNodesImportService;
use Services\Integration\MiningElement\PDM\UnitEtoImportService;
use Services\Integration\MiningElement\PDM\UnitsImportService;
use Throwable;

class UnitsImportAgent
{
    const DEFAULT_PERIOD = '-10 days';
    const UNITS_IMPORT_LOG_FILENAME = 'pdm/units_import.log';
    const UNITS_ALIAS_IMPORT_LOG_FILENAME = 'pdm/units_alias_import.log';

    /**
     * @param  string  $fromDt
     *
     * @return string
     */
    public static function run(string $fromDt = self::DEFAULT_PERIOD): string
    {
        try {
            $fromDt = date_create($fromDt) ?: date_create(self::DEFAULT_PERIOD);
            $beforeStartDt = date_create();
            $pdmConnection = PDMConnection::getInstance();

            (new ProductMaterialsImportService($pdmConnection))->run();
            (new ProductKeysImportService($pdmConnection))->run();
            (new UnitsImportService($pdmConnection, new Logger(self::UNITS_IMPORT_LOG_FILENAME)))->run($fromDt);
            (new UnitAliasesImportService($pdmConnection, new Logger(self::UNITS_ALIAS_IMPORT_LOG_FILENAME)))->run($fromDt);
            (new ProductGroupsRefUpdateService())->run();
            (new UnitEtoImportService($pdmConnection))->run();
            (new UnitAssemblyNodesImportService($pdmConnection))->run();

        } catch (Throwable $e) {
            echo $e->getMessage().PHP_EOL.$e->getTraceAsString();
            return sprintf("%s('%s');", __METHOD__, $fromDt->format(DATE_ISO8601));
        }

        return sprintf("%s('%s');", __METHOD__, $beforeStartDt->format(DATE_ISO8601));
    }
}
