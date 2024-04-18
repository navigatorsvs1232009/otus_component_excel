
<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadSheet = new Spreadsheet();
$writer = new Xlsx($spreadSheet);
$activeSheet = $spreadSheet->getActiveSheet();

$column = 'A';
foreach ($arResult['COLUMNS'] as $value) {
    $activeSheet->setCellValue($column.'1', $value['name']); // Убрали лишний параметр $value['value']
    $column++;
}

$row = 2;
foreach ($arResult['LIST'] as $value) {
    $column = 'A'; // Сбросим столбец в начало перед каждой строкой
    foreach ($value['data'] as $itemText) {
        $activeSheet->setCellValue($column.$row, $itemText);
        $column++;
    }
    $row++;
}

$APPLICATION->RestartBuffer();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
$writer->save('php://output');

exit();
