<?php

namespace Agents\Integration\MiningElement\QA;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use GuzzleHttp\Client as HttpClient;
use Miningelement\Logger;
use Services\Integration\MiningElement\QA\ClaimsImportService;

abstract class IncidentsImportAgent
{
    /**
     * @param  string|null  $fromDatetime
     *
     * @return string|null
     */
    public static function run(?string $fromDatetime = null): ?string
    {
        $timestamp = null;
        if (!empty($fromDatetime)) {
            try {
                $timestamp = new DateTime($fromDatetime);
            } catch (\Throwable) {
                //
            }
        }

        try {
            (new ClaimsImportService(
                new HttpClient(['verify' => false]),
                Option::get('integration', 'incidents_import_endpoint'),
                Option::get('integration','qa_api_key'),
                new Logger('incidents_import.log'),
                'I'
            ))->run($timestamp, true);

            $fromDatetime = date_create()->format('d.m.Y H:i:s');

        } catch (\Throwable) {
            //
        }

        return __METHOD__."('$fromDatetime');";
    }
}
