<?php

//phpinfo();

error_reporting(-1);
ini_set('display_errors', 'On');
set_error_handler("var_dump");

if ( function_exists( 'mail' ) )
{
    echo 'mail() is available';
    mail("marco.salmi89@gmail.com","P","P");
}
else
{
    echo 'mail() has been disabled';
}

$servername = "localhost";
$username = "admin";
$password = "Iniziale1!?";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";

echo  time();

?>
