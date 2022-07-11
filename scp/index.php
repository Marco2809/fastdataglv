<?php
/*********************************************************************
    index.php
    
    Future site for helpdesk summary aka Dashboard.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
//Nothing for now...simply redirect to tickets page.



//callAjaxLoginTicket($_POST['userid'], $_POST['passwd'],1); // chiama la funzione

/*

function callAjaxLoginTicket($username, $pass, $log_ticket)
{
    if ($log_ticket == 1)
    {
        print'<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>';

        print'<script>';
        print' $(document).ready(function () {';
        print '$.ajax({';
        print " url: 'http://glvservice.fast-data.it/login_unificato.php?username=$username&pass=$pass',";
        print "dataType: 'jsonp',";
        print "success: function (dataWeGotViaJsonp) {";
        print "  var text = '';";


        print " $('#login_ticket').html(text);";
        print "}";
        print " });";
        print " })";
        print "</script>";
    }
}
*/


require('tickets.php');
?>
