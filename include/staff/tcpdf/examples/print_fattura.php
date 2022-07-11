<?php

$intestazione = "<p>Spett.le</p> <p>Ordine dei Dottori Commercialisti e</p> <p>degli Esperti Contabili di Catanzaro</p> Corso Mazzini,45 <p><u>88100 Catanzaro</ul></p>";

$dati = array ('cod_cliente'=>1234, 'p_iva'=>"IT5437126364589", 'cod_fiscale'=>"97056120799", 'n_fattura'=>"12/2014", 'data_fattura'=>"25/12/2014", 'pagamento'=> '60 gg', 'banca'=>"BancoPosta delle Poste italiane S.p.A IBAN ITnumeroacaso15757");
$elenco = array (array('id'=>"ARM",'descrizione'=>"importo una tantum per l'attivazione del nuovo sito e i servizi di formazione e avviamento", 'qt'=>"1",'imp_unitario'=>"3.000,00",'sc'=>"15","imp_totale"=>"2.550,00","cod_iva"=>"22"),
                 array ('id'=>"CAN",'descrizione'=>"Canone di esercizio relativo al periodo ottobre 2014-marzo 2015", 'qt'=>"1",'imp_unitario'=>"600,00",'sc'=>"","imp_totale"=>"600,00","cod_iva"=>"22")
                 );
$riepilogo = array ('cod_iva'=>"22",'desc_iva'=>"IVA al 22%", 'aliquota'=>"22,00%", 'imponibile'=>"4.910,00",'imposta'=>"1080,20",'totale'=>"5990,20", 'tot_aliquota'=>"4910,00",'tot_imposta'=>"1080,20",'tot_netto'=>"5990,20");

$fattura = new stampaFattura($dati,$elenco,$riepilogo,$intestazione);
$fattura->getStampaFattura();

$rip = array ('cod_iva'=>"22905",'desc_iva'=>"IVA al 22%", 'aliquota'=>"23,00%", 'imponibile'=>"5.910,00",'imposta'=>"1080,20",'totale'=>"5990,20", 'tot_aliquota'=>"4910,00",'tot_imposta'=>"1080,20",'tot_netto'=>"5990,20");
/** prova per impostare un array di dati
$fattura->setRiepilogo($rip);
$fattura->getStampaFattura(); 
*/

class stampaFattura 
{
   private $dati =null;
   private $elenco = null;
   private $riepilogo = null;
   private $intestazione = null;
   public function __construct($dat = null,$list = null,$riep = null, $intest = null)
   {
       $this->dati =$dat;
       $this->elenco = $list;
       $this->riepilogo = $riep;
       $this->intestazione = $intest;
   }
   /**
    * genera una fattura con i dati ricevuto da 4 strutture:
    * intestazione: "spett.le ecc"
    * dati: le informazioni di generalità del cliente a cui è intestato la fattura.
    * elenco: la lista degli oggetti acquistati.
    * riepilogo: i dati di acquisti complessivo di iva
    * @return boolean se ci sono errori inserimento nei dati, altrimenti ritorna il riferimento di un file salvato nel percorso assoluto
    */
  public function getStampaFattura ()
  {
    if (!$this->seDatiOk())
    {
        return false;
    }
    $n=count($this->elenco);
    $record ='';
    for ($i=0;$i<$n;$i++)
    {
        $record = $record.
       '<tr>
        <td>'.@$this->elenco[$i]["id"].'
        </td>
        <td>'.@$this->elenco[$i]["descrizione"].'
        </td>
        <td>'.@$this->elenco[$i]["qt"].'
        </td>
        <td>'.@$this->elenco[$i]["imp_unitario"].'
        </td>

        <td>'.@$this->elenco[$i]["sc"].'
        </td>
        <td>'.@$this->elenco[$i]["imp_totale"].'
        </td>
        <td>'.@$this->elenco[$i]["cod_iva"].'
        </td>
      </tr>';
    }
    $html = '<head>
    <meta charset="UTF-8">        
    <title>Fatttura</title>
    <link rel="stylesheet" type="text/css" href="stile.css">
    </head>
    <body>
    <img class="logo" alt="webloom-logo"src="Webloom_logo.jpg">
    <p>Fattura</p>
    <class="intestazione">'.@$this->intestazione.'
    
    <table class="datiFattura">
    <thead>
    <tr>
    <th>Cod.Cliente</th>
    <th>Partita IVA
    </th>
    <th>Codice fiscale
    </th>
    <th>N.fattura
    </th>
    <th>Data fattura
    </th>
    <th>Pag.
    </th>
    </tr>
    </thead>
    <tbody>
    <tr>
    <td>'.@$this->dati["cod_cliente"].'
    </td>
    <td>'.@$this->dati["p_iva"].'
    </td>
    <td>'.@$this->dati["cod_fiscale"].'
    </td>
    <td>'.@$this->dati["n_fattura"].'
    </td>
    <td>'.@$this->dati["data_fattura"].'
    </td>
    <td>1/1
    </td>
    </tr>
    </tbody>
    </table>
    <table class="datiFattura">
    <thead>
    <tr>
    <th colspan="2">Pagamento
    </th>
    <th colspan="2">Banca
    </th>
    </tr>
    </thead>
    <tbody>
    <tr>
    <td>'.@$this->dati["pagamento"].'
    </td>
    <td>'.@$this->dati["banca"].'
    </td>
    </tr>
    </tbody>
    </table>
    <table class="elenco">
    <tbody>
    <tr>
    <th>Id
    </th>
    <th>Descrizione
    </th>
    <th>Q.tà
    </th>
    <th>Importo unitario
    </th>
    <th>Sc.%
    </th>
    <th>Importo totale
    </th>
    <th>Cod.Iva
    </th>
    </tr>
    '.$record
    .'</tbody> </table> ';

     $html =$html.'
    <table class="riepilogo">
    <thead>
    <tr>
    <th> Cod.Iva
    </th>
    <th>Descrizione
    </td>
    <th>Aliquota
    </th>
         <th>Imponibile
          </th>
    <th>Imposta
    </th>
    <th>Totale
    </th>
    </tr>
    </thead>
    <tbody>
    <tr>
    <td>'.@$this->riepilogo["cod_iva"].'
    </td>
    <td>'.@$this->riepilogo["desc_iva"].'
    </td>
    <td>'.@$this->riepilogo["aliquota"].'
    </td>
    <td>'.@$this->riepilogo["imponibile"].'
    </td>
    <td>'.@$this->riepilogo["imposta"].'
    </td>
    <td>'.@$this->riepilogo["totale"].'
    </td>
    </tr>
    </tbody>
    <tbody>
    <tr>
    <td>
    </td>
    <td>
    </td>
    <td>Totali
    </td>
    <td>'.@$this->riepilogo["tot_aliquota"].'
    </td>
    <td>'.@$this->riepilogo["tot_imposta"].'
    </td>
    <td>'.@$this->riepilogo["tot_netto"].'
    </td>
    </tr>
    </tbody>
    </table>
    <p><strong>Webloom S.r.l.</strong></p> 
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

   /* echo $html;
    return;
   */
    require_once('tcpdf_include.php');
    // crea un documento PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->setPageOrientation('PDF_PAGE_ORIENTATION'); 

    $pdf->AddPage('L', 'A4');
    $pdf->writeHTML($html, true, false, false, false, '');
    ob_clean();
    $directory = dirname(__FILE__); //percorso assoluto (con Joomla usare altro)
    //return $pdf->Output($directory.'/fattura.pdf', 'F'); // inserisce il nome del file e viene salvato nel percorso
    return $pdf->Output('fattura.pdf', 'I');
  }
 
  /**
   * imposta dati
   * @param type array
   */
  public function setDati ($dati = null)
   {
       $this->dati = $dati;
   }
   
   public function getDati ()
   {
       return $this->dati;
   }
   
   /**
    * imposta l'intestatario
    * @param type array
    */
   public function setIntestazione($intest)
   {
       $this->intestazione = $intest;
   }
   public function getIntestazione ()
   {
       return  $this->intestazione;
   }
   /**
    * imposta elenco
    * @param type array
    */
   public function setElenco ($list)
   {
       $this->elenco = $list;
   }
   
   public function getElenco ()
   {
       return $this->elenco;
   }
   
   public function setRiepilogo ($riep)
   {
       $this->riepilogo = $riep;
   }
   
   public function getRiepilogo ()
   {
       return $this->riepilogo = $riep;;
   }
   /**
    * verifica se i dati inseriti siano corretti
    * @return boolean true se le strutture dati inseriti sono corretti, altrimenti false 
    */
   private function seDatiOk ()
   {
       if (empty($this->intestazione))
       {
           return false;
       }
       if (empty($this->dati))
       {
           return false;
       }
       if (empty($this->elenco))
       {
           return false;
       }
       if (empty($this->riepilogo))
       {
           return false;
       }
       
       return true;
   }
    
}