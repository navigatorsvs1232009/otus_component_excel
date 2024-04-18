<?php

namespace Agents\Integration\Me1C;

use Bitrix\Main\Config\Option;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Miningelement\Logger;
use Services\Integration\Me1C\IncotermsPlaceExportService;
use Throwable;

class IncotermsPlaceExportAgent
{
    use ScheduleRunOnceTrait;

    /**
     * @param  int  $incotermsPlaceId
     *
     * @return string|void
     */
    public static function runOnce(int $incotermsPlaceId)
    {
        $logger = new Logger('incoterms_place_export_to_1cme.log');

        try {
            (new IncotermsPlaceExportService(
                new HttpClient(),
                Option::get('me1c', 'incoterms_place_export_url', '')
            ))->run($incotermsPlaceId);

        } catch (GuzzleException $e) {
            $logger->error($e->getMessage());
            return __METHOD__."({$incotermsPlaceId});";

        } catch (Throwable $e) {
            $logger->error($e->getMessage());
        }
    }
}
