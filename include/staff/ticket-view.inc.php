<?php
//Note that ticket obj is initiated in tickets.php.
if(!defined('OSTSCPINC') || !$thisstaff || !is_object($ticket) || !$ticket->getId()) die('Invalid path');

//Make sure the staff is allowed to access the page.
if(!@$thisstaff->isStaff() || !$ticket->checkStaffAccess($thisstaff)) die('Access Denied');

//Re-use the post info on error...savekeyboards.org (Why keyboard? -> some people care about objects than users!!)
$info=($_POST && $errors)?Format::input($_POST):array();

//Auto-lock the ticket if locking is enabled.. If already locked by the user then it simply renews.
if($cfg->getLockTime() && !$ticket->acquireLock($thisstaff->getId(),$cfg->getLockTime()))
    $warn.=__('Unable to obtain a lock on the ticket');

//Get the goodies.
$dept  = $ticket->getDept();  //Dept
$staff = $ticket->getStaff(); //Assigned or closed by..
$user  = $ticket->getOwner(); //Ticket User (EndUser)
$team  = $ticket->getTeam();  //Assigned team.
$sla   = $ticket->getSLA();
$lock  = $ticket->getLock();  //Ticket lock obj
$id    = $ticket->getId();    //Ticket ID.
$username=$thisstaff->getUserName();
//Useful warnings and errors the user might want to know!
if ($ticket->isClosed() && !$ticket->isReopenable())
    $warn = sprintf(
            __('Current ticket status (%s) does not allow the end user to reply.'),
            $ticket->getStatus());
elseif ($ticket->isAssigned()
        && (($staff && $staff->getId()!=$thisstaff->getId())
            || ($team && !$team->hasMember($thisstaff))
        ))
    $warn.= sprintf('&nbsp;&nbsp;<span class="Icon assignedTicket">%s</span>',
            sprintf(__('Ticket is assigned to %s'),
                implode('/', $ticket->getAssignees())
                ));

if (!$errors['err']) {

    if ($lock && $lock->getStaffId()!=$thisstaff->getId())
        $errors['err'] = sprintf(__('This ticket is currently locked by %s'),
                $lock->getStaffName());
    elseif (($emailBanned=TicketFilter::isBanned($ticket->getEmail())))
        $errors['err'] = __('Email is in banlist! Must be removed before any reply/response');
    elseif (!Validator::is_valid_email($ticket->getEmail()))
        $errors['err'] = __('EndUser email address is not valid! Consider updating it before responding').' - '.$ticket->getEmail();
}

$unbannable=($emailBanned) ? BanList::includes($ticket->getEmail()) : false;

if($ticket->isOverdue())
    $warn.='&nbsp;&nbsp;<span class="Icon overdueTicket">'.__('Marked overdue!').'</span>';

?>

<table width="100%" cellpadding="2" cellspacing="0" border="0"><!--width="940"-->
    <tr>
        <td width="20%" class="has_bottom_border">
             <h2><a href="tickets.php?id=<?php echo $ticket->getId(); ?>"
             title="<?php echo __('Reload'); ?>"><i class="icon-refresh"></i>
             <?php echo sprintf(__('Ticket #%s'), $ticket->getNumber()); ?></a></h2>
        </td>
        <td width="auto" class="flush-right has_bottom_border">
            <?php
            if ($thisstaff->canBanEmails()
                    || $thisstaff->canEditTickets()
                    || ($dept && $dept->isManager($thisstaff))) { ?>
            <!--<span class="action-button pull-right" data-dropdown="#action-dropdown-more">
                <i class="icon-caret-down pull-right"></i>
                <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
            </span>ABILITARE ALTRE OPERAZIONI PER ADMIN-->
            <?php
            }
            // Status change options
            //echo TicketStatus::status_options(); //ABILITARE GLI STATI PER ADMIN

            if ($thisstaff->canEditTickets()) { ?>
                <!--<a class="action-button pull-right" href="tickets.php?id=<?php echo $ticket->getId(); ?>&a=edit"><i class="icon-edit"></i> <?php
                    echo __('Edit'); ?></a>ABILITARE EDITAZIONE TICKET PER ADMIN-->
            <?php
            }
            $noedit = array('10','11','12','13','14','15','16','17','18','19');
            if (!in_array($thisstaff->getDeptId(),$noedit)){
            if ($ticket->isOpen()
                    && !$ticket->isAssigned()
                    && $thisstaff->canAssignTickets()
                    && $ticket->getDept()->isMember($thisstaff)) {?>
                <a id="ticket-claim" class="action-button pull-right confirm-action" href="#claim"><i class="icon-user"></i>EDIT</a>

            <?php
            }elseif(!$ticket->isClosed()){?>
            <a class="action-button pull-right confirm-action" id="link-opzioni_risposta" onclick="mostranascondi3('opzioni_risposta','link-opzioni_risposta');"><i class="icon-user"></i><strong>EDIT</strong></a>
            <?php
            }elseif($ticket->isClosed() and $thisstaff->getDeptId()==6){?>
			 <a class="action-button pull-right confirm-action" id="link-opzioni_risposta" onclick="mostranascondi3('opzioni_risposta','link-opzioni_risposta');"><i class="icon-user"></i><strong>EDIT</strong></a>
            <?php
            }

            }?>	
            
            <?php
            if($ticket->isClosed() and $thisstaff->getDeptId()==17 and time()-strtotime($ticket->getCloseDate())<259200){?>
            <a class="action-button pull-right confirm-action" id="ticket-answered" href="#answered"><i class="icon-edit"></i>Rifiuta Chiusura Ticket</a>
            <?php
            }?>
            
            <?php
            if($thisstaff->getDeptId()==6 || $thisstaff->getDeptId()==7 || $thisstaff->getDeptId()==9){?>
            <a class="action-button pull-right confirm-action"  href="#postnote"><i class="icon-edit"></i>Allega file</a>
            <?php
            }?>	
            <span class="action-button pull-right" data-dropdown="#action-dropdown-print">
                <i class="icon-caret-down pull-right"></i>
                <a id="ticket-print" href="tickets.php?id=<?php echo $ticket->getId(); ?>&a=print"><i class="icon-print"></i> <?php
                    echo __('Print'); ?></a>
            </span>
            <div id="action-dropdown-print" class="action-dropdown anchor-right">
              <ul>
                 <li><a class="no-pjax" target="_blank" href="tickets.php?id=<?php echo $ticket->getId(); ?>&a=print&notes=0"><i
                 class="icon-file-alt"></i> <?php echo __('Ticket Thread'); ?></a>
                 <li><a class="no-pjax" target="_blank" href="tickets.php?id=<?php echo $ticket->getId(); ?>&a=print&notes=1"><i
                 class="icon-file-text-alt"></i> <?php echo __('Thread + Internal Notes'); ?></a>
              </ul>
            </div>
            
            <div id="action-dropdown-more" class="action-dropdown anchor-right">
              <ul>
                <?php
                 if($thisstaff->canEditTickets()) { ?>
                    <li><a class="change-user" href="#tickets/<?php
                    echo $ticket->getId(); ?>/change-user"><i class="icon-user"></i> <?php
                    echo __('Change Owner'); ?></a></li>
                <?php
                 }
                 if($thisstaff->canDeleteTickets()) {
                     ?>
                    <li><a class="ticket-action" href="#tickets/<?php
                    echo $ticket->getId(); ?>/status/delete"
                    data-href="tickets.php"><i class="icon-trash"></i> <?php
                    echo __('Delete Ticket'); ?></a></li>
                <?php
                 }
                if($ticket->isOpen() && ($dept && $dept->isManager($thisstaff))) {

                    if($ticket->isAssigned()) { ?>
                        <li><a  class="confirm-action" id="ticket-release" href="#release"><i class="icon-user"></i> <?php
                            echo __('Release (unassign) Ticket'); ?></a></li>
                    <?php
                    }

                    if(!$ticket->isOverdue()) { ?>
                        <li><a class="confirm-action" id="ticket-overdue" href="#overdue"><i class="icon-bell"></i> <?php
                            echo __('Mark as Overdue'); ?></a></li>
                    <?php
                    }

                    if($ticket->isAnswered()) { ?>
                    <li><a class="confirm-action" id="ticket-unanswered" href="#unanswered"><i class="icon-circle-arrow-left"></i> <?php
                            echo __('Mark as Unanswered'); ?></a></li>
                    <?php
                    } else { ?>
                    <li><a class="confirm-action" id="ticket-answered" href="#answered"><i class="icon-circle-arrow-right"></i> <?php
                            echo __('Mark as Answered'); ?></a></li>
                    <?php
                    }
                } ?>
                <li><a href="#ajax.php/tickets/<?php echo $ticket->getId();
                    ?>/forms/manage" onclick="javascript:
                    $.dialog($(this).attr('href').substr(1), 201);
                    return false"
                    ><i class="icon-paste"></i> <?php echo __('Manage Forms'); ?></a></li>

<?php           if($thisstaff->canBanEmails()) {
                     if(!$emailBanned) {?>
                        <li><a class="confirm-action" id="ticket-banemail"
                            href="#banemail"><i class="icon-ban-circle"></i> <?php echo sprintf(
                                Format::htmlchars(__('Ban Email <%s>')),
                                $ticket->getEmail()); ?></a></li>
                <?php
                     } elseif($unbannable) { ?>
                        <li><a  class="confirm-action" id="ticket-banemail"
                            href="#unbanemail"><i class="icon-undo"></i> <?php echo sprintf(
                                Format::htmlchars(__('Unban Email <%s>')),
                                $ticket->getEmail()); ?></a></li>
                    <?php
                     }
                }?>
              </ul>
            </div>
        </td>
    </tr>
</table>
<table border="0" width="100%"  style="border-collapse: separate; border-spacing: 0px 0px; margin-top:0px; margin-bottom:0px; background:transparent; "><tr><td align="right"><!--<a style="margin-right: 30px;" id="link-opzioni_risposta" onclick="mostranascondi3('opzioni_risposta','link-opzioni_risposta');"><strong>EDIT</strong></a>--></td></tr></table>
<table style="border-collapse: separate; border-spacing: 0px 0px; margin-top:0px; margin-bottom:10px; background:transparent; ">
<tr>
<td rowspan="2" style="vertical-align:top;">
<!--TECNICI -->
<?php  
$tec = array(7,8,9,10,11,12,13,14,15,16,17,18,19);
if (!in_array($thisstaff->getDeptId(), $tec)) {
//if ($thisstaff->getDeptId()!=8 AND $thisstaff->getDeptId()!=9 AND $thisstaff->getDeptId()!=10 AND $thisstaff->getDeptId()!=11 AND $thisstaff->getId()!=2 AND $thisstaff->getDeptId()!=12) {?>
<div style="height:472px;
	width:250px;
	overflow:auto;
	text-align:center;
	border: 0px solid #CCC;
	border-radius: 12px;
	background: #CCC;
	margin-right:auto;
	margin-left:auto;
	alignment-adjust:central;
	-moz-box-shadow:    inset 0 0 20px #000000;
   -webkit-box-shadow: inset 0 0 20px #000000;
   box-shadow:         inset 0 0 20px #000000;">
  <div style="margin-left:auto;
    margin-right:auto;
    width: 100%;
	height:450px;
	overflow: auto;">
	  <h1 align="center">&nbsp;Tecnici</h1><center>
 <center>
 <table width="90%" border="0"  style="border-collapse: separate; border-spacing: 0px 2px; margin-top:-20px; margin-bottom:10px; background:transparent; ">
     
     <tbody style="border-radius:25px;">
        <?php
        // Setup Subject field for display
        if ($thisstaff->getId()==5) {
        $tecnici=array('Tecnico1'=>'2');
        }else{
		$tecnici=array('Zavattolo Domenico'=>'1',
		 'Casorelli Ernesto'=>'3', 
		 'Dezi Francesco'=>'5', 
		 'Reale Ferdinando'=>'4', 
		 'Borghi Raffaella'=>'6', 
		 'magazzino magazzino'=>'14',
		 'tpr tpr'=>'14',
		 'pcm pcm'=>'16',
		 'SolariVGA SolariVGA'=>'17',
		 'TPR - Solari NGA TPR - Solari NGA'=>'18',
		 'SolariNGA SolariNGA'=>'19',
		 'ST- Solari NGA ST- Solari NGA'=>'20',
		 //'Romani Cesare'=>'21',
		 'Giglio Francesco'=>'22',
		 'Di Lernia Nicolò'=>'23',
		 'Candini Franco'=>'24',
		 'Giglio Pantaleo'=>'25',
		 'Tocci Marcello'=>'26',
		 'Nobile Mattia'=>'27',
		 'Mosca Fabio'=>'28',
		 'Presutti Stefano'=>'29',
		 'Rosati Luciano'=>'30',
		 'Morlupi Claudio'=>'31',
		 'Orecchio Giovanni'=>'32',
		 'Madonna Fortunato'=>'33',
		 'Anzoletti Franco'=>'34',
		 'Bruni Vincenzo'=>'35',
		 'Di Filippo Andrea'=>'36',
		 'Frontini Andrea'=>'37',
		 'PCM - Solari NGA PCM - Solari NGA'=>'38',
		 'Lanzi Alessandro'=>'44',
		 'D\'Antonio Fabrizio'=>'46',
		 'Panebianco Stefano'=>'47',
		 'Onofri Massimiliano'=>'48',
		 'Lovadina Piero'=>'52',
		 'Moles Valerio'=>'53',
		 'Farci Paolo'=>'54',
		 'Arena Ivan'=>'55',
		 'Bosco Carmine'=>'56',
		 'Caprino Rodolfo'=>'57',
		 'Gallo Piero'=>'58',
		 'Mannino Biagio'=>'59',
		 'Caglieri Marcello'=>'60',
		 'Colinelli Bruna'=>'61',
		 'Tolazzi Mauro'=>'62');
        }
        
        ksort($tecnici);
        
        foreach ($tecnici as $utente=>$tecnico) {
        $query ='SELECT ticket.ticket_id,user.name FROM '.TICKET_TABLE.' ticket '.
       ' LEFT JOIN '.USER_TABLE.' user ON user.id = ticket.user_id WHERE ticket.staff_id='.$tecnico.' AND ticket.status_id!=2';
       
        $res = db_query($query); //mettere if su res
        $results = array();
        while ($row = db_fetch_array($res)) {
         $results[] = $row;

}
//print_r($results);
/*
echo $utente;
foreach ($results as $risultato) {
	echo $risultato['number']."<br>";
	echo $risultato['name']."<br>";
}
}  */
?>
      
             <?php if ($results) { ?>
             <tr>
                <td>
			     <center>		
                 <table width="99%" border="0" style="border-collapse: separate; border-spacing: 0px 0px; margin-top:8px; margin-bottom:0px; background:transparent; ">

                 <tr>

                 <td align="center" width="20%" class="nohover" style="background:#82AA63; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:12px 0px 0px 0px;  padding-top:10px; -moz-border-radius:12px 0px 0px 0px; -webkit-border-radius:12px 0px 0px 0px;">
                 <img src="../images/tecnico.png" style="margin-left:10px">   
                 </td>
                

                 <td align="center" nowrap  width="60%" colspan="2" style="background:#82AA63;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><strong><a href="?a=profilo&id=<?php echo $ticket->getId();?>&tecnico=<?php $cognome=explode(' ', $utente, 3); if (trim($cognome[0])=='ST-'){$cognome[0]='ST-Solari';}elseif (trim($cognome[0])=='PCM'){$cognome[0]='PCM-Solari';}elseif (trim($cognome[0])=='TPR'){$cognome[0]='TPR-Solari';}elseif (trim($cognome[0])=='Di'){$cognome[0]=$cognome[0].' '.$cognome[1];} echo $cognome[0];?>&riferimento=<?php echo $tecnico; ?>"><?php  echo $cognome[0];?></a></strong></td>

                 <td align="center"  width="20%" class="nohover" style="background:#82AA63; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:0px 12px 0px 0px;  padding-top:10px; -moz-border-radius:0px 12px 0px 0px; -webkit-border-radius:0px 12px 0px 0px;"></td>

                 </tr>

                 <?php $z=0; foreach ($results as $risultato) { //visualizzo il numero di ticket assegnati ad ogni tecnico?>
                 <!--<tr>

                 <td align="center" nowrap width="50%" colspan="2" style="background:#82AA63; border-right: 1px solid #396E42; border-top: 1px solid #396E42; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; padding-top:10px;"><strong>Ticket Nr:<br></strong><a href="tickets.php?id=<?php echo $risultato['ticket_id']; ?>"><?php echo $risultato['number'];?></a></td>

                 <td align="center" colspan="2" width="50%" nowrap style="background:#82AA63; border-top: 1px solid #396E42; padding-top:10px; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px   black;"><strong>Cliente:<br></strong><?php echo $risultato['name']; ?></td>

                 </tr>-->
                  <?php $z++;} ?>
                  
                  <tr>

                 <td align="center" nowrap width="100%" colspan="4" style="background:#82AA63; border-right: 1px solid #396E42; border-top: 1px solid #396E42; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; padding-top:10px;"><strong>Ticket assegnati:&nbsp;</strong>(<?php echo $z;?>)</td>

                 
                 </tr>
                  <tr>

                 <td align="center" nowrap width="50%" colspan="2" style="background:#82AA63;  border-top: 1px solid #396E42; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:0px 0px 0px 12px;  padding-top:10px; -moz-border-radius:0px 0px 0px 12px; -webkit-border-radius:0px 0px 0px 12px;">&nbsp;</td>

                 <td align="center" colspan="2" width="50%" nowrap style="background:#82AA63; border-top: 1px solid #396E42; border-radius:0px 0px 12px 0px;  padding-top:10px; -moz-border-radius:0px 0px 12px 0px; -webkit-border-radius:0px 0px 12px 0px; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px   black;">&nbsp;</td>

                 </tr>
                </table> 
                </center>
                </td>
                </tr>
                <?php } ?>
<?php } ?>
                
   
    </tbody>
    <tfoot>
    </tfoot>
    </table>
    </center>
  </div></div>
 <?php }?>
 
 <!--FINE TECNICI-->
 
 
 <?php	//query per la personalizzazione
				$sql1= 'SELECT number, zz_date1, 
				created, user_id, ref_num, status_sym, 
				customer_middle_name, status_id, group_last_name, 
				category_sym, customer_location_l_addr7, 
				customer_location_l_addr1, customer_phone_number,
				zz_date6, zz_dt_clmghw, customer_zz_top_sp_ch_lun, zz_data_inizio_intervento_man, codice, nome, cliente, descrizione, gruppo 
				FROM '.TICKET_TABLE.'__cdata  NATURAL JOIN '.TICKET_TABLE.' NATURAL JOIN ost_commesse WHERE `ticket_id`='.$ticket->getId().' LIMIT 1';	
		        //echo $sql1;
		        
		        $risultanza = db_query($sql1);
		        while ($row = db_fetch_array($risultanza )) {
        
        
        $ticket_interno = $row['number'];
        $stato_interno = $row['status_id'];
        $cliente = $row['user_id'];
        $data_creazione = $row['created'];
        $trasfer_date = $row['zz_date1'];
		$problem=$row['ref_num'];
		$stato_cliente = $row['status_sym']; //attenzione 4 settembre
		$sede_cliente = $row['customer_middle_name'];
		$categoria = $row['category_sym'];
		$gruppo = $row['group_last_name'];
		$localita = $row['customer_location_l_addr7'];
		$provincia = $row['customer_location_l_addr1'];
		$tel_ufficio = $row['customer_phone_number'];
		$commessa_codice = $row['codice'];
		$commessa_nome = $row['nome'];
		$commessa_descrizione = $row['descrizione'];
		$commessa_gruppo = $row['gruppo'];
		$commessa_cliente = $row['cliente'];
		$data_previsto_intervento =$row['zz_date6'];
		$data_proposta_chiusura =$row['zz_dt_clmghw'];
		$mono =$row['customer_zz_top_sp_ch_lun'];
		$data_inizio_intervento =$row['zz_data_inizio_intervento_man'];
        }

 if  (isset($provincia) && $provincia!=''){
 $sql2= "SELECT nomeregione FROM ost_regioni  NATURAL JOIN ost_province WHERE siglaprovincia='".$provincia."' LIMIT 1";	
		        $valore = db_query($sql2);
		        while ($riga = db_fetch_array($valore )) {
				$regione =$riga['nomeregione'];
				}
}				
 //inizio commessa
 //echo $gruppo;
  if($cliente==9){
  switch ($gruppo) {
                                   
                                   case 'GESTIONE_ATTESE':
                                   $nome_commessa="Poste_GA";
                                   $codice_commessa="Pt-GA";
                                   $desc_commessa="Manutenzione gestore attese";
                                   break;
                                   case 'NEW_ATTESE':
                                   $nome_commessa="Poste_NGA_hw";
                                   $codice_commessa="Pt-NGA_hw";
                                   $desc_commessa="Manutenzione nuovo gestore attese hardware";
                                   break;
                                   case 'WEBTV':
                                   $nome_commessa="Poste_WEBTV";
                                   $codice_commessa="Pt-WEBTV";
                                   $desc_commessa="Manutenzione WebTV";
                                   break;
                                   case 'RASWAP_PTL':
                                   $nome_commessa="Poste_PTL";
                                   $codice_commessa="Pt-PTL";
                                   $desc_commessa="Postino Telematico";
                                   break;
                                   case 'CARICA BATT.4SLOT POS.PORTALETTERE':
                                   $nome_commessa="PM-MANUTENZIONE-ONSITE";
                                   $codice_commessa="PM_MANUTENZIONE_ONSITE";
                                   $desc_commessa="Postino Telematico";
                                   break;
                                   case 'CARICA BATT.4SLOT STAMP.BLUETOOTH':
                                   $nome_commessa="PM-MANUTENZIONE-ONSITE";
                                   $codice_commessa="PM_MANUTENZIONE_ONSITE";
                                   $desc_commessa="Postino Telematico";
                                   break;
                                   case 'CARICA BATT.4SLOT TERM.PORTALETTERE':
                                   $nome_commessa="PM-MANUTENZIONE-ONSITE";
                                   $codice_commessa="PM_MANUTENZIONE_ONSITE";
                                   $desc_commessa="Postino Telematico";
                                   break;
                                   case 'CULLE':
                                   $nome_commessa="PM-MANUTENZIONE-ONSITE";
                                   $codice_commessa="PM_MANUTENZIONE_ONSITE";
                                   $desc_commessa="Postino Telematico";
                                   break;
                                   case 'PALMARI':
                                   $nome_commessa="PM-MANUTENZIONE-ONSITE";
                                   $codice_commessa="PM_MANUTENZIONE_ONSITE";
                                   $desc_commessa="Postino Telematico";
                                   break;
                                   case 'POS':
                                   $nome_commessa="PM-MANUTENZIONE-ONSITE";
                                   $codice_commessa="PM_MANUTENZIONE_ONSITE";
                                   $desc_commessa="Postino Telematico";
                                   break;
                                   case 'POS_PORTALETTERE':
                                   $nome_commessa="PM-MANUTENZIONE-ONSITE";
                                   $codice_commessa="PM_MANUTENZIONE_ONSITE";
                                   $desc_commessa="Postino Telematico";
                                   break;
                                   case 'STAMPANTE TERMICA':
                                   $nome_commessa="PM-MANUTENZIONE-ONSITE";
                                   $codice_commessa="PM_MANUTENZIONE_ONSITE";
                                   $desc_commessa="Postino Telematico";
                                   break;
                                   case 'STAMPANTI_PORTALETTERE':
                                   $nome_commessa="PM-MANUTENZIONE-ONSITE";
                                   $codice_commessa="PM_MANUTENZIONE_ONSITE";
                                   $desc_commessa="Postino Telematico";
                                   break;
                                   case 'TABLET_GIPA':
                                   $nome_commessa="PM-MANUTENZIONE-ONSITE";
                                   $codice_commessa="PM_MANUTENZIONE_ONSITE";
                                   $desc_commessa="Postino Telematico";
                                   break;
                                   case 'TERMINALI_PORTALETTERE':
                                   $nome_commessa="PM-MANUTENZIONE-ONSITE";
                                   $codice_commessa="PM_MANUTENZIONE_ONSITE";
                                   $desc_commessa="Postino Telematico";
                                   break;
 }
 
}else{
                                   $nome_commessa=$commessa_nome;
                                   $codice_commessa=$commessa_codice;
                                   $desc_commessa=$commessa_descrizione;
}
 //fine commessa 
  
     
 switch ($stato_interno) {
                                   case '1':
                                   $stato_int="Transferred";
                                   break;
                                   case '2':
                                   $stato_int="Risolto";
                                   break;
                                   case '3':
                                   $stato_int="Chiuso";
                                   break;
                                   case '4':
                                   $stato_int="Archiviato";
                                   break;
                                   case '5':
                                   $stato_int="Cancellato";
                                   break;
                                   case '6':
                                   $stato_int="Screening";
                                   break;
                                   case '7':
                                   $stato_int="Planning";
                                   break;
                                   case '8':
                                   $stato_int="Rifiutato";
                                   break;
                                   case '9':
                                   $stato_int="Non risolto";
                                   break;
                                   case '10':
                                   $stato_int="Da validare PLN";
                                   break;
                                   case '11':
                                   $stato_int="In carico al magazzino";
                                   break;
                                   case '12':
                                   $stato_int="In carico al laboratorio";
                                   break;
                                   case '13':
                                   $stato_int="Attesa preventivo";
                                   break;
                                   case '14':
                                   $stato_int="Riparato LAB";
                                   break;
                                   case '15':
                                   $stato_int="Lavorato magazzino";
                                   break;
                                   case '16':
                                   $stato_int="In carico Tec. LAB";
                                   break;
                                   case '17':
                                   $stato_int="Da validare LAB";
                                   break;
                                   case '18':
                                   $stato_int="Non riparato";
                                   break;
                                   case '19':
                                   $stato_int="In attesa parti";
                                   break;
                                   case '20':
                                   $stato_int="Da dismettere";
                                   break;
                                   case '21':
                                   $stato_int="Attesa cliente";
                                   break;
                                   case '22':
                                   $stato_int="Ripreso in carico";
                                   break;
                                   case '23':
                                   $stato_int="Assegnato";
                                   break;
                                   }
                                   
                       
function getInbetweenStrings($start, $end, $str){
        $matches = array();
        $regex = "/$start(.*?)$end/s";
        preg_match($regex, $str, $matches);
        $ritorno = @trim($matches[1]);
        return $ritorno;
        } 
        
				
	

 
 ?>         

</td>
<td rowspan="2" width="1%" style="vertical-align:top;">
<td valign="top"><!-- inizio tabella 4 celle-->	
<div style="width: 98%;">
  <div style="width:98%;padding:2%;
    height:auto;
	margin-right:auto;
	margin-left:auto;
	margin-bottom:10px;
	padding-bottom:10px;
	border: 0px solid #ccc;
	border-radius: 12px;
	background:#ccc;
			-moz-box-shadow:    inset 0 0 20px #000000;
   -webkit-box-shadow: inset 0 0 20px #000000;
   box-shadow:         inset 0 0 20px #000000;">	
   <?php //if(isset($_GET['a']) || $_GET['a']=='edit') echo "profilo";?>
<table border="0" width="100%"  style="border-collapse: separate; border-spacing: 0px 0px; margin-top:0px; margin-bottom:0px; background:transparent; "><tr><td align="left" style="background:#ffffff; border-radius:12px 0px 0px 12px;  padding-top:10px; -moz-border-radius:12px 0px 0px 12px; -webkit-border-radius:12px 0px 0px 12px; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><h2 style="color:#82AA63; margin-bottom:10px;">&nbsp;Dati Ticket </h2></td><td align="right" style="background:#ffffff; border-radius:0px 12px 12px 0px;  padding-top:10px; -moz-border-radius:0px 12px 12px 0px; -webkit-border-radius:0px 12px 12px 0px; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><a style="float:right; margin-bottom:10px;" id="link-<?php echo $ticket->getId(); ?>" onclick="mostranascondi('eventbody-<?php echo $ticket->getId(); ?>','link-<?php echo $ticket->getId(); ?>');"><img src="../images/up.png"></a></td></tr></table>
<div id="eventbody-<?php
                echo $ticket->getId(); ?>" style="display:block;">
	
<table  cellspacing="0" cellpadding="0" width="100%" border="0"><!--width="940"-->
    <tr>
        <td width="50%">
            <table border="0" cellspacing="" cellpadding="4" width="100%" style="border-collapse: separate; border-spacing: 0px 0px; margin-top:5px; margin-bottom:0px; background:transparent; ">
                <tr>
                    <th align="left" width="200" style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:12px 0px 0px 0px;  padding-top:10px; -moz-border-radius:12px 0px 0px 0px; -webkit-border-radius:12px 0px 0px 0px;">Numero ticket interno:</th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo $ticket_interno; ?></td>
                </tr>
                <tr>
                    <th align="left" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo __('Status');?> interno:</th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo $ticket->getStatus(); ?></td>
                </tr>
                <?php if ($cliente==9){?>
                <tr>
                    <th align="left" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">Località:</th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo $localita.' ('.$provincia.')'; ?></td>
                </tr>
                <?php }?>
                <tr>
                    <th align="left" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><font color="<?php echo $tel_ufficio?'black':'grey';?>">Telefono Ufficio:</font></th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo $tel_ufficio;//echo Format::htmlchars($ticket->getDeptName()); ?></td>
                </tr>
                
                <tr>
                    <th align="left" style="background:#ffffff; border-right: 1px solid #ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;"><font color="<?php echo $ticket->frazionario()?'black':'grey';?>">Frazionario:</font></th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo $ticket->frazionario(); if (isset($mono)&&!empty($mono)){echo (strpos($ticket->mono_turno(), '19') !== false OR strpos($ticket->mono_turno(), '18') !== false)?' (doppio turno)':' (mono turno)';}?></td>
                </tr>
                <tr>
                    <th align="left" style="background:#ffffff; border-right: 1px solid #ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;"><font color="<?php echo date('d/m/Y H:i:s',$ticket->data_previsto_intervento())?'black':'grey';?>">Data prevista intervento:</font></th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo date('d/m/Y H:i:s',$ticket->data_previsto_intervento()); ?></td>
                </tr>
                <tr>
                    <th align="left" style="background:#ffffff; border-right: 1px solid #ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;"><font color="<?php echo date('d/m/Y H:i:s',$ticket->data_appuntamento())?'black':'grey';?>">Data appuntamento:</font></th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo date('d/m/Y H:i:s',$ticket->data_appuntamento()); ?></td>
                </tr>
                <tr>
                    <th align="left" style="background:#ffffff; border-right: 1px solid #ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;"><font color="<?php echo date('d/m/Y H:i:s',$data_inizio_intervento)?'black':'grey';?>">Data inizio intervento:</font></th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo date('d/m/Y H:i:s',$data_inizio_intervento); ?></td>
                </tr>
                <tr>
                    <th align="left" style="background:#ffffff; border-right: 1px solid #ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:0px 0px 0px 12px;  -moz-border-radius:0px 0px 0px 12px; -webkit-border-radius:0px 0px 0px 12px;">Transfer Date:</th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo $cliente==9?date('d/m/Y H:i:s', $trasfer_date):Format::db_datetime($ticket->getCreateDate()); ?></td>
                </tr>
            </table>
        </td>
        <td width="50%">
            <table border="0" cellspacing="" cellpadding="4" width="100%" style="border-collapse: separate; border-spacing: 0px 0px; margin-top:5px; margin-bottom:0px; background:transparent; ">
                 <tr>
                    <th align="left" width="200" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">Numero ticket cliente:</th>
                    <td style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:0px 12px 0px 0px;  padding-top:10px; -moz-border-radius:0px 12px 0px 0px; -webkit-border-radius:0px 12px 0px 0px;"><?php echo $problem; ?></td>
                </tr>
                <?php if ($cliente==9){?>
                <tr>
                    <th align="left" width="200" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">Stato cliente:</th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo $stato_cliente;//$ticket->getStatus(); ?></td>
                </tr>
                <?php }?>
                <tr>
                    <th align="left" width="200" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">Cliente:</th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo $commessa_cliente; ?></td>
                    <!--<td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><a href="#tickets/<?php echo $ticket->getId(); ?>/user"
                        onclick="javascript:
                            $.userLookup('ajax.php/tickets/<?php echo $ticket->getId(); ?>/user',
                                    function (user) {
                                        $('#user-'+user.id+'-name').text(user.name);
                                        $('#user-'+user.id+'-email').text(user.email);
                                        $('#user-'+user.id+'-phone').text(user.phone);
                                        $('select#emailreply option[value=1]').text(user.name+' <'+user.email+'>');
                                    });
                            return false;
                            "><i class="icon-user"></i> <span id="user-<?php echo $ticket->getOwnerId(); ?>-name"
                            ><?php echo Format::htmlchars($ticket->getName());
                        ?></span></a>
                        <?php
                        if($user) {
                            echo sprintf('&nbsp;&nbsp;<a href="tickets.php?a=search&uid=%d" title="%s" data-dropdown="#action-dropdown-stats">(<b>%d</b>)</a>',
                                    urlencode($user->getId()), __('Related Tickets'), $user->getNumTickets());
                        ?>
                            <div id="action-dropdown-stats" class="action-dropdown anchor-right">
                                <ul>
                                    <?php
                                    if(($open=$user->getNumOpenTickets()))
                                        echo sprintf('<li><a href="tickets.php?a=search&status=open&uid=%s"><i class="icon-folder-open-alt icon-fixed-width"></i> %s</a></li>',
                                                $user->getId(), sprintf(_N('%d Open Ticket', '%d Open Tickets', $open), $open));

                                    if(($closed=$user->getNumClosedTickets()))
                                        echo sprintf('<li><a href="tickets.php?a=search&status=closed&uid=%d"><i
                                                class="icon-folder-close-alt icon-fixed-width"></i> %s</a></li>',
                                                $user->getId(), sprintf(_N('%d Closed Ticket', '%d Closed Tickets', $closed), $closed));
                                    ?>
                                    <li><a href="tickets.php?a=search&uid=<?php echo $ticket->getOwnerId(); ?>"><i class="icon-double-angle-right icon-fixed-width"></i> <?php echo __('All Tickets'); ?></a></li>
                                    <li><a href="users.php?id=<?php echo
                                    $user->getId(); ?>"><i class="icon-user
                                    icon-fixed-width"></i> <?php echo __('Manage User'); ?></a></li>
<?php if ($user->getOrgId()) { ?>
                                    <li><a href="orgs.php?id=<?php echo $user->getOrgId(); ?>"><i
                                        class="icon-building icon-fixed-width"></i> <?php
                                        echo __('Manage Organization'); ?></a></li>
<?php } ?>
                                </ul>
                            </div>
                    <?php
                        }
                    ?>
                    </td>-->
                </tr>
                <tr>
                    <th align="left" width="200" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><font color="<?php echo $sede_cliente?'black':'grey';?>">Sede cliente:</font></th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo $sede_cliente; ?></td>
                </tr>
                 <tr>
                    <th align="left" width="200" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><font color="<?php echo $regione?'black':'grey';?>">Regione:</font></th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo $regione; ?></td>
                </tr>
                <tr>
                    <th align="left" width="200" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><font color="<?php echo date('d/m/Y H:i:s',$ticket->data_contatto_utente())?'black':'grey';?>">Data contatto utente:</font></th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo date('d/m/Y H:i:s',$ticket->data_contatto_utente()); ?></td>
                </tr>
                <tr>
                    <th align="left" width="200" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><font color="<?php echo $ticket->motivo_sospensione()?'black':'grey';?>">Motivo sospensione:</font></th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><a id="motivo-sospensione" href="#">
                     <?php echo ucfirst(strtolower(Format::truncate($ticket->motivo_sospensione(),50))); ?></a></td>
                </tr>
                <tr>
                    <th align="left" style="background:#ffffff; border-right: 1px solid #ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;"><font color="<?php echo date('d/m/Y H:i:s',$data_proposta_chiusura)?'black':'grey';?>">Data proposta chiusura:</font></th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo date('d/m/Y H:i:s',$data_proposta_chiusura); ?></td>
                </tr>
                <tr>
                    <th align="left" width="200" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo __('Due Date');?>:</th>
                    <td style="background:#ffffff; border-right: 1px solid #ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:0px 0px 12px 0px; -moz-border-radius:0px 0px 12px 0px; -webkit-border-radius:0px 0px 12px 0px;"><?php echo Format::db_datetime($ticket->getEstDueDate()); ?></td>
                </tr>
                <!--<tr>
                    <th align="left" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo __('Email'); ?>:</th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">
                        <span id="user-<?php echo $ticket->getOwnerId(); ?>-email"><?php echo $ticket->getEmail(); ?></span>
                    </td>
                </tr>
                <tr>
                    <th align="left" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo __('Phone'); ?>:</th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">
                        <span id="user-<?php echo $ticket->getOwnerId(); ?>-phone"><?php echo $ticket->getPhoneNumber(); ?></span>
                    </td>
                </tr>
                <tr>
                    <th align="left" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo __('Source'); ?>:</th>
                    <td style="background:#ffffff; border-right: 1px solid #ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:0px 0px 12px 0px;  padding-top:10px; -moz-border-radius:0px 0px 12px 0px; -webkit-border-radius:0px 0px 12px 0px;"><?php
                        (Format::htmlchars($ticket->getSource())=="API") ? print "SOAP": print(Format::htmlchars($ticket->getSource()));

                        if($ticket->getIP())
                            echo '&nbsp;&nbsp;<span class="faded">('.$ticket->getIP().')</span>';
                        ?>
                    </td>
                </tr>-->
            </table>
        </td>
    </tr>
</table>
</div><!--div per hide show-->
<br><!--dettaglio commessa -->
<table border="0" width="100%"  style="border-collapse: separate; border-spacing: 0px 0px; margin-top:0px; margin-bottom:0px; background:transparent; "><tr><td align="left" style="background:#ffffff; border-radius:12px 0px 0px 12px;  padding-top:10px; -moz-border-radius:12px 0px 0px 12px; -webkit-border-radius:12px 0px 0px 12px; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><h2 style="color:#82AA63; margin-bottom:10px;">&nbsp;Dettaglio commessa </h2></td><td align="right" style="background:#ffffff; border-radius:0px 12px 12px 0px;  padding-top:10px; -moz-border-radius:0px 12px 12px 0px; -webkit-border-radius:0px 12px 12px 0px; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><a style="float:right; margin-bottom:10px;" id="link-<?php echo strtotime($ticket->getLastMsgDate()); ?>" onclick="mostranascondi('eventbody-<?php echo strtotime($ticket->getLastMsgDate()); ?>','link-<?php echo strtotime($ticket->getLastMsgDate()); ?>');"><img src="../images/up.png"></a></td></tr></table>
<div id="eventbody-<?php
                echo strtotime($ticket->getLastMsgDate());?>" style="display:block;">
                
<table  cellspacing="0" cellpadding="0" width="100%" border="0"><!--width="940"-->
    <tr>
        <td width="50%">
            <table cellspacing="0" cellpadding="4" width="100%" border="0" style="border-collapse: separate; border-spacing: 0px 0px; margin-top:5px; margin-bottom:0px; background:transparent; ">
                <!--<?php
                if($ticket->isOpen()) { ?>
                <tr>
                    <th align="left" width="200" style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:12px 0px 0px 0px;  padding-top:10px; -moz-border-radius:12px 0px 0px 0px; -webkit-border-radius:12px 0px 0px 0px;"><?php echo __('Assigned To');?>:</th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">
                        <?php
                        if($ticket->isAssigned())
                            echo Format::htmlchars(implode('/', $ticket->getAssignees()));
                        else
                            echo '<span class="faded">&mdash; '.__('Unassigned').' &mdash;</span>';
                        ?>
                    </td>
                </tr>
                <?php
                } else { ?>
                <tr>
                    <th align="left" width="200" style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:12px 0px 0px 0px;  padding-top:10px; -moz-border-radius:12px 0px 0px 0px; -webkit-border-radius:12px 0px 0px 0px;"><?php echo __('Closed By');?>:</th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">
                        <?php
                        if(($staff = $ticket->getStaff()))
                            echo Format::htmlchars($staff->getName());
                        else
                            echo '<span class="faded">&mdash; '.__('Unknown').' &mdash;</span>';
                        ?>
                    </td>
                </tr>
                <?php
                } ?>-->
                <tr>
                    <th align="left" width="200" style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:12px 0px 0px 0px;  padding-top:10px; -moz-border-radius:12px 0px 0px 0px; -webkit-border-radius:12px 0px 0px 0px;"><?php //echo __('SLA Plan');?>Codice commessa:</th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo $codice_commessa; ?></td>
                </tr>
                <?php
                if($ticket->isOpen()){ ?>
                <tr>
                    <th align="left" style="background:#ffffff; border-right: 1px solid #ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:0px 0px 0px 12px;  -moz-border-radius:0px 0px 0px 12px; -webkit-border-radius:0px 0px 0px 12px;"><?php //echo __('Due Date');?>Nome commessa:</th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo $nome_commessa; ?></td>
                </tr>
                <?php
                }else { ?>
                <tr>
                    <th align="left" style="background:#ffffff; border-right: 1px solid #ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:0px 0px 0px 12px; -moz-border-radius:0px 0px 0px 12px; -webkit-border-radius:0px 0px 0px 12px;"><?php //echo __('Close Date');?>Nome commessa:</th>
                    <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo $nome_commessa; ?></td>
                </tr>
                <?php
                }
                ?>
            </table>
        </td>
        <td width="50%">
            <table cellspacing="0" cellpadding="4" width="100%" border="0" style="border-collapse: separate; border-spacing: 0px 0px; margin-top:5px; margin-bottom:0px; background:transparent; ">
                <tr>
                    <th width="200" align="left" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php //echo __('Help Topic');?>Tipologia commessa:</th>
                    <td style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:0px 12px 0px 0px;  padding-top:10px; -moz-border-radius:0px 12px 0px 0px; -webkit-border-radius:0px 12px 0px 0px;"><?php echo Format::htmlchars($ticket->getHelpTopic()); ?></td>
                </tr>
                <tr>
                    <th nowrap align="left" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php //echo __('Last Message');?>Descrizione commessa:</th>
                    <td style="background:#ffffff; border-right: 1px solid #ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:0px 0px 12px 0px; -moz-border-radius:0px 0px 12px 0px; -webkit-border-radius:0px 0px 12px 0px;"><?php echo $desc_commessa;?></td>
                </tr>
                <!--<tr>
                    <th nowrap align="left" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo __('Last Response');?>:</th>
                    <td style="background:#ffffff; border-right: 1px solid #ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:0px 0px 12px 0px; -moz-border-radius:0px 0px 12px 0px; -webkit-border-radius:0px 0px 12px 0px;"><?php echo Format::db_datetime($ticket->getLastRespDate()); ?></td>
                </tr>-->
            </table>
        </td>
    </tr>
</table>
</div>
<br>
<!--dettaglio ticket -->
<?php
$randomico=rand(0,1000000000);
$_SESSION['dett_'.$randomico]=rand(0,1000000000);?>
<table border="0" width="100%"  style="border-collapse: separate; border-spacing: 0px 0px; margin-top:0px; margin-bottom:0px; background:transparent; "><tr><td align="left" style="background:#ffffff; border-radius:12px 0px 0px 12px;  padding-top:10px; -moz-border-radius:12px 0px 0px 12px; -webkit-border-radius:12px 0px 0px 12px; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><h2 style="color:#82AA63; margin-bottom:10px;">&nbsp;Dettaglio ticket </h2></td><td align="right" style="background:#ffffff; border-radius:0px 12px 12px 0px;  padding-top:10px; -moz-border-radius:0px 12px 12px 0px; -webkit-border-radius:0px 12px 12px 0px; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><a style="float:right; margin-bottom:10px;" id="link-<?php echo $_SESSION['dett_'.$randomico]; ?>" onclick="mostranascondi('eventbody-<?php echo $_SESSION['dett_'.$randomico]; ?>','link-<?php echo $_SESSION['dett_'.$randomico]; ?>');"><img src="../images/down.png"></a></td></tr></table>
<div id="eventbody-<?php
                echo $_SESSION['dett_'.$randomico];?>" style="display:none;">
<table cellspacing="0" cellpadding="0" width="100%" border="0"><!--width="940"-->
<?php
$idx = 0;
foreach (DynamicFormEntry::forTicket($ticket->getId()) as $form) {
    // Skip core fields shown earlier in the ticket view
    // TODO: Rewrite getAnswers() so that one could write
    //       ->getAnswers()->filter(not(array('field__name__in'=>
    //           array('email', ...))));
    $answers = array_filter($form->getAnswers(), function ($a) {
        return !in_array($a->getField()->get('name'),
                array('email','subject','name','priority'));
        });
    if (count($answers) == 0)
        continue;
    ?>   
        <tr>
        <td colspan="2">
            <table cellspacing="0" cellpadding="4" width="100%" border="0" style="border-collapse: separate; border-spacing: 0px 0px; margin-top:5px; margin-bottom:0px; background:transparent; ">
            <tr><th width="200" style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:12px 0px 0px 0px;  padding-top:10px; -moz-border-radius:12px 0px 0px 0px; -webkit-border-radius:12px 0px 0px 0px;">&nbsp;</th>
            <th style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"></th>
			<th style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"></th>	
            <th style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:0px 12px 0px 0px;  padding-top:10px; -moz-border-radius:0px 12px 0px 0px; -webkit-border-radius:0px 12px 0px 0px;">&nbsp;</th>
            </tr>
            <?php
                $k=$j=0;
                echo "<tr>";
                foreach($answers as $a) {
                if (!($v = $a->display())) continue;
                if ($a->getField()->get('label')!="Stato del Problem" 
                and $a->getField()->get('label')!="Identificativo SDM" 
                and $a->getField()->get('label')!="Tipo ticket"
                and $a->getField()->get('label')!="Data Previsto Intervento"
                and $a->getField()->get('label')!="Guasto Riscontrato"
                and $a->getField()->get('label')!="Intervento"
                and $a->getField()->get('label')!="Commessa"
                and $a->getField()->get('label')!="Ricambi Sostituiti"){
                $j++;
                
                ?>
            <td width="25%" align="left" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">
			<?php
                echo "<strong>".$a->getField()->get('label')."</strong>";
            ?>:</td>
            <td width="25%" style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">
		    <?php
		        if ($a->getField()->get('label')=="attivo S/N"){
                $v == 0 ? print ("N") : print ("S");
                //}elseif($a->getField()->get('label')=="Stato del Problem"){
				//echo "";	
                }else{
                echo $v;
                } 
                if($j == 2) { 
                echo '</tr><tr>';
                $j = 0;
            }?>
            </td>
              <?php }else{continue;}?>  
            <?php $k++;} if ($k % 2 != 0) echo '<td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"></td><td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"></td></tr>';?>
            <tr><td style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:0px 0px 0px 12px;  padding-top:10px; -moz-border-radius:0px 0px 0px 12px; -webkit-border-radius:0px 0px 0px 12px;">&nbsp;</td>
            <td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"></td>
			<td style="background:#ffffff;  -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"></td>
            <td style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:0px 0px 12px 0px;  padding-top:10px; -moz-border-radius:0px 0px 12px 0px; -webkit-border-radius:0px 0px 12px 0px;">&nbsp;</td>
            </tr>
            </table>
        </td>
        </tr>
    <?php
    $idx++;
    } ?>
</table>
</div>
<div class="clear"></div>
<!--<h2 style="padding:10px 0 5px 0; font-size:11pt;"><?php echo Format::htmlchars($ticket->getSubject()); ?></h2>summary del ticket-->
<?php
$tcount = $ticket->getThreadCount();
$tcount+= $ticket->getNumNotes();
?><!--
<ul id="threads">
    <li><a class="active" id="toggle_ticket_thread" href="#"><?php echo sprintf(__('Ticket Thread (%d)'), $tcount); ?></a></li>
</ul> altre crap-->
<table border="0" width="100%"  style="border-collapse: separate; border-spacing: 0px 0px; margin-top:18px; margin-bottom:0px; background:transparent; "><tr><td align="left" style="background:#ffffff; border-radius:12px 0px 0px 12px;  padding-top:10px; -moz-border-radius:12px 0px 0px 12px; -webkit-border-radius:12px 0px 0px 12px; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><h2 style="color:#82AA63; margin-bottom:10px;">&nbsp;<?php echo $thisstaff->getDeptId()==18?'Allegati':'Ticket tracking';?> </h2></td><td align="right" style="background:#ffffff; border-radius:0px 12px 12px 0px;  padding-top:10px; -moz-border-radius:0px 12px 12px 0px; -webkit-border-radius:0px 12px 12px 0px; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><a style="float:right; margin-bottom:10px;" id="link-<?php echo $entry['id']; ?>" onclick="mostranascondi('ticket_thread-<?php echo $entry['id']; ?>','link-<?php echo $entry['id']; ?>');"><img src="../images/down.png"></a></td></tr></table>

<div id="ticket_thread-<?php echo $entry['id']; ?>" style="display:block;">
    <?php
    $threadTypes=array('M'=>'message','R'=>'response', 'N'=>'note');
    /* -------- Messages & Responses & Notes (if inline)-------------*/
    
    //if($thisstaff->getDeptId()==18) {
     //$types = array('A'); //da implementare
     //}else{
     $types = array('M', 'R', 'N');
     //}
    if(($thread=$ticket->getThreadEntries($types))) {
		echo '<table border="0" width="100%"  style="border-collapse: separate; border-spacing: 0px 0px; margin-top:5px; margin-bottom:0px; background:transparent; ">
        <tr><td style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:12px 0px 0px 0px;  padding-top:10px; -moz-border-radius:12px 0px 0px 0px; -webkit-border-radius:12px 0px 0px 0px;">&nbsp;</td>
            <td style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:0px 12px 0px 0px;  padding-top:10px; -moz-border-radius:0px 12px 0px 0px; -webkit-border-radius:0px 12px 0px 0px;">&nbsp;</td>
            </tr>
        </table>';
       foreach($thread as $entry) { 
		   if($thisstaff->getDeptId()!=18 or ($thisstaff->getDeptId()==18 AND ($entry['attachments']
                    && ($tentry = $ticket->getThreadEntry($entry['id']))
                    && ($urls = $tentry->getAttachmentUrls())
                    && ($links = $tentry->getAttachmentsLinks())))) {?>
        <table class="thread-entry <?php echo $threadTypes[$entry['thread_type']]; ?>" cellspacing="0" cellpadding="1" width="100%" border="0"><!--width="940"-->
            <tr>
                <th colspan="4" width="100%" style="background:#EEF3F8; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">
                <div>
                    <span class="pull-left">
                    <span style="display:inline-block"><?php
                        echo Format::db_datetime($entry['created']);?></span>
                    <span style="display:inline-block;padding:0 1em"><?php
                        echo Format::truncate($entry['title'], 100); ?></span>
                    </span>
                    <span class="pull-right" style="white-space:no-wrap;display:inline-block">
                        <span style="vertical-align:middle;" class="textra"></span>
                        <span style="vertical-align:middle;"><?php
                            echo Format::htmlchars($entry['name'] ?: $entry['poster']); ?></span>
                    </span>
                </div>
                </th>
            </tr>
            <tr><td colspan="4" class="thread-body" id="thread-id-<?php
                echo $entry['id']; ?>" style=" -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><div><?php
                echo $entry['body']->toHtml(); ?></div></td></tr>
            <?php
            if($entry['attachments']
                    && ($tentry = $ticket->getThreadEntry($entry['id']))
                    && ($urls = $tentry->getAttachmentUrls())
                    && ($links = $tentry->getAttachmentsLinks())) {?>
            <tr>
                <td class="thread-body" colspan="4" style=" -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><?php echo $tentry->getAttachmentsLinks(); ?></td>
            </tr> <?php
            }
            if ($urls) { ?>
                <script type="text/javascript">
                    $('#thread-id-<?php echo $entry['id']; ?>')
                        .data('urls', <?php
                            echo JsonDataEncoder::encode($urls); ?>)
                        .data('id', <?php echo $entry['id']; ?>);
                </script>
<?php
            } ?>
        </table>
        <?php
        if($entry['thread_type']=='M')
            $msgId=$entry['id'];
            
       }
       }
       echo '<table border="0" width="100%"  style="border-collapse: separate; border-spacing: 0px 0px; margin-top:0px; margin-bottom:0px; background:transparent; ">
        <tr><td style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:0px 0px 0px 12px;  padding-top:10px; -moz-border-radius:0px 0px 0px 12px; -webkit-border-radius:0px 0px 0px 12px;">&nbsp;</td>
            <td style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black; border-radius:0px 0px 12px 0px;  padding-top:10px; -moz-border-radius:0px 0px 12px 0px; -webkit-border-radius:0px 0px 12px 0px;">&nbsp;</td>
            </tr>
        </table>'; 
    } else {
        echo $thisstaff->getDeptId()==18?'':'<p>'.__('Error fetching ticket thread - get technical help.').'</p>';
    }?>
</div>
<div class="clear" style="padding-bottom:10px;"></div>
<?php if($errors['err']) { ?>
    <div id="msg_error"><?php echo $errors['err']; ?></div>
<?php }elseif($msg) { ?>
    <div id="msg_notice"><?php echo $msg; ?></div>
<?php }elseif($warn) { ?>
    <!--<div id="msg_warning" style="width:80%;"><?php echo $warn; ?></div>questo div gestisce gli avvisi-->
<?php } ?>
</div></div>
</td></tr><tr><td width="100%"> <!-- tabella 4 celle continua-->

<div style="width: 98%;">
  <div style="width:98%;padding:2%;
    height:auto;
	margin-right:auto;
	margin-left:auto;
	margin-bottom:10px;
	padding-bottom:30px;
	border: 0px solid #ccc;
	border-radius: 12px;
	background:#ccc;
			-moz-box-shadow:    inset 0 0 20px #000000;
   -webkit-box-shadow: inset 0 0 20px #000000;
   box-shadow:         inset 0 0 20px #000000; display:none;" id="opzioni_risposta">
<div style="width:100%;">
    <table>
    <!--<tr>        
		        <td width="120">
                    <label><strong>Stato interno (attuale):</strong></label>
                </td>
                <td>
                </td>
       </tr>-->
              
               <tr>         
                <td width="120">
                    <label><strong>Stato interno:</strong></label>
                </td>
                <td>
                    <select name="reply_status_id" id="reply_status_id">
                    <?php
                    $statusId = $info['reply_status_id'] ?: $ticket->getStatusId();
                    $states = array('open');
                    if ($thisstaff->canCloseTickets())
                        $states = array_merge($states, array('closed'));
                    
                    if ($thisstaff->getDeptId()==9 || $thisstaff->getDeptId()==7 || $thisstaff->getDeptId()==11){
						echo '<option value="" selected="selected">Assegnato (corrente)</option>';
						
						}
                    foreach (TicketStatusList::getStatuses(
                                array('states' => $states)) as $s) {
                        if (!$s->isEnabled()) continue;
                        $selected = ($statusId == $s->getId());
                        //stampa gli stati a seconda di chi
                        if ($thisstaff->getDeptId()==7){ //tecnici
						//if ($s->getId()==17 || $s->getId()==18){ 9 settembre ernesto dice che i tecnici non sono di lab ma devono essere considerati come partner (cambio if)
						if ($s->getId()==2 || $s->getId()==9){
						echo sprintf('<option value="%d" %s>%s%s</option>',
                                $s->getId(),
                                $selected
                                 ? 'selected="selected"' : '',
                                __($s->getName()),
                                $selected
                                ? (' ('.__('current').')') : ''
                                );
						}
						}
						
						if ($thisstaff->getId()==5){ //resp di laboratorio
						if ($s->getId()==19 || $s->getId()==13 || $s->getId()==20 || $s->getId()==14 || $s->getId()==23){
						echo sprintf('<option value="%d" %s>%s%s</option>',
                                $s->getId(),
                                $selected
                                 ? 'selected="selected"' : '',
                                __($s->getName()),
                                $selected
                                ? (' ('.__('current').')') : ''
                                );
						}
						}
						
						if ($thisstaff->getDeptId()==9 || $thisstaff->getDeptId()==11){ //partner or solari
						if ($s->getId()==2 || $s->getId()==9 || $s->getId()==21){
						//if ($s->getId()==21)
						//$s->getName()="Sospensione";
						echo sprintf('<option value="%d" %s>%s%s</option>',
                                $s->getId(),
                                $selected
                                 ? 'selected="selected"' : '',
                                __($s->getName()),
                                $selected
                                ? (' ('.__('current').')') : ''
                                );
						}
						}
						
						if ($thisstaff->getDeptId()==8){ //magazzino
						if ($s->getId()==13 || $s->getId()==15 || $s->getId()==12){
						echo sprintf('<option value="%d" %s>%s%s</option>',
                                $s->getId(),
                                $selected
                                 ? 'selected="selected"' : '',
                                __($s->getName()),
                                $selected
                                ? (' ('.__('current').')') : ''
                                );
						}
						}
						
						if ($thisstaff->getDeptId()==6){ //planner
						if ( $selected || $s->getId()==2 || $s->getId()==11 || $s->getId()==12 || $s->getId()==21 || $s->getId()==23 || $s->getId()==22){
						if ($selected==$s->getId()){
							$selezione=$s->getName()." (corrente)";
						echo sprintf('<option selected="true" style="display:none;">%s%s</option>',
                                __($s->getName()),
                                $selected
                                ? (' ('.__('current').')') : ''
                                );
                        }else{	
                        echo sprintf('<option value="%s" %s>%s%s</option>',
                                $s->getId(),
                                $selected
                                 ? 'selected="selected"' : '',
                                __($s->getName()),
                                $selected
                                ? (' ('.__('current').')') : ''
                                );
                        }        
						}
						}
						
						if ($thisstaff->getId()==1){ //admin
						
						echo sprintf('<option value="%d" %s>%s%s</option>',
                                $s->getId(),
                                $selected
                                 ? 'selected="selected"' : '',
                                __($s->getName()),
                                $selected
                                ? (' ('.__('current').')') : ''
                                );
						
						}
						
		
                    }
                    ?>
                    </select>
                    <?php 
                   
                    if ($thisstaff->getDeptId()==6){
						
			
						?>
                    <label><strong>&nbsp;&nbsp;&nbsp;Stato cliente:</strong></label>
                    <select name="stato_cliente" id="stato_cliente">
		<option selected="true" style="display:none;"><?php echo $stato_cliente." (corrente)";?></option>				
		<option value="50">Chiuso da manutentore</option>
		<?php  if ($stato_cliente!="Sospeso da Manutentore"){ ?><option value="51">Sospeso da manutentore</option><?php } ?><!-- solo se lo stato cliente è != sospeso-->
		<?php  if ($stato_cliente=="Sospeso da Manutentore"){ ?><option value="52">Ripreso da manutentore</option><?php } ?><!-- solo se lo stato cliente è sospeso-->
	</select>
	<input Type="button" value="Annulla" onClick="history.go(0)">
	                <?php } ?>
                </td>
                
            </tr>
          
    </table>

   
    <div id="assegnato" style="display: none;">
    <?php

    if($thisstaff->canAssignTickets()) { ?>
    <form id="assign" action="tickets.php?id=<?php echo $ticket->getId(); ?>#assign" name="assign" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="a" value="assign">
        <table style="width:100%" border="0" cellspacing="0" cellpadding="3">

            <?php
            if($errors['assign']) {
                ?>
            <tr>
                <td width="120">&nbsp;</td>
                <td class="error"><?php echo $errors['assign']; ?></td>
            </tr>
            <?php
            } ?>
            <tr>
                <td width="120" style="vertical-align:top">
                    <label for="assignId"><strong><?php echo __('Assignee');?>:</strong></label>
                </td>
                <td>
                    <select id="assignId" name="assignId">
                        <option value="0" selected="selected">&mdash; Selezionare un operatore<?php //echo __('Select an Agent OR a Team');?> &mdash;</option>
                        <?php
                        if ($ticket->isOpen()
                                && !$ticket->isAssigned()
                                && $ticket->getDept()->isMember($thisstaff))
                            echo sprintf('<option value="%d">'.__('Claim Ticket (comments optional)').'</option>', $thisstaff->getId());

                        $sid=$tid=0;

                        if ($dept->assignMembersOnly())
                            $users = $dept->getAvailableMembers();
                        else
                            $users = Staff::getAvailableStaffMembers();
                            

                        if ($users) {
                            //echo '<OPTGROUP label="'.sprintf(__('Agents (%d)'), count($users)).'">';
                            echo '<OPTGROUP label="Operatori">';
                            $staffId=$ticket->isAssigned()?$ticket->getStaffId():0;
                            foreach($users as $id => $name) {
                                if($staffId && $staffId==$id)
                                    continue;

                                if (!is_object($name))
                                    $name = new PersonsName($name);

                                $k="s$id";
                                
                                
                                if ($thisstaff->getId()==5) {//resp. laboratorio
									if ($name=="tecnico1 tecnico1") {
                                        echo sprintf('<option value="%s" %s>%s</option>',
                                        $k,(($info['assignId']==$k)?'selected="selected"':''), $name);
                                        }        
                                }
                                
                                if ($thisstaff->getDeptId()==4 || $thisstaff->getDeptId()==5 || $thisstaff->getDeptId()==6) {//screener, admin, planner
									if ($name!="tecnico1 tecnico1") {
                                        echo sprintf('<option value="%s" %s>%s</option>',
                                        $k,(($info['assignId']==$k)?'selected="selected"':''), $name);
                                      }        
                                }
                            
                            }
                            echo '</OPTGROUP>';
                        }


                        ?>
                    </select>&nbsp;<span class='error'>*&nbsp;<?php echo $errors['assignId']; ?></span>
                    <?php
                    if ($ticket->isAssigned() && $ticket->isOpen()) { ?>
                        <div class="faded"><?php echo sprintf(__('Ticket is currently assigned to %s'),
                            sprintf('<b>%s</b>', $ticket->getAssignee())); ?></div> <?php
                    } elseif ($ticket->isClosed()) { ?>
                        <div class="faded"><?php echo __('Assigning a closed ticket will <b>reopen</b> it!'); ?></div>
                    <?php } ?>
                </td>
            </tr>
            <tr>
            <td width="120" style="vertical-align:top">
                <label><strong><?php echo "Data previsto intervento"; ?>: </strong></label>  
                </td>
                <td style="vertical-align:top">
                <?php echo "(".date('d/m/Y H:i:s',$data_previsto_intervento).") ";?>
        <input type="text" class="dp input-medium search-query"
            name="data_prev_inter_tec" placeholder="Giorno"/>
          &nbsp;&nbsp;ora:
               
                    
                     <select id="ora" name="ora_prev_inter_tec">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        
                        
                        
                    </select>
                    &nbsp;minuti
                    <select id="minuti" name="min_prev_inter_tec">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        <option value="24">24</option>
                        <option value="25">25</option>
                        <option value="26">26</option>
                        <option value="27">27</option>
                        <option value="28">28</option>
                        <option value="29">29</option>
                        <option value="30">30</option>
                        <option value="31">31</option>
                        <option value="32">32</option>
                        <option value="33">33</option>
                        <option value="34">34</option>
                        <option value="35">35</option>
                        <option value="36">36</option>
                        <option value="37">37</option>
                        <option value="38">38</option>
                        <option value="39">39</option>
                        <option value="40">40</option>
                        <option value="41">41</option>
                        <option value="42">42</option>
                        <option value="43">43</option>
                        <option value="44">44</option>
                        <option value="45">45</option>
                        <option value="46">46</option>
                        <option value="47">47</option>
                        <option value="48">48</option>
                        <option value="49">49</option>
                        <option value="50">50</option>
                        <option value="51">51</option>
                        <option value="52">52</option>
                        <option value="53">53</option>
                        <option value="54">54</option>
                        <option value="55">55</option>
                        <option value="56">56</option>
                        <option value="57">57</option>
                        <option value="58">58</option>
                        <option value="59">59</option>
                    </select>
                   
                </td>
            </tr>
            <tr>
                <td width="120" style="vertical-align:top">
                    <label><strong>Note di Screening:</strong><span class='error'>&nbsp;</span></label>
                </td>
                <td>
                    <textarea name="assign_comments" id="assign_comments"
                        cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo __('Enter reasons for the assignment or instructions for assignee'); ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['assign_comments']; ?></textarea>
                    <span class="error"><?php echo $errors['assign_comments']; ?></span><br>
                </td>
            </tr>
        </table>
        <p  style="padding-left:165px;">
            <input class="btn_sm" type="submit" value="Assegna">
            <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
        </p>
    </form>
    <?php
    } ?>
    </div>
<!-- INIZIO ERICSSON -->
  <!--inizio sospensione -->
  
    <div id="attesacliente" style="display: none;">
   <form id="sospensione" action="tickets.php?id=<?php echo $ticket->getId(); ?>#sospensione" name="sospensione" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="locktime" value="<?php echo $cfg->getLockTime(); ?>">
        <input type="hidden" name="a" value="sospensione">
        <input type="hidden" name="inizio_sospensione" value="<?php echo date('Y-m-d H:i:s'); ?>">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
         <tr>
            <td width="120" style="vertical-align:top">
                <label><strong><?php echo "Data contatto utente"; ?>: </strong></label>  
                </td>
                <td style="vertical-align:top">
                
        <input type="text" class="dp input-medium search-query"
            name="zz_dt_callagt" placeholder="Giorno"/>
    &nbsp;&nbsp;ora:
               
                    
                     <select id="ora" name="ora_iniziale">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        
                        
                        
                    </select>
                    &nbsp;minuti
                    <select id="minuti" name="minuti_iniziale">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        <option value="24">24</option>
                        <option value="25">25</option>
                        <option value="26">26</option>
                        <option value="27">27</option>
                        <option value="28">28</option>
                        <option value="29">29</option>
                        <option value="30">30</option>
                        <option value="31">31</option>
                        <option value="32">32</option>
                        <option value="33">33</option>
                        <option value="34">34</option>
                        <option value="35">35</option>
                        <option value="36">36</option>
                        <option value="37">37</option>
                        <option value="38">38</option>
                        <option value="39">39</option>
                        <option value="40">40</option>
                        <option value="41">41</option>
                        <option value="42">42</option>
                        <option value="43">43</option>
                        <option value="44">44</option>
                        <option value="45">45</option>
                        <option value="46">46</option>
                        <option value="47">47</option>
                        <option value="48">48</option>
                        <option value="49">49</option>
                        <option value="50">50</option>
                        <option value="51">51</option>
                        <option value="52">52</option>
                        <option value="53">53</option>
                        <option value="54">54</option>
                        <option value="55">55</option>
                        <option value="56">56</option>
                        <option value="57">57</option>
                        <option value="58">58</option>
                        <option value="59">59</option>
                    </select>
                    
                </td>
            </tr>
            <tr>
			<td width="120" style="vertical-align:top"><label><strong>
<?php
            echo "Data appuntamento"; ?>:</strong></label>
                </td>	
            <td style="vertical-align:top">
                  <label>
        
        <input type="text" class="dp input-medium search-query"
            name="zz_dt_recall" placeholder="Giorno"/>
    &nbsp;&nbsp;ora:
       
                    <select id="ora" name="ora_finale">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        
                        
                        
                    </select>
                    &nbsp;minuti
                    <select id="minuti" name="minuti_finale">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        <option value="24">24</option>
                        <option value="25">25</option>
                        <option value="26">26</option>
                        <option value="27">27</option>
                        <option value="28">28</option>
                        <option value="29">29</option>
                        <option value="30">30</option>
                        <option value="31">31</option>
                        <option value="32">32</option>
                        <option value="33">33</option>
                        <option value="34">34</option>
                        <option value="35">35</option>
                        <option value="36">36</option>
                        <option value="37">37</option>
                        <option value="38">38</option>
                        <option value="39">39</option>
                        <option value="40">40</option>
                        <option value="41">41</option>
                        <option value="42">42</option>
                        <option value="43">43</option>
                        <option value="44">44</option>
                        <option value="45">45</option>
                        <option value="46">46</option>
                        <option value="47">47</option>
                        <option value="48">48</option>
                        <option value="49">49</option>
                        <option value="50">50</option>
                        <option value="51">51</option>
                        <option value="52">52</option>
                        <option value="53">53</option>
                        <option value="54">54</option>
                        <option value="55">55</option>
                        <option value="56">56</option>
                        <option value="57">57</option>
                        <option value="58">58</option>
                        <option value="59">59</option>
                        
                    </select>
                    
                </td>
            </tr>
            <tr>
                <td width="120" style="vertical-align:top">
                    <label><strong>Motivo sospensione:</strong><span class='error'>&nbsp;*</span></label>
                </td>
                
                <td>
                    <textarea name="msg_sospeso" id="msg_sospeso"
                        cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo "Motivo sospensione (obbligatorio)"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['sos']; ?></textarea>
                    <span class="error"><?php echo $errors['sos']; ?></span><br>
                </td>
            </tr>
            <tr><td colspan="2">&nbsp;</td></tr>
            
        </table>

       <p  style="padding-left:165px;">
            <input class="btn_sm" type="submit" value="Sospendi">
            <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
        </p>
   </form>
   
   </div>
   
   
  <div id="sospensione_soap" style="display: none;">
   <form id="sospensione" action="tickets.php?id=<?php echo $ticket->getId(); ?>#sospensione" name="sospensione" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="locktime" value="<?php echo $cfg->getLockTime(); ?>">
        <input type="hidden" name="a" value="sospensione">
        <input type="hidden" name="inizio_sospensione" value="<?php echo date('Y-m-d H:i:s'); ?>">
        <input type="hidden" name="stato_cliente" value="51">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
         <tr>
            <td width="120" style="vertical-align:top">
                <label><strong><?php echo "Data contatto utente"; ?>: </strong></label>  
                </td>
                <td style="vertical-align:top">
                
        <input type="text" class="dp input-medium search-query"
            name="zz_dt_callagt" placeholder="Giorno"/>
    &nbsp;&nbsp;ora:
               
                    
                     <select id="ora" name="ora_iniziale">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        
                        
                        
                    </select>
                    &nbsp;minuti
                    <select id="minuti" name="minuti_iniziale">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        <option value="24">24</option>
                        <option value="25">25</option>
                        <option value="26">26</option>
                        <option value="27">27</option>
                        <option value="28">28</option>
                        <option value="29">29</option>
                        <option value="30">30</option>
                        <option value="31">31</option>
                        <option value="32">32</option>
                        <option value="33">33</option>
                        <option value="34">34</option>
                        <option value="35">35</option>
                        <option value="36">36</option>
                        <option value="37">37</option>
                        <option value="38">38</option>
                        <option value="39">39</option>
                        <option value="40">40</option>
                        <option value="41">41</option>
                        <option value="42">42</option>
                        <option value="43">43</option>
                        <option value="44">44</option>
                        <option value="45">45</option>
                        <option value="46">46</option>
                        <option value="47">47</option>
                        <option value="48">48</option>
                        <option value="49">49</option>
                        <option value="50">50</option>
                        <option value="51">51</option>
                        <option value="52">52</option>
                        <option value="53">53</option>
                        <option value="54">54</option>
                        <option value="55">55</option>
                        <option value="56">56</option>
                        <option value="57">57</option>
                        <option value="58">58</option>
                        <option value="59">59</option>
                    </select>
                    
                </td>
            </tr>
            <tr>
			<td width="120" style="vertical-align:top"><label><strong>
<?php
            echo "Data appuntamento"; ?>:</strong></label>
                </td>	
            <td style="vertical-align:top">
                  <label>
        
        <input type="text" class="dp input-medium search-query"
            name="zz_dt_recall" placeholder="Giorno"/>
    &nbsp;&nbsp;ora:
       
                    <select id="ora" name="ora_finale">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        
                        
                        
                    </select>
                    &nbsp;minuti
                    <select id="minuti" name="minuti_finale">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        <option value="24">24</option>
                        <option value="25">25</option>
                        <option value="26">26</option>
                        <option value="27">27</option>
                        <option value="28">28</option>
                        <option value="29">29</option>
                        <option value="30">30</option>
                        <option value="31">31</option>
                        <option value="32">32</option>
                        <option value="33">33</option>
                        <option value="34">34</option>
                        <option value="35">35</option>
                        <option value="36">36</option>
                        <option value="37">37</option>
                        <option value="38">38</option>
                        <option value="39">39</option>
                        <option value="40">40</option>
                        <option value="41">41</option>
                        <option value="42">42</option>
                        <option value="43">43</option>
                        <option value="44">44</option>
                        <option value="45">45</option>
                        <option value="46">46</option>
                        <option value="47">47</option>
                        <option value="48">48</option>
                        <option value="49">49</option>
                        <option value="50">50</option>
                        <option value="51">51</option>
                        <option value="52">52</option>
                        <option value="53">53</option>
                        <option value="54">54</option>
                        <option value="55">55</option>
                        <option value="56">56</option>
                        <option value="57">57</option>
                        <option value="58">58</option>
                        <option value="59">59</option>
                        
                    </select>
                    
                </td>
            </tr>
            <tr>
                <td width="120" style="vertical-align:top">
                    <label><strong>Motivo sospensione:</strong><span class='error'>&nbsp;*</span></label>
                </td>
                
                <td>
                    <textarea name="msg_sospeso" id="msg_sospeso"
                        cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo "Motivo sospensione (obbligatorio)"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['sos']; ?></textarea>
                    <span class="error"><?php echo $errors['sos']; ?></span><br>
                </td>
            </tr>
            <tr><td colspan="2">&nbsp;</td></tr>
            
        </table>

       <p  style="padding-left:165px;">
            <input class="btn_sm" type="submit" value="Sospendi">
            <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
        </p>
   </form>
   
   </div>
   <!-- fine sospensione-->
   
   <!--inizio ripresa -->
   
   <div id="ripresa" style="display: none;">
   <form id="ripresaattivita" action="tickets.php?id=<?php echo $ticket->getId(); ?>#ripresaattivita" name="ripresaattivita" method="post" enctype="multipart/form-data">
   <?php csrf_token(); ?>
   <table width="100%" border="0" cellspacing="0" cellpadding="3">
            
            <tr>
				<td width="120" style="vertical-align:top">
                <label><strong><?php echo "Data ripresa"; ?>: </strong></label>  
                </td>
                <td style="vertical-align:top">
        <input type="text" class="dp input-medium search-query"
            name="start_ripresa" placeholder="Giorno"/>
              &nbsp;&nbsp;ora:
                     <select id="ora_rip" name="ora_ripresa">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        
                        
                        
                    </select>
                    &nbsp;minuti
                    <select id="minuti_rip" name="minuti_ripresa">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        <option value="24">24</option>
                        <option value="25">25</option>
                        <option value="26">26</option>
                        <option value="27">27</option>
                        <option value="28">28</option>
                        <option value="29">29</option>
                        <option value="30">30</option>
                        <option value="31">31</option>
                        <option value="32">32</option>
                        <option value="33">33</option>
                        <option value="34">34</option>
                        <option value="35">35</option>
                        <option value="36">36</option>
                        <option value="37">37</option>
                        <option value="38">38</option>
                        <option value="39">39</option>
                        <option value="40">40</option>
                        <option value="41">41</option>
                        <option value="42">42</option>
                        <option value="43">43</option>
                        <option value="44">44</option>
                        <option value="45">45</option>
                        <option value="46">46</option>
                        <option value="47">47</option>
                        <option value="48">48</option>
                        <option value="49">49</option>
                        <option value="50">50</option>
                        <option value="51">51</option>
                        <option value="52">52</option>
                        <option value="53">53</option>
                        <option value="54">54</option>
                        <option value="55">55</option>
                        <option value="56">56</option>
                        <option value="57">57</option>
                        <option value="58">58</option>
                        <option value="59">59</option>
                        
                    </select>
                      
                    
                </td>
            </tr>
            
            
        </table>
       <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="locktime" value="<?php echo $cfg->getLockTime(); ?>">
        <input type="hidden" name="a" value="ripresaattivita">
        <p  style="padding-left:165px;">
            <input class="btn_sm" type="submit" value="Riprendi">
            <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
        </p>

    <br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;
</form>
</div>

<div id="ripresa_soap" style="display: none;">
   <form id="ripresaattivita" action="tickets.php?id=<?php echo $ticket->getId(); ?>#ripresaattivita" name="ripresaattivita" method="post" enctype="multipart/form-data">
   <?php csrf_token(); ?>
   <table width="100%" border="0" cellspacing="0" cellpadding="3">
            
            <tr>
				<td width="120" style="vertical-align:top">
                <label><strong><?php echo "Data ripresa"; ?>: </strong></label>  
                </td>
                <td style="vertical-align:top">
        <input type="text" class="dp input-medium search-query"
            name="start_ripresa" placeholder="Giorno"/>
              &nbsp;&nbsp;ora:
                     <select id="ora_rip" name="ora_ripresa">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        
                        
                        
                    </select>
                    &nbsp;minuti
                    <select id="minuti_rip" name="minuti_ripresa">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        <option value="24">24</option>
                        <option value="25">25</option>
                        <option value="26">26</option>
                        <option value="27">27</option>
                        <option value="28">28</option>
                        <option value="29">29</option>
                        <option value="30">30</option>
                        <option value="31">31</option>
                        <option value="32">32</option>
                        <option value="33">33</option>
                        <option value="34">34</option>
                        <option value="35">35</option>
                        <option value="36">36</option>
                        <option value="37">37</option>
                        <option value="38">38</option>
                        <option value="39">39</option>
                        <option value="40">40</option>
                        <option value="41">41</option>
                        <option value="42">42</option>
                        <option value="43">43</option>
                        <option value="44">44</option>
                        <option value="45">45</option>
                        <option value="46">46</option>
                        <option value="47">47</option>
                        <option value="48">48</option>
                        <option value="49">49</option>
                        <option value="50">50</option>
                        <option value="51">51</option>
                        <option value="52">52</option>
                        <option value="53">53</option>
                        <option value="54">54</option>
                        <option value="55">55</option>
                        <option value="56">56</option>
                        <option value="57">57</option>
                        <option value="58">58</option>
                        <option value="59">59</option>
                        
                    </select>
                      
                    
                </td>
            </tr>
            
            
        </table>
       <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="locktime" value="<?php echo $cfg->getLockTime(); ?>">
        <input type="hidden" name="a" value="ripresaattivita">
        <input type="hidden" name="stato_cliente" value="52">
        <p  style="padding-left:165px;">
            <input class="btn_sm" type="submit" value="Riprendi">
            <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
        </p>

    <br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;
</form>
</div>
   <!-- fine ripresa-->
   
   <!--inizio proposta chiusura -->
   
   <div id="risolto" style="display: none;">
   <form id="propostachiusura" action="tickets.php?id=<?php echo $ticket->getId(); ?>#propostachiusura" name="propostachiusura" method="post" enctype="multipart/form-data">
   <?php csrf_token(); ?>
   <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="locktime" value="<?php echo $cfg->getLockTime(); ?>">
        <input type="hidden" name="a" value="propostachiusura">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
            <tr>
            <td width="120" style="vertical-align:top">
                <label><strong><?php echo "Data partenza tecnico"; ?>: </strong><span class='error'>&nbsp;*</span></label>  
                </td>
                <td style="vertical-align:top">
                
        <input type="text" class="dp input-medium search-query"
            name="start_partenza" placeholder="Giorno"/>
                    <span>&nbsp;&nbsp;ora:</span>
               
                    
                     <select id="ora" name="ora_partenza">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        
                        
                        
                    </select>
                   <span> &nbsp;minuti</span>
                    <select id="minuti" name="minuti_partenza">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        <option value="24">24</option>
                        <option value="25">25</option>
                        <option value="26">26</option>
                        <option value="27">27</option>
                        <option value="28">28</option>
                        <option value="29">29</option>
                        <option value="30">30</option>
                        <option value="31">31</option>
                        <option value="32">32</option>
                        <option value="33">33</option>
                        <option value="34">34</option>
                        <option value="35">35</option>
                        <option value="36">36</option>
                        <option value="37">37</option>
                        <option value="38">38</option>
                        <option value="39">39</option>
                        <option value="40">40</option>
                        <option value="41">41</option>
                        <option value="42">42</option>
                        <option value="43">43</option>
                        <option value="44">44</option>
                        <option value="45">45</option>
                        <option value="46">46</option>
                        <option value="47">47</option>
                        <option value="48">48</option>
                        <option value="49">49</option>
                        <option value="50">50</option>
                        <option value="51">51</option>
                        <option value="52">52</option>
                        <option value="53">53</option>
                        <option value="54">54</option>
                        <option value="55">55</option>
                        <option value="56">56</option>
                        <option value="57">57</option>
                        <option value="58">58</option>
                        <option value="59">59</option>
                    </select>
                </td>
            </tr>
            <tr>
            <td width="120" style="vertical-align:top">
                <label><strong><?php echo "Data inizio intervento"; ?>: </strong><span class='error'>&nbsp;*</span></label>  
                </td>
                <td style="vertical-align:top">
                
        <input type="text" class="dp input-medium search-query"
            name="start_chiusura" placeholder="Giorno"/>
          &nbsp;&nbsp;ora:
               
                    
                     <select id="ora" name="ora_iniziale">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        
                        
                        
                    </select>
                    &nbsp;minuti
                    <select id="minuti" name="minuti_iniziale">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        <option value="24">24</option>
                        <option value="25">25</option>
                        <option value="26">26</option>
                        <option value="27">27</option>
                        <option value="28">28</option>
                        <option value="29">29</option>
                        <option value="30">30</option>
                        <option value="31">31</option>
                        <option value="32">32</option>
                        <option value="33">33</option>
                        <option value="34">34</option>
                        <option value="35">35</option>
                        <option value="36">36</option>
                        <option value="37">37</option>
                        <option value="38">38</option>
                        <option value="39">39</option>
                        <option value="40">40</option>
                        <option value="41">41</option>
                        <option value="42">42</option>
                        <option value="43">43</option>
                        <option value="44">44</option>
                        <option value="45">45</option>
                        <option value="46">46</option>
                        <option value="47">47</option>
                        <option value="48">48</option>
                        <option value="49">49</option>
                        <option value="50">50</option>
                        <option value="51">51</option>
                        <option value="52">52</option>
                        <option value="53">53</option>
                        <option value="54">54</option>
                        <option value="55">55</option>
                        <option value="56">56</option>
                        <option value="57">57</option>
                        <option value="58">58</option>
                        <option value="59">59</option>
                    </select>
                    
                </td>
            </tr>
            <tr>
			<td width="120" style="vertical-align:top">
                <label><strong><?php echo "Data fine intervento"; ?>: </strong><span class='error'>&nbsp;*</span></label>  
                </td>	
            <td style="vertical-align:top">
                  
        <input type="text" class="dp input-medium search-query"
            name="end_chiusura" placeholder="Giorno"/>
          &nbsp;&nbsp;ora:
       
                    <select id="ora" name="ora_finale">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        
                        
                        
                    </select>
                    &nbsp;minuti
                    <select id="minuti" name="minuti_finale">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        <option value="24">24</option>
                        <option value="25">25</option>
                        <option value="26">26</option>
                        <option value="27">27</option>
                        <option value="28">28</option>
                        <option value="29">29</option>
                        <option value="30">30</option>
                        <option value="31">31</option>
                        <option value="32">32</option>
                        <option value="33">33</option>
                        <option value="34">34</option>
                        <option value="35">35</option>
                        <option value="36">36</option>
                        <option value="37">37</option>
                        <option value="38">38</option>
                        <option value="39">39</option>
                        <option value="40">40</option>
                        <option value="41">41</option>
                        <option value="42">42</option>
                        <option value="43">43</option>
                        <option value="44">44</option>
                        <option value="45">45</option>
                        <option value="46">46</option>
                        <option value="47">47</option>
                        <option value="48">48</option>
                        <option value="49">49</option>
                        <option value="50">50</option>
                        <option value="51">51</option>
                        <option value="52">52</option>
                        <option value="53">53</option>
                        <option value="54">54</option>
                        <option value="55">55</option>
                        <option value="56">56</option>
                        <option value="57">57</option>
                        <option value="58">58</option>
                        <option value="59">59</option>
                        
                    </select>
                    
                </td>
            </tr>
            <tr>
				<td width="120" style="vertical-align:top">
                <label><strong><?php echo "Motivo della chiamata"; ?>: </strong><span class='error'>&nbsp;*</span></label>  
                </td>
                 <td>
                    <textarea name="guasto" id="guasto" cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo "Motivo della chiamata (obbligatorio)"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['chiusura']; ?></textarea>
                    <span class="error"><?php echo $errors['chiusura']; ?></span><br>
                </td>
             </tr>
             <tr>
             <td width="120" style="vertical-align:top">
                <label><strong><?php echo "Descrizione intervento"; ?>: </strong><span class='error'>&nbsp;*</span></label>  
                </td>          

                <td>
                    <textarea name="intervento" id="intervento" cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo "Descrizione intervento (obbligatorio)"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['chiusura']; ?></textarea>
                    <span class="error"><?php echo $errors['chiusura']; ?></span><br>
                <div class="attachments">
<?php
print $response_form->getField('attachments')->render();
?>
                    </div>
                
                </td>
                
                  
            </tr>
            <?php if ($thisstaff->getDeptId()==100){?>
            <tr>
				<td width="120" style="vertical-align:top">
                <label><strong><?php echo "Aggiungi asset"; ?>: </strong></label>  
                </td> 
                <td style="vertical-align:top">
                <a class="action-button pull-left confirm-action"  href="#ws"><i class="icon-plus icon-large"></i> Aggiungi asset</a>
                </td>
              </tr>
             <?php }?> 
            <tr>
				<td width="120" style="vertical-align:top">
                <label><strong><?php echo "Esito"; ?>: </strong><span class='error'>&nbsp;*</span></label>  
                </td> 
                <td style="vertical-align:top">
                    <select id="esito" name="esito_intervento">
                        <option value="Riparato">Riparato</option>
                        <option value="Sostituito">Sostituito</option>
                        <option value="ChiusodaRemoto">Chiuso da Remoto</option>
                        <option value="CausaPoste">Mancato intervento causa Poste</option>
                    </select>
                    </td>
              </tr>
              <!--
            <tr>
				<td width="120" style="vertical-align:top">
                <label><strong><?php echo "Analisi guasto"; ?>: </strong><span class='error'>&nbsp;*</span></label>  
                </td> 
                <td style="vertical-align:top">
                    <select id="analisi" name="analisi_guasto">
						<option value="" selected></option>
                        <option value="altro">Altro</option>
                        <option value="firmware">Dopo aggiornamento firmware</option>
                        <option value="maltempo">Eventi naturali</option>
                        <option value="vandalismo">Atti vandalici</option>
                        <option value="display_sportello_off">Display sportello off</option>
                        <option value="display_riepilogativo_off">Display riepilogativo off</option>
                        <option value="vetrina_digitale_off">Vetrina digitale off</option>
                    </select>
                    </td>
              </tr>   -->
               <tr>
				<td width="120" style="vertical-align:top">
                <label><strong><?php echo "Descrizione operazioni effettuate"; ?>: </strong></label>  
                </td>     
                
                <td>
                    <textarea name="riassunto" id="riassunto" cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo "Descrizione operazioni effettuate"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['chiusura']; ?></textarea>
                    <span class="error"><?php echo $errors['chiusura']; ?></span><br>
                </td>
                
            </tr>
            <tr>
				<td width="120" style="vertical-align:top">
                <label><strong><?php echo "Note manutentore"; ?>: </strong></label>  
                </td> 
                <td>
                    <textarea name="note_man" id="note_man" cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo "Note manutentore"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['chiusura']; ?></textarea>
                    <span class="error"><?php echo $errors['chiusura']; ?></span><br>
                </td>
                  
            </tr>
            <?php if ($thisstaff->getDeptId()==7){?>
            <tr>
				<td width="120" style="vertical-align:top">
                <label><span style="font-size:14px; font-family:play; font-weight:bold; color:black;"><?php echo "Aggiungi asset"; ?>: </span></label>  
                </td> 
                <td style="vertical-align:top">           
                <select name="search_category_eqGA"  id="search_category_id_eqGA">
		        <option value="" selected="selected"></option>
		        <option value="asset">Asset</option>
                </select>
                <div>
					<br>
		           <div id="show_sub_categories_eqGA">
			    <img src="http://fastdata2training.service-tech.org/ticket/include/staff/loader.gif" style="margin-top:8px; float:left" id="loader" alt="" />
		          </div>
                </div>	
                </td>
              </tr>
              <?php } ?>
            
            
        </table>
   
    
    <p  style="padding-left:165px;">
            <input class="btn_sm" type="submit" value="Risolvi">
            <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
        </p>
</form>
</div>

</div>




<!---@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@---->

<div id="lab_13" style="display: none;">
<form id="reply" action="tickets.php?id=<?php echo $ticket->getId(); ?>#reply" name="reply" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="msgId" value="<?php echo $msgId; ?>">
        <input type="hidden" name="a" value="reply">
        <input type="hidden" name="reply_status_id" value="13">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
            <tr>
                <td width="120" style="vertical-align:top">
                    <label><strong>Nota:</strong></label>
                </td>
                
                <td>
                    <textarea name="response" id="response"
                        cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo "Nota"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['sos']; ?></textarea>
                    <span class="error"><?php echo $errors['sos']; ?></span><br>
                </td>
            </tr>
                     
        </table>

       <p  style="padding-left:165px;">
            <input class="btn_sm" type="submit" value="Invia">
            <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
        </p>
   </form>
   
   </div>
<div id="lab_14" style="display: none;">
<form id="reply" action="tickets.php?id=<?php echo $ticket->getId(); ?>#reply" name="reply" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="msgId" value="<?php echo $msgId; ?>">
        <input type="hidden" name="a" value="reply">
        <input type="hidden" name="reply_status_id" value="14">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
            <tr>
                <td width="120" style="vertical-align:top">
                    <label><strong>Nota:</strong></label>
                </td>
                
                <td>
                    <textarea name="response" id="response"
                        cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo "Nota"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['sos']; ?></textarea>
                    <span class="error"><?php echo $errors['sos']; ?></span><br>
                </td>
            </tr>
                     
        </table>

       <p  style="padding-left:165px;">
            <input class="btn_sm" type="submit" value="Invia">
            <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
        </p>
   </form>
   
   </div>
<div id="lab_19" style="display: none;">
<form id="reply" action="tickets.php?id=<?php echo $ticket->getId(); ?>#reply" name="reply" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="msgId" value="<?php echo $msgId; ?>">
        <input type="hidden" name="a" value="reply">
        <input type="hidden" name="reply_status_id" value="19">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
            <tr>
                <td width="120" style="vertical-align:top">
                    <label><strong>Nota:</strong></label>
                </td>
                
                <td>
                    <textarea name="response" id="response"
                        cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo "Nota"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['sos']; ?></textarea>
                    <span class="error"><?php echo $errors['sos']; ?></span><br>
                </td>
            </tr>
                     
        </table>

       <p  style="padding-left:165px;">
            <input class="btn_sm" type="submit" value="Invia">
            <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
        </p>
   </form>
   
   </div>
   
<div id="lab_20" style="display: none;">
<form id="reply" action="tickets.php?id=<?php echo $ticket->getId(); ?>#reply" name="reply" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="msgId" value="<?php echo $msgId; ?>">
        <input type="hidden" name="a" value="reply">
        <input type="hidden" name="reply_status_id" value="20">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
            <tr>
                <td width="120" style="vertical-align:top">
                    <label><strong>Nota:</strong></label>
                </td>
                
                <td>
                    <textarea name="response" id="response"
                        cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo "Nota"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['sos']; ?></textarea>
                    <span class="error"><?php echo $errors['sos']; ?></span><br>
                </td>
            </tr>
                     
        </table>

       <p  style="padding-left:165px;">
            <input class="btn_sm" type="submit" value="Invia">
            <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
        </p>
   </form>
   
   </div>
   
<div id="tec_18" style="display: none;">
<form id="reply" action="tickets.php?id=<?php echo $ticket->getId(); ?>#reply" name="reply" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="msgId" value="<?php echo $msgId; ?>">
        <input type="hidden" name="a" value="reply">
        <input type="hidden" name="reply_status_id" value="18">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
            <tr>
                <td width="120" style="vertical-align:top">
                    <label><strong>Nota:</strong></label>
                </td>
                
                <td>
                    <textarea name="response" id="response"
                        cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo "Nota"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['sos']; ?></textarea>
                    <span class="error"><?php echo $errors['sos']; ?></span><br>
                </td>
            </tr>
                     
        </table>

       <p  style="padding-left:165px;">
            <input class="btn_sm" type="submit" value="Invia">
            <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
        </p>
   </form>
   
   </div>   

<div id="tec_17" style="display: none;">
<form id="reply" action="tickets.php?id=<?php echo $ticket->getId(); ?>#reply" name="reply" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="msgId" value="<?php echo $msgId; ?>">
        <input type="hidden" name="a" value="reply">
        <input type="hidden" name="reply_status_id" value="17">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
            <tr>
                <td width="120" style="vertical-align:top">
                    <label><strong>Nota:</strong></label>
                </td>
                
                <td>
                    <textarea name="response" id="response"
                        cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo "Nota"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['sos']; ?></textarea>
                    <span class="error"><?php echo $errors['sos']; ?></span><br>
                </td>
            </tr>
                     
        </table>

       <p  style="padding-left:165px;">
            <input class="btn_sm" type="submit" value="Invia">
            <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
        </p>
   </form>
   
   </div> 

<div id="mag_12" style="display: none;">
<form id="reply" action="tickets.php?id=<?php echo $ticket->getId(); ?>#reply" name="reply" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="msgId" value="<?php echo $msgId; ?>">
        <input type="hidden" name="a" value="reply">
        <input type="hidden" name="reply_status_id" value="12">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
            <tr>
                <td width="120" style="vertical-align:top">
                    <label><strong>Nota:</strong></label>
                </td>
                
                <td>
                    <textarea name="response" id="response"
                        cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo "Nota"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['sos']; ?></textarea>
                    <span class="error"><?php echo $errors['sos']; ?></span><br>
                </td>
            </tr>
                     
        </table>

       <p  style="padding-left:165px;">
            <input class="btn_sm" type="submit" value="Invia">
            <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
        </p>
   </form>
   
   </div> 
   
  <div id="mag_13" style="display: none;">
<form id="reply" action="tickets.php?id=<?php echo $ticket->getId(); ?>#reply" name="reply" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="msgId" value="<?php echo $msgId; ?>">
        <input type="hidden" name="a" value="reply">
        <input type="hidden" name="reply_status_id" value="13">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
            <tr>
                <td width="120" style="vertical-align:top">
                    <label><strong>Nota:</strong></label>
                </td>
                
                <td>
                    <textarea name="response" id="response"
                        cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo "Nota"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['sos']; ?></textarea>
                    <span class="error"><?php echo $errors['sos']; ?></span><br>
                </td>
            </tr>
                     
        </table>

       <p  style="padding-left:165px;">
            <input class="btn_sm" type="submit" value="Invia">
            <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
        </p>
   </form>
   
   </div> 
   
  <div id="mag_15" style="display: none;">
<form id="reply" action="tickets.php?id=<?php echo $ticket->getId(); ?>#reply" name="reply" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="msgId" value="<?php echo $msgId; ?>">
        <input type="hidden" name="a" value="reply">
        <input type="hidden" name="reply_status_id" value="15">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
            <tr>
                <td width="120" style="vertical-align:top">
                    <label><strong>Nota:</strong></label>
                </td>
                
                <td>
                    <textarea name="response" id="response"
                        cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo "Nota"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['sos']; ?></textarea>
                    <span class="error"><?php echo $errors['sos']; ?></span><br>
                </td>
            </tr>
                     
        </table>

       <p  style="padding-left:165px;">
            <input class="btn_sm" type="submit" value="Invia">
            <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
        </p>
   </form>
   
   </div>  
<div id="par_9" style="display: none;">
<form id="reply" action="tickets.php?id=<?php echo $ticket->getId(); ?>#reply" name="reply" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="msgId" value="<?php echo $msgId; ?>">
        <input type="hidden" name="a" value="reply">
        <input type="hidden" name="reply_status_id" value="9">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
            <tr>
                <td width="120" style="vertical-align:top">
                    <label><strong>Nota:</strong></label>
                </td>
                
                <td>
                    <textarea name="response" id="response"
                        cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo "Nota"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['sos']; ?></textarea>
                    <span class="error"><?php echo $errors['sos']; ?></span><br>
                </td>
            </tr>
                     
        </table>

       <p  style="padding-left:165px;">
            <input class="btn_sm" type="submit" value="Invia">
            <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
        </p>
   </form>
   
   </div> 
   <div id="magazzino" style="display: none;">
<form id="reply" action="tickets.php?id=<?php echo $ticket->getId(); ?>#reply" name="reply" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="msgId" value="<?php echo $msgId; ?>">
        <input type="hidden" name="a" value="reply">
        <input type="hidden" name="reply_status_id" value="11">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
            <tr>
                <td width="120" style="vertical-align:top">
                    <label><strong>Nota:</strong></label>
                </td>
                
                <td>
                    <textarea name="response" id="response"
                        cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo "Nota"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['sos']; ?></textarea>
                    <span class="error"><?php echo $errors['sos']; ?></span><br>
                </td>
            </tr>
                     
        </table>

       <p  style="padding-left:165px;">
            <input class="btn_sm" type="submit" value="Invia">
            <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
        </p>
   </form>
   
   </div>   
   
   <div id="laboratorio" style="display: none;">
<form id="reply" action="tickets.php?id=<?php echo $ticket->getId(); ?>#reply" name="reply" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="msgId" value="<?php echo $msgId; ?>">
        <input type="hidden" name="a" value="reply">
        <input type="hidden" name="reply_status_id" value="12">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
            <tr>
                <td width="120" style="vertical-align:top">
                    <label><strong>Nota:</strong></label>
                </td>
                
                <td>
                    <textarea name="response" id="response"
                        cols="80" rows="7" wrap="soft"
                        placeholder="<?php echo "Nota"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['sos']; ?></textarea>
                    <span class="error"><?php echo $errors['sos']; ?></span><br>
                </td>
            </tr>
                     
        </table>

       <p  style="padding-left:165px;">
            <input class="btn_sm" type="submit" value="Invia">
            <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
        </p>
   </form>
   
   </div>                                       
<!---@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@----> 
</div>
</div></div>
</td></tr></table><!--fine tabella 4 celle -->
<div style="display:none;" class="dialog" id="print-options">
    <h3><?php echo __('Ticket Print Options');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <form action="tickets.php?id=<?php echo $ticket->getId(); ?>" method="post" id="print-form" name="print-form">
        <?php csrf_token(); ?>
        <input type="hidden" name="a" value="print">
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <fieldset class="notes">
            <label class="fixed-size" for="notes"><?php echo __('Print Notes');?>:</label>
            <input type="checkbox" id="notes" name="notes" value="1"> <?php echo __('Print <b>Internal</b> Notes/Comments');?>
        </fieldset>
        <fieldset>
            <label class="fixed-size" for="psize"><?php echo __('Paper Size');?>:</label>
            <select id="psize" name="psize">
                <option value="">&mdash; <?php echo __('Select Print Paper Size');?> &mdash;</option>
                <?php
                  $psize =$_SESSION['PAPER_SIZE']?$_SESSION['PAPER_SIZE']:$thisstaff->getDefaultPaperSize();
                  foreach(Export::$paper_sizes as $v) {
                      echo sprintf('<option value="%s" %s>%s</option>',
                                $v,($psize==$v)?'selected="selected"':'', __($v));
                  }
                ?>
            </select>
        </fieldset>
        <hr style="margin-top:3em"/>
        <p class="full-width">
            <span class="buttons pull-left">
                <input type="reset" value="<?php echo __('Reset');?>">
                <input type="button" value="<?php echo __('Cancel');?>" class="close">
            </span>
            <span class="buttons pull-right">
                <input type="submit" value="<?php echo __('Print');?>">
            </span>
         </p>
    </form>
    <div class="clear"></div>
</div>

<div style="display:none; width:1380px" class="dialog" id="confirm-action">
    <h3>Gestisci asset</h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="ws-confirm"></p>
    <?php require_once(STAFFINC_DIR.'asset.inc1.php'); ?>
    <!--
    <form id="note" action="tickets.php?id=<?php echo $ticket->getId(); ?>#note" name="note" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="locktime" value="<?php echo $cfg->getLockTime(); ?>">
        <input type="hidden" name="a" value="postnote">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
            <?php
            if($errors['postnote']) {?>
            <tr>
              
                <td class="error"><?php echo $errors['postnote']; ?></td>
            </tr>
            <?php
            } ?>
            <tr>
                
                <td>
                    <div>
                        <div class="faded" style="padding-left:0.15em">Titolo allegati (opzionale)</div>
                        <input type="text" name="title" id="title" size="53" value="<?php echo $info['title']; ?>" >
                        <br/>
                        <span class="error">&nbsp;<?php echo $errors['title']; ?></span>
                    </div>
                    <br/>
                    <div class="error"><?php echo $errors['note']; ?></div>
                    <textarea name="note" id="internal_note" cols="50"
                        placeholder="Breve descrizione (obbligatoria)"
                        rows="9" wrap="soft" data-draft-namespace="ticket.note"
                        data-draft-object-id="<?php echo $ticket->getId(); ?>"
                        class="richtext ifhtml draft draft-delete"><?php echo $info['note'];
                        ?></textarea>
                <div class="attachments">
<?php
print $note_form->getField('attachments')->render();
?>
                </div>
                </td>
            </tr>
            
        </table>

       <p  style="padding-left:165px;">
           <input class="btn_sm" type="submit" value="Allega file">
           <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
       </p>
   </form>
   -->
    <div class="clear"></div>
</div>



<div style="display:none;" class="dialog" id="confirm-action">
    <h3>Allega uno o più file</h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="postnote-confirm"></p>
    <form id="note" action="tickets.php?id=<?php echo $ticket->getId(); ?>#note" name="note" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="locktime" value="<?php echo $cfg->getLockTime(); ?>">
        <input type="hidden" name="a" value="postnote">
        <table width="100%" border="0" cellspacing="0" cellpadding="3">
            <?php
            if($errors['postnote']) {?>
            <tr>
              
                <td class="error"><?php echo $errors['postnote']; ?></td>
            </tr>
            <?php
            } ?>
            <tr>
                
                <td>
                    <div>
                        <div class="faded" style="padding-left:0.15em">Titolo allegati (opzionale)</div>
                        <input type="text" name="title" id="title" size="53" value="<?php echo $info['title']; ?>" >
                        <br/>
                        <span class="error">&nbsp;<?php echo $errors['title']; ?></span>
                    </div>
                    <br/>
                    <div class="error"><?php echo $errors['note']; ?></div>
                    <textarea name="note" id="internal_note" cols="50"
                        placeholder="Breve descrizione (obbligatoria)"
                        rows="9" wrap="soft" data-draft-namespace="ticket.note"
                        data-draft-object-id="<?php echo $ticket->getId(); ?>"
                        class="richtext ifhtml draft draft-delete"><?php echo $info['note'];
                        ?></textarea>
                <div class="attachments">
<?php
print $note_form->getField('attachments')->render();
?>
                </div>
                </td>
            </tr>
            
        </table>

       <p  style="padding-left:165px;">
           <input class="btn_sm" type="submit" value="Allega file">
           <input class="btn_sm" type="reset" value="<?php echo __('Reset');?>">
       </p>
   </form>
    <div class="clear"></div>
</div>



<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="claim-confirm">
        <?php //echo __('Are you sure you want to <b>claim</b> (self assign) this ticket?');?>
        Se vuoi <strong>prendere in carico</strong> questo ticket, specifica una data di previsione intervento.
    </p>
    <p class="confirm-action" style="display:none;" id="answered-confirm">
        <?php //echo __('Are you sure you want to flag the ticket as <b>answered</b>?');
        echo "E' possibile rifiutare la chiusura di un ticket entro 3 gg dalla stessa.<br>Sei sicuro di voler rifiutare la chiusura del ticket?";?>
    </p>
    
    <p class="confirm-action" style="display:none;" id="unanswered-confirm">
        <?php echo __('Are you sure you want to flag the ticket as <b>unanswered</b>?');?>
    </p>
    <p class="confirm-action" style="display:none;" id="overdue-confirm">
        <?php echo __('Are you sure you want to flag the ticket as <font color="red"><b>overdue</b></font>?');?>
    </p>
    <p class="confirm-action" style="display:none;" id="banemail-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>ban</b> %s?'), $ticket->getEmail());?> <br><br>
        <?php echo __('New tickets from the email address will be automatically rejected.');?>
    </p>
    <p class="confirm-action" style="display:none;" id="unbanemail-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>remove</b> %s from ban list?'), $ticket->getEmail()); ?>
    </p>
    <p class="confirm-action" style="display:none;" id="release-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>unassign</b> ticket from <b>%s</b>?'), $ticket->getAssigned()); ?>
    </p>
    <p class="confirm-action" style="display:none;" id="changeuser-confirm">
        <span id="msg_warning" style="display:block;vertical-align:top">
        <?php echo sprintf(Format::htmlchars(__('%s <%s> will longer have access to the ticket')),
            '<b>'.Format::htmlchars($ticket->getName()).'</b>', Format::htmlchars($ticket->getEmail())); ?>
        </span>
        <?php echo sprintf(__('Are you sure you want to <b>change</b> ticket owner to %s?'),
            '<b><span id="newuser">this guy</span></b>'); ?>
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo __('Are you sure you want to DELETE this ticket?');?></strong></font>
        <br><br><?php echo __('Deleted data CANNOT be recovered, including any associated attachments.');?>
    </p>
    <?php if($thisstaff->getDeptId()!=17){?><div>Data previsto intervento:<br><br></div><?php } ?>
    <form action="tickets.php?id=<?php echo $ticket->getId(); ?>" method="post" id="confirm-form" name="confirm-form">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="a" value="process">
        <input type="hidden" name="do" id="action" value="">
       
         <table width="100%" border="0" cellspacing="0" cellpadding="3">
            
            <tr>
                <td  style="vertical-align:top">
              <?php if($thisstaff->getDeptId()!=17){?>     
                   <label>
        <input type="text" class="dp input-medium search-query"
            name="start_previsto" placeholder="Giorno"/>
        </label>&nbsp;&nbsp;ora:
                     <select id="ora_pre" name="ora_previsto">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        
                        
                        
                    </select>
                    &nbsp;minuti
                    <select id="minuti_pre" name="minuti_previsto">
                        <option value="00">00</option>
                        <option value="01">01</option>
                        <option value="02">02</option>
                        <option value="03">03</option>
                        <option value="04">04</option>
                        <option value="05">05</option>
                        <option value="06">06</option>
                        <option value="07">07</option>
                        <option value="08">08</option>
                        <option value="09">09</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        <option value="24">24</option>
                        <option value="25">25</option>
                        <option value="26">26</option>
                        <option value="27">27</option>
                        <option value="28">28</option>
                        <option value="29">29</option>
                        <option value="30">30</option>
                        <option value="31">31</option>
                        <option value="32">32</option>
                        <option value="33">33</option>
                        <option value="34">34</option>
                        <option value="35">35</option>
                        <option value="36">36</option>
                        <option value="37">37</option>
                        <option value="38">38</option>
                        <option value="39">39</option>
                        <option value="40">40</option>
                        <option value="41">41</option>
                        <option value="42">42</option>
                        <option value="43">43</option>
                        <option value="44">44</option>
                        <option value="45">45</option>
                        <option value="46">46</option>
                        <option value="47">47</option>
                        <option value="48">48</option>
                        <option value="49">49</option>
                        <option value="50">50</option>
                        <option value="51">51</option>
                        <option value="52">52</option>
                        <option value="53">53</option>
                        <option value="54">54</option>
                        <option value="55">55</option>
                        <option value="56">56</option>
                        <option value="57">57</option>
                        <option value="58">58</option>
                        <option value="59">59</option>
                        
                    </select>            
                </td>
            </tr>
           </table>
           <div><br>Se vuoi <strong>rifiutare</strong>questo ticket, seleziona il Gruppo di competenza del ticket e inserisci il motivo del rifiuto.</div>
            <table>
            <tr><td><br>
           
    <select id="rifiuto_ticket" name="rifiuto_ticket">
		                <option value="" selected="selected">Prendi in carico</option>
                        <option value="cnt:4938065152c31241bd80c5593c410c42">MANUTENZIONEICT_NORD</option>
                        <option value="cnt:892653e7ffefcd4c93826faab10cf7ff">MANUTENZIONEICT_CENTRO</option>
                        <option value="cnt:bf186c5bf9aed34ea132ab2ce32870bf">MANUTENZIONEICT_SUD</option>
    </select>
    
    <br><br>
    <div id="messaggio_rifiuto" name="messaggio_rifiuto" style="display:none;">
    <textarea name="msg_rifiuto" id="msg_rifiuto"
                        cols="60" rows="5" wrap="soft"
                        placeholder="Motivo rifiuto (obbligatorio)"></textarea></div>
     <?php }else{ ?>
		<div id="messaggio_rifiuto" name="messaggio_rifiuto">	
    <textarea name="rejected" id="rejected"
                        cols="60" rows="5" wrap="soft"
                        placeholder="Motivo rifiuto (obbligatorio)"></textarea></div> 
		 <?php }?>
            </td></tr>
        </table>
   <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="locktime" value="<?php echo $cfg->getLockTime(); ?>">
        <p class="full-width">
            <span class="buttons pull-left">
                <input type="button" value="<?php echo __('Cancel');?>" class="close">
            </span>
            <span class="buttons pull-right">
                <input type="submit" value="<?php echo __('OK');?>" id="link-opzioni_risposta" onclick="mostranascondi2('opzioni_risposta','link-opzioni_risposta');">
            </span>
         </p>
    </form>
    <div class="clear"></div>
    
</div>
<script type="text/javascript">
$(function() {
    $(document).on('click', 'a.change-user', function(e) {
        e.preventDefault();
        var tid = <?php echo $ticket->getOwnerId(); ?>;
        var cid = <?php echo $ticket->getOwnerId(); ?>;
        var url = 'ajax.php/'+$(this).attr('href').substr(1);
        $.userLookup(url, function(user) {
            if(cid!=user.id
                    && $('.dialog#confirm-action #changeuser-confirm').length) {
                $('#newuser').html(user.name +' &lt;'+user.email+'&gt;');
                $('.dialog#confirm-action #action').val('changeuser');
                $('#confirm-form').append('<input type=hidden name=user_id value='+user.id+' />');
                $('#overlay').show();
                $('.dialog#confirm-action .confirm-action').hide();
                $('.dialog#confirm-action p#changeuser-confirm')
                .show()
                .parent('div').show().trigger('click');
            }
        });
    });
<?php
    // Set the lock if one exists
    if ($lock) { ?>
!function() {
  var setLock = setInterval(function() {
    if (typeof(window.autoLock) === 'undefined')
      return;
    clearInterval(setLock);
    autoLock.setLock({
      id:<?php echo $lock->getId(); ?>,
      time: <?php echo $cfg->getLockTime(); ?>}, 'acquire');
  }, 50);
}();
<?php } ?>
});
</script>

<script>
function mostranascondi(div, switchImgTag) {
        var ele = document.getElementById(div);
        var imageEle = document.getElementById(switchImgTag);
        if(ele.style.display == "block") {
                ele.style.display = "none";
		imageEle.innerHTML = '<img src="../images/down.png">';
        }
        else {
                ele.style.display = "block";
                imageEle.innerHTML = '<img src="../images/up.png">';
        }
}
</script>
<script>
function mostranascondi2(div, switchImgTag) {
        var ele = document.getElementById(div);
        var imageEle = document.getElementById(switchImgTag);
        if(ele.style.display == "block") {
                ele.style.display = "none";
		imageEle.innerHTML = 'EDIT';
        }
        else {
                ele.style.display = "block";
                imageEle.innerHTML = 'EDIT';
        }
}
</script>
<script>
function mostranascondi3(div, switchImgTag) {
        var ele = document.getElementById(div);
        var imageEle = document.getElementById(switchImgTag);
        if(ele.style.display == "block") {
                ele.style.display = "none";
		imageEle.innerHTML = 'EDIT';
        }
        else {
                ele.style.display = "block";
                imageEle.innerHTML = 'EDIT';
        }
}
</script>




<!--SCRIPT DEFINITIVI??? -->
<script>
$(document).ready(function() {
$reply_status_id = $("select[name='reply_status_id']");
$stato_cliente = $("select[name='stato_cliente']");

$reply_status_id.change(function() {

if ($(this).val() == "2") {
$("select[name='stato_cliente'] option").remove();
<?php  if ($stato_cliente=="Closed&Certified"){ ?>
$("<option value='50'>Closed&Certified</option>").appendTo($stato_cliente); 
<?php }else{ ?>
$("<option value='50'>Chiuso da manutentore</option>").appendTo($stato_cliente);//oppure chiuso e certificato 
<?php } ?>	
}

if ($(this).val() == "12") 
{
$("select[name='stato_cliente'] option").remove();
//attuale (in carico al man)
$("<option value='53'>In carico a manutentore</option>").appendTo($stato_cliente);
}

if ($(this).val() == "11") 
{
$("select[name='stato_cliente'] option").remove();
//attuale (in carico al man)
$("<option value='53'>In carico a manutentore</option>").appendTo($stato_cliente);
}

if ($(this).val() == "23") 
{
$("select[name='stato_cliente'] option").remove();
//attuale (in carico al man)
$("<option value='53'>In carico a manutentore</option>").appendTo($stato_cliente);
}

if ($(this).val() == "21") 
{
$("select[name='stato_cliente'] option").remove();
//attuale (in carico al man)
$("<option value='51'>Sospeso da manutentore</option>").appendTo($stato_cliente);	
}

if ($(this).val() == "22") 
{
$("select[name='stato_cliente'] option").remove();
//attuale (in carico al man)
$("<option value='52'>Ripreso da manutentore</option>").appendTo($stato_cliente);	
}


});
});
</script>
<script>
$(document).ready(function() {
$reply_status_id = $("select[name='reply_status_id']");
$stato_cliente = $("select[name='stato_cliente']");

$stato_cliente.change(function() {

if ($(this).val() == "50") {
$("select[name='reply_status_id'] option").remove();

$("<option value='2'>Risolto</option>").appendTo($reply_status_id);
}

if ($(this).val() == "51") 
{
//stato attuale	
$("select[name='reply_status_id'] option").remove();

$("<option value=''><?php echo $stato_int.' (corrente)';?></option>").appendTo($reply_status_id);

}

if ($(this).val() == "52") //solo se lo stato cliente è sospeso
{
//stato attuale	
$("select[name='reply_status_id'] option").remove();

$("<option value=''><?php echo $stato_int.' (corrente)';?></option>").appendTo($reply_status_id);

}



});
});
</script>
<script>
$(document).ready(function(){
  $('#reply_status_id').change(function(){
    $('#risolto')[$(this).val()=='2' ? 'show' : 'hide']();
  });
});
$(document).ready(function(){
  $('#reply_status_id').change(function(){
    $('#attesacliente')[$(this).val()=='21' ? 'show' : 'hide']();
  });
});
$(document).ready(function(){
  $('#reply_status_id').change(function(){
    $('#magazzino')[$(this).val()=='11' ? 'show' : 'hide']();
  });
});
$(document).ready(function(){
  $('#reply_status_id').change(function(){
    $('#laboratorio')[$(this).val()=='12' ? 'show' : 'hide']();
  });
});
$(document).ready(function(){
  $('#reply_status_id').change(function(){
    $('#ripresa')[$(this).val()=='22' ? 'show' : 'hide']();
  });
});
$(document).ready(function(){
  $('#reply_status_id').change(function(){
    $('#assegnato')[$(this).val()=='23' ? 'show' : 'hide']();
  });
});

//lab, mag e partner
$(document).ready(function(){
  $('#reply_status_id').change(function(){
    $('#lab_13')[$(this).val()=='13' ? 'show' : 'hide']();
  });
});
$(document).ready(function(){
  $('#reply_status_id').change(function(){
    $('#lab_14')[$(this).val()=='14' ? 'show' : 'hide']();
  });
});
$(document).ready(function(){
  $('#reply_status_id').change(function(){
    $('#lab_19')[$(this).val()=='19' ? 'show' : 'hide']();
  });
});
$(document).ready(function(){
  $('#reply_status_id').change(function(){
    $('#lab_20')[$(this).val()=='20' ? 'show' : 'hide']();
  });
});

$(document).ready(function(){
  $('#reply_status_id').change(function(){
    $('#tec_18')[$(this).val()=='18' ? 'show' : 'hide']();
  });
});

$(document).ready(function(){
  $('#reply_status_id').change(function(){
    $('#tec_17')[$(this).val()=='17' ? 'show' : 'hide']();
  });
});

$(document).ready(function(){
  $('#reply_status_id').change(function(){
    $('#par_9')[$(this).val()=='9' ? 'show' : 'hide']();
  });
});



$(document).ready(function(){
  $('#stato_cliente').change(function(){
    $('#chiusura_soap')[$(this).val()=='50' ? 'show' : 'hide']();
  });
});
$(document).ready(function(){
  $('#stato_cliente').change(function(){
    $('#sospensione_soap')[$(this).val()=='51' ? 'show' : 'hide']();
  });
});
$(document).ready(function(){
  $('#stato_cliente').change(function(){
    $('#ripresa_soap')[$(this).val()=='52' ? 'show' : 'hide']();
  });
});
$(document).ready(function(){
  $('#rifiuto_ticket').change(function(){
    $('#messaggio_rifiuto')[$(this).val()=='cnt:4938065152c31241bd80c5593c410c42' || $(this).val()=='cnt:892653e7ffefcd4c93826faab10cf7ff' || $(this).val()=='cnt:bf186c5bf9aed34ea132ab2ce32870bf' ? 'show' : 'hide']();
  });
});
</script>

<script type="text/javascript">
    $('a#motivo-sospensione').click(function(e) {
        e.preventDefault();
        alert("<?php echo $ticket->motivo_sospensione();?>");
        return false;
    });
</script>


<script type="text/javascript">

$(document).ready(function() {

	$('#loader').hide();
	$('#show_heading_eqGA').hide();
	
	$('#search_category_id_eqGA').change(function(){
		$('#show_sub_categories_eqGA').fadeOut();
		$('#loader').show();
		$.post("http://fastdata2training.service-tech.org/ticket/include/staff/asset.inc1.php?username=<?php echo $username;?>", {
			parent_id: $('#search_category_id_eqGA').val(),
		}, function(response){
			
			setTimeout("finishAjax('show_sub_categories_eqGA', '"+escape(response)+"')", 400);
		});
		return false;
	});
});

function finishAjax(id, response){
  $('#loader').hide();
  $('#show_heading_eqGA').show();
  $('#'+id).html(unescape(response));
  $('#'+id).fadeIn();
} 

function alert_id()
{
	if($('#sub_category_id_eqGA').val() == '')
	alert('seleziona un asset');
	else
	alert($('#sub_category_id_eqGA').val());
	return false;
}

</script>

<script type="text/javascript">
$(document).ready(function() {
    $('#esito').on('change', function() {
      if ( this.value == 'Sostituito')
      {
        $("#ptswappato").show();
      }
      else
      {
        $("#ptswappato").hide();
      }
    });
});
</script>
