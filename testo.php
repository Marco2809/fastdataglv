<?php

//phpinfo();

if ( function_exists( 'mail' ) )
{
    echo 'mail() is available';
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
