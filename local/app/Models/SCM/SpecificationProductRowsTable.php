<?php

namespace Models\SCM;

use Bitrix\Crm\ProductRowTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\SystemException;
use Models\ToolingPropertyValuesTable;

Loader::includeModule('crm');

class SpecificationProductRowsTable extends DataManager
{
    /*
CREATE TABLE `e_scm_specification_product_rows` (
`SPECIFICATION_ID` int(11) NOT NULL,
`ROW_ID` int(11) NOT NULL,
`USE_PRODUCTION_ID` tinyint NULL,
`DESCRIPTION` varchar(255) NULL,
`QUANTITY` float NULL,
`PRODUCT_WEIGHT` float NULL,
`PRODUCT_ME_MATERIAL` varchar(255) NULL,
`DRAWING_NUMBER` varchar(255) NULL,
`SPECIAL_REQUIREMENTS` varchar(255) NULL,
`PAINTING_COLOR` varchar(255) NULL,
`MARKING_ARTICLE` varchar(64) NULL,
`ARTICLE_MARKING_TYPE` varchar(16) NULL,
`TRACE_NUMBERS_TYPE` varchar(16) NULL,
`TRACE_NUMBERS_MARKING_TYPE` varchar(16) NULL,
PRIMARY KEY (`SPECIFICATION_ID`,`ROW_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
    */

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'e_scm_specification_product_rows';
    }

/**
 * @return array
 * @throws ArgumentException
 * @throws SystemException
 */
    public static function getMap(): array
    {
        return [
            'SPECIFICATION_ID' => new IntegerField('SPECIFICATION_ID', ['primary' => true]),
            'SPECIFICATION'    => new ReferenceField(
                'SPECIFICATION',
                SpecificationPropertyValuesTable::class,
                ['=this.SPECIFICATION_ID' => 'ref.IBLOCK_ELEMENT_ID']
            ),

            'ROW_ID' => new IntegerField('ROW_ID', ['primary' => true]),
            'ROW'    => new ReferenceField(
                'ROW',
                ProductRowTable::class,
                ['=this.ROW_ID' => 'ref.ID']
            ),

            'USE_PRODUCTION_ID'          => new BooleanField('USE_PRODUCTION_ID', ['values' => [0, 1]]),
            'USE_ELEMENT_ID'             => new BooleanField('USE_ELEMENT_ID', ['values' => [0, 1]]),
            'DESCRIPTION'                => new StringField('DESCRIPTION'),
            'QUANTITY'                   => new FloatField('QUANTITY'),
            'PRODUCT_WEIGHT'             => new FloatField('PRODUCT_WEIGHT'),
            'PRODUCT_ME_MATERIAL'        => new StringField('PRODUCT_ME_MATERIAL'),
            'DRAWING_NUMBER'             => new StringField('DRAWING_NUMBER'),
            'SPECIAL_REQUIREMENTS'       => new StringField('SPECIAL_REQUIREMENTS'),
            'PAINTING_COLOR'             => new StringField('PAINTING_COLOR'),
            'MARKING_ARTICLE'            => new StringField('MARKING_ARTICLE'),
            'ARTICLE_MARKING_TYPE'       => new StringField('ARTICLE_MARKING_TYPE'),
            'TRACE_NUMBERS_TYPE'         => new StringField('TRACE_NUMBERS_TYPE'),
            'TRACE_NUMBERS_MARKING_TYPE' => new StringField('TRACE_NUMBERS_MARKING_TYPE'),

            # alter table e_scm_specification_product_rows add column `TOOLING_ID` int(11) NULL
            'TOOLING_ID' => new IntegerField('TOOLING_ID'),
            'TOOLING'    => new ReferenceField(
                'TOOLING',
                ToolingPropertyValuesTable::class,
                ['=this.TOOLING_ID' => 'ref.IBLOCK_ELEMENT_ID']
            )
        ];
    }

    /**
     * @param  array  $rows
     *
     * @throws ArgumentException
     * @throws SystemException
     * @throws SqlQueryException
     */
    public static function saveRows(array $rows): void
    {
        $tableName = self::getTableName();
        $scalarFields = self::getEntity()->getScalarFields();
        $columns = join(',', array_keys($scalarFields));
        $duplicates = join(',', array_map(fn ($field) => "{$field->getName()} = VALUES(`{$field->getName()}`)", $scalarFields));

        foreach ($rows as $row) {
            $values[] = '(' . join(',', array_map(function ($field) use ($row) {
                if ($field instanceof IntegerField) {
                    if ($row[$field->getName()] === 0) {
                        return $row[$field->getName()];
                    }

                    if (empty($row[$field->getName()])) {
                        return 'NULL';
                    }

                    return $row[$field->getName()];
                }

                return "'{$row[$field->getName()]}'" ?? 'NULL';
            }, $scalarFields)). ')';
        }
        if (empty($values)) {
            return;
        }
        $values = join(',', $values);

        $sql = "INSERT INTO {$tableName} ({$columns}) VALUES {$values} ON DUPLICATE KEY UPDATE {$duplicates}";

        Application::getConnection()->query($sql);
    }
}
