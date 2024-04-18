<?php

namespace Controllers\Rest\SCM;

use Bitrix\Rest\RestException;
use Miningelement\Logger;
use Services\Rest\SCM\FinalPriceImportRestService;

class FinalPriceController
{
    public static function import(array $params)
    {
        try {
            return (new FinalPriceImportRestService(new Logger('final_price_import.log')))->run($params);

        } catch (DomainException $e) {
            throw new RestException($e->getMessage(), 400, CRestServer::STATUS_WRONG_REQUEST);

        } catch (Throwable $e) {
            throw new RestException('Internal server error', 500, CRestServer::STATUS_INTERNAL);
        }
    }
}
