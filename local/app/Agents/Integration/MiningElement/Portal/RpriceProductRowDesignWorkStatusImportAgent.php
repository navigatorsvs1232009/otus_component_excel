<?php

namespace Agents\Integration\MiningElement\Portal;

use Bitrix\Main\Config\Option;
use CAllAgent;
use GuzzleHttp\Client;
use Miningelement\Logger;
use Services\Integration\MiningElement\Portal\RpriceProductRowDesignWorkStatusImportService;
use Throwable;

class RpriceProductRowDesignWorkStatusImportAgent
{
    /**
     * @param  int  $rpriceProductRowId
     *
     * @return string|null
     */
    public static function run(int $rpriceProductRowId): ?string
    {
        $logger = new Logger('rprice_product_row_design_work_import.log');

        try {
            $isFinished = (new RpriceProductRowDesignWorkStatusImportService(
                new Client(),
                Option::get('element', 'portal_lists_rest_endpoint')
            ))->run($rpriceProductRowId);

            if ($isFinished) {
                return null;
            }

        } catch (Throwable $e) {
            $logger->error($e->getMessage());
        }

        return __METHOD__."($rpriceProductRowId);";
    }

    /**
     * @param  int  $rpriceProductRowId
     */
    public static function scheduleRun(int $rpriceProductRowId): void
    {
        $agentName = __CLASS__ . "::run($rpriceProductRowId);";
        $nextExec = date_create("+3 hours")->format('d.m.Y H:i:s');

        CAllAgent::AddAgent(
            $agentName,
            '',
            'N',
            12000,
            $nextExec,
            'Y',
            $nextExec
        );
    }
}
