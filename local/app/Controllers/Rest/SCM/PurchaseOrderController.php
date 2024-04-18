<?php

namespace Controllers\Rest\SCM;

use Bitrix\Crm\DealTable as PurchaseOrder;
use Bitrix\Crm\LeadTable as Demand;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Rest\RestException;
use Controllers\AbstractController;
use CRestServer;
use Miningelement\Logger;
use Services\Rest\SCM\ProcurementCreateRestService;
use Services\Rest\SCM\PurchaseOrderCreateRestService;
use Services\Rest\SCM\PurchaseOrderUpdateRestService;
use Throwable;

class PurchaseOrderController extends AbstractController
{
    const CREATE_MANDATORY_PARAMS = [
        'purchaseOrderGuid'   => FILTER_SANITIZE_STRING,
        'purchaseOrderNumber' => FILTER_SANITIZE_STRING,
        'responsibleUser'     => FILTER_VALIDATE_EMAIL,
        'contractors'         => ['flags' => FILTER_FORCE_ARRAY],
        'currency'            => FILTER_SANITIZE_STRING,
        'productItems'        => ['flags' => FILTER_FORCE_ARRAY],
    ];

    const UPDATE_MANDATORY_PARAMS = [
        'purchaseOrderGuid' => FILTER_SANITIZE_STRING,
        'productItems'      => ['flags' => FILTER_FORCE_ARRAY],
    ];

    /**
     * @param  array  $params
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws RestException
     * @throws SystemException
     */
    public static function update(array $params): array
    {
        self::checkParams($params, self::UPDATE_MANDATORY_PARAMS);

        if (empty($purchaseOrder = self::getPurchaseOrder($params['purchaseOrderGuid']))) {
            throw new RestException(
                "Purchase order not found",
                404,
                CRestServer::STATUS_NOT_FOUND
            );
        }

        if (empty($demand = self::getDemand($params['purchaseOrderGuid']))) {
            throw new RestException(
                "Demand of purchase order not found",
                500,
                CRestServer::STATUS_INTERNAL
            );
        }

        $logger = new Logger('purchase_order_update.log');
        $logger->debug("received params: \n".json_encode($params, JSON_UNESCAPED_SLASHES));

        return (new PurchaseOrderUpdateRestService())->run($demand, $purchaseOrder, $params);
    }

    /**
     * @param  array  $params
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws RestException
     * @throws SystemException
     * @throws Throwable
     * @throws SqlQueryException
     * @throws ObjectException
     */
    public static function create(array $params): array
    {
        self::checkParams($params, self::CREATE_MANDATORY_PARAMS);

        if (self::getPurchaseOrder($params['purchaseOrderGuid'], $params['purchaseOrderNumber'])) {
            throw new RestException(
                "Purchase order already exists",
                403,
                CRestServer::STATUS_FORBIDDEN
            );
        }

        $logger = new Logger('purchase_order_create.log');
        $logger->debug("received params: \n".json_encode($params, JSON_UNESCAPED_SLASHES));

        return (new PurchaseOrderCreateRestService())->run($params);
    }

    /**
     * @param  string  $purchaseOrderGuid
     * @param  string|null  $purchaseOrderExternalNumber
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected static function getPurchaseOrder(string $purchaseOrderGuid, ?string $purchaseOrderExternalNumber = null): array
    {
        $filter['UF_XML_ID'] = $purchaseOrderGuid;
        if (!empty($purchaseOrderExternalNumber)) {
            $filter['LOGIC'] = 'OR';
            $filter['UF_EXTERNAL_NUMBER'] = $purchaseOrderExternalNumber;
        }

        return PurchaseOrder::getList([
            'select' => ['ID', 'ASSIGNED_BY_ID', 'TITLE'],
            'filter' => $filter
        ])->fetch() ?: [];
    }

    /**
     * @param  string  $purchaseOrderGuid
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected static function getDemand(string $purchaseOrderGuid): array
    {
        return Demand::getList([
            'select' => ['ID'],
            'filter' => ['UF_XML_ID' => $purchaseOrderGuid]
        ])->fetch() ?: [];
    }
}
