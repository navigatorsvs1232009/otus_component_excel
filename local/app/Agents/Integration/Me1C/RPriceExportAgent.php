<?php

namespace Agents\Integration\Me1C;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use GuzzleHttp\Client as HttpClient;
use Miningelement\Logger;
use Services\Integration\Me1C\RPriceExportService;

Loader::includeModule('crm');

abstract class RPriceExportAgent
{
    use ScheduleRunOnceTrait;

    /**
     * @param  int  $rpriceId
     *
     * @return string
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     * @throws ArgumentException
     * @throws SystemException
     */
    public static function runOnce(int $rpriceId): string
    {
        $result = (new RPriceExportService(
            new HttpClient(),
            Option::get('me1c', 'rprice_export_url', ''),
            new Logger('rprice_export.log')
        ))->run($rpriceId);

        return $result ? '' : (__METHOD__ . "({$rpriceId});");
    }
}
