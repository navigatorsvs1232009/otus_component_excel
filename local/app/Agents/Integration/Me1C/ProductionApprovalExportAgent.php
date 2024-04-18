<?php

namespace Agents\Integration\Me1C;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CAllAgent;
use GuzzleHttp\Client as HttpClient;
use Miningelement\Logger;
use Models\SCM\ProductionApprovalTable;
use Services\Integration\Me1C\ProductionApprovalExportService;

class ProductionApprovalExportAgent
{
    /**
     * @param  int  $productionApprovalId
     *
     * @return string|null
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     */
    public static function runOnce(int $productionApprovalId): ?string
    {
        $result = (new ProductionApprovalExportService(
            new HttpClient(),
            Option::get('me1c', 'production_approval_export_url'),
            new Logger('production_approval_export_to_1cme.log')
        ))->run($productionApprovalId);

        return !$result ? __METHOD__."({$productionApprovalId});" : null;
    }

    /**
     * @param  int  $id
     * @param  int  $delay
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function scheduleRunOnce(int $id, int $delay = 0): void
    {
        $productionApproval = ProductionApprovalTable::getById($id)->fetch();
        if (empty($productionApproval) || substr($productionApproval['XML_ID'], 0, 4) !== '0000') {
            return;
        }

        $agentName = __CLASS__ . "::runOnce({$id});";
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
            CAllAgent::Update($agent['ID'], [
                'NEXT_EXEC' => $nextExec
            ]);
        }
    }
}
