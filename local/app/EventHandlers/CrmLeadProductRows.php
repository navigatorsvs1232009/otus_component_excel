<?php

namespace EventHandlers;

use Services\Infrastructure\EntityChangesLoggingService;
use Services\Infrastructure\SCM\DemandProductRowsUpdateHandler;

class CrmLeadProductRows
{
    public static function onAfterSave($leadId, $productRows): void
    {
        EntityChangesLoggingService::run('demand_products', $leadId);

        (new DemandProductRowsUpdateHandler())->saveExtraFields($leadId, $productRows);
    }
}
