<?php

namespace Controllers\Rest\SCM;

use Bitrix\Rest\RestException;
use Controllers\AbstractController;
use CRestServer;
use DomainException;
use Miningelement\Logger;
use Services\Infrastructure\SCM\DemandImportService;
use Throwable;

class DemandController extends AbstractController
{
    /**
     * @param  array  $params
     *
     * @return array
     * @throws RestException
     */
    public static function import(array $params): array
    {
        $logger = new Logger('demand_import.log');

        try {
            return (new DemandImportService($logger))->run($params);
        } catch (DomainException $e) {
            $logger->debug(json_encode($params));
            $logger->error($e->getMessage());
            throw new RestException($e->getMessage(), 400, CRestServer::STATUS_WRONG_REQUEST);
        } catch (Throwable $e) {
            $logger->debug(json_encode($params));
            $logger->error($e->getMessage());
            throw new RestException('Internal server error', 500, CRestServer::STATUS_INTERNAL);
        }
    }
}
