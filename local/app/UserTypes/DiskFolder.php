<?php

namespace UserTypes;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Folder;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\NotImplementedException;
use CUserTypeManager;

class DiskFolder
{
    const USER_TYPE_ID = 'disk_folder';
    const DESCRIPTION  = 'Folder (drive)';

    /**
     * @return array
     */
    public static function getUserTypeDescription(): array
    {
        return [
            'USER_TYPE_ID'  => self::USER_TYPE_ID,
            'CLASS_NAME'    => self::class,
            'DESCRIPTION'   => self::DESCRIPTION,
            'BASE_TYPE'     => CUserTypeManager::BASE_TYPE_INT,
            'PROPERTY_TYPE' => 'N',
            'USER_TYPE'     => self::class,

            'GetPublicViewHTML'    => [self::class, 'getPublicViewHTML'],
            'GetAdminListViewHTML' => [self::class, 'getPublicViewHTML'],
            'GetPublicEditHTML'    => [self::class, 'getPublicViewHTML'],
            'GetPropertyFieldHtml' => [self::class, 'getPublicViewHTML'],

            'VIEW_CALLBACK' => [self::class, 'getPublicViewHTML'],
            'EDIT_CALLBACK' => [self::class, 'getPublicViewHTML'],
        ];
    }

    /**
     * @return string
     */
    public static function GetDBColumnType(): string
    {
        return 'int(18)';
    }

    /**
     * @param  array  $field
     * @param  array|null  $value
     *
     * @return string
     * @throws LoaderException
     * @throws NotImplementedException
     */
    public static function getPublicViewHTML(array $field, ?array $value = []): string
    {
        Loader::includeModule('disk');

        $folderId = $value['VALUE'] ?? $field['VALUE'] ?? null;
        if (is_array($folderId) || empty($folderId)) {
            return '';
        }

        $folder = Folder::getById($folderId, ['STORAGE']);
        if (empty($folder)) {
            return '';
        }

        $folderUrl = Driver::getInstance()->getUrlManager()->getPathFolderList($folder);

        return "<a target='_blank' href='{$folderUrl}'>{$folder->getName()}</a>";
    }
}
