<?php

namespace Controllers\Rest\SCM;

use Bitrix\Rest\RestException;
use Controllers\AbstractController;
use CRestServer;
use DomainException;
use Miningelement\Logger;
use Services\Rest\SCM\ProductionApprovalImportService;
use Throwable;

class ProductionApprovalController extends AbstractController
{
    const IMPORT_MANDATORY_PARAMS = [
        'clientTitle'  => FILTER_SANITIZE_STRING,
        'orderId'      => FILTER_SANITIZE_STRING,
        'dealId'       => FILTER_SANITIZE_STRING,
        'productItems' => ['flags' => FILTER_FORCE_ARRAY],
    ];

    /**
     * @param  array  $params
     *
     * @return array
     * @throws RestException
     */
    public static function import(array $params): array
    {
        self::checkParams($params, self::IMPORT_MANDATORY_PARAMS);
        $logger = new Logger('production_approval_import.log');

        $operationCode = substr(md5(microtime().$params['orderId']), 0, 4);
        $logger->debug("[{$operationCode}] Received: \n".json_encode($params, JSON_UNESCAPED_UNICODE));

        try {
            $response = (new ProductionApprovalImportService())->run($params);
        } catch (DomainException $e) {
            throw new RestException($e->getMessage(), 400, CRestServer::STATUS_WRONG_REQUEST);
        } catch (Throwable $e) {
            throw new RestException('Internal server error', 500, CRestServer::STATUS_INTERNAL);
        }

        $logger->notice("[{$operationCode}] finished");

        return $response;
    }
}
