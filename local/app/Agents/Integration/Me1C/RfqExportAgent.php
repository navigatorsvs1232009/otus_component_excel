<?php

namespace Agents\Integration\Me1C;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use GuzzleHttp\Client as HttpClient;
use Miningelement\Logger;
use Services\Integration\Me1C\RfqExportService;
use Throwable;

Loader::includeModule('crm');

abstract class RfqExportAgent
{
    use ScheduleRunOnceTrait;

    /**
     * @param  int  $rfqId
     *
     * @return string|null
     */
    public static function runOnce(int $rfqId): ?string
    {
        try {
            (new RfqExportService(
                new HttpClient(),
                Option::get('me1c', 'rfq_export_url', ''),
                new Logger('rfq_export.log')
            ))->run($rfqId);

        } catch (Throwable $e) {
            return __METHOD__."({$rfqId});";
        }

        return null;
    }
}
