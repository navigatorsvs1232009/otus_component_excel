<?php

namespace UserTypes\Delivery;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\AttachedObjectTable;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Uf\IblockElementConnector;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CIBlockElement;
use Models\DeliveryTransportExpensesPropertyValuesTable;
use Models\SCM\DeliveryPropertyValuesTable;
use Repositories\Currencies;

class TransportExpenses
{
    public const USER_TYPE_ID = 'delivery_transport_expenses';

    /**
     * @return array
     */
    public static function getUserTypeDescription(): array
    {
        return [
            'USER_TYPE_ID'  => self::USER_TYPE_ID,
            'DESCRIPTION'   => 'Delivery transport expenses',
            'PROPERTY_TYPE' => 'I',
            'USER_TYPE'     => self::class,

            'GetAdminListViewHTML'   => [self::class, 'GetPublicViewHTML'],
            'GetPublicEditHTMLMulty' => [self::class, 'GetPublicEditHTMLMulty'],
            'GetPropertyFieldHtml'   => [self::class, 'GetPublicEditHTML'],
            'GetPublicEditHTML'      => [self::class, 'GetPublicEditHTML'],
        ];
    }

    /**
     * @param  array  $field
     * @param  ?array  $values
     * @param  array  $HTMLControl
     *
     * @return string
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function GetPublicEditHTMLMulty(array $field, ?array $values, array $HTMLControl = []) : string
    {
        Loader::includeModule('disk');
        $field['USER_TYPE'] = self::class;

        $rows = $field['ELEMENT_ID'] > 0 ? self::getDeliveryTransportExpenses($field['ELEMENT_ID']) : [];
        $fileIds = array_reduce(
            $rows,
            fn($fileIds, $row) => array_merge($fileIds, $row['FILE_ID']),
            []
        );
        $files = self::getFiles($fileIds);
        foreach ($rows as &$row) {
            $row['FILES'] = array_filter(
                array_map(fn($fileId) => $files[$fileId], $row['FILE_ID'])
            );
        }

        ob_start();
        $GLOBALS['APPLICATION']->IncludeComponent(
            'bitrix:system.field.edit',
            self::USER_TYPE_ID,
            [
                'arUserField'   => $field,
                'etdPropertyId' => DeliveryPropertyValuesTable::getPropertyId('ETD'),
                'data'          => [
                    'controlName' => $HTMLControl['VALUE'],
                    'currencies'  => Currencies::all(),
                    'types'       => array_values(DeliveryTransportExpensesPropertyValuesTable::getEnumPropertyOptions('TYPE_ID')),
                    'departments' => array_values(DeliveryTransportExpensesPropertyValuesTable::getEnumPropertyOptions('DEPARTMENT_ID')),
                    'rows'        => $rows,
                ],
            ]
        );

        return ob_get_clean();
    }

    /**
     * @param  array  $field
     * @param  array|null  $values
     * @param  array  $HTMLControl
     *
     * @return string
     */
    public static function GetPublicViewHTMLMulty(array $field, ?array $values, array $HTMLControl = []): string
    {
        return '';
    }

    /**
     * @param  array  $field
     * @param  array  $value
     *
     * @return string
     */
    public static function ConvertToDB(array $field, array $value): string
    {
        return json_encode($value['VALUE'], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  int  $deliveryId
     * @param  array  $rows
     * @param  array  $files
     * @param  Folder  $filesFolder
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws LoaderException
     */
    public static function saveValue(int $deliveryId, array $rows, array $files, Folder $filesFolder): void
    {
        Loader::includeModule('disk');

        $iblockElement = new CIBlockElement();
        $deliveryTransportExpenses = self::getDeliveryTransportExpenses($deliveryId);
        $deliveryTransportExpenses = array_combine(
            array_column($deliveryTransportExpenses, 'ID'),
            $deliveryTransportExpenses
        );

        $totalCostsEUR = 0.0;
        foreach ($rows as $rowIndex => $row) {
            $iblockElementId = $row['ID'];

            if (empty($iblockElementId)) {
                $iblockElementId = $iblockElement->Add([
                    'NAME'            => $row['NAME'] ?: 'Not set',
                    'IBLOCK_ID'       => DELIVERY_TRANSPORT_EXPENSES_IBLOCK_ID,
                    'PROPERTY_VALUES' => [
                        'DELIVERY_ID'             => $deliveryId,
                        'PERCENTAGE'              => $row['PERCENTAGE'],
                        'CURRENCY'                => $row['CURRENCY'],
                        'EXPECTED_CURRENCY'       => $row['EXPECTED_CURRENCY'],
                        'TYPE_ID'                 => $row['TYPE_ID'],
                        'DEPARTMENT_ID'           => $row['DEPARTMENT_ID'],
                        'AMOUNT'                  => $row['AMOUNT'],
                        'EXPECTED_AMOUNT'         => $row['EXPECTED_AMOUNT'],
                        'COUNTERAGENT_COMPANY_ID' => $row['COUNTERAGENT_COMPANY_ID'],
                        'COST_EUR'                => $row['COST_EUR']
                    ]
                ]);

            } else {
                # по какой-то причине нет записи в исходном наборе => пропускаем
                if (empty($deliveryTransportExpenses[$iblockElementId])) {
                    continue;
                }

                if ($row['NAME'] !== $deliveryTransportExpenses[$row['ID']]['NAME']) {
                    $iblockElement->Update($row['ID'], ['NAME' => $row['NAME']]);
                }

                CIBlockElement::SetPropertyValuesEx(
                    $iblockElementId,
                    DELIVERY_TRANSPORT_EXPENSES_IBLOCK_ID,
                    [
                        'PERCENTAGE'              => $row['PERCENTAGE'],
                        'CURRENCY'                => $row['CURRENCY'],
                        'EXPECTED_CURRENCY'       => $row['EXPECTED_CURRENCY'],
                        'TYPE_ID'                 => $row['TYPE_ID'],
                        'AMOUNT'                  => $row['AMOUNT'],
                        'EXPECTED_AMOUNT'         => $row['EXPECTED_AMOUNT'],
                        'DEPARTMENT_ID'           => $row['DEPARTMENT_ID'],
                        'COUNTERAGENT_COMPANY_ID' => $row['COUNTERAGENT_COMPANY_ID'],
                        'COST_EUR'                => $row['COST_EUR']
                    ]
                );

                unset($deliveryTransportExpenses[$row['ID']]);
            }

            # отдельно аттачим файлы
            $filesData = array_map(fn($item) => $item[$rowIndex]['FILE'], $files);
            foreach ($row['FILE_ID'] as $i => $fileId) {
                if (empty($fileId)) {
                    $fileData = array_map(fn($item) => $item[$i], $filesData);
                    $file = !empty($fileData['name'])
                        ? $filesFolder->uploadFile(
                            $fileData,
                            [
                                'NAME'       => $fileData['name'],
                                'CREATED_BY' => CurrentUser::get()->getId(),
                            ],
                            [],
                            true
                        )
                        : null;

                    if ($file instanceof File) {
                        $attachedObject = AttachedObject::add([
                            'OBJECT_ID'   => $file->getId(),
                            'ENTITY_ID'   => $iblockElementId,
                            'ENTITY_TYPE' => IblockElementConnector::class,
                            'MODULE_ID'   => 'iblock',
                        ], new ErrorCollection());

                        $row['FILE_ID'][$i] = $attachedObject->getId();
                    }
                }
            }

            CIBlockElement::SetPropertyValuesEx(
                $iblockElementId,
                DELIVERY_TRANSPORT_EXPENSES_IBLOCK_ID,
                ['FILE_ID' => join(',', $row['FILE_ID'] ?? [])]
            );

            # сумма Cost EUR
            $totalCostsEUR += (float) $row['COST_EUR'];
        }

        # сохраним набор контрагентов в деливери
        $counteragentCompanyIds = array_unique(
            array_filter(
                array_column($rows, 'COUNTERAGENT_COMPANY_ID')
            )
        );

        CIBlockElement::SetPropertyValuesEx(
            $deliveryId,
            DELIVERY_IBLOCK_ID,
            [
                'COUNTERAGENT_COMPANY_ID' => $counteragentCompanyIds,
                'TOTAL_COSTS_EUR'         => $totalCostsEUR
            ]
        );

        # удалим строки, которых не было в форме
        foreach ($deliveryTransportExpenses as $deliveryTransportExpensesItem) {
            CIBlockElement::Delete($deliveryTransportExpensesItem['ID']);
        }
    }

    /**
     * @param  int  $deliveryId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function getDeliveryTransportExpenses(int $deliveryId): array
    {
        $dbResult = DeliveryTransportExpensesPropertyValuesTable::query()
            ->setSelect([
                '*',
                'ID'                               => 'IBLOCK_ELEMENT_ID',
                'NAME'                             => 'ELEMENT.NAME',
                'CREATED_AT'                       => 'ELEMENT.DATE_CREATE',
                'COUNTERAGENT_COMPANY_TITLE'       => 'COUNTERAGENT_COMPANY.TITLE',
                'COUNTERAGENT_COMPANY_SHORT_TITLE' => 'COUNTERAGENT_COMPANY.UF_SHORT_TITLE',
            ])
            ->where('DELIVERY_ID', $deliveryId)
            ->exec();
        $dbResult->addFetchDataModifier(function ($row) {
            $row['FILE_ID'] = explode(',', $row['FILE_ID']);
            $row['CREATED_AT'] = $row['CREATED_AT']->format('H:i:s / d.m.Y');
            $row['COUNTERAGENT_COMPANY_TITLE'] = $row['COUNTERAGENT_COMPANY_SHORT_TITLE'] ?: $row['COUNTERAGENT_COMPANY_TITLE'];
            $row['COST_EUR'] = number_format((float) $row['COST_EUR'], 2, '.', '');

            return $row;
        });

        return $dbResult->fetchAll();
    }

    /**
     * @param  array  $fileIds
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function getFiles(array $fileIds): array
    {
        if (empty($fileIds)) {
            return [];
        }

        $dbResult = AttachedObjectTable::getList([
            'select' => ['ID', 'NAME' => 'OBJECT.NAME',],
            'filter' => ['=ID' => $fileIds]
        ]);
        while ($row = $dbResult->fetch()) {
            $row['HREF'] = "/bitrix/tools/disk/uf.php?attachedId={$row['ID']}&action=download&ncc=1";

            $files[$row['ID']] = $row;
        }

        return $files ?? [];
    }
}
