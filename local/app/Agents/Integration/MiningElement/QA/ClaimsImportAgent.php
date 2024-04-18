<?php

namespace Agents\Integration\MiningElement\QA;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use GuzzleHttp\Client as HttpClient;
use Miningelement\Logger;
use Services\Integration\MiningElement\QA\ClaimsImportService;

abstract class ClaimsImportAgent
{
    /**
     * @param  string|null  $fromDatetime
     * @param  bool  $sendNotification
     *
     * @return string|null
     */
    public static function run(?string $fromDatetime = null, bool $sendNotification = false): ?string
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
                Option::get('integration', 'claims_import_endpoint'),
                Option::get('integration','qa_api_key'),
                new Logger('claims_import.log'),
                'C'
            ))->run($timestamp, $sendNotification);

            $fromDatetime = date_create()->format('d.m.Y H:i:s');

        } catch (\Throwable) {
            //
        }

        $sendNotification = $sendNotification ? 'true' : 'false';

        return __METHOD__."('$fromDatetime', $sendNotification);";
    }
}
