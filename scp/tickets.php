<?php
/*************************************************************************
    tickets.php

    Handles all tickets related actions.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require('staff.inc.php');
require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.dept.php');
require_once(INCLUDE_DIR.'class.filter.php');
require_once(INCLUDE_DIR.'class.canned.php');
require_once(INCLUDE_DIR.'class.json.php');
include_once(PEAR_DIR.'Mail.php');
require_once(INCLUDE_DIR.'class.dynamic_forms.php');
require_once(INCLUDE_DIR.'class.export.php');       // For paper sizes

/*error_reporting(E_ALL);
ini_set("display_errors", 1);
*/



$coopper=array(18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38);
function prepara_sql($value)
        {
        $cerca = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
        $rimpiazza = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");

        return str_replace($cerca, $rimpiazza, $value);
        }


$page='';
$ticket = $user = null; //clean start.
//LOCKDOWN...See if the id provided is actually valid and if the user has access.
if($_REQUEST['id']) {
    if(!($ticket=Ticket::lookup($_REQUEST['id'])))
         $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('ticket'));
    elseif(!$ticket->checkStaffAccess($thisstaff)) {
        $errors['err']=__('Access denied. Contact admin if you believe this is in error');
        $ticket=null; //Clear ticket obj.
    }
}

//Lookup user if id is available.
if ($_REQUEST['uid']) {
    $user = User::lookup($_REQUEST['uid']);
}
elseif (!isset($_REQUEST['advsid']) && @$_REQUEST['a'] != 'search'
    && !isset($_REQUEST['status']) && isset($_SESSION['::Q'])
) {
    $_REQUEST['status'] = $_SESSION['::Q'];
}
// Configure form for file uploads
$response_form = new Form(array(
    'attachments' => new FileUploadField(array('id'=>'attach',
        'name'=>'attach:response',
        'configuration' => array('extensions'=>'')))
));
$note_form = new Form(array(
    'attachments' => new FileUploadField(array('id'=>'attach',
        'name'=>'attach:note',
        'configuration' => array('extensions'=>'')))
));

//At this stage we know the access status. we can process the post.
if($_POST && !$errors):

    if($ticket && $ticket->getId()) {
        //More coffee please.
        $errors=array();
        $lock=$ticket->getLock(); //Ticket lock if any
        switch(strtolower($_POST['a'])):
        case 'reply':

            $planner = db_query('SELECT pln_alpha, pc_flag,cr FROM '.TICKET_TABLE.'__cdata WHERE `ticket_id`='.$ticket->getId());
            while ($row = db_fetch_array($planner )) {
				$pln=$row['pln_alpha'];
				$pc_flag=$row['pc_flag'];
				$resultasi ="cr:".$row['cr'];
            }


            if(!$thisstaff->canPostReply()){
                $errors['err'] = __('Action denied. Contact admin for access');
                }
            else {

                if(!$_POST['response'])
                    //$errors['response']=__('Response required');
                    //$_POST['response']="&nbsp;";
                    $_POST['response']="Ticket risposto";
                //Use locks to avoid double replies
                if($lock && $lock->getStaffId()!=$thisstaff->getId()){
                    $errors['err']=__('Action Denied. Ticket is locked by someone else!');
                }

                //Make sure the email is not banned
                if(!$errors['err'] && TicketFilter::isBanned($ticket->getEmail())){
                    $errors['err']=__('Email is in banlist. Must be removed to reply.');
                }
                //if($pc_flag!=1) {
                    //$errors['err']="Si sta cercando di modificare lo stato e/o l'assegnazione del ticket senza averlo prima preso in carico.";
                //}
            }

            //If no error...do the do.
            //if($_POST['reply_status_id']==12){$ticket->transfer(7, 'trasferito al laboratorio');} //trasferimento automatico al LAB su cambiamento di stato




            $vars = $_POST;

            //print_r($_POST);
            //die();


            $vars['cannedattachments'] = $response_form->getField('attachments')->getClean();

            if(!$errors && ($response=$ticket->postReply($vars, $errors, $_POST['emailreply']))) {

                 switch($thisstaff->getDeptId()){
            case 4: //screener

            if ($_POST['reply_status_id']==11) { //in carico al magazzino
            $ticket->transfer(8, 'Trasferito al magazzino');
            $ticket->assign(14, "Assegnato alla coda del magazzino");
            }elseif($_POST['reply_status_id']==12){ //in carico al lab
		    $ticket->transfer(7, 'Trasferito al Laboratorio');
		    $ticket->assign(5, "Riassegnato alla coda del laboratorio");
            }

            break;
            case 5: //admin

            if ($_POST['reply_status_id']==11) { //in carico al magazzino
            $ticket->transfer(8, 'Trasferito al magazzino');
            $ticket->assign(14, "Assegnato alla coda del magazzino");
            }elseif($_POST['reply_status_id']==12){ //in carico al lab
		    $ticket->transfer(7, 'Trasferito al Laboratorio');
		    $ticket->assign(5, "Riassegnato alla coda del laboratorio");
            }

            break;
            case 6: //planner

            if ($_POST['reply_status_id']==11) { //in carico al magazzino
            $ticket->transfer(8, 'Trasferito al magazzino');
            $ticket->assign(14, "Assegnato alla coda del magazzino");
            }elseif($_POST['reply_status_id']==12){ //in carico al lab
		    $ticket->transfer(7, 'Trasferito al Laboratorio');
		    $ticket->assign(5, "Riassegnato alla coda del laboratorio");
            }

            break;
            case 7: //laboratorio

            if ($thisstaff->getId()==5) { //resp. laboratorio


            if ($_POST['reply_status_id']==19) { //in attesa parti
            $ticket->transfer(8, 'Trasferito al magazzino');
            $ticket->assign(14, "Assegnato alla coda del magazzino");
            }elseif($_POST['reply_status_id']==13){ //attesa preventivo
		    $ticket->transfer(6, 'Trasferito ai Planner');
		    $ticket->assign($pln, "Riassegnato alla coda dei Planner");
            }elseif($_POST['reply_status_id']==20){ //da dismettere
		    $ticket->transfer(6, 'Trasferito ai Planner');
		    $ticket->assign($pln, "Riassegnato alla coda dei Planner");
            }elseif($_POST['reply_status_id']==14){ //riparato lab
		    $ticket->transfer(6, 'Trasferito ai Planner');
		    $ticket->assign($pln, "Riassegnato alla coda dei Planner");
            }


            }else{ //tecnici

		    if ($_POST['reply_status_id']==17) { //da validare lab
            //$ticket->transfer(6, 'Trasferito ai Planner');
            $ticket->assign(5, "Riassegnato alla coda del laboratorio");
            }elseif($_POST['reply_status_id']==18){ //non riparato
		    //$ticket->transfer(6, 'Trasferito ai Planner');
		    $ticket->assign(5, "Riassegnato alla coda del laboratorio");
            }elseif($_POST['reply_status_id']==9){ //non risolto
		    $ticket->transfer(6, 'Trasferito ai Planner');
		    $ticket->assign($pln, "Non risolto dal tecnico");
            }
            }


            break;
            case 8: //magazzino


            if ($_POST['reply_status_id']==13) { //attesa preventivo
            $ticket->transfer(6, 'Trasferito ai Planner dal magazzino');
            $ticket->assign($pln, "Riassegnato alla coda dei planner");
            }elseif($_POST['reply_status_id']==15){ //lavorato magazzino
		    $ticket->transfer(6, 'Trasferito ai Planner dal magazzino');
		    $ticket->assign($pln, "Riassegnato alla coda dei planner");
            }elseif($_POST['reply_status_id']==12){ //in carico al lab
		    $ticket->transfer(7, 'Trasferito al Laboratorio dal magazzino');
		    $ticket->assign(5, "Riassegnato alla coda del laboratorio");
            }

            break;
            case 9: //partner


            if ($_POST['reply_status_id']==2) { //risolto
            $ticket->transfer(6, 'Trasferito ai Planner');
            $ticket->assign($pln, "Risolto da partner");
            }elseif($_POST['reply_status_id']==9){ //non risolto
		    $ticket->transfer(6, 'Trasferito ai Planner');
		    $ticket->assign($pln, "Non risolto da partner");
            }

            break;
            }

                $msg = sprintf(__('%s: Reply posted successfully '),
                        sprintf(__('Ticket: %s'),
                            sprintf('<a href="tickets.php?id=%d"><b>%s</b></a>',
                                $ticket->getId(), $ticket->getNumber()))
                        );

                // Clear attachment list
                $response_form->setSource(array());
                $response_form->getField('attachments')->reset();

                // Remove staff's locks
                TicketLock::removeStaffLocks($thisstaff->getId(),
                        $ticket->getId());

                // Cleanup response draft for this user
                Draft::deleteForNamespace(
                    'ticket.response.' . $ticket->getId(),
                    $thisstaff->getId());

                // Go back to the ticket listing page on reply
                $ticket = null;

            } elseif(!$errors['err']) {
                $errors['err']=__('Unable to post the reply. Correct the errors below and try again!');
                //$errors['err']="Attenzione! ".$errors['err'];
            }
            unset($_POST['response']);
            break;

        case 'transfer': /** Transfer ticket **/
            //Check permission
            if(!$thisstaff->canTransferTickets())
                $errors['err']=$errors['transfer'] = __('Action Denied. You are not allowed to transfer tickets.');
            else {

                //Check target dept.
                if(!$_POST['deptId'])
                    $errors['deptId'] = __('Select department');
                elseif($_POST['deptId']==$ticket->getDeptId())
                    $errors['deptId'] = __('Ticket already in the department');
                elseif(!($dept=Dept::lookup($_POST['deptId'])))
                    $errors['deptId'] = __('Unknown or invalid department');

                //Transfer message - required.
                if(!$_POST['transfer_comments'])
                    $errors['transfer_comments'] = __('Transfer comments required');
                elseif(strlen($_POST['transfer_comments'])<5)
                    $errors['transfer_comments'] = __('Transfer comments too short!');

                //If no errors - them attempt the transfer.
                if(!$errors && $ticket->transfer($_POST['deptId'], $_POST['transfer_comments'])) {
                    $msg = sprintf(__('Ticket transferred successfully to %s'),$ticket->getDeptName());
                    //Check to make sure the staff still has access to the ticket
                    if(!$ticket->checkStaffAccess($thisstaff))
                        $ticket=null;

                } elseif(!$errors['transfer']) {
                    $errors['err'] = __('Unable to complete the ticket transfer');
                    $errors['transfer']=__('Correct the error(s) below and try again!');
                }
            }
            break;

        case 'assign':


            $chiave = $ticket->getId();




             if(!$thisstaff->canAssignTickets())
                 $errors['err']=$errors['assign'] = __('Action Denied. You are not allowed to assign/reassign tickets.');
             else {

                 $id = preg_replace("/[^0-9]/", "",$_POST['assignId']);
                 $claim = (is_numeric($_POST['assignId']) && $_POST['assignId']==$thisstaff->getId());
                 $dept = $ticket->getDept();

                 if (!$_POST['assignId'] || !$id)
                     $errors['assignId'] = __('Select assignee');
                 elseif ($_POST['assignId'][0]!='s' && $_POST['assignId'][0]!='t' && !$claim)
                     $errors['assignId']= sprintf('%s - %s',
                             __('Invalid assignee'),
                             __('get technical support'));
                 elseif ($_POST['assignId'][0]!='s'
                         && $dept->assignMembersOnly()
                         && !$dept->isMember($id)) {
                     $errors['assignId'] = sprintf('%s. %s',
                             __('Invalid assignee'),
                             __('Must be department member'));
                 } elseif($ticket->isAssigned()) {
                     if($_POST['assignId'][0]=='s' && $id==$ticket->getStaffId())
                         $errors['assignId']=__('Ticket already assigned to the agent.');
                     elseif($_POST['assignId'][0]=='t' && $id==$ticket->getTeamId())
                         $errors['assignId']=__('Ticket already assigned to the team.');
                 }

                 //Comments are not required on self-assignment (claim)
                 if($claim && !$_POST['assign_comments'])
                     $_POST['assign_comments'] = sprintf(__('Ticket claimed by %s'),$thisstaff->getName());
                 /*rimuovo i controlli sulla nota di trasferimento  */
                 elseif(!$_POST['assign_comments'])
                     //$errors['assign_comments'] = __('Assignment comments required');
                     $_POST['assign_comments'] = sprintf('Ticket assegnato da: %s',$thisstaff->getName());
                 //elseif(strlen($_POST['assign_comments'])<5)
                         //$errors['assign_comments'] = __('Comment too short');

                 //if($pc_flag!=1) {
                   // $errors['err']="Si sta cercando di modificare lo stato e/o l'assegnazione del ticket senza averlo prima preso in carico.";
                 //}

				 //print_r($_POST);
                 //die();

                 if(!$errors && $ticket->assign($_POST['assignId'], $_POST['assign_comments'], !$claim)) {
					 if ($thisstaff->getDeptId()==4 || $thisstaff->getDeptId()==5 || $thisstaff->getDeptId()==6) {
					 $ticket->setStatus(23);
					 $ticket->setAnsweredState(1);
					 $ticket->setDeptId(7);

				     }
				     $idx=$_POST['assignId'];
				     $tecnico=preg_replace("/[^0-9]/", "", $idx);


###TARIFFE###


					 $zona=array('RM','RI','VT','LT','FR');
                //$zona2=array('AQ','PE','TE','CH');

				$topico=db_query("SELECT `topic_id`,`customer_location_l_addr1` FROM ".TICKET_TABLE." NATURAL JOIN ".TICKET_TABLE."__cdata WHERE `ticket_id`=".$ticket->getId());
				   while ($row=db_fetch_array($topico)){
		                 $utopico=$row['topic_id'];
		                 $utopica=trim($row['customer_location_l_addr1']);
	               }



				/*if (in_array($utopico, Array(15,16,17))){


               if(($utopica=='VT' || $utopica=='RI') && $utopico==17)
               $costo_ext =19.50;
               elseif(($utopica=='RM' || $utopica=='LT' || $utopica=='FR') && $utopico==17)
               $costo_ext =20;
               else
               $costo_ext =16;


				       $costo_int =($tecnico==80)?0:10;

				}elseif(in_array($utopico, Array(13,14))){

          if($utopica=='VT' || $utopica=='RI')
          $costo_ext =19.50;
          else
          $costo_ext =20;



				       $costo_int =($tecnico==80)?0:11;

				}elseif($utopico==12){

               $costo_ext =15;
				       $costo_int =($tecnico==80)?0:8;

				}elseif(in_array($utopico, Array(18,19,20,21,22,23))){

          if (in_array($utopica,$zona)){
               $costo_ext =25;
				       $costo_int =($tecnico==80)?0:11;
          }else{
               $costo_ext =25;

               if($tecnico==80)//ditta
               $costo_int=0;
               elseif($tecnico==89)//levino
               $costo_int=15;
               else
               $costo_int=12;

                    }

				}elseif(in_array($utopico, Array(24,25))){

          if (in_array($utopica,$zona)){
               $costo_ext =10;
				       $costo_int =($tecnico==80)?0:6;
          }else{
               $costo_ext =10;
				       $costo_int =($tecnico==80)?0:6;
                    }

				}elseif(in_array($utopico, Array(26,27,35))){

          if (in_array($utopica,$zona)){
               $costo_ext =25;
				       $costo_int =($tecnico==80)?0:10;
          }else{
               $costo_ext =25;

               if($tecnico==80)//ditta
               $costo_int=0;
               elseif($tecnico==89)//levino
               $costo_int=15;
               else
               $costo_int=12;


                    }

				}elseif($utopico==36 or $utopico==38){

          if (in_array($utopica,$zona)){
               $costo_ext =19;
				       $costo_int =($tecnico==80)?0:8;
          }else{
               $costo_ext =19;
				       $costo_int =($tecnico==80)?0:10;
               //$costo_int =($tecnico==89)?10:9; //TEC. LEVINO
                    }

				}elseif($utopico==39){

     					 $costo_ext =16;
     				   $costo_int =($tecnico==80)?0:10;

     		}elseif($utopico==40){

     					 $costo_ext =55;
     				   $costo_int =($tecnico==80)?0:32;

     		}elseif($utopico==41){

     					 $costo_ext =45;
     				   $costo_int =($tecnico==80)?0:23;

     		}elseif($utopico==42){

     					 $costo_ext =15;
     				   $costo_int =($tecnico==80)?0:14;

     		}elseif($utopico==43){

     					 $costo_ext =15;
     				   $costo_int =($tecnico==80)?0:8;

     		}else{

               $costo_ext =0;
				       $costo_int =0;
				}*/

                     /*if($tecnico==80) {
                         $costo_int=0;
                         $topico=db_query("SELECT `costo_ext` FROM ".TICKET_TABLE."__cdata WHERE `ticket_id`=".$ticket->getId());
                         $row=db_fetch_array($topico);
                         $costo_ext=$row['costo_ext'];
                         db_query("UPDATE ".TICKET_TABLE."__cdata SET costo_int='".$costo_int."',prezzo='".($costo_ext-$costo_int)."' WHERE `ticket_id`=".$ticket->getId());
                     }*/

				//db_query("UPDATE ".TICKET_TABLE."__cdata SET costo_int='".$costo_int."',prezzo='".($costo_ext-$costo_int)."' WHERE `ticket_id`=".$ticket->getId());



				     //Data previsto intervento tecnici
				     if ($_POST['ora_prev_inter_tec']!='00'&&$_POST['min_prev_inter_tec']!='00'){
				     $data_prev_inter_tec = strtotime($_POST['data_prev_inter_tec'].' '.$_POST['ora_prev_inter_tec'].':'.$_POST['min_prev_inter_tec']);
				     $res = db_query('UPDATE '.TICKET_TABLE.'__cdata SET zz_date6='.$data_prev_inter_tec.' WHERE `ticket_id`='.$ticket->getId());




				     }




                     if($claim) {
                         $msg = __('Ticket is NOW assigned to you!');
                     } else {
                         $msg=sprintf(__('Ticket assigned successfully to %s'), $ticket->getAssigned());
                         TicketLock::removeStaffLocks($thisstaff->getId(), $ticket->getId());
                         $ticket=null;
                     }
                 } elseif(!$errors['assign']) {
                     $errors['err'] = __('Unable to complete the ticket assignment').". ".$errors['err'];
                     $errors['assign'] = __('Correct the error(s) below and try again!');
                 }

             }
            break;

        case 'telefono1':
        if (isset($_POST['telefono1_andrea']) && !empty($_POST['telefono1_andrea'])){
        $res = db_query("UPDATE ".TICKET_TABLE."__cdata SET tec_contatto_phone='".$_POST['telefono1_andrea']."' WHERE `ticket_id`=".$ticket->getId());
        }
        break;
        case 'telefono':
        if (isset($_POST['telefono_andrea']) && !empty($_POST['telefono_andrea'])){
        $res = db_query("UPDATE ".TICKET_TABLE."__cdata SET customer_phone_number='".$_POST['telefono_andrea']."' WHERE `ticket_id`=".$ticket->getId());
        }
        break;
        case 'nuovoindirizzo':
        if (isset($_POST['nuovoindirizzo_andrea']) && !empty($_POST['nuovoindirizzo_andrea'])){
        $res = db_query("UPDATE ".TICKET_TABLE."__cdata SET customer_location_l_addr2='".prepara_sql($_POST['nuovoindirizzo_andrea'])."' WHERE `ticket_id`=".$ticket->getId());
        }
        break;
        case 'postnote': /* Post Internal Note */
            $vars = $_POST;
            $attachments = $note_form->getField('attachments')->getClean();
            $vars['cannedattachments'] = array_merge(
            $vars['cannedattachments'] ?: array(), $attachments);

            $wasOpen = ($ticket->isOpen());


            if(($note=$ticket->postNote($vars, $errors, $thisstaff))) {

                //$msg=__('Internal note posted successfully');
                $msg="Files allegati correttamente";
                // Clear attachment list
                $note_form->setSource(array());
                $note_form->getField('attachments')->reset();

                if($wasOpen && $ticket->isClosed())
                    $ticket = null; //Going back to main listing.
                else
                    // Ticket is still open -- clear draft for the note
                    Draft::deleteForNamespace('ticket.note.'.$ticket->getId(),
                        $thisstaff->getId());

            } else {

                if(!$errors['err'])
                    //$errors['err'] = __('Unable to post internal note - missing or invalid data.');
                    $errors['err'] = "Errore nell'allegare i files. Descrizione obbligatoria";

                //$errors['postnote'] = __('Unable to post the note. Correct the error(s) below and try again!');
                $errors['postnote'] ="Impossibile allegare i files! Correggi gli errori e riprova";
            }
            break;

        //INIZIO ERICSSON
         //miei case
        case 'sospensione': /* Post Internal Note */
            $chiave = $ticket->getId();
            $inizio_sospensione = strtotime($_POST['inizio_sospensione']); //controllare!!!
            $motivazione = prepara_sql(strip_tags($_POST['msg_sospeso']));
            //$motivazione = db_input($_POST['msg_sospeso']);

            $zz_dt_callagt = strtotime($_POST['zz_dt_callagt'].' '.$_POST['ora_iniziale'].':'.$_POST['minuti_iniziale']);
            $zz_dt_recall = strtotime($_POST['zz_dt_recall'].' '.$_POST['ora_finale'].':'.$_POST['minuti_finale']);


            $presa = db_query('SELECT source,user_id FROM '.TICKET_TABLE.' WHERE `ticket_id`='.$chiave);
            while ($row = db_fetch_array($presa )) {
				$sorgente=$row['source'];
				$poste=$row['user_id'];
            }




            $_POST['response']=$_POST['msg_sospeso'];

            if(!$thisstaff->canPostReply()){
                $errors['err'] = __('Action denied. Contact admin for access');
                }
            else {

                if($lock && $lock->getStaffId()!=$thisstaff->getId()){
                    $errors['err']=__('Action Denied. Ticket is locked by someone else!');
                }
                //Make sure the email is not banned
                if(!$errors['err'] && TicketFilter::isBanned($ticket->getEmail())){
                    $errors['err']=__('Email is in banlist. Must be removed to reply.');
                }
                if(!$_POST['msg_sospeso']) {
                    $errors['err']="Attenzione! Occorre specificare il motivo della sospensione";
                }
                //if($pc_flag!=1) {
                  //  $errors['err']="Si sta cercando di modificare lo stato e/o l'assegnazione del ticket senza averlo prima preso in carico.";
               // }


            }

            $vars = $_POST;
            $vars['cannedattachments'] = $response_form->getField('attachments')->getClean();


             //print_r($_POST);
            //die();

            if(!$errors && ($response=$ticket->postReply($vars, $errors, $_POST['emailreply']))) {

				db_query("UPDATE ost_ticket__cdata SET zz_dt_recall='$zz_dt_recall', zz_dt_callagt='$zz_dt_callagt', zz_tecreason=concat(zz_tecreason,' ', '$motivazione'), status_sym='Sospeso da Manutentore' WHERE ticket_id='$chiave'");
                $msg = sprintf(__('%s: Reply posted successfully '),
                        sprintf(__('Ticket: %s'),
                            sprintf('<a href="tickets.php?id=%d"><b>%s</b></a>',
                                $ticket->getId(), $ticket->getNumber()))
                        );


                $ticket->setStatus(21);
                //$msgg="UPDATE ost_ticket SET isoverdue=0,staff_id='".$thisstaff->getId()."',dept_id='".$thisstaff->getDeptId()."' WHERE ticket_id=".$chiave;
                $msgg="UPDATE ost_ticket SET isoverdue=0,staff_id=3,dept_id=6 WHERE ticket_id=".$chiave;

                db_query($msgg);
                //in caso di sospensione il ticket ritorna ai planner 10 marzo 2017




                // Clear attachment list
                $response_form->setSource(array());
                $response_form->getField('attachments')->reset();

                // Remove staff's locks
                TicketLock::removeStaffLocks($thisstaff->getId(),
                        $ticket->getId());

                // Cleanup response draft for this user
                Draft::deleteForNamespace(
                    'ticket.response.' . $ticket->getId(),
                    $thisstaff->getId());

                // Go back to the ticket listing page on reply
                $ticket = null;

            } elseif(!$errors['err']) {
                $errors['err']=__('Unable to post the reply. Correct the errors below and try again!');
            }

            unset($_POST['response']);
            break;

        case 'ripresaattivita': /* Post Internal Note */



            $adesso = time();
            $chiave = $ticket->getId();
            $ora_ripresa = strtotime($_POST['start_ripresa'].' '.$_POST['ora_ripresa'].':'.$_POST['minuti_ripresa']);


            $_POST['response']="Data ripresa in carico: ".date('d-m-Y H:i:s',$ora_ripresa);

            $presa = db_query('SELECT topic_id FROM '.TICKET_TABLE.' WHERE `ticket_id`='.$chiave);
            while ($row = db_fetch_array($presa )) {
				$categoria=$row['topic_id'];
            }


            if(!$thisstaff->canPostReply()){
                $errors['err'] = __('Action denied. Contact admin for access');
                }
            else {

                if(!$_POST['response'])
                    $errors['response']=__('Response required');
                //Use locks to avoid double replies
                if($lock && $lock->getStaffId()!=$thisstaff->getId()){
                    $errors['err']=__('Action Denied. Ticket is locked by someone else!');
                }

                //Make sure the email is not banned
                if(!$errors['err'] && TicketFilter::isBanned($ticket->getEmail())){
                    $errors['err']=__('Email is in banlist. Must be removed to reply.');
                }
                /*
                if($ora_ripresa > $adesso) {
                    $errors['err']="Attenzione! Data ripresa intervento maggiore della data attuale";
                }
                if($_POST['ora_ripresa']=="00" AND $_POST['minuti_ripresa']=="00") {
                    $errors['err']="Attenzione! Inserire una data corretta di ripreso intervento";
                }
                */
            }

            $vars = $_POST;
            $vars['cannedattachments'] = $response_form->getField('attachments')->getClean();

            //print_r($_POST);
            //die();

            if(!$errors && ($response=$ticket->postReply($vars, $errors, $_POST['emailreply']))) {

              if($ora_ripresa>1262300400) //anno 2000
              $datadichiarataripresa=date('d-m-Y H:i',$ora_ripresa);
              else
              $datadichiarataripresa=date('Y-m-d H:i');


            if($_POST['msg_ripreso'])
                $tecreason='[Ripreso in carico il '.$datadichiarataripresa.'. Nota: '.$_POST['msg_ripreso'].']';
            else
                $tecreason='[Ripreso in carico il '.$datadichiarataripresa.']';


            $tecreason=db_input($tecreason);
				    db_query("UPDATE ost_ticket__cdata SET zz_date1=NOW(),zz_dt_restart='$ora_ripresa', zz_tecreason=concat(zz_tecreason,' ',$tecreason), status_sym='Ripreso da Manutentore' WHERE ticket_id='$chiave'");
                $msg = sprintf(__('%s: Reply posted successfully '),
                        sprintf(__('Ticket #%s'),
                            sprintf('<a href="tickets.php?id=%d"><b>%s</b></a>',
                                $ticket->getId(), $ticket->getNumber()))
                        );




                #########################
        if (in_array($categoria, Array(17,28,29,30,31,32,33,34,35,37))){
				  $scadenza = nuovascadenza($datadichiarataripresa,1,$holidays);
				}elseif(in_array($categoria, Array(13,14,18,19,20,21,22,23,27))){
					$scadenza = nuovascadenza($datadichiarataripresa,4,$holidays);
				}elseif(in_array($categoria, Array(15,16,36,38,39))){//10 anni
					$scadenza = nuovascadenza($datadichiarataripresa,3650,$holidays);
				}elseif(in_array($categoria, Array(24,25))){
					$scadenza = nuovascadenza($datadichiarataripresa,6,$holidays);
				}elseif($categoria==12||$categoria==43){
					$scadenza = nuovascadenza($datadichiarataripresa,10,$holidays);
				}elseif($categoria==26){
				  $scadenza = date('Y-m-d H:i', $ora_ripresa+6*3600);
				}

				//db_query("UPDATE ost_ticket SET duedate='$scadenza',isoverdue=0 WHERE ticket_id='$chiave'");

                #########################

                $ticket->setStatus(22);

            //$msgg="UPDATE ost_ticket SET duedate='$scadenza',isoverdue=0,staff_id='".$thisstaff->getId()."',dept_id='".$thisstaff->getDeptId()."' WHERE ticket_id=".$chiave;
            $msgg="UPDATE ost_ticket SET duedate='$scadenza',isoverdue=0,staff_id=3,dept_id=6 WHERE ticket_id=".$chiave;
                db_query($msgg);


            $sql8888="INSERT INTO ost_ticket_thread (pid,ticket_id,staff_id,user_id,thread_type,poster,title, body, format, ip_address, created)
                VALUES
                (0,".$chiave.",".$thisstaff->getId().",0,'N','".$thisstaff->getName()."','Dettaglio ripresa',$tecreason, 'html','127.0.0.1',NOW())";

                 db_query($sql8888);
                // Clear attachment list
                $response_form->setSource(array());
                $response_form->getField('attachments')->reset();

                // Remove staff's locks
                TicketLock::removeStaffLocks($thisstaff->getId(),
                        $ticket->getId());

                // Cleanup response draft for this user
                Draft::deleteForNamespace(
                    'ticket.response.' . $ticket->getId(),
                    $thisstaff->getId());

                // Go back to the ticket listing page on reply
                $ticket = null;

            } elseif(!$errors['err']) {
                $errors['err']=__('Unable to post the reply. Correct the errors below and try again!');
            }
            break;






        case 'propostachiusura': /* Post Internal Note */

            $chiave = $ticket->getId();


            $presa = db_query('SELECT ticket.created as created, ticket.ticket_id as ticket_id, source, ticket.user_id as user_id, number, name, commesse.comm_id, firstname,lastname,cr,topic,from_unixtime(zz_dt_clmghw),ref_num
FROM ost_ticket as ticket
LEFT JOIN ost_ticket__cdata as cdata ON (cdata.ticket_id=ticket.ticket_id)
LEFT JOIN ost_user as user ON (user.id=ticket.user_id)
LEFT JOIN ost_commesse as commesse ON (commesse.user_id=ticket.user_id)
LEFT JOIN ost_staff as staff ON (staff.staff_id=ticket.staff_id)
LEFT JOIN ost_help_topic as topic ON (topic.topic_id=ticket.topic_id)
WHERE ticket.ticket_id='.$chiave);
            while ($row = db_fetch_array($presa )) {
				$creato=$row['created'];
				$sorgente=$row['source'];
				$poste=$cliente=$row['user_id'];
				$numero=$row['number'];
				$nome_cliente=$row['name'];
				$id_commessa=$row['comm_id'];
				$id_ticket=$row['ticket_id'];
        $firstname=$row['firstname'];
        $lastname=$row['lastname'];
        $tipologia=$row['topic'];
        $termid=$row['cr'];
        $ref_num=$row['ref_num'];
        $propo=$row['zz_dt_clmghw'];
            }




            if (!$_POST['start_partenza']){
            //$errors['err']="Attenzione! Scegliere la data di partenza del tecnico";
            $start_partenza = 0;
            }else{
			$start_partenza = strtotime($_POST['start_partenza'].' '.$_POST['ora_partenza'].':'.$_POST['minuti_partenza']);
			}


            $start_chiusura = strtotime($_POST['start_chiusura'].' '.$_POST['ora_iniziale'].':'.$_POST['minuti_iniziale']);
            $end_chiusura = strtotime($_POST['end_chiusura'].' '.$_POST['ora_finale'].':'.$_POST['minuti_finale']);

            //if ($end_chiusura > strtotime("now")) {$errors['err']="Attenzione! La data proposta chiusura è maggiore della data attuale";}
            //if ($end_chiusura < strtotime($creato)) {$errors['err']="Attenzione! La data proposta chiusura è antecedente alla data di acquisizione del ticket";}

            $esito_operazioni = $_POST['esito_intervento'];

            $guasto_riscontrato = prepara_sql($_POST['guasto']);
            $descrizione_intervento = prepara_sql($_POST['intervento']);
            $riassunto = prepara_sql($_POST['riassunto']);
            $note_man = prepara_sql($_POST['note_man']);
            $seriale = db_input($_POST['seriale']);



            $cod_intervento = $_POST['cod_intervento'];


            $desc_statocomponente = $_POST['desc_statocomponente'];


            $category_analisiguasto = $_POST['search_category_analisiguasto'];
            $area_descrizione_intervento = $_POST['area_descrizione_intervento'];



            $_POST['response']='Matricola: '.$seriale.' - '.$descrizione_intervento;

            if(!$thisstaff->canPostReply())
                $errors['err'] = __('Action denied. Contact admin for access');
            else {

                if(!$_POST['response']){
                    $errors['response']=__('Response required');

                 }
                //Use locks to avoid double replies
                if($lock && $lock->getStaffId()!=$thisstaff->getId()){
                    $errors['err']=__('Action Denied. Ticket is locked by someone else!');

                 }
                //Make sure the email is not banned
                if(!$errors['err'] && TicketFilter::isBanned($ticket->getEmail())){
                    $errors['err']=__('Email is in banlist. Must be removed to reply.');

                 }   /*
                if($_POST['ora_iniziale']=="00" AND $_POST['minuti_iniziale']=="00") {
                    $errors['err']="Attenzione! Inserire una data corretta di inizio intervento";

                }

                if($_POST['ora_finale']=="00" AND $_POST['minuti_finale']=="00") {
                    $errors['err']="Attenzione! Inserire una data corretta di fine intervento";

                }

                if(!$_POST['guasto']) {
                    $errors['err']="Attenzione! Specificare la natura del guasto riscontrato";

                }



                if(!$_POST['intervento']) {
                    $errors['err']="Attenzione! Descrivere l'intervento effettuato";

                }*/

                //if($pc_flag!=1 AND $active!=0) {
                   // $errors['err']="Si sta cercando di modificare lo stato e/o l'assegnazione del ticket senza averlo prima preso in carico.";
               // }


            }

            $vars = $_POST;
            $vars['cannedattachments'] = $response_form->getField('attachments')->getClean();
            $vars['poster']=$ticket->getAssignee();
            $vars['staffId']=$ticket->getStaffId();


            #############
            $seriale=preg_replace('/[^\da-z$,]/i', '', $seriale);

            if(isset($seriale) and !empty($seriale) and in_array($ticket->getTopicId(),$coopper)){
            $data = array('matricola'=>$seriale,'firstname'=>$firstname,'lastname'=>$lastname,'number'=>$ref_num,'tipologia'=>$tipologia,'termid'=>$termid,'propostachiusura'=>$end_chiusura);//firstname,lastname,number




            $urlo='http://5.249.147.181:8081/product/script_swap.php';
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $urlo);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $cresult  = curl_exec($ch);
            $error  = curl_error($ch);
            curl_close($ch);

            if ($error)
               // mail('openunit3@gmail.com','errore curl zoccali',$error);

            if (strpos($cresult, 'Errore') !== false){
              $mat=explode('_',$cresult);
              foreach($mat as $met){
                $matr .= ' '.str_replace("Errore", "", $met). ' ';
              }
              $errors['err'] = 'Attenzione: matricola/e '.$matr.' non presente';
            }



}else{
//mail('openunit3@gmail.com','nessuna matricola',$seriale);
}
            ###############





            if(!$errors && ($response=$ticket->postReply($vars, $errors, $_POST['emailreply']))) {

				//db_query("UPDATE ost_ticket__cdata SET zz_dt_clmghw='$end_chiusura', zz_data_inizio_intervento_man='$start_chiusura', zz_guasto_riscontrato='$guasto_riscontrato', zz_esito_op='$esito_operazioni', zz_desc_op_eff='$descrizione_intervento', zz_intervento_manutentore='$riassunto', zz_mgnote='$note_man', analisi_guasto='$analisi_guasto', status_sym='Chiuso da Manutentore' WHERE ticket_id='$chiave'");
				db_query("UPDATE ost_ticket__cdata SET zz_dt_clmghw='$end_chiusura', zz_data_inizio_intervento_man='$start_chiusura', zz_guasto_riscontrato='$guasto_riscontrato', zz_esito_op='$esito_operazioni', zz_desc_op_eff='$descrizione_intervento', zz_intervento_manutentore='$riassunto', zz_mgnote='$note_man',   status_sym='Chiuso da Manutentore',zz_ci_ptinterv='$ptinterv',zz_ci_ptswap='$ptswap',affected_resource_zz_wam_string2='$seriale' WHERE ticket_id='$chiave'");
				$msg = sprintf(__('%s: Reply posted successfully '),
                        sprintf(__('Ticket #%s'),
                            sprintf('<a href="tickets.php?id=%d"><b>%s</b></a>',
                                $ticket->getId(), $ticket->getNumber()))
                        );


                $query_due= 'SELECT created,duedate,zz_date1,rejected_date,tec_contatto_email,customer_middle_name,customer_location_l_addr7 FROM '.TICKET_TABLE.' NATURAL JOIN '.TICKET_TABLE.'__cdata WHERE `ticket_id`='.$chiave;
		        $result_due = db_query($query_due);
		        while ($row_due = db_fetch_array($result_due )) {
                $due= strtotime($row_due['duedate']);
                $trans= $row_due['zz_date1'];
                $rejected_date=(!empty(trim($row_due['rejected_date'])))?strtotime(trim($row_due['rejected_date'])):'';
                $crea= strtotime($row_due['created']);
                $emailequi= trim($row_due['tec_contatto_email']);
                $indirizzo= trim($row_due['customer_middle_name']);
                $citta= trim($row_due['customer_location_l_addr7']);
                }
                $inflag=($end_chiusura<$due)?1:0;
                $inter_time=$end_chiusura-$start_chiusura;

                if (empty($rejected_date)){
                $ticket_time=(isset($trans)&&!empty($trans))?$end_chiusura-$trans:$end_chiusura-$crea;
			    }else{
				$ticket_time=$end_chiusura-$rejected_date;
				}


                $query_tempi = 'SELECT ticket_id FROM `ost_ticket_tempi` WHERE `ticket_id`='.$chiave;
                if(!db_query($query_tempi) || !db_affected_rows()){
                db_query("INSERT INTO ost_ticket_tempi (ticket_id,in_sla,inter_time,ticket_time,last_update) VALUES ('$chiave','$inflag','$inter_time','$ticket_time',now())");
			    }else{
				db_query("UPDATE ost_ticket_tempi SET in_sla='$inflag', inter_time='$inter_time', ticket_time='$ticket_time', last_update=now() WHERE ticket_id='$chiave'");
				}


                $ticket->setStatus(2);






                // Clear attachment list
                $response_form->setSource(array());
                $response_form->getField('attachments')->reset();

                // Remove staff's locks
                TicketLock::removeStaffLocks($thisstaff->getId(),
                        $ticket->getId());

                // Cleanup response draft for this user
                Draft::deleteForNamespace(
                    'ticket.response.' . $ticket->getId(),
                    $thisstaff->getId());

                // Go back to the ticket listing page on reply
                $ticket = null;

            } elseif(!$errors['err']) {
                $errors['err']=__('Unable to post the reply. Correct the errors below and try again!');
            }

            unset($_POST['response']);
            break;
        //fine mio case
        //FINE ERICSSON
        case 'edit':
        case 'update':
            if(!$ticket || !$thisstaff->canEditTickets())
                $errors['err']=__('Permission Denied. You are not allowed to edit tickets');
            elseif($ticket->update($_POST,$errors)) {
                $msg=__('Ticket updated successfully');
                $_REQUEST['a'] = null; //Clear edit action - going back to view.
                //Check to make sure the staff STILL has access post-update (e.g dept change).
                if(!$ticket->checkStaffAccess($thisstaff))
                    $ticket=null;
            } elseif(!$errors['err']) {
                $errors['err']=__('Unable to update the ticket. Correct the errors below and try again!');
            }
            break;
        case 'process':
            switch(strtolower($_POST['do'])):
                case 'release':
                    if(!$ticket->isAssigned() || !($assigned=$ticket->getAssigned())) {
                        $errors['err'] = __('Ticket is not assigned!');
                    } elseif($ticket->release()) {
                        $msg=sprintf(__(
                            /* 1$ is the current assignee, 2$ is the agent removing the assignment */
                            'Ticket released (unassigned) from %1$s by %2$s'),
                            $assigned, $thisstaff->getName());
                        $ticket->logActivity(__('Ticket unassigned'),$msg);
                    } else {
                        $errors['err'] = __('Problems releasing the ticket. Try again');
                    }
                    break;
                case 'claim':

                  $adesso = time();
                  if (empty($_POST['rifiuto_ticket'])){ //se non è un rifiuto

                    $ora_prevista = strtotime($_POST['start_previsto'].' 23:59');
                    if(!$thisstaff->canAssignTickets()) {
                        $errors['err'] = __('Permission Denied. You are not allowed to assign/claim tickets.');
                    } elseif(!$ticket->isOpen()) {
                        $errors['err'] = __('Only open tickets can be assigned');
                    } elseif($ticket->isAssigned()) {
                        $errors['err'] = sprintf(__('Ticket is already assigned to %s'),$ticket->getAssigned());
                    } elseif ($ora_prevista>$adesso) {
						/* qui lavora la data di inzio intervento quando autoassegno il ticket*
						 * inoltre qui viene caricato il payload soap per la presa in carico  */

						if($lock && $lock->getStaffId()!=$thisstaff->getId()){ //vediamo se il ticket è bloccato da qualcuno
                        $errors['err']=__('Action Denied. Ticket is locked by someone else!');
                        exit;
                        }
						if ($ticket->claim()){
						$chiave = $ticket->getId();
						$planner = $thisstaff->getId();

						db_query("UPDATE ost_ticket__cdata SET zz_date6='$ora_prevista', pln_alpha='$planner', status_sym='In Carico a Manutentore' WHERE ticket_id='$chiave'");

                        $ticket->setStatus(7); //presa in carico (stato:planning)

                        TicketLock::removeStaffLocks($thisstaff->getId(),$ticket->getId());

                        $msg = __('Ticket is now assigned to you!');
						//il ticket è stato autoassegnato, è stata inserita una data prevista intervento ed
						$ticket = null;

						} else {
                        $errors['err'] = __('Problems assigning the ticket. Try again');
                        }


                    } else {
                        $errors['err']="Inserire una data corretta in cui si prevede di eseguire l'intervento";
                    }
				  }else{

                    if (!empty($_POST['msg_rifiuto'])){

						if($lock && $lock->getStaffId()!=$thisstaff->getId()){ //vediamo se il ticket è bloccato da qualcuno
                        $errors['err']=__('Action Denied. Ticket is locked by someone else!');
                        exit;
                        }
						if ($ticket->claim()){

							$cr_number = db_query('SELECT source,user_id,cr,isoverdue,created FROM '.TICKET_TABLE.'__cdata  NATURAL JOIN '.TICKET_TABLE.' WHERE `ticket_id`='.$ticket->getId());

		                    while ($row = db_fetch_array($cr_number )) {
                                   $sorgente= $row['source'];
                                   $resultasi ="cr:".$row['cr'];
                                   $poste=$row['user_id'];
                                   $creato=$row['created'];
                                   $scaduto=$row['isoverdue'];
                             }



						######
						$chiave = $ticket->getId();
						$planner = $thisstaff->getId();
						$note_rifiuto = $_POST['rifiuto_ticket'].' | '.$_POST['msg_rifiuto'];
						$ticket_time = time()-strtotime($creato);
						$inter_time=0;
						$inflag=$scaduto==0?1:0;

						db_query("UPDATE ost_ticket__cdata SET zz_mgnote='$note_rifiuto', pln_alpha='$planner', status_sym='Rifiutato da Manutentore' WHERE ticket_id='$chiave'");

                        $ticket->setStatus(8); //rifiuto (status:closed)

                        db_query("UPDATE ost_ticket SET closed=NOW() WHERE ticket_id='$chiave'");
                        db_query("INSERT INTO ost_ticket_tempi (ticket_id,in_sla,inter_time,ticket_time,last_update) VALUES ('$chiave','$inflag','$inter_time','$ticket_time',now())");

                        TicketLock::removeStaffLocks($thisstaff->getId(),$ticket->getId());

                        $msg = 'Il ticket è stato rifiutato';
						$ticket = null;




					    } else {
                        $errors['err'] = 'Non è stato possibile rifiutare il ticket. Riprovare più tardi';
                        }
                    }else{
					   $errors['err']="Occorre specificare il motivo del rifiuto";
					}
                   }
                    break;
                case 'overdue':
                    $dept = $ticket->getDept();
                    if(!$dept || !$dept->isManager($thisstaff)) {
                        $errors['err']=__('Permission Denied. You are not allowed to flag tickets overdue');
                    } elseif($ticket->markOverdue()) {
                        $msg=sprintf(__('Ticket flagged as overdue by %s'),$thisstaff->getName());
                        $ticket->logActivity(__('Ticket Marked Overdue'),$msg);
                    } else {
                        $errors['err']=__('Problems marking the the ticket overdue. Try again');
                    }
                    break;
                case 'answered':
                if ($thisstaff->getDeptId()!=17){
                    $dept = $ticket->getDept();
                    if(!$dept || !$dept->isManager($thisstaff)) {
                        $errors['err']=__('Permission Denied. You are not allowed to flag tickets');
                    } elseif($ticket->markAnswered()) {
                        $msg=sprintf(__('Ticket flagged as answered by %s'),$thisstaff->getName());
                        $ticket->logActivity(__('Ticket Marked Answered'),$msg);
                    } else {
                        $errors['err']=__('Problems marking the the ticket answered. Try again');
                    }
				}else{
                    if($ticket->markAnswered()) {

						if (!isset($_POST['rejected']) or empty($_POST['rejected'])){

						$errors['err']='Occorre specificare un motivo per il rifiuto della chiusura';
					    }else{
						$chiave = $ticket->getId();
						$rejected = prepara_sql($_POST['rejected']);
						//$rejected = db_input($_POST['rejected']);

						if (db_query("UPDATE ost_ticket ticket INNER JOIN ost_ticket__cdata cdata ON (ticket.ticket_id=cdata.ticket_id) SET ticket.staff_id=0, ticket.isanswered=0, cdata.status_sym='Riassegnato a Manutentore', cdata.rejected='$rejected', cdata.rejected_date=NOW() WHERE ticket.ticket_id='$chiave'")){

                        $ticket->setStatus(1); //trasferred

                        TicketLock::removeStaffLocks($thisstaff->getId(),$ticket->getId());
                        $msg=sprintf('<strong>Motivo: </strong> %s', $rejected);
                        $ticket->logActivity('Chiusura Ticket Rifiutata',$msg);

					   }else{$errors['err']=__('Problems marking the the ticket answered. Try again');}
				      }
                    } else {
                        $errors['err']=__('Problems marking the the ticket answered. Try again');
                    }
				}
                    break;
                case 'unanswered':
                    $dept = $ticket->getDept();
                    if(!$dept || !$dept->isManager($thisstaff)) {
                        $errors['err']=__('Permission Denied. You are not allowed to flag tickets');
                    } elseif($ticket->markUnAnswered()) {
                        $msg=sprintf(__('Ticket flagged as unanswered by %s'),$thisstaff->getName());
                        $ticket->logActivity(__('Ticket Marked Unanswered'),$msg);
                    } else {
                        $errors['err']=__('Problems marking the ticket unanswered. Try again');
                    }
                    break;
                case 'banemail':
                    if(!$thisstaff->canBanEmails()) {
                        $errors['err']=__('Permission Denied. You are not allowed to ban emails');
                    } elseif(BanList::includes($ticket->getEmail())) {
                        $errors['err']=__('Email already in banlist');
                    } elseif(Banlist::add($ticket->getEmail(),$thisstaff->getName())) {
                        $msg=sprintf(__('Email %s added to banlist'),$ticket->getEmail());
                    } else {
                        $errors['err']=__('Unable to add the email to banlist');
                    }
                    break;
                case 'unbanemail':
                    if(!$thisstaff->canBanEmails()) {
                        $errors['err'] = __('Permission Denied. You are not allowed to remove emails from banlist.');
                    } elseif(Banlist::remove($ticket->getEmail())) {
                        $msg = __('Email removed from banlist');
                    } elseif(!BanList::includes($ticket->getEmail())) {
                        $warn = __('Email is not in the banlist');
                    } else {
                        $errors['err']=__('Unable to remove the email from banlist. Try again.');
                    }
                    break;
                case 'changeuser':
                    if (!$thisstaff->canEditTickets()) {
                        $errors['err']=__('Permission Denied. You are not allowed to edit tickets');
                    } elseif (!$_POST['user_id'] || !($user=User::lookup($_POST['user_id']))) {
                        $errors['err'] = __('Unknown user selected');
                    } elseif ($ticket->changeOwner($user)) {
                        $msg = sprintf(__('Ticket ownership changed to %s'),
                            Format::htmlchars($user->getName()));
                    } else {
                        $errors['err'] = __('Unable to change ticket ownership. Try again');
                    }
                    break;
                default:
                    $errors['err']=__('You must select action to perform');
            endswitch;
            break;
        default:
            $errors['err']=__('Unknown action');
        endswitch;
        if($ticket && is_object($ticket))
            $ticket->reload();//Reload ticket info following post processing
    }elseif($_POST['a']) {

        switch($_POST['a']) {
            case 'open':
                $ticket=null;
                if(!$thisstaff || !$thisstaff->canCreateTickets()) {
                     $errors['err'] = sprintf('%s %s',
                             sprintf(__('You do not have permission %s.'),
                                 __('to create tickets')),
                             __('Contact admin for such access'));
                } else {
                    $vars = $_POST;

                    $vars['uid'] = $user? $user->getId() : 0;

                    $vars['cannedattachments'] = $response_form->getField('attachments')->getClean();

                    if ($_POST['name']=='Equitalia' && (empty($_POST['ref_contatto']) OR empty($_POST['customer_phone_number']) OR empty($_POST['tec_contatto']) OR empty($_POST['tec_contatto_email']) OR empty($_POST['tec_contatto_phone'])))
                    $errors['err']='Non è possibile creare il ticket. Controllare i campi obbligatori';


                    if(($ticket=Ticket::open($vars, $errors))) {
                        $msg =__('Ticket created successfully');//$vars['statusId'].$ticket->getId();

                        $_REQUEST['a']=null;
                        if (!$ticket->checkStaffAccess($thisstaff) || $ticket->isClosed())
                            $ticket=null;
                        Draft::deleteForNamespace('ticket.staff%', $thisstaff->getId());
                        // Drop files from the response attachments widget
                        $response_form->setSource(array());
                        $response_form->getField('attachments')->reset();
                        unset($_SESSION[':form-data']);
                    } elseif(!$errors['err']) {
                        $errors['err']=__('Unable to create the ticket. Correct the error(s) and try again');
                    }

                }
                break;
            case 'valori_economici': /** Transfer ticket **/
            //Check permission
            if(!$thisstaff->canEditTickets()){
                $errors['err']='Errore: non puoi modificare i valori economici';
            }elseif ($_POST['c']=='costi') {
				$costo_int = $_POST['costo_interno'];
				$costo_ext = $_POST['costo_esterno'];
				$prezzo = $_POST['prezzo'];
				$id_ticket = $_POST['ticket_id'];
				$query = "UPDATE ".TICKET_TABLE."__cdata SET costo_int='".$costo_int."',costo_ext='".$costo_ext."',prezzo='".$prezzo."' WHERE `ticket_id`=".$id_ticket;
                //echo $query;
                if (!db_query($query))
                $errors['err']='Errore: non è stato possibile modificare i valori economici';
            }elseif ($_POST['d']=='ass') {
				$tecnico=$_POST['assegnamento'];
				$id_ticket = $_POST['ticket_id'];
				$staff=Staff::lookup($tecnico);
			    $query = "UPDATE ".TICKET_TABLE." SET dept_id=7,status_id=23,staff_id='".$tecnico."' WHERE `ticket_id`=".$id_ticket;
                //echo $query;


                if (!db_query($query)){
                $errors['err']='Errore: non è stato possibile assegnare il ticket';
                }else{


                    if($_POST['invio_email']==1){
                            //$ema=substr($staff->getEmail(), 4);
                        $ticketton=Ticket::lookup($id_ticket);
                            //mail('domenico.zavattolo@service-tech.org','email zoccali',$tecnico);
                     $ticketton->assign($tecnico, "Ticket assegnato ed email inviata al tecnico");
	               }


###TARIFFE###
                    $zona=array('RM','RI','VT','LT','FR');
                //$zona2=array('AQ','PE','TE','CH');

				$topico=db_query("SELECT `topic_id`,`customer_location_l_addr1` FROM ".TICKET_TABLE." NATURAL JOIN ".TICKET_TABLE."__cdata WHERE `ticket_id`=".$id_ticket);
				   while ($row=db_fetch_array($topico)){
		                 $utopico=$row['topic_id'];
                     $utopica=trim($row['customer_location_l_addr1']);
	               }



				/*if (in_array($utopico, Array(15,16,17))){


               if(($utopica=='VT' || $utopica=='RI') && $utopico==17)
               $costo_ext =19.50;
               elseif(($utopica=='RM' || $utopica=='LT' || $utopica=='FR') && $utopico==17)
               $costo_ext =20;
               else
               $costo_ext =16;


				       $costo_int =($tecnico==80)?0:10;

				}elseif(in_array($utopico, Array(13,14))){

          if($utopica=='VT' || $utopica=='RI')
          $costo_ext =19.50;
          else
          $costo_ext =20;



				       $costo_int =($tecnico==80)?0:11;

				}elseif($utopico==12){

               $costo_ext =15;
				       $costo_int =($tecnico==80)?0:8;

         				}elseif(in_array($utopico, Array(18,19,20,21,22,23))){

                   if (in_array($utopica,$zona)){
                        $costo_ext =25;
         				       $costo_int =($tecnico==80)?0:11;
                   }else{
                        $costo_ext =25;

                        if($tecnico==80)//ditta
                        $costo_int=0;
                        elseif($tecnico==89)//levino
                        $costo_int=15;
                        else
                        $costo_int=12;

                             }

         				}elseif(in_array($utopico, Array(24,25))){

                   if (in_array($utopica,$zona)){
                        $costo_ext =10;
         				       $costo_int =($tecnico==80)?0:6;
                   }else{
                        $costo_ext =10;
         				       $costo_int =($tecnico==80)?0:6;
                             }

         				}elseif(in_array($utopico, Array(26,27,35))){

                   if (in_array($utopica,$zona)){
                        $costo_ext =25;
         				       $costo_int =($tecnico==80)?0:10;
                   }else{
                        $costo_ext =25;

                        if($tecnico==80)//ditta
                        $costo_int=0;
                        elseif($tecnico==89)//levino
                        $costo_int=15;
                        else
                        $costo_int=12;


                             }

         				}elseif($utopico==36 or $utopico==38){

                   if (in_array($utopica,$zona)){
                        $costo_ext =19;
         				       $costo_int =($tecnico==80)?0:8;
                   }else{
                        $costo_ext =19;
         				       $costo_int =($tecnico==80)?0:10;
                        //$costo_int =($tecnico==89)?10:9; //TEC. LEVINO
                             }


                 }elseif($utopico==39){

              					 $costo_ext =16;
              				   $costo_int =($tecnico==80)?0:10;

              		}elseif($utopico==40){

               					 $costo_ext =55;
               				   $costo_int =($tecnico==80)?0:32;

               		}elseif($utopico==41){

               					 $costo_ext =45;
               				   $costo_int =($tecnico==80)?0:23;

               		}elseif($utopico==42){

               					 $costo_ext =15;
               				   $costo_int =($tecnico==80)?0:14;

               		}elseif($utopico==43){

               					 $costo_ext =15;
               				   $costo_int =($tecnico==80)?0:8;

               		}else{

                        $costo_ext =0;
         				       $costo_int =0;
         				}*/

                    /*if($tecnico==80) {
                        $costo_int=0;
                        $topico=db_query("SELECT `costo_ext` FROM ".TICKET_TABLE."__cdata WHERE `ticket_id`=".$id_ticket);
                        $row=db_fetch_array($topico);
                        $costo_ext=$row['costo_ext'];
                        db_query("UPDATE ".TICKET_TABLE."__cdata SET costo_int='".$costo_int."',prezzo='".($costo_ext-$costo_int)."' WHERE `ticket_id`=".$id_ticket);
                    }*/


				$sql="INSERT INTO ost_ticket_thread (pid,ticket_id,staff_id,user_id,thread_type,poster,title, body, format, ip_address, created)
VALUES
(0,".$id_ticket.",".$thisstaff->getId().",0,'N','".$thisstaff->getName()."','Ticket assegnato a ".$staff->getLastName()."', 'Ticket assegnato da: ".$thisstaff->getName()."', 'html','127.0.0.1',NOW())";

                db_query($sql);
                $msg="Ticket assegnato correttamente a: ".$staff->getLastName();

			    }
			}
            break;
        }
    }
    if(!$errors)
        $thisstaff ->resetStats(); //We'll need to reflect any changes just made!
endif;

/*... Quick stats ...*/
$stats= $thisstaff->getTicketsStats();
############patch per il count di equitalia+solari##########

if ($thisstaff->getDeptId()==6){//partner tecnici
$stats['open']=db_count("SELECT count(DISTINCT ticket_id) FROM ost_ticket WHERE  status_id not in (2,3,8,21) and topic_id not in (15,16,36,38,39)");
$stats['overdue']=db_count("SELECT count(DISTINCT ticket_id) FROM ost_ticket WHERE isoverdue=1 and status_id not in (2,8,21) and topic_id not in (15,16,36,38,39)");
$stats['ordini_lunga_scadenza']=db_count("SELECT count(DISTINCT ticket_id) FROM ost_ticket WHERE  status_id not in (2,3,8,21) and topic_id in (15,16,36,38,39)");
}

if ($thisstaff->getDeptId()==9){//partner tecnici
$usertec=$thisstaff->getId();
$stats['closed']=db_count("SELECT count(DISTINCT ticket_id) FROM ost_ticket WHERE staff_id=$usertec AND (status_id=2 OR status_id=3 OR status_id=8)");
$stats['overdue']=db_count("SELECT count(DISTINCT ticket_id) FROM ost_ticket WHERE staff_id=$usertec and isoverdue=1 and status_id!=2");
}

if ($thisstaff->getDeptId()==7){//laboratorio
$usertec=$thisstaff->getId();
$stats['closed']=db_count("SELECT count(DISTINCT ticket_id) FROM ost_ticket WHERE staff_id=$usertec AND (status_id=2 OR status_id=3 OR status_id=8)");
$stats['overdue']=db_count("SELECT count(DISTINCT ticket_id) FROM ost_ticket WHERE staff_id=$usertec and isoverdue=1 and status_id!=2");
}

$stats['disallineati']=db_count("SELECT count(DISTINCT ticket_id) FROM ost_ticket__cdata NATURAL JOIN ost_ticket WHERE pc_flag=1 AND status_id!=2");
############fine patch equitalia#####################


//Navigation
$nav->setTabActive('tickets');
$open_name = _P('queue-name',
    /* This is the name of the open ticket queue */
    'Open');
if($cfg->showAnsweredTickets()) {
    $nav->addSubMenu(array('desc'=>$open_name.' ('.number_format($stats['open']).')',
                            'title'=>__('Open Tickets'),
                            'href'=>'tickets.php?status=open',
                            'iconclass'=>'Ticket'),
                        (!$_REQUEST['status'] || $_REQUEST['status']=='open'));
} else {

    if ($stats) {

        $nav->addSubMenu(array('desc'=>$open_name.' ('.number_format($stats['open']).')',
                               'title'=>__('Open Tickets'),
                               'href'=>'tickets.php?status=open',
                               'iconclass'=>'Ticket'),
                            (!$_REQUEST['status'] || $_REQUEST['status']=='open'));


    }

    if($stats['answered']) {
        $nav->addSubMenu(array('desc'=>__('Answered').' ('.number_format($stats['answered']).')',
                               'title'=>__('Answered Tickets'),
                               'href'=>'tickets.php?status=answered',
                               'iconclass'=>'answeredTickets'),
                            ($_REQUEST['status']=='answered'));
    }
}

if($thisstaff->getDeptId()==6) {
  $nav->addSubMenu(array('desc'=>'Lunga scadenza ('.number_format($stats['ordini_lunga_scadenza']).')',
                         'title'=>'Ordini a lunga scadenza',
                          'href'=>'?a=ordini_lunga_scadenza',
                          'iconclass'=>'newTicket'),
                        ($_REQUEST['status']=='ordini_lunga_scadenza'));
                      }

if($stats['assigned'] and $thisstaff->getDeptId()!=6) {

    $nav->addSubMenu(array('desc'=>__('My Tickets').' ('.number_format($stats['assigned']).')',
                           'title'=>__('Assigned Tickets'),
                           'href'=>'tickets.php?status=assigned',
                           'iconclass'=>'assignedTickets'),
                        ($_REQUEST['status']=='assigned'));
}

if($stats['overdue']) {
    $nav->addSubMenu(array('desc'=>__('Overdue').' ('.number_format($stats['overdue']).')',
                           'title'=>'Tickets Scaduti',
                           'href'=>'tickets.php?status=overdue',
                           'iconclass'=>'overdueTickets'),
                        ($_REQUEST['status']=='overdue'));

    if(!$sysnotice && $stats['overdue']>10)
        $sysnotice=sprintf(__('%d overdue tickets!'),$stats['overdue']);
}

if($stats['disallineati']) {
    $nav->addSubMenu(array('desc'=>'Disallineati ('.number_format($stats['disallineati']).')',
                           'title'=>'Tickets Disallineati',
                           'href'=>'tickets.php?status=disallineati',
                           'iconclass'=>'disallineatiTickets'),
                        ($_REQUEST['status']=='disallineati'));

}



if($thisstaff->showAssignedOnly() && $stats['closed']) {
    $nav->addSubMenu(array('desc'=>__('My Closed Tickets').' ('.number_format($stats['closed']).')',
                           'title'=>__('My Closed Tickets'),
                           'href'=>'tickets.php?status=closed',
                           'iconclass'=>'closedTickets'),
                        ($_REQUEST['status']=='closed'));
} else {

    $nav->addSubMenu(array('desc' => __('Closed').' ('.number_format($stats['closed']).')',
                           'title'=>__('Closed Tickets'),
                           'href'=>'tickets.php?status=closed',
                           'iconclass'=>'closedTickets'),
                        ($_REQUEST['status']=='closed'));
}

if($thisstaff->canCreateTickets()) {
    $nav->addSubMenu(array('desc'=>'Nuovo',
                           'title'=> __('Open a New Ticket'),
                           'href'=>'tickets.php?a=open',
                           'iconclass'=>'newTicket',
                           'id' => 'new-ticket'),
                        ($_REQUEST['a']=='open'));
}


if($thisstaff->getDeptId()==6) {

    $nav->addSubMenu(array('desc'=>'Nexi',
                           'title'=>'Importazione Nexi',
                           'href'=>'?a=ticket_cartasi',
                           'iconclass'=>'newTicket'),
                        ($_REQUEST['status']=='ticket-cartasi'));

    $nav->addSubMenu(array('desc'=>'Cooper',
                           'title'=>'Importazione Coopersystem',
                           'href'=>'?a=ticket_cooper',
                           'iconclass'=>'newTicket'),
                        ($_REQUEST['status']=='ticket_cooper'));

    $nav->addSubMenu(array('desc'=>'Sisal',
                           'title'=>'Importazione Sisal',
                           'href'=>'?a=ticket_sisal',
                           'iconclass'=>'newTicket'),
                        ($_REQUEST['status']=='ticket_sisal'));

    $nav->addSubMenu(array('desc'=>'CSC',
                            'title'=>'Importazione CSC',
                            'href'=>'?a=ticket_csc',
                            'iconclass'=>'newTicket'),
                            ($_REQUEST['status']=='ticket_csc'));


    $nav->addSubMenu(array('desc'=>'Stampa',
                           'title'=>'Stampa massiva',
                           'href'=>'?a=stampa_massiva',
                           'iconclass'=>'newTicket'),
                        ($_REQUEST['status']=='stampa_massiva'));



    $nav->addSubMenu(array('desc'=>'Azioni',
                           'title'=>'Azioni massive',
                           'href'=>'?a=azioni_massive',
                           'iconclass'=>'newTicket'),
                        ($_REQUEST['status']=='azioni_massive'));

}

$ost->addExtraHeader('<script type="text/javascript" src="js/ticket.js?a7d44f8"></script>');
$ost->addExtraHeader('<meta name="tip-namespace" content="tickets.queue" />',
    "$('#content').data('tipNamespace', 'tickets.queue');");
//QUALE INTERFACCIA

if($thisstaff->getDeptId()==5 ) { //screener, admin, planner
$inc = 'tickets.inc.php';

}elseif ($thisstaff->getDeptId()==4 || $thisstaff->getDeptId()==6) {
$inc = 'tickets_new.inc.php';

}elseif($thisstaff->getDeptId()==7 AND $thisstaff->getId()==5) { //resp.laboratorio
$inc = 'tickets_lab.inc.php';
}elseif($thisstaff->getDeptId()==7 AND $thisstaff->getId()!=5) { //tecnico laboratorio
//$inc = 'tickets_lab_tec.inc.php';
$inc = 'tickets_lab_tec_new.inc.php';
}elseif($thisstaff->getDeptId()==8) { //magazzino
$inc = 'tickets_mag.inc.php';
}elseif($thisstaff->getDeptId()==9) { //partner tecnico
$inc = 'tickets_par.inc.php';
}elseif($thisstaff->getDeptId()==10 and $thisstaff->getId()!=69) { //solari
$inc = 'tickets_solari.inc.php';
}elseif($thisstaff->getDeptId()==11) { //gruppo solari
$inc = 'tickets_grsolari.inc.php';
}elseif($thisstaff->getDeptId()==12) { //equitalia
$inc = 'tickets_equitalia.inc.php';
}elseif($thisstaff->getDeptId()==13) { //fick
$inc = 'tickets_fick.inc.php';
}elseif($thisstaff->getDeptId()==14) { //fidasc
$inc = 'tickets_fidasc.inc.php';
}elseif($thisstaff->getDeptId()==15) { //ismea
$inc = 'tickets_ismea.inc.php';
}elseif($thisstaff->getDeptId()==16) { //arpro
$inc = 'tickets_arpro.inc.php';
}elseif($thisstaff->getDeptId()==17) { //arpro
$inc = 'tickets_equitaliaGA.inc.php';
}elseif($thisstaff->getDeptId()==18) { //arpro
$inc = 'tickets_postemobile.inc.php';
}elseif($thisstaff->getDeptId()==19) { //arpro
$inc = 'tickets_ericsson.inc.php';
}elseif($thisstaff->getId()==69) { //arpro
$inc = 'tickets_pre.inc.php';
}


//FINE QUALE INTERFACCIA
if($ticket) {
    $ost->setPageTitle(sprintf(__('Ticket #%s'),$ticket->getNumber()));
    $nav->setActiveSubMenu(-1);
    if ($thisstaff->getDeptId()==4 || $thisstaff->getDeptId()==6 || $thisstaff->getDeptId()==7) {
    $inc = 'ticket-view_new.inc.php';
    }else{
    $inc = 'ticket-view.inc.php';
    }
    if($_REQUEST['a']=='edit' && $thisstaff->canEditTickets()) {
        $inc = 'ticket-edit.inc.php';
        if (!$forms) $forms=DynamicFormEntry::forTicket($ticket->getId());
        // Auto add new fields to the entries
        foreach ($forms as $f) $f->addMissingFields();
    } elseif($_REQUEST['a'] == 'print' && !$ticket->pdfExport($_REQUEST['psize'], $_REQUEST['notes']))
        $errors['err'] = __('Internal error: Unable to export the ticket to PDF for print.');

    if($_REQUEST['a']=='profilo' && $thisstaff->canEditTickets()) {
         if ($thisstaff->getDeptId()==4 || $thisstaff->getDeptId()==6) {
        $inc = 'profilo_new.inc.php';
        }else{
        $inc = 'profilo.inc.php';
	    }
        }




} else {
	//$inc = 'tickets.inc.php';


if($thisstaff->getDeptId()==5) { //screener, admin, planner
$inc = 'tickets.inc.php';

}elseif ($thisstaff->getDeptId()==4 || $thisstaff->getDeptId()==6) {
$inc = 'tickets_new.inc.php';

}elseif($thisstaff->getDeptId()==7 AND $thisstaff->getId()==5) { //resp.laboratorio
$inc = 'tickets_lab.inc.php';
}elseif($thisstaff->getDeptId()==7 AND $thisstaff->getId()!=5) { //tecnico laboratorio
//$inc = 'tickets_lab_tec.inc.php';
$inc = 'tickets_lab_tec_new.inc.php';
}elseif($thisstaff->getDeptId()==8) { //magazzino
$inc = 'tickets_mag.inc.php';
}elseif($thisstaff->getDeptId()==9) { //partner tecnico
$inc = 'tickets_par.inc.php';
}elseif($thisstaff->getDeptId()==10 and $thisstaff->getId()!=69) { //solari
$inc = 'tickets_solari.inc.php';
}elseif($thisstaff->getDeptId()==11) { //gruppo solari
$inc = 'tickets_grsolari.inc.php';
}elseif($thisstaff->getDeptId()==12) { //gruppo solari
$inc = 'tickets_equitalia.inc.php';
}elseif($thisstaff->getDeptId()==13) { //fick
$inc = 'tickets_fick.inc.php';
}elseif($thisstaff->getDeptId()==14) { //fick
$inc = 'tickets_fidasc.inc.php';
}elseif($thisstaff->getDeptId()==15) { //fidasc
$inc = 'tickets_ismea.inc.php';
}elseif($thisstaff->getDeptId()==16) { //arpro
$inc = 'tickets_arpro.inc.php';
}elseif($thisstaff->getDeptId()==17) { //arpro
$inc = 'tickets_equitaliaGA.inc.php';
}elseif($thisstaff->getDeptId()==18) { //arpro
$inc = 'tickets_postemobile.inc.php';
}elseif($thisstaff->getDeptId()==19) { //arpro
$inc = 'tickets_ericsson.inc.php';
}elseif($thisstaff->getId()==69) { //arpro
$inc = 'tickets_pre.inc.php';
}

   if($_REQUEST['a']=='open' && $thisstaff->canCreateTickets())
        if ($thisstaff->getDeptId()==12){
        $inc = 'ticket-open_equi.inc.php';
        }elseif ($thisstaff->getDeptId()==13){
        $inc = 'ticket-open_fick.inc.php';
	    }elseif ($thisstaff->getDeptId()==14){
        $inc = 'ticket-open_fidasc.inc.php';
	    }elseif ($thisstaff->getDeptId()==15){
        $inc = 'ticket-open_ismea.inc.php';
        }elseif ($thisstaff->getDeptId()==16){
        $inc = 'ticket-open_arpro.inc.php';
        }elseif ($thisstaff->getDeptId()==17){
        $inc = 'ticket-open_equiGA.inc.php';
	    }else{
		$inc = 'ticket-open_new.inc.php';
	}
    elseif($_REQUEST['a'] == 'export') {
        $ts = strftime('%Y%m%d');
        $who=$_REQUEST['who']?$_REQUEST['who']:$thisstaff->getId();
        $nome=$_REQUEST['who']==15?"kpi-$ts.csv":"tickets-$ts.csv";


        //mail("marco.salmi89@gmail.com","QUERY",$_SESSION['search_'.$_REQUEST['h']]);


        if (!($token=$_REQUEST['h'])){
            $errors['err'] = __('Query token required');
        }elseif (!($query=$_SESSION['search_'.$token])){
            $errors['err'] = __('Query token not found');
        }elseif (!Export::saveTickets($query, $nome, 'csv',$who)){
            $errors['err'] = __('Internal error: Unable to dump query results');
          }

    }
    elseif($_REQUEST['a']=='ticket_cartasi' && $thisstaff->canEditTickets()) {

                     $inc = 'cartasi.inc.php';

    }
    elseif($_REQUEST['a']=='ticket_cooper' && $thisstaff->canEditTickets()) {

                     $inc = 'cooper.inc.php';

    }
    elseif($_REQUEST['a']=='ticket_sisal' && $thisstaff->canEditTickets()) {

                     $inc = 'sisal.inc.php';

    }
   elseif($_REQUEST['a']=='ticket_csc' && $thisstaff->canEditTickets()) {

       $inc = 'csc.inc.php';

   }
    elseif($_REQUEST['a']=='stampa_massiva' && $thisstaff->canEditTickets()) {
        $inc = 'stampa.inc.php';
    }
    elseif($_REQUEST['a']=='commesse' && $thisstaff->canEditTickets()) {
		$inc = 'commesse.inc.php';
	}elseif($_REQUEST['a']=='ordini_lunga_scadenza') {
		$inc = 'tickets_new_lungamarcia.inc.php';
	}elseif($_REQUEST['a']=='azioni_massive') {
		$inc = 'azioni.inc.php';
	}elseif($_REQUEST['a']=='stampamassiva') {

      $startTime= $_REQUEST['startDate'];

      $endTime= $_REQUEST['endDate'];

      $escludi_topic='(17,28,29,30,31,32,33,34,35,37,39)';

      switch($_REQUEST['societa']) {
            case 'all':
                $where=1;
                break;
            case 'cartasi':
                $where="ref_num NOT LIKE '%C%' AND ref_num NOT LIKE '%CA%' AND ref_num NOT LIKE '%GE%'";
                break;
            case 'coopersystem':
                $where="ref_num LIKE '%C%' AND ref_num NOT LIKE '%CA%'";
                break;
            case 'sisal':
                $where="ref_num LIKE '%GE%' OR ref_num LIKE '%CA%' ";
                break;
          case 'csc':
              $where="ref_num LIKE '[A-Z][A-Z][0-9][0-9][0-9][0-9][A-Z][A-Z]-[0-9][0-9]'";
              break;
            case 'altro':
                $where="ref_num LIKE '%C%' ";
                break;
		}

	if (!empty(trim($_REQUEST['ordinesingolo']))){
	$sql="SELECT ticket_id FROM ost_ticket NATURAL JOIN ost_ticket__cdata WHERE ref_num='".trim($_REQUEST['ordinesingolo'])."'";
	}else{
   if($startTime==$endTime){

    $sql="SELECT ticket_id FROM ost_ticket NATURAL JOIN ost_ticket__cdata WHERE created>='".$startTime." 00:00' AND created<='".$endTime." 23:59' AND ".$where." AND topic_id NOT IN ".$escludi_topic." AND status_id NOT IN (2,8)";
   }else{
    $sql="SELECT ticket_id FROM ost_ticket NATURAL JOIN ost_ticket__cdata WHERE created>='".$startTime."' AND created<='".$endTime."' AND ".$where." AND topic_id NOT IN ".$escludi_topic." AND status_id NOT IN (2,8)";
   }
  }

      echo $sql;
      //die;

      $result=db_query($sql);

      while ($row=db_fetch_array($result)){
		  $id[]=$row['ticket_id'];
	  }

       $array_unico = array();

       foreach ($id as $valore) {

         if(!$valore)
         continue;

       $ticketto=Ticket::lookup($valore);
	   //$ticketto->pdfExport();

	   $thread=$ticketto->getThreadEntries('M');
       foreach($thread as $entry) {
       $description=$entry['body']->toHtml();
       }

	   $array_unico[]=array('numero'=>$ticketto->getNumber(),
	   'open'=>$ticketto->getCreateDate(),
	   'scadenza'=>$ticketto->getDueDate(),
	   'termid'=>$ticketto->termid(),
	   'tipo_ordine'=>$ticketto->getHelpTopic(),
	   'abi'=>$ticketto->frazionario(),
	   'banca'=>$ticketto->banca(),
	   'insegna'=>$ticketto->denominazione_ufficio(),
	   'ordine'=>$ticketto->problem(),
	   'indirizzo'=>$ticketto->via_ufficio(),
	   'luogo'=>$ticketto->localita_ufficio(),
	   'provincia'=>$ticketto->provincia_ufficio(),
	   'telefono'=>$ticketto->telefono_ufficio(),
     'cellulare'=>$ticketto->cellulare(),
	   'soggetto'=>$ticketto->getSubject(),
     'ora_ordine'=>$ticketto->ora_ordine(),
	   'messaggio'=>$description,
	   'hw'=>$ticketto->hw(),
     'licenza'=>$ticketto->string9(),
     'modem'=>$ticketto->string8(),
     'rch'=>$ticketto->string2(),
     'cashless'=>$ticketto->string15(),
	   'collegamento'=>$ticketto->collegamento(),
	   'urgenza'=>$ticketto->urgenza(),
	   'cap'=>$ticketto->cap(),
	   'topicId'=>$ticketto->getTopicId(),
	   'orario_apertura'=>$ticketto->orario_apertura(),
	   'giornochiusura_1'=>$ticketto->giornochiusura_1(),
	   'giornochiusura_2'=>$ticketto->giornochiusura_2(),
	   'giornochiusura_3'=>$ticketto->giornochiusura_3()
	   );
        }

        //$array_unico=array_reverse($array_unico);

        /*
        echo '<pre>';
        print_r($array_unico);
        echo '</pre>';
        */

        if($ticketto){
          if($ticketto->getStatusId()==2 or $ticketto->getStatusId()==8){
            //mail("marco.salmi89@gmail.com","PROVA",$ticketto->getStatusId());
            $focus=$ticketto->problem();
            $blocco=true;
            $inc = 'stampa.inc.php';
          }else{

            $inc='/tcpdf/examples/ticket_pdf.php';

            header("Refresh:0");
          }
        }else{
          $focus=trim($_REQUEST['ordinesingolo']);
          $inesistente=true;
          $inc = 'stampa.inc.php';
        }



      }elseif($_REQUEST['a']=='azionimassive') {


    if(isset($_REQUEST['status_id'])){

        if($_REQUEST['status_id']==2){
           $status_id=$_REQUEST['status_id'];
           $zz_dt_clmghw=strtotime($_REQUEST['zz_dt_clmghw']);

           $tecnico=$_REQUEST['tec_chiusura'];



           if($zz_dt_clmghw>1262300400 &&!empty($tecnico)){
             $staff=Staff::lookup($tecnico);




             if(isset($_REQUEST['input_field'])){

                     foreach($_REQUEST['input_field'] as $chiave=>$valore){

                       $number=preg_replace("/[^0-9abcisgeABCISGE+]/", "",$valore[0]);

                       if(strpos($number, '+') !== false){
                         $ref_num=preg_replace("/[^0-9abcisgeABCISGE]/", "",$number);

                         if(strlen($ref_num)>4)
                         $id_ticket=Ticket::getIdByRef_num($ref_num);

                       }else{

                         if(strlen($number)>4)
                         $id_ticket=Ticket::getIdByNumber($number);

                         $sql99 ='SELECT ref_num FROM ost_ticket__cdata WHERE ticket_id='.$id_ticket;



                        if(($res99=db_query($sql99)) && db_num_rows($res99))
                            list($id99)=db_fetch_row($res99);

                         $ref_num=$id99;

                       }

                       if(!(ctype_digit($id_ticket)&&$id_ticket>4000))
                       continue;


                       $query = "UPDATE ".TICKET_TABLE." SET dept_id=7,status_id=23,staff_id='".$tecnico."',updated=NOW() WHERE `ticket_id`=".$id_ticket;
                       //echo $query.'<br>';
                       //mail("marco.salmi89@gmail.com","PROVA",$query);
                       if (!db_query($query)){
                       $errors['err']='Errore: non è stato possibile assegnare il ticket';
                       $inc = 'azioni.inc.php';
                       }else{




                       if($_POST['invio_email']==1){
                       $ticketton=Ticket::lookup($id_ticket);
                       $ticketton->assign($tecnico, "Ticket assegnato ed email inviata al tecnico");
       	               }

###TARIFFE###
                       $zona=array('RM','RI','VT','LT','FR');
                       //$zona2=array('AQ','PE','TE','CH');

       				         $topico=db_query("SELECT `topic_id`,`customer_location_l_addr1` FROM ".TICKET_TABLE." NATURAL JOIN ".TICKET_TABLE."__cdata WHERE `ticket_id`=".$id_ticket);
       				         while ($row=db_fetch_array($topico)){
       		                 $utopico=$row['topic_id'];
                           $utopica=trim($row['customer_location_l_addr1']);
      	               }



      				/*if (in_array($utopico, Array(15,16,17))){


                     if(($utopica=='VT' || $utopica=='RI') && $utopico==17)
                     $costo_ext =19.50;
                     elseif(($utopica=='RM' || $utopica=='LT' || $utopica=='FR') && $utopico==17)
                     $costo_ext =20;
                     else
                     $costo_ext =16;


      				       $costo_int =($tecnico==80)?0:10;

      				}elseif(in_array($utopico, Array(13,14))){

                if($utopica=='VT' || $utopica=='RI')
                $costo_ext =19.50;
                else
                $costo_ext =20;



      				       $costo_int =($tecnico==80)?0:11;

      				}elseif($utopico==12){

                     $costo_ext =15;
      				       $costo_int =($tecnico==80)?0:8;

                			}elseif(in_array($utopico, Array(18,19,20,21,22,23))){

                      if (in_array($utopica,$zona)){
                               $costo_ext =25;
                				       $costo_int =($tecnico==80)?0:11;
                      }else{
                               $costo_ext =25;

                               if($tecnico==80)//ditta
                               $costo_int=0;
                               elseif($tecnico==89)//levino
                               $costo_int=15;
                               else
                               $costo_int=12;

                                    }

                			}elseif(in_array($utopico, Array(24,25))){

                          if (in_array($utopica,$zona)){
                               $costo_ext =10;
                				       $costo_int =($tecnico==80)?0:6;
                          }else{
                               $costo_ext =10;
                				       $costo_int =($tecnico==80)?0:6;
                                    }

                			}elseif(in_array($utopico, Array(26,27,35))){

                          if (in_array($utopica,$zona)){
                               $costo_ext =25;
                				       $costo_int =($tecnico==80)?0:10;
                          }else{
                               $costo_ext =25;

                               if($tecnico==80)//ditta
                               $costo_int=0;
                               elseif($tecnico==89)//levino
                               $costo_int=15;
                               else
                               $costo_int=12;


                                    }

                				}elseif($utopico==36 or $utopico==38){

                          if (in_array($utopica,$zona)){
                               $costo_ext =19;
                				       $costo_int =($tecnico==80)?0:8;
                          }else{
                               $costo_ext =19;
                				       $costo_int =($tecnico==80)?0:10;
                               //$costo_int =($tecnico==89)?10:9; //TEC. LEVINO
                                    }


                        }elseif($utopico==39){

                     					 $costo_ext =16;
                     				   $costo_int =($tecnico==80)?0:10;

                     		}elseif($utopico==40){

                     					 $costo_ext =55;
                     				   $costo_int =($tecnico==80)?0:32;

                     		}elseif($utopico==41){

                     					 $costo_ext =45;
                     				   $costo_int =($tecnico==80)?0:23;

                     		}elseif($utopico==42){

                     					 $costo_ext =15;
                     				   $costo_int =($tecnico==80)?0:14;

                     		}elseif($utopico==43){

                     					 $costo_ext =15;
                     				   $costo_int =($tecnico==80)?0:8;

                     		}else{

                               $costo_ext =0;
                				       $costo_int =0;
                				}*/

                           /*if($tecnico==80) {
                               $costo_int=0;
                               $topico=db_query("SELECT `costo_ext` FROM ".TICKET_TABLE."__cdata WHERE `ticket_id`=".$id_ticket);
                               $row=db_fetch_array($topico);
                               $costo_ext=$row['costo_ext'];
                               db_query("UPDATE ".TICKET_TABLE."__cdata SET costo_int='".$costo_int."',prezzo='".($costo_ext-$costo_int)."' WHERE `ticket_id`=".$id_ticket);

                           }*/

                                //echo "UPDATE ".TICKET_TABLE."__cdata SET costo_ext='".$costo_ext."',costo_int='".$costo_int."',prezzo='".($costo_ext-$costo_int)."' WHERE `ticket_id`=".$id_ticket."<br>";


       				          $sql="INSERT INTO ost_ticket_thread (pid,ticket_id,staff_id,user_id,thread_type,poster,title, body, format, ip_address, created)
                        VALUES
                        (0,".$id_ticket.",".$thisstaff->getId().",0,'N','".$thisstaff->getName()."','Ticket assegnato a ".$staff->getLastName()."', 'Ticket assegnato da: ".$thisstaff->getName()."', 'html','127.0.0.1',NOW())";

                        db_query($sql);
                        //$msg="Ticket assegnati correttamente a: ".$staff->getLastName();

       			           }

       			          //mail('marco.salmi89@gmail.com, openunit3@gmail.com','ref_num',$ref_num);

if (strpos($ref_num, 'c') !== false or strpos($ref_num, 'C') !== false or strpos($ref_num, 'S') !== false){

                       $presa = db_query('SELECT ticket.created as created, ticket.ticket_id as ticket_id, source, ticket.user_id as user_id, number, name, commesse.comm_id, firstname,lastname,cr,topic,from_unixtime(zz_dt_clmghw),ref_num
           FROM ost_ticket as ticket
           LEFT JOIN ost_ticket__cdata as cdata ON (cdata.ticket_id=ticket.ticket_id)
           LEFT JOIN ost_user as user ON (user.id=ticket.user_id)
           LEFT JOIN ost_commesse as commesse ON (commesse.user_id=ticket.user_id)
           LEFT JOIN ost_staff as staff ON (staff.staff_id=ticket.staff_id)
           LEFT JOIN ost_help_topic as topic ON (topic.topic_id=ticket.topic_id)
           WHERE ticket.ticket_id='.$id_ticket);
                       while ($row = db_fetch_array($presa )) {
           				$creato=$row['created'];
           				$sorgente=$row['source'];
           				$poste=$cliente=$row['user_id'];
           				$numero=$row['number'];
           				$nome_cliente=$row['name'];
           				$id_commessa=$row['comm_id'];
           				$id_ticket=$row['ticket_id'];
                   $firstname=$row['firstname'];
                   $lastname=$row['lastname'];
                   $tipologia=$row['topic'];
                   $termid=$row['cr'];
                   $ref_num=$row['ref_num'];
                   $propo=$row['zz_dt_clmghw'];
                       }

                       #############
                       $seriale=preg_replace('/[^\da-z$,]/i', '', db_input($valore[1]));

                        //mail('marco.salmi89@gmail.com, openunit3@gmail.com','zoccali',$seriale);

                       if(isset($seriale) and !empty($seriale)){
                       $data = array('matricola'=>$seriale,'firstname'=>$firstname,'lastname'=>$lastname,'number'=>$ref_num,'tipologia'=>$tipologia,'termid'=>$termid,'propostachiusura'=>$zz_dt_clmghw);//firstname,lastname,number


                       //mail('marco.salmi89@gmail.com','array chiusura da zoccali',serialize($data));

                       $urlo='http://5.249.147.181:8081/product/script_swap.php';
                       $ch = curl_init();

                       curl_setopt($ch, CURLOPT_URL, $urlo);
                       curl_setopt($ch, CURLOPT_POST, 1);
                       curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
                       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                       $cresult  = curl_exec($ch);
                       $error  = curl_error($ch);
                       curl_close($ch);

                       if($error)
                           mail('kakinho@hotmail.it','errore curl_massivo zoccali',$error);

                       if (strpos($cresult, 'Errore') !== false){
                         $mat=explode('_',$cresult);
                         foreach($mat as $met){
                           $matr .= ' '.str_replace("Errore", "", $met). ' ';
                         }
                         $errors['err'] = $matr;
                         unset($matr);
                       }
                     }

}

                    $querry= "UPDATE ost_ticket SET status_id=2, closed=NOW(),updated=NOW() WHERE ticket_id=".db_input($id_ticket);
                    $query='UPDATE ost_ticket__cdata SET zz_dt_clmghw='.$zz_dt_clmghw.',zz_desc_op_eff='.db_input($valore[2]).',status_sym=\'Chiuso da Manutentore\',affected_resource_zz_wam_string2='.db_input($valore[1]).' WHERE ticket_id='.db_input($id_ticket);

                   if(!$errors){

                     if(db_query($querry)&&db_query($query))
                     $avviso.=str_replace('+','',$number)." - ";

                     $sql232="INSERT INTO ost_ticket_thread (pid,ticket_id,staff_id,user_id,thread_type,poster,title, body, format, ip_address, created)
                  VALUES
                  (0,".$id_ticket.",".$thisstaff->getId().",0,'N','".$thisstaff->getName()."','Dettagli', 'Matricola/e: ".addslashes($valore[1])." - ".addslashes($valore[2])."', 'html','127.0.0.1',NOW())";

                   db_query($sql232);

                   }else{
                     $nomatricole.=$errors['err'];
                     $avviso2.=str_replace('+','',$number)." - ";
                     $errorimatricolari=true;
                     unset($errors);
                   }


                    unset($id_ticket);
                    unset($number);
                    unset($ref_num);
                  }

                if($errorimatricolari)
                $msg="I seguenti ticket non sono stati chiusi: ".$avviso2.". Le seguenti matricole non sono state trovate: ".$nomatricole;
                else
                $msg="I seguenti ticket sono stati chiusi: ".$avviso;

                unset($errorimatricolari);

             }
           }else{
             $errors['err']='Attenzione: la data proposta chiusura non sembra corretta oppure tecnico mancante';
             $inc = 'azioni.inc.php';
           }

###RECESSO####
        }elseif($_REQUEST['status_id']==8){
          if(isset($_REQUEST['input_field1'])){


            $ora_recesso=strtotime($_REQUEST['zz_dt_recesso']);

            if($ora_recesso<1262300400)
            $ora_recesso=time();

            if($ora_recesso>1262300400){
              foreach($_REQUEST['input_field1'] as $chiave=>$valore){

                $number=preg_replace("/[^0-9abcisgeABCISGE+]/", "",$valore[0]);

                if(strpos($number, '+') !== false){
                  $ref_num=preg_replace("/[^0-9abcisgeABCISGE]/", "",$number);

                  if(strlen($ref_num)>4)
                  $id_ticket=Ticket::getIdByRef_num($ref_num);

                }else{

                  if(strlen($number)>4)
                  $id_ticket=Ticket::getIdByNumber($number);

                }

                if(!(ctype_digit($id_ticket)&&$id_ticket>4000))
                continue;

                $querry= "UPDATE ost_ticket SET status_id=8, dept_id=6, staff_id=0, isoverdue=0,updated=NOW(),closed=".db_input($_REQUEST['zz_dt_recesso'])." WHERE ticket_id=".db_input($id_ticket);
                if(db_query($querry))
                $avviso.=str_replace('+','',$number)." - ";

                unset($querry);
                unset($id_ticket);
                unset($number);
                unset($ref_num);
              }
           $msg="I seguenti ticket sono stati rifiutati: ".$avviso;

            }else{
              $errors['err']='Attenzione: la data di recesso non sembra corretta';
              $inc = 'azioni.inc.php';
            }

          }
##########FINE RECESSO
##########BLOCCO
        }elseif($_REQUEST['status_id']==21){

          $status_id=$_REQUEST['status_id'];

$ora_attesa=strtotime($_REQUEST['zz_dt_attesa']);

if($ora_attesa>1262300400){

}else{
  $ora_attesa=time();
}


            if(isset($_REQUEST['input_field2'])){
                foreach($_REQUEST['input_field2'] as $chiave=>$valore){


                  $number=preg_replace("/[^0-9abcisgeABCISGE+]/", "",$valore[0]);

                  if(strpos($number, '+') !== false){
                    $ref_num=preg_replace("/[^0-9abcisgeABCISGE]/", "",$number);

                    if(strlen($ref_num)>4)
                    $id_ticket=Ticket::getIdByRef_num($ref_num);

                  }else{

                    if(strlen($number)>4)
                    $id_ticket=Ticket::getIdByNumber($number);

                  }

                  if(!(ctype_digit($id_ticket)&&$id_ticket>4000))
                  continue;

                  $querry= "UPDATE ost_ticket SET status_id=21, dept_id=6, staff_id=3, isoverdue=0, updated=NOW() WHERE ticket_id=".db_input($id_ticket);
                  $querry1= "UPDATE ost_ticket__cdata SET  zz_tecreason=".db_input($valore[1]).", status_sym='Sospeso da Manutentore' WHERE ticket_id=".db_input($id_ticket);

                  $sql="INSERT INTO ost_ticket_thread (pid,ticket_id,staff_id,user_id,thread_type,poster,title, body, format, ip_address, created)
                  VALUES
                  (0,".$id_ticket.",".$thisstaff->getId().",0,'N','".$thisstaff->getName()."','Ticket bloccato in data ".date('d-m-Y H:i',$ora_attesa)."', 'Ticket bloccato da: ".$thisstaff->getName()."<br> Motivo: ".addslashes($valore[1])."', 'html','127.0.0.1',NOW())";

                   //db_query($sql);

                   if(db_query($querry)&&db_query($querry1)&&db_query($sql))
                   $avviso.=str_replace('+','',$number)." - ";

                   unset($id_ticket);
                   unset($number);
                   unset($ref_num);
                 }
              $msg="I seguenti ticket sono stati bloccati: ".$avviso;

            }

##########FINE BLOCCO
##########ATTESA
         }elseif($_REQUEST['status_id']==22){




$ora_ripresa=strtotime($_REQUEST['zz_dt_restart']);

if($ora_ripresa<1262300400)
$ora_ripresa=time();

if($ora_ripresa>1262300400){
  foreach($_REQUEST['input_field3'] as $id_ticket=>$valore){

    $number=preg_replace("/[^0-9abcisgeABCISGE+]/", "",$valore[0]);

    if(strpos($number, '+') !== false){
      $ref_num=preg_replace("/[^0-9abcisgeABCISGE]/", "",$number);

      if(strlen($ref_num)>4)
      $id_ticket=Ticket::getIdByRef_num($ref_num);

    }else{

      if(strlen($number)>4)
      $id_ticket=Ticket::getIdByNumber($number);

    }

    if(!(ctype_digit($id_ticket)&&$id_ticket>4000))
    continue;


                $presa = db_query('SELECT topic_id FROM '.TICKET_TABLE.' WHERE `ticket_id`='.$id_ticket);
                while ($row = db_fetch_array($presa )) {
				$categoria=$row['topic_id'];
                }


                if($ora_ripresa>1262300400) //anno 2000
                $datadichiarataripresa=date('d-m-Y H:i',$ora_ripresa);
                else
                $datadichiarataripresa=date('Y-m-d H:i');


                if($valore[1])
                $tecreason='[Ripreso in carico il '.$datadichiarataripresa.'. Nota: '.$valore[1].']';
                else
                $tecreason='[Ripreso in carico il '.$datadichiarataripresa.']';


                $tecreason=db_input($tecreason);
	        //db_query("UPDATE ost_ticket__cdata SET zz_dt_restart='$ora_ripresa', zz_tecreason=concat(zz_tecreason,' ',$tecreason), status_sym='Ripreso da Manutentore' WHERE ticket_id=$id_ticket");


$querry= "UPDATE ost_ticket__cdata SET zz_dt_restart='$ora_ripresa', zz_tecreason=concat(zz_tecreason,' ',$tecreason), status_sym='Ripreso da Manutentore' WHERE ticket_id=".db_input($id_ticket);


                #########################
                                if (in_array($categoria, Array(17,28,29,30,31,32,33,34,35,37))){
				  $scadenza = nuovascadenza($datadichiarataripresa,1,$holidays);
				}elseif(in_array($categoria, Array(13,14,18,19,20,21,22,23,27))){
					$scadenza = nuovascadenza($datadichiarataripresa,4,$holidays);
				}elseif(in_array($categoria, Array(15,16,36,38,39))){//10 anni
					$scadenza = nuovascadenza($datadichiarataripresa,3650,$holidays);
				}elseif(in_array($categoria, Array(24,25))){
					$scadenza = nuovascadenza($datadichiarataripresa,6,$holidays);
				}elseif($categoria==12){
					$scadenza = nuovascadenza($datadichiarataripresa,10,$holidays);
				}elseif($categoria==26){
				  $scadenza = date('Y-m-d H:i', $ora_ripresa+6*3600);
				}



                #########################


                $querry1="UPDATE ost_ticket SET duedate=".db_input($scadenza).",updated=NOW(),isoverdue=0,status_id=22, staff_id=3,dept_id=".$thisstaff->getDeptId()." WHERE ticket_id=".db_input($id_ticket);
                //db_query($msgg);
                $sql="INSERT INTO ost_ticket_thread (pid,ticket_id,staff_id,user_id,thread_type,poster,title, body, format, ip_address, created)
                VALUES
                (0,".$id_ticket.",".$thisstaff->getId().",0,'N','".$thisstaff->getName()."','Ticket ripreso in carico con data ".$datadichiarataripresa."', 'Ticket ripreso in carico da: ".$thisstaff->getName()."<br> Motivo: ".addslashes($valore[1])."', 'html','127.0.0.1',NOW())";

                 //db_query($sql);

                 if(db_query($querry)&&db_query($querry1)&&db_query($sql))
                 $avviso.=str_replace('+','',$number)." - ";

unset($id_ticket);
unset($number);
unset($ref_num);
}
$msg="I seguenti ticket sono stati ripresi in carico: ".$avviso;

}else{
  $errors['err']='Attenzione: la data di ripresa in carico non sembra corretta';
  $inc = 'azioni.inc.php';
}

##########FINE Attesa
##########ASSEGNA
         } elseif($_REQUEST['status_id']=='scontrino'){



                if(isset($_REQUEST['input_field_scontrino'])) {


                    foreach ($_REQUEST['input_field_scontrino'] as $chiave => $valore) {

                      //mail("marco.salmi89@gmail.com","QUERY SCONTRINO",$valore[0]."-".$valore[1]);

                        $number = preg_replace("/[^0-9abcisgeABCISGE+]/", "", $valore[0]);

                        if (strpos($number, '+') !== false) {
                            $ref_num = preg_replace("/[^0-9abcisgeABCISGE]/", "", $number);

                            if (strlen($ref_num) > 4)
                                $id_ticket = Ticket::getIdByRef_num($ref_num);

                        } else {

                            if (strlen($number) > 4)
                                $id_ticket = Ticket::getIdByNumber($number);

                        }

                        if (!(ctype_digit($id_ticket) && $id_ticket > 4000))
                            continue;

                        $sql = "INSERT INTO ost_ticket_thread (pid,ticket_id,staff_id,user_id,thread_type,poster,title, body, format, ip_address, created)
                        VALUES
                        (0," . $id_ticket . "," . $thisstaff->getId() . ",0,'N','" . $thisstaff->getName() . "','Data scontrino', " .db_input($valore[1]) . ", 'html','127.0.0.1',NOW())";


                        //mail("marco.salmi89@gmail.com","QUERY SCONTRINO",$sql);
                        db_query($sql);
                        //$msg="Query: ".$sql;
                    }
                }
            }elseif($_REQUEST['status_id']==23){

           $status_id=$_REQUEST['status_id'];
           $tecnico=$_POST['tec_assegnato'];


             if(isset($_REQUEST['input_field4'])&&!empty($tecnico)){

                $staff=Staff::lookup($tecnico);

                 foreach($_REQUEST['input_field4'] as $chiave=>$valore){


                     $number=preg_replace("/[^0-9abcisgeABCISGE+]/", "",$valore[0]);

                     if(strpos($number, '+') !== false){
                       $ref_num=preg_replace("/[^0-9abcisgeABCISGE]/", "",$number);
                       //mail("marco.salmi89@gmail.com","REF NUM",$ref_num);
                       if(strlen($ref_num)>4)
                       $id_ticket=Ticket::getIdByRef_num($ref_num);

                     }else{

                       if(strlen($number)>4)
                       $id_ticket=Ticket::getIdByNumber($number);

                     }

                     if(!$id_ticket)
                     continue;



                     $query = "UPDATE ".TICKET_TABLE." SET dept_id=7,status_id=23,staff_id='".$tecnico."',updated=NOW() WHERE `ticket_id`=".$id_ticket;
                     //echo $query.'<br>';

                     if (!db_query($query)){
                     $errors['err']='Errore: non è stato possibile assegnare il ticket';
                     $inc = 'azioni.inc.php';
                     }else{




                     if($_POST['invio_email']==1){
                     $ticketton=Ticket::lookup($id_ticket);
                     $ticketton->assign($tecnico, "Ticket assegnato ed email inviata al tecnico");
     	               }

###TARIFFE###
                     $zona=array('RM','RI','VT','LT','FR');
                     //$zona2=array('AQ','PE','TE','CH');

     				         $topico=db_query("SELECT `topic_id`,`customer_location_l_addr1` FROM ".TICKET_TABLE." NATURAL JOIN ".TICKET_TABLE."__cdata WHERE `ticket_id`=".$id_ticket);
     				         while ($row=db_fetch_array($topico)){
     		                 $utopico=$row['topic_id'];
                         $utopica=trim($row['customer_location_l_addr1']);
    	               }



    				/*if (in_array($utopico, Array(15,16,17))){


                   if(($utopica=='VT' || $utopica=='RI') && $utopico==17)
                   $costo_ext =19.50;
                   elseif(($utopica=='RM' || $utopica=='LT' || $utopica=='FR') && $utopico==17)
                   $costo_ext =20;
                   else
                   $costo_ext =16;


    				       $costo_int =($tecnico==80)?0:10;

    				}elseif(in_array($utopico, Array(13,14))){

              if($utopica=='VT' || $utopica=='RI')
              $costo_ext =19.50;
              else
              $costo_ext =20;



    				       $costo_int =($tecnico==80)?0:11;

    				}elseif($utopico==12){

                   $costo_ext =15;
    				       $costo_int =($tecnico==80)?0:8;

              			}elseif(in_array($utopico, Array(18,19,20,21,22,23))){

                    if (in_array($utopica,$zona)){
                             $costo_ext =25;
              				       $costo_int =($tecnico==80)?0:11;
                    }else{
                             $costo_ext =25;

                             if($tecnico==80)//ditta
                             $costo_int=0;
                             elseif($tecnico==89)//levino
                             $costo_int=15;
                             else
                             $costo_int=12;

                                  }

              			}elseif(in_array($utopico, Array(24,25))){

                        if (in_array($utopica,$zona)){
                             $costo_ext =10;
              				       $costo_int =($tecnico==80)?0:6;
                        }else{
                             $costo_ext =10;
              				       $costo_int =($tecnico==80)?0:6;
                                  }

              			}elseif(in_array($utopico, Array(26,27,35))){

                        if (in_array($utopica,$zona)){
                             $costo_ext =25;
              				       $costo_int =($tecnico==80)?0:10;
                        }else{
                             $costo_ext =25;

                             if($tecnico==80)//ditta
                             $costo_int=0;
                             elseif($tecnico==89)//levino
                             $costo_int=15;
                             else
                             $costo_int=12;


                                  }

              				}elseif($utopico==36 or $utopico==38){

                        if (in_array($utopica,$zona)){
                             $costo_ext =19;
              				       $costo_int =($tecnico==80)?0:8;
                        }else{
                             $costo_ext =19;
              				       $costo_int =($tecnico==80)?0:10;
                             //$costo_int =($tecnico==89)?10:9; //TEC. LEVINO
                                  }


                      }elseif($utopico==39){

                   					 $costo_ext =16;
                   				   $costo_int =($tecnico==80)?0:10;

                   		}elseif($utopico==40){

                   					 $costo_ext =55;
                   				   $costo_int =($tecnico==80)?0:32;

                   		}elseif($utopico==41){

                   					 $costo_ext =45;
                   				   $costo_int =($tecnico==80)?0:23;

                   		}elseif($utopico==42){

                   					 $costo_ext =15;
                   				   $costo_int =($tecnico==80)?0:14;

                   		}elseif($utopico==43){

                   					 $costo_ext =15;
                   				   $costo_int =($tecnico==80)?0:8;

                   		}else{

                             $costo_ext =0;
              				       $costo_int =0;
              				}*/

                         /*if($tecnico==80) {
                             $costo_int=0;
                             $topico=db_query("SELECT `costo_ext` FROM ".TICKET_TABLE."__cdata WHERE `ticket_id`=".$id_ticket);
                             $row=db_fetch_array($topico);
                             $costo_ext=$row['costo_ext'];
                             db_query("UPDATE ".TICKET_TABLE."__cdata SET costo_int='".$costo_int."',prezzo='".($costo_ext-$costo_int)."' WHERE `ticket_id`=".$id_ticket);

                         } */
                         
                         //echo "UPDATE ".TICKET_TABLE."__cdata SET costo_ext='".$costo_ext."',costo_int='".$costo_int."',prezzo='".($costo_ext-$costo_int)."' WHERE `ticket_id`=".$id_ticket."<br>";


     				          $sql="INSERT INTO ost_ticket_thread (pid,ticket_id,staff_id,user_id,thread_type,poster,title, body, format, ip_address, created)
                      VALUES
                      (0,".$id_ticket.",".$thisstaff->getId().",0,'N','".$thisstaff->getName()."','Ticket assegnato a ".$staff->getLastName()."', 'Ticket assegnato da: ".$thisstaff->getName()."', 'html','127.0.0.1',NOW())";

                      db_query($sql);
                      //$msg="Ticket assegnati correttamente a: ".$staff->getLastName();

     			           }


                     $avviso.=str_replace('+','',$number)." - ";
                     unset($id_ticket);
                     unset($number);
                     unset($ref_num);
                   }
                   $msg="I seguenti ticket sono stati assegnati a ".$staff->getLastName().": ".$avviso;
                }else{
                  $errors['err']='Attenzione: inserire dati validi';
                  $inc = 'azioni.inc.php';
                }

            }
##########FINE ASSEGNA
         }


      }

    //Clear active submenu on search with no status
    if($_REQUEST['a']=='search' && !$_REQUEST['status'])
        $nav->setActiveSubMenu(-1);

    //set refresh rate if the user has it configured
    if(!$_POST && !$_REQUEST['a'] && ($min=$thisstaff->getRefreshRate())) {
        $js = "clearTimeout(window.ticket_refresh);
               window.ticket_refresh = setTimeout($.refreshTicketView,"
            .($min*60000).");";
        $ost->addExtraHeader('<script type="text/javascript">'.$js.'</script>',
            $js);
    }
}




if($thisstaff->getDeptId()==4 || $thisstaff->getDeptId()==6 || $thisstaff->getDeptId()==7){
require_once(STAFFINC_DIR.'header_new.inc.php');
}else{
require_once(STAFFINC_DIR.'header.inc.php');
}

require_once(STAFFINC_DIR.$inc);
print $response_form->getMedia();
require_once(STAFFINC_DIR.'footer.inc.php');

function nuovascadenza($from, $days, $holidays) {
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
    return $from->format('Y-m-d')." 01:00:00"; #  or just return DateTime object
}

