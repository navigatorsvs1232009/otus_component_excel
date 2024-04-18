<?php
namespace Repositories;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

class ClientTable extends Entity\DataManager
{
    public static function getTableName(): string
    {
        return 'client_db_list';
    }

    public static function getMap(): array
    {
        return [
            new Entity\IntegerField('id', [
                'primary' => true,
                'autocomplete' => true,
            ]),
            new Entity\StringField('name'),
            new Entity\StringField('lastname'),
            new Entity\StringField('phone'),
            new Entity\StringField('job_position'),
        ];
    }

    public static function saveData($COMPANY_ID, $DATA)
    {
        $rowData = [
            "COMPANY_ID" => $COMPANY_ID,
            "DATA" => json_decode($DATA),
        ];
        $result = self::getList(
            [
                "filter" => [
                    "COMPANY_ID" => $COMPANY_ID,
                ],
            ]
        );
        if ($row = $result->Fetch()) {
            return self::update($row['ID'], $rowData);
        } else {
            return self::add($rowData);
        }
    }

    public static function getData($COMPANY_ID)
    {
        $result = self::getList(
            [
                "filter" => [
                    "COMPANY_ID" => $COMPANY_ID,
                ]
            ]
        );
        if ($row = $result->Fetch()) {
            return json_decode($row['DATA'], true);
        }
        return false;
    }
}