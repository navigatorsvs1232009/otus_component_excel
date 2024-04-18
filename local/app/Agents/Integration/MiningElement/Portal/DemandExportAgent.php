<?php

namespace Agents\Integration\MiningElement\Portal;

use Bitrix\Crm\LeadTable as Demand;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use GuzzleHttp\Client as HttpClient;
use Miningelement\Logger;
use Models\SCM\DemandProductRowsTable;
use Agents\Integration\Me1C\ScheduleRunOnceTrait;
use Services\Integration\MiningElement\Portal\ProductDemandsExportService;
use Throwable;

Loader::includeModule('crm');

abstract class DemandExportAgent
{
    use ScheduleRunOnceTrait;

    /**
     * @param  int  $demandId
     *
     * @return string|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function runOnce(int $demandId): ?string
    {
        $demand = Demand::getByPrimary($demandId)->fetch();
        if (empty($demand)) {
            return null;
        }

        $externalProductDemands = self::getExternalProductDemands($demandId);
        if (empty($externalProductDemands)) {
            return null;
        }

        try {
            (new ProductDemandsExportService(
                new HttpClient(),
                Option::get('element', 'demand_export_to_me_crm_endpoint'),
                new Logger('demand_export_to_me_crm.log')
            ))->run(array_column($externalProductDemands, 'XML_ID'));

        } catch (Throwable $e) {
            return __METHOD__."({$demandId});";
        }

        return null;
    }

    /**
     * @param  int  $demandId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getExternalProductDemands(int $demandId): array
    {
        return DemandProductRowsTable::getList([
            'select' => [
                'ROW_ID',
                'XML_ID',
                'DEMAND_ID'     => 'ROW.OWNER_ID',
            ],
            'filter' => ['DEMAND_ID' => $demandId, '!=XML_ID' => false]
        ])->fetchAll() ?: [];
    }
}
