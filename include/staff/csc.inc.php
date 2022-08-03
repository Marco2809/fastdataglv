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
    $_SESSION['gettonecsc'] = md5(session_id() . time());
    ?>
    <form enctype="multipart/form-data" action="?" method="POST"
          onsubmit="document.getElementById('cscino').disabled=true;
document.getElementById('cscino').value='Submitting, please wait...';">
        <?php csrf_token(); ?>
        <input type="hidden" name="a" value="ticket_csc" />
        <input type="hidden" name="massive" value="1" />
        <input type="hidden" name="gettonecsc" value="<?php echo $_SESSION['gettonecsc'] ?>" />
        <input name="csv" type="file"></br>
        <input type="submit" value="Invia File" id="cscino">
    </form>

    <?php
}else{

    if ($_POST['gettonecsc'] == $_SESSION['gettonecsc']){
        $file = $_FILES['csv']['tmp_name'];
        $keys = $line = $csv = array();

        $fh = fopen($file, 'r');
        set_time_limit(0);

        $keys = fgetcsv($fh, 1000, ";");

        unset($keys[count($keys)-1]);
        $keys = array_map('trim',$keys);

        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'ID intervento cliente'),
                'ref_num'
            )
        );

        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'id scheda'),
                'area_descrizione_intervento'
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
                array_keys($keys, 'CAP'),
                'customer_location_l_addr3'
            )
        );
        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'Insegna'),
                'customer_middle_name'
            )
        );
        /*$keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'Urgente'),
                'category_sym'
            )
        );*/
        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'ModelloNEW'),
                'affected_resource_zz_wam_string1'
            )
        );
        /*$keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'D/O Scadenza'),
                'ref_contatto'
            )
        );*/

        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'Tipo int.'),
                'group_last_name'
            )
        );
        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'ABI Rif.'),
                'customer_last_name'
            )
        );
        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'Località'),
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
                array_keys($keys, 'Tel.'),
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
                    array_keys($keys, 'Tel.'),
                    'tec_contatto_phone'
                )
            );
        }

        $keys = array_replace($keys,
            array_fill_keys(
                array_keys($keys, 'D/O Ricezione'),
                'zz_date1'
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
        $key_subject = array_search('customer_middle_name', $keys);
        $key_data = array_search('zz_date1', $keys);
        $key_termid = array_search('cr', $keys);
        $key_ordine = array_search('ref_num', $keys);
        $key_ordine2 = array_search('area_descrizione_intervento', $keys);
        $key_datascad = array_search('D/O Scadenza', $keys);
        $key_cliente = array_search('Cliente', $keys);



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
            if($line[$key_ordine]=="") $line[$key_ordine] = $line[$key_ordine2];
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
            //$nuovadata=substr($line[$key_data],5,4)."-".substr($line[$key_data],3,2)."-".substr($line[$key_data],0,2)." ".;

            $nuovadata= substr($line[$key_data],6,4)."-".substr($line[$key_data],3,2)."-".substr($line[$key_data],0,2);
            $line[$key_data]=strtotime($nuovadata);


            $date=$nuovadata;

            $data_scad = substr($line[$key_datascad],6,4)."-".substr($line[$key_datascad],3,2)."-".substr($line[$key_datascad],0,2);
            $ora_scad = substr($line[$key_datascad],11,2).":".substr($line[$key_datascad],14,2);
            //echo "DATA: ".$nuovadata;
            //echo "DATASCAD: ".$data_scad." ".$ora_scad;

//echo "DATA:".$line[$key_datascad];
            $date=date('Y-m-d',time());

            echo $line[$key_topicId];

            if(in_array($line[$key_provincia],$zona1)){
                $posto = "L";
            } else if(in_array($line[$key_provincia],$zona2)){
                $posto = "A";
            } $posto = "M";

            switch (trim($line[$key_topicId])) {


                case 'Assistenza':
                    if($line[$key_cliente]=="B2X Care"){
                        $ci = 55;
                        $ce = 0;
                        $ct = 55;
                        $id_topid = 59;
                    } else if($line[$key_cliente]=="Q8"){
                        $ci = 17.5;
                        if($posto=="L"){
                            $ce = 10;
                            $ct = 7.5;
                        } else {
                            $ce = 15;
                            $ct = 2.5;
                        }
                        $id_topic = 45;
                    } else if($line[$key_cliente]=="Yamamay"){
                        $ci = 17.5;
                        if($posto=="L"){
                            $ce = 10;
                            $ct = 7.5;
                        } else {
                            $ce = 15;
                            $ct = 2.5;
                        }
                        $id_topic = 45;
                    }
                    $n=180;
                    $line[] = $id_topic;
                    $line[] = $data_scad;
                    $line[] = $ora_scad;

                    $line[] = $ci;
                    $line[] = $ce;
                    $line[] = $ct;

                    break;
                case 'Installazione Massiva':
                    $n=180;
                    if($line[$key_cliente]=="B2X Care"){
                        $ci = 55;
                        $ce = 0;
                        $ct = 55;
                        $id_topid = 59;
                    } else if($line[$key_cliente]=="Q8"){
                        $ci = 17.5;
                        if($posto=="L"){
                            $ce = 10;
                            $ct = 7.5;
                        } else {
                            $ce = 15;
                            $ct = 2.5;
                        }
                        $id_topic = 45;
                    } else if($line[$key_cliente]=="Yamamay"){
                        $ci = 17.5;
                        if($posto=="L"){
                            $ce = 10;
                            $ct = 7.5;
                        } else {
                            $ce = 15;
                            $ct = 2.5;
                        }
                        $id_topic = 65;
                    }

                    break;
                case 'Disinstallazione':
                    $n=180;
                    if($line[$key_cliente]=="Q8"){
                        $ci = 17.5;
                        if($posto=="L"){
                            $ce = 6;
                            $ct = 11.5;
                        } else {
                            $ce = 6;
                            $ct = 11.5;
                        }
                        $id_topic = 46;
                    } else if($line[$key_cliente]=="Yamamay"){
                        $ci = 12;
                        if($posto=="L"){
                            $ce = 6;
                            $ct = 6;
                        } else {
                            $ce = 6;
                            $ct = 6;
                        }
                        $id_topic = 67;
                    }
                    break;
                case 'Installazione POS':
                    $n=180;
                    if($line[$key_cliente]=="Q8"){
                        $ci = 18.5;
                        if($posto=="L"){
                            $ce = 11;
                            $ct = 7.5;
                        } else {
                            $ce = 15;
                            $ct = 3.5;
                        }
                        $id_topic = 47;
                    } else if($line[$key_cliente]=="Yamamay"){
                        $ci = 18.5;
                        if($posto=="L"){
                            $ce = 11;
                            $ct = 7.5;
                        } else {
                            $ce = 15;
                            $ct = 3.5;
                        }
                        $id_topic = 68;
                    } else if($line[$key_cliente]=="WEB-KORNER"){
                        $ci = 26;
                        if($posto=="L"){
                            $ce = 13;
                            $ct = 13;
                        } else {
                            $ce = 15;
                            $ct = 11;
                        }
                        $id_topic = 62;
                    }
                    break;
                case 'Installazione POS(reinst. pos in loco)':
                    $n=180;
                    if($line[$key_cliente]=="Q8"){
                        $ci = 18.5;
                        if($posto=="L"){
                            $ce = 11;
                            $ct = 7.5;
                        } else {
                            $ce = 15;
                            $ct = 3.5;
                        }
                        $id_topic = 48;
                    } else if($line[$key_cliente]=="Yamamay"){
                        $ci = 18.5;
                        if($posto=="L"){
                            $ce = 11;
                            $ct = 7.5;
                        } else {
                            $ce = 15;
                            $ct = 3.5;
                        }
                        $id_topic = 70;
                    }
                    break;
                case 'Installazione POS Urgente':
                    $n=180;
                    if($line[$key_cliente]=="Q8"){
                        $ci = 28.5;
                        if($posto=="L"){
                            $ce = 11;
                            $ct = 17.5;
                        } else {
                            $ce = 15;
                            $ct = 13.5;
                        }
                        $id_topic = 49;
                    } else if($line[$key_cliente]=="Yamamay"){
                        $ci = 28.5;
                        if($posto=="L"){
                            $ce = 11;
                            $ct = 17.5;
                        } else {
                            $ce = 15;
                            $ct = 13.5;
                        }
                        $id_topic = 69;
                    }
                    break;
                case 'Installazione cassa successiva':
                    $n=180;
                    if($line[$key_cliente]=="Q8"){
                        $ci = 9.25;
                        if($posto=="L"){
                            $ce = 11;
                            $ct = -1.75;
                        } else {
                            $ce = 15;
                            $ct = -5.75;
                        }
                        $id_topic = 49;
                    } else if($line[$key_cliente]=="Yamamay"){
                        $ci = 9.25;
                        if($posto=="L"){
                            $ce = 11;
                            $ct = -1.75;
                        } else {
                            $ce = 15;
                            $ct = -5.75;
                        }
                        $id_topic = 69;
                    }
                    break;
                case 'Intervento Straordinario':
                    $n=180;
                    $line[] = 50;
                    $line[] = $data_scad;
                    $line[] = $ora_scad;

                    $line[] = 15;
                    $line[] = 1;
                    $line[] = 14;
                    break;
                case 'Intervento Tecnico':
                    $n=180;
                    if($line[$key_cliente]=="B2X Care"){
                        $ci = 55;
                        $ce = 0;
                        $ct = 55;
                        $id_topid = 60;
                    } else if($line[$key_cliente]=="Q8"){
                        $ci = 17.5;
                        if($posto=="L"){
                            $ce = 10;
                            $ct = 7.5;
                        } else {
                            $ce = 15;
                            $ct = 2.5;
                        }
                        $id_topic = 51;
                    } else if($line[$key_cliente]=="Yamamay"){
                        $ci = 17.5;
                        if($posto=="L"){
                            $ce = 10;
                            $ct = 7.5;
                        } else {
                            $ce = 15;
                            $ct = 2.5;
                        }
                        $id_topic = 66;
                    }
                    break;
                case 'Migrazione':
                    $n=180;
                    if($line[$key_cliente]=="Q8"){
                        $ci = 14;
                        if($posto=="L"){
                            $ce = 8;
                            $ct = 6;
                        } else {
                            $ce = 10;
                            $ct = 4;
                        }
                        $id_topic = 52;
                    } else if($line[$key_cliente]=="Yamamay"){
                        $ci = 14;
                        if($posto=="L"){
                            $ce = 8;
                            $ct = 6;
                        } else {
                            $ce = 10;
                            $ct = 4;
                        }
                        $id_topic = 76;
                    }
                    break;
                case 'Sostituzione MASSIVA':
                    $n=180;
                    if($line[$key_cliente]=="Q8"){
                        $ci = 14;
                        if($posto=="L"){
                            $ce = 8;
                            $ct = 6;
                        } else {
                            $ce = 10;
                            $ct = 4;
                        }
                        $id_topic = 53;
                    } else if($line[$key_cliente]=="Yamamay"){
                        $ci = 14;
                        if($posto=="L"){
                            $ce = 8;
                            $ct = 6;
                        } else {
                            $ce = 10;
                            $ct = 4;
                        }
                        $id_topic = 77;
                    }
                    break;
                case 'Sostituzione Terminale':
                    $n=180;
                    if($line[$key_cliente]=="Q8"){
                        $ci = 18.5;
                        if($posto=="L"){
                            $ce = 11;
                            $ct = 7.5;
                        } else {
                            $ce = 15;
                            $ct = 3.5;
                        }
                        $id_topic = 54;
                    } else if($line[$key_cliente]=="Yamamay"){
                        $ci = 18.5;
                        if($posto=="L"){
                            $ce = 11;
                            $ct = 7.5;
                        } else {
                            $ce = 15;
                            $ct = 3.5;
                        }
                        $id_topic = 71;
                    }
                    break;
                case 'Sostituzione POS urgente':
                    $n=180;
                    if($line[$key_cliente]=="Q8"){
                        $ci = 28.5;
                        if($posto=="L"){
                            $ce = 11;
                            $ct = 17.5;
                        } else {
                            $ce = 15;
                            $ct = 13.5;
                        }
                        $id_topic = 72;
                    } else if($line[$key_cliente]=="Yamamay"){
                        $ci = 28.5;
                        if($posto=="L"){
                            $ce = 11;
                            $ct = 17.5;
                        } else {
                            $ce = 15;
                            $ct = 13.5;
                        }
                        $id_topic = 73;
                    }
                    break;
                case 'Sostituzione cassa successiva':
                    $n=180;
                    if($line[$key_cliente]=="Q8"){
                        $ci = 9.25;
                        if($posto=="L"){
                            $ce = 11;
                            $ct = -1.75;
                        } else {
                            $ce = 15;
                            $ct = -5.75;
                        }
                        $id_topic = 75;
                    } else if($line[$key_cliente]=="Yamamay"){
                        $ci = 9.25;
                        if($posto=="L"){
                            $ce = 11;
                            $ct = -1.75;
                        } else {
                            $ce = 15;
                            $ct = -5.75;
                        }
                        $id_topic = 74;
                    }
                    break;
                case 'Uscita a vuoto':
                    $n=180;
                    if($line[$key_cliente]=="B2X Care"){
                        $ci = 24;
                        $ce = 0;
                        $ct = 24;
                        $id_topid = 78;
                    } else if($line[$key_cliente]=="Q8"){
                        $ci = 13;
                        if($posto=="L"){
                            $ce = 0;
                            $ct = 13;
                        } else {
                            $ce = 7;
                            $ct = 6;
                        }
                        $id_topic = 56;
                    } else if($line[$key_cliente]=="Yamamay"){
                        $ci = 13;
                        if($posto=="L"){
                            $ce = 0;
                            $ct = 13;
                        } else {
                            $ce = 7;
                            $ct = 6;
                        }
                        $id_topic = 80;
                    } else if($line[$key_cliente]=="WEB-KORNER"){
                        $ci = 24;
                        if($posto=="L"){
                            $ce = 0;
                            $ct = 24;
                        } else {
                            $ce = 0;
                            $ct = 24;
                        }
                        $id_topic = 81;
                    }
                    break;

                case 'Ore Extra':
                    $n=180;
                    if($line[$key_cliente]=="B2X Care"){
                        $ci = 24;
                        $ce = 0;
                        $ct = 24;
                        $id_topid = 61;
                    } else if($line[$key_cliente]=="Q8"){
                        $ci = 20;
                        if($posto=="L"){
                            $ce = 0;
                            $ct = 20;
                        } else {
                            $ce = 0;
                            $ct = 20;
                        }
                        $id_topic = 57;
                    } else if($line[$key_cliente]=="Yamamay"){
                        $ci = 20;
                        if($posto=="L"){
                            $ce = 0;
                            $ct = 20;
                        } else {
                            $ce = 5;
                            $ct = 15;
                        }
                        $id_topic = 80;
                    } else if($line[$key_cliente]=="WEB-KORNER"){
                        $ci = 20;
                        if($posto=="L"){
                            $ce = 0;
                            $ct = 20;
                        } else {
                            $ce = 0;
                            $ct = 20;
                        }
                        $id_topic = 82;
                    }
                    break;

                default:
                    $n=1;
                    $line[] = 45;
                    $line[] = $data_scad;
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

            //print_r($csv);

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

    //echo 'quauqa';
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
            $mail = str_replace("'","",$data['email']);

            $sql_1 = "INSERT INTO ost_user (id, org_id, default_email_id,status, name) VALUES ($id_user,0,$id_mail,0,'".$nome."')";
            //echo $sql_1."<br>";
            $result =db_query($sql_1);

            $sql_2 = "INSERT INTO ost_user_email (id, user_id, address) VALUES ($id_mail,$id_user,'".$mail."')";
            //echo $sql_2."<br>";
            $result =db_query($sql_2);

        }

        //echo json_encode($data);

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
