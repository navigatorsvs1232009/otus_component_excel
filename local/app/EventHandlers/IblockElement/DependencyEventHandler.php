<?php

namespace EventHandlers\IblockElement;

use CIBlockElement;
use EventHandlers\IblockElement\Interfaces\OnAfterDeleteEventHandlerInterface;
use EventHandlers\IblockElement\Interfaces\OnAfterSetPropertyValuesEventHandlerInterface;

class DependencyEventHandler implements OnAfterSetPropertyValuesEventHandlerInterface, OnAfterDeleteEventHandlerInterface
{
    /**
     * @param $elementId
     * @param $propertyValues
     */
    public function onAfterSetPropertyValues($elementId, $propertyValues): void
    {
        # найдём тек. связь. Если нет привязки к компании -> удалим
        $dependency = $this->getDependency($elementId);
        if (empty($dependency['PROPERTY_COMPANY_VALUE'])) {
            CIBlockElement::Delete($dependency['ID']);
            return;
        }

        $dependencyRef = $this->getDependencyRef();

        # попробуем найти перекрёстную запись
        if (!empty($dependency['PROPERTY_OPPOSITE_DEPENDENCY_ID_VALUE'])) {
            $oppositeDependency = CIBlockElement::GetList(
                [],
                ['ID' => $dependency['PROPERTY_OPPOSITE_DEPENDENCY_ID_VALUE']],
                false,
                false,
                ['ID', 'PROPERTY_COMPANY', 'PROPERTY_AFFILIATED_COMPANY', 'PROPERTY_AFFILIATED_COMPANY_TYPE']
            )->Fetch();
        }

        # нет перекрёстной записи - создадим
        if (empty($oppositeDependency)) {
            $oppositeDependencyId = (new CIBlockElement())->Add($f = [
                'NAME'            => '-',
                'IBLOCK_ID'       => COMPANY_DEPENDENCIES_IBLOCK_ID,
                'PROPERTY_VALUES' => [
                    'COMPANY'                 => $dependency['PROPERTY_AFFILIATED_COMPANY_VALUE'],
                    'AFFILIATED_COMPANY'      => $dependency['PROPERTY_COMPANY_VALUE'],
                    'AFFILIATED_COMPANY_TYPE' => $dependencyRef[$dependency['PROPERTY_AFFILIATED_COMPANY_TYPE_VALUE']],
                    'OPPOSITE_DEPENDENCY_ID'  => $dependency['ID'],
                ],
            ]);

            CIBlockElement::SetPropertyValuesEx(
                $dependency['ID'],
                COMPANY_DEPENDENCIES_IBLOCK_ID,
                ['OPPOSITE_DEPENDENCY_ID' => $oppositeDependencyId]
            );

        } elseif ($oppositeDependency['PROPERTY_AFFILIATED_COMPANY_TYPE_VALUE'] != $dependencyRef[$dependency['PROPERTY_AFFILIATED_COMPANY_TYPE_VALUE']]) {

            CIBlockElement::SetPropertyValuesEx(
                $dependency['PROPERTY_OPPOSITE_DEPENDENCY_ID_VALUE'],
                COMPANY_DEPENDENCIES_IBLOCK_ID,
                ['AFFILIATED_COMPANY_TYPE' => $dependencyRef[$dependency['PROPERTY_AFFILIATED_COMPANY_TYPE_VALUE']]]
            );
        }
    }

    /**
     * @param $element
     */
    public function onAfterDelete($element): void
    {
        $oppositeDependency = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => COMPANY_DEPENDENCIES_IBLOCK_ID, 'PROPERTY_OPPOSITE_DEPENDENCY_ID' => $element['ID']],
            false,
            ['nTopCount' => 1],
            ['ID']
        )->Fetch();
        if (!empty($oppositeDependency)) {
            CIBlockElement::Delete($oppositeDependency['ID']);
        }
    }

    /**
     * @param  int  $dependencyId
     *
     * @return array
     */
    private function getDependency(int $dependencyId): array
    {
        return CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => COMPANY_DEPENDENCIES_IBLOCK_ID, 'ID' => $dependencyId],
            false,
            false,
            ['ID', 'PROPERTY_COMPANY', 'PROPERTY_AFFILIATED_COMPANY', 'PROPERTY_AFFILIATED_COMPANY_TYPE', 'PROPERTY_OPPOSITE_DEPENDENCY_ID']
        )->Fetch() ?: [];
    }

    /**
     * @return array
     */
    private function getDependencyRef(): array
    {
        $dbResult = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => COMPANY_DEPENDENCIES_REF_IBLOCK_ID],
            false,
            false,
            ['ID', 'PROPERTY_OPPOSITE']
        );
        while ($row = $dbResult->Fetch()) {
            $dependencyRef[$row['ID']] = $row['PROPERTY_OPPOSITE_VALUE'];
        }

        return $dependencyRef ?? [];
    }
}
