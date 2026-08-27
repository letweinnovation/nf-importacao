<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

function analyze_file($filename) {
    echo "Analyzing $filename:\n";
    try {
        $spreadsheet = IOFactory::load($filename);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        for ($rowIdx = 1; $rowIdx <= 10; $rowIdx++) {
            $hasData = false;
            $rowStr = "Row $rowIdx: ";
            for ($col = 1; $col <= 20; ++$col) {
                $val = $worksheet->getCell([$col, $rowIdx])->getValue();
                if (!empty($val)) {
                    $hasData = true;
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $rowStr .= "[$colLetter] $val | ";
                }
            }
            if ($hasData) {
                echo $rowStr . "\n";
            }
        }
    } catch (Exception $e) {
        echo 'Error: ', $e->getMessage(), "\n";
    }
    echo "====================================\n";
}

ini_set('memory_limit', '2048M');
analyze_file('resources/templates/Omie_Produtos_v1_9_5.xlsx');
analyze_file('resources/templates/Omie_Produtos_v1_9_6.xlsx');
