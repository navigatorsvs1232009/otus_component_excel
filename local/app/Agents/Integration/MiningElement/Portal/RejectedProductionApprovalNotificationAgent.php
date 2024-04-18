<?php

namespace Agents\Integration\MiningElement\Portal;

use Agents\Integration\Me1C\ScheduleRunOnceTrait;
use Bitrix\Main\Config\Option;
use GuzzleHttp\Client;
use Miningelement\Logger;
use Services\Integration\MiningElement\Portal\RejectedProductionApprovalNotificationService;
use Throwable;

class RejectedProductionApprovalNotificationAgent
{
    use ScheduleRunOnceTrait;

    /**
     * @param  int  $productionApprovalId
     */
    public static function runOnce(int $productionApprovalId): void
    {
        $logger = new Logger('rejected_production_approval_notification.log');

        try {
            (new RejectedProductionApprovalNotificationService(
                new Client(),
                Option::get('integration', 'portal_rest_endpoint')
            ))->run($productionApprovalId);

        } catch (Throwable $e) {
            $logger->error($e->getMessage());
        }
    }
}
