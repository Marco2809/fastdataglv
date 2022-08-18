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


if (!isset($_FILES['userfile']) || !is_uploaded_file($_FILES['userfile']['tmp_name'])) {
    ?>
    <?php
    $_SESSION['gettonecarta'] = md5(session_id() . time());
    ?>
    <form enctype="multipart/form-data" action="?" method="POST"
          onsubmit="document.getElementById('nexino').disabled=true;
document.getElementById('nexino').value='Submitting, please wait...';">
        <?php csrf_token(); ?>
        <input type="hidden" name="a" value="ticket_cartasi" />
        <input type="hidden" name="massive" value="1" />
        <input type="hidden" name="gettonecarta" value="<?php echo $_SESSION['gettonecarta'] ?>" />
        <input type="hidden" name="MAX_FILE_SIZE" value="3000000">
        <input name="userfile" type="file"></br>
        <input type="submit" value="Invia File" id="nexino">
    </form>

    <?php
}else{

    if ($_POST['gettonecarta'] == $_SESSION['gettonecarta']){
        $file = $_FILES['userfile']['tmp_name'];

        $keys = $line = $csv = array();

        $fh = fopen($file, 'r');
        set_time_limit(0);

        $keys = fgetcsv($fh, 1000, ";");
        unset($keys[count($keys)-1]);
        $keys = array_map('trim',$keys);

        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'id Intervento'),
                'ref_num'
            )
        );
        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'TERMID'),
                'cr'
            )
        );
        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'RISORSA'),
                'affected_resource_zz_wam_string1'
            )
        );
        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'RISORSA_NEW'),
                'affected_resource_zz_wam_string1'
            )
        );
        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'DTORD'),
                'zz_date1'
            )
        );
        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'INSEGNA'),
                'customer_middle_name'
            )
        );
        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'ABI'),
                'customer_last_name'
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
                array_keys($keys, 'REFERENTE'),
                'ref_contatto'
            )
        );
        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'REFERENTE'),
                'duedate'
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
                array_keys($keys, 'PREFISSO'),
                'customer_prefiss_number'
            )
        );
        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'TELEFONO'),
                'customer_phone_number'
            )
        );
        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'ORAAPERTURA'),
                'customer_zz_top_sp_ap_lun'
            )
        );
        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'ORACHIUSURA'),
                'customer_zz_top_sp_ch_lun'
            )
        );

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
        $key_data = array_search('zz_date1', $keys);
        $key_ordine = array_search('ref_num', $keys);
        $key_telefono = array_search('customer_phone_number', $keys);
        $key_prefix = array_search('customer_prefiss_number', $keys);
        $key_message = array_search('NOTE', $keys);
        $key_provincia = array_search('customer_location_l_addr1', $keys);
        $key_termid = array_search('cr', $keys);
        $key_datascad = array_search('DATA SCADENZA',$keys);

        if (strpos($_FILES['userfile']['name'], 'assist') !== false) {
            $key_subject = array_search('MALFUNZIONE', $keys);
        }else{
            $key_subject = array_search('CAUSALE', $keys);
        }

        $eternit=false;
        if (strpos($_FILES['userfile']['name'], 'updgrm') !== false)
            $eternit=true;

//$sql="select ref_num from ost_ticket__cdata natural join ost_ticket where status_id!=2";
        $sql="select ref_num from ost_ticket__cdata where 1";
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
            $dataordine=explode('/',trim($line[$key_data]));
            $dtordine=$dataordine[2].'-'.$dataordine[1].'-'.$dataordine[0];
            $line[$key_data]=strtotime($dtordine);
            $line[$key_termid]=(string) str_pad($line[$key_termid], 8, '0', STR_PAD_LEFT);
            $line[$key_telefono]= $line[$key_prefix].$line[$key_telefono];
            $stripped=preg_replace("(\(|\)|\-|\.|\+|[  ]+)","",$line[$key_telefono]);
            $line[$key_telefono]=(strlen($stripped)<7)?str_pad($stripped, 7, '0', STR_PAD_LEFT):$stripped;
            $line[$key_name] = str_replace("'","",$line[$key_name]);
            $line[$key_name] = str_replace('"','',$line[$key_name]);
            $line[$key_message] = str_replace("'","\'",$line[$key_message]);
            //$line[$key_telefono]=($line[$key_telefono]!='')?$line[$key_telefono]:'123456789';
            $line[$key_name] = str_replace("'","",$line[$key_name]);
            $line[]=isset($line[$key_name])?$line[$key_name]:null;
            $line[$key_name] = str_replace(" ","",$line[$key_name]);
            $line[]=isset($line[$key_name])?$line[$key_name].'@service-tech.org':null;

                $data_scadenza = substr($line[$key_datascad],6,4)."-".substr($line[$key_datascad],3,2)."-".substr($line[$key_datascad],0,2);
                $ora_scadenza = str_replace(".",":",substr($line[$key_datascad],11,5));

            $date=$dtordine;


            switch (trim(preg_replace('/[^A-Za-z]/u','',$line[$key_topicId]))) {



                case 'Disinstallazione':
                    $n=7;
                    $line[] = 12;
                    if($data_scadenza=="--") {
                        $line[] = nworkingdaysafter($date,$n,$holidays);
                        $line[] = '20:00';
                    } else {
                        $line[] = $data_scadenza;
                        $line[] = $ora_scadenza;
                    }
                    //$line[] = '20:00';
                    if (in_array($line[$key_provincia],$zona1)){
                        $line[] = '12';//14;
                        $line[] = '6';//7;
                        $line[] = '6';//7;
                    }else{
                        $line[] = '12';
                        $line[] = '6';
                        $line[] = '6';
                    }
                    break;
                case 'Installazione':
                    $n=2;
                    $line[] = 13;
                    if($data_scadenza=="--") {
                        $line[] = nworkingdaysafter($date,$n,$holidays);
                        $line[] = '20:00';
                    } else {
                        $line[] = $data_scadenza;
                        $line[] = $ora_scadenza;
                    }
                    if (in_array($line[$key_provincia],$zona1)){
                        $line[] = '18.5';//20;
                        $line[] = '11';//10;
                        $line[] = '7.5';//10;
                    }else{
                        $line[] = '18.5';
                        $line[] = '15';
                        $line[] = '3.5';
                    }
                    break;
                case 'Sostituzione':
                    $n=2;
                    $line[] = 14;
                    if($data_scadenza=="--") {
                        $line[] = nworkingdaysafter($date,$n,$holidays);
                        $line[] = '20:00';
                    } else {
                        $line[] = $data_scadenza;
                        $line[] = $ora_scadenza;
                    }
                    if (in_array($line[$key_provincia],$zona1)){
                        $line[] = '14';//20;
                        $line[] = '8';//10;
                        $line[] = '6';//10;
                    }else{
                        $line[] = '14';
                        $line[] = '10';
                        $line[] = '4';
                    }
                    break;
                case 'Riconfigurazionemassiva':
                    $n=3650;
                    $line[] = 15;
                    if($data_scadenza=="--") {
                        $line[] = nworkingdaysafter($date,$n,$holidays);
                        $line[] = '20:00';
                    } else {
                        $line[] = $data_scadenza;
                        $line[] = $ora_scadenza;
                    }
                    if (in_array($line[$key_provincia],$zona1)){
                        $line[] = '14';//20;
                        $line[] = '8';//10;
                        $line[] = '6';//10;
                    }else{
                        $line[] = '14';
                        $line[] = '10';
                        $line[] = '4';
                    }
                    break;
                case 'Cambiogestore':
                    $n=3650;
                    $line[] = 16;
                    if($data_scadenza=="--") {
                        $line[] = nworkingdaysafter($date,$n,$holidays);
                        $line[] = '20:00';
                    } else {
                        $line[] = $data_scadenza;
                        $line[] = $ora_scadenza;
                    }
                    if (in_array($line[$key_provincia],$zona1)){
                        $line[] = '14';//18;
                        $line[] = '8';//9;
                        $line[] = '6';//9;
                    }else{
                        $line[] = '14';
                        $line[] = '10';
                        $line[] = '4';
                    }
                    break;
                case 'ORDINEDIVERSO':
                    $n=3650;
                    $line[] = 43;
                    if($data_scadenza=="--") {
                        $line[] = nworkingdaysafter($date,$n,$holidays);
                        $line[] = '20:00';
                    } else {
                        $line[] = $data_scadenza;
                        $line[] = $ora_scadenza;
                    }
                    if (in_array($line[$key_provincia],$zona1)){
                        $line[] = 'x';//18;
                        $line[] = 'x';//9;
                        $line[] = 'x';//9;
                    }else{
                        $line[] = 'x';
                        $line[] = 'x';
                        $line[] = 'x';
                    }
                    break;
                default:
                    if($eternit) {
                        $n=3650;
                        $line[] = 39;
                        if($data_scadenza=="--") {
                            $line[] = nworkingdaysafter($date,$n,$holidays);
                            $line[] = '20:00';
                        } else {
                            $line[] = $data_scadenza;
                            $line[] = $ora_scadenza;
                        }
                    }else{
                        $n=1;
                        $line[] = 17;
                        if($data_scadenza=="--") {
                            $line[] = nworkingdaysafter($date,$n,$holidays);
                            $line[] = '20:00';
                        } else {
                            $line[] = $data_scadenza;
                            $line[] = $ora_scadenza;
                        }
                    }
                    if (in_array($line[$key_provincia],$zona1)){
                        $line[] = '17.5';
                        $line[] = '10';
                        $line[] = '7.5';
                    }else{
                        $line[] = '17.5';
                        $line[] = '15';
                        $line[] = '2.5';
                    }
            }


            $line[]=($line[$key_subject]!='')?$line[$key_subject]:'xxx';
            $line[]=$line[$key_message]=($line[$key_message]!='')?$line[$key_message]:'xxx';
            $line[]=$_SERVER['REMOTE_ADDR'];




            array_map('db_input', $line);


            array_walk(
                $line,
                function (&$entry) {
                    $entry = iconv('Windows-1252', 'UTF-8', $entry);
                }
            );

            /*
                  echo "<pre>";
            print_r($keys);
            echo "</pre>";


            */


            if ((strpos($_FILES['userfile']['name'], 'assist') !== false) or (strpos($_FILES['userfile']['name'], 'updgrm') !== false)) {
                $csv[] = array_combine($keys, array_filter($line));

                /*
                     echo "<pre>";
               print_r($keys);
               echo "</pre>";

                     echo "<pre>";
               print_r($line);
               echo "</pre>";
               */
            }else{
                echo "<pre>";
                //print_r($line);
                echo "</pre>";
                $csv[] = array_combine($keys, $line);

            }


        }

        /*
        if (isset($ordini)){
        $diff = array_diff($ordini, $ordini_assist);

        if (!empty($diff)){
        foreach ($diff as $ord){
                $id=db_query('select ticket_id from ost_ticket__cdata where ref_num='.$ord);
                while ($row = db_fetch_array($id )) {
                    db_query('UPDATE ost_ticket SET status_id=2, closed=NOW() WHERE ticket_id='.$row['ticket_id']);
        }

              }
        }

        }


        echo "<pre>";
        print_r($csv);
        echo "</pre>";
        die;
        */

    }
}
//array_pop($csv);

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
    set_time_limit(3000);

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

        $mail = str_replace("'","",$data['email']);
        $mail = str_replace(" ","",$data['email']);

        $sql1="select id from ost_user_email where address = '".$mail."'";
        //echo $sql."<br><br>";
        //else
        //$sql="select ref_num from ost_ticket__cdata natural join ost_ticket where status_id!=2";
        $result1=db_query($sql1);
        $num1 = db_num_rows($result1);
        $id_mail_1 = db_fetch_array($result1);
        //echo $num."<br><br>";
        if($num<1){

            echo $nome. " già esistente<br>";

            $sql_user = "SELECT MAX(id) as id_user FROM ost_user";
            $result_id =db_query($sql_user);
            $id_user = db_fetch_array($result_id);
            $id_user = $id_user['id_user']+1;

            $sql_user = "SELECT MAX(id) as id_email FROM ost_user_email";
            $result_id =db_query($sql_user);
            $id_mail = db_fetch_array($result_id);
            $id_mail = $id_mail['id_email']+1;
            if($num1>=1){
                $id_mail = $id_mail_1;
            }


            $mail = str_replace("'","",$data['email']);
            $mail = str_replace(" ","",$data['email']);



            $sql_1 = "INSERT INTO ost_user (id, org_id, default_email_id,status, name) VALUES ($id_user,0,$id_mail,0,'".$nome."')";
            $result =db_query($sql_1);

            if($num1<1){
                $sql_2 = "INSERT INTO ost_user_email (id, user_id, address) VALUES ($id_mail,$id_user,'".$mail."')";
                $result =db_query($sql_2);
            }

        }


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

        //echo json_encode($data);

        //echo $config['url'];


        if ($code != 201){
            if ($i % 2 == 0)
                echo "<tr style='font-size:80%; font-family:play; color:black;' class='pair'><td>".$result."</td><td>".$code."</td><td>".$error."</td><td></td><td>".$data['INSEGNA']."</td><td></td><td></td><td></td><td><font color='red'>Fail</font></td></tr>";
            else
                echo "<tr style='font-size:80%; font-family:play; color:black;' class='pair'><td>".$result."</td><td>".$code."</td><td>".$error."</td><td></td><td>".$data['INSEGNA']."</td><td></td><td></td><td></td><td><font color='red'>Fail</font></td></tr>";
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
if (!empty($ordini_assist)){
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
