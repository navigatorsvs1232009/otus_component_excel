<?php

namespace Models\SCM;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\DateField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\Internals\TaskTable;
use Models\AbstractIblockPropertyValuesTable;
use Models\ProductPropertyValueTable;
use Models\ToolingPropertyValuesTable;

Loader::includeModule('tasks');

class ProductionApprovalTable extends DataManager
{
    /*

CREATE TABLE `e_scm_production_approval` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `PRODUCT_ID` int(11) NOT NULL,
  `QUANTITY` int(11) NOT NULL,
  `CLIENT_TITLE` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `DEADLINE` date NOT NULL,
  `CREATED_AT` datetime NOT NULL,
  `APPROVED` int(1) NOT NULL,
  `REJECTED` int(1) NOT NULL,
  `PROCESSED_AT` datetime NOT NULL,
  `PROCESSED_BY_ID` int(11) NOT NULL,
  `XML_ID` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `CRM_DEAL_ID` int(11) NULL,
  `COMMENT` text COLLATE utf8_unicode_ci NOT NULL,
  `TASK_ID` int(11) DEFAULT NULL,
  `EXPIRES_AT` datetime NULL,
  `PRODUCT_HASH` varchar(32) NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci

    */

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'e_scm_production_approval';
    }

    /**
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return [
            'ID' => new IntegerField('ID', [
                'primary'      => true,
                'autocomplete' => true,
            ]),

            'PRODUCT_ID'   => new IntegerField('PRODUCT_ID'),
            'PRODUCT_PROPERTY' => new ReferenceField(
                'PRODUCT_PROPERTY',
                ProductPropertyValueTable::class,
                ['=this.PRODUCT_ID' => 'ref.IBLOCK_ELEMENT_ID']
            ),

            'QUANTITY'     => new IntegerField('QUANTITY'),
            'CLIENT_TITLE' => new StringField('CLIENT_TITLE'),
            'DEADLINE'     => new DateField('DEADLINE'),
            'CREATED_AT'   => new DateTimeField('CREATED_AT'),

            'DEMAND_PRODUCT_ROW' => new ReferenceField(
                'DEMAND_PRODUCT_ROW',
                DemandProductRowsTable::class,
                ['=this.ID' => 'ref.PRODUCTION_APPROVAL_ID'],
                ['join_type' => 'LEFT']
            ),

            'APPROVED'        => new IntegerField('APPROVED'),
            'REJECTED'        => new IntegerField('REJECTED'),
            'PROCESSED_AT'    => new DatetimeField('PROCESSED_AT'),
            'PROCESSED_BY_ID' => new IntegerField('PROCESSED_BY_ID'),
            'PROCESSED_BY'    => new ReferenceField(
                'PROCESSED_BY',
                UserTable::class,
                ['=this.PROCESSED_BY_ID' => 'ref.ID'],
                ['join_type' => 'LEFT']
            ),

            'XML_ID'      => new StringField('XML_ID'),
            'CRM_DEAL_ID' => new IntegerField('CRM_DEAL_ID'),
            'COMMENT'     => new TextField('COMMENT'),
            'TASK_ID'     => new IntegerField('TASK_ID'),
            'TASK'        => new ReferenceField(
                'TASK',
                TaskTable::class,
                ['=this.TASK_ID' => 'ref.ID'],
                ['join_type' => 'LEFT']
            ),

            'EXPIRES_AT'   => new DateField('EXPIRES_AT'),
            'PRODUCT_HASH' => new StringField('PRODUCT_HASH'), # md5($product['ID'].$product['KD_REVISION'].$product['ME_MATERIAL'].$product['WEIGHT']);

            'PATTERN_ID' => new ExpressionField(
                'PATTERN_ID',
                sprintf('(select group_concat(`IBLOCK_ELEMENT_ID` SEPARATOR "\0") as VALUE from %s as m where m.VALUE = %s and m.IBLOCK_PROPERTY_ID = %d)',
                    ToolingPropertyValuesTable::getTableNameMulti(),
                    '%s',
                    ToolingPropertyValuesTable::getPropertyId('PRODUCT_ID')
                ),
                ['PRODUCT_ID'],
                ['fetch_data_modification' => [AbstractIblockPropertyValuesTable::class, 'getMultipleFieldValueModifier']]
            ),
        ];
    }
}
