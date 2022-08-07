<?php
if(!defined('OSTSCPINC') || !$thisstaff || !@$thisstaff->isStaff()) die('Access Denied');

$qs= array(); //Query string collector
if($_REQUEST['status']) { //Query string status has nothing to do with the real status used below; gets overloaded.
    $qs += array('status' => $_REQUEST['status']);
}

//See if this is a search
$search=($_REQUEST['a']=='search');
$searchTerm='';
//make sure the search query is 3 chars min...defaults to no query with warning message
if($search) {
  $searchTerm=$_REQUEST['query'];
  //$searchTerm=preg_replace("/[^0-9cC,%+]/", "",$searchTerm);
  if( ($_REQUEST['query'] && strlen($_REQUEST['query'])<3)
      || (!$_REQUEST['query'] && isset($_REQUEST['basic_search'])) ){ //Why do I care about this crap...
      $search=false; //Instead of an error page...default back to regular query..with no search.
      $errors['err']=__('Search term must be more than 3 chars');
      $searchTerm='';
  }
}
$showoverdue=$showanswered=$showdisallineati=false;
$staffId=0; //Nothing for now...TODO: Allow admin and manager to limit tickets to single staff level.
$showassigned= true; //show Assigned To column - defaults to true

//Get status we are actually going to use on the query...making sure it is clean!
$status=null;
switch(strtolower($_REQUEST['status'])){ //Status is overloaded
    case 'open':
        $status='open';
        //$showanswered=true;
		$results_type=__('Open Tickets');
        break;
    case 'closed':
        $status='closed';
		$results_type=__('Closed Tickets');
        $showassigned=true; //closed by.
        break;
    case 'overdue':
        $status='open';
        $showoverdue=true;
        $results_type=__('Overdue Tickets');
        break;
    case 'disallineati':
        $status='open';
        $showdisallineati=true;
        $results_type='Ticket Disallineati';
        break;
    case 'assigned':
        $status='open';
        $staffId=$thisstaff->getId();
        $results_type=__('My Tickets');
        break;
    case 'answered':
        $status='open';
        $showanswered=true;
        $results_type=__('Answered Tickets');
        break;
    default:
        if (!$search && !isset($_REQUEST['advsid'])) {
            $_REQUEST['status']=$status='open';
            $results_type=__('Open Tickets');
        }
}

// Stash current queue view
$_SESSION['::Q'] = $_REQUEST['status'];

$qwhere ='';
/*
   STRICT DEPARTMENTS BASED PERMISSION!
   User can also see tickets assigned to them regardless of the ticket's dept.
*/

$depts=$thisstaff->getDepts();
$qwhere =' WHERE ( '
        .'  ( ticket.staff_id='.db_input($thisstaff->getId())
        .' AND status.state="open") ';

if(!$thisstaff->showAssignedOnly())
    $qwhere.=' OR ticket.dept_id IN ('.($depts?implode(',', db_input($depts)):0).')';

if(($teams=$thisstaff->getTeams()) && count(array_filter($teams)))
    $qwhere.=' OR (ticket.team_id IN ('.implode(',', db_input(array_filter($teams)))
            .') AND status.state="open") ';

$qwhere .= ' )';

//STATUS to states
$states = array(
    'open' => array('open'),
    'closed' => array('closed'));

if($status && isset($states[$status])) {
    $qwhere.=' AND status.state IN (
                '.implode(',', db_input($states[$status])).' ) ';
}

if (isset($_REQUEST['uid']) && $_REQUEST['uid']) {
    $qwhere .= ' AND (ticket.user_id='.db_input($_REQUEST['uid'])
            .' OR collab.user_id='.db_input($_REQUEST['uid']).') ';
    $qs += array('uid' => $_REQUEST['uid']);
}

//Queues: Overloaded sub-statuses  - you've got to just have faith!
if($staffId && ($staffId==$thisstaff->getId())) { //My tickets
    $results_type=__('Assigned Tickets');
    $qwhere.=' AND ticket.staff_id='.db_input($staffId);
    $showassigned=false; //My tickets...already assigned to the staff.
}elseif($showoverdue) { //overdue
    $qwhere.=' AND ticket.isoverdue=1 ';
}elseif($showdisallineati) { //overdue
    $qwhere.=' AND cdata.pc_flag=1 ';
}elseif($showanswered) { ////Answered
    $qwhere.=' AND ticket.isanswered=1 ';
}elseif(!strcasecmp($status, 'open') && !$search) { //Open queue (on search OPEN means all open tickets - regardless of state).
    //Showing answered tickets on open queue??
    if(!$cfg->showAnsweredTickets())
        $qwhere.=' AND ticket.isanswered=0 ';

    /* Showing assigned tickets on open queue?
       Don't confuse it with show assigned To column -> F'it it's confusing - just trust me!
     */
    if(!($cfg->showAssignedTickets() || $thisstaff->showAssignedTickets())) {
        $qwhere.=' AND ticket.staff_id=0 '; //XXX: NOT factoring in team assignments - only staff assignments.
        $showassigned=false; //Not showing Assigned To column since assigned tickets are not part of open queue
    }
}

if(!$search)
$qwhere.=' AND ticket.topic_id NOT IN (15,16,36,38,39) ';

//Search?? Somebody...get me some coffee
$deep_search=false;
$order_by=$order=null;
if($search):
    $qs += array('a' => $_REQUEST['a'], 't' => $_REQUEST['t']);
    //query
    if($searchTerm){
        $qs += array('query' => $searchTerm);
        $queryterm=db_real_escape($searchTerm,false); //escape the term ONLY...no quotes.
        if (is_numeric($searchTerm)) {
            $qwhere.=" AND ticket.`number` LIKE '$queryterm%'";

            //mail('marco.salmi89@gmail.com','CLASSICO',$query);
        } elseif (strpos($searchTerm,'@') && Validator::is_email($searchTerm)) {
            //pulling all tricks!
            # XXX: What about searching for email addresses in the body of
            #      the thread message
            $qwhere.=" AND email.address='$queryterm'";
        } elseif(strpos($searchTerm,'+')) {
            $queryterm = str_replace("+","",$queryterm);
            $qwhere.=" AND (cdata.`ref_num` = '$queryterm' OR cdata.`cr` LIKE '%$queryterm' OR cdata.`customer_middle_name` LIKE '%$queryterm')";
        }else {//Deep search!
            //This sucks..mass scan! search anything that moves!
            //mail('marco.salmi89@gmail.com','PROFONDO',$query);

            require_once(INCLUDE_DIR.'ajax.tickets.php');

            $tickets = TicketsAjaxApi::_search(array('query'=>$queryterm));
            if (count($tickets)) {
                $ticket_ids = implode(',',db_input($tickets));
                $qwhere .= ' AND ticket.ticket_id IN ('.$ticket_ids.')';
                $order_by = 'FIELD(ticket.ticket_id, '.$ticket_ids.')';
                $order = ' ';
                //mail('marco.salmi89@gmail.com','PROFONDO',$query);
            }
            else
                // No hits -- there should be an empty list of results
                $qwhere .= ' AND false';

            //OR thread.`body` LIKE '%$queryterm%'

        }
   }


    //mail('marco.salmi89@gmail.com','query old',$searchTerm);
endif;

if ($_REQUEST['advsid'] && isset($_SESSION['adv_'.$_REQUEST['advsid']])) {
    $ticket_ids = implode(',', db_input($_SESSION['adv_'.$_REQUEST['advsid']]));
    $qs += array('advsid' => $_REQUEST['advsid']);
    $qwhere .= ' AND ticket.ticket_id IN ('.$ticket_ids.')';
    // Thanks, http://stackoverflow.com/a/1631794
    $order_by = 'FIELD(ticket.ticket_id, '.$ticket_ids.')';
    $order = ' ';
}

$sortOptions=array('date'=>'effective_date','ID'=>'ticket.`number`*1',
    'pri'=>'pri.priority_urgency','name'=>'user.name','subj'=>'cdata.subject',
    'status'=>'status.name','assignee'=>'assigned','staff'=>'staff',
    'dept'=>'dept.dept_name');

$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');

//Sorting options...
$queue = isset($_REQUEST['status'])?strtolower($_REQUEST['status']):$status;
if($_REQUEST['sort'] && $sortOptions[$_REQUEST['sort']])
    $order_by =$sortOptions[$_REQUEST['sort']];
elseif($sortOptions[$_SESSION[$queue.'_tickets']['sort']]) {
    $_REQUEST['sort'] = $_SESSION[$queue.'_tickets']['sort'];
    $_REQUEST['order'] = $_SESSION[$queue.'_tickets']['order'];

    $order_by = $sortOptions[$_SESSION[$queue.'_tickets']['sort']];
    $order = $_SESSION[$queue.'_tickets']['order'];
}

if($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])])
    $order=$orderWays[strtoupper($_REQUEST['order'])];

//Save sort order for sticky sorting.
if($_REQUEST['sort'] && $queue) {
    $_SESSION[$queue.'_tickets']['sort'] = $_REQUEST['sort'];
    $_SESSION[$queue.'_tickets']['order'] = $_REQUEST['order'];
}

//Set default sort by columns.
if(!$order_by ) {
    if($showanswered)
        $order_by='ticket.lastresponse, ticket.created'; //No priority sorting for answered tickets.
    elseif(!strcasecmp($status,'closed'))
        $order_by='ticket.closed, ticket.created'; //No priority sorting for closed tickets.
    elseif($showoverdue) //priority> duedate > age in ASC order.
        $order_by='pri.priority_urgency ASC, ISNULL(ticket.duedate) ASC, ticket.duedate ASC, effective_date ASC, ticket.created';
    else //XXX: Add due date here?? No -
        //$order_by='pri.priority_urgency ASC, effective_date DESC, ticket.created';
        $order_by='ticket.created';
}

$order=$order?$order:'DESC';
if($order_by && strpos($order_by,',') && $order)
    $order_by=preg_replace('/(?<!ASC|DESC),/', " $order,", $order_by);

$sort=$_REQUEST['sort']?strtolower($_REQUEST['sort']):'pri.priority_urgency'; //Urgency is not on display table.
$x=$sort.'_sort';
$$x=' class="'.strtolower($order).'" ';

if($_GET['limit'])
    $qs += array('limit' => $_GET['limit']);

$qselect ='SELECT ticket.ticket_id,tlock.lock_id,ticket.number,ticket.dept_id,ticket.staff_id,ticket.team_id'
    .',user.name'
    .',email.address as email, dept.dept_name, status.state '
         .',status.name as status,ticket.source,ticket.isoverdue,ticket.isanswered,ticket.created ';

$qfrom=' FROM '.TICKET_TABLE.' ticket '.
       ' LEFT JOIN '.TICKET_STATUS_TABLE. ' status
            ON (status.id = ticket.status_id) '.
       ' LEFT JOIN '.USER_TABLE.' user ON user.id = ticket.user_id'.
       ' LEFT JOIN '.USER_EMAIL_TABLE.' email ON user.id = email.user_id'.
       ' LEFT JOIN ost_ticket_tempi tempi ON tempi.ticket_id = ticket.ticket_id'.
       ' LEFT JOIN ost_ticket__cdata cdata ON cdata.ticket_id = ticket.ticket_id'.
        //' LEFT JOIN ost_ticket_thread thread ON (thread.ticket_id = ticket.ticket_id)'.
       ' LEFT JOIN '.DEPT_TABLE.' dept ON ticket.dept_id=dept.dept_id ';

if ($_REQUEST['uid'])
    $qfrom.=' LEFT JOIN '.TICKET_COLLABORATOR_TABLE.' collab
        ON (ticket.ticket_id = collab.ticket_id )';


$sjoin='';

if($search && $deep_search) {

    $qfrom.=' LEFT JOIN '.TICKET_THREAD_TABLE.' thread ON (ticket.ticket_id=thread.ticket_id )';

}

//get ticket count based on the query so far..
//$total_dis=$total_lav=$total_sos=$total=db_count("SELECT count(DISTINCT ticket.ticket_id) $qfrom $sjoin $qwhere");
$totale = db_count("SELECT count(DISTINCT ticket.ticket_id) $qfrom $sjoin $qwhere"); //mi serve il totale per export


$total=db_count("SELECT count(CASE WHEN status.name='Transferred' OR status.name='Planning' OR status.name='Riparato LAB' OR status.name='Lavorato magazzino' OR status.name='Non risolto' OR status.name='Da validare PLN' THEN 1 END) $qfrom $sjoin $qwhere");
$total_sos=db_count("SELECT count(CASE WHEN status.name='Attesa cliente' OR status.name='Attesa preventivo' THEN 1 END) $qfrom $sjoin $qwhere");
$total_lav=db_count("SELECT count(CASE WHEN status.name='In carico al magazzino' OR status.name='In carico al laboratorio' OR status.name='Assegnato' OR status.name='In carico Tec. LAB' OR status.name='Da validare LAB' OR status.name='Non riparato' OR status.name='In attesa parti' OR status.name='Ripreso in carico' THEN 1 END) $qfrom $sjoin $qwhere");
$total_dis=db_count("SELECT count(CASE WHEN status.name='Rifiutato' THEN 1 END) $qfrom $sjoin $qwhere");
//echo $totale.'tra->'.$total.'sos->'.$total_sos.'lav->'.$total_lav.'dis->'.$total_dis;

//pagenate
if($status=='closed'){
$pagelimit=($_GET['limit'] && is_numeric($_GET['limit']))?$_GET['limit']:20;
}else{
$pagelimit=($_GET['limit'] && is_numeric($_GET['limit']))?$_GET['limit']:10;
}
//$pagelimit=6;
$_SESSION['pagina_a']=$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$_SESSION['pagina_s']=$page_s=($_GET['ps'] && is_numeric($_GET['ps']))?$_GET['ps']:1;
$_SESSION['pagina_l']=$page_l=($_GET['pl'] && is_numeric($_GET['pl']))?$_GET['pl']:1;
$_SESSION['pagina_d']=$page_d=($_GET['pd'] && is_numeric($_GET['pd']))?$_GET['pd']:1;
//$page=2;
$pageExp=new Pagenate($totale,$page,$pagelimit);
$pageNav=new Pagenate($total,$page,$pagelimit);
//print_r($pageNav);
//navigazione per i ticket sospesi
$pageNav_sos=new Pagenate($total_sos,$page_s,$pagelimit/2);
$pageNav_lav=new Pagenate($total_lav,$page_l,$pagelimit);
$pageNav_dis=new Pagenate($total_dis,$page_d,$pagelimit/2);

$qstr = '&amp;'.http::build_query($qs);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageExp->setURL('tickets.php', $qs);
/*
$pageNav->setURL('tickets.php', $qs);
$pageNav_sos->setURL('tickets.php', $qs);
$pageNav_lav->setURL('tickets.php', $qs);
$pageNav_dis->setURL('tickets.php', $qs);
*/

//ADD attachment,priorities, lock and other crap
$qselect.=' ,IF(ticket.duedate IS NULL,IF(sla.id IS NULL, NULL, DATE_ADD(ticket.created, INTERVAL sla.grace_period HOUR)), ticket.duedate) as duedate '
         .' ,CAST(GREATEST(IFNULL(ticket.lastmessage, 0), IFNULL(ticket.closed, 0), IFNULL(ticket.reopened, 0), ticket.created) as datetime) as effective_date '
         .' ,ticket.created as ticket_created, CONCAT_WS(" ", staff.firstname, staff.lastname) as staff, team.name as team '
         .' ,IF(staff.staff_id IS NULL,team.name,CONCAT_WS(" ", staff.lastname, staff.firstname)) as assigned, staff.lastname as lastname '
         .' ,IF(ptopic.topic_pid IS NULL, topic.topic, CONCAT_WS(" / ", ptopic.topic, topic.topic)) as helptopic '
         .' ,cdata.priority as priority_id, cdata.subject, cdata.active, cdata.zz_date1, cdata.pln_alpha, cdata.status_sym, cdata.ref_num'
         .' ,cdata.group_last_name, cdata.affected_resource_zz_wam_string1,cdata.customer_middle_name,cdata.customer_last_name,cdata.pc_flag, cdata.pc_sn, cdata.imac, cdata.cr, cdata.comm_id, cdata.costo_ext, cdata.costo_int, cdata.prezzo,commesse.gruppo, pri.priority_desc, pri.priority_color, regioni.nomeregione';

$qfrom.=' LEFT JOIN '.TICKET_LOCK_TABLE.' tlock ON (ticket.ticket_id=tlock.ticket_id AND tlock.expire > NOW()
               AND tlock.staff_id!='.db_input($thisstaff->getId()).') '
       .' LEFT JOIN '.STAFF_TABLE.' staff ON (ticket.staff_id=staff.staff_id) '
       .' LEFT JOIN '.TEAM_TABLE.' team ON (ticket.team_id=team.team_id) '
       .' LEFT JOIN '.SLA_TABLE.' sla ON (ticket.sla_id=sla.id AND sla.isactive=1) '
       .' LEFT JOIN '.TOPIC_TABLE.' topic ON (ticket.topic_id=topic.topic_id) '
       .' LEFT JOIN '.TOPIC_TABLE.' ptopic ON (ptopic.topic_id=topic.topic_pid) '
       //.' LEFT JOIN '.TABLE_PREFIX.'banche banche ON (banche.abi = cdata.customer_last_name) '
       .' LEFT JOIN ost_province province ON (province.siglaprovincia = cdata.customer_location_l_addr1)'
       .' LEFT JOIN ost_regioni regioni ON (regioni.idregione = province.idregione)'
       .' LEFT JOIN ost_commesse commesse ON (commesse.comm_id = cdata.comm_id)'
       .' LEFT JOIN '.PRIORITY_TABLE.' pri ON (pri.priority_id = cdata.priority)';

TicketForm::ensureDynamicDataView();

if($search or $_REQUEST['advsid'] or $status=='closed'){
  $query_exp="$qselect $qfrom ".str_replace("AND ticket.topic_id NOT IN (15,16,36,38,39)"," ",$qwhere)." ORDER BY $order_by $order LIMIT ".$pageExp->getStart().",".$pageExp->getLimit();

}else{
  $query_exp="$qselect $qfrom $qwhere AND ticket.status_id!=21 AND ticket.status_id!=8 ORDER BY $order_by $order LIMIT ".$pageExp->getStart().",".$pageExp->getLimit();

}


//mail('domenico.zavattolo@service-tech.org','da ticket',$query_exp);
//echo $query_exp;
$hash = md5($query_exp);
//echo $hash;
$_SESSION['search_'.$hash] = $query_exp;
$res_exp = db_query($query_exp);
//$res['num_rows']=30;
$showing_exp=db_num_rows($res_exp)? ' &mdash; '.$pageExp->showing():"";
//echo $showing_exp;
$query="$qselect $qfrom $qwhere "."AND (ticket.status_id=1 OR ticket.status_id=2 OR ticket.status_id=7 OR ticket.status_id=9 OR ticket.status_id=14 OR ticket.status_id=15 OR ticket.status_id=3 OR ticket.status_id=10 OR ticket.status_id=22)"." ORDER BY $order_by $order LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
//mail('domenico.zavattolo@service-tech.org','query new',$query);
//echo $query;
//$hash = md5($query);
//$_SESSION['search_'.$hash] = $query;
$res = db_query($query);
//$res['num_rows']=30;
$showing=db_num_rows($res)? ' &mdash; '.$pageNav->showing():"";
//print_r($showing);

$query_sos="$qselect $qfrom $qwhere "."AND (ticket.status_id=21 OR ticket.status_id=13)"." ORDER BY $order_by $order LIMIT ".$pageNav_sos->getStart().",".$pageNav_sos->getLimit();
$res_sos = db_query($query_sos);
$showing_sos=db_num_rows($res_sos)? ' &mdash; '.$pageNav_sos->showing():"";

$query_dis="$qselect $qfrom $qwhere "."AND (ticket.status_id=8)"." ORDER BY $order_by $order LIMIT ".$pageNav_dis->getStart().",".$pageNav_dis->getLimit();
$res_dis = db_query($query_dis);
$showing_dis=db_num_rows($res_dis)? ' &mdash; '.$pageNav_dis->showing():"";

$query_lav="$qselect $qfrom $qwhere "."AND (ticket.status_id=16 OR ticket.status_id=11 OR ticket.status_id=23 OR ticket.status_id=12 OR ticket.status_id=17 OR ticket.status_id=18 OR ticket.status_id=19)"." ORDER BY $order_by $order LIMIT ".$pageNav_lav->getStart().",".$pageNav_lav->getLimit();
$res_lav = db_query($query_lav);
$showing_lav=db_num_rows($res_lav)? ' &mdash; '.$pageNav_lav->showing():"";


if(!$results_type)
    $results_type = sprintf(__('%s Tickets' /* %s will be a status such as 'open' */),
        mb_convert_case($status, MB_CASE_TITLE));

if($search)
    $results_type.= ' ('.__('Search Results').')';

$negorder=$order=='DESC'?'ASC':'DESC'; //Negate the sorting..

// Fetch the results
$results_exp = array();
while ($row = db_fetch_array($res_exp)) {
    $results_exp[$row['ticket_id']] = $row;
}
//print_r($results);
// Fetch attachment and thread entry counts
if ($results_exp) {
    $counts_sql = 'SELECT ticket.ticket_id, coalesce(attach.count, 0) as attachments, '
        .'coalesce(thread.count, 0) as thread_count, coalesce(collab.count, 0) as collaborators '
        .'FROM '.TICKET_TABLE.' ticket '
        .'left join (select count(attach.attach_id) as count, ticket_id from '.TICKET_ATTACHMENT_TABLE
            .' attach group by attach.ticket_id) as attach on (attach.ticket_id = ticket.ticket_id) '
        .'left join (select count(thread.id) as count, ticket_id from '.TICKET_THREAD_TABLE
            .' thread group by thread.ticket_id) as thread on (thread.ticket_id = ticket.ticket_id) '
        .'left join (select count(collab.id) as count, ticket_id from '.TICKET_COLLABORATOR_TABLE
            .' collab group by collab.ticket_id) as collab on (collab.ticket_id = ticket.ticket_id) '
         .' WHERE ticket.ticket_id IN ('.implode(',', db_input(array_keys($results_exp))).');';
    $ids_res = db_query($counts_sql);
    while ($row = db_fetch_array($ids_res)) {
        $results_exp[$row['ticket_id']] += $row;
    }
}

// Fetch the results
$results = array();
while ($row = db_fetch_array($res)) {
    $results[$row['ticket_id']] = $row;
}
//print_r($results);
// Fetch attachment and thread entry counts
if ($results) {
    $counts_sql = 'SELECT ticket.ticket_id, coalesce(attach.count, 0) as attachments, '
        .'coalesce(thread.count, 0) as thread_count, coalesce(collab.count, 0) as collaborators '
        .'FROM '.TICKET_TABLE.' ticket '
        .'left join (select count(attach.attach_id) as count, ticket_id from '.TICKET_ATTACHMENT_TABLE
            .' attach group by attach.ticket_id) as attach on (attach.ticket_id = ticket.ticket_id) '
        .'left join (select count(thread.id) as count, ticket_id from '.TICKET_THREAD_TABLE
            .' thread group by thread.ticket_id) as thread on (thread.ticket_id = ticket.ticket_id) '
        .'left join (select count(collab.id) as count, ticket_id from '.TICKET_COLLABORATOR_TABLE
            .' collab group by collab.ticket_id) as collab on (collab.ticket_id = ticket.ticket_id) '
         .' WHERE ticket.ticket_id IN ('.implode(',', db_input(array_keys($results))).');';
    $ids_res = db_query($counts_sql);
    while ($row = db_fetch_array($ids_res)) {
        $results[$row['ticket_id']] += $row;
    }
}


$results_sos = array();
while ($row = db_fetch_array($res_sos)) {
    $results_sos[$row['ticket_id']] = $row;
}

// Fetch attachment and thread entry counts
if ($results_sos) {
    $counts_sql = 'SELECT ticket.ticket_id, coalesce(attach.count, 0) as attachments, '
        .'coalesce(thread.count, 0) as thread_count, coalesce(collab.count, 0) as collaborators '
        .'FROM '.TICKET_TABLE.' ticket '
        .'left join (select count(attach.attach_id) as count, ticket_id from '.TICKET_ATTACHMENT_TABLE
            .' attach group by attach.ticket_id) as attach on (attach.ticket_id = ticket.ticket_id) '
        .'left join (select count(thread.id) as count, ticket_id from '.TICKET_THREAD_TABLE
            .' thread group by thread.ticket_id) as thread on (thread.ticket_id = ticket.ticket_id) '
        .'left join (select count(collab.id) as count, ticket_id from '.TICKET_COLLABORATOR_TABLE
            .' collab group by collab.ticket_id) as collab on (collab.ticket_id = ticket.ticket_id) '
         .' WHERE ticket.ticket_id IN ('.implode(',', db_input(array_keys($results_sos))).');';
    $ids_res = db_query($counts_sql);
    while ($row = db_fetch_array($ids_res)) {
        $results_sos[$row['ticket_id']] += $row;
    }
}
//print_r($results_sos);
$results_lav = array();
while ($row = db_fetch_array($res_lav)) {
    $results_lav[$row['ticket_id']] = $row;
}
// Fetch attachment and thread entry counts
if ($results_lav) {
    $counts_sql = 'SELECT ticket.ticket_id, coalesce(attach.count, 0) as attachments, '
        .'coalesce(thread.count, 0) as thread_count, coalesce(collab.count, 0) as collaborators '
        .'FROM '.TICKET_TABLE.' ticket '
        .'left join (select count(attach.attach_id) as count, ticket_id from '.TICKET_ATTACHMENT_TABLE
            .' attach group by attach.ticket_id) as attach on (attach.ticket_id = ticket.ticket_id) '
        .'left join (select count(thread.id) as count, ticket_id from '.TICKET_THREAD_TABLE
            .' thread group by thread.ticket_id) as thread on (thread.ticket_id = ticket.ticket_id) '
        .'left join (select count(collab.id) as count, ticket_id from '.TICKET_COLLABORATOR_TABLE
            .' collab group by collab.ticket_id) as collab on (collab.ticket_id = ticket.ticket_id) '
         .' WHERE ticket.ticket_id IN ('.implode(',', db_input(array_keys($results_lav))).');';
    $ids_res = db_query($counts_sql);
    while ($row = db_fetch_array($ids_res)) {
        $results_lav[$row['ticket_id']] += $row;
    }
}

$results_dis = array();
while ($row = db_fetch_array($res_dis)) {
    $results_dis[$row['ticket_id']] = $row;
}
// Fetch attachment and thread entry counts
if ($results_dis) {
    $counts_sql = 'SELECT ticket.ticket_id, coalesce(attach.count, 0) as attachments, '
        .'coalesce(thread.count, 0) as thread_count, coalesce(collab.count, 0) as collaborators '
        .'FROM '.TICKET_TABLE.' ticket '
        .'left join (select count(attach.attach_id) as count, ticket_id from '.TICKET_ATTACHMENT_TABLE
            .' attach group by attach.ticket_id) as attach on (attach.ticket_id = ticket.ticket_id) '
        .'left join (select count(thread.id) as count, ticket_id from '.TICKET_THREAD_TABLE
            .' thread group by thread.ticket_id) as thread on (thread.ticket_id = ticket.ticket_id) '
        .'left join (select count(collab.id) as count, ticket_id from '.TICKET_COLLABORATOR_TABLE
            .' collab group by collab.ticket_id) as collab on (collab.ticket_id = ticket.ticket_id) '
         .' WHERE ticket.ticket_id IN ('.implode(',', db_input(array_keys($results_dis))).');';
    $ids_res = db_query($counts_sql);
    while ($row = db_fetch_array($ids_res)) {
        $results_dis[$row['ticket_id']] += $row;
    }
}


?>


<div class="clear"></div>
<div style="margin-bottom:5px; padding-top:5px;">
<div style="margin-left:10px;">
        <div class="pull-left flush-left">
            <h2><span style="font-size:100%; font-family:play;"><a href="<?php echo Format::htmlchars($_SERVER['REQUEST_URI']); ?>"
                title="<?php echo __('Refresh'); ?>"><i class="icon-refresh"></i>
            <?php echo $results_type.'</a> --  ';//.$showing;
            echo sprintf('<a class="export-csv no-pjax" href="?%s">%s</a>',
                Http::build_query(array(
                        'a' => 'export', 'h' => $hash,
                        'status' => $_REQUEST['status'])),
                __('Export'));
            echo '&nbsp;<i class="help-tip icon-question-sign" href="#export"></i>&nbsp;&nbsp;';
            ?> | <a <?php echo $id_sort; ?> href="tickets.php?sort=ID&order=<?php echo $negorder; ?><?php echo $qstr; ?>"
                    title="<?php echo sprintf(__('Sort by %s %s'), __('Ticket ID'), __($negorder)); ?>">Ordina per numero interno</a></span></h2>
        </div>
        <?php if ($thisstaff->getId()==1 OR $thisstaff->getId()==3) { //backend per me?>
        <div class="pull-left flush-left">
            <?php
            if ($thisstaff->canDeleteTickets()) { ?>
            <a id="tickets-delete" class="action-button pull-right tickets-action"
                href="#tickets/status/delete"><i class="icon-trash"></i> <?php echo __('Delete'); ?></a>
            <?php
            } ?>
            <?php
            if ($thisstaff->canManageTickets()) {
                //echo TicketStatus::status_options();
            }
            ?>
        </div>
        <?php } ?>
</div>
<div class="clear" style="margin-bottom:10px;"></div>
<form action="tickets.php" method="POST" name='tickets' id="tickets">
<?php csrf_token(); ?>
 <input type="hidden" name="a" value="mass_process" >
 <input type="hidden" name="do" id="action" value="" >
 <input type="hidden" name="status" value="<?php echo
 Format::htmlchars($_REQUEST['status'], true); ?>" >

 <!--QUI INIZIA LA TABELLA A 4 CELLE -->
  <?php if($_REQUEST['a']!='search' AND !isset($_REQUEST['advsid'])) { //SE NON E 'UNA RICERCA PROCEDI?>
 <?php if(strtolower($_REQUEST['status'])!="closed") { //SE IL TICKET NON E' CHIUSO PROCEDI?>
 <?php if(strtolower($_REQUEST['status'])!="disallineati") { //SE IL TICKET NON E' DISALLINEATO PROCEDI?>
 <table border="0" cellspacing="1" cellpadding="2" width="100%">
	 <tr style="background-color:transparent; border-radius:0px; -webkit-box-shadow: 0 0px 0px 0px; -moz-box-shadow: 0 0px 0px 0px; box-shadow: 0 0px 0px 0px;"><td style="width:75%; vertical-align: top;"><!--prima cella-->
	<div style="width: 100%; height:auto; overflow:auto; float: left;">
  <div>
	  <center><table width="100%" border="0" cellspacing="20px" cellpadding="10px" style="border-collapse: separate; border-spacing: 0px 10px; margin-top:-10px; margin-bottom:10px; background:transparent;">
<th class="liste_titre" colspan="11" style="width:100%;" align="left">

<?php if($thisstaff->getDeptId()==5 || $thisstaff->getDeptId()==6 ) {
  $query_aperti=substr($query_exp, 0, strpos( $query_exp, ' WHERE'));
	$query_aperti .= " WHERE ticket.status_id IN (1,7,22) ORDER BY ticket.created DESC LIMIT 0,100 ";
	$hash_aperti = md5($query_aperti);
    $_SESSION['search_'.$hash_aperti] = $query_aperti;

	echo sprintf('&nbsp;&nbsp;<a class="export-csv no-pjax" href="?%s">%s</a>',
                Http::build_query(array(
                        'a' => 'export', 'h' => $hash_aperti,
                        'who' => '3')),
                '<img src="../include/staff/img_new/clessidra.png">');
  }else{
  echo '<img src="../include/staff/img_new/clessidra.png">';
  }
  ?><strong style="font-size:15px; font-family:play; font-weight:bold;">&nbsp;Da lavorare nel c.c.&nbsp;

<?php if($thisstaff->getDeptId()==5 || $thisstaff->getDeptId()==6 ) {echo "Planner";}elseif($thisstaff->getDeptId()==7){echo "Laboratorio";}elseif($thisstaff->getDeptId()==8){echo "Magazzino";}else{echo "Screener";}?></strong></th>
     <tbody style="border-radius:25px;">
        <?php
        // Setup Subject field for display
        $subject_field = TicketForm::objects()->one()->getField('subject');
        $class = "row1";
        $total=0;
        if($res && ($num=count($results))):
            $ids=($errors && $_POST['tids'] && is_array($_POST['tids']))?$_POST['tids']:null;
            $pari=0;
            foreach ($results as $row) {
				//$ticketto=Ticket::lookup($row['ticket_id']);
				//$ticketto->pdfExport();

				//qui bisogna mettere l'if sugli stati ed sul compentence center??
				$ticket_color=$row['pc_sn']==1?"#ffff00":"#ffffff";
                $tag=$row['staff_id']?'assigned':'openticket';
                $flag=null;
                if($row['lock_id'])
                    $flag='locked';
                elseif($row['isoverdue'])
                    $flag='overdue';

                $lc='';
                if($showassigned) {
                    if($row['staff_id'])
                        $lc=sprintf('<span class="Icon staffAssigned">%s</span>',Format::truncate($row['staff'],40));
                    elseif($row['team_id'])
                        $lc=sprintf('<span class="Icon teamAssigned">%s</span>',Format::truncate($row['team'],40));
                    else
                        $lc=' ';
                }else{
                    $lc=Format::truncate($row['dept_name'],40);
                }
                $tid=$row['number'];

                $subject = Format::truncate($subject_field->display(
                    $subject_field->to_php($row['subject']) ?: $row['subject']
                ), 30);
                $threadcount=$row['thread_count'];
                if(!strcasecmp($row['state'],'open') && !$row['isanswered'] && !$row['lock_id']) {
                    $tid=sprintf('<b>%s</b>',$tid);
                }
                ?>
                <?php //CONTROLLO STATI AMMESSI
                if($row['status']=="Transferred" || $row['status']=="Planning" || $row['status']=="Riparato LAB" || $row['status']=="Ripreso in carico" || $row['status']=="Non risolto" || $row['status']=="Da validare PLN") {
                ?>

            <tr id="<?php echo $row['ticket_id']; ?>" <?php if ($row['group_last_name']=='GESTIONE_ATTESE'){echo 'style="background:#ff8000;"';}elseif($row['pc_sn']==1){echo 'style="background:#ffff00;"';}elseif($row['comm_id']=='xx'){echo 'style="background:#ff8000;"';}else{?>class="<?php echo ($pari % 2 == 0)?'pair':'impair' ?>"<?php }?>>
               <?php //if($thisstaff->canManageTickets()) {
               if ($row['imac']==1) { //backend per me

                    ?>
                <td align="center" class="nohover" width="1%" >
                    <img src="../images/imac.png">
                </td>
                <?php } else { ?>
                 <td align="center" width="1%" nowrap  >&nbsp;</td>
                 <?php }?>
                 <?php if ($thisstaff->getId()==1 OR $thisstaff->getId()==3) { //backend per me?>
                 <td align="center"  width="3%" class="nohover" >
                    <input class="ckb" type="checkbox" name="tids[]"
                        value="<?php echo $row['ticket_id']; ?>" <?php echo $sel?'checked="checked"':''; ?>>
                </td>
                <?php } ?>
                <td title="<?php echo $row['email']; ?>" width="1%" nowrap >
                  <a class="Icon <?php echo strtolower($row['source']); ?>Ticket ticketPreview"
                    title="<?php echo __('Preview Ticket'); ?>"
                    href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><span style="font-size:80%; font-family:play;"><?php echo "<strong>Ticket Nr:</strong><br>".$tid; ?></span></a></td>
                <td width="3%" nowrap ><strong style="font-weight:bold;">Ordine:</strong><br><span style="font-size:80%; font-family:play;"><?php echo $row['ref_num'];?></span></td>
                <td width="3%" nowrap ><strong style="font-weight:bold;">Transfer:</strong><br><span style="font-size:80%; font-family:play;"><?php echo $row['name']=='Poste'?date('d/m/Y H:i',$row['zz_date1']):Format::db_datetime($row['ticket_created']);?></span></td>
                <td width="3%" nowrap ><strong style="font-weight:bold;">Scadenza:</strong><br><span style="font-size:80%; font-family:play;"><?php echo Format::db_datetime($row['duedate']); ?></span></td>
                <td width="3%" nowrap ><strong style="font-weight:bold;">Cliente:</strong><br><span style="font-size:80%; font-family:play;"><?php echo $row['name']=='terzo'?'TERZO FORNITORE':Format::htmlchars(
                        Format::truncate($row['customer_middle_name'], 22, strpos($row['customer_middle_name'], '@'))); ?>&nbsp;</span></td>
                <!--<td width="40%" style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><a href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><strong>Subject:</strong><br><?php echo $subject; ?></a>
                </td>-->
                <td width="3%" nowrap ><strong style="font-weight:bold;">Stato in:</strong><br><span style="font-size:80%; font-family:play;"><?php echo ucfirst($row['status']);?></span></td>
                <td width="3%" nowrap ><strong style="font-weight:bold;">Termid:</strong><br><span style="font-size:80%; font-family:play;"><?php echo $row['cr']; ?></span></td>
                <td width="3%" nowrap ><strong style="font-weight:bold;">Gruppo:</strong><br><span style="font-size:80%; font-family:play;">
                <?php

                echo $row['helptopic'];
                ?>
                </span></td>
                <td width="3%" align="center" class="nohover" >
					<?php if($row['active']=="0" && $row['pc_flag']!="1"){echo '<img src="../images/inactive.png">';
					}elseif($row['active']=="1" && $row['status_sym']!="Sollecitato a Manutentore" && $row['pc_flag']!="1"){echo '<img src="../images/active.png">';
					}elseif($row['status_sym']=="Sollecitato a Manutentore"){echo '<img src="../images/sospeso.png">';
					}elseif($row['pc_flag']=="1"){echo '<img src="../images/disallineato.png">';
					}else{echo "&nbsp;";}?>
				</td>
            </tr>
            <?php } ?>
            <?php
            $pari++;
            } //end of while.
        else: //not tickets found!! set fetch error.
            $ferror=__('There are no tickets matching your criteria.');
        endif; ?>
    </tbody>
    <tfoot>
     <!--<tr>
        <td colspan="10">
            <?php if($res && $num && $thisstaff->canManageTickets()){ ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo '<i>';
                echo $ferror?Format::htmlchars($ferror):__('Query returned 0 results.');
                echo '</i>';
            } ?>
        </td>
     </tr>-->
    </tfoot>
    </table>
    </center>
    <?php
    if ($num>0) { //if we actually had any tickets returned.
        echo '<center><div>'.$pageNav->getPageLinks('primo').'</div></center>';
        /*echo sprintf('<a class="export-csv no-pjax" href="?%s">%s</a>',
                Http::build_query(array(
                        'a' => 'export', 'h' => $hash,
                        'status' => $_REQUEST['status'])),
                __('Export'));
        echo '&nbsp;<i class="help-tip icon-question-sign" href="#export"></i></div>';*/
    } ?>
  </div></div>
	 </td><td style="width:25%; vertical-align: top;"><!--seconda cella-->
	 <div style="width: 100%; height:auto; overflow:auto; float: left;">
  <div>
<center>
 <table width="100%" border="0" cellspacing="20px" cellpadding="10px" style="border-collapse: separate; border-spacing: 0px 10px; margin-top:-10px; margin-bottom:10px; background:transparent; ">
  <th class="liste_titre" colspan="2" align="left">
  <?php
  $query_attesa=substr($query_exp, 0, strpos( $query_exp, ' WHERE'));
	$query_attesa .= " WHERE ticket.status_id IN (21) ORDER BY ticket.created DESC LIMIT 0,100 ";
	$hash_attesa = md5($query_attesa);
    $_SESSION['search_'.$hash_attesa] = $query_attesa;
	echo sprintf('&nbsp;&nbsp;<a class="export-csv no-pjax" href="?%s">%s</a>',
                Http::build_query(array(
                        'a' => 'export', 'h' => $hash_attesa,
                        'who' => '3')),
                '<img src="../include/staff/img_new/time.png">');

  ?>

  <strong style="font-size:15px; font-family:play; font-weight:bold;">&nbsp;Blocchi</strong>


  </th>
     <tbody style="border-radius:25px;">
        <?php
        // Setup Subject field for display
        $subject_field = TicketForm::objects()->one()->getField('subject');
        $class = "row1";
        $total=0;



       if($res_sos && ($num=count($results_sos))):
            $ids=($errors && $_POST['tids'] && is_array($_POST['tids']))?$_POST['tids']:null;
            $pari=0;
            foreach ($results_sos as $row) {
				$ticket_color=$row['pc_sn']==1?"#ffff00":"#82AA63";
                $tag=$row['staff_id']?'assigned':'openticket';
                $flag=null;
                if($row['lock_id'])
                    $flag='locked';
                elseif($row['isoverdue'])
                    $flag='overdue';

                $lc='';
                if($showassigned) {
                    if($row['staff_id'])
                        $lc=sprintf('<span class="Icon staffAssigned">%s</span>',Format::truncate($row['staff'],40));
                    elseif($row['team_id'])
                        $lc=sprintf('<span class="Icon teamAssigned">%s</span>',Format::truncate($row['team'],40));
                    else
                        $lc=' ';
                }else{
                    $lc=Format::truncate($row['dept_name'],40);
                }
                $tid=$row['number'];

                $subject = Format::truncate($subject_field->display(
                    $subject_field->to_php($row['subject']) ?: $row['subject']
                ), 30);
                $threadcount=$row['thread_count'];
                if(!strcasecmp($row['state'],'open') && !$row['isanswered'] && !$row['lock_id']) {
                    $tid=sprintf('<b>%s</b>',$tid);
                }
                ?>
                <?php //CONTROLLO STATI AMMESSI
                if($row['status']=="Attesa cliente" || $row['status']=="Attesa preventivo") {
                ?>
             <tr id="<?php echo $row['ticket_id']; ?>" class="<?php echo ($pari % 2 == 0)?'pair':'impair' ?>">
                <td>
			     <center>
                 <table width="99%" border="0">

                 <tr>

                 <?php //if($thisstaff->canManageTickets()) {
               if ($row['imac']==1) { //backend per me

                    ?>
                <td align="center" width="20%" class="nohover"<img height="25px" src="../images/imac.png">
                </td>
                 <?php } else { ?>
                 <td align="center" nowrap  width="20%" >&nbsp;</td>
                 <?php }?>

                 <td align="center" nowrap  width="60%" colspan="2" ><strong style="font-weight:bold;">Cliente:&nbsp;</strong><span style="font-size:80%; font-family:play;"><?php echo $row['name']=='terzo'?'TERZO FORNITORE':Format::htmlchars(
                        Format::truncate($row['customer_middle_name'], 22, strpos($row['customer_middle_name'], '@'))); ?>&nbsp;</span></td>

                 <td align="center"  width="20%" class="nohover" >
					<?php if($row['active']=="0" && $row['pc_flag']!="1"){echo '<img src="../images/inactive.png">';
					}elseif($row['active']=="1" && $row['status_sym']!="Sollecitato a Manutentore" && $row['pc_flag']!="1"){echo '<img src="../images/active.png">';
					}elseif($row['status_sym']=="Sollecitato a Manutentore"){echo '<img src="../images/sospeso.png">';
					}elseif($row['pc_flag']=="1"){echo '<img src="../images/disallineato.png">';
					}else{echo "&nbsp;";}?>
				</td>

                 </tr>

                 <tr>

                 <td align="center" colspan="2"  width="50%" title="<?php echo $row['email']; ?>" nowrap >
                  <a class="Icon <?php echo strtolower($row['source']); ?>Ticket ticketPreview"
                    title="<?php echo __('Preview Ticket'); ?>"
                    href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><strong style="font-weight:bold;">Ticket Nr:<br></strong><span style="font-size:80%; font-family:play;"><?php echo $tid; ?></span></a></td>

                 <td align="center" nowrap  width="50%" colspan="2" ><strong style="font-weight:bold;">Ordine:<br></strong><span style="font-size:80%; font-family:play;"><?php echo $row['ref_num']; ?></span></td>

                 </tr>

                 <tr>
                 <td align="center" nowrap width="50%" colspan="2" ><strong style="font-weight:bold;">Transfer date:</strong><br><span style="font-size:80%; font-family:play;"><?php echo $row['name']=='Poste'?date('d/m/Y H:i',$row['zz_date1']):Format::db_datetime($row['ticket_created']);?></span></td>

                 <td align="center" colspan="2" width="50%" nowrap ><strong style="font-weight:bold;">Data scadenza:</strong><br><span style="font-size:80%; font-family:play;"><?php echo Format::db_datetime($row['duedate']); ?></span></td>

                 </tr>

                </table>
                </center>
                </td>
                </tr>
            <?php } ?>
            <?php
            $pari++;
            } //end of while.
        else: //not tickets found!! set fetch error.
            $ferror=__('There are no tickets matching your criteria.');
        endif; ?>


    </tbody>
    <tfoot>
     <!--<tr>
        <td colspan="5">
            <?php if($res_sos && $num && $thisstaff->canManageTickets()){ ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo '<i>';
                echo $ferror?Format::htmlchars($ferror):__('Query returned 0 results.');
                echo '</i>';
            } ?>
        </td>
     </tr>-->
    </tfoot>
    </table>
    </center>
    <?php
    if ($num>0) { //if we actually had any tickets returned.
        echo '<center><div><br>'.$pageNav_sos->getPageLinks('secondo').'</div></center>';
        /*echo sprintf('<a class="export-csv no-pjax" href="?%s">%s</a>',
                Http::build_query(array(
                        'a' => 'export', 'h' => $hash,
                        'status' => $_REQUEST['status'])),
                __('Export'));
        echo '&nbsp;<i class="help-tip icon-question-sign" href="#export"></i></div>';*/
    } ?>
  </div></div>
	 </td></tr>
	 <tr style="background-color:transparent; border-radius:0px; -webkit-box-shadow: 0 0px 0px 0px; -moz-box-shadow: 0 0px 0px 0px; box-shadow: 0 0px 0px 0px;"><td style="width:75%; vertical-align: top;"><!--terza cella-->
	 <div style="width: 100%; height:auto; overflow:auto; float: left;">
  <div>
	 <center>
 <table width="100%" border="0" cellspacing="20px" cellpadding="10px" style="border-collapse: separate; border-spacing: 0px 10px; margin-top:-10px; margin-bottom:10px; background:transparent; ">
    <th class="liste_titre" colspan="10" style="width:100%" align="left"><?php
  $query_assegnato=substr($query_exp, 0, strpos( $query_exp, ' WHERE'));
	$query_assegnato .= " WHERE ticket.status_id IN (23) ORDER BY ticket.created DESC LIMIT 0,100 ";
	$hash_assegnato = md5($query_assegnato);
    $_SESSION['search_'.$hash_assegnato] = $query_assegnato;
	echo sprintf('&nbsp;&nbsp;<a class="export-csv no-pjax" href="?%s">%s</a>',
                Http::build_query(array(
                        'a' => 'export', 'h' => $hash_assegnato,
                        'who' => '3')),
                '<img src="../include/staff/img_new/work.png">');

  ?><strong style="font-size:15px; font-family:play; font-weight:bold;">Ticket in lavorazione</strong>

    </th>
     <tbody style="border-radius:25px;">
        <?php
        // Setup Subject field for display
        $subject_field = TicketForm::objects()->one()->getField('subject');
        $class = "row1";
        $total=0;
        if($res_lav && ($num=count($results_lav))):
            $ids=($errors && $_POST['tids'] && is_array($_POST['tids']))?$_POST['tids']:null;

            $pari=0;
            foreach ($results_lav as $row) {
				//qui bisogna mettere l'if sugli stati ed sul compentence center??
				$ticket_color=$row['pc_sn']==1?"#ffff00":"#ffffff";
                $tag=$row['staff_id']?'assigned':'openticket';
                $flag=null;
                if($row['lock_id'])
                    $flag='locked';
                elseif($row['isoverdue'])
                    $flag='overdue';

                $lc='';
                if($showassigned) {
                    if($row['staff_id'])
                        $lc=sprintf('<span class="Icon staffAssigned">%s</span>',Format::truncate($row['staff'],40));
                    elseif($row['team_id'])
                        $lc=sprintf('<span class="Icon teamAssigned">%s</span>',Format::truncate($row['team'],40));
                    else
                        $lc=' ';
                }else{
                    $lc=Format::truncate($row['dept_name'],40);
                }
                $tid=$row['number'];

                $subject = Format::truncate($subject_field->display(
                    $subject_field->to_php($row['subject']) ?: $row['subject']
                ), 30);
                $threadcount=$row['thread_count'];
                if(!strcasecmp($row['state'],'open') && !$row['isanswered'] && !$row['lock_id']) {
                    $tid=sprintf('<b>%s</b>',$tid);
                }
                ?>

            <?php //CONTROLLO STATI AMMESSI
                if($row['status']=="In carico al magazzino" ||
                $row['status']=="In carico al laboratorio" ||
                $row['status']=="In carico Tec. LAB" ||
                $row['status']=="Da validare LAB" ||
                $row['status']=="Non riparato" ||
                $row['status']=="In attesa parti" ||
                $row['status']=="Assegnato"
                ) {
                ?>
           <!--<tr id="<?php echo $row['ticket_id']; ?>" <?php if($row['pc_sn']==1){echo 'style="background:#ffff00;"';}else{?>class="<?php echo ($pari % 2 == 0)?'pair':'impair' ?>"<?php }?>>-->
           <tr id="<?php echo $row['ticket_id']; ?>" <?php if ($row['group_last_name']=='GESTIONE_ATTESE'){echo 'style="background:#ff8000;"';}elseif($row['pc_sn']==1){echo 'style="background:#ffff00;"';}elseif($row['comm_id']=='xx'){echo 'style="background:#ff8000;"';}else{?>class="<?php echo ($pari % 2 == 0)?'pair':'impair' ?>"<?php }?>>
                <?php //if($thisstaff->canManageTickets()) {
               if ($row['imac']==1) { //backend per me

                    ?>
                <td align="center" class="nohover" width="1%">
                    <img src="../images/imac.png">
                </td>
                <?php } else { ?>
                 <td align="center" width="1%" nowrap >&nbsp;</td>
                 <?php }?>
                <td title="<?php echo $row['email']; ?>" width="3%" nowrap >
                  <a class="Icon <?php echo strtolower($row['source']); ?>Ticket ticketPreview"
                    title="<?php echo __('Preview Ticket'); ?>"
                    href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><span style="font-size:80%; font-family:play;"><?php echo "<strong>Ticket Nr:</strong><br>".$tid; ?></span></a></td>
                <td width="3%" nowrap ><strong style="font-weight:bold;">Ordine:</strong><br><span style="font-size:80%; font-family:play;"><?php echo $row['ref_num'];?></span></td>
                <td width="3%" nowrap ><strong style="font-weight:bold;">Transfer:</strong><br><span style="font-size:80%; font-family:play;"><?php echo $row['name']=='Poste'?date('d/m/Y H:i',$row['zz_date1']):Format::db_datetime($row['ticket_created']);?></span></td>
                <td width="3%" nowrap ><strong style="font-weight:bold;">Scadenza:</strong><br><span style="font-size:80%; font-family:play;"><?php echo Format::db_datetime($row['duedate']); ?></span></td>
                <td width="3%" nowrap ><strong style="font-weight:bold;">Cliente:</strong><br><span style="font-size:80%; font-family:play;"><?php echo $row['name']=='terzo'?'TERZO FORNITORE':Format::htmlchars(
                        Format::truncate($row['customer_middle_name'], 22, strpos($row['customer_middle_name'], '@'))); ?>&nbsp;</span></td>
                <!--<td width="40%" style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><a href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><strong>Subject:</strong><br><?php echo $subject; ?></a>
                </td>-->
                <td width="3%" nowrap ><strong style="font-weight:bold;">Stato in:</strong><br><span style="font-size:80%; font-family:play;"><?php echo ucfirst($row['status'])!='Assegnato'?ucfirst($row['status']):ucfirst($row['status']).' ('.$row['lastname'].')';?></span></td>
                <td width="3%" nowrap ><strong style="font-weight:bold;">Termid:</strong><br><span style="font-size:80%; font-family:play;"><?php echo $row['cr']; ?></span></td>
                <td width="3%" nowrap ><strong style="font-weight:bold;">Gruppo:</strong><br><span style="font-size:80%; font-family:play;">

                <?php

                echo $row['helptopic'];
                ?></span>
                </td>
                <td width="3%" align="center" class="nohover" >
					<?php if($row['active']=="0" && $row['pc_flag']!="1"){echo '<img src="../images/inactive.png">';
					}elseif($row['active']=="1" && $row['status_sym']!="Sollecitato a Manutentore" && $row['pc_flag']!="1"){echo '<img src="../images/active.png">';
					}elseif($row['status_sym']=="Sollecitato a Manutentore"){echo '<img src="../images/sospeso.png">';
					}elseif($row['pc_flag']=="1"){echo '<img src="../images/disallineato.png">';
					}else{echo "&nbsp;";}?>
				</td>
            </tr>
            <?php } ?>
            <?php
            $pari++;
            } //end of while.
        else: //not tickets found!! set fetch error.
            $ferror=__('There are no tickets matching your criteria.');
        endif; ?>
    </tbody>
    <tfoot>
     <!--<tr>
        <td colspan="10">
            <?php if($res_lav && $num && $thisstaff->canManageTickets()){ ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo '<i>';
                echo $ferror?Format::htmlchars($ferror):__('Query returned 0 results.');
                echo '</i>';
            } ?>
        </td>
     </tr>-->
    </tfoot>
    </table>
    </center>

    <?php
    if ($num>0) { //if we actually had any tickets returned.
        echo '<center><div>'.$pageNav_lav->getPageLinks('terzo').'</div></center>';
        /*echo sprintf('<a class="export-csv no-pjax" href="?%s">%s</a>',
                Http::build_query(array(
                        'a' => 'export', 'h' => $hash,
                        'status' => $_REQUEST['status'])),
                __('Export'));
        echo '&nbsp;<i class="help-tip icon-question-sign" href="#export"></i></div>';*/
    } ?>
   </div></div>
	 </td><td style="width:25%; vertical-align: top;"><!--quarta cella-->
	 <div style="width: 100%; height:auto; overflow:auto; float: left;">
  <div>
	 <center>
 <table width="100%" border="0" cellspacing="20px" cellpadding="10px" style="border-collapse: separate; border-spacing: 0px 10px; margin-top:-10px; margin-bottom:10px; background:transparent; ">
  <th class="liste_titre" colspan="2" align="left">
	   <?php
  $query_recesso=substr($query_exp, 0, strpos( $query_exp, ' WHERE'));
	$query_recesso .= " WHERE ticket.status_id IN (8) ORDER BY ticket.created DESC LIMIT 0,100 ";
	//mail('domenico.zavattolo@service-tech.org','query', $query_recesso);
	$hash_recesso = md5($query_recesso);
    $_SESSION['search_'.$hash_recesso] = $query_recesso;
	echo sprintf('&nbsp;&nbsp;<a class="export-csv no-pjax" href="?%s">%s</a>',
                Http::build_query(array(
                        'a' => 'export', 'h' => $hash_recesso,
                        'who' => '3')),
                '<img src="../include/staff/img_new/time.png">');

  ?>
<strong style="font-size:15px; font-family:play; font-weight:bold;">Recessi</strong></th>
     <tbody style="border-radius:25px;">
        <?php
        // Setup Subject field for display
        $subject_field = TicketForm::objects()->one()->getField('subject');
        $class = "row1";
        $total=0;
        if($res_dis && ($num=count($results_dis))):
            $ids=($errors && $_POST['tids'] && is_array($_POST['tids']))?$_POST['tids']:null;
            $pari=0;
            foreach ($results_dis as $row) {
				$ticket_color=$row['pc_sn']==1?"#ffff00":"#82AA63";
                $tag=$row['staff_id']?'assigned':'openticket';
                $flag=null;
                //echo $row['status'];
                if($row['lock_id'])
                    $flag='locked';
                elseif($row['isoverdue'])
                    $flag='overdue';

                $lc='';
                if($showassigned) {
                    if($row['staff_id'])
                        $lc=sprintf('<span class="Icon staffAssigned">%s</span>',Format::truncate($row['staff'],40));
                    elseif($row['team_id'])
                        $lc=sprintf('<span class="Icon teamAssigned">%s</span>',Format::truncate($row['team'],40));
                    else
                        $lc=' ';
                }else{
                    $lc=Format::truncate($row['dept_name'],40);
                }
                $tid=$row['number'];

                $subject = Format::truncate($subject_field->display(
                    $subject_field->to_php($row['subject']) ?: $row['subject']
                ), 30);
                $threadcount=$row['thread_count'];
                if(!strcasecmp($row['state'],'open') && !$row['isanswered'] && !$row['lock_id']) {
                    $tid=sprintf('<b>%s</b>',$tid);
                }
                ?>
           <?php //CONTROLLO STATI AMMESSI
                if($row['status']=="Rifiutato") {
                ?>
               <tr id="<?php echo $row['ticket_id']; ?>" class="<?php echo ($pari % 2 == 0)?'pair':'impair' ?>">
                <td>
			     <center>
                 <table width="99%" border="0" >

                 <tr>
                 <?php //if($thisstaff->canManageTickets()) {
               if ($row['imac']==1) { //backend per me

                    ?>
                <td align="center" width="20%" class="nohover" >
                    <img src="../images/imac.png">
                </td>
                 <?php } else { ?>
                 <td align="center" nowrap  width="20%" >&nbsp;</td>
                 <?php }?>

                 <td align="center" nowrap  width="60%" colspan="2" ><strong style="font-weight:bold;">Cliente:&nbsp;</strong><span style="font-size:80%; font-family:play;"><?php echo $row['name']=='terzo'?'TERZO FORNITORE':Format::htmlchars(
                        Format::truncate($row['customer_middle_name'], 22, strpos($row['customer_middle_name'], '@'))); ?>&nbsp;</span></td>

                 <td align="center"  width="20%" class="nohover" >
					<?php if($row['active']=="0" && $row['pc_flag']!="1"){echo '<img src="../images/inactive.png">';
					}elseif($row['active']=="1" && $row['status_sym']!="Sollecitato a Manutentore" && $row['pc_flag']!="1"){echo '<img src="../images/active.png">';
					}elseif($row['status_sym']=="Sollecitato a Manutentore"){echo '<img src="../images/sospeso.png">';
					}elseif($row['pc_flag']=="1"){echo '<img src="../images/disallineato.png">';
					}else{echo "&nbsp;";}?>
				</td>

                 </tr>

                 <tr>

                 <td align="center" colspan="2"  width="50%" title="<?php echo $row['email']; ?>" nowrap >
                  <a class="Icon <?php echo strtolower($row['source']); ?>Ticket ticketPreview"
                    title="<?php echo __('Preview Ticket'); ?>"
                    href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><strong style="font-weight:bold;">Ticket Nr:<br></strong><span style="font-size:80%; font-family:play;"><?php echo $tid; ?></span></a></td>

                 <td align="center" nowrap  width="50%" colspan="2" ><strong style="font-weight:bold;">Ordine:<br></strong><span style="font-size:80%; font-family:play;"><?php echo $row['ref_num']; ?></span></td>

                 </tr>

                 <tr>
                 <td align="center" nowrap width="50%" colspan="2" ><strong style="font-weight:bold;">Transfer date:</strong><br><span style="font-size:80%; font-family:play;"><?php echo $row['name']=='Poste'?date('d/m/Y H:i',$row['zz_date1']):Format::db_datetime($row['ticket_created']);?></span></td>

                 <td align="center" colspan="2" width="50%" nowrap ><strong style="font-weight:bold;">Data scadenza:</strong><br><span style="font-size:80%; font-family:play;"><?php echo Format::db_datetime($row['duedate']); ?></span></td>

                 </tr>
                </table>
                </center>
                </td>
                </tr>
            <?php } ?>
            <?php
            $pari++;
            } //end of while.
        else: //not tickets found!! set fetch error.
            $ferror=__('There are no tickets matching your criteria.');
        endif; ?>
    </tbody>
    <tfoot>
     <!--<tr>
        <td colspan="5">
            <?php if($res_dis && $num && $thisstaff->canManageTickets()){ ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo '<i>';
                echo $ferror?Format::htmlchars($ferror):__('Query returned 0 results.');
                echo '</i>';
            } ?>
        </td>
     </tr>-->
    </tfoot>
    </table>
    </center>
    <?php
    if ($num>0) { //if we actually had any tickets returned.
        echo '<center><div><br>'.$pageNav_dis->getPageLinks('quarto').'</div></center>';
        /*echo sprintf('<a class="export-csv no-pjax" href="?%s">%s</a>',
                Http::build_query(array(
                        'a' => 'export', 'h' => $hash,
                        'status' => $_REQUEST['status'])),
                __('Export'));
        echo '&nbsp;<i class="help-tip icon-question-sign" href="#export"></i></div>';*/
    } ?>
  </div></div>
    </form>
</div>
	 </td></tr>
</table>
<!--QUI FINISCE LA TABELLA A 4 CELLE-->
<?php } //FINE CONTROLLO DISALLINEATI?>
<?php } //FINE CONTROLLO CHIUSO?>
<?php } //FINE CONTROLLO NON E' UNA RICERCA?>
<!--CHIUSO-->
<?php //SE E' CHIUSO O PROVIENE DA UNA RICERCA
                if(strtolower($_REQUEST['status'])=="closed" || $_REQUEST['a']=='search' || isset($_REQUEST['advsid']) || strtolower($_REQUEST['status'])=="disallineati") {
                ?>
<div style="width: 100%; height:auto; overflow:auto; float: left;">
  <div>

 <center><table width="100%" border="0" cellspacing="20px" cellpadding="10px" style="border-collapse: separate; border-spacing: 0px 10px; margin-top:-10px; margin-bottom:10px; background:transparent;">
   <th class="liste_titre" colspan="<?php echo $thisstaff->getDeptId()==6?15:11;?>" style="width:100%" align="left"><img src="../images/dlnelcc_2.png"><strong style="font-size:15px; font-family:play; font-weight:bold;"><?php if(strtolower($_REQUEST['status'])==="closed" AND $_REQUEST['a']!='search' AND !isset($_REQUEST['advsid'])) { echo "&nbsp;Ticket Chiusi"; } if($_REQUEST['a']=='search' || isset($_REQUEST['advsid'])) { echo "&nbsp;Risultati della ricerca"; }?></strong></th>
     <tbody style="border-radius:25px;">
               <?php
        // Setup Subject field for display
        $subject_field = TicketForm::objects()->one()->getField('subject');
        $class = "row1";
        $total=0;
        if($res_exp && ($num=count($results_exp))):
            $ids=($errors && $_POST['tids'] && is_array($_POST['tids']))?$_POST['tids']:null;
            $pari=0;
            foreach ($results_exp as $row) {
				//qui bisogna mettere l'if sugli stati ed sul compentence center??
				//$ticket_color=$row['pc_sn']==1?"#ffff00":"#ffffff";
                $tag=$row['staff_id']?'assigned':'openticket';
                $flag=null;
                if($row['lock_id'])
                    $flag='locked';
                elseif($row['isoverdue'])
                    $flag='overdue';

                $lc='';
                if($showassigned) {
                    if($row['staff_id'])
                        $lc=sprintf('%s',explode(' ',Format::truncate($row['staff'],40), 2)[1]);
                    elseif($row['team_id'])
                        $lc=sprintf('<span class="Icon teamAssigned">%s</span>',Format::truncate($row['team'],40));
                    else
                        $lc=' ';
                }else{
                    $lc=Format::truncate($row['dept_name'],40);
                }
                $tid=$row['number'];

                $subject = Format::truncate($subject_field->display(
                    $subject_field->to_php($row['subject']) ?: $row['subject']
                ), 30);
                $threadcount=$row['thread_count'];
                if(!strcasecmp($row['state'],'open') && !$row['isanswered'] && !$row['lock_id']) {
                    $tid=sprintf('<b>%s</b>',$tid);
                }
                ?>

         <tr id="<?php echo $row['ticket_id']; ?>" <?php if($row['pc_sn']==1){echo 'style="background:#ffff00;"';}else{?>class="<?php echo ($pari % 2 == 0)?'pair':'impair' ?>"<?php }?>>
                <?php //if($thisstaff->canManageTickets()) {
               if ($row['imac']==1) { //backend per me

                    ?>
                <td align="center" class="nohover" width="1%" >
                    <img src="../images/imac.png">
                </td>

                <?php } else { ?>
                 <td align="center" width="1%" nowrap >&nbsp;</td>
                 <?php }?>
                 <?php if ($thisstaff->getId()==1 OR $thisstaff->getId()==3) { //backend per me?>
                 <?php csrf_token(); ?>
                 <td align="center"  width="3%" class="nohover" >
                    <input class="ckb" type="checkbox" name="tids[]"
                        value="<?php echo $row['ticket_id']; ?>" <?php echo $sel?'checked="checked"':''; ?>>
                </td>
                <?php } ?>
                <td title="<?php echo $row['email']; ?>" width="3%" nowrap >
                   <?php if ($flag) { ?> <a class="Icon <?php echo $flag; ?>Ticket ticketPreview"
                    title="<?php echo __('Preview Ticket'); ?>"
                    href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><?php echo '<strong style="font-weight:bold;">Ticket Nr:</strong><br><span style="font-size:80%; font-family:play;">'.$tid.'</span>'; ?></a><?php }else{?>
				<a class="Icon <?php echo strtolower($row['source']); ?>Ticket ticketPreview"
                    title="<?php echo __('Preview Ticket'); ?>"
                    href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><?php echo '<strong style="font-weight:bold;">Ticket Nr:</strong><br><span style="font-size:80%; font-family:play;">'.$tid.'</span>'; ?></a><?php }?></td>
                <td width="3%" nowrap ><strong style="font-weight:bold;">Ordine:</strong><br><span style="font-size:80%; font-family:play;"><?php echo $row['ref_num'];?></span></td>
                <td width="3%" nowrap ><strong style="font-weight:bold;">Transfer date:</strong><br><span style="font-size:80%; font-family:play;"><?php echo $row['name']=='Poste'?date('d/m/Y H:i',$row['zz_date1']):Format::db_datetime($row['ticket_created']);?></span></td>
                <td width="3%" nowrap ><strong style="font-weight:bold;">Data scadenza:</strong><br><span style="font-size:80%; font-family:play;"><?php echo Format::db_datetime($row['duedate']); ?></span></td>
                <td width="3%" nowrap ><strong style="font-weight:bold;">Cliente:</strong><br><span style="font-size:80%; font-family:play;"><?php echo $row['name']=='terzo'?'TERZO FORNITORE':Format::htmlchars(
                        Format::truncate($row['customer_middle_name'], 22, strpos($row['customer_middle_name'], '@'))); ?>&nbsp;</td>

                <td width="3%" nowrap ><strong style="font-weight:bold;">Stato interno:</strong><br><span style="font-size:80%; font-family:play;"><?php echo ucfirst($row['status'])!='Assegnato'?ucfirst($row['status']):ucfirst($row['status']).' ('.$row['lastname'].')';?></span></td>
                <td width="3%" nowrap ><strong style="font-weight:bold;">Termid:</strong><br><span style="font-size:80%; font-family:play;"><?php echo  $row['cr'];?></span></td>
                <td width="3%" nowrap ><strong style="font-weight:bold;">Gruppo:</strong><br><span style="font-size:80%; font-family:play;">
                <?php

                echo $row['helptopic'];
                ?>
                </span></td>
                <?php if(strtolower($_REQUEST['status'])=="closed"){?>
                <td width="3%" nowrap >
                        <a <?php echo $staff_sort; ?> href="tickets.php?sort=staff&order=<?php echo $negorder; ?><?php echo $qstr; ?>"
                            title="<?php echo sprintf(__('Sort by %s %s'), __("Closing Agent's Name"), __($negorder)); ?>"><strong style="font-weight:bold;"><?php echo __('Closed By').':'; ?></strong></a><br></td>
                <?php }?>
                <?php if(($thisstaff->getId()==78 || $thisstaff->getId()==79) AND strtolower($row['status'])=='risolto' AND strtolower($_REQUEST['a'])=="search") {?>

                <form action="tickets.php" method="POST" name='modifica_valori' id="modifica_valori">
                <?php csrf_token(); ?>
                <input type="hidden" name="a" value="valori_economici" >
                <input type="hidden" name="c" value="costi" >
                <input type="hidden" name="ticket_id" value="<?php echo $row['ticket_id']; ?>" >
				<td width="3%" nowrap><strong style="font-weight:bold;">Prezzo tecnico</strong><br><span style="font-size:80%; font-family:play;">
				<input type="text" name="costo_interno" value="<?php echo ($row['costo_int']===null or empty($row['costo_int']))?0:$row['costo_int'];?>" maxlength="7" size="7">
				</td>
				<td width="3%" nowrap><strong style="font-weight:bold;">Costo ditta</strong><br><span style="font-size:80%; font-family:play;">
				<input type="text" name="costo_esterno" value="<?php echo ($row['costo_ext']===null or empty($row['costo_ext']))?0:$row['costo_ext'];?>"  maxlength="7" size="7">
				</td>
				<td width="3%" nowrap><strong style="font-weight:bold;">Ricavi</strong><br><span style="font-size:80%; font-family:play;">
				<input type="text" name="prezzo" value="<?php echo ($row['prezzo']===null or empty($row['prezzo']))?0:$row['prezzo'];?>" maxlength="7" size="7">
				</td>
				<td width="3%" nowrap><br>
				<input style="font-family: play;
				font-size: 12px;
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
    box-shadow: 2px 2px 3px #DDD;" type="submit" value="Salva" id="<?php echo $row['ticket_id']; ?>">
                </form>
				</td>

				<?php }?>

				<?php if($thisstaff->getDeptId()==6 AND strtolower($_REQUEST['a'])=="search" AND strtolower($row['status'])!='risolto' AND strtolower($row['status'])!='rifiutato') {?>
                <form action="tickets.php" method="POST" name='modifica_valori' id="modifica_valori">
                <?php csrf_token(); ?>
                <input type="hidden" name="a" value="valori_economici" >
                <input type="hidden" name="d" value="ass" >
                <input type="hidden" name="ticket_id" value="<?php echo $row['ticket_id']; ?>" >
				<td width="3%" nowrap><strong style="font-weight:bold;">Assegna</strong><br><span style="font-size:80%; font-family:play;">
				<select id="assegnamento" name="assegnamento">
				<option value="">&mdash; <?php echo __('Anyone');?> &mdash;</option>
                <!--<option value="s0">&mdash; <?php echo __('Unassigned');?> &mdash;</option>-->
                <?php
                if(($users=Staff::getStaffMembers(true))) {
                    foreach($users as $id => $name) {
                        if (in_array($id,array(1,3,5,14,63,77,78,79)))
                            continue;
                        $k="$id";
                        echo sprintf('<option value="%s">%s</option>', $k, $name);
                    }
                }
                ?>
            </select>
				</td>
                                <td width="3%" nowrap><strong style="font-weight:bold;">Email</strong><br><input name="invio_email" value="1" type="checkbox"></td>
				<td width="3%" nowrap><br>
				<input style="font-family: play;
				font-size: 12px;
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
    box-shadow: 2px 2px 3px #DDD;" type="submit" value="Salva" id="<?php echo $row['ticket_id']; ?>">
                </form>
				</td>
        <td width="3%" align="center" class="nohover" ></td>

				<?php }?>
				<td width="3%" align="center" class="nohover" >
					<?php if($row['active']=="0" && $row['pc_flag']!="1"){echo '<img src="../images/inactive.png">';
					}elseif($row['active']=="1" && $row['status_sym']!="Sollecitato a Manutentore" && $row['pc_flag']!="1"){echo '<img src="../images/active.png">';
					}elseif($row['status_sym']=="Sollecitato a Manutentore"){echo '<img src="../images/sospeso.png">';
					}elseif($row['pc_flag']=="1"){echo '<img src="../images/disallineato.png">';
					}else{echo "&nbsp;";}?>
				</td>
            </tr>

            <?php
            $pari++;
            } //end of while.
        else: //not tickets found!! set fetch error.
            $ferror=__('There are no tickets matching your criteria.');
        endif; ?>
    </tbody>
    <tfoot>
    <tr>
        <td colspan="10"><h2><span style="font-size:100%; font-family:play;">
            <?php if($res && $num && $thisstaff->canManageTickets()){ ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo '<i>';
                echo $ferror?Format::htmlchars($ferror):__('Query returned 0 results.');
                echo '</i>';
            } ?>
        </span></h2></td>
     </tr>
    </tfoot>
    </table>
    </center>

    <?php
    if ($num>0) { //if we actually had any tickets returned.
        echo '<center><div>'.$pageExp->getPageLinks('primo').'</div></center>';
        /*echo sprintf('<a class="export-csv no-pjax" href="?%s">%s</a>',
                Http::build_query(array(
                        'a' => 'export', 'h' => $hash,
                        'status' => $_REQUEST['status'])),
                __('Export'));
        echo '&nbsp;<i class="help-tip icon-question-sign" href="#export"></i></div>';*/
    } ?>
  </div></div>

<?php } ?>
<!--FINE CHIUSO-->


<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="mark_overdue-confirm">
        <?php echo __('Are you sure you want to flag the selected tickets as <font color="red"><b>overdue</b></font>?');?>
    </p>
    <div><?php echo __('Please confirm to continue.');?></div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" value="<?php echo __('No, Cancel');?>" class="close">
        </span>
        <span class="buttons pull-right">
            <input type="button" value="<?php echo __('Yes, Do it!');?>" class="confirm">
        </span>
     </p>
    <div class="clear"></div>
</div>

<div class="dialog" style="display:none;" id="advanced-search">
    <h3><?php echo __('Advanced Ticket Search');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <form action="tickets.php" method="post" id="search" name="search">
        <input type="hidden" name="a" value="search">
        <fieldset class="query">
            <input type="input" id="query" name="query" size="20" placeholder="<?php echo __('Keywords') . ' &mdash; ' . __('Optional'); ?>">
        </fieldset>
        <fieldset class="span6">
            <label for="statusId"><?php echo __('Statuses');?>:</label>
            <select id="statusId" name="statusId">
                 <option value="">&mdash; <?php echo __('Any Status');?> &mdash;</option>
                <?php
                $stati_nascosti=array(3,8,9,10,11,12,13,14,15,16,17,18,19,20);
                foreach (TicketStatusList::getStatuses(
                            array('states' => array('open', 'closed'))) as $s) {
						if (!in_array($s->getId(),$stati_nascosti))
                    echo sprintf('<option data-state="%s" value="%d">%s</option>',
                            $s->getState(), $s->getId(), __($s->getName()));
                }
                ?>
            </select>
        </fieldset>
        <!--
        <fieldset class="span6">
            <label for="deptId"><?php echo __('Departments');?>:</label>
            <select id="deptId" name="deptId">
                <option value="">&mdash; <?php echo __('All Departments');?> &mdash;</option>
                <?php
                $ammessi=array('6','7','8','9');
                if(($mydepts = $thisstaff->getDepts()) && ($depts=Dept::getDepartments())) {
                    foreach($depts as $id =>$name) {
                        if(!in_array($id, $ammessi)) continue;
                        echo sprintf('<option value="%d">%s</option>', $id, $name);
                    }
                }
                ?>
            </select>
        </fieldset>

        <fieldset class="span6">
            <label for="clienteId">Clienti:</label>
            <select id="clienteId" name="clienteId">
                 <option value="">&mdash; Tutti i clienti &mdash;</option>
                <?php
                    $clienti = db_query('select id, name from ost_user');
                    while ($row = db_fetch_array($clienti)) {
                    echo sprintf('<option value="%d">%s</option>', $row['id'], $row['name']);
                    }
                 ?>
            </select>
        </fieldset>
        <fieldset class="span6">
            <label for="flag"><?php echo __('Flags');?>:</label>
            <select id="flag" name="flag">
                 <option value="">&mdash; <?php echo __('Any Flags');?> &mdash;</option>
                 <?php
                 if (!$cfg->showAnsweredTickets()) { ?>
                 <option data-state="open" value="answered"><?php echo __('Answered');?></option>
                 <?php
                 } ?>
                 <option data-state="open" value="overdue"><?php echo __('Overdue');?></option>
            </select>
        </fieldset>-->
        <fieldset class="span6">
            <label for="utopicId">Aziende:</label>
            <select id="utopicId" name="utopicId" tabindex="1">
                <option value="0" selected >&mdash; Tutte &mdash;</option>
                <option value="(12,13,14,15,16,17,39,43,44)">Nexi</option>
                <option value="(18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38)">Coopersystem</option>
                <option value="(40,41,42)">Sisal</option>
                <option value="(45,46,47,48,49,50,51,52,53,54,55,56,57,58,72,75)">Q8</option>
                <option value="(65,66,67,68,69,70,71,73,74,76,77,80,82)">Yamamay</option>
                <option value="(60,61,78,59)">B2X Care</option>
                <option value="(84,85,86,87,88)">CSE</option>
                <option value="(62,63,64,81)">WEB-KORNER</option>
                <option value="(83)">EXTRA</option>
            </select>
        </fieldset>
        <fieldset class="owner span6">
            <label for="assignee"><?php echo __('Assigned To');?>:</label>
            <select id="assignee" name="assignee">
                <option value="">&mdash; <?php echo __('Anyone');?> &mdash;</option>
                <option value="s0">&mdash; <?php echo __('Unassigned');?> &mdash;</option>
                <option value="s<?php echo $thisstaff->getId(); ?>"><?php echo __('Me');?></option>
                <?php
                if(($users=Staff::getStaffMembers(true))) {
                    echo '<OPTGROUP label="'.sprintf(__('Agents (%d)'),count($users)-1).'">';
                    foreach($users as $id => $name) {

                        if (in_array($id,array(1,3,5,14,63,77,78,79)))
                            continue;
                        $k="s$id";
                        echo sprintf('<option value="%s">%s</option>', $k, $name);
                    }
                    echo '</OPTGROUP>';
                }
    /*
                if(($teams=Team::getTeams())) {
                    echo '<OPTGROUP label="'.__('Teams').' ('.count($teams).')">';
                    foreach($teams as $id => $name) {
                        $k="t$id";
                        echo sprintf('<option value="%s">%s</option>', $k, $name);
                    }
                    echo '</OPTGROUP>';
                }*/
                ?>
            </select>
        </fieldset>
        <fieldset class="span6">
            <label for="topicId"><?php echo __('Help Topics');?>:</label>
            <select id="topicId" name="topicId[]" multiple="multiple" tabindex="1">
                <option value="0" selected >&mdash; <?php echo __('All Help Topics');?> &mdash;</option>
                <?php
                if($topics=Topic::getHelpTopics()) {
                    foreach($topics as $id =>$name)
                        echo sprintf('<option value="%d" >%s</option>', $id, $name);
                }
                ?>
            </select>
        </fieldset>
        <fieldset class="owner span6">
            <label for="staffId"><?php echo __('Closed By');?>:</label>
            <select id="staffId" name="staffId">
                <option value="0">&mdash; <?php echo __('Anyone');?> &mdash;</option>
                <option value="<?php echo $thisstaff->getId(); ?>"><?php echo __('Me');?></option>
                <?php
                if(($users=Staff::getStaffMembers(true))) {
                    foreach($users as $id => $name){
                        if (in_array($id,array(1,3,5,14,63,77,78,79)))
                            continue;
                        echo sprintf('<option value="%d">%s</option>', $id, $name);
                    }
                }
                ?>
            </select>
        </fieldset>
        <fieldset class="date_range">
            <label><?php echo __('Date Range').' &mdash; '.__('Create Date');?>:</label>
            <input class="dp" type="input" size="20" name="startDate">
            <span class="between"><?php echo __('TO');?></span>
            <input class="dp" type="input" size="20" name="endDate">
        </fieldset>
        <fieldset class="date_range">
            <label><?php echo __('Date Range').' &mdash; Data chiusura';?>:</label>
            <input class="dp" type="input" size="20" name="startDate_c">
            <span class="between"><?php echo __('TO');?></span>
            <input class="dp" type="input" size="20" name="endDate_c">
        </fieldset>
        <?php
        /*
        $tform = TicketForm::objects()->one();
        echo $tform->getForm()->getMedia();
        foreach ($tform->getInstance()->getFields() as $f) {
            if (!$f->hasData())
                continue;
            elseif (!$f->getImpl()->hasSpecialSearch())
                continue;
            ?><fieldset class="span6">
            <label><?php echo $f->getLabel(); ?>:</label><div><?php
                     $f->render('search'); ?></div>
            </fieldset>
        <?php } */?>
        <hr/>
        <div id="result-count" class="clear"></div>
        <p>
            <span class="buttons pull-right">
                <input type="submit" value="<?php echo __('Search');?>">
            </span>
            <span class="buttons pull-left">
                <input type="reset" value="<?php echo __('Reset');?>">
                <input type="button" value="<?php echo __('Cancel');?>" class="close">
            </span>
            <span class="spinner">
                <img src="./images/ajax-loader.gif" width="16" height="16">
            </span>
        </p>
    </form>
</div>
<script type="text/javascript">
$(function() {
    $(document).off('.tickets');
    $(document).on('click.tickets', 'a.tickets-action', function(e) {
        e.preventDefault();
        var count = checkbox_checker($('form#tickets'), 1);
        if (count) {
            var url = 'ajax.php/'
            +$(this).attr('href').substr(1)
            +'?count='+count
            +'&_uid='+new Date().getTime();
            $.dialog(url, [201], function (xhr) {
                window.location.href = window.location.href;
             });
        }
        return false;
    });
});
</script>
