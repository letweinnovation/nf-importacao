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

            $colMap = [
                'cProd' => 'C',   // Código do Produto
                'xProd' => 'D',   // Descrição do Produto
                'NCM' => 'E',     // Código NCM
                'uCom' => 'I',    // Unidade
            ];

            $currentRow = 6;

            foreach ($files as $file) {
                $xml = simplexml_load_file($file->getRealPath());
                if (!$xml)
                    continue;

                $ns = $xml->getNamespaces(true);
                $xml->registerXPathNamespace('nfe', $ns[''] ?? 'http://www.portalfiscal.inf.br/nfe');

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
            $writer->setPreCalculateFormulas(false);

            $fileName = 'Importacao_Omie_' . date('Y-m-d_H-i-s') . '.xlsx';
            $tempPath = storage_path('app/' . $fileName);

            $writer->save($tempPath);

            return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);

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
                throw new \Exception('Modelo CSV não encontrado no servidor em: ' . $templatePath);
            }

            // Read template
            $templateContent = file_get_contents($templatePath);

            // Remove BOM if present (prevents header detection failure if file starts with UTF-8 BOM)
            $templateContent = preg_replace('/^\xEF\xBB\xBF/', '', $templateContent);

            // Normalize endings
            $templateContent = str_replace("\r\n", "\n", $templateContent);

            // Extract header columns for mapping
            $lines = explode("\n", $templateContent);
            $headerLine = '';
            foreach ($lines as $line) {
                $trimmed = trim($line);
                // Skip empty lines and true comments (starts with # after trimming)
                if ($trimmed !== '' && strpos($trimmed, '#') !== 0) {
                    $headerLine = $trimmed;
                    break;
                }
            }

            if (empty($headerLine)) {
                throw new \Exception('Cabeçalho (linha de colunas) não encontrado no modelo CSV.');
            }

            $headerColumns = explode(';', $headerLine);
            $csvContent = $templateContent;

            // Ensure template ends with a newline to append data
            if (!str_ends_with($csvContent, "\n")) {
                $csvContent .= "\n";
            }

            $totalProducts = 0;

            foreach ($files as $file) {
                $xml = simplexml_load_file($file->getRealPath());
                if (!$xml)
                    continue;

                $ns = $xml->getNamespaces(true);
                $xml->registerXPathNamespace('nfe', $ns[''] ?? 'http://www.portalfiscal.inf.br/nfe');

                // Get CNPJ (Issuer)
                $emit = $xml->xpath('//nfe:infNFe/nfe:emit') ?: $xml->xpath('//emit');
                $cnpj = !empty($emit) ? (string) ($emit[0]->CNPJ ?? $emit[0]->CPF ?? '') : '';

                // Get Products
                $dets = $xml->xpath('//nfe:infNFe/nfe:det') ?: $xml->xpath('//det');

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
            }

            if ($totalProducts === 0) {
                throw new \Exception('O sistema não encontrou nenhum produto (<det>) nos arquivos XML fornecidos.');
            }

            $fileName = 'Importacao_GTI_' . date('Y-m-d_H-i-s') . '.csv';
            $tempPath = storage_path('app/' . $fileName);
            file_put_contents($tempPath, $csvContent);

            return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Log::error('Erro exportacao GTI: ' . $e->getMessage());
            return response()->json(['message' => 'Ocorreu um erro: ' . $e->getMessage()], 500);
        }
    }

    public function index()
    {
        return view('welcome');
    }

    public function getHistory()
    {
        return response()->json([]);
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
