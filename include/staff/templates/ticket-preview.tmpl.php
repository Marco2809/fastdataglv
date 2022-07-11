<?php
/*
 * Ticket Preview popup template
 *
 */

$staff=$ticket->getStaff();
$lock=$ticket->getLock();
$error=$msg=$warn=null;

if($lock && $lock->getStaffId()==$thisstaff->getId())
    $warn.='&nbsp;<span class="Icon lockedTicket">'
    .sprintf(__('Ticket is locked by %s'), $lock->getStaffName()).'</span>';
elseif($ticket->isOverdue())
    $warn.='&nbsp;<span class="Icon overdueTicket">'.__('Marked overdue!').'</span>';

echo sprintf(
        '<div style="width:600px; padding: 2px 2px 0 5px;" id="t%s">
         <h2>'.__('Ticket #%s').': %s</h2><br>',
         $ticket->getNumber(),
         $ticket->getNumber(),
         Format::htmlchars($ticket->getSubject()));

if($error)
    echo sprintf('<div id="msg_error">%s</div>',$error);
elseif($msg)
    echo sprintf('<div id="msg_notice">%s</div>',$msg);
elseif($warn)
    echo sprintf('<div id="msg_warning">%s</div>',$warn);
 
echo '<ul class="tabs">';

echo '
        <li><a id="preview_tab" href="#preview" class="active"
            ><i class="icon-list-alt"></i>&nbsp;'.__('Ticket Summary').'</a></li>';
if ($ticket->getNumCollaborators()) {
echo sprintf('
        <li><a id="collab_tab" href="#collab"
            ><i class="icon-fixed-width icon-group
            faded"></i>&nbsp;'.__('Collaborators (%d)').'</a></li>',
            $ticket->getNumCollaborators());
}
/*
if ($ticket->motivo_sospensione()) {
echo sprintf('
        <li><a id="sospensione_tab" href="#sospensione"
            ><i class="icon-fixed-width icon-group
            faded"></i>&nbsp;%s</a></li>',
            $ticket->motivo_sospensione());
}
*/
echo '</ul>';

echo '<div class="tab_content" id="preview">';

//if($ticket->motivo_sospensione())
//echo sprintf('<div id="msg_sospensione">%s</div>',$ticket->motivo_sospensione());  
/*
echo '<table border="0" cellspacing="" cellpadding="1" width="100%" class="ticket_info">';

$ticket_state=sprintf('<span>%s</span>',ucfirst($ticket->getStatus()));
if($ticket->isOpen()) {
    if($ticket->isOverdue())
        $ticket_state.=' &mdash; <span>'.__('Overdue').'</span>';
    else
        $ticket_state.=sprintf(' &mdash; <span>%s</span>',$ticket->getPriority());
}

echo sprintf('
        <tr>
            <th width="100">'.__('Ticket State').':</th>
            <td>%s</td>
        </tr>
        <tr>
            <th>'.__('Created').':</th>
            <td>%s</td>
        </tr>',$ticket_state,
        Format::db_datetime($ticket->getCreateDate()));
if($ticket->isClosed()) {
    echo sprintf('
            <tr>
                <th>'.__('Closed').':</th>
                <td>%s   <span class="faded">by %s</span></td>
            </tr>',
            Format::db_datetime($ticket->getCloseDate()),
            ($staff?$staff->getName():'staff')
            );
} elseif($ticket->getEstDueDate()) {
    echo sprintf('
            <tr>
                <th>'.__('Due Date').':</th>
                <td>%s</td>
            </tr>',
            Format::db_datetime($ticket->getEstDueDate()));
            echo $ticket->problem();
}
echo '</table>';
*/

echo '<table border="0" cellspacing="" cellpadding="1" width="100%" class="ticket_info">';

if ($ticket->mono_turno()){$mono_turno=(strpos($ticket->mono_turno(), '19') !== false OR strpos($ticket->mono_turno(), '18') !== false)?'(<strong>Doppio turno</strong>)':'(<strong>Mono turno</strong>)';}  

echo sprintf('
        <tr>
            <th width="100">Ticket Fastdata:</th>
            <td>%s</td>
        </tr>
        <tr>
            <th>Ticket Cliente:</th>
            <td>%s</td>
        </tr>
        <tr>
            <th width="100">Ufficio:</th>
            <td>%s</td>
        </tr>',$ticket->getNumber(),$ticket->problem(),$ticket->denominazione_ufficio().' '.$mono_turno);
        
 
 
 if (empty($ticket->via_ufficio())&&empty($ticket->localita_ufficio())&&empty($ticket->frazionario())){
	 echo  sprintf('
        <tr>
            <th>Telefono:</th>
            <td>%s</td>
        </tr>',$ticket->telefono_ufficio()); 
 }else{
 echo  sprintf('
        <tr>
            <th>Via:</th>
            <td>%s</td>
        </tr>
        <tr>
            <th width="100">Località:</th>
            <td>%s (%s)</td>
        </tr>
        <tr>
            <th width="100">Frazionario:</th>
            <td>%s</td>
        </tr>
        <tr>
            <th>Telefono:</th>
            <td>%s</td>
        </tr>',$ticket->via_ufficio(),$ticket->localita_ufficio(),$ticket->provincia_ufficio(),$ticket->frazionario(),$ticket->telefono_ufficio());

}
echo '</table>';

echo '<hr>
    <table border="0" cellspacing="" cellpadding="1" width="100%" class="ticket_info">';



$thread=$ticket->getThreadEntries('M');
foreach($thread as $entry) {
$description=$entry['body']->toHtml();	
}

echo sprintf('
        <tr>
            <th width="100">Description:</th>
            <td>%s</td>
        </tr>
        ',$description);

echo '
    </table>';
echo '</div>'; // ticket preview content.
?>
<div class="tab_content" id="collab" style="display:none;">
    <table border="0" cellspacing="" cellpadding="1">
        <colgroup><col style="min-width: 250px;"></col></colgroup>
        <?php
        if (($collabs=$ticket->getCollaborators())) {?>
        <?php
            foreach($collabs as $collab) {
                echo sprintf('<tr><td %s><i class="icon-%s"></i>
                        <a href="users.php?id=%d" class="no-pjax">%s</a> <em>&lt;%s&gt;</em></td></tr>',
                        ($collab->isActive()? '' : 'class="faded"'),
                        ($collab->isActive()? 'comments' :  'comment-alt'),
                        $collab->getUserId(),
                        $collab->getName(),
                        $collab->getEmail());
            }
        }  else {
            echo __("Ticket doesn't have any collaborators.");
        }?>
    </table>
    <br>
    <?php
    echo sprintf('<span><a class="collaborators"
                            href="#tickets/%d/collaborators">%s</a></span>',
                            $ticket->getId(),
                            $ticket->getNumCollaborators()
                                ? __('Manage Collaborators') : __('Add Collaborator')
                                );
    ?>
</div>
<?php
$options = array();
$options[]=array('action'=>sprintf(__('Thread (%d)'),$ticket->getThreadCount()),'url'=>"tickets.php?id=$tid");
if($ticket->getNumNotes())
    $options[]=array('action'=>sprintf(__('Notes (%d)'),$ticket->getNumNotes()),'url'=>"tickets.php?id=$tid#notes");

if($ticket->isOpen())
    $options[]=array('action'=>__('Reply'),'url'=>"tickets.php?id=$tid#reply");

if($thisstaff->canAssignTickets())
    $options[]=array('action'=>($ticket->isAssigned()?__('Reassign'):__('Assign')),'url'=>"tickets.php?id=$tid#assign");

if($thisstaff->canTransferTickets())
    $options[]=array('action'=>'Transfer','url'=>"tickets.php?id=$tid#transfer");

$options[]=array('action'=>'Post Note','url'=>"tickets.php?id=$tid#note");

if($thisstaff->canEditTickets())
    $options[]=array('action'=>'Edit Ticket','url'=>"tickets.php?id=$tid&a=edit");
/*
if($options) {
    echo '<ul class="tip_menu">';
    foreach($options as $option)
        echo sprintf('<li><a href="%s">%s</a></li>',$option['url'],$option['action']);
    echo '</ul>';
}
*/
echo '</div>';
?>
