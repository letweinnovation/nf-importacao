<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ChunkReadFilter implements IReadFilter
{
    private $startRow = 0;
    private $endRow = 0;

    public function setRows($startRow, $chunkSize)
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize;
    }

    public function readCell(string $column, int $row, string $worksheetName = ''): bool
    {
        if ($row >= $this->startRow && $row < $this->endRow) {
            return true;
        }
        return false;
    }
}

$excelFile = 'Omie_Produtos_v1_9_5.xlsx';
$xmlFile = 'XML NF.3285  DRESS.xml';

echo "--- Analyzing XML ---\n";
if (file_exists($xmlFile)) {
    $xml = simplexml_load_file($xmlFile);
    $ns = $xml->getNamespaces(true);
    $xml->registerXPathNamespace('nfe', $ns[''] ?? 'http://www.portalfiscal.inf.br/nfe');

    // Find first det/prod
    $det = $xml->xpath('//nfe:infNFe/nfe:det[1]');

    if (empty($det)) {
        // Try without namespace if failed
        $det = $xml->xpath('//det[1]');
    }

    if (!empty($det)) {
        $prod = $det[0]->prod;
        echo "Found Product:\n";
        echo "cProd: " . ($prod->cProd ?? 'Not Found') . "\n";
        echo "xProd: " . ($prod->xProd ?? 'Not Found') . "\n";
        echo "NCM: " . ($prod->NCM ?? 'Not Found') . "\n";
    } else {
        echo "Could not find <det> tag in XML.\n";
    }
} else {
    echo "XML file not found.\n";
}

echo "\n--- Analyzing Excel Headers ---\n";
try {
    $reader = IOFactory::createReaderForFile($excelFile);
    $reader->setReadDataOnly(true);

    // Create a filter to read first 10 rows
    $chunkFilter = new ChunkReadFilter();
    $chunkFilter->setRows(1, 10);
    $reader->setReadFilter($chunkFilter);

    $spreadsheet = $reader->load($excelFile);
    $worksheet = $spreadsheet->getActiveSheet();

    $headers = [];
    $highestColumn = $worksheet->getHighestColumn();
    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

    echo "Scanning first 10 rows...\n";

    for ($row = 1; $row <= 10; ++$row) {
        $foundInRow = false;
        for ($col = 1; $col <= $highestColumnIndex; ++$col) {
            $colString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $cell = $worksheet->getCell($colString . $row);
            $val = $cell->getValue();

            if ($val) {
                echo "Row $row, Col $colString: $val\n";
                $foundInRow = true;
                if ($row === 1) {
                    // Still save row 1 as main headers just in case
                    $headers[$val] = $colString;
                }
            }
        }
        if ($foundInRow)
            echo "--- End of Row $row ---\n";
    }

} catch (Exception $e) {
    echo 'Error loading Excel: ', $e->getMessage();
}
