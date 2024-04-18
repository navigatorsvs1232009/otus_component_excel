<?php

namespace Controllers\Rest\SCM;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\RequisiteTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Rest\RestException;
use CCrmOwnerType;
use CRestServer;
use DomainException;
use Repositories\CompanySupplyAreasRepository;
use Repositories\IncotermsPlacesRef;
use Repositories\MarketAreaRef;
use Repositories\ProductKeysRef;
use Services\Rest\SCM\CompanyUpdateRestService;

class CompanyController
{
    const OPERATOR_MAP = [
        'gt'  => '>',
        'gte' => '>=',
        'lt'  => '<',
        'lte' => '<=',
        'eq'  => '=',
        'neq' => '!=',
    ];

    /**
     * @param  array  $params
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws RestException
     * @throws SystemException
     */
    public static function list(array $params): array
    {
        try {
            $productKeysRef = ProductKeysRef::all();
            $companies = self::getCompanies(self::prepareFilter($params));
            $companyIds = array_column($companies, 'ID');
            $companiesSupplyAreas = self::getCompaniesSupplyAreas($companyIds);
            $incotermsPlaceRef = IncotermsPlacesRef::all();
            $marketAreaRef = MarketAreaRef::all();
            $companiesRequisites = self::getCompaniesRequisites($companyIds);

            return array_map(fn($company) => self::prepareCompanyItem($company, $productKeysRef, $companiesSupplyAreas, $incotermsPlaceRef, $marketAreaRef, $companiesRequisites), $companies);

        } catch (DomainException) {
            throw new RestException(CRestServer::STATUS_WRONG_REQUEST, 400);

        } catch (\Error) {
            throw new RestException(CRestServer::STATUS_INTERNAL, 500);

        } catch (Throwable) {
            throw new RestException(CRestServer::STATUS_INTERNAL, 500);
        }
    }

    /**
     * @param  array  $params
     *
     * @return bool
     * @throws RestException
     */
    public static function update(array $params): bool
    {
        return (new CompanyUpdateRestService())->run($params);
    }

    /**
     * @param  array  $params
     *
     * @return array
     */
    private static function prepareFilter(array $params):array
    {
        try {
            foreach ($params as $param => $values) {
                switch ($param) {
                    case 'modifiedAt':
                        foreach ($values as $operator => $value) {
                            if (empty(self::OPERATOR_MAP[$operator]) || empty($value)) {
                                continue;
                            }

                            list($value) = explode(' ', $value);
                            $date = date_create($value);
                            if (!$date) {
                                throw new DomainException('Invalid date param');
                            }

                            $filter[self::OPERATOR_MAP[$operator].'DATE_MODIFY'] = DateTime::createFromPhp($date);
                        }

                        break;

                    case 'id':
                        foreach ($values as $operator => $value) {
                            if (empty(self::OPERATOR_MAP[$operator]) || empty($value)) {
                                continue;
                            }

                            list($value) = explode(' ', $value);
                            $filter[self::OPERATOR_MAP[$operator].'ID'] = (int) $value;
                        }

                        break;
                }
            }

        } catch (Throwable $e) {
            throw $e;
        }

        return $filter ?? [];
    }

    /**
     * @param  array  $filter
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function getCompanies(array $filter = []): array
    {
        return CompanyTable::getList([
            'select' => [
                'ID',
                'TITLE',
                'UF_SHORT_TITLE',
                'RESPONSIBLE_NAME'      => 'ASSIGNED_BY.NAME',
                'RESPONSIBLE_LAST_NAME' => 'ASSIGNED_BY.LAST_NAME',
                'RESPONSIBLE_EMAIL'     => 'ASSIGNED_BY.EMAIL',
                'COMPANY_TYPE',
                'COMPANY_TYPE_NAME'     => 'COMPANY_TYPE_BY.NAME',
                'DATE_CREATE',
                'DATE_MODIFY',
                'UF_COUNTRY',
                'UF_COUNTRY_CODE'       => 'UF_COUNTRY_REF.DETAIL_TEXT',
                'UF_COUNTRY_NAME'       => 'UF_COUNTRY_REF.NAME',
                'UF_STATUS',
                'UF_STATUS_NAME'        => 'UF_STATUS_REF.NAME',
                'UF_ME_PARTNER_GUID',
                'UF_ME_CONTRAGENT_GUID',
                'COMPANY_EMAIL'         => 'EMAIL',
                'COMPANY_PHONE'         => 'PHONE',
                'UF_PRODUCT_KEY',
                'UF_QA_TRACE_CODE',
                'UF_SUPPLY_AREA_ID'
            ],
            'filter' => $filter,
            'runtime' => [
                'UF_STATUS_REF' => new ReferenceField(
                    'UF_STATUS_REF',
                    ElementTable::class,
                    ['=this.UF_STATUS' => 'ref.ID'],
                    ['join_type' => 'LEFT']
                ),
                'UF_COUNTRY_REF' => new ReferenceField(
                    'UF_COUNTRY_REF',
                    ElementTable::class,
                    ['=this.UF_COUNTRY' => 'ref.ID'],
                    ['join_type' => 'LEFT']
                ),
            ],
        ])->fetchAll();
    }

    /**
     * @param  array  $company
     *
     * @param  array  $productKeysRef
     * @param  array  $companiesSupplyAreas
     *
     * @param  array  $incotermsPlaceRef
     * @param  array  $marketAreaRef
     * @param  array  $companiesRequisites
     *
     * @return array
     */
    private static function prepareCompanyItem(
        array $company,
        array $productKeysRef,
        array $companiesSupplyAreas,
        array $incotermsPlaceRef,
        array $marketAreaRef,
        array $companiesRequisites
    ): array
    {
        $item = [
            'id'                => (int) $company['ID'],
            'title'             => empty($companiesRequisites[$company['ID']]['RQ_COMPANY_NAME']) ? $company['TITLE'] : $companiesRequisites[$company['ID']]['RQ_COMPANY_NAME'],
            'shortTitle'        => $company['UF_SHORT_TITLE'],
            'responsiblePerson' => null,
            'email'             => $company['COMPANY_EMAIL'],
            'phone'             => $company['COMPANY_PHONE'],
            'type'              => [
                'code' => $company['COMPANY_TYPE'],
                'name' => $company['COMPANY_TYPE_NAME'],
            ],
            'traceCode'       => $company['UF_QA_TRACE_CODE'],
            'productKey'      => array_map(
                fn ($productKeyId) => $productKeysRef[$productKeyId]['NAME'] ?? 'N/A',
                $company['UF_PRODUCT_KEY'] ?: []
            ),

            'supplyArea' => array_map(function ($companySupplyAreas) use ($incotermsPlaceRef, $marketAreaRef, $company) {
                # нет конкретной привязки => берём из компании
                if (empty($companySupplyAreas['PROPERTY_SUPPLY_AREA_ID_VALUE'])) {
                    $companySupplyAreas['PROPERTY_SUPPLY_AREA_ID_VALUE'] = $company['UF_SUPPLY_AREA_ID'];
                }

                return [
                    'shipmentBaseId' => (int) $incotermsPlaceRef[$companySupplyAreas['PROPERTY_INCOTERMS_PLACE_ID_VALUE']]['ID'] ?? 0,
                    'supplyAreas'    => array_map(function ($supplyAreaId) use ($marketAreaRef) {
                        return (int) $marketAreaRef[$supplyAreaId]['XML_ID'];
                    }, $companySupplyAreas['PROPERTY_SUPPLY_AREA_ID_VALUE'] ?: [])
                ];
            }, $companiesSupplyAreas[$company['ID']] ?? []),

            'country'           => null,
            'status'            => null,
            'mePartnerGuid'     => $company['UF_ME_PARTNER_GUID'],
            'meContragentGuid'  => $company['UF_ME_CONTRAGENT_GUID'] ?: [],
            'createdAt'         => $company['DATE_CREATE'] instanceof DateTime ? $company['DATE_CREATE']->format(DATE_ATOM) : null,
            'modifiedAt'        => $company['DATE_MODIFY'] instanceof DateTime ? $company['DATE_MODIFY']->format(DATE_ATOM) : null,
        ];

        if ( ! empty($company['RESPONSIBLE_EMAIL'])) {
            $item['responsiblePerson'] = [
                'name'     => $company['RESPONSIBLE_NAME'],
                'lastName' => $company['RESPONSIBLE_LAST_NAME'],
                'email'    => $company['RESPONSIBLE_EMAIL'],
            ];
        }

        if ( ! empty($company['UF_COUNTRY'])) {
            $item['country'] = [
                'code'     => $company['UF_COUNTRY_CODE'],
                'name'     => $company['UF_COUNTRY_NAME'],
            ];
        }

        if ( ! empty($company['UF_STATUS'])) {
            $item['status'] = [
                'code'     => $company['UF_STATUS'],
                'name'     => $company['UF_STATUS_NAME'],
            ];
        }

        return $item;
    }

    /**
     * @param  array  $companyIds
     *
     * @return array
     */
    private static function getCompaniesSupplyAreas(array $companyIds): array
    {
        foreach (CompanySupplyAreasRepository::all(
            'ID',
            ['PROPERTY_COMPANY_ID' => $companyIds],
            ['PROPERTY_COMPANY_ID', 'PROPERTY_SUPPLY_AREA_ID', 'PROPERTY_INCOTERMS_PLACE_ID']
        ) as $companySupplyArea) {
            $companiesSupplyAreas[$companySupplyArea['PROPERTY_COMPANY_ID_VALUE']][] = $companySupplyArea;
        }

        return $companiesSupplyAreas ?? [];
    }

    /**
     * @param  array  $companyIds
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function getCompaniesRequisites(array $companyIds): array
    {
        $dbResult = RequisiteTable::getList([
            'select' => ['ENTITY_ID', 'RQ_INN', 'RQ_COMPANY_NAME'],
            'filter' => ['ENTITY_TYPE_ID' => CCrmOwnerType::Company, 'ENTITY_ID' => $companyIds],
        ]);
        while ($row = $dbResult->fetch()) {
            $companiesRequisites[$row['ENTITY_ID']] = $row;
        }

        return $companiesRequisites ?? [];
    }
}
