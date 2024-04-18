<?php


namespace Models\SCM;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\SystemException;

class SupplierRatingTable extends DataManager
{
    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'e_scm_supplier_rating';
    }

    /**
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return [
            'COMPANY_ID' => new IntegerField('COMPANY_ID', ['primary' => true]),
            'RATING_VALUES' => new TextField('RATING_VALUES', [
                'fetch_data_modification' => function () {
                    return [
                        function ($value) {
                            return json_decode($value, true);
                        },
                    ];
                },
            ]),
        ];
    }
}
