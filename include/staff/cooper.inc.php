<?php
//ini_set('display_errors',1);
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
    $_SESSION['gettonecooper'] = md5(session_id() . time());
?>
<form enctype="multipart/form-data" action="?" method="POST"
onsubmit="document.getElementById('cooperino').disabled=true;
document.getElementById('cooperino').value='Submitting, please wait...';">
<?php csrf_token(); ?>
<input type="hidden" name="a" value="ticket_cooper" />
<input type="hidden" name="massive" value="1" />
<input type="hidden" name="gettonecooper" value="<?php echo $_SESSION['gettonecooper'] ?>" />
  <input name="csv" type="file"></br>
  <input type="submit" value="Invia File" id="cooperino">
</form>

<?php
}else{

if ($_POST['gettonecooper'] == $_SESSION['gettonecooper']){
$file = $_FILES['csv']['tmp_name'];
$keys = $line = $csv = array();

$fh = fopen($file, 'r');
set_time_limit(0);

$keys = fgetcsv($fh, 1000, ";");

unset($keys[count($keys)-1]);
$keys = array_map('trim',$keys);

$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'Numero Intervento'),
        'ref_num'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'Termid'),
        'cr'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'Cap'),
        'customer_location_l_addr3'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'Insegna'),
        'customer_middle_name'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'Urgenza'),
        'category_sym'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'Modello tml'),
        'affected_resource_zz_wam_string1'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'DataScadenza'),
        'ref_contatto'
    )
);

$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'Codiceintervento'),
        'group_last_name'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'Cod Abi'),
        'customer_last_name'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'Citta'),
        'customer_location_l_addr7'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'Provincia'),
        'customer_location_l_addr1'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'Indirizzo'),
        'customer_location_l_addr2'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'Telefono'),
        'customer_phone_number'
    )
);
if (in_array('Cellulare',$keys)){
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'Cellulare'),
        'tec_contatto_phone'
    )
);
}else if(in_array('Telefono1',$keys)){
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'Telefono1'),
        'tec_contatto_phone'
    )
);
}else {
  $keys = array_replace($keys,
      array_fill_keys(
          array_keys($keys, 'Telefono'),
          'tec_contatto_phone'
      )
  );
}
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'giornochiusura_1'),
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

$key_name = array_search('Rag Sociale', $keys);
$key_topicId = array_search('group_last_name', $keys);
$key_subject = array_search('Rag Sociale', $keys);
$key_data = array_search('zz_date1', $keys);
$key_termid = array_search('cr', $keys);
$key_ordine = array_search('ref_num', $keys);

$key_datascad = array_search('ref_contatto', $keys);


if (array_search('EventualiNote', $keys))
$key_message = array_search('EventualiNote', $keys);
else
$key_message = array_search('Descrizionerichiesta', $keys);

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
	unset($line[count($line)-1]);

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
    $line[$key_data]=strtotime($nuovadata);


    $date=$nuovadata;

    $data_scad = substr($line[$key_datascad],4,4)."-".substr($line[$key_datascad],2,2)."-".substr($line[$key_datascad],0,2);
    $ora_scad = substr($line[$key_datascad],9,2).":".substr($line[$key_datascad],11,2);

//echo "DATA:".$line[$key_datascad];

switch (trim(preg_replace('/[^0-9]/u','',$line[$key_topicId]))) {
case 100010:
$n=4;
$line[] = 18;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
if (in_array($line[$key_provincia],$zona1)){
	$line[] = '21.25';//24;
	$line[] = '11';//10;
	$line[] = '10.25';//14;
}else{
    $line[] = '21.25';//27;
    $line[] = '15';//12;
    $line[] = '6.25';//15;
}
break;
case 100011:
$n=4;
$line[] = 19;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
if (in_array($line[$key_provincia],$zona1)){
	$line[] = '31.88';//24;
	$line[] = '11';//10;
	$line[] = '20.88';//14;
}else{
    $line[] = '31.88';//27;
    $line[] = '15';//12;
    $line[] = '16.88';//15;
}
break;
case 100012:
$n=4;
$line[] = 20;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
if (in_array($line[$key_provincia],$zona1)){
        $line[] = '21.25';//24;
        $line[] = '11';//10;
        $line[] = '10.25';//14;
    }else{
        $line[] = '21.25';//27;
        $line[] = '15';//12;
        $line[] = '6.25';//15;
    }
break;
case 100013:
$n=4;
$line[] = 21;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
 if (in_array($line[$key_provincia],$zona1)){
        $line[] = '21.25';//24;
        $line[] = '11';//10;
        $line[] = '10.25';//14;
    }else{
        $line[] = '21.25';//27;
        $line[] = '15';//12;
        $line[] = '6.25';//15;
    }
break;
case 100030:
$n=4;
$line[] = 22;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
    if (in_array($line[$key_provincia],$zona1)){
        $line[] = '21.25';//24;
        $line[] = '11';//10;
        $line[] = '10.25';//14;
    }else {
        $line[] = '21.25';//27;
        $line[] = '15';//12;
        $line[] = '6.25';//14;
    }
        break;
case 100031:
$n=4;
$line[] = 23;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
 if (in_array($line[$key_provincia],$zona1)){
     $line[] = '21.25';//24;
     $line[] = '11';//10;
     $line[] = '10.25';//14;
 }else {
     $line[] = '21.25';//27;
     $line[] = '15';//12;
     $line[] = '6.25';//14;
 }
     break;
case 20038:
$n=10;
$line[] = 24;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
if (in_array($line[$key_provincia],$zona1)){
	$line[] = '8.5';//10;
	$line[] = '6';//5;
	$line[] = '2.5';//5;
}else{
    $line[] = '8.5';//13;
    $line[] = '6';//6;
    $line[] = '2.5';//7;
}
break; 
case 20040:
$n=10;
$line[] = 25;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
if (in_array($line[$key_provincia],$zona1)){
	$line[] = '8.5';//10;
	$line[] = '0';//5;
	$line[] = '8.5';//5;
}else{
    $line[] = '8.5';//13;
    $line[] = '0';//6;
    $line[] = '8.5';//7;
}
break;
case 20000:
$date=Date('Y-m-d H:i');
$ora=explode(' ',$date);
$nuovaora=strtotime($ora[1])+6*3600;
$line[] = 26;
//$line[] = $ora[0];
//$line[] = Date('H:i',$nuovaora);
$line[] =  $data_scad;
$line[] = $ora_scad;
    if (in_array($line[$key_provincia],$zona1)){
        $line[] = '21.25';//24;
        $line[] = '10';//10;
        $line[] = '11.25';//14;
    }else {
        $line[] = '21.25';//27;
        $line[] = '15';//12;
        $line[] = '6.25';//12;
    }
break;
case 20100:
$n=4;
$line[] = 27;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
    if (in_array($line[$key_provincia],$zona1)){
        $line[] = '21.25';//24;
        $line[] = '10';//10;
        $line[] = '11.25';//14;
    }else {
        $line[] = '21.25';//27;
        $line[] = '15';//12;
        $line[] = '6.25';//12;
    }
break;
case 20010:
$n=1;
$line[] = 28;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
if (in_array($line[$key_provincia],$zona1)){
	$line[] = 'x';
	$line[] = 'x';
	$line[] = 'x';
}else{
	$line[] = 'x';
    $line[] = 'x';
    $line[] = 'x';
}
break;
case 20045:
$n=1;
$line[] = 29;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
if (in_array($line[$key_provincia],$zona1)){
	$line[] = 'x';
	$line[] = 'x';
	$line[] = 'x';
}else{
	$line[] = 'x';
    $line[] = 'x';
    $line[] = 'x';
}
break;
case 90000:
$n=1;
$line[] = 30;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
if (in_array($line[$key_provincia],$zona1)){
	$line[] = 'x';
	$line[] = 'x';
	$line[] = 'x';
}else{
	$line[] = 'x';
    $line[] = 'x';
    $line[] = 'x';
}
break;
case 90038:
$n=1;
$line[] = 31;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
if (in_array($line[$key_provincia],$zona1)){
	$line[] = 'x';
	$line[] = 'x';
	$line[] = 'x';
}else{
	$line[] = 'x';
    $line[] = 'x';
    $line[] = 'x';
}
break;
case 90040:
$n=1;
$line[] = 32;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
if (in_array($line[$key_provincia],$zona1)){
	$line[] = 'x';
	$line[] = 'x';
	$line[] = 'x';
}else{
	$line[] = 'x';
    $line[] = 'x';
    $line[] = 'x';
}
break;
case 20060:
$n=1;
$line[] = 33;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
if (in_array($line[$key_provincia],$zona1)){
	$line[] = 'x';
	$line[] = 'x';
	$line[] = 'x';
}else{
	$line[] = 'x';
    $line[] = 'x';
    $line[] = 'x';
}
break;
case 20070:
$n=1;
$line[] = 34;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
if (in_array($line[$key_provincia],$zona1)){
	$line[] = 'x';
	$line[] = 'x';
	$line[] = 'x';
}else{
	$line[] = 'x';
    $line[] = 'x';
    $line[] = 'x';
}
break;
case 20101:
$n=1;
$line[] = 35;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
    if (in_array($line[$key_provincia],$zona1)){
        $line[] = '21.25';//24;
        $line[] = '10';//10;
        $line[] = '11.25';//14;
    }else {
        $line[] = '21.25';//27;
        $line[] = '15';//12;
    }
break;
case 20030:
$n=3650;
$line[] = 36;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
if (in_array($line[$key_provincia],$zona1)){
	$line[] = '16.15';//14;
	$line[] = '8';//7;
	$line[] = '8.15';//7;
}else{
	$line[] = '16.15';//17;
    $line[] = '10';//8;
    $line[] = '6.15';//9;
}
break;
case 60010:
$n=3650;
$line[] = 38;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
    if (in_array($line[$key_provincia],$zona1)){
        $line[] = '16.15';//14;
        $line[] = '8';//7;
        $line[] = '8.15';//7;
    }else{
        $line[] = '16.15';//17;
        $line[] = '10';//8;
        $line[] = '6.15';//9;
    }
break;
default:
$n=1;
$line[] = 37;
//$line[] = nworkingdaysafter($date,$n,$holidays);
//$line[] = '20:00';
$line[] =  $data_scad;
$line[] = $ora_scad;
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


//$data['phone'] = $data['customer_phone_number'];
//echo json_encode($data);


$nome = mres($data['name']);
$sql="select id from ost_user where name = '".$nome."'";
//echo $sql."<br><br>";
//else
//$sql="select ref_num from ost_ticket__cdata natural join ost_ticket where status_id!=2";
$result=db_query($sql);
$num = db_num_rows($result);
//$num =2;

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
  //echo $sql_1."<br>";
  $result =db_query($sql_1);

  $sql_2 = "INSERT INTO ost_user_email (id, user_id, address) VALUES ($id_mail,$id_user,'".$mail."')";
  //echo $sql_2."<br>";
  $result =db_query($sql_2);

}

curl_setopt($ch, CURLOPT_URL, $config['url']);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_USERAGENT, 'osTicket API Client v1.7');
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
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

//echo $config['url'];
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
