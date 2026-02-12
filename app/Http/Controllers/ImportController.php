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
        } elseif ($type === 'recebimento') {
            return $this->exportRecebimento($files);
        } elseif ($type === 'expedicao') {
            return $this->exportExpedicao($files);
        }

        return back()->with('error', 'Tipo de exportação inválido.');
    }

    private function exportOmie(array $files)
    {
        $templatePath = base_path('resources/templates/Omie_Produtos_v1_9_5.xlsx');

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
            $templatePath = base_path('resources/templates/modeloImportacaoProduto.csv');

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

    private function exportRecebimento(array $files)
    {
        return $this->generateCsvExport($files, 'resources/templates/modeloImportacaoRecebimento.csv', 'Importacao_Recebimento_', function ($xml, $det, $headerColumns) {
            $prod = $det->prod;
            $rowData = [];
            
            // Commmon XML data extraction (using xpath on $xml is safe as ns is registered on $xml)
            $emit = $xml->xpath('//nfe:infNFe/nfe:emit') ?: $xml->xpath('//emit');
            $dest = $xml->xpath('//nfe:infNFe/nfe:dest') ?: $xml->xpath('//dest');
            $ide = $xml->xpath('//nfe:infNFe/nfe:ide') ?: $xml->xpath('//ide');
            $total = $xml->xpath('//nfe:infNFe/nfe:total/nfe:ICMSTot') ?: $xml->xpath('//total/ICMSTot');

            $nNF = !empty($ide) ? (string) ($ide[0]->nNF ?? '') : '';
            $serie = !empty($ide) ? (string) ($ide[0]->serie ?? '') : '';
            $destDoc = !empty($dest) ? (string) ($dest[0]->CNPJ ?? $dest[0]->CPF ?? '') : '';
            $vNF = !empty($total) ? (string) ($total[0]->vNF ?? '') : '';

             foreach ($headerColumns as $col) {
                $colName = trim($col);
                switch ($colName) {
                    case 'ID_EXTERNO':
                        $rowData[] = $nNF;
                        break;
                    case 'DESTINATARIO':
                        $rowData[] = $destDoc;
                        break;
                    case 'ARMAZEM':
                        $rowData[] = '[INFORMAR NO ARQUIVO]';
                        break;
                    case 'PRODUTO':
                        $rowData[] = (string) $prod->cProd;
                        break;
                    case 'QUANTIDADE':
                        $rowData[] = (string) $prod->qCom;
                        break;
                    case 'SERIE_ITEM':
                        $rowData[] = '';
                        break;
                    case 'LOTE_ITEM':
                        // Use property access instead of xpath to avoid namespace issues on $det
                        if (isset($prod->rastro) && isset($prod->rastro->nLote)) {
                            $rowData[] = (string) $prod->rastro->nLote;
                        } else {
                            // Try to extract from infAdProd if it contains "Lote:"
                            $infAdProd = (string)($det->infAdProd ?? '');
                            if (preg_match('/Lote:\s*([^\s]+)/i', $infAdProd, $matches)) {
                                $rowData[] = $matches[1];
                            } else {
                                $rowData[] = '';
                            }
                        }
                        break;
                    case 'PESO_ITEM':
                        $rowData[] = '';
                        break;
                    case 'VALOR_ITEM':
                        $rowData[] = (string) $prod->vUnCom;
                        break;
                    case 'DATA_FABRICACAO_ITEM':
                         if (isset($prod->rastro) && isset($prod->rastro->dFab)) {
                             $rowData[] = (string) $prod->rastro->dFab; 
                         } else {
                             $rowData[] = '';
                         }
                        break;
                    case 'DATA_VALIDADE_ITEM':
                        if (isset($prod->rastro) && isset($prod->rastro->dVal)) {
                             $rowData[] = (string) $prod->rastro->dVal;
                         } else {
                             $rowData[] = '';
                         }
                        break;
                    case 'CONTRATO':
                        $rowData[] = '[INFORMAR NO ARQUIVO]';
                        break;
                    case 'DATA_AGENDAMENTO':
                        $rowData[] = '';
                        break;
                    case 'NUMERO_PEDIDO':
                         $rowData[] = (string) ($prod->xPed ?? '');
                        break;
                    case 'NUMERO_N_F':
                        $rowData[] = $nNF;
                        break;
                    case 'SERIE_N_F':
                        $rowData[] = $serie;
                        break;
                    case 'VALOR_OPERACAO':
                         $rowData[] = $vNF;
                        break;
                    default:
                        $rowData[] = '';
                }
            }
            return $rowData;
        });
    }

    private function exportExpedicao(array $files)
    {
         return $this->generateCsvExport($files, 'resources/templates/modeloImportacaoExpedicao.csv', 'Importacao_Expedicao_', function ($xml, $det, $headerColumns) {
            $prod = $det->prod;
            $rowData = [];
            
            // Commmon XML data extraction
            $dest = $xml->xpath('//nfe:infNFe/nfe:dest') ?: $xml->xpath('//dest');
            $ide = $xml->xpath('//nfe:infNFe/nfe:ide') ?: $xml->xpath('//ide');
            $total = $xml->xpath('//nfe:infNFe/nfe:total/nfe:ICMSTot') ?: $xml->xpath('//total/ICMSTot');

            $nNF = !empty($ide) ? (string) ($ide[0]->nNF ?? '') : '';
            $serie = !empty($ide) ? (string) ($ide[0]->serie ?? '') : '';
            $destDoc = !empty($dest) ? (string) ($dest[0]->CNPJ ?? $dest[0]->CPF ?? '') : '';
            $vNF = !empty($total) ? (string) ($total[0]->vNF ?? '') : '';

             foreach ($headerColumns as $col) {
                $colName = trim($col);
                switch ($colName) {
                    case 'ID_EXTERNO':
                        $rowData[] = $nNF;
                        break;
                    case 'DESTINATARIO':
                        $rowData[] = $destDoc;
                        break;
                    case 'ARMAZEM':
                        $rowData[] = '[INFORMAR NO ARQUIVO]';
                        break;
                    case 'PRODUTO':
                        $rowData[] = (string) $prod->cProd;
                        break;
                    case 'QUANTIDADE':
                        $rowData[] = (string) $prod->qCom;
                        break;
                    case 'SERIE_ITEM':
                        $rowData[] = '';
                        break;
                    case 'LOTE_ITEM':
                        if (isset($prod->rastro) && isset($prod->rastro->nLote)) {
                            $rowData[] = (string) $prod->rastro->nLote;
                        } else {
                             // Try to extract from infAdProd if it contains "Lote:"
                             $infAdProd = (string)($det->infAdProd ?? '');
                             if (preg_match('/Lote:\s*([^\s]+)/i', $infAdProd, $matches)) {
                                 $rowData[] = $matches[1];
                             } else {
                                 $rowData[] = '';
                             }
                        }
                        break;
                    case 'PESO_ITEM':
                        $rowData[] = '';
                        break;
                    case 'VALOR_ITEM':
                        $rowData[] = (string) $prod->vUnCom;
                        break;
                     case 'DATA_FABRICACAO_ITEM':
                         if (isset($prod->rastro) && isset($prod->rastro->dFab)) {
                             $rowData[] = (string) $prod->rastro->dFab;
                         } else {
                             $rowData[] = '';
                         }
                        break;
                    case 'DATA_VALIDADE_ITEM':
                        if (isset($prod->rastro) && isset($prod->rastro->dVal)) {
                             $rowData[] = (string) $prod->rastro->dVal;
                         } else {
                             $rowData[] = '';
                         }
                        break;
                    case 'CONTRATO':
                        $rowData[] = '[INFORMAR NO ARQUIVO]';
                        break;
                    case 'DATA_AGENDAMENTO':
                        $rowData[] = '';
                        break;
                    case 'NUMERO_PEDIDO':
                        $rowData[] = (string) ($prod->xPed ?? '');
                        break;
                    case 'NUMERO_N_F':
                        $rowData[] = $nNF;
                        break;
                    case 'SERIE_N_F':
                        $rowData[] = $serie;
                        break;
                    case 'VALOR_OPERACAO':
                        $rowData[] = $vNF;
                        break;
                    case 'INDICADOR_TRANSFERENCIA':
                        $rowData[] = 'N'; // Padrão N
                        break;
                    case 'ARMAZEM_DESTINO_TRANSFERENCIA':
                        $rowData[] = '';
                        break;
                    case 'RECEBIMENTO_ETAPA_REFERENCIA':
                        $rowData[] = '';
                        break;
                    default:
                        $rowData[] = '';
                }
            }
            return $rowData;
        });
    }

    private function generateCsvExport(array $files, string $templateRelativePath, string $outputPrefix, callable $rowMapper)
    {
         try {
            $templatePath = base_path($templateRelativePath);

            if (!file_exists($templatePath)) {
                throw new \Exception('Modelo CSV não encontrado no servidor em: ' . $templatePath);
            }

            // Read template
            $templateContent = file_get_contents($templatePath);
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

            if (empty($headerLine)) {
                throw new \Exception('Cabeçalho não encontrado no modelo CSV.');
            }

            $headerColumns = explode(';', $headerLine);
            $csvContent = $templateContent;

            if (!str_ends_with($csvContent, "\n")) {
                $csvContent .= "\n";
            }

            $totalItems = 0;

            foreach ($files as $file) {
                $xml = simplexml_load_file($file->getRealPath());
                if (!$xml) continue;

                $ns = $xml->getNamespaces(true);
                $xml->registerXPathNamespace('nfe', $ns[''] ?? 'http://www.portalfiscal.inf.br/nfe');

                $dets = $xml->xpath('//nfe:infNFe/nfe:det') ?: $xml->xpath('//det');

                foreach ($dets as $det) {
                    $totalItems++;
                    $rowData = $rowMapper($xml, $det, $headerColumns);
                    $csvContent .= implode(';', $rowData) . "\n";
                }
            }

            if ($totalItems === 0) {
                throw new \Exception('Nenhum item encontrado nos arquivos XML.');
            }

            $fileName = $outputPrefix . date('Y-m-d_H-i-s') . '.csv';
            $tempPath = storage_path('app/' . $fileName);
            file_put_contents($tempPath, $csvContent);

            return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
             \Log::error('Erro exportacao: ' . $e->getMessage());
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
