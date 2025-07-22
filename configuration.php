<?php
$host = 'localhost';           
$dbname = 'minorproject';      
$username = 'root';            
$password = 'ram123';               


$conn = new mysqli($host, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>
