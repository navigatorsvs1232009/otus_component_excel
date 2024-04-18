<?php

namespace Models\SCM;

use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\SystemException;

class ContractSpecificationNumberTable extends DataManager
{
    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'e_contract_specification_number';
    }

    /**
     * @return array
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return [
            'CONTRACT_HASH'        => new StringField('CONTRACT_HASH', ['primary' => true]),
            'SPECIFICATION_NUMBER' => new IntegerField('SPECIFICATION_NUMBER', ['primary' => true]),
        ];
    }
}
