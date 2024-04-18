<?php

namespace Agents\Integration\MiningElement\Portal;

use Agents\Integration\Me1C\ScheduleRunOnceTrait;
use Bitrix\Main\Config\Option;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Miningelement\Logger;
use Services\Integration\MiningElement\Portal\DesignWorkExportService;
use Throwable;

class DesignWorkExportAgent
{
    use ScheduleRunOnceTrait;

    /**
     * @param  int  $productRowId
     *
     * @return string|null
     */
    public static function runOnce(int $productRowId): ?string
    {
        $logger = new Logger('design_work_export.log');

        try {
            (new DesignWorkExportService(
                new Client(),
                Option::get('element', 'portal_lists_rest_endpoint')
            ))->run($productRowId);

            return null;

        } catch (RequestException $e) {
            $logger->error($e->getMessage());
            if ($e->getCode() == 400 || $e->getCode() == 404) {
                return null;
            }

        } catch (Throwable $e) {
            $logger->error($e->getMessage());
        }

        return __METHOD__."({$productRowId});";
    }
}
