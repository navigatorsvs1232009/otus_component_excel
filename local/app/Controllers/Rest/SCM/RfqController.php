<?php

namespace Controllers\Rest\SCM;

use Bitrix\Rest\RestException;
use Controllers\AbstractController;
use CRestServer;
use Miningelement\Logger;
use Services\Rest\SCM\RfqImportRestService;
use Throwable;

class RfqController extends AbstractController
{
    const MANDATORY_DATA_FIELDS = [
        'rfqGuid'             => FILTER_SANITIZE_STRING,
        'supplierCompanyGuid' => FILTER_SANITIZE_STRING,
        'currency'            => FILTER_SANITIZE_STRING
    ];

    /**
     * @param  array  $params
     *
     * @return array
     * @throws RestException
     */
    public static function import(array $params): array
    {
        self::checkParams($params, self::MANDATORY_DATA_FIELDS);

        try {
            return (new RfqImportRestService(new Logger('rfq_import.log')))->run($params);
        } catch (RestException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new RestException('Internal server error', 500, CRestServer::STATUS_INTERNAL);
        }
    }
}
