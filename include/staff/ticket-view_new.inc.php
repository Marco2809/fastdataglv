<?php

/*error_reporting(E_ALL);
ini_set("display_errors", 1);*/

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

<script>
    function mostranascondi(div, switchImgTag) {
        var ele = document.getElementById(div);
        var imageEle = document.getElementById(switchImgTag);
        if(ele.style.display == "block") {
            ele.style.display = "none";
            imageEle.innerHTML = '<img src="../images/down_new.png">';
        }
        else {
            ele.style.display = "block";
            imageEle.innerHTML = '<img src="../images/up_new.png">';
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
<script>
    function mostranascondi4(div, switchImgTag) {
        var ele = document.getElementById(div);
        var imageEle = document.getElementById(switchImgTag);
        if(ele.style.display == "block") {
            ele.style.display = "none";
            imageEle.innerHTML = 'Allega file';
        }
        else {
            ele.style.display = "block";
            imageEle.innerHTML = 'Allega file';
        }
    }
</script>

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
            $noedit = array('10','11','12','13','14','15','16','17');
            if (!in_array($thisstaff->getDeptId(),$noedit) and $ticket->getStatusId()!=8){
            if ($ticket->isOpen()
                    && !$ticket->isAssigned()
                    && $thisstaff->canAssignTickets()
                    && $ticket->getDept()->isMember($thisstaff)) {?>
                <a id="ticket-claim" class="action-button pull-right confirm-action" href="#claim"><i class="icon-user"></i>Modifica</a>

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
            if(($thisstaff->getDeptId()==6 || $thisstaff->getDeptId()==7 || $thisstaff->getDeptId()==9) and $ticket->getStatusId()!=8){?>
            <!--<a class="action-button pull-right confirm-action"  href="#postnote"><i class="icon-edit"></i>Allega file</a>-->
            <a class="action-button pull-right confirm-action" id="link-opzioni_risposta1" onclick="mostranascondi4('confirm-action','link-opzioni_risposta1');"><i class="icon-user"></i><strong>Allega file</strong></a>
            <?php
            }?>
            <!--
            <span class="action-button pull-right" data-dropdown="#action-dropdown-print">
                <i class="icon-caret-down pull-right"></i>
                <a id="ticket-print" href="tickets.php?id=<?php echo $ticket->getId(); ?>&a=print"><i class="icon-print"></i> <?php
                    echo __('Print'); ?></a>
            </span>-->
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
                 if($thisstaff->canDeleteTickets()) {
                     ?>
                    <li><a class="ticket-action" href="#tickets/<?php
                    echo $ticket->getId(); ?>/status/delete"
                    data-href="tickets.php"><i class="icon-trash"></i> <?php
                    echo __('Delete Ticket'); ?></a></li>
                <?php

                }?>
              </ul>
            </div>
        </td>
    </tr>
</table>
<table border="0" width="100%"  style="border-collapse: separate; border-spacing: 0px 0px; margin-top:0px; margin-bottom:0px; background:transparent; "><tr><td align="right"><!--<a style="margin-right: 30px;" id="link-opzioni_risposta" onclick="mostranascondi3('opzioni_risposta','link-opzioni_risposta');"><strong>EDIT</strong></a>--></td></tr></table>

<!--TECNICI -->
<?php
$tec = array(7,8);
if (!in_array($thisstaff->getDeptId(), $tec)) {
//if ($thisstaff->getDeptId()!=8 AND $thisstaff->getDeptId()!=9 AND $thisstaff->getDeptId()!=10 AND $thisstaff->getDeptId()!=11 AND $thisstaff->getId()!=2 AND $thisstaff->getDeptId()!=12) {?>
<table style="border-collapse: separate; border-spacing: 0px 0px; margin-top:0px; margin-bottom:10px; background:transparent; ">
<tr>
<td rowspan="2" style="vertical-align:top;">
<div style="height:612px;
	width:250px;
	overflow:auto;
	text-align:center;">
  <div style="margin-left:auto;
    margin-right:auto;
    width: 100%;
	height:612px;
	overflow: auto;">
<center>
	<table width="100%">
	<th class="liste_titre" style="width:100%; height:50px;"  align="center"><strong style="font-size:18px; font-family:play; font-weight:bold;">Tecnici</strong></th></table>
 </center>
 <center>
 <table width="100%" border="0"  >

     <tbody>
        <?php
        // Setup Subject field for display
        if ($thisstaff->getId()==5) {
        $tecnici=array('Tecnico1'=>'2');
        }else{

		/*
		$query ="SELECT lastname, staff_id FROM ost_staff";
		$res = db_query($query); //mettere if su res
        $results = array();
        while ($row = db_fetch_array($res)) {
         $results[] = $row;
	    }
	    */
	    //print_r($results);


      $tecnici=array('Zavattolo Domenico'=>'1',
       'De Lorenzo Paolo'=>'2',
       'Planner Planner'=>'3',
       'Tavani Nunzio'=>'23',
       'Corradengo Giuseppe '=>'27',
       'Mandrisi Emanuele'=>'48',
       'Spinelli Giuseppe'=>'76',
       'A.Matilli Alessandro'=>'77',
       'G.Matilli Giovanna'=>'78',
       'Zoccali Andrea'=>'79',
       'Ditta Andrea'=>'80',
       'Salvatori Liviano'=>'81',
       'Graziosi Andrea'=>'82',
       'Stoyanov Marian'=>'83',
       'Santini Bruno'=>'84',
       'Allera Alain'=>'85',
       'Cofini Roberto'=>'86',
       'Pizzichillo Antonio'=>'87',
       'Tremoni Mariano'=>'88',
       'Levino Umberto'=>'89',
       'Roscioli Andrea'=>'90',
       'Penta Guido'=>'92');
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

?>

             <?php if ($results) { ?>
             <tr>
                <td>
			     <center>
                 <table width="100%" border="0">

                 <tr class="pair">

                 <td align="center" width="20%" class="nohover" >
                 <img src="../images/tecnico.png" style="margin-left:10px">
                 </td>


                 <td align="center" nowrap  width="60%" colspan="2" ><strong><a href="?a=profilo&id=<?php echo $ticket->getId();?>&tecnico=<?php $cognome=explode(' ', $utente, 3); if (trim($cognome[0])=='De'){$cognome[0]=$cognome[0].' '.$cognome[1];} echo $cognome[0];?>&riferimento=<?php echo $tecnico; ?>"><?php  echo $cognome[0];?></a></strong></td>

                 <td align="center"  width="20%" class="nohover" ></td>

                 </tr>

                 <?php $z=0; foreach ($results as $risultato) { //visualizzo il numero di ticket assegnati ad ogni tecnico?>

                  <?php $z++;} ?>

                  <tr class="impair">

                 <td align="center" nowrap width="100%" colspan="4" style="font-size:13px; font-family:play; font-weight:bold; color:black;"><strong>Ticket assegnati:&nbsp;</strong>(<?php echo $z;?>)</td>


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

                                   $nome_commessa=$commessa_nome;
                                   $codice_commessa=$commessa_codice;
                                   $desc_commessa=$commessa_descrizione;

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
<div style="width: 100%;">
  <div style="width:100%;">

<table border="0" width="100%"><tr class="liste_titre" height="50px"><td align="left"><strong style="font-size:18px; font-family:play; font-weight:bold;">&nbsp;Dati Ticket </strong></td><td align="right"><a style="float:right; margin-bottom:10px;" id="link-<?php echo $ticket->getId(); ?>" onclick="mostranascondi('eventbody-<?php echo $ticket->getId(); ?>','link-<?php echo $ticket->getId(); ?>');"><img src="../images/up_new.png"></a></td></tr></table>
<div id="eventbody-<?php
                echo $ticket->getId(); ?>" style="display:block;">

<table  cellspacing="0" cellpadding="0" width="100%" border="0"><!--width="940"-->
    <tr>
        <td width="50%">
            <table border="0" cellspacing="" cellpadding="4" width="100%" style="border-collapse: separate; border-spacing: 0px 0px; margin-top:5px; margin-bottom:0px; background:transparent; ">
                <tr class="pair">
                    <th align="left" style="font-size:14px; font-family:play; font-weight:bold; color:black;">Numero ticket interno:</th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo $ticket_interno; ?></td>
                </tr>
                <tr class="impair">
                    <th align="left"  style="font-size:14px; font-family:play; font-weight:bold; color:black;"><?php echo __('Status');?> interno:</th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo $ticket->getStatus(); ?></td>
                </tr>
                <?php if ($cliente==9){?>
                <tr class="pair">
                    <th align="left"  style="font-size:14px; font-family:play; font-weight:bold; color:black;">Località:</th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo $localita.' ('.$provincia.')'; ?></td>
                </tr>
                <?php }?>
                <tr class="<?php echo ($cliente == 9)?'impair':'pair' ?>">
                    <th align="left"  style="font-size:14px; font-family:play; font-weight:bold;"><font color="<?php echo $tel_ufficio?'black':'grey';?>">Telefono Ufficio:</font></th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo $tel_ufficio; ?></td>
                </tr>

                <tr class="<?php echo ($cliente == 9)?'pair':'impair' ?>">
                    <th align="left"  style="font-size:14px; font-family:play; font-weight:bold;"><font color="<?php echo $ticket->frazionario()?'black':'grey';?>">ABI:</font></th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo $ticket->frazionario(); //if (isset($mono)&&!empty($mono)){echo (strpos($ticket->mono_turno(), '19') !== false OR strpos($ticket->mono_turno(), '18') !== false)?' (doppio turno)':' (mono turno)';}?></td>
                </tr>
                <tr class="<?php echo ($cliente == 9)?'impair':'pair' ?>">
                    <th align="left"  style="font-size:14px; font-family:play; font-weight:bold;"><font color="<?php echo date('d/m/Y H:i:s',$ticket->data_previsto_intervento())?'black':'grey';?>">Data prevista intervento:</font></th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo date('d/m/Y H:i:s',$ticket->data_previsto_intervento()); ?></td>
                </tr>
                <tr class="<?php echo ($cliente == 9)?'pair':'impair' ?>">
                    <th align="left"  style="font-size:14px; font-family:play; font-weight:bold;"><font color="<?php echo date('d/m/Y H:i:s',$ticket->data_appuntamento())?'black':'grey';?>">Nuova scadenza:</font></th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo date('d/m/Y H:i:s',$ticket->data_appuntamento()); ?></td>
                </tr>
                <tr class="<?php echo ($cliente == 9)?'impair':'pair' ?>">
                    <th align="left"  style="font-size:14px; font-family:play; font-weight:bold;"><font color="<?php echo date('d/m/Y H:i:s',$data_inizio_intervento)?'black':'grey';?>">Data inizio intervento:</font></th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo date('d/m/Y H:i:s',$data_inizio_intervento); ?></td>
                </tr>
                <tr class="<?php echo ($cliente == 9)?'pair':'impair' ?>">
                    <th align="left"  style="font-size:14px; font-family:play; font-weight:bold; color:black;">Transfer Date:</th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo $cliente==9?date('d/m/Y H:i:s', $trasfer_date):Format::db_datetime($ticket->getCreateDate()); ?></td>
                </tr>
            </table>
        </td>
        <td width="50%">
            <table border="0" cellspacing="" cellpadding="4" width="100%" style="border-collapse: separate; border-spacing: 0px 0px; margin-top:5px; margin-bottom:0px; background:transparent; ">
                 <tr class="pair">
                    <th align="left" style="font-size:14px; font-family:play; font-weight:bold; color:black;">Numero ticket cliente:</th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo $problem; ?></td>
                </tr>
                <?php if ($cliente==9){?>
                <tr class="impair">
                    <th align="left" style="font-size:14px; font-family:play; font-weight:bold; color:black;">Stato cliente:</th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo $stato_cliente; ?></td>
                </tr>
                <?php }?>
                <tr class="<?php echo ($cliente == 9)?'pair':'impair' ?>">
                    <th align="left" style="font-size:14px; font-family:play; font-weight:bold; color:black;">Cliente:</th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo $commessa_cliente; ?></td>

                </tr>
                <tr class="<?php echo ($cliente == 9)?'impair':'pair' ?>">
                    <th align="left" style="font-size:14px; font-family:play; font-weight:bold;"><font color="<?php echo $sede_cliente?'black':'grey';?>">Insegna:</font></th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo $sede_cliente; ?></td>
                </tr>
                 <tr class="<?php echo ($cliente == 9)?'pair':'impair' ?>">
                    <th align="left" style="font-size:14px; font-family:play; font-weight:bold;"><font color="<?php echo $regione?'black':'grey';?>">Regione:</font></th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo $regione; ?></td>
                </tr>
                <tr class="<?php echo ($cliente == 9)?'impair':'pair' ?>">
                    <th align="left" style="font-size:14px; font-family:play; font-weight:bold;"><font color="<?php echo date('d/m/Y H:i:s',$ticket->data_contatto_utente())?'black':'grey';?>">Data contatto utente:</font></th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo date('d/m/Y H:i:s',$ticket->data_contatto_utente()); ?></td>
                </tr>
                <tr class="<?php echo ($cliente == 9)?'pair':'impair' ?>">
                    <th align="left" style="font-size:14px; font-family:play; font-weight:bold;"><font color="<?php echo $ticket->motivo_sospensione()?'black':'grey';?>">Motivo sospensione:</font></th>
                    <td style="font-size:14px; font-family:play; color:black;"><a id="motivo-sospensione" href="#">
                     <?php echo ucfirst(strtolower(Format::truncate($ticket->motivo_sospensione(),50))); ?></a></td>
                </tr>
                <tr class="<?php echo ($cliente == 9)?'impair':'pair' ?>">
                    <th align="left" style="font-size:14px; font-family:play; font-weight:bold;"><font color="<?php echo date('d/m/Y H:i:s',$data_proposta_chiusura)?'black':'grey';?>">Data proposta chiusura:</font></th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo date('d/m/Y H:i:s',$data_proposta_chiusura); ?></td>
                </tr>
                <tr class="<?php echo ($cliente == 9)?'pair':'impair' ?>">
                    <th align="left" style="font-size:14px; font-family:play; font-weight:bold; color:black;"><?php echo __('Due Date');?>:</th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo Format::db_datetime($ticket->getEstDueDate()); ?></td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</div><!--div per hide show-->
<br><!--dettaglio commessa -->
<table border="0" width="100%"><tr class="liste_titre" height="50px"><td align="left"><strong style="font-size:18px; font-family:play; font-weight:bold;">&nbsp;Dettaglio commessa </strong></td><td align="right"><a style="float:right; margin-bottom:10px;" id="link-<?php echo strtotime($ticket->getLastMsgDate()); ?>" onclick="mostranascondi('eventbody-<?php echo strtotime($ticket->getLastMsgDate()); ?>','link-<?php echo strtotime($ticket->getLastMsgDate()); ?>');"><img src="../images/up_new.png"></a></td></tr></table>
<div id="eventbody-<?php
                echo strtotime($ticket->getLastMsgDate());?>" style="display:block;">

<table  cellspacing="0" cellpadding="0" width="100%" border="0"><!--width="940"-->
    <tr>
        <td width="50%">
            <table cellspacing="0" cellpadding="4" width="100%" border="0">
                <tr class="pair">
                    <th align="left"  style="font-size:14px; font-family:play; font-weight:bold; color:black;">Codice commessa:</th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo $codice_commessa; ?></td>
                </tr>
                <?php
                if($ticket->isOpen()){ ?>
                <tr class="impair">
                    <th align="left" style="font-size:14px; font-family:play; font-weight:bold; color:black;">Nome commessa:</th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo $nome_commessa; ?></td>
                </tr>
                <?php
                }else { ?>
                <tr class="impair">
                    <th align="left" style="font-size:14px; font-family:play; font-weight:bold; color:black;">Nome commessa:</th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo $nome_commessa; ?></td>
                </tr>
                <?php
                }
                ?>
            </table>
        </td>
        <td width="50%">
            <table cellspacing="0" cellpadding="4" width="100%" border="0">
                <tr class="pair">
                    <th align="left" style="font-size:14px; font-family:play; font-weight:bold; color:black;">Tipologia commessa:</th>
                    <td style="font-size:14px; font-family:play;  color:black;"><?php echo Format::htmlchars($ticket->getHelpTopic()); ?></td>
                </tr>
                <tr class="impair">
                    <th nowrap align="left" style="font-size:14px; font-family:play; font-weight:bold; color:black;">Descrizione commessa:</th>
                    <td style="font-size:14px; font-family:play; color:black;"><?php echo $desc_commessa;?></td>
                </tr>

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
<table border="0" width="100%"><tr class="liste_titre" height="50px"><td align="left"><strong style="font-size:18px; font-family:play; font-weight:bold;">&nbsp;Dettaglio Ticket </strong></td><td align="right"><a style="float:right; margin-bottom:10px;" id="link-<?php echo $_SESSION['dett_'.$randomico]; ?>" onclick="mostranascondi('eventbody-<?php echo $_SESSION['dett_'.$randomico]; ?>','link-<?php echo $_SESSION['dett_'.$randomico]; ?>');"><img src="../images/up_new.png"></a></td></tr></table>
<div id="eventbody-<?php
                echo $_SESSION['dett_'.$randomico];?>" style="display:block;">
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

            <?php
                $k=$j=0;
                echo '<tr class="pair">';
                $pari=1;
                foreach($answers as $a) {
                if (!($v = $a->display())) continue;
                if ($a->getField()->get('label')!="Stato del Problem"
                and $a->getField()->get('label')!="Identificativo SDM"
                and $a->getField()->get('label')!="Tipo ticket"
                and $a->getField()->get('label')!="Data Previsto Intervento"
                and $a->getField()->get('label')!="Guasto Riscontrato"
                and $a->getField()->get('label')!="Intervento"
                and $a->getField()->get('label')!="Commessa"
                and $a->getField()->get('label')!="Ricambi Sostituiti"
                and $a->getField()->get('label')!="Prezzo ditta"
                and $a->getField()->get('label')!="Costo tecnico"
                and $a->getField()->get('label')!="Ora Ordine"
                and $a->getField()->get('label')!="Ricavi"){
                $j++;
                $pari++;
                ?>
            <td width="25%" align="left" style="font-size:14px; font-family:play; font-weight:bold; color:black;">
			<?php
                echo $a->getField()->get('label');
            ?>:</td>
            <td width="25%" style="font-size:14px; font-family:play; color:black;">
		    <?php
		        if ($a->getField()->get('label')=="attivo S/N"){
                $v == 0 ? print ("N") : print ("S");
              }elseif ($a->getField()->get('label')=="Telefono1" and $thisstaff->getDeptId()==6){
            ?>
        <form id="telefono1" action="tickets.php?id=<?php echo $ticket->getId(); ?>#telefono1" name="telefono1" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="a" value="telefono1">
        <input type="text"  name="telefono1_andrea" placeholder="<?php echo $ticket->cellulare(); ?>"/>
        <input class="btn_sm" type="submit" value="Go">
        </form>

            <?php
              }elseif ($a->getField()->get('label')=="Telefono" and $thisstaff->getDeptId()==6){
            ?>
        <form id="telefono" action="tickets.php?id=<?php echo $ticket->getId(); ?>#telefono" name="telefono" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="a" value="telefono">
        <input type="text"  name="telefono_andrea" placeholder="<?php echo $ticket->telefono_ufficio(); ?>"/>
        <input class="btn_sm" type="submit" value="Go">
        </form>

            <?php
          }elseif ($a->getField()->get('label')=="Indirizzo" and $thisstaff->getDeptId()==6){
            ?>
        <form id="nuovoindirizzo" action="tickets.php?id=<?php echo $ticket->getId(); ?>#nuovoindirizzo" name="nuovoindirizzo" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="a" value="nuovoindirizzo">
        <input type="text"  name="nuovoindirizzo_andrea" placeholder="<?php echo $ticket->via_ufficio(); ?>"/>
        <input class="btn_sm" type="submit" value="Go">
        </form>

            <?php
              }else{
                echo $v;
              }




                if($j == 2) {
				if ($pari % 2 == 0){
                echo '</tr><tr class="pair">';
			    }else{
				echo '</tr><tr class="impair">';
				}
				$pari++;
                $j = 0;
            }?>

              <?php }else{continue;}?>
            <?php
            $k++;}
            if ($k % 2 != 0) echo '<td></td><td></td></tr>';
            ?>
            </table>
        </td>
        </tr>
    <?php
    $idx++;
    } ?>
</table>
</div>
<br>
<!--<h2 style="padding:10px 0 5px 0; font-size:11pt;"><?php echo Format::htmlchars($ticket->getSubject()); ?></h2>summary del ticket-->
<?php
$tcount = $ticket->getThreadCount();
$tcount+= $ticket->getNumNotes();
?><!--
<ul id="threads">
    <li><a class="active" id="toggle_ticket_thread" href="#"><?php echo sprintf(__('Ticket Thread (%d)'), $tcount); ?></a></li>
</ul> altre crap-->
<table border="0" width="100%"><tr class="liste_titre" height="50px"><td align="left"><strong style="font-size:18px; font-family:play; font-weight:bold;">&nbsp;Ticket tracking </strong></td><td align="right"><a style="float:right; margin-bottom:10px;" id="link-<?php echo $entry['id']; ?>" onclick="mostranascondi('ticket_thread-<?php echo $entry['id']; ?>','link-<?php echo $entry['id']; ?>');"><img src="../images/up_new.png"></a></td></tr></table>

<div id="ticket_thread-<?php echo $entry['id']; ?>" style="display:block;">
    <?php
    $threadTypes=array('M'=>'message','R'=>'response', 'N'=>'note');
    /* -------- Messages & Responses & Notes (if inline)-------------*/
    $types = array('M', 'R', 'N');
    if(($thread=$ticket->getThreadEntries($types))) {
echo '<table cellspacing="0" cellpadding="0" width="100%" border="0"><tr><td>';
       foreach($thread as $entry) { ?>
        <table class="thread-entry <?php echo $threadTypes[$entry['thread_type']]; ?>" cellspacing="0" cellpadding="0" width="100%" border="0"><!--width="940"-->
            <tr class="pair">
                <th colspan="4" width="100%" style="font-size:14px; font-family:play; font-weight:bold; color:black;">
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
            <tr class="impair"><td colspan="4" id="thread-id-<?php
                echo $entry['id']; ?>" style="font-size:14px; font-family:play; color:black;"><div><?php
                echo $entry['body']->toHtml(); ?></div></td></tr>
            <?php
            if($entry['attachments']
                    && ($tentry = $ticket->getThreadEntry($entry['id']))
                    && ($urls = $tentry->getAttachmentUrls())
                    && ($links = $tentry->getAttachmentsLinks())) {?>
            <tr>
                <td class="thread-body" colspan="4" style="font-size:14px; font-family:play; color:black;"><><?php echo $tentry->getAttachmentsLinks(); ?></td>
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
echo '</td></tr></table>';
    } else {
        echo '<p>'.__('Error fetching ticket thread - get technical help.').'</p>';
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

<div style="width: 100%;">
  <div style="width:100%; display:none;" id="opzioni_risposta">
<div style="width:100%;">

 <table width="100%" border="1" cellspacing="20px" cellpadding="10px" style="border-collapse: separate; border-spacing: 0px 10px; margin-top:-10px; margin-bottom:10px; background:transparent;"><tr><td>
    <table>


               <tr>
                <td width="120">
                    <label><span style="font-size:14px; font-family:play; font-weight:bold; color:black;">Stato interno:</span></label>
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
						if ($selected || $s->getId()==2 || $s->getId()==21 || $s->getId()==23 || $s->getId()==22){
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

                    if ($thisstaff->getDeptId()==6 && $cliente==9){

						?>
                    <label><span style="font-size:14px; font-family:play; font-weight:bold; color:black;">&nbsp;&nbsp;&nbsp;Stato cliente:</span></label>
                    <select name="stato_cliente" id="stato_cliente">
		<option selected="true" style="display:none;"><?php echo $stato_cliente." (corrente)";?></option>
		<option value="50">Chiuso da manutentore</option>
		<?php  if ($stato_cliente!="Sospeso da Manutentore"){ ?><option value="51">Sospeso da manutentore</option><?php } ?><!-- solo se lo stato cliente è != sospeso-->
		<?php  if ($stato_cliente=="Sospeso da Manutentore"){ ?><option value="52">Ripreso da manutentore</option><?php } ?><!-- solo se lo stato cliente è sospeso-->
	</select>
	<?php } ?>
	<input Type="button" value="Annulla" onClick="history.go(0)">

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
                    <label for="assignId"><span style="font-size:14px; font-family:play; font-weight:bold; color:black;"><?php echo __('Assignee');?>:</span></label>
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
									if ($name!="Domenico Zavattolo" and $name!="magazzino magazzino" and $name!="responsabile laboratorio" and $name!="screener screener") {
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
                <label><span style="font-size:14px; font-family:play; font-weight:bold; color:black;"><?php echo "Data previsto intervento"; ?>: </span></label>
                </td>
                <td style="vertical-align:top">
                <?php echo "(".date('d/m/Y H:i:s',$data_previsto_intervento).") ";?>
        <input type="text" class="dp input-medium search-query"
            name="data_prev_inter_tec" placeholder="Giorno"/>
          <span style="font-size:14px; font-family:play; font-weight:bold; color:black;">&nbsp;&nbsp;ora:</span>


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
                   <span style="font-size:14px; font-family:play; font-weight:bold; color:black;"> &nbsp;minuti</span>
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
                    <label><span style="font-size:14px; font-family:play; font-weight:bold; color:black;">Note di Screening:</span><span class='error'>&nbsp;</span></label>
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

            <!--<tr>
			<td width="120" style="vertical-align:top"><label><span style="font-size:14px; font-family:play; font-weight:bold; color:black;">
<?php
            echo "Nuova scadenza"; ?>:</span></label>
                </td>
            <td style="vertical-align:top">
                  <label>

        <input type="text" class="dp input-medium search-query"
            name="zz_dt_recall" placeholder="Giorno"/>
    <span style="font-size:14px; font-family:play; font-weight:bold; color:black;">&nbsp;&nbsp;ora:</span>

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
                   <span style="font-size:14px; font-family:play; font-weight:bold; color:black;"> &nbsp;minuti</span>
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
          </tr>--><tr>
                <td width="120" style="vertical-align:top">
                    <label><span style="font-size:14px; font-family:play; font-weight:bold; color:black;">Motivo sospensione:</span><span class='error'>&nbsp;*</span></label>
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
              <span style="font-size:14px; font-family:play; font-weight:bold; color:black;">&nbsp;&nbsp;ora:</span>
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
                    <span style="font-size:14px; font-family:play; font-weight:bold; color:black;">&nbsp;minuti</span>
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

            <tr>
                  <td width="120" style="vertical-align:top">
                      <label><span style="font-size:14px; font-family:play; font-weight:bold; color:black;">Nota:</span></label>
                  </td>

                  <td>
                      <textarea name="msg_ripreso" id="msg_ripreso"
                          cols="80" rows="7" wrap="soft"
                          placeholder="<?php echo "Inserire nota (facoltativo)"; ?>"
                          class="richtext ifhtml no-bar"><?php echo $info['sos']; ?></textarea>
                      <span class="error"><?php echo $errors['sos']; ?></span><br>
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
                <label><span style="font-size:14px; font-family:play; font-weight:bold; color:black;"><?php echo "Data fine intervento"; ?>: </span></label>
                </td>
            <td style="vertical-align:top">

        <input type="text" class="dp input-medium search-query"
            name="end_chiusura"  value="<?php echo date('d/m/Y',strtotime("-1 days"));?>"/>
          <span style="font-size:14px; font-family:play; font-weight:bold; color:black;">&nbsp;&nbsp;ora:</span>

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
                    <span style="font-size:14px; font-family:play; font-weight:bold; color:black;">&nbsp;minuti</span>
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
                <label><span style="font-size:14px; font-family:play; font-weight:bold; color:black;"><?php echo "Matricole"; ?>: </span></label>
                </td>
                <td>
                    <textarea name="seriale" id="seriale" cols="1" rows="7" wrap="soft"
                        placeholder="<?php echo "Serial Number"; ?>"
                        class="richtext ifhtml no-bar"><?php echo $info['chiusura']; ?></textarea>
                    <span class="error"><?php echo $errors['chiusura']; ?></span><br>
                </td>

            </tr>
             <tr>
             <td width="120" style="vertical-align:top">
                <label><span style="font-size:14px; font-family:play; font-weight:bold; color:black;"><?php echo "Descrizione intervento"; ?>: </span></label>
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
                <label><span style="font-size:14px; font-family:play; font-weight:bold; color:black;"><?php echo "Aggiungi asset"; ?>: </span></label>
                </td>
                <td style="vertical-align:top">
                <a class="action-button pull-left confirm-action"  href="#ws"><i class="icon-plus icon-large"></i> Aggiungi asset</a>
                </td>
              </tr>
             <?php }?>


        </table>
    <p  style="padding-left:165px;">
            <input style="font-family: play;
	font-weight: bold;
	background: white;
	border: 1px solid #8CACBB;
	color: #434956;
	text-decoration: none;
	white-space: nowrap;
	padding: 0.4em <?php echo ($dol_optimize_smallscreen?'0.4':'0.7'); ?>em;
	margin: 0em <?php echo ($dol_optimize_smallscreen?'0.7':'0.9'); ?>em;
    -moz-border-radius:0px 5px 0px 5px;
	-webkit-border-radius:0px 5px 0px 5px;
	border-radius:0px 5px 0px 5px;
    -moz-box-shadow: 2px 2px 3px #DDD;
    -webkit-box-shadow: 2px 2px 3px #DDD;
    box-shadow: 2px 2px 3px #DDD;" type="submit" value="Risolvi">
            <input style="font-family: play;
	font-weight: bold;
	background: white;
	border: 1px solid #8CACBB;
	color: #434956;
	text-decoration: none;
	white-space: nowrap;
	padding: 0.4em <?php echo ($dol_optimize_smallscreen?'0.4':'0.7'); ?>em;
	margin: 0em <?php echo ($dol_optimize_smallscreen?'0.7':'0.9'); ?>em;
    -moz-border-radius:0px 5px 0px 5px;
	-webkit-border-radius:0px 5px 0px 5px;
	border-radius:0px 5px 0px 5px;
    -moz-box-shadow: 2px 2px 3px #DDD;
    -webkit-box-shadow: 2px 2px 3px #DDD;
    box-shadow: 2px 2px 3px #DDD;" type="reset" value="<?php echo __('Reset');?>">
        </p>
</form>
</div>



</td></tr></table>
   <!-- fine proposta chiusura-->




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

<div style="display:none; width:500px" class="dialog" id="confirm-action">
	<!--
    <h3>Gestisci asset</h3>

    <hr/>
    <p class="confirm-action" style="display:none;" id="ws-confirm"></p>
    <?php require_once(STAFFINC_DIR.'asset.inc1.php'); ?>-->
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
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
        </label>
                </td>
            </tr>
           </table>





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
		imageEle.innerHTML = '<img src="../images/down_new.png">';
        }
        else {
                ele.style.display = "block";
                imageEle.innerHTML = '<img src="../images/up_new.png">';
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
<script>
function mostranascondi4(div, switchImgTag) {
        var ele = document.getElementById(div);
        var imageEle = document.getElementById(switchImgTag);
        if(ele.style.display == "block") {
                ele.style.display = "none";
		imageEle.innerHTML = 'Allega file';
        }
        else {
                ele.style.display = "block";
                imageEle.innerHTML = 'Allega file';
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
	$('#show_heading_analisiguasto').hide();

	$('#search_category_id_analisiguasto').change(function(){
		$('#show_sub_categories_analisiguasto').fadeOut();
		$('#loader').show();
		$.post("http://ticketglv.fast-data.it/include/staff/get_chid_categories_analisiguasto.php", {
			parent_id: $('#search_category_id_analisiguasto').val(),
		}, function(response){

			setTimeout("finishAjax('show_sub_categories_analisiguasto', '"+escape(response)+"')", 400);
		});
		return false;
	});
});

function finishAjax(id, response){
  $('#loader').hide();
  $('#show_heading_analisiguasto').show();
  $('#'+id).html(unescape(response));
  $('#'+id).fadeIn();
}

function alert_id()
{
	if($('#sub_category_id_analisiguasto').val() == '')
	alert('seleziona un asset');
	else
	alert($('#sub_category_id_analisiguasto').val());
	return false;
}

</script>
<script type="text/javascript">

$(document).ready(function() {

	$('#loader').hide();
	$('#show_heading_eqGA').hide();

	$('#search_category_id_eqGA').change(function(){
		$('#show_sub_categories_eqGA').fadeOut();
		$('#loader').show();
		$.post("http://ticketglv.fast-data.it/include/staff/asset.inc1.php?username=<?php echo $username;?>", {
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
    $('#esitone').on('change', function() {
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
