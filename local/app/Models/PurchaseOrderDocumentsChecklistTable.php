<?php

namespace Models;

use Bitrix\Crm\DealTable;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\TaskTable;
use Models\SCM\OrderPropertyValueTable;

class PurchaseOrderDocumentsChecklistTable extends DataManager
{
    const STATUS_ENUM = [
        'not_uploaded' => 'Not uploaded',
        'uploaded'     => 'Uploaded',
        'not_used'     => 'Not used',
    ];

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'e_scm_purchase_order_documents_checklist';
    }

    /**
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return [
            'PURCHASE_ORDER_ID' => new IntegerField('PURCHASE_ORDER_ID', ['primary' => true]),
            'DOCUMENT_ID'       => new IntegerField('DOCUMENT_ID', ['primary' => true]),
            'DOCUMENT'          => new ReferenceField(
                'DOCUMENT',
                ElementTable::class,
                ['=this.DOCUMENT_ID' => 'ref.ID'],
            ),
            'PURCHASE_ORDER'    => new ReferenceField(
                'PURCHASE_ORDER',
                DealTable::class,
                ['=this.PURCHASE_ORDER_ID' => 'ref.ID'],
            ),

            'TASK_ID' => new ExpressionField(
                'TASK_ID',
                '(select VALUE_ID from b_uts_tasks_task where UF_PROC_ORDER_ID=%s and UF_PROC_ORDER_STAGE=%s)',
                ['ORDER_ID', 'ORDER_STAGE_ID']
            ),
            'TASK'    => new ReferenceField(
                'TASK',
                TaskTable::class,
                ['=this.TASK_ID' => 'ref.ID']
            ),

            'ORDER_STAGE_ID'       => new IntegerField('ORDER_STAGE_ID'),
            'ORDER_STAGE'          => new ReferenceField(
                'ORDER_STAGE',
                ElementTable::class,
                ['=this.ORDER_STAGE_ID' => 'ref.ID'],
            ),

            'ORDER_ID' => new IntegerField('ORDER_ID'),
            'ORDER' => new ReferenceField(
                'ORDER',
                OrderPropertyValueTable::class,
                ['=this.ORDER_ID' => 'ref.IBLOCK_ELEMENT_ID'],
            ),

            'STATUS'  => new EnumField('STATUS', ['values' => array_keys(self::STATUS_ENUM)]),
            'FILE_ID' => new IntegerField('FILE_ID', ['nullable' => true]),
            'FILE'    => new ReferenceField(
                'FILE',
                ObjectTable::class,
                ['=this.FILE_ID' => 'ref.ID'],
            ),
        ];
    }
}
