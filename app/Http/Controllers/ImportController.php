<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function update(Request $request, string $type)
    {
        // Validation (can be expanded later)
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|file|mimes:xml',
        ]);

        $files = $request->file('files');

        if ($type === 'omie') {
            return $this->exportOmie($files);
        } elseif ($type === 'gti') {
            return $this->exportGti($files);
        }

        return back()->with('error', 'Tipo de exportação inválido.');
    }

    private function exportOmie(array $files)
    {
        $templatePath = base_path('Omie_Produtos_v1_9_5.xlsx');

        if (!file_exists($templatePath)) {
            throw new \Exception('Modelo Excel não encontrado no servidor.');
        }

        // Increase memory limit for large imports
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Template Omie_Produtos_v1_9_5.xlsx has headers in row 5
            // Fixed column mapping based on template structure:
            // Column C = "Código do Produto" (cProd)
            // Column D = "Descrição do Produto" (xProd)  
            // Column E = "Código NCM" (NCM)
            // Column F = "CEST" (cest)
            // Column G = "GTIN/EAN" (cEAN or cEANTrib)
            // Column I = "Unidade" (uCom)
            $colMap = [
                'cProd' => 'C',   // Código do Produto
                'xProd' => 'D',   // Descrição do Produto
                'NCM' => 'E',     // Código NCM
                'uCom' => 'I',    // Unidade
            ];

            // Data must start at row 6 (row 5 is the header with column names)
            $currentRow = 6;

            foreach ($files as $file) {
                $xml = simplexml_load_file($file->getRealPath());
                $ns = $xml->getNamespaces(true);
                $xml->registerXPathNamespace('nfe', $ns[''] ?? 'http://www.portalfiscal.inf.br/nfe');

                // Try with and without namespace
                $dets = $xml->xpath('//nfe:infNFe/nfe:det');
                if (empty($dets)) {
                    $dets = $xml->xpath('//det');
                }

                foreach ($dets as $det) {
                    $prod = $det->prod;

                    if (isset($colMap['cProd']))
                        $sheet->setCellValue($colMap['cProd'] . $currentRow, (string) $prod->cProd);
                    if (isset($colMap['xProd']))
                        $sheet->setCellValue($colMap['xProd'] . $currentRow, (string) $prod->xProd);
                    if (isset($colMap['NCM']))
                        $sheet->setCellValue($colMap['NCM'] . $currentRow, (string) $prod->NCM);
                    if (isset($colMap['uCom']))
                        $sheet->setCellValue($colMap['uCom'] . $currentRow, (string) $prod->uCom);

                    $currentRow++;
                }
            }

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            // Disable pre-calculation to avoid corruption issues with complex files
            $writer->setPreCalculateFormulas(false);

            $fileName = 'Importacao_Omie_' . date('Y-m-d_H-i-s') . '.xlsx';
            $tempPath = storage_path('app/' . $fileName);

            $writer->save($tempPath);

            // Update Session History
            $history = session()->get('generated_files', []);
            $history[] = $fileName;
            session()->put('generated_files', $history);

            // CHANGED: Do not delete after send, so we can keep history
            return response()->download($tempPath);

        } catch (\Exception $e) {
            \Log::error('Erro importacao: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao processar arquivos: ' . $e->getMessage()], 500);
        }
    }

    private function exportGti(array $files)
    {
        try {
            $templatePath = base_path('modeloImportacaoProduto.csv');

            if (!file_exists($templatePath)) {
                throw new \Exception('Modelo CSV não encontrado no servidor.');
            }

            // Read template to get header structure
            $templateContent = file_get_contents($templatePath);
            $lines = explode("\n", $templateContent);

            // Find the header line (last non-comment line)
            $headerLine = '';
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line) && $line[0] !== '#') {
                    $headerLine = $line;
                    break;
                }
            }

            if (empty($headerLine)) {
                throw new \Exception('Cabeçalho do modelo CSV não encontrado.');
            }

            // Parse header columns
            $headerColumns = explode(';', $headerLine);
            $columnCount = count($headerColumns);

            // Start building CSV content
            $csvContent = $headerLine . "\n";

            foreach ($files as $file) {
                $xml = simplexml_load_file($file->getRealPath());
                $ns = $xml->getNamespaces(true);
                $xml->registerXPathNamespace('nfe', $ns[''] ?? 'http://www.portalfiscal.inf.br/nfe');

                // Get CNPJ from emitente (issuer)
                $emit = $xml->xpath('//nfe:infNFe/nfe:emit');
                if (empty($emit)) {
                    $emit = $xml->xpath('//emit');
                }
                $cnpj = '';
                if (!empty($emit)) {
                    $cnpj = (string) ($emit[0]->CNPJ ?? $emit[0]->CPF ?? '');
                }

                // Get products
                $dets = $xml->xpath('//nfe:infNFe/nfe:det');
                if (empty($dets)) {
                    $dets = $xml->xpath('//det');
                }

                foreach ($dets as $det) {
                    $prod = $det->prod;

                    // Build row data based on template columns
                    $rowData = [];
                    foreach ($headerColumns as $col) {
                        $col = trim($col);
                        switch ($col) {
                            case 'FORNECEDOR_UNICO':
                                $rowData[] = $cnpj;
                                break;
                            case 'NOME':
                                $rowData[] = (string) $prod->xProd;
                                break;
                            case 'TIPO':
                                $rowData[] = 'INTEIRO';
                                break;
                            case 'ID_EXTERNO':
                                $rowData[] = (string) $prod->cProd;
                                break;
                            case 'LIMITE_PALLET_VIRTUAL':
                                $rowData[] = '10000';
                                break;
                            default:
                                $rowData[] = ''; // Empty for other columns
                        }
                    }

                    $csvContent .= implode(';', $rowData) . "\n";
                }
            }

            $fileName = 'Importacao_GTI_' . date('Y-m-d_H-i-s') . '.csv';
            $tempPath = storage_path('app/' . $fileName);

            file_put_contents($tempPath, $csvContent);

            // Update Session History
            $history = session()->get('generated_files', []);
            $history[] = $fileName;
            session()->put('generated_files', $history);

            return response()->download($tempPath, $fileName, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro exportacao GTI: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao processar arquivos: ' . $e->getMessage()], 500);
        }
    }

    public function index()
    {
        // Delete files from session before clearing it
        $sessionFiles = session()->get('generated_files', []);
        foreach ($sessionFiles as $filename) {
            $path = storage_path('app/' . $filename);
            if (file_exists($path)) {
                @unlink($path); // Silently try to delete
            }
        }

        // Clear history on page reload
        session()->forget('generated_files');
        return view('welcome');
    }

    public function getHistory()
    {
        $sessionFiles = session()->get('generated_files', []);
        $files = [];

        foreach ($sessionFiles as $filename) {
            $path = storage_path('app/' . $filename);
            if (file_exists($path)) {
                $files[] = $path;
            }
        }

        // Reverse order (newest first)
        $files = array_reverse($files);

        $history = [];
        foreach ($files as $file) {
            $history[] = [
                'name' => basename($file),
                'size' => $this->formatBytes(filesize($file)),
                'date' => date('d/m/Y H:i:s', filemtime($file)),
                'url' => route('download.file', ['filename' => basename($file)])
            ];
        }

        return response()->json($history);
    }

    public function downloadFile($filename)
    {
        $path = storage_path('app/' . $filename);
        if (!file_exists($path)) {
            abort(404);
        }
        return response()->download($path);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
