<?php

namespace Agents\Integration\Me1C;

use Bitrix\Main\Config\Option;
use CAllAgent;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Miningelement\Logger;
use Services\Integration\Me1C\TransportationTimeExportService;
use Throwable;

class TransportTimeExportAgent
{
    /**
     * @param  int  $incotermsPlaceId
     * @param  int  $transportTypeId
     * @param  int  $supplyAreaId
     *
     * @return string
     */
    public static function runOnce(int $incotermsPlaceId, int $transportTypeId, int $supplyAreaId): ?string
    {
        $logger = new Logger('transport_time_export_to_1cme.log');

        try {
            (new TransportationTimeExportService(
                new HttpClient(),
                Option::get('integration', 'transport_time_export_to_1cme_url'),
                $logger
            ))->run($incotermsPlaceId, $transportTypeId, $supplyAreaId);

        } catch (GuzzleException $e) {
            $logger->error($e->getMessage());
            return __METHOD__."({$incotermsPlaceId}, {$transportTypeId}, {$supplyAreaId});";

        } catch (Throwable $e) {
            $logger->error($e->getMessage());
        }

        return null;
    }

    /**
     * @param  int  $incotermsPlaceId
     * @param  int  $transportTypeId
     * @param  int  $supplyAreaId
     * @param  int  $delay
     */
    public static function scheduleRunOnce(int $incotermsPlaceId, int $transportTypeId, int $supplyAreaId, int $delay = 0): void
    {
        $agentName = __CLASS__ . "::runOnce({$incotermsPlaceId}, {$transportTypeId}, {$supplyAreaId});";
        $nextExec = date_create("+{$delay}min")->format('d.m.Y H:i:s');

        if (empty($agent = CAllAgent::GetList([], ['NAME' => $agentName, 'RUNNING' => 'N'])->Fetch())) {
            CAllAgent::AddAgent(
                $agentName,
                '',
                'N',
                0,
                '',
                'Y',
                $nextExec
            );

        } else {
            CAllAgent::Update($agent['ID'], ['NEXT_EXEC' => $nextExec]);
        }
    }
}
