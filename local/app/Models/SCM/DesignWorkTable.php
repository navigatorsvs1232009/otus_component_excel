<?php

namespace Models\SCM;

use Bitrix\Crm\ProductRowTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\SystemException;

class DesignWorkTable extends DataManager
{
    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'e_scm_design_work';
    }

    /**
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return [
            'ROW_ID' => new IntegerField('ROW_ID', ['primary' => true]),
            'ROW'    => new ReferenceField('ROW', ProductRowTable::class, ['=this.ROW_ID' => 'ref.ID']),

            'DESIGN_WORK_ID' => new IntegerField('DESIGN_WORK_ID'),
            'TICK_FOR_SCAN'  => new IntegerField('TICK_FOR_SCAN'),
        ];
    }

    /**
     * @param  int  $rowId
     * @param  bool  $value
     *
     * @throws SqlQueryException
     */
    public static function setTickForScan(int $rowId, bool $value): void
    {
        Application::getConnection()->query(
            sprintf(
                "insert into %s (ROW_ID, TICK_FOR_SCAN) value (%d, %d) on duplicate key update TICK_FOR_SCAN = VALUES(`TICK_FOR_SCAN`)",
                self::getTableName(),
                $rowId,
                (int) $value
            )
        );
    }
}
