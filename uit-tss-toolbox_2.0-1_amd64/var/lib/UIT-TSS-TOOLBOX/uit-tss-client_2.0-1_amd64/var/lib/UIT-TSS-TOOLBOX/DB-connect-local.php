<?php
date_default_timezone_set('America/Chicago');

$servername = '10.0.0.1';
$username = 'laptops';
$password = 'UHouston!';
$dbname = "laptopDB";

global $conn;
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn -> connect_errno) {
  echo "Failed to connect to MySQL: " . $conn -> connect_error;
  exit();
}

?>