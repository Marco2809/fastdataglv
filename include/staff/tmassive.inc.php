<?php
//ini_set('display_errors','On');
//error_reporting(E_ALL);
$config = array(
        'url'=>'62.75.155.64/api/http.php/tickets.json',
        'key'=>'07B4D26F5CBB16DAD3508C7456D2473C'
        );
        
if (!isset($_FILES['userfile']) || !is_uploaded_file($_FILES['userfile']['tmp_name'])) {
?>

<form enctype="multipart/form-data" action="?" method="POST">
<?php csrf_token(); ?>
<input type="hidden" name="a" value="ticket_massivi" />
<input type="hidden" name="massive" value="1" />
  <input type="hidden" name="MAX_FILE_SIZE" value="30000">
  Invia questo file: <input name="userfile" type="file"></br>
  <input type="submit" value="Invia File">
</form>

<?php
}else{

$file = $_FILES['userfile']['tmp_name'];

$keys = $line = $csv = array();

$fh = fopen($file, 'r');

$keys = fgetcsv($fh, 1000, "|");
unset($keys[count($keys)-1]);


$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'ORDINE'),
        'ref_num'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'TIPO_ORDINE'),
        'group_last_name'
    )
);
$keys = array_replace($keys,
    array_fill_keys(
        array_keys($keys, 'LUOGO'),
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
        array_keys($keys, 'TELEFONO'),
        'customer_phone_number'
    )
);


$keys[]='name';
$keys[]='email';
$keys[]='topicId';
$keys[]='subject';
$keys[]='message';
$keys[]='ip';

$key_name = array_search('CLIEN', $keys);
$key_topicId = array_search('group_last_name', $keys);
$key_subject = array_search('CAUSALE', $keys);
$key_message = array_search('NOTE', $keys);

while (!feof($fh)) {
	$line = fgetcsv($fh, 1000, "|");
	unset($line[count($line)-1]);

	$line[]=isset($line[$key_name])?$line[$key_name]:null;
	$line[]=isset($line[$key_name])?$line[$key_name].'@service-tech.org':null;
	$line[]=$line[$key_topicId]=='DISINSTALLAZIONE'?12:13;
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



	if (strpos($_FILES['userfile']['name'], 'assist') !== false) {
      $csv[] = array_combine($keys, array_filter($line));
    }else{
      $csv[] = array_combine($keys, $line);
    }
}

/*
echo "<pre>";
print_r($csv);
echo "</pre>";
die;
*/

}



if (!empty($csv)){
	
function_exists('curl_version') or die('CURL support required');
function_exists('json_encode') or die('JSON support required');	

array_pop($csv);

echo '<table width=100% style="font-size:100%; font-family:play; color:black;"><tr><td>Ticket cliente</td><td>Cliente</td><td>Email</td><td>Id Commessa</td><td>Argomento</td><td>Soggetto</td><td>Messaggio</td><td>Open date</td><td>IMAC</td><td>PT number</td><td>Riferimento contatto</td><td>Creato</td></tr>';	
$i=0;

#set timeout
set_time_limit(30);

#curl post

$ch = curl_init();

foreach ($csv as $data){

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
echo "<tr style='font-size:80%; font-family:play; color:black;' class='pair'><td>".$data['TIPO_ORDINE']."</td><td>".$data['CLIEN']."</td><td>".$data['STAB']."</td><td>".$data['ABI']."</td><td>".$data['INSEGNA']."</td><td>".$data['INDIRIZZO']."</td><td>".$data['CAP']."</td><td>".$data['LUOGO']."</td><td>".$data['PROV']."</td><td>".$data['REFERENTE']."</td><td>".$data['ORAAPERTURA']."</td><td><font color='red'>Fail</font></td></tr>";
 else
echo "<tr style='font-size:80%; font-family:play; color:black;' class='pair'><td>".$data['TIPO_ORDINE']."</td><td>".$data['CLIEN']."</td><td>".$data['STAB']."</td><td>".$data['ABI']."</td><td>".$data['INSEGNA']."</td><td>".$data['INDIRIZZO']."</td><td>".$data['CAP']."</td><td>".$data['LUOGO']."</td><td>".$data['PROV']."</td><td>".$data['REFERENTE']."</td><td>".$data['ORAAPERTURA']."</td><td><font color='red'>Fail</font></td></tr>";
}else{
if ($i % 2 == 0)
echo "<tr style='font-size:80%; font-family:play; color:black;' class='pair'><td>".$data['TIPO_ORDINE']."</td><td>".$data['CLIEN']."</td><td>".$data['STAB']."</td><td>".$data['ABI']."</td><td>".$data['INSEGNA']."</td><td>".$data['INDIRIZZO']."</td><td>".$data['CAP']."</td><td>".$data['LUOGO']."</td><td>".$data['PROV']."</td><td>".$data['REFERENTE']."</td><td>".$data['ORAAPERTURA']."</td><td><font color='gren'>OK</font></td></tr>";
 else
echo "<tr style='font-size:80%; font-family:play; color:black;' class='pair'><td>".$data['TIPO_ORDINE']."</td><td>".$data['CLIEN']."</td><td>".$data['STAB']."</td><td>".$data['ABI']."</td><td>".$data['INSEGNA']."</td><td>".$data['INDIRIZZO']."</td><td>".$data['CAP']."</td><td>".$data['LUOGO']."</td><td>".$data['PROV']."</td><td>".$data['REFERENTE']."</td><td>".$data['ORAAPERTURA']."</td><td><font color='gren'>OK</font></td></tr>";
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
?>


