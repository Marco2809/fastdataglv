<?php
ini_set('display_errors','On');
error_reporting(E_ALL);

//session_start();
$user = isset($_REQUEST['username']) ? $_REQUEST['username'] : ""; // prende username dalla varibile globale
$pass = isset($_REQUEST['pass']) ? $_REQUEST['pass'] : ""; // prende la password dalla varibile globale

//con una echo vedi se vengono lette username e password che ti passo

if (!empty($user) && !empty($pass))
{
    $_POST['userid'] = $user;
    $_POST['passwd'] = $pass;
    $_POST['entity'] = 1;
    $_POST['image'] = true;
    // qui se hai altri valori per aprire la sessione puoi scriverlo manualmente, come io ho fatto con $_POST['entity'] = 1
}

//$_SESSION["dol_login"] = $user;

require 'login.tpl.php'; // viene passato i datti della request nello script index.php (che presumo effettua il login nel modulo ticket)

// per chieudere la sessione in tutti siti collegati:
// occorre chiudere la sessione nel modulo ticket e inviarmi la una risposta della chiusura della sessione.
// io poi in fastdata ricevuto il comando della chiusura sessione chiudo anche nell'ambiente padre.
