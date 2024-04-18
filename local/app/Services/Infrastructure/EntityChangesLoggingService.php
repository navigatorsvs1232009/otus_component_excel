<?php

namespace Services\Infrastructure;

use Exception;
use Miningelement\Logger;

abstract class EntityChangesLoggingService
{
    const AGENT_USER_ID = -1;

    const ADD_ACTION    = 'add';
    const UPDATE_ACTION = 'update';
    const DELETE_ACTION = 'delete';

    /**
     * @param  string  $entityName
     * @param  int  $entityId
     * @param  string  $action
     * @param  array|null  $data
     * @param  bool  $debug
     */
    public static function run(string $entityName, int $entityId, string $action = '', ?array $data = [], bool $debug = false): void
    {
        $date = date_create()->format('dmY');
        $action = $action ? $action.'_' : '';
        $logger = new Logger("entity_changes/{$entityName}/{$action}{$entityId}/{$date}.log");
        $trace = $debug ? self::getTrace()."\n" : '';
        $body = self::getRequestBody();
        $userId = self::getUserId();
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'CLI';
        $data = $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : '';
        $logger->debug("{$requestUri}\nUserId => {$userId}\n{$trace}{$body}\n{$data}");
    }

    /**
     * @return string
     */
    private static function getRequestBody(): string
    {
        return file_get_contents('php://input') ?: json_encode($_REQUEST);
    }

    /**
     * @return string
     */
    private static function getTrace(): string
    {
        return (new Exception())->getTraceAsString();
    }

    /**
     * @return int
     */
    private static function getUserId(): int
    {
        return isset($GLOBALS['USER']) ? ($GLOBALS['USER']->GetID() ?: self::AGENT_USER_ID) : self::AGENT_USER_ID;
    }
}
