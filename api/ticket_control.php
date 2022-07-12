<?php
header("Access-Control-Allow-Origin: *");

$host = "localhost";
// username dell'utente in connessione
$user = "admin";
// password dell'utente
$password = "Iniziale1!?";
// nome del database
$db = "fd_ticket";

$connessione = new mysqli($host, $user, $password, $db);

if(isset($_POST['num'])&&$_POST['num']!="")
{
  $_POST['num'] = trim($_POST['num']);
  urldecode($_POST['num']);
  if(strstr($_POST['num'],'+')){
    $_POST['num'] = str_replace('+','',$_POST['num']);
    $sql="SELECT t.ticket_id FROM ost_ticket__cdata as tc, ost_ticket as t WHERE tc.ref_num='".$_POST['num']."' AND t.ticket_id=tc.ticket_id";
    $result = $connessione->query($sql);
    if(mysqli_num_rows($result)>0){
      $sql="SELECT t.ticket_id FROM ost_ticket__cdata as tc, ost_ticket as t WHERE tc.ref_num='".$_POST['num']."' AND t.ticket_id=tc.ticket_id AND t.status_id IN(2,3,4,5,8)";
      $result = $connessione->query($sql);
      if(mysqli_num_rows($result)>0){
        echo 'Ticket già chiuso, recesso o rifiutato';
      } else echo 'OK';
    } else echo 'Ticket non esistente';

  } else {

    $sql="SELECT t.ticket_id FROM ost_ticket__cdata as tc, ost_ticket as t WHERE t.number='".$_POST['num']."' AND t.ticket_id=tc.ticket_id";
    $result = $connessione->query($sql);
    if(mysqli_num_rows($result)>0){
      $sql="SELECT t.ticket_id FROM ost_ticket__cdata as tc, ost_ticket as t WHERE t.number='".$_POST['num']."' AND t.ticket_id=tc.ticket_id AND t.status_id IN(2,3,4,5,8)";
      $result = $connessione->query($sql);
      if(mysqli_num_rows($result)>0){
        echo 'Ticket già chiuso, recesso o rifiutato';
      } else echo 'OK';
    } else echo 'Ticket non esistente';

  }
}
?>
