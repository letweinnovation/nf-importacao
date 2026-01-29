<?php

// Mocking some Laravel functions and environment
function base_path($path = '')
{
    return __DIR__ . ($path ? '/' . $path : '');
}
function storage_path($path = '')
{
    return __DIR__ . '/storage/' . ($path ? '/' . $path : '');
}

// Ensure storage exists
if (!is_dir(__DIR__ . '/storage'))
    mkdir(__DIR__ . '/storage');

// Use real files
$xmlFile = __DIR__ . '/XML NF.3285  DRESS.xml';
$templatePath = __DIR__ . '/modeloImportacaoProduto.csv';

if (!file_exists($xmlFile)) {
    die("XML real não encontrado em: $xmlFile\n");
}

// The logic from ImportController
try {
    if (!file_exists($templatePath)) {
        die("Modelo CSV não encontrado em: $templatePath\n");
    }

    $templateContent = file_get_contents($templatePath);

    // REMOVE BOM FIX
    $templateContent = preg_replace('/^\xEF\xBB\xBF/', '', $templateContent);

    $templateContent = str_replace("\r\n", "\n", $templateContent);

    $lines = explode("\n", $templateContent);
    $headerLine = '';
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed !== '' && strpos($trimmed, '#') !== 0) {
            $headerLine = $trimmed;
            break;
        }
    }

    echo "Header detected: " . $headerLine . "\n";

    $headerColumns = explode(';', $headerLine);
    $csvContent = $templateContent;
    if (!str_ends_with($csvContent, "\n")) {
        $csvContent .= "\n";
    }

    $totalProducts = 0;

    // Simulate $files loop
    $xml = simplexml_load_file($xmlFile);
    $ns = $xml->getNamespaces(true);
    $xml->registerXPathNamespace('nfe', $ns[''] ?? 'http://www.portalfiscal.inf.br/nfe');

    $emit = $xml->xpath('//nfe:infNFe/nfe:emit') ?: $xml->xpath('//emit');
    $cnpj = !empty($emit) ? (string) ($emit[0]->CNPJ ?? $emit[0]->CPF ?? '') : '';
    echo "CNPJ found: " . $cnpj . "\n";

    $dets = $xml->xpath('//nfe:infNFe/nfe:det') ?: $xml->xpath('//det');
    echo "Items found in XML: " . count($dets) . "\n";

    foreach ($dets as $det) {
        $totalProducts++;
        $prod = $det->prod;
        $rowData = [];

        foreach ($headerColumns as $col) {
            $colName = trim($col);
            switch ($colName) {
                case 'FORNECEDOR':
                case 'FORNECEDOR_UNICO':
                    $rowData[] = $cnpj;
                    break;
                case 'NOME':
                    $rowData[] = (string) $prod->xProd;
                    break;
                case 'DESCRICAO':
                    $rowData[] = '';
                    break;
                case 'TIPO':
                    $rowData[] = 'INTEIRO';
                    break;
                case 'ID_EXTERNO':
                    $rowData[] = (string) $prod->cProd;
                    break;
                case 'LIMITE_PALL':
                case 'LIMITE_PALLET_VIRTUAL':
                    $rowData[] = '10000';
                    break;
                default:
                    $rowData[] = '';
            }
        }
        $csvContent .= implode(';', $rowData) . "\n";
    }

    echo "Total products added to CSV: " . $totalProducts . "\n";

    $outPath = __DIR__ . '/test_output_real.csv';
    file_put_contents($outPath, $csvContent);
    echo "Output saved to test_output_real.csv\n";

    if ($totalProducts > 0 && strlen($csvContent) > strlen($templateContent)) {
        echo "VERDICT: SUCCESS. Content appended to template.\n";
    } else {
        echo "VERDICT: FAILURE. Check if tags <det> and <prod> exist in XML.\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
