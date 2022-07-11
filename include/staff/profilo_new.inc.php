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
        $errors['err'] = __('EndUser email address is not valid! Consider updating it before responding');
}

$unbannable=($emailBanned) ? BanList::includes($ticket->getEmail()) : false;

if($ticket->isOverdue())
    $warn.='&nbsp;&nbsp;<span class="Icon overdueTicket">'.__('Marked overdue!').'</span>';

?>



<table style="border-collapse: separate; border-spacing: 0px 0px; margin-top:0px; margin-bottom:10px; background:transparent; ">
<tr>
<td rowspan="2" style="vertical-align:top;">
<!--TECNICI -->
<?php
$tec = array(7,8);
if (!in_array($thisstaff->getDeptId(), $tec)) {
//if ($thisstaff->getDeptId()!=8 AND $thisstaff->getDeptId()!=9 AND $thisstaff->getDeptId()!=10 AND $thisstaff->getDeptId()!=11 AND $thisstaff->getId()!=2 AND $thisstaff->getDeptId()!=12) {?>
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
           'Roscioli Andrea'=>'90');
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
				zz_date6,codice, nome, cliente, descrizione, gruppo
				FROM '.TICKET_TABLE.'__cdata  NATURAL JOIN '.TICKET_TABLE.' NATURAL JOIN ost_commesse WHERE `ticket_id`='.$ticket->getId().' LIMIT 1';
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
<td valign="top" width="98%"><!-- inizio tabella 4 celle-->
<div style="width: 100%; height:auto; overflow:auto; float: left;">
  <div>
 <!--profilo: --><?php //echo $_REQUEST['tecnico'];
 include('tickets_tecnico_new.inc.php');
 ?>
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
<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="claim-confirm">
        <?php //echo __('Are you sure you want to <b>claim</b> (self assign) this ticket?');?>
        Se vuoi <strong>prendere in carico</strong> questo ticket, specifica una data di previsione intervento.
    </p>
    <p class="confirm-action" style="display:none;" id="answered-confirm">
        <?php echo __('Are you sure you want to flag the ticket as <b>answered</b>?');?>
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
    <div>Data previsto intervento:<br><br><?php //echo __('Please confirm to continue.');?></div>
    <form action="tickets.php?id=<?php echo $ticket->getId(); ?>" method="post" id="confirm-form" name="confirm-form">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="a" value="process">
        <input type="hidden" name="do" id="action" value="">
         <table width="100%" border="0" cellspacing="0" cellpadding="3">

            <tr>
                <td  style="vertical-align:top">

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
