<?php
//ini_set('display_errors','On');
//error_reporting(E_ALL);
$config = array(
    'url'=>'http://5.249.147.181:8080/api/http.php/tickets.json',
    'key'=>'07B4D26F5CBB16DAD3508C7456D2473C'
);

$zona1=array('RM','RI','VT','LT','FR');
$zona2=array('AQ','PE','TE','CH');
$holidays = ['2018-12-08', '2018-12-25', '2018-12-26', '2019-01-01', '2019-01-06', '2019-04-22', '2019-04-25', '2019-05-01'];

if (!isset($_FILES['csv']) ) {
?>
<?php
    $_SESSION['gettonesisal'] = md5(session_id() . time());
?>
<form enctype="multipart/form-data" action="?" method="POST"
onsubmit="document.getElementById('sisalino').disabled=true;
document.getElementById('sisalino').value='Submitting, please wait...';">
<?php csrf_token(); ?>
<input type="hidden" name="a" value="ticket_sisal" />
<input type="hidden" name="massive" value="1" />
<input type="hidden" name="gettonesisal" value="<?php echo $_SESSION['gettonesisal'] ?>" />
  <input name="csv" type="file"></br>
  <input type="submit" value="Invia File" id="sisalino">
</form>

<?php
}else{

if ($_POST['gettonesisal'] == $_SESSION['gettonesisal']){
$file = $_FILES['csv']['tmp_name'];
$keys = $line = $csv = array();

$fh = fopen($file, 'r');
set_time_limit(0);

$keys = fgetcsv($fh, 1000, ";");

unset($keys[count($keys)]);
$keys = array_map('trim',$keys);


$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'ZONA RICE'),
        'ref_num'
    )
);

$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'CAS'),
        'cr'
    )
);

$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'CAP'),
        'customer_location_l_addr3'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'DENOMINAZIONE'),
        'customer_middle_name'
    )
);/*
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'Urgenza'),
        'category_sym'
    )
);
*/
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'S/N OCR'),
        'affected_resource_zz_wam_string1'
    )
);

$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'S/N Modem'),
        'affected_resource_zz_wam_string8'
    )
);

$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'S/N Licenza Windows'),
        'affected_resource_zz_wam_string9'
    )
);

$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'S/N RCH'),
        'affected_resource_zz_wam_string2'
    )
);
/*
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'riferimento'),
        'ref_contatto'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'TIPOLOGIA'),
        'group_last_name'
    )
);*/

$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'S/N Cashless'),
        'affected_resource_zz_wam_string15'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'COMUNE'),
        'customer_location_l_addr7'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'PROV'),
        'customer_location_l_addr1'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'INDIRIZZO'),
        'customer_location_l_addr2'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'TEL1'),
        'customer_phone_number'
    )
);
if (in_array('TEL_CEL_TITOL',$keys)){
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'TEL2'),
        'tec_contatto_phone'
    )
);
}else{
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'TEL2'),
        'tec_contatto_phone'
    )
);
}

    if (in_array('TIPOLOGIA',$keys)){
        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'TIPOLOGIA'),
                'group_last_name'
            )
        );
    }else{
        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, "ATTIVITA'"),
                'group_last_name'
            )
        );
    }




/*$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'CHIUSURA COMM.'),
        'area_descrizione_intervento'
    )
);


$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'giornochiusura_2'),
        'desc_statocomponente'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'giornochiusura_3'),
        'cod_intervento'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'Ora Ordine'),
        'data_partenza'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, '1_e_2_Tentativo_di_connessione'),
        'category_analisiguasto'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'GGchiusura'),
        'rejected_date'
    )
);

$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'orario_apertura'),
        'customer_zz_top_sp_ap_lun'
    )
);


if ((strpos(strtolower ($_FILES['csv']['name']), 'installazioni') !== false) || (strpos(strtolower ($_FILES['csv']['name']), 'sostituzioni_massive') !== false || (strpos(strtolower ($_FILES['csv']['name']), 'migrazione_centroservizi') !== false))) {
      $keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'Dataordine'),
        'zz_date1'
    )
);
    }else{
      $keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'DataScheda'),
        'zz_date1'
    )
);
    }
*/



$keys[]='name';
$keys[]='email';
$keys[]='topicId';
$keys[]='duedate';
$keys[]='time';
$keys[]='costo_ext';
$keys[]='costo_int';
$keys[]='prezzo';
$keys[]='subject';
$keys[]='message';
$keys[]='ip';


$key_name = array_search('customer_middle_name', $keys);
$key_topicId = array_search('group_last_name', $keys);
$key_subject = array_search('customer_middle_name', $keys);
$key_data = array_search('zz_date1', $keys);
$key_termid = array_search('cr', $keys);
$key_ordine = array_search('ref_num', $keys);





    if (array_search('TIPOLOGIA', $keys))
        $key_message = array_search('EventualiNote', $keys);
    else
        $key_message = array_search('CAS', $keys);

$key_provincia = array_search('customer_location_l_addr1', $keys);
$key_abi = array_search('customer_last_name', $keys);

//$sql="select ref_num from ost_ticket__cdata natural join ost_ticket where status_id!=2";
//if ($thisstaff->getId()!=3)
$sql="select ref_num from ost_ticket__cdata where 1";
//else
//$sql="select ref_num from ost_ticket__cdata natural join ost_ticket where status_id!=2";

     $result=db_query($sql);
      while ($row = db_fetch_array($result )) {
	    $ordini[]=$row['ref_num'];
      }

while (!feof($fh)) {
	$line = fgetcsv($fh, 1000, ";");
	unset($line[count($line)]);

	if (isset($ordini) and in_array($line[$key_ordine], $ordini)){
	$ordini_assist[]=$line[$key_ordine];
	continue;
    }


if (substr( $line[$key_abi], 0, 1 ) != 0)
$line[$key_abi]='0'.$line[$key_abi];


	$line[]=isset($line[$key_name])?$line[$key_name]:null;
	$line[]=isset($line[$key_name])?preg_replace("/[^a-zA-Z0-9]+/", "", $line[$key_name]).'@service-tech.org':null;

	$line[$key_termid]=(string) str_pad($line[$key_termid], 8, '0', STR_PAD_LEFT);
	$line[$key_data]=(string) str_pad($line[$key_data], 8, '0', STR_PAD_LEFT);

	$datasplit=str_split($line[$key_data], 2);
    $nuovadata=$datasplit[2].$datasplit[3].'-'.$datasplit[1].'-'.$datasplit[0];
    $line[$key_data]=time();


    $date=date('Y-m-d',time());

    echo $line[$key_topicId]."<br>";


switch (trim($line[$key_topicId])) {
case 'SWAP':
$n=180;
$line[] = 40;
$line[] = nworkingdaysafter($date,$n,$holidays);
$line[] = '20:00';

$line[] = 55;
$line[] = 23;
$line[] = 32;

break;
    case 'SWAP DA BIG A FUSION':
        $n=180;
        $line[] = 40;
        $line[] = nworkingdaysafter($date,$n,$holidays);
        $line[] = '20:00';

        $line[] = 55;
        $line[] = 23;
        $line[] = 32;

        break;
    case 'INSTALLAZIONE FUSION':
        $n=180;
        $line[] = 40;
        $line[] = nworkingdaysafter($date,$n,$holidays);
        $line[] = '20:00';

        $line[] = 55;
        $line[] = 23;
        $line[] = 32;

        break;
case 'RCH':
$n=180;
$line[] = 41;
$line[] = nworkingdaysafter($date,$n,$holidays);
$line[] = '20:00';

$line[] = 45;
$line[] = 23;
$line[] = 22;
break;
case 'KO':
$n=180;
$line[] = 42;
$line[] = nworkingdaysafter($date,$n,$holidays);
$line[] = '20:00';

$line[] = 15;
$line[] = 1;
$line[] = 14;
break;

default:
$n=1;
$line[] = 41;
$line[] = nworkingdaysafter($date,$n,$holidays);
$line[] = '20:00';
$line[] = 'x';
$line[] = 'x';
$line[] = 'x';
}

    $line[]=isset($line[$key_subject])?$line[$key_subject]:null;
	$line[]=isset($line[$key_message])?$line[$key_message]:null;
	$line[]=$_SERVER['REMOTE_ADDR'];

	array_map('db_input', $line);



array_walk(
    $line,
    function (&$entry) {
        $entry = iconv('Windows-1252', 'UTF-8', $entry);
    }
);





      $csv[] = array_combine($keys, $line);

}



foreach ($csv as $keynes => $subArr) {

unset($subArr['IDRICE']);
unset($subArr['ZONARICE']);
unset($subArr['ZONA']);
unset($subArr['TITOLARE']);
unset($subArr['FRAZ']);
unset($subArr['REGIONE']);
unset($subArr['TEL_CEL_TITOL']);
unset($subArr['CASHLESS PRESENTE']);
unset($subArr['OCR PRESENTE']);
unset($subArr['ADSL / 3G']);
unset($subArr['TG PRESENTE']);
unset($subArr['TIPO INTERVENTO']);
unset($subArr['NOMINATIVO RA']);
unset($subArr['NUMERO RA']);
unset($subArr['NOMINATIVO DM']);
unset($subArr['NUMERO DM']);
unset($subArr['UFFICIO VENDITE 1']);
unset($subArr['NUMERO']);
unset($subArr['UFFICIO VENDITE 2']);
unset($subArr['UFFICIO VENDITE 3']);
    $subArr['zz_date1'] = time();
    $csv[$keynes] = $subArr;

}

/*
echo "<pre>";
print_r($csv);
echo "</pre>";
die;
*/

}
}

function mres($value)
{
    $search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
    $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");

    return str_replace($search, $replace, $value);
}

if (!empty($csv)){

function_exists('curl_version') or die('CURL support required');
function_exists('json_encode') or die('JSON support required');

//array_pop($csv);

echo '<table width=100% style="font-size:100%; font-family:play; color:black;"><tr><td>N. interv.</td><td>Codice interv.</td><td>Città</td><td>Prov.</td><td>Cliente</td><td>Soggetto</td><td>Messaggio</td><td>Codice</td></tr>';
$i=0;

#set timeout
set_time_limit(20000);

#curl post

$ch = curl_init();

foreach ($csv as $data){


  $nome = mres($data['name']);

  $sql="select id from ost_user where name = '".$nome."'";
  //echo $sql."<br><br>";
  //else
  //$sql="select ref_num from ost_ticket__cdata natural join ost_ticket where status_id!=2";
  $result=db_query($sql);
  $num = db_num_rows($result);

  //echo $num."<br><br>";
  if($num<1){

    $sql_user = "SELECT MAX(id) as id_user FROM ost_user";
    $result_id =db_query($sql_user);
    $id_user = db_fetch_array($result_id);
    $id_user = $id_user['id_user']+1;

    $sql_user = "SELECT MAX(id) as id_email FROM ost_user_email";
    $result_id =db_query($sql_user);
    $id_mail = db_fetch_array($result_id);
    $id_mail = $id_mail['id_email']+1;
    $mail = $data['email'];

    $sql_1 = "INSERT INTO ost_user (id, org_id, default_email_id,status, name) VALUES ($id_user,0,$id_mail,0,'".$nome."')";
    $result =db_query($sql_1);

    $sql_2 = "INSERT INTO ost_user_email (id, user_id, address) VALUES ($id_mail,$id_user,'".$mail."')";
    $result =db_query($sql_2);
  }

echo json_encode($data);

curl_setopt($ch, CURLOPT_URL, $config['url']);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_USERAGENT, 'osTicket API Client v1.7');
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Expect:', 'X-API-Key: '.$config['key']));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$result=curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error  = curl_error($ch);

if ($code != 201){
if ($i % 2 == 0)
echo "<tr style='font-size:80%; font-family:play; color:black;' class='pair'><td>".$result."</td><td>".$code."</td><td>".$error."</td><td>".$data['ABI']."</td><td>".$data['INSEGNA']."</td><td>".$data['INDIRIZZO']."</td><td>".$data['CAP']."</td><td>".$data['LUOGO']."</td><td><font color='red'>Fail</font></td></tr>";
 else
echo "<tr style='font-size:80%; font-family:play; color:black;' class='pair'><td>".$result."</td><td>".$code."</td><td>".$error."</td><td>".$data['ABI']."</td><td>".$data['INSEGNA']."</td><td>".$data['INDIRIZZO']."</td><td>".$data['CAP']."</td><td>".$data['LUOGO']."</td><td><font color='red'>Fail</font></td></tr>";
}else{
if ($i % 2 == 0)
echo "<tr style='font-size:80%; font-family:play; color:black;' class='pair'><td>".$data['ref_num']."</td><td>".$data['group_last_name']."</td><td>".$data['customer_location_l_addr7']."</td><td>".$data['customer_location_l_addr1']."</td><td>".$data['name']."</td><td>".$data['subject']."</td><td>".$data['message']."</td><td>".$data['Codiceintervento']."</td><td><font color='gren'>OK</font></td></tr>";
 else
echo "<tr style='font-size:80%; font-family:play; color:black;' class='pair'><td>".$data['ref_num']."</td><td>".$data['group_last_name']."</td><td>".$data['customer_location_l_addr7']."</td><td>".$data['customer_location_l_addr1']."</td><td>".$data['name']."</td><td>".$data['subject']."</td><td>".$data['message']."</td><td>".$data['Codiceintervento']."</td><td><font color='gren'>OK</font></td></tr>";
}

$i++;

unset($data);

}//fine ciclo


curl_close($ch);
echo '</table>';

}
if ($error){
echo 'Errore: '.$error.'<br>';
echo 'Risultato: '.$result.'<br>';
echo 'Codice: '.$code.'<br><br>';
}

if ($ordini_assist){
echo "<br><br><br><br><br><br>I seguenti ordini sono già inseriti:<br><br><pre>";
print_r($ordini_assist);
echo "</pre>";
}

/*
function nworkingdaysafter($data,$n,$holidays){
   for ($i=1;$i<=$n;$i++) {
       $nextBusinessDay = date('Y-m-d', strtotime($data . ' +' . $i . ' Weekday'));
       if(in_array($nextBusinessDay, $holidays)){
	   $n++;
	   }
   }
   return $nextBusinessDay;
}
*/
function nworkingdaysafter($from, $days, $holidays) {
    $workingDays = [1, 2, 3, 4, 5, 6]; # date format = N (1 = Monday, ...)
    $holidayDays = ['*-12-25', '*-01-01','*-01-06','*-04-25','*-05-01','*-06-02','*-08-15','*-11-01','2019-01-01', '2019-01-06', '2019-04-22', '2019-04-25', '2019-05-01'];# variable and fixed holidays

    $from = new DateTime($from);
    while ($days) {
        $from->modify('+1 day');
        if (!in_array($from->format('N'), $workingDays)) continue;
        if (in_array($from->format('Y-m-d'), $holidayDays)) continue;
        if (in_array($from->format('*-m-d'), $holidayDays)) continue;
        $days--;
    }
    return $from->format('Y-m-d'); #  or just return DateTime object
}
?>
