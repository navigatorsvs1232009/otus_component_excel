<?php

namespace Services\Infrastructure;

use Bitrix\Main\Application;
use Throwable;

class IblockElementNameHandler
{
    public static function handle(?int $id, string $entityName = ''): void
    {
        if (empty($id)) {
            return;
        }

        $sql = "update b_iblock_element set NAME=concat('{$entityName} ', ID, ' from ', date_format(DATE_CREATE, '%d.%m.%Y %H:%i')) where ID={$id}";
        try {
            Application::getConnection()->query($sql);
        } catch (Throwable $e) {
            //
        }
    }
}
