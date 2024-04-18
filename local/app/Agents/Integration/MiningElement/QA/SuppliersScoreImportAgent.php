<?php

namespace Agents\Integration\MiningElement\QA;

use Bitrix\Main\Config\Option;
use GuzzleHttp\Client;
use Miningelement\Logger;
use Services\Integration\MiningElement\QA\SuppliersScoreImportService;
use Throwable;

abstract class SuppliersScoreImportAgent
{
    /**
     * @return string
     */
    public static function run(): string
    {
        $logger = new Logger('supplier_score_import.log');

        try {
            (new SuppliersScoreImportService(
                new Client(),
                Option::get('integration', 'companies_score_import_endpoint'),
                Option::get('integration', 'qa_api_key'),
            ))->run();

        } catch (Throwable $e) {
            $logger->error($e->getMessage());
        }

        return __METHOD__.'();';
    }
}
