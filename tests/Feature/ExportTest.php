<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportTest extends TestCase
{
    public function test_export_recebimento()
    {
        Storage::fake('local');

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?><nfeProc xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00"><NFe xmlns="http://www.portalfiscal.inf.br/nfe"><infNFe Id="NFe35251244893410002470550300000162051282898692" versao="4.00"><ide><cUF>35</cUF><cNF>28289869</cNF><natOp>VENDA</natOp><mod>55</mod><serie>30</serie><nNF>16205</nNF><dhEmi>2025-12-15T14:35:00-03:00</dhEmi><dhSaiEnt>2025-12-15T14:35:00-03:00</dhSaiEnt><tpNF>1</tpNF><idDest>1</idDest><cMunFG>3549805</cMunFG><tpImp>1</tpImp><tpEmis>1</tpEmis><cDV>2</cDV><tpAmb>1</tpAmb><finNFe>1</finNFe><indFinal>0</indFinal><indPres>0</indPres><procEmi>0</procEmi><verProc>12.1.2410 | 3.0</verProc></ide><emit><CNPJ>44893410002470</CNPJ><xNome>USINA FORTALEZA INDUSTRIA E COMERCIO DE MASSA FINA LTDA</xNome><xFant>USINA FORTALEZA</xFant><enderEmit><xLgr>RUA VITORIO GASPARO</xLgr><nro>120</nro><xBairro>MIN DISTRITO ADAIL VETORASSO</xBairro><cMun>3549805</cMun><xMun>SAO JOSE DO RIO PRETO</xMun><UF>SP</UF><CEP>15046768</CEP><cPais>1058</cPais><xPais>BRASIL</xPais></enderEmit><IE>124244132115</IE><CRT>3</CRT></emit><dest><CNPJ>23743831000159</CNPJ><xNome>CALHAS BORGES &amp; BORGES LTDA</xNome><enderDest><xLgr>R VICENTE LOMBARDI</xLgr><nro>40</nro><xBairro>VILA SANTA EDWIRGES</xBairro><cMun>3549102</cMun><xMun>SAO JOAO DA BOA VISTA</xMun><UF>SP</UF><CEP>13874227</CEP><cPais>1058</cPais><xPais>BRASIL</xPais><fone>1936231715</fone></enderDest><indIEDest>1</indIEDest><IE>639109843117</IE><email>tatiborginha@hotmil.com;usinafortaleza@usinafortaleza.spedco</email></dest><det nItem="1"><prod><cProd>10099</cProd><cEAN>7891605100997</cEAN><xProd>CARTUCHO PU 40 FORTALEZA CINZA 400G</xProd><NCM>35061090</NCM><cBenef></cBenef><CFOP>5106</CFOP><uCom>UN</uCom><qCom>360.0000</qCom><vUnCom>10.7511111111</vUnCom><vProd>3870.40</vProd><cEANTrib>7891605100997</cEANTrib><uTrib>UN</uTrib><qTrib>360.0000</qTrib><vUnTrib>10.7511111111</vUnTrib><vFrete>96.27</vFrete><indTot>1</indTot><xPed>450287867</xPed><nItemPed>0</nItemPed></prod><imposto><ICMS><ICMS00><orig>0</orig><CST>00</CST><modBC>3</modBC><vBC>3966.67</vBC><pICMS>18.0000</pICMS><vICMS>714.00</vICMS></ICMS00></ICMS><IPI><cEnq>999</cEnq><IPINT><CST>53</CST></IPINT></IPI><PIS><PISAliq><CST>01</CST><vBC>3252.67</vBC><pPIS>1.6500</pPIS><vPIS>53.66</vPIS></PISAliq></PIS><COFINS><COFINSAliq><CST>01</CST><vBC>3252.67</vBC><pCOFINS>7.6000</pCOFINS><vCOFINS>247.20</vCOFINS></COFINSAliq></COFINS></imposto><infAdProd>Lote: 050143</infAdProd></det><total><ICMSTot><vBC>3966.67</vBC><vICMS>714.00</vICMS><vICMSDeson>0</vICMSDeson><vFCPUFDest>0</vFCPUFDest><vICMSUFDest>0</vICMSUFDest><vICMSUFRemet>0</vICMSUFRemet><vFCP>0</vFCP><vBCST>0</vBCST><vST>0</vST><vFCPST>0</vFCPST><vFCPSTRet>0</vFCPSTRet><vProd>3870.40</vProd><vFrete>96.27</vFrete><vSeg>0</vSeg><vDesc>0</vDesc><vII>0</vII><vIPI>0</vIPI><vIPIDevol>0</vIPIDevol><vPIS>53.66</vPIS><vCOFINS>247.20</vCOFINS><vOutro>0</vOutro><vNF>3966.67</vNF></ICMSTot></total></infNFe></NFe></nfeProc>';

        $file = UploadedFile::fake()->createWithContent('test_nfe.xml', $xmlContent);

        $response = $this->post(route('process.files', ['type' => 'recebimento']), [
            'files' => [$file],
        ]);

        $response->assertStatus(200);
        
        // Ensure content type is CSV (or binary for download)
        // Note: The controller returns response()->download() which sets appropriate headers.
        
        $content = file_get_contents($response->baseResponse->getFile()->getPathname());

        $this->assertStringContainsString('ID_EXTERNO;DESTINATARIO;', $content);
        $this->assertStringContainsString('16205;23743831000159;ESTOQUE;10099;360.0000;;050143;;10.7511111111;;;;;450287867;16205;30;3966.67', $content);
    }

    public function test_export_expedicao()
    {
        Storage::fake('local');

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?><nfeProc xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00"><NFe xmlns="http://www.portalfiscal.inf.br/nfe"><infNFe Id="NFe35251244893410002470550300000162051282898692" versao="4.00"><ide><cUF>35</cUF><cNF>28289869</cNF><natOp>VENDA</natOp><mod>55</mod><serie>30</serie><nNF>16205</nNF><dhEmi>2025-12-15T14:35:00-03:00</dhEmi><dhSaiEnt>2025-12-15T14:35:00-03:00</dhSaiEnt><tpNF>1</tpNF><idDest>1</idDest><cMunFG>3549805</cMunFG><tpImp>1</tpImp><tpEmis>1</tpEmis><cDV>2</cDV><tpAmb>1</tpAmb><finNFe>1</finNFe><indFinal>0</indFinal><indPres>0</indPres><procEmi>0</procEmi><verProc>12.1.2410 | 3.0</verProc></ide><emit><CNPJ>44893410002470</CNPJ><xNome>USINA FORTALEZA INDUSTRIA E COMERCIO DE MASSA FINA LTDA</xNome><xFant>USINA FORTALEZA</xFant><enderEmit><xLgr>RUA VITORIO GASPARO</xLgr><nro>120</nro><xBairro>MIN DISTRITO ADAIL VETORASSO</xBairro><cMun>3549805</cMun><xMun>SAO JOSE DO RIO PRETO</xMun><UF>SP</UF><CEP>15046768</CEP><cPais>1058</cPais><xPais>BRASIL</xPais></enderEmit><IE>124244132115</IE><CRT>3</CRT></emit><dest><CNPJ>23743831000159</CNPJ><xNome>CALHAS BORGES &amp; BORGES LTDA</xNome><enderDest><xLgr>R VICENTE LOMBARDI</xLgr><nro>40</nro><xBairro>VILA SANTA EDWIRGES</xBairro><cMun>3549102</cMun><xMun>SAO JOAO DA BOA VISTA</xMun><UF>SP</UF><CEP>13874227</CEP><cPais>1058</cPais><xPais>BRASIL</xPais><fone>1936231715</fone></enderDest><indIEDest>1</indIEDest><IE>639109843117</IE><email>tatiborginha@hotmil.com;usinafortaleza@usinafortaleza.spedco</email></dest><det nItem="1"><prod><cProd>10099</cProd><cEAN>7891605100997</cEAN><xProd>CARTUCHO PU 40 FORTALEZA CINZA 400G</xProd><NCM>35061090</NCM><cBenef></cBenef><CFOP>5106</CFOP><uCom>UN</uCom><qCom>360.0000</qCom><vUnCom>10.7511111111</vUnCom><vProd>3870.40</vProd><cEANTrib>7891605100997</cEANTrib><uTrib>UN</uTrib><qTrib>360.0000</qTrib><vUnTrib>10.7511111111</vUnTrib><vFrete>96.27</vFrete><indTot>1</indTot><xPed>450287867</xPed><nItemPed>0</nItemPed></prod><imposto><ICMS><ICMS00><orig>0</orig><CST>00</CST><modBC>3</modBC><vBC>3966.67</vBC><pICMS>18.0000</pICMS><vICMS>714.00</vICMS></ICMS00></ICMS><IPI><cEnq>999</cEnq><IPINT><CST>53</CST></IPINT></IPI><PIS><PISAliq><CST>01</CST><vBC>3252.67</vBC><pPIS>1.6500</pPIS><vPIS>53.66</vPIS></PISAliq></PIS><COFINS><COFINSAliq><CST>01</CST><vBC>3252.67</vBC><pCOFINS>7.6000</pCOFINS><vCOFINS>247.20</vCOFINS></COFINSAliq></COFINS></imposto><infAdProd>Lote: 050143</infAdProd></det><total><ICMSTot><vBC>3966.67</vBC><vICMS>714.00</vICMS><vICMSDeson>0</vICMSDeson><vFCPUFDest>0</vFCPUFDest><vICMSUFDest>0</vICMSUFDest><vICMSUFRemet>0</vICMSUFRemet><vFCP>0</vFCP><vBCST>0</vBCST><vST>0</vST><vFCPST>0</vFCPST><vFCPSTRet>0</vFCPSTRet><vProd>3870.40</vProd><vFrete>96.27</vFrete><vSeg>0</vSeg><vDesc>0</vDesc><vII>0</vII><vIPI>0</vIPI><vIPIDevol>0</vIPIDevol><vPIS>53.66</vPIS><vCOFINS>247.20</vCOFINS><vOutro>0</vOutro><vNF>3966.67</vNF></ICMSTot></total></infNFe></NFe></nfeProc>';

        $file = UploadedFile::fake()->createWithContent('test_nfe.xml', $xmlContent);

        $response = $this->post(route('process.files', ['type' => 'expedicao']), [
            'files' => [$file],
        ]);

        $response->assertStatus(200);

        $content = file_get_contents($response->baseResponse->getFile()->getPathname());

        $this->assertStringContainsString('ID_EXTERNO;DESTINATARIO;', $content);
        $this->assertStringContainsString('16205;23743831000159;ESTOQUE;10099;360.0000;;050143;;10.7511111111;;;;;450287867;16205;30;3966.67;N;;', $content);
    }
}
