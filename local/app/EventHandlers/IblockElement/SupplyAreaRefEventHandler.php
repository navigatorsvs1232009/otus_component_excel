<?php

namespace EventHandlers\IblockElement;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use EventHandlers\IblockElement\Interfaces\OnBeforeUpdateEventHandlerInterface;
use Models\SCM\SupplyAreaRefPropertyValuesTable;
use Repositories\StoresRepository;

class SupplyAreaRefEventHandler implements OnBeforeUpdateEventHandlerInterface
{
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
        $supplyAreaDuplicatesByStock = $this->getSupplyAreaDuplicatesByStock($element);
        if (!empty($supplyAreaDuplicatesByStock)) {
            $stocksRef = StoresRepository::all();
            $errorMessage = join("\n", array_map(
                fn($duplicate) => "{$stocksRef[$duplicate['SINGLE_STOCK_ID']]['TITLE']} is already used in Supply area: {$duplicate['NAME']}",
                $supplyAreaDuplicatesByStock
            ));
            $GLOBALS['APPLICATION']->ThrowException($errorMessage);

            return false;
        }

        return null;
    }

    /**
     * @param  array  $element
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getSupplyAreaDuplicatesByStock(array $element): array
    {
        $stockIdPropertyId = SupplyAreaRefPropertyValuesTable::getPropertyId('STOCK_ID');
        $stockIds = array_values(
            array_map(
                fn($item) => $item['VALUE'],
                $element['PROPERTY_VALUES'][$stockIdPropertyId]
            )
        );
        if (empty($stockIds)) {
            return [];
        }

        $dbResult = SupplyAreaRefPropertyValuesTable::query()
            ->setSelect([
                'ID'              => 'IBLOCK_ELEMENT_ID',
                'NAME'            => 'ELEMENT.NAME',
                'SINGLE_STOCK_ID' => 'STOCK_ID|SINGLE.VALUE',
            ])
            ->whereNot('ID', $element['ID'])
            ->whereIn('SINGLE_STOCK_ID', $stockIds)
            ->exec();
        while ($row = $dbResult->fetch()) {
            $duplicates[] = $row;
        }

        return $duplicates ?? [];
    }
}
