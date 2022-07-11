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
  if( ($_REQUEST['query'] && strlen($_REQUEST['query'])<3)
      || (!$_REQUEST['query'] && isset($_REQUEST['basic_search'])) ){ //Why do I care about this crap...
      $search=false; //Instead of an error page...default back to regular query..with no search.
      $errors['err']=__('Search term must be more than 3 chars');
      $searchTerm='';
  }
}
$showoverdue=$showanswered=false;
$staffId=0; //Nothing for now...TODO: Allow admin and manager to limit tickets to single staff level.
$showassigned= true; //show Assigned To column - defaults to true

//Get status we are actually going to use on the query...making sure it is clean!
$status=null;
switch(strtolower($_REQUEST['status'])){ //Status is overloaded
    case 'open':
        $status='open';
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

//if(!$thisstaff->showAssignedOnly())
    $qwhere.=' OR (ticket.dept_id IN ('.($depts?implode(',', db_input($depts)):0).') AND ticket.staff_id='.db_input($thisstaff->getId()).')';

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
        } elseif (strpos($searchTerm,'@') && Validator::is_email($searchTerm)) {
            //pulling all tricks!
            # XXX: What about searching for email addresses in the body of
            #      the thread message
            $qwhere.=" AND email.address='$queryterm'";
        } else {//Deep search!
            //This sucks..mass scan! search anything that moves!
            require_once(INCLUDE_DIR.'ajax.tickets.php');

            $tickets = TicketsAjaxApi::_search(array('query'=>$queryterm));
            if (count($tickets)) {
                $ticket_ids = implode(',',db_input($tickets));
                $qwhere .= ' AND ticket.ticket_id IN ('.$ticket_ids.')';
                $order_by = 'FIELD(ticket.ticket_id, '.$ticket_ids.')';
                $order = ' ';
            }
            else
                // No hits -- there should be an empty list of results
                $qwhere .= ' AND false';
        }
   }

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
        $order_by='pri.priority_urgency ASC, effective_date DESC, ticket.created';
}

$order=$order?$order:'DESC';
if($order_by && strpos($order_by,',') && $order)
    $order_by=preg_replace('/(?<!ASC|DESC),/', " $order,", $order_by);

$sort=$_REQUEST['sort']?strtolower($_REQUEST['sort']):'pri.priority_urgency'; //Urgency is not on display table.
$x=$sort.'_sort';
$$x=' class="'.strtolower($order).'" ';

if($_GET['limit'])
    $qs += array('limit' => $_GET['limit']);

$qselect ='SELECT ticket.ticket_id,tlock.lock_id,ticket.`number`,ticket.dept_id,ticket.staff_id,ticket.team_id '
    .' ,user.name'
    .' ,email.address as email, dept.dept_name, status.state '
         .' ,status.name as status,ticket.source,ticket.isoverdue,ticket.isanswered,ticket.created ';

$qfrom=' FROM '.TICKET_TABLE.' ticket '.
       ' LEFT JOIN '.TICKET_STATUS_TABLE. ' status
            ON (status.id = ticket.status_id) '.
       ' LEFT JOIN '.USER_TABLE.' user ON user.id = ticket.user_id'.
       ' LEFT JOIN '.USER_EMAIL_TABLE.' email ON user.id = email.user_id'.
       ' LEFT JOIN ost_ticket_tempi tempi ON tempi.ticket_id = ticket.ticket_id'.
       ' LEFT JOIN '.DEPT_TABLE.' dept ON ticket.dept_id=dept.dept_id ';

if ($_REQUEST['uid'])
    $qfrom.=' LEFT JOIN '.TICKET_COLLABORATOR_TABLE.' collab
        ON (ticket.ticket_id = collab.ticket_id )';


$sjoin='';

if($search && $deep_search) {
    $sjoin.=' LEFT JOIN '.TICKET_THREAD_TABLE.' thread ON (ticket.ticket_id=thread.ticket_id )';
}

//get ticket count based on the query so far..
//$total_dis=$total_lav=$total_sos=$total=db_count("SELECT count(DISTINCT ticket.ticket_id) $qfrom $sjoin $qwhere");
$totale = db_count("SELECT count(DISTINCT ticket.ticket_id) $qfrom $sjoin $qwhere"); //mi serve il totale per export


$total=db_count("SELECT count(CASE WHEN status.name='Assegnato' OR status.name='Attesa cliente' OR status.name='Risolto' THEN 1 END) $qfrom $sjoin $qwhere");
/*
$total_sos=db_count("SELECT count(CASE WHEN status.name='Sospeso' OR status.name='Attesa preventivo' THEN 1 END) $qfrom $sjoin $qwhere");
$total_lav=db_count("SELECT count(CASE WHEN status.name='In carico al magazzino' OR status.name='In carico al laboratorio' OR status.name='In carico Tec. LAB' OR status.name='Da validare LAB' OR status.name='Non riparato' OR status.name='In attesa parti' OR status.name='Ripreso in carico' THEN 1 END) $qfrom $sjoin $qwhere");
$total_dis=db_count("SELECT count(CASE WHEN status.name='Da dismettere' THEN 1 END) $qfrom $sjoin $qwhere");
*/
//echo $total_sos; numero totale di ticket sospesi o in attesa di preventivo
//echo $total."<br>";
//echo $total_sos."<br>";
//echo $total_lav."<br>";
//echo $total_dis."<br>";

//pagenate
$pagelimit=($_GET['limit'] && is_numeric($_GET['limit']))?$_GET['limit']:6;
//$pagelimit=6;
$_SESSION['pagina_a']=$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
/*
$_SESSION['pagina_s']=$page_s=($_GET['ps'] && is_numeric($_GET['ps']))?$_GET['ps']:1;
$_SESSION['pagina_l']=$page_l=($_GET['pl'] && is_numeric($_GET['pl']))?$_GET['pl']:1;
$_SESSION['pagina_d']=$page_d=($_GET['pd'] && is_numeric($_GET['pd']))?$_GET['pd']:1;
*/

//$page=2;
$pageExp=new Pagenate($totale,$page,$pagelimit);
$pageNav=new Pagenate($total,$page,$pagelimit);
//print_r($pageNav);
//navigazione per i ticket sospesi

/*
$pageNav_sos=new Pagenate($total_sos,$page_s,$pagelimit/2);
$pageNav_lav=new Pagenate($total_lav,$page_l,$pagelimit);
$pageNav_dis=new Pagenate($total_dis,$page_d,$pagelimit/2);
*/

$qstr = '&amp;'.http::build_query($qs);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageExp->setURL('tickets.php', $qs);
$pageNav->setURL('tickets.php', $qs);

/*
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
         .' ,cdata.priority as priority_id, cdata.subject, cdata.active, cdata.zz_date1, cdata.pln_alpha, cdata.status_sym, cdata.ref_num, cdata.group_last_name, cdata.pc_flag, cdata.pc_sn, cdata.imac, cdata.comm_id, commesse.gruppo, pri.priority_desc, pri.priority_color, regioni.nomeregione';

$qfrom.=' LEFT JOIN '.TICKET_LOCK_TABLE.' tlock ON (ticket.ticket_id=tlock.ticket_id AND tlock.expire>NOW()
               AND tlock.staff_id!='.db_input($thisstaff->getId()).') '
       .' LEFT JOIN '.STAFF_TABLE.' staff ON (ticket.staff_id=staff.staff_id) '
       .' LEFT JOIN '.TEAM_TABLE.' team ON (ticket.team_id=team.team_id) '
       .' LEFT JOIN '.SLA_TABLE.' sla ON (ticket.sla_id=sla.id AND sla.isactive=1) '
       .' LEFT JOIN '.TOPIC_TABLE.' topic ON (ticket.topic_id=topic.topic_id) '
       .' LEFT JOIN '.TOPIC_TABLE.' ptopic ON (ptopic.topic_id=topic.topic_pid) '
       .' LEFT JOIN '.TABLE_PREFIX.'ticket__cdata cdata ON (cdata.ticket_id = ticket.ticket_id) '
       .' LEFT JOIN ost_province province ON (province.siglaprovincia = cdata.customer_location_l_addr1)'
       .' LEFT JOIN ost_regioni regioni ON (regioni.idregione = province.idregione)'
       .' LEFT JOIN ost_commesse commesse ON (commesse.comm_id = cdata.comm_id)'
       .' LEFT JOIN '.PRIORITY_TABLE.' pri ON (pri.priority_id = cdata.priority)';

TicketForm::ensureDynamicDataView();

$query_exp="$qselect $qfrom $qwhere ORDER BY $order_by $order LIMIT ".$pageExp->getStart().",".$pageExp->getLimit();
$hash = md5($query_exp);
$_SESSION['search_'.$hash] = $query_exp;
$res_exp = db_query($query_exp);
//$res['num_rows']=30;
$showing_exp=db_num_rows($res_exp)? ' &mdash; '.$pageExp->showing():"";

$query="$qselect $qfrom $qwhere ORDER BY $order_by $order LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
//echo $query;
//$hash = md5($query);
//$_SESSION['search_'.$hash] = $query;
$res = db_query($query);
//$res['num_rows']=30;
$showing=db_num_rows($res)? ' &mdash; '.$pageNav->showing():"";
//print_r($showing);

/*
$query_sos="$qselect $qfrom $qwhere "."AND (ticket.status_id=21 OR ticket.status_id=13)"." ORDER BY $order_by $order LIMIT ".$pageNav_sos->getStart().",".$pageNav_sos->getLimit();
$res_sos = db_query($query_sos);
$showing_sos=db_num_rows($res_sos)? ' &mdash; '.$pageNav_sos->showing():"";

$query_dis="$qselect $qfrom $qwhere "."AND (ticket.status_id=20)"." ORDER BY $order_by $order LIMIT ".$pageNav_dis->getStart().",".$pageNav_dis->getLimit();
$res_dis = db_query($query_dis);
$showing_dis=db_num_rows($res_dis)? ' &mdash; '.$pageNav_dis->showing():"";

$query_lav="$qselect $qfrom $qwhere "."AND (ticket.status_id=16 OR ticket.status_id=11 OR ticket.status_id=12 OR ticket.status_id=17 OR ticket.status_id=18 OR ticket.status_id=19 OR ticket.status_id=22)"." ORDER BY $order_by $order LIMIT ".$pageNav_lav->getStart().",".$pageNav_lav->getLimit();
$res_lav = db_query($query_lav);
$showing_lav=db_num_rows($res_lav)? ' &mdash; '.$pageNav_lav->showing():"";
*/

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

/*
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

*/ 
//FINE ALTRE QUERY COMMENTATE


/*
$pageNav=new Pagenate($total,$page,5);
//print_r($pageNav);
//navigazione per i ticket sospesi
$pageNav_sos=new Pagenate($total_sos,$page_s,5);
$pageNav_lav=new Pagenate($total_lav,$page_l,5);
$pageNav_dis=new Pagenate($total_dis,$page_d,5);
*/
        /*conta solo i ticket della pagina corrente
        $count = array();
        foreach($results as $one)
        {
        @$count[$one['status']]++;
        }

        printf("ci sono %d ticket sospesi.\r", $count['Sospeso']);
        printf("ci sono %d ticket attesa preventivo.\r", $count['Attesa preventivo']);
        printf("ci sono %d ticket Planning.\r", $count['Planning']);
        printf("ci sono %d ticket Transferred.\r", $count['Transferred']);       
        */ 
          
//print_r($results);
//YOU BREAK IT YOU FIX IT.
?>
<!-- SEARCH FORM START -->
<!--ricerca originale
<div id='basic_search'>
    <form action="tickets.php" method="get">
    <?php csrf_token(); ?>
    <input type="hidden" name="a" value="search">
    <table>
        <tr style="background-color:transparent; border-radius:0px; -webkit-box-shadow: 0 0px 0px 0px; -moz-box-shadow: 0 0px 0px 0px; box-shadow: 0 0px 0px 0px;">
            <td><input type="text" id="basic-ticket-search" name="query"
            size=30 value="<?php echo Format::htmlchars($_REQUEST['query'],
            true); ?>"
                autocomplete="off" autocorrect="off" autocapitalize="off"></td>
            <td><input type="submit" name="basic_search" class="button" value="<?php echo __('Search'); ?>"></td>
            <td>&nbsp;&nbsp;<a href="#" id="go-advanced">[<?php echo __('advanced'); ?>]</a>&nbsp;<i class="help-tip icon-question-sign" href="#advanced"></i></td>
        </tr>
    </table>
    </form>
</div>
-->
<!-- SEARCH FORM END -->
<div class="clear"></div>
<div style="margin-bottom:5px; padding-top:5px;">
<div style="margin-left:10px;">
        <div class="pull-left flush-left">
            <h2><a href="<?php echo Format::htmlchars($_SERVER['REQUEST_URI']); ?>"
                title="<?php echo __('Refresh'); ?>"><i class="icon-refresh"></i> 
            <?php echo $results_type.'</a> --  ';//.$showing;
            echo sprintf('<a class="export-csv no-pjax" href="?%s">%s</a>',
                Http::build_query(array(
                        'a' => 'export', 'h' => $hash,
                        'status' => $_REQUEST['status'])),
                __('Export'));
            echo '&nbsp;<i class="help-tip icon-question-sign" href="#export"></i>&nbsp;&nbsp;';
            ?></h2>
        </div>
        <div class="pull-left flush-left">
            <?php
            if ($thisstaff->canDeleteTickets()) { ?>
            <a id="tickets-delete" class="action-button pull-right tickets-action"
                href="#tickets/status/delete"><i class="icon-trash"></i> <?php echo __('Delete'); ?></a>
            <?php
            } ?>
            <?php
            if ($thisstaff->canManageTickets()) {
                echo TicketStatus::status_options();
            }
            ?>
        </div>
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
	 
 <table border="0" cellspacing="1" cellpadding="2" width="100%">
	 <tr style="background-color:transparent; border-radius:0px; -webkit-box-shadow: 0 0px 0px 0px; -moz-box-shadow: 0 0px 0px 0px; box-shadow: 0 0px 0px 0px;"><td style="width:auto;"><!--prima cella-->
	<div style="width: 100%; height:auto; overflow:auto; float: left;">
  <div style="width: 100%;
    height:500px;
	margin-right:auto;
	margin-left:auto;
	margin-bottom:10px;
	padding-bottom:10px;
	border: 0px solid #CCC;
	border-radius: 12px;
	background:#CCC;
   -moz-box-shadow: inset 0 0 20px #000000;
   -webkit-box-shadow: inset 0 0 20px #000000;
   box-shadow: inset 0 0 20px #000000;">
	  <h1><img src="../images/dlnelcc_2.png" style="margin-top:10px;margin-left:10px">&nbsp;Ticket Da lavorare</h1>
 <center><table width="99%" border="0" cellspacing="20px" cellpadding="10px" style="border-collapse: separate; border-spacing: 0px 10px; margin-top:10px; margin-bottom:10px; background:transparent; ">
    
     <tbody style="border-radius:25px;">
        <?php
        // Setup Subject field for display
        $subject_field = TicketForm::objects()->one()->getField('subject');
        $class = "row1";
        $total=0;
        if($res && ($num=count($results))):
            $ids=($errors && $_POST['tids'] && is_array($_POST['tids']))?$_POST['tids']:null;
            foreach ($results as $row) {
				//qui bisogna mettere l'if sugli stati ed sul compentence center??
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
                ), 10);
                $threadcount=$row['thread_count'];
                if(!strcasecmp($row['state'],'open') && !$row['isanswered'] && !$row['lock_id']) {
                    $tid=sprintf('<b>%s</b>',$tid);
                }
                ?>
                <?php //CONTROLLO STATI AMMESSI
                if($row['status']=="Assegnato" || $row['status']=="Attesa cliente") {
                ?> 
           <tr id="<?php echo $row['ticket_id']; ?>" style="border-radius: 12px;  padding-top:10px; -webkit-box-shadow:1px 1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px 1px  black;  box-shadow: 1px 1px 1px 1px  black;">
                <?php //if($thisstaff->canManageTickets()) {
                if ($row['imac']==1) { //backend per me
                   
                    ?>
                <td align="center" class="nohover" width="1%" style="background:#ffffff; border-radius:12px 0px 0px 12px;  padding-top:10px; -moz-border-radius:12px 0px 0px 12px; -webkit-border-radius:12px 0px 0px 12px; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">
                    <img src="../images/imac.png">
                </td>
                <?php } else { ?>
                 <td align="center" width="1%" nowrap  style="background:#ffffff; border-radius:12px 0px 0px 12px;  padding-top:10px; -moz-border-radius:12px 0px 0px 12px; -webkit-border-radius:12px 0px 0px 12px; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">&nbsp;</td>
                 <?php }?>
                <td title="<?php echo $row['email']; ?>" width="3%" nowrap style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">
                  <a class="Icon <?php echo strtolower($row['source']); ?>Ticket ticketPreview"
                    title="<?php echo __('Preview Ticket'); ?>"
                    href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><?php echo "<strong>Ticket Nr:</strong><br>".$tid; ?></a></td>
                <td width="3%" nowrap style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><strong>Ticket cliente:</strong><br><?php echo $row['ref_num'];?></td>
                <td width="3%" nowrap style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><strong>Transfer date:</strong><br><?php echo $row['name']=='Poste'?date('d/m/Y H:i',$row['zz_date1']):Format::db_datetime($row['ticket_created']);?></td>
                <td width="3%" nowrap style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><strong>Data scadenza:</strong><br><?php echo Format::db_datetime($row['duedate']); ?></td>
                <td width="3%" nowrap style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><strong>Cliente:</strong><br><?php echo Format::htmlchars(
                        Format::truncate($row['name'], 22, strpos($row['name'], '@'))); ?>&nbsp;</td>
                <!--<td width="40%" style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><a href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><strong>Subject:</strong><br><?php echo $subject; ?></a>
                </td>-->
                <td width="3%" nowrap style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><strong>Stato interno:</strong><br><?php echo ucfirst($row['status']);?></td>
                <td width="3%" nowrap style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><strong>Stato cliente:</strong><br><?php echo  $row['name']=='Poste'?$row['status_sym']:'Non disponibile';?></td>
                <td width="3%" nowrap style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><strong>Gruppo:</strong><br>
                <?php if($row['name']=='Poste'){echo $row['group_last_name']=='GESTIONE_ATTESE'?'GA':$row['group_last_name'];}
                elseif($row['name']=='Equitalia'){echo "EQUI_MAN";}
                elseif($row['name']=='MEP'){echo "MEP_MAN";}
                elseif($row['name']=='FICK'){echo "FICK_MAN";}
                elseif($row['name']=='FIDASC'){echo "FIDASC_MAN";}
                elseif($row['name']=='ISMEA'){echo "ISMEA_MAN";}
                elseif($row['name']=='Ambasciata Kuwait'){echo "AMB_KW";}
                elseif($row['name']=='Kion'){echo "KION_MAN";}
                elseif($row['name']=='Asilo Baby Club'){echo "ABC_MAN";}
                elseif($row['name']=='Solari AE'){echo "SAE_MAN";}
                elseif($row['name']=='Solari MEF'){echo "MEF_MAN";}
                elseif($row['name']=='Kaba'){echo "KAB_MAN";}
                elseif($row['name']=='Infocamere'){echo "CCIAA_MAN";}
                elseif($row['name']=='T4T'){echo "T4T_MAN";}
                elseif($row['name']=='Equitalia Sud'){echo "EqSUD_MAN";}
                elseif($row['name']=='PL-TECH'){echo "PLTECH_MAN";}
                elseif($row['name']=='Extra Poste NGA'){echo "EP_NGA_MAN";}
                elseif($row['name']=='Glam Event'){echo "GLAM_MAN";}
                elseif($row['name']=='ISMI'){echo "ISMI_MAN";}
                elseif($row['name']=='ST-Xservices'){echo "ST_XS_MAN";}
                elseif($row['name']=='ARP SERVICES'){echo "ARP_MAN";}
                elseif($row['name']=='Solari EXTRA'){echo "SOEXTRA_MAN";}
                elseif($row['name']=='Stech-IN'){echo "STECHIN_MAN";}
                elseif($row['name']=='ST-Spot'){echo "SPOT_MAN";}
                elseif($row['name']=='Farmacia S. Pietro'){echo "FSP_MAN";}
                elseif($row['name']=='NTAGLOBAL'){echo "NTA_MAN";}
                elseif($row['name']=='ST-XPCM'){echo "ST-XPCM_MAN";}
                elseif($row['name']=='Casa Chef'){echo "CHEF_MAN";}
                elseif($row['name']=='Avv. Reda'){echo "REDA_MAN";}
                elseif($row['name']=='Equitalia GA'){echo "EqGA_MAN";}
                elseif($row['name']=='Idealclima'){echo "IDEA_MAN";}
                elseif($row['name']=='ST-XTPR'){echo "XTPR_MAN";}
                
                ?>
                </td>
                <td width="3%" align="center" class="nohover" style="background:#ffffff; border-radius:0px 12px 12px 0px;  padding-top:10px; -moz-border-radius:0px 12px 12px 0px; -webkit-border-radius:0px 12px 12px 0px; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">
					<?php if($row['active']=="0"){echo '<img src="../images/inactive.png">'; 
					}elseif($row['active']=="1" and $row['status_sym']!="Sollecitato a Manutentore"){echo '<img src="../images/active.png">';
					}elseif($row['status_sym']=="Sollecitato a Manutentore"){echo '<img src="../images/sospeso.png">';
					}else{echo "&nbsp;";}?>
				</td>
            </tr>
            <?php } ?>
            <?php
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
        </td>-->
     </tr>
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
    </form>
</div>
	 </td></tr>
</table>
<!--QUI FINISCE LA TABELLA A 4 CELLE-->	
<?php } //FINE CONTROLLO CHIUSO?> 
<?php } //FINE CONTROLLO NON E' UNA RICERCA?> 
<!--CHIUSO-->
<?php //SE E' CHIUSO O PROVIENE DA UNA RICERCA
                if(strtolower($_REQUEST['status'])=="closed" || $_REQUEST['a']=='search' || isset($_REQUEST['advsid'])) {
                ?>
<div style="width: 100%; height:auto; overflow:auto; float: left;">
  <div style="width: 100%;
    height:500px;
	margin-right:auto;
	margin-left:auto;
	margin-bottom:10px;
	padding-bottom:10px;
	border: 0px solid #CCC;
	border-radius: 12px;
	background:#CCC;
   -moz-box-shadow: inset 0 0 20px #000000;
   -webkit-box-shadow: inset 0 0 20px #000000;
   box-shadow: inset 0 0 20px #000000;">
	  <h1><img src="../images/dlnelcc_2.png" style="margin-top:10px;margin-left:10px"><?php if(strtolower($_REQUEST['status'])==="closed" AND $_REQUEST['a']!='search' AND !isset($_REQUEST['advsid'])) { echo "&nbsp;Ticket Chiusi"; } if($_REQUEST['a']=='search' || isset($_REQUEST['advsid'])) { echo "&nbsp;Risultati della ricerca"; }?></h1>
 <center><table width="99%" border="0" cellspacing="20px" cellpadding="10px" style="border-collapse: separate; border-spacing: 0px 10px; margin-top:10px; margin-bottom:10px; background:transparent; ">
     <tbody style="border-radius:25px;">
        <?php
        // Setup Subject field for display
        $subject_field = TicketForm::objects()->one()->getField('subject');
        $class = "row1";
        $total=0;
        if($res_exp && ($num=count($results_exp))):
            $ids=($errors && $_POST['tids'] && is_array($_POST['tids']))?$_POST['tids']:null;
            foreach ($results_exp as $row) {
				//qui bisogna mettere l'if sugli stati ed sul compentence center??
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
                ), 10);
                $threadcount=$row['thread_count'];
                if(!strcasecmp($row['state'],'open') && !$row['isanswered'] && !$row['lock_id']) {
                    $tid=sprintf('<b>%s</b>',$tid);
                }
                ?>
                 
            <tr id="<?php echo $row['ticket_id']; ?>" style="border-radius: 12px;  padding-top:10px; -webkit-box-shadow:1px 1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px 1px  black;  box-shadow: 1px 1px 1px 1px  black;">
                <?php //if($thisstaff->canManageTickets()) {
               if ($row['imac']==1) { //backend per me
                   
                    ?>
                <td align="center" class="nohover" width="1%" style="background:#ffffff; border-radius:12px 0px 0px 12px;  padding-top:10px; -moz-border-radius:12px 0px 0px 12px; -webkit-border-radius:12px 0px 0px 12px; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">
                    <img src="../images/imac.png">
                </td>
                <?php } else { ?>
                 <td align="center" width="1%" nowrap  style="background:#ffffff; border-radius:12px 0px 0px 12px;  padding-top:10px; -moz-border-radius:12px 0px 0px 12px; -webkit-border-radius:12px 0px 0px 12px; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">&nbsp;</td>
                 <?php }?>
                <td title="<?php echo $row['email']; ?>" width="3%" nowrap style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">
                  <a class="Icon <?php echo strtolower($row['source']); ?>Ticket ticketPreview"
                    title="<?php echo __('Preview Ticket'); ?>"
                    href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><?php echo "<strong>Ticket Nr:</strong><br>".$tid; ?></a></td>
                <td width="3%" nowrap style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><strong>Ticket cliente:</strong><br><?php echo $row['ref_num'];?></td>
                <td width="3%" nowrap style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><strong>Transfer date:</strong><br><?php echo $row['name']=='Poste'?date('d/m/Y H:i',$row['zz_date1']):Format::db_datetime($row['ticket_created']);?></td>
                <td width="3%" nowrap style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><strong>Data scadenza:</strong><br><?php echo Format::db_datetime($row['duedate']); ?></td>
                <td width="3%" nowrap style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><strong>Cliente:</strong><br><?php echo Format::htmlchars(
                        Format::truncate($row['name'], 22, strpos($row['name'], '@'))); ?>&nbsp;</td>
                <!--<td width="40%" style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><a href="tickets.php?id=<?php echo $row['ticket_id']; ?>"><strong>Subject:</strong><br><?php echo $subject; ?></a>
                </td>-->
                <td width="3%" nowrap style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><strong>Stato interno:</strong><br><?php echo ucfirst($row['status']);?></td>
                <td width="3%" nowrap style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><strong>Stato cliente:</strong><br><?php echo  $row['name']=='Poste'?$row['status_sym']:'Non disponibile';?></td>
                <td width="3%" nowrap style="background:#ffffff; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;"><strong>Gruppo:</strong><br>
                <?php if($row['name']=='Poste'){echo $row['group_last_name']=='GESTIONE_ATTESE'?'GA':$row['group_last_name'];}
                elseif($row['name']=='Equitalia'){echo "EQUI_MAN";}
                elseif($row['name']=='MEP'){echo "MEP_MAN";}
                elseif($row['name']=='FICK'){echo "FICK_MAN";}
                elseif($row['name']=='FIDASC'){echo "FIDASC_MAN";}
                elseif($row['name']=='ISMEA'){echo "ISMEA_MAN";}
                elseif($row['name']=='Ambasciata Kuwait'){echo "AMB_KW";}
                elseif($row['name']=='Kion'){echo "KION_MAN";}
                elseif($row['name']=='Asilo Baby Club'){echo "ABC_MAN";}
                elseif($row['name']=='Solari AE'){echo "SAE_MAN";}
                elseif($row['name']=='Solari MEF'){echo "MEF_MAN";}
                elseif($row['name']=='Kaba'){echo "KAB_MAN";}
                elseif($row['name']=='Infocamere'){echo "CCIAA_MAN";}
                elseif($row['name']=='T4T'){echo "T4T_MAN";}
                elseif($row['name']=='Equitalia Sud'){echo "EqSUD_MAN";}
                elseif($row['name']=='PL-TECH'){echo "PLTECH_MAN";}
                elseif($row['name']=='Extra Poste NGA'){echo "EP_NGA_MAN";}
                elseif($row['name']=='Glam Event'){echo "GLAM_MAN";}
                elseif($row['name']=='ISMI'){echo "ISMI_MAN";}
                elseif($row['name']=='ST-Xservices'){echo "ST_XS_MAN";}
                elseif($row['name']=='ARP SERVICES'){echo "ARP_MAN";}
                elseif($row['name']=='Solari EXTRA'){echo "SOEXTRA_MAN";}
                elseif($row['name']=='Stech-IN'){echo "STECHIN_MAN";}
                elseif($row['name']=='ST-Spot'){echo "SPOT_MAN";}
                elseif($row['name']=='Farmacia S. Pietro'){echo "FSP_MAN";}
                elseif($row['name']=='NTAGLOBAL'){echo "NTA_MAN";}
                elseif($row['name']=='ST-XPCM'){echo "ST-XPCM_MAN";}
                elseif($row['name']=='Casa Chef'){echo "CHEF_MAN";}
                elseif($row['name']=='Avv. Reda'){echo "REDA_MAN";}
                elseif($row['name']=='Equitalia GA'){echo "EqGA_MAN";}
                elseif($row['name']=='Idealclima'){echo "IDEA_MAN";}
                elseif($row['name']=='ST-XTPR'){echo "XTPR_MAN";}
                ?>
                </td>
                <td width="3%" align="center" class="nohover" style="background:#ffffff; border-radius:0px 12px 12px 0px;  padding-top:10px; -moz-border-radius:0px 12px 12px 0px; -webkit-border-radius:0px 12px 12px 0px; -webkit-box-shadow:1px 1px 1px  black;-moz-box-shadow: 1px 1px 1px  black;  box-shadow: 1px 1px 1px  black;">
					<?php if($row['active']=="0"){echo '<img src="../images/inactive.png">'; 
					}elseif($row['active']=="1" and $row['status_sym']!="Sollecitato a Manutentore"){echo '<img src="../images/active.png">';
					}elseif($row['status_sym']=="Sollecitato a Manutentore"){echo '<img src="../images/sospeso.png">';
					}else{echo "&nbsp;";}?>
				</td>
            </tr>
            
            <?php
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
        </td>-->
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
                foreach (TicketStatusList::getStatuses(
                            array('states' => array('open', 'closed'))) as $s) {
                    echo sprintf('<option data-state="%s" value="%d">%s</option>',
                            $s->getState(), $s->getId(), __($s->getName()));
                }
                ?>
            </select>
        </fieldset>
        <fieldset class="span6">
            <label for="deptId"><?php echo __('Departments');?>:</label>
            <select id="deptId" name="deptId">
                <option value="">&mdash; <?php echo __('All Departments');?> &mdash;</option>
                <?php
                if(($mydepts = $thisstaff->getDepts()) && ($depts=Dept::getDepartments())) {
                    foreach($depts as $id =>$name) {
                        if(!in_array($id, $mydepts)) continue;
                        echo sprintf('<option value="%d">%s</option>', $id, $name);
                    }
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
        </fieldset>
        <fieldset class="owner span6">
            <label for="assignee"><?php echo __('Assigned To');?>:</label>
            <select id="assignee" name="assignee">
                <option value="">&mdash; <?php echo __('Anyone');?> &mdash;</option>
                <option value="s0">&mdash; <?php echo __('Unassigned');?> &mdash;</option>
                <option value="s<?php echo $thisstaff->getId(); ?>"><?php echo __('Me');?></option>
                <?php
                if(($users=Staff::getStaffMembers())) {
                    echo '<OPTGROUP label="'.sprintf(__('Agents (%d)'),count($users)-1).'">';
                    foreach($users as $id => $name) {
                        if ($id == $thisstaff->getId())
                            continue;
                        $k="s$id";
                        echo sprintf('<option value="%s">%s</option>', $k, $name);
                    }
                    echo '</OPTGROUP>';
                }

                if(($teams=Team::getTeams())) {
                    echo '<OPTGROUP label="'.__('Teams').' ('.count($teams).')">';
                    foreach($teams as $id => $name) {
                        $k="t$id";
                        echo sprintf('<option value="%s">%s</option>', $k, $name);
                    }
                    echo '</OPTGROUP>';
                }
                ?>
            </select>
        </fieldset>
        <fieldset class="span6">
            <label for="topicId"><?php echo __('Help Topics');?>:</label>
            <select id="topicId" name="topicId">
                <option value="" selected >&mdash; <?php echo __('All Help Topics');?> &mdash;</option>
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
                if(($users=Staff::getStaffMembers())) {
                    foreach($users as $id => $name)
                        echo sprintf('<option value="%d">%s</option>', $id, $name);
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
        <?php
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
        <?php } ?>
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
