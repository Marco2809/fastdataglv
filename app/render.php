<?php
ini_set('display_errors','On');
error_reporting(E_ALL);

define('DEFAULT_WORK_FACTOR',8);
define('TIME_LOGOUT',900);

define('SOAP_USER', "ServiceTech");
define('SOAP_PASS', "ServiceTech01");
define('SOAP_URL', "http://10.194.202.143:8080/axis/services/USD_R11_WebService?wsdl");

$dest='domenico.zavattolo@service-tech.org';


if(!file_exists('../main.inc.php')) die('Fatal error... get technical support');

require_once('../main.inc.php');
//require_once('../bootstrap.php');

if(!defined('INCLUDE_DIR')) die('Fatal error... invalid setting.');

require_once(INCLUDE_DIR.'PasswordHash.php');

require_once(INCLUDE_DIR.'class.ticket.php');

global $cfg;


if (!empty($_POST['data'])){
     	
	$response = array();
	
	if ($cfg->isHelpDeskOffline()){
	     $response["status"] = 'noAuth';
		 $response["response"] = 'LG789';	
		 echo json_encode($response);
         die; 
	}
	
	if ($_POST['request']!='login'){
	$sql="SELECT created FROM ost_tokenapp WHERE staff_id='".$_POST['staff_id']."'";
    $result = db_query($sql) or die(db_error());
    if (db_num_rows($result) > 0) {
		while ($row = db_fetch_array($result)) {
			$created=(int) $row['created'];
		}
		
		if (time() >= ($created+TIME_LOGOUT)){
		    $response["status"] = 'noAuth';
		    $response["msg"] = 'Tempo scaduto ';//.$sql;
            echo json_encode($response);
            die;
		}
					  
	}else{
	        $response["status"] = 'noAuth';
	        $response["msg"] = 'Id non trovato ';//.$sql;
            echo json_encode($response);
            die;
	}
	
    }
	
	
    switch ($_POST['request']) {
          case 'login':
          $username=trim(json_decode($_POST['data'])->userid);
          $password=trim(json_decode($_POST['data'])->passwd);
          $sql="SELECT * FROM ost_staff WHERE username='".$username."'";
          
          $result = db_query($sql) or die(db_error());

          if (db_num_rows($result) > 0) {
	
                 
                 $response["response"] = array();
                 
                 while ($row = db_fetch_array($result)) {
     
                      $user = array();
                      $user["staff_id"] = $row["staff_id"];
                      $user["username"] = $row["username"];
                      $user["firstname"] = $row["firstname"];
                      $user["lastname"] = $row["lastname"];
                      $psw = $row["passwd"];
                      $response["response"]=$user;
                 }
                 
                 if (cmp($password,$psw)){
					 
					  $token=password_hash('',PASSWORD_DEFAULT);
					  $sql="SELECT token, created FROM ost_tokenapp WHERE staff_id=".$user["staff_id"];
                      $result = db_query($sql) or die(db_error());
                      if (db_num_rows($result) > 0) {
						  $sql="UPDATE ost_tokenapp SET token='".$token."',created=".time()." WHERE staff_id=".$user["staff_id"];
                          $result = db_query($sql) or die(db_error());
					  }else{
					      $sql="INSERT INTO ost_tokenapp (`staff_id`, `token`, `created`) VALUES (".$user["staff_id"].",'".$token."',".time().")";
                          $result = db_query($sql) or die(db_error());
					  }
					  $response["token"] = $token;	  
                 }else{
                      $response["status"] = 'ko';
                      $response["response"] = 'LG123';//password errata
                      //mail($dest,'Warning APP LG123','user: '.$username.' - pass: '.$password);
                      echo json_encode($response);
                      die;
				 }
                           
                 $response["status"] = 'ok';
                 echo json_encode($response);

          } else {

                $response["status"] = 'ko';
                $response["response"] = 'LG222';//username errato
                //mail($dest,'Warning APP LG222','user: '.$username.' - pass: '.$password);
                echo json_encode($response);
                die;
          }

          break;
          case 'open':
          
          if ($_POST['staff_id']==69){
		  $sql="SELECT * FROM ost_ticket t 
          LEFT JOIN ost_ticket__cdata c ON (t.ticket_id=c.ticket_id)
          LEFT JOIN ost_ticket_thread h ON (t.ticket_id=h.ticket_id AND h.thread_type='M')
          LEFT JOIN ost_province p ON (c.customer_location_l_addr1=p.siglaprovincia)
          LEFT JOIN ost_regioni r ON (p.idregione=r.idregione)
          LEFT JOIN ost_staff s ON (s.staff_id=69)
          LEFT JOIN ost_tokenapp k ON (k.staff_id=69)
          LEFT JOIN ost_ticket_status n ON (t.status_id=n.id)
          WHERE c.comm_id=73 AND t.status_id!=2";
            
		  }else{
          $sql="SELECT * FROM ost_ticket t 
          LEFT JOIN ost_ticket__cdata c ON (t.ticket_id=c.ticket_id)
          LEFT JOIN ost_ticket_thread h ON (t.ticket_id=h.ticket_id AND h.thread_type='M')
          LEFT JOIN ost_province p ON (c.customer_location_l_addr1=p.siglaprovincia)
          LEFT JOIN ost_regioni r ON (p.idregione=r.idregione)
          LEFT JOIN ost_staff s ON (t.staff_id=s.staff_id)
          LEFT JOIN ost_tokenapp k ON (t.staff_id=k.staff_id)
          LEFT JOIN ost_ticket_status n ON (t.status_id=n.id)
          WHERE t.staff_id='".$_POST['staff_id']."' AND t.status_id!=2 AND c.comm_id=73";
	      }
          //echo $sql;
          //die;
 
          $result = db_query($sql) or die(db_error());

          if (db_num_rows($result) > 0) {
	           
              $response["status"] = 'ok';
              $response["response"] = array();
              $tickets = array();
 
              while ($row = db_fetch_array($result)) {

                  $ticket = array();
                  $user = array();
                  $ticket["ticket_id"] = $row["ticket_id"];
                  $ticket["number"] = $row["number"];
                  $ticket["status_id"] = $row["status_id"];
                  $ticket["dept_id"] = $row["dept_id"];
                  $ticket["user_id"] = $row["user_id"];
                  $ticket["staff_id"] = $row["staff_id"];
                  $ticket["duedate"] = !empty($row["duedate"])?$row["duedate"]:date('Y-m-d H:i:s', strtotime('+1 day', strtotime($row["created"])));
                  $ticket["created"] = $row["created"];
                  $ticket["stato"] = $row["name"];
        
                  $ticket["subject"] = $row["subject"];
                  $ticket["body"] = strip_tags($row["body"]);
       
                  $ticket["ref_num"] = $row["ref_num"];
                  $ticket["zz_date1"] = !empty($row["zz_date1"])?date('Y-m-d H:i',$row["zz_date1"]):'N.A.';
                  $ticket["pt_number"] = $row["affected_resource_zz_wam_string1"];
                  $ticket["ufficio"] = $row["customer_middle_name"];
                  $ticket["via"] = $row["customer_location_l_addr2"];
                  $ticket["localita"] = $row["customer_location_l_addr7"];
                  $ticket["provincia"] = !empty($row["nomeprovincia"])?$row["nomeprovincia"]:'N.A.';
                  $ticket["regione"] = !empty($row["nomeregione"])?$row["nomeregione"]:'N.A.';;
                  $ticket["frazionario"] = $row["customer_last_name"];
                  $ticket["telefono"] = $row["customer_phone_number"];
        
                  $ticket["ap_lun"] = $row["customer_zz_top_sp_ap_lun"];
                  $ticket["ch_lun"] = $row["customer_zz_top_sp_ch_lun"];
                  $ticket["ap_mar"] = $row["customer_zz_top_sp_ap_mar"];
                  $ticket["ch_mar"] = $row["customer_zz_top_sp_ch_mar"];
                  $ticket["ap_mer"] = $row["customer_zz_top_sp_ap_mer"];
                  $ticket["ch_mer"] = $row["customer_zz_top_sp_ch_mer"];
                  $ticket["ap_gio"] = $row["customer_zz_top_sp_ap_gio"];
                  $ticket["ch_gio"] = $row["customer_zz_top_sp_ch_gio"];
                  $ticket["ap_ven"] = $row["customer_zz_top_sp_ap_ven"];
                  $ticket["ch_ven"] = $row["customer_zz_top_sp_ch_ven"];
                  $ticket["ap_sab"] = $row["customer_zz_top_sp_ap_sab"];
                  $ticket["ch_sab"] = $row["customer_zz_top_sp_ch_sab"];
                  
                  $user["username"] = $row["username"];
                  $user["firstname"] = $row["firstname"];
                  $user["lastname"] = $row["lastname"];
                  $user["pepito"] = 1;
                  $staff_id=$row["staff_id"];       
                  array_push($tickets, $ticket);
             }  
             


             utf8_encode_deep($tickets);


             $token=password_hash('',PASSWORD_DEFAULT);
             $sql="UPDATE ost_tokenapp SET token='".$token."',created=".time()." WHERE staff_id=".$staff_id;
             $result = db_query($sql) or die(db_error());
             
            
             
             $appo = array("user"=>$user,"tickets"=>$tickets);
			 $response['response']=$appo;
             $response['token']=$token;
             echo json_encode($response);
             

          } else {

             $response["status"] = 'ko';
             $response["response"] = 'OP454';//nessun ticket trovato
             //mail($dest,'Warning APP OP454','user: '.$_POST['staff_id']);

             echo json_encode($response);
             die;

          }
          
          
          break;
          case 'closed':

          break;
          case 'notresolve':
          //$note=json_decode($_POST['data'])->note;
          $note=db_input(json_decode($_POST['data'])->note);
          $ticket_id=json_decode($_POST['data'])->ticket_id;
          $staff_id=$_POST['staff_id'];
          //mail($dest,'Errore APP NR121','user: '.$staff_id.' - ticket_id: '.$ticket_id.'<br>'.$note1);
          if (empty($ticket_id) or empty($staff_id)){
			 $response["status"] = 'ko';
             $response["response"] = 'NR323';//Dati mancanti
             mail($dest,'Errore APP NR323','user: '.$staff_id.' - ticket_id: '.$ticket_id);
             echo json_encode($response);
             die;  
		  }
		  $sql="UPDATE ost_ticket SET status_id=9,dept_id=6,staff_id=3 WHERE ticket_id=".$ticket_id;

          if($result = db_query($sql)){
			  $sql="INSERT INTO ost_ticket_thread (pid, ticket_id, staff_id, user_id, thread_type, title, body, format, ip_address, created) 
              VALUES (0,".$ticket_id.",".$staff_id.",0,'N','Ticket non risolto',".$note.",'text','".$_SERVER['REMOTE_HOST']."',NOW())";
              if($result = db_query($sql)){
			    $response["status"] = 'ok';
                $response["response"] = Array('success'=>'Operazione eseguita');

                echo json_encode($response);
			  }else{
			    $response["status"] = 'ko';
                $response["response"] = 'NR121';//Impossibile aggiungere le note
                mail($dest,'Errore APP NR121','user: '.$staff_id.' - ticket_id: '.$ticket_id);
                echo json_encode($response);
                die;
			  }
		  }else{
		     $response["status"] = 'ko';
             $response["response"] = 'NR567';//Impossibile modificare lo stato
             mail($dest,'Errore APP NR567','user: '.$staff_id.' - ticket_id: '.$ticket_id);
             echo json_encode($response);
             die;
		  }
		  
		  $token=password_hash('',PASSWORD_DEFAULT);
          $sql="UPDATE ost_tokenapp SET token='".$token."',created=".time()." WHERE staff_id=".$staff_id;
          $result = db_query($sql) or die(db_error());
		        
          break;
          case 'resolve':
          
          $soap=false;
          
          if (json_decode($_POST['data'])->ticket->user_id==9){
		        $soapParameters = Array('username' => SOAP_USER, 'password' => SOAP_PASS) ;
                
                try{
				   $clientsoap = new SoapClient(SOAP_URL, $soapParameters);	
				   $sid=$clientsoap->login($soapParameters)->loginReturn;	
				   //$client->logout(array('sid'=>$sid));
				}catch(SoapFault $e){
				   $response["status"] = 'ko';
                   $response["response"] = 'SS666';
                   mail($dest,'Warning APP SS666','SDM out');
                   echo json_encode($response);
                   die;
				}
				
				$soap=true;
		  }
         
          
          $user_id=json_decode($_POST['data'])->ticket->user_id;
          $end_chiusura=strtotime(str_replace('T',' ',json_decode($_POST['data'])->info_ticket->date_fine));
          $start_chiusura=strtotime(str_replace('T',' ',json_decode($_POST['data'])->info_ticket->date_inizio));
          $guasto_riscontrato=db_input(json_decode($_POST['data'])->info_ticket->descrizione_guasto);
          $esito_operazioni=json_decode($_POST['data'])->info_ticket->esito;
          $descrizione_intervento=db_input(json_decode($_POST['data'])->info_ticket->motivo);
          $riassunto='';//json_decode($_POST['data'])->info_ticket->note;
          $note_man='';//json_decode($_POST['data'])->info_ticket->note;
          $cod_intervento=json_decode($_POST['data'])->info_ticket->codice_codifica_intervento;
          $desc_statocomponente=json_decode($_POST['data'])->info_ticket->codice_stato_componente;
          $category_analisiguasto=json_decode($_POST['data'])->info_ticket->codice_codifica_intervento;
          $area_descrizione_intervento=json_decode($_POST['data'])->info_ticket->codice_area_intervento;
          $start_partenza=strtotime(str_replace('T',' ',json_decode($_POST['data'])->info_ticket->date_partenza));
          $ptinterv=json_decode($_POST['data'])->info_ticket->pt_problem;
          $ptswap=json_decode($_POST['data'])->info_ticket->pt_number;
          
          
          
          $img1=json_decode($_POST['data'])->firma_tecnico;
          $img2=json_decode($_POST['data'])->firma_cliente;
          $firma_leggibile=db_input(json_decode($_POST['data'])->firma_leggibile);
          
          $ticket_id=json_decode($_POST['data'])->ticket->ticket_id;
          $staff_id=$_POST['staff_id'];
          
          $user_id=0; //sovrascrive il cliente
          
      
          
          //c'è una foto della check list?
          if (!empty(json_decode($_POST['data'])->file_name)){

          
          $file['name']=json_decode($_POST['data'])->file_name;
          $file['size']=json_decode($_POST['data'])->file_size;
          $b64=json_decode($_POST['data'])->file_data;
          $file['data']=base64_decode($b64);

          $attach_id = AttachmentFile::save($file);
   
          $sql="INSERT INTO ost_ticket_thread (pid, ticket_id, staff_id, user_id, thread_type, title, body, format, ip_address, created) 
              VALUES (0,".$ticket_id.",".$staff_id.",".$user_id.",'N','Check List','Check List','html','".$_SERVER['REMOTE_HOST']."',NOW())";
          if (!$result = db_query($sql)){
		  $response["status"] = 'ko';
          $response["response"] = 'SS345';
          mail($dest,'Errore APP SS345','user: '.$staff_id.' - ticket_id: '.$ticket_id);
          echo json_encode($response);
          die;
		  
		  }
          $thread_id = db_insert_id();
   
          $sql="INSERT INTO ost_ticket_attachment (ticket_id, file_id, ref_id, inline, created) 
              VALUES (".$ticket_id.",".$attach_id.",".$thread_id.",0,NOW())";
          if (!$result = db_query($sql)){
		  $response["status"] = 'ko';
          $response["response"] = 'SS346';
          mail($dest,'Errore APP SS346','user: '.$staff_id.' - ticket_id: '.$ticket_id);
          echo json_encode($response);
          die;
		  
		  }
          
	      }
          
          
          
          $sql="UPDATE ost_ticket SET status_id=2, closed=NOW() WHERE ticket_id=".$ticket_id;
          if (!$result = db_query($sql)){
		  $response["status"] = 'ko';
          $response["response"] = 'SS347';
          mail($dest,'Errore APP SS347','user: '.$staff_id.' - ticket_id: '.$ticket_id);
          echo json_encode($response);
          die;
		  
		  }
          
          if (!$result = db_query("UPDATE ost_ticket__cdata SET zz_dt_clmghw='$end_chiusura', zz_data_inizio_intervento_man='$start_chiusura', zz_guasto_riscontrato=$guasto_riscontrato, zz_esito_op='$esito_operazioni', zz_desc_op_eff=$descrizione_intervento, zz_intervento_manutentore='$riassunto', zz_mgnote='$note_man', cod_intervento='$cod_intervento', desc_statocomponente='$desc_statocomponente', category_analisiguasto='$category_analisiguasto', ref_contatto=$firma_leggibile, area_descrizione_intervento='$area_descrizione_intervento',  status_sym='Chiuso da Manutentore',data_partenza='$start_partenza',zz_ci_ptinterv='$ptinterv',zz_ci_ptswap='$ptswap' WHERE ticket_id='$ticket_id'")){
          $response["status"] = 'ko';
          $response["response"] = 'SS350';
          mail($dest,'Errore APP SS350','user: '.$staff_id.' - ticket_id: '.$ticket_id);
          echo json_encode($response);
          die;
	      }
	      
	      
	      
          
          //allego rapportino
          $ticket=Ticket::lookup($ticket_id);
          
          $file_r['name']='Report_'.$ticket_id.'.pdf';
          $file_r['data']=$ticket->pdfExport_app('',0,$img1,$img2);

          $attach_id_r = AttachmentFile::save($file_r);
   
          $sql="INSERT INTO ost_ticket_thread (pid, ticket_id, staff_id, user_id, thread_type, title, body, format, ip_address, created) 
              VALUES (0,".$ticket_id.",".$staff_id.",".$user_id.",'N','Report','Report','html','".$_SERVER['REMOTE_HOST']."',NOW())";
          if (!$result = db_query($sql)){
		  $response["status"] = 'ko';
          $response["response"] = 'SS348';
          mail($dest,'Errore APP SS348','user: '.$staff_id.' - ticket_id: '.$ticket_id);
          echo json_encode($response);
          die;
		  
		  }
          $thread_id_r = db_insert_id();
   
          $sql="INSERT INTO ost_ticket_attachment (ticket_id, file_id, ref_id, inline, created) 
              VALUES (".$ticket_id.",".$attach_id_r.",".$thread_id_r.",0,NOW())";
          if (!$result = db_query($sql)){
		  $response["status"] = 'ko';
          $response["response"] = 'SS349';
          mail($dest,'Errore APP SS349','user: '.$staff_id.' - ticket_id: '.$ticket_id);
          echo json_encode($response);
          die;
		  
		  }
		  
		  ###########tempi#########
	      $inflag=$ticket->isOverdue()?1:0;
	      $inter_time=$end_chiusura-$start_chiusura;
	      $ticket_time=$end_chiusura-$start_partenza;
	      $query_tempi = 'SELECT ticket_id FROM `ost_ticket_tempi` WHERE `ticket_id`='.$ticket_id;
          
          if(!db_query($query_tempi) || !db_affected_rows()){
                db_query("INSERT INTO ost_ticket_tempi (ticket_id,in_sla,inter_time,ticket_time,last_update) VALUES ('$ticket_id','$inflag','$inter_time','$ticket_time',now())");
		  }else{
				db_query("UPDATE ost_ticket_tempi SET in_sla='$inflag', inter_time='$inter_time', ticket_time='$ticket_time', last_update=now() WHERE ticket_id='$ticket_id'");
		  }
				
		 if (isset($ptinterv)&&isset($ptswap)) {
					
				$sqlsw="INSERT INTO ost_ticket_thread (pid,ticket_id,staff_id,user_id,thread_type,poster,title, body, format, ip_address, created)
                VALUES 
                (0,".$ticket_id.",0,0,'N','SYSTEM','Swap di asset', 'PT Number IN: ".$ptinterv." - PT Number OUT: ".$ptswap."', 'html','127.0.0.1',NOW())";  
	            $sw=db_query($sqlsw);	
					
		  }
	      #########finetempi#######
		  
		  ####################SOAP##################
		  
		  if($soap){		    
			  
		  $sql = 'SELECT active,source,number,user_id,ref_num,cr,zz_date6,zz_mgnote,zz_dt_callagt,zz_dt_recall,zz_tecreason,zz_dt_restart,zz_dt_clmghw,zz_data_inizio_intervento_man,zz_guasto_riscontrato,zz_intervento_manutentore,zz_ricambi_sostituiti,zz_esito_op,zz_desc_op_eff,pc_flag,pc_sn,zz_ci_ptinterv,zz_ci_ptswap FROM ost_ticket__cdata  NATURAL JOIN ost_ticket WHERE `ticket_id`='.$ticket_id;	
		  
		  $cr_number = db_query($sql);
		  while ($row = db_fetch_array($cr_number )) {
        
          $sorgente= $row['source'];
          $ticket_interno = $row['number'];
          $poste = $row['user_id'];
          $utente = "Service Tech";
          $resultasi ="cr:".$row['cr'];
          $data_previsto_intervento = $row['zz_date6'];
          $note_manutentore = $row['zz_mgnote'];
          $data_contatto_utente = $row['zz_dt_callagt'];
          $data_appuntamento = $row['zz_dt_recall'];
          $motivo_sospensione = $row['zz_tecreason'];
          $data_ripresa_attivita = $row['zz_dt_restart'];
          $data_proposta_chiusura = $row['zz_dt_clmghw'];
          $inizio_intervento = $row['zz_data_inizio_intervento_man'];
          $guasto_riscontrato = db_input($row['zz_guasto_riscontrato']);
          $intervento = $row['zz_intervento_manutentore'];
          $ricambi_sostituiti = $row['zz_ricambi_sostituiti'];
          $ptinterv = $row['zz_ci_ptinterv'];
	      $ptswap = $row['zz_ci_ptswap'];
	      
          if ($row['zz_esito_op']=='Sostituito'){
            $esito_operazioni_effettuate = 'zzesitoop:400001';
	      }elseif($row['zz_esito_op']=='Riparato'){
			$esito_operazioni_effettuate = 'zzesitoop:400002';
		  }elseif($row['zz_esito_op']=='ChiusodaRemoto'){
			$esito_operazioni_effettuate = 'zzesitoop:400003';
		  }elseif($row['zz_esito_op']=='CausaPoste'){
			$esito_operazioni_effettuate = 'zzesitoop:400004';
		  }else{
		    $esito_operazioni_effettuate = $row['zz_esito_op'];
		  }
		  
          $operazioni_effettuate = $row['zz_desc_op_eff'];
          //$pc_flag=$row['pc_flag'];
		  //$pc_sn=$row['pc_sn'];
		  $active=$row['active'];
		  $problem=$row['ref_num'];
          }
        
       

		 
        
         
         
		 	 
      
		  $parametri = Array('sid' => $sid, 'objectType' => "cr",
          'whereClause' => "ref_num = '$problem'", 'maxRows'=>100, 'attributes'=> array ('string'=> array('status.sym','active')));
          $result = $clientsoap->doSelect($parametri)->doSelectReturn;
          $result .= "@#@#@#@";

         
          preg_match("/status.sym(.*?)active/s", $result, $matches);
          $stato_cliente=trim(strip_tags($matches[1]));
         
          preg_match("/active(.*?)@#@#@#@/s", $result, $matches);
          $active=trim(strip_tags($matches[1]));
          $active=preg_replace('/[^0-9]/u','', $active);
       
         
		 
		  if($stato_cliente=="Sospeso da Manutentore"){
		 
		    $data_ripresa_attivita=$data_proposta_chiusura-300;	 
		    /** upload: ripreso in carico **/
            $attrVals = array ('status','crs:15872296','zz_dt_restart',$data_ripresa_attivita);
            $attributes = array('status.sym');
            $par_update= array('sid' => $sid,'objectHandle'=>$resultasi, 'attrVals'=>$attrVals, 'attributes'=>$attributes);
            $risultato = $clientsoap->updateObject($par_update)->updateObjectReturn;
            /****/
		 
		  }	 
		 
           /** upload: proposta chiusura **/
          if ($active==1){
             $attrVals = array ('status','crs:15872291',
             'zz_dt_clmghw',$data_proposta_chiusura,
             'zz_mgnote',$note_manutentore,
             'zz_data_inizio_intervento_man',$inizio_intervento,
             'zz_guasto_riscontrato',$guasto_riscontrato,
             'zz_intervento_manutentore',$intervento,
             'zz_ricambi_sostituiti',$ricambi_sostituiti,
             'zz_esito_op',$esito_operazioni_effettuate,
             'zz_desc_op_eff',$operazioni_effettuate,
             'zz_ci_ptinterv',$ptinterv,
	         'zz_ci_ptswap',$ptswap);
          }elseif($active==0){
             $attrVals = array ('zz_dt_clmghw',$data_proposta_chiusura,
             'zz_mgnote',$note_manutentore,
             'zz_data_inizio_intervento_man',$inizio_intervento,
             'zz_guasto_riscontrato',$guasto_riscontrato,
             'zz_intervento_manutentore',$intervento,
             'zz_ricambi_sostituiti',$ricambi_sostituiti,
             'zz_esito_op',$esito_operazioni_effettuate,
             'zz_desc_op_eff',$operazioni_effettuate,
             'zz_ci_ptinterv',$ptinterv,
	         'zz_ci_ptswap',$ptswap);
          }    
         
          $attributes = array('status.sym');
          $par_update= array('sid' => $sid,'objectHandle'=>$resultasi, 'attrVals'=>$attrVals, 'attributes'=>$attributes);
          if ($risultato = $clientsoap->updateObject($par_update)->updateObjectReturn){
	      $sql="INSERT INTO ost_ticket_thread (pid, ticket_id, staff_id, user_id, thread_type, title, body, format, ip_address, created) 
              VALUES (0,".$ticket_id.",".$staff_id.",0,'N','Ticket chiuso da APP',".$guasto_riscontrato.",'text','".$_SERVER['REMOTE_HOST']."',NOW())";
          db_query($sql);		  
          mail($dest,'Chiusura soap da APP',$ticket_id);
          }else{
          mail($dest,'Problemi chiusura soap da APP',$ticket_id);
	      }
          /****/
             
         
   
          $clientsoap->logout(array('sid'=>$sid));
          unset ($soap);
        
	      }
	      
		  ##########################################
          
          $response["status"] = 'ok';
          $response["response"] = Array('success'=>'Ticket chiuso con successo');
          
          $token=password_hash('',PASSWORD_DEFAULT);
          $sql="UPDATE ost_tokenapp SET token='".$token."',created=".time()." WHERE staff_id=".$staff_id;
          $result = db_query($sql) or die(db_error()); 
          $response["token"]=$token;
          echo json_encode($response);
               
          break;
	  }

	
}else{
   //header('HTTP/1.0 401 Unauthorized'); 
   echo 'ok';  
}


function cmp($passwd,$hash,$work_factor=0){
        
        if($work_factor < 4 || $work_factor > 31)
            $work_factor=DEFAULT_WORK_FACTOR;

        $hasher = new PasswordHash($work_factor,FALSE);

        return ($hasher && $hasher->CheckPassword($passwd,$hash));
}

function utf8_encode_deep(&$input) {
	if (is_string($input)) {
		$input = utf8_encode($input);
	} else if (is_array($input)) {
		foreach ($input as &$value) {
			utf8_encode_deep($value);
		}
		
		unset($value);
	} else if (is_object($input)) {
		$vars = array_keys(get_object_vars($input));
		
		foreach ($vars as $var) {
			utf8_encode_deep($input->$var);
		}
	}
}

function cmp_token($id,$token) {
    $sql="SELECT token FROM ost_tokenapp WHERE staff_id='".$id."'";
    $result = db_query($sql) or die(db_error());
    if (db_num_rows($result) > 0) {
		while ($row = db_fetch_array($result)) {
			$user_token=$row['token'];
		}
		if ($user_token==$token){
			return true;
		}else{
			return false;
		}
	}else{
	    return false;
	}	
}

?>

