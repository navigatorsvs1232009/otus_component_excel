<?php

namespace Services\Infrastructure;

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class IblockConstantsHandler
{
    private string $filePath;

    /**
     * @param  string  $filePath
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function run(): void
    {
        $iblocks = $this->getIblocks();
        $fileContents = $this->getFileContents($iblocks);
        file_put_contents($this->filePath, $fileContents);
    }

    /**
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getIblocks(): array
    {
        return IblockTable::getList([
            'select' => ['ID', 'CODE'],
            'filter' => ['!CODE' => false],
        ])->fetchAll();
    }

    /**
     * @param  array  $iblocks
     *
     * @return string
     */
    private function getFileContents(array $iblocks): string
    {
        $rows = join("\n",
            array_map(
                fn($iblock) => "const {$iblock['CODE']}_IBLOCK_ID = {$iblock['ID']};",
                $iblocks
            )
        );

        return <<<PHP
<?php

// Generated automatically. Not intended for manual edit.

{$rows}

PHP;
    }
}
