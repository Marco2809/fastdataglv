<?php

require_once('tcpdf_include.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->AddPage();

$matrit=array(12,24,25);
$matins=array(13,18,19,21,23,);
$matrin=array(14,15,16,20,22,26,27,36,38,39);
$matrsis=array(40,41);



for ($i = 0; $i < count($array_unico); $i++)
{
    $array_ticket = $array_unico[$i];
    //$params = TCPDF_STATIC::serializeTCPDFtagParameters(array($array_ticket['ordine'].'-'.$array_ticket['termid'], 'C128', '', '', 65, 20, 0.4, array('position'=>'S', 'border'=>false, 'padding'=>4, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255), 'text'=>true, 'font'=>'helvetica', 'fontsize'=>8, 'stretchtext'=>4), 'N'));
    $params = $pdf->serializeTCPDFtagParameters(array(' '.$array_ticket['numero'].' ', 'C39', '', '', 65, 20, 0.4, array('position'=>'S', 'border'=>false, 'padding'=>4, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255), 'text'=>true, 'font'=>'helvetica', 'fontsize'=>8, 'stretchtext'=>4), 'N'));

  switch(true) {
            case in_array($array_ticket['topicId'],$matrit):
                $matricola="Matr.RIT.________________________________";
                break;
            case in_array($array_ticket['topicId'],$matins):
                $matricola="Matr.INS.________________________________";
                break;
            case in_array($array_ticket['topicId'],$matrin):
                $matricola="Matr.INS.________________________________<br><br>Matr.RIT.________________________________";
                break;
		}
   $html = '<table cellSpacing="0" cellPadding="0" border="1" style="border-collapse:collapse;"><tr><td width="70%">';

   if(strpos($array_ticket['ordine'], 'CA') !== false or strpos($array_ticket['ordine'], 'GE') !== false){
     $html .= '<img alt="sisal" width="120" src="http://5.249.147.181:8080/include/staff/tcpdf/examples/Sisal.png">';
   }else if (strpos($array_ticket['ordine'], 'C') !== false or strpos($array_ticket['ordine'], 'c') !== false) {

    $html .= '<img alt="coopersystem" width="120" src="http://5.249.147.181:8080/include/staff/tcpdf/examples/coopersystem.gif">  URG: '.$array_ticket['urgenza'];
   }elseif(strpos($array_ticket['ordine'], 'T') !== false or strpos($array_ticket['ordine'], 't') !== false){
    //$html .= "<br><br>REGISTRAZIONE EFFETTUATA NEXI BUSINESS &nbsp;&nbsp;&nbsp;|SI|&nbsp;&nbsp;|NO|";
  }else{
    $html .= '<img alt="cartasi" width="120" src="http://5.249.147.181:8080/include/staff/tcpdf/examples/cartasi.png">';
    //$html .= "<br><br>REGISTRAZIONE EFFETTUATA NEXI BUSINESS &nbsp;&nbsp;&nbsp;|SI|&nbsp;&nbsp;|NO|";
   }
    //$html .= '<tcpdf method="write1DBarcode" params="'.$params.'" />';
    $html .= "<br>";
    $html .= '<table>';

    $html .= "<tr>";
    $html .= "<td width=\"30%\">";
    $html .= "<strong> <h4>Tipo</h4></strong>";
    $html .= "</td> ";
    $html .= "<td> ";
    $html .= "<strong><h4>".$array_ticket['tipo_ordine']."</h4></strong>";
    $html .= "</td>";
    $html .= "</tr>";

    $html .= "<tr>";
    $html .= "<td>";
    $html .= "<strong> Data ordine</strong>";
    $html .= "</td> ";
    $html .= "<td> ";
    $html .= date('d/m/Y',strtotime($array_ticket['open']))." (".$array_ticket['ora_ordine'].")";
    $html .= "</td>";
    $html .= "</tr>";

    $html .= "<tr>";
    $html .= "<td>";
    $html .= "<strong> Termid</strong>";
    $html .= "</td> ";
    $html .= "<td> ";
    $html .= "<strong>".$array_ticket['termid']."</strong>";
    $html .= "</td>";
    $html .= "</tr>";

    $html .= "<tr>";
    $html .= "<td>";
    $html .= "<strong> Insegna</strong>";
    $html .= "</td> ";
    $html .= "<td> ";
    $html .= "<strong>".$array_ticket['insegna']."</strong>";
    $html .= "</td>";
    $html .= "</tr>";

    $html .= "<tr>";

    $html .= "<td>";
    $html .= "<strong> Ordine</strong>";
    $html .= "</td>";
    $html .= "<td> ";
    $html .= "<strong>".$array_ticket['ordine']."</strong>";
    $html .= "</td>";
    $html .= "</tr>";



if(!in_array($array_ticket['topicId'],$matrsis))
{

  $html .= "<tr>";
  $html .= "<td>";
  $html .= "<strong> Hw</strong>";
  $html .= "</td> ";
  $html .= "<td> ";
  $html .= $array_ticket['hw'];
  $html .= "</td>";
  $html .= "</tr>";

  $html .= "<tr>";
  $html .= "<td>";
  $html .= "<strong> Collegamento</strong>";
  $html .= "</td> ";
  $html .= "<td> ";
  $html .= $array_ticket['collegamento'];
  $html .= "</td>";
  $html .= "</tr>";

  $html .= "<tr>";
  $html .= "<td>";
  $html .= "<strong> Abi</strong>";
  $html .= "</td> ";
  $html .= "<td> ";
  $html .= $array_ticket['abi'];
  $html .= "</td>";
  $html .= "</tr>";

  $html .= "<tr>";
  $html .= "<td>";
  $html .= "<strong> Banca</strong>";
  $html .= "</td> ";
  $html .= "<td> ";
  $html .= $array_ticket['banca'];
  $html .= "</td>";
  $html .= "</tr>";

} else {
$myString = print_r($array_ticket, TRUE);
  $html .= "<tr>";
  $html .= "<td>";
  $html .= "<strong> S/N OCR</strong>";
  $html .= "</td> ";
  $html .= "<td> ";
  $html .= $array_ticket['hw'];
  $html .= "</td>";
  $html .= "</tr>";

  $html .= "<tr>";
  $html .= "<td>";
  $html .= "<strong> S/N RCH</strong>";
  $html .= "</td> ";
  $html .= "<td> ";
  $html .= $array_ticket['rch'];
  $html .= "</td>";
  $html .= "</tr>";

  $html .= "<tr>";
  $html .= "<td>";
  $html .= "<strong> S/N Modem</strong>";
  $html .= "</td> ";
  $html .= "<td> ";
  $html .= $array_ticket['modem'];
  $html .= "</td>";
  $html .= "</tr>";

  $html .= "<tr>";
  $html .= "<td>";
  $html .= "<strong> S/N Cashless</strong>";
  $html .= "</td> ";
  $html .= "<td> ";
  $html .= $array_ticket['cashless'];
  $html .= "</td>";
  $html .= "</tr>";

  $html .= "<tr>";
  $html .= "<td>";
  $html .= "<strong> S/N Licenza</strong>";
  $html .= "</td> ";
  $html .= "<td> ";
  $html .= $array_ticket['licenza'];
  $html .= "</td>";
  $html .= "</tr>";
}





    $html .= "<tr>";
    $html .= "<td>";
    $html .= "<strong> Indirizzo</strong>";
    $html .= "</td>";
    $html .= "<td> ";
    $html .= $array_ticket['indirizzo'];
    $html .= "</td>";
    $html .= "</tr>";

    $html .= "<tr>";
    $html .= "<td>";
    $html .= "<strong> Luogo</strong>";
    $html .= "</td>";
    $html .= "<td> ";
    $html .= $array_ticket['luogo'];
    $html .= "</td>";
    $html .= "</tr>";


    $html .= "<tr>";
    $html .= "<td>";
    $html .= "<strong> Provincia</strong>";
    $html .= "</td>";
    $html .= "<td> ";
    $html .= $array_ticket['provincia'];
    $html .= "</td>";
    $html .= "</tr>";

if(!in_array($array_ticket['topicId'],$matrsis))
{
  $html .= "<tr>";
  $html .= "<td>";
  $html .= "<strong> Cap</strong>";
  $html .= "</td>";
  $html .= "<td> ";
  $html .= $array_ticket['cap'];
  $html .= "</td>";
  $html .= "</tr>";
}

    $html .= "<tr>";
    $html .= "<td>";
    $html .= "<strong> Telefono</strong>";
    $html .= "</td>";
    $html .= "<td> ";
    $html .= $array_ticket['telefono'];
    $html .= "</td>";
    $html .= "</tr>";

    $html .= "<tr>";
    $html .= "<td>";
    $html .= "<strong> Telefono1</strong>";
    $html .= "</td>";
    $html .= "<td> ";
    $html .= $array_ticket['cellulare'];
    $html .= "</td>";
    $html .= "</tr>";

    $html .= "<tr>";
    $html .= "<td> ";
    $html .= "<strong>Note</strong>";
    $html .= "</td>";
    $html .= "<td><h5>";
    $html .= trim($array_ticket['messaggio']);
    $html .= "</h5></td>";
    $html .= "</tr>";

    if(!in_array($array_ticket['topicId'],$matrsis))
    {
    $html .= "<tr>";
    $html .= "<td>";
    $html .= "<strong> Scadenza</strong>";
    $html .= "</td>";
    $html .= "<td> ";
    $html .= date('d/m/Y H:i',strtotime($array_ticket['scadenza']));
    $html .= "</td>";
    $html .= "</tr>";

    $html .= "<tr>";
    $html .= "<td>";
    $html .= "<strong> Orario apertura</strong>";
    $html .= "</td>";
    $html .= "<td> ";
    $html .= $array_ticket['orario_apertura'];
    $html .= "</td>";
    $html .= "</tr>";

    $html .= "<tr>";
    $html .= "<td>";
    $html .= "<strong> Giorno chiusura 1</strong>";
    $html .= "</td>";
    $html .= "<td> ";
    $html .= $array_ticket['giornochiusura_1'];
    $html .= "</td>";
    $html .= "</tr>";
if (strpos($array_ticket['ordine'], 'C') !== false) {
    $html .= "<tr>";
    $html .= "<td>";
    $html .= "<strong> Giorno chiusura 2</strong>";
    $html .= "</td>";
    $html .= "<td> ";
    $html .= $array_ticket['giornochiusura_2'];
    $html .= "</td>";
    $html .= "</tr>";

    $html .= "<tr>";
    $html .= "<td>";
    $html .= "<strong> Giorno chiusura 3</strong>";
    $html .= "</td>";
    $html .= "<td> ";
    $html .= $array_ticket['giornochiusura_3'];
    $html .= "</td>";
    $html .= "</tr>";
}

    $html .= "<tr>";
    $html .= "<td>";
    $html .= "<strong> Appuntamento</strong>";
    $html .= "</td>";
    $html .= "<td> ";
    $html .= "<br><br>";
    $html .= "</td>";
    $html .= "</tr>";
  }

    $html .= "</table></td><td><tcpdf method=\"write1DBarcode\" params=\"".$params."\" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Scontrino o note blocco</td></tr><tr><td><br><br>".$matricola."<br><br>Firma e Timbro Esercente___________________<br><br>Data____________________________________<br><br>Firma Tecnico____________________________<br><br></td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Data causale blocco</td></tr></table><br><br>ORD. ASSEGNATO _____________________ - ORD. CHIUSO |__|";
/*
    if (strpos($array_ticket['ordine'], 'C') === false) {
    $html .= "<br><br><br>REGISTRAZIONE EFFETTUATA NEXI BUSINESS &nbsp;&nbsp;&nbsp;|SI|&nbsp;&nbsp;|NO|";
  }*/
    $pdf->writeHTMLCell(0, 0, $pdf->GetX(), $pdf->GetY(), $html, "", 1);

    if  ($i+1 < count($array_unico))
        $pdf->AddPage();
}


ob_clean();

$pdf->Output('/var/www/fastdataglv/ticket_massivi.pdf', 'F');


?>
<script type="text/javascript">
window.open('http://5.249.147.181:8080/ticket_massivi.pdf');
</script>
<?php
exit;
?>
