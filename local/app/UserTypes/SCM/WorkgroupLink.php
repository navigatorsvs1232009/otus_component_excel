<?php

namespace UserTypes\SCM;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use CSocNetGroup;
use CUserTypeManager;

class WorkgroupLink
{
    const USER_TYPE_ID = 'workgroup_link';
    const DESCRIPTION  = 'Workgroup link';

    const WORKGROUP_STAFF_SUBJECT_IDS = [1, 2, 3, 4];

    /**
     * @return array
     */
    public static function getUserTypeDescription(): array
    {
        return [
            'USER_TYPE_ID'  => self::USER_TYPE_ID,
            'DESCRIPTION'   => self::DESCRIPTION,
            'PROPERTY_TYPE' => 'I',
            'USER_TYPE'     => self::class,
            'BASE_TYPE'     => CUserTypeManager::BASE_TYPE_INT,
            'CLASS_NAME'    => self::class,

            'GetPublicViewHTML'    => [self::class, 'GetPublicViewHTML'],
            'GetAdminListViewHTML' => [self::class, 'GetPublicViewHTML'],
            'GetPropertyFieldHtml' => [self::class, 'GetPropertyFieldHtml'],
            'GetPublicEditHTML'    => [self::class, 'GetPublicEditHTML'],
            'GetPropertyFieldHtml' => [self::class, 'GetPublicEditHTML']
        ];
    }

    /**
     * @param  array  $field
     * @param  array  $value
     *
     * @return string
     * @throws LoaderException
     */
    public static function GetPublicViewHTML(array $field, array $value = []): string
    {
        $workgroupId = $value['VALUE'] ?? $field['VALUE'] ?? null;
        if (empty($workgroupId)) {
            return '';
        }

        $workgroupList = self::getWorkgroupList();
        $workgroup = $workgroupList[$workgroupId];
        if (empty($workgroup)) {
            return '';
        }

        return "<span><a target='_blank' href='/workgroups/group/{$workgroup['ID']}/'>{$workgroup['NAME']}</a></span>";
    }

    /**
     * @param  array  $field
     * @param  array  $value
     * @param  array  $HTMLControl
     *
     * @return string
     * @throws LoaderException
     */
    public static function GetPublicEditHTML(array $field, $value = [], $HTMLControl = []): string
    {
        global $APPLICATION;
        $field['USER_TYPE'] = self::class;

        ob_start();
        $APPLICATION->IncludeComponent(
            'bitrix:system.field.edit',
            self::USER_TYPE_ID,
            [
                'arUserField'   => $field,
                'value'         => $value['VALUE'] ?? $field['VALUE'] ?? null,
                'htmlControl'   => $HTMLControl['VALUE'],
                'workgroupList' => self::getWorkgroupList(),
            ]
        );

        return ob_get_clean();
    }

    /**
     * @return array
     * @throws LoaderException
     */
    public static function getWorkgroupList(): array
    {
        static $workgroupList = null;
        if (is_null($workgroupList)) {
            Loader::includeModule('workgroup');

            $dbResult = CSocNetGroup::GetList(
                ['NAME' => 'ASC'],
                ['SUBJECT_ID' => self::WORKGROUP_STAFF_SUBJECT_IDS],
                false,
                false,
                ['ID', 'NAME']
            );
            while ($row = $dbResult->Fetch()) {
                $workgroupList[$row['ID']] = $row;
            }
        }

        return $workgroupList ?? [];
    }
}
