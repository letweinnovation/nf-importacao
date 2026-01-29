<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$inputFile = 'Omie_Produtos_v1_9_5.xlsx';

try {
    $spreadsheet = IOFactory::load($inputFile);
    $worksheet = $spreadsheet->getActiveSheet();

    echo "Reading headers from row 1:\n";

    $highestColumn = $worksheet->getHighestColumn();
    $headers = [];

    // Iterate through columns A to highest
    foreach (range('A', $highestColumn) as $col) {
        $val = $worksheet->getCell($col . '1')->getValue();
        if ($val) {
            echo "Col $col: $val\n";
            $headers[$val] = $col;
        }
    }

    echo "\n--- Key Columns ---\n";
    $keys = ['Código do Produto', 'Descrição', 'Código NCM'];
    foreach ($keys as $key) {
        $found = false;
        foreach ($headers as $headerVal => $col) {
            // Loose comparison for headers usually good
            if (stripos($headerVal, $key) !== false) {
                echo "Found '$key' at Column: $col (Header: $headerVal)\n";
                $found = true;
                break;
            }
        }
        if (!$found)
            echo "WARNING: Could not find column for '$key'\n";
    }

} catch (Exception $e) {
    echo 'Error loading file: ', $e->getMessage();
}
