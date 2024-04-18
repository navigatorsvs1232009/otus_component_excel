<?php

namespace EventHandlers\IblockElement;

use Agents\Integration\MiningElement\QA\PurchaseOrderExportAgent;
use Bitrix\Crm\CompanyTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\Integration\Report\Internals\TaskTable;
use CBitrixComponent;
use CIBlockElement;
use CTaskAssertException;
use CTaskItem;
use CTasks;
use EventHandlers\IblockElement\Interfaces\OnAfterUpdateEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeListElementEditFormFieldsPreparedEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnBeforeUpdateEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterAddEventHandlerInterface;
use Models\SCM\SopoPropertyValueTable;
use Models\SCM\SpecificationProductRowsTable;
use Models\SCM\SpecificationPropertyValuesTable;
use Bitrix\Main\Entity\ReferenceField;
use Repositories\OrderStagesRef;
use Repositories\UsersRepository;
use Services\Infrastructure\EntityChangesLoggingService;
use Services\Infrastructure\SCM\SpecificationTraceNumbersHandler;
use TasksException;

/**
 * Class SpecificationEventHandler
 *
 * @package EventHandlers\IblockElement
 */
class SpecificationEventHandler implements OnBeforeListElementEditFormFieldsPreparedEventHandlerInterface, OnAfterAddEventHandlerInterface,
                                           OnBeforeUpdateEventHandlerInterface, OnAfterUpdateEventHandlerInterface
{
    private array $orderStagesRef;
    private static array $state = [];

    /**
     * SpecificationEventHandler constructor.
     */
    public function __construct()
    {
        $this->orderStagesRef = OrderStagesRef::all('CODE');
    }

    /**
     * @param $element
     *
     * @return bool|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onBeforeUpdate(&$element): ?bool
    {
        self::$state['onBeforeUpdate'][$element['ID']] = $this->getSpecification($element['ID']);

        return null;
    }

    /**
     * @param $element
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onAfterAdd($element): void
    {
        if (empty($element['ID'])) {
            return;
        }

        $this->onAfterUpdate($element);
    }

    /**
     * @param $element
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onAfterUpdate($element): void
    {
        EntityChangesLoggingService::run('specification', $element['ID']);

        $specification = $this->getSpecification($element['ID']);
        $this->handleRevisionNumber(
            self::$state['onBeforeUpdate'][$specification['ID']] ?? null,
            $specification
        );

        # дальнейшие действия только для подтверждённых продактами
        if (!$specification['PRODUCTION_APPROVED']) {
            return;
        }

        (new SpecificationTraceNumbersHandler())->run($specification);

        # формируем файл спецификации
        $GLOBALS['APPLICATION']->IncludeComponent('element:lists.specification.export.excel', '',
            [
                'specificationId' => $specification['ID'],
                'action'          => 'save_to_drive',
                'driveFolderId'   => $specification['DRIVE_FOLDER_ID'],
            ]
        );

        PurchaseOrderExportAgent::scheduleRunOnce($specification['PURCHASE_ORDER_ID']);

        # спецификация перешла в статус PRODUCTION_APPROVED = true
        $this->completeSpecificationTask($specification['PURCHASE_ORDER_ID']);
    }

    /**
     * @param  array|null  $specificationBeforeUpdate
     * @param  array  $specification
     */
    private function handleRevisionNumber(?array $specificationBeforeUpdate, array $specification): void
    {
        $revision = $specification['REVISION'];

        # не учитываем старые хеш и ревизию при расчёте нового хеша
        unset($specification['HASH']);
        unset($specification['REVISION']);
        $specification['HASH'] = md5(json_encode($specification));

        if (isset($specificationBeforeUpdate)
            && $specificationBeforeUpdate['PRODUCTION_APPROVED']
            && $specificationBeforeUpdate['PROCUREMENT_APPROVED']
            && $specification['PRODUCTION_APPROVED']
            && $specification['PROCUREMENT_APPROVED']
            && $specificationBeforeUpdate['HASH'] !== $specification['HASH']
        ) {
            $revision++;
        }

        CIBlockElement::SetPropertyValuesEx(
            $specification['ID'],
            SPECIFICATION_IBLOCK_ID,
            [
                'HASH'     => $specification['HASH'],
                'REVISION' => $revision,
            ]
        );
    }

    /**
     * @param  CBitrixComponent  $component
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function onBeforeListElementEditFormFieldsPrepared(CBitrixComponent $component): void
    {
        $sopoId = (int) Application::getInstance()->getContext()->getRequest()->get('sopoId');

        if ($sopoId > 0) {
            $sopo = SopoPropertyValueTable::getByPrimary($sopoId)->fetch();
            $purchaseOrderId = (int) $sopo['PURCHASE_ORDER_ID'];
            $lastSpecification = $this->getLastSpecification($purchaseOrderId, $sopo['SELLER_COMPANY_ID']);

            # уже есть спецификация, редиректим на неё
            if ($lastSpecification) {
                localRedirect(sprintf("/services/lists/%d/element/0/%d/", SPECIFICATION_IBLOCK_ID, $lastSpecification['ID'])); // todo взять ссылку из репозитория
            }

            $firstPurchaseOrderOrder = $this->getFirstPurchaseOrderOrder($purchaseOrderId);

        } elseif (array_key_exists('sopoId', Application::getInstance()->getContext()->getRequest()->getValues())) {
            exit('Invalid link to specification');
        }

        $userDepartmentIds = $this->getUserDepartments($GLOBALS['USER']->GetID());

        # подставим дефолтные значения
        foreach ($component->arResult['FIELDS'] as &$field) {
            if ($field['CODE'] === 'SOPO_ID') {
                $field['DEFAULT_VALUE'] = $sopo['IBLOCK_ELEMENT_ID'] ?? '';
            }

            if ($field['CODE'] === 'SUPPLY_CONTRACT_NUMBER') {
                $field['DEFAULT_VALUE'] = $sopo['SUPPLY_CONTRACT_NUMBER'] ?? '';
            }

            if ($field['CODE'] === 'NUMBER') {
                $field['DEFAULT_VALUE'] = $sopo['SPECIFICATION_NUMBER'] ?? '';
            }

            if ($field['CODE'] === 'REVISION') {
                $lastRevision = $lastSpecification['PROPERTY_REVISION_VALUE'] ?? null;
                empty($lastRevision) ? $lastRevision = 'A' : $lastRevision++;
                $field['DEFAULT_VALUE'] = $lastRevision;
            }

            if ($field['CODE'] === 'INCOTERMS_ID') {
                # не показываем поле продуктовикам
                if (in_array(ME_PRODUCTION_DEPARTMENT_ID, $userDepartmentIds)) {
                    $field['SETTINGS']['SHOW_ADD_FORM'] = 'N';
                    $field['SETTINGS']['SHOW_EDIT_FORM'] = 'N';
                }

                $field['DEFAULT_VALUE'] = $firstPurchaseOrderOrder['PROPERTY_INCOTERMS_VALUE'] ?? null;
            }

            if ($field['CODE'] === 'PRODUCT_ROWS') {
                $field['PURCHASE_ORDER_ID'] = $purchaseOrderId ?? null;
                $field['SOPO'] = $sopo ?? [];
            }

            if ($field['CODE'] === 'SELLER_COMPANY_ID') {
                $field['DEFAULT_VALUE'] = $sopo['SELLER_COMPANY_ID'] ?? '';
                $sellerCompanyId = $component->arResult['ELEMENT_PROPS'][$field['ID']]['VALUE'] ?: $field['DEFAULT_VALUE'];
            }

            if ($field['CODE'] === 'BUYER_COMPANY_ID') {
                $field['DEFAULT_VALUE'] = $sopo['BUYER_COMPANY_ID'] ?? '';
                $buyerCompanyId = $component->arResult['ELEMENT_PROPS'][$field['ID']]['VALUE'] ?: $field['DEFAULT_VALUE'];
            }

            if ($field['CODE'] === 'PURCHASE_ORDER_ID') {
                $field['DEFAULT_VALUE'] = $sopo['PURCHASE_ORDER_ID'] ?? '';
            }

            if ($field['CODE'] === 'INCOTERMS_PLACE_ID') {
                # не показываем поле продуктовикам
                if (in_array(ME_PRODUCTION_DEPARTMENT_ID, $userDepartmentIds)) {
                    $field['SETTINGS']['SHOW_ADD_FORM'] = 'N';
                    $field['SETTINGS']['SHOW_EDIT_FORM'] = 'N';
                }

                $field['DEFAULT_VALUE'] = $firstPurchaseOrderOrder['PROPERTY_INCOTERMS_PLACE_VALUE'] ?? null;
                $incotermsPlaceField = &$field;
            }

            if ($field['CODE'] === 'TRANSFER_OF_OWNERSHIP_ID'
                || $field['CODE'] === 'DELIVERY_DAYS'
                || $field['CODE'] === 'PAYMENT_TERMS_ID'
                || $field['CODE'] === 'PROCUREMENT_APPROVED'
            ) {
                # не показываем поле продуктовикам
                if (in_array(ME_PRODUCTION_DEPARTMENT_ID, $userDepartmentIds)) {
                    $field['SETTINGS']['SHOW_ADD_FORM'] = 'N';
                    $field['SETTINGS']['SHOW_EDIT_FORM'] = 'N';
                }
            }
        }

        $incotermsPlaceField['COMPANIES'] = [
          'SELLER_COMPANY_ID' => $sellerCompanyId ?? '',
          'BUYER_COMPANY_ID'  => $buyerCompanyId ?? '',
        ];
    }

    /**
     * @param  int  $purchaseOrderId
     *
     * @param  int  $sellerCompanyId
     *
     * @return array
     */
    private function getLastSpecification(int $purchaseOrderId, int $sellerCompanyId): array
    {
        return CIBlockElement::GetList(
            ['PROPERTY_REVISION' => 'ASC'],
            ['IBLOCK_ID' => SPECIFICATION_IBLOCK_ID, 'PROPERTY_PURCHASE_ORDER_ID' => $purchaseOrderId, 'PROPERTY_SELLER_COMPANY_ID' => $sellerCompanyId],
            false,
            ['nTopCount' => 1],
            ['ID', 'PROPERTY_REVISION']
        )->Fetch() ?: [];
    }

    /**
     * @param  int  $purchaseOrderId
     *
     * @return array
     */
    private function getFirstPurchaseOrderOrder(int $purchaseOrderId): array
    {
        return CIBlockElement::GetList(
            ['DATE_CREATED' => 'ASC'],
            ['IBLOCK_ID' => ORDERS_IBLOCK_ID, 'PROPERTY_PROCUREMENT' => $purchaseOrderId],
            false,
            ['nTopCount' => 1],
            ['ID', 'PROPERTY_INCOTERMS', 'PROPERTY_INCOTERMS_PLACE']
        )->Fetch() ?: [];
    }

    /**
     * @param  int  $specificationId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getSpecification(int $specificationId): array
    {
        $specification = SpecificationPropertyValuesTable::getList([
            'select'  => [
                'ID'                   => 'IBLOCK_ELEMENT_ID',
                'DRIVE_FOLDER_ID'      => 'SOPO.DRIVE_FOLDER',
                'IS_MY_SELLER_COMPANY' => 'SELLER_COMPANY.IS_MY_COMPANY',
                'SELLER_COMPANY_ID',
                'PURCHASE_ORDER_ID',
                'PRODUCTION_APPROVED',
                'PROCUREMENT_APPROVED',
                'SOPO_ID',
                'TRANSFER_OF_OWNERSHIP_ID',
                'DELIVERY_DAYS',
                'PAYMENT_TERMS_ID',
                'INCOTERMS_ID',
                'INCOTERMS_PLACE_ID',
                'REVISION',
                'HASH'
            ],
            'filter'  => ['ID' => $specificationId],
            'runtime' => [
                new ReferenceField(
                    'SOPO',
                    SopoPropertyValueTable::class,
                    ['=this.SOPO_ID' => 'ref.IBLOCK_ELEMENT_ID']
                ),
                new ReferenceField(
                    'SELLER_COMPANY',
                    CompanyTable::class,
                    ['=this.SELLER_COMPANY_ID' => 'ref.ID']
                ),
            ],
        ])->fetch() ?: [];

        if ($specification) {
            $specification['PRODUCT_ROWS'] = SpecificationProductRowsTable::getList(['filter' => ['ROW.OWNER_TYPE' => 'D', 'SPECIFICATION_ID' => $specification['ID']]])->fetchAll();
        }

        return $specification;
    }

    /**
     * @param  int  $userId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getUserDepartments(int $userId): array
    {
        $user = UserTable::getByPrimary($userId, ['select' => ['ID', 'UF_DEPARTMENT']])->fetch();

        return $user['UF_DEPARTMENT'] ?? [];
    }

    /**
     * @param  int  $purchaseOrderId
     *
     * @throws ArgumentException
     * @throws CTaskAssertException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function completeSpecificationTask(int $purchaseOrderId): void
    {
        $task = TaskTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=UF_CRM_TASK'        => "D_{$purchaseOrderId}",
                'ZOMBIE'              => 'N',
                'UF_PROC_ORDER_STAGE' => $this->orderStagesRef['SP']['ID'],
                '!=STATUS'            => CTasks::STATE_COMPLETED,
            ],
        ])->fetch();
        if (empty($task)) {
            return;
        }

        try {
            (new CTaskItem($task['ID'], UsersRepository::getCurrentUserId()))->complete();

        } catch (TasksException) {
            //
        }
    }
}
