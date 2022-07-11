
<?php

//$fattura = new datiFattura();
$dati = array ('cod_cliente'=>1234, 'p_iva'=>"IT5437126364589", 'cod_fiscale'=>"97056120799", 'n_fattura'=>"12/2014", 'data_fattura'=>"25/12/2014", 'pagamento'=> '60 gg', 'banca'=>"BancoPosta delle Poste italiane S.p.A IBAN ITnumeroacaso15757");
$elenco = array (array('id'=>"ARM",'descrizione'=>"importo una tantum per l'attivazione del nuovo sito e i servizi di formazione e avviamento", 'qt'=>"1",'imp_unitario'=>"3.000,00",'sc'=>"15","imp_totale"=>"2.550,00","cod_iva"=>"22"),
                 array ('id'=>"CAN",'descrizione'=>"Canone di esercizio relativo al periodo ottobre 2014-marzo 2015", 'qt'=>"1",'imp_unitario'=>"600,00",'sc'=>"","imp_totale"=>"600,00","cod_iva"=>"22")
                 );

$riepilogo = array ('cod_iva'=>"22",'desc_iva'=>"IVA al 22%", 'aliquota'=>"22,00%", 'imponibile'=>"4.910,00",'imposta'=>"1080,20",'totale'=>"5990,20", 'tot_aliquota'=>"4910,00",'tot_imposta'=>"1080,20",'tot_netto'=>"5990,20");

$datiFattura = array ($dati,$elenco,$riepilogo);

//$fattura->getDatiFattura($datiFattura); 


$n=count($datiFattura[1]);
$record ='';
 for ($i=0;$i<$n;$i++)
 {
     $record = $record.
     '<tr>
      <td>'.$datiFattura[1][$i]["id"].'<br>
      </td>
      <td>'.$datiFattura[1][$i]["descrizione"].'<br>
      </td>
      <td>'.$datiFattura[1][$i]["qt"].'<br>
      </td>
      <td>'.$datiFattura[1][$i]["imp_unitario"].'<br>
      </td>

      <td>'.$datiFattura[1][$i]["sc"].'<br>
      </td>
      <td>'.$datiFattura[1][$i]["imp_totale"].'<br>
      </td>
      <td>'.$datiFattura[1][$i]["cod_iva"].'<br>
      </td>
    </tr>';
 }

$html = '<head>
  <meta charset="UTF-8">        
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fatttura</title>
  <style type="text/css">
.datiFattura {
}
.elenco {
}
.pagamento {
}
.riepilogo {
}
.datiWebloom {
}
.logo {
}
.intestazione {
}
  </style>
</head>
<body>
<br>
<br>
<img class="logo" alt="webloom-logo"src="Webloom_logo.jpg">
<br>
<br>
Fattura<br>
<table class="datiFattura">
<thead>
<tr>
<th>Cod.Cliente</th>
<th>Partita IVA<br>
</th>
<th>Codice fiscale<br>
</th>
<th>N.fattura<br>
</th>
<th>Data fattura<br>
</th>
<th>Pag.<br>
</th>
</tr>
</thead>
<tbody>
<tr>
<td>'.$datiFattura[0]["cod_cliente"].'<br>
</td>
<td>'.$datiFattura[0]["p_iva"].'<br>
</td>
<td>'.$datiFattura[0]["cod_fiscale"].'<br>
</td>
<td>'.$datiFattura[0]["n_fattura"].'<br>
</td>
<td>'.$datiFattura[0]["data_fattura"].'<br>
</td>
<td>1/1<br>
</td>
</tr>
</tbody>
</table>
<br>
<table class="datiFattura">
<thead>
<tr>
<th>Pagamento<br>
</th>
<th>Banca<br>
</th>
</tr>
</thead>
<tbody>
<tr>
<td>'.$datiFattura[0]["pagamento"].'<br>
</td>
<td>'.$datiFattura[0]["banca"].'<br>
</td>
</tr>
</tbody>
</table>
<br>
<table class="elenco">
<tbody>
<tr>
<th>Id<br>
</th>
<th>Descrizione<br>
</th>
<th>Q.tà<br>
</th>
<th>Importo unitario<br>
</th>
<th>Sc.%<br>
</th>
<th>Importo totale<br>
</th>
<th>Cod.Iva<br>
</th>
</tr>
'.$record
.'</tbody> </table> ';

 $html =$html.'
<br>
<br>
<table class="riepilogo">
<thead>
<tr>
<th> Cod.Iva<br>
</th>
<th>Descrizione<br>
</td>
<th>Aliquota<br>
</th>
     <th>Imponibile<br>
      </th>
<th>Imposta<br>
</th>
<th>Totale<br>
</th>
</tr>
</thead>
<tbody>
<tr>
<td>'.$datiFattura[2]["cod_iva"].'<br>
</td>
<td>'.$datiFattura[2]["desc_iva"].'<br>
</td>
<td>'.$datiFattura[2]["aliquota"].'<br>
</td>
<td>'.$datiFattura[2]["imponibile"].'<br>
</td>
<td>'.$datiFattura[2]["imposta"].'<br>
</td>
<td>'.$datiFattura[2]["totale"].'<br>
</td>
</tr>
</tbody>
<tbody>
<tr>
<td><br>
</td>
<td><br>
</td>
<td>Totali<br>
</td>
<td>'.$datiFattura[2]["imponibile"].'<br>
</td>
<td>'.$datiFattura[2]["imposta"].'<br>
</td>
<td>'.$datiFattura[2]["totale"].'<br>
</td>
</tr>
</tbody>
</table>
<br>
<br>
<strong>Webloom S.r.l.</strong> 
<p>Sede legale: Via
Tarvisio 2 - 00198 Roma. Sede operativa: Via
Montasio 11 -
00141 Roma </p>
<p>Tel.: 06.83.90.41.26/27 - Fax: 06.62.20.97.64
P.IVA: 06457411004 - PEC: <u>webloom@pec.webloom.it</u> - Email <u>info@webloom.it</u>
</p>
<p>C.F. e P.IVA 06457411004 - REA Roma n.969579 Capitale Sociale € 30.000,00</p>
</body>
';
 
echo $html;
return;
define('EURO', chr(128));
require_once('tcpdf_include.php');
// crea un documento PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setPageOrientation('PDF_PAGE_ORIENTATION'); 

$pdf->AddPage('L', 'A4');
$pdf->writeHTML($html, true, false, false, false, '');
ob_clean();
$pdf->Output('prova_fattura.pdf', 'I'); // inserisce il nome del file pdfs

class stampaFattura 
{
   private $datiFattura =null;
   
   public function __construct() {
       
   }
    
  public function getDatiFattura ($dati = null)
  {
      if (is_null($this->datiFattura))
      {
          $this->datiFattura = $dati;
      }
      
      return $this->datiFattura;
  }
    
  
}