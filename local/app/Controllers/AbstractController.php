<?php

namespace Controllers;

use Bitrix\Rest\RestException;
use CRestServer;

abstract class AbstractController
{
    /**
     * @param  array  $params
     * @param  array  $rules
     *
     * @throws RestException
     */
    protected static function checkParams(array $params, array $rules): void
    {
        foreach ((filter_var_array($params, $rules) ?? []) as $paramName => $value) {
            if (empty($value)) {
                throw new RestException(
                    "Mandatory param '{$paramName}' is invalid",
                    400,
                    CRestServer::STATUS_WRONG_REQUEST
                );
            }
        }
    }
}
