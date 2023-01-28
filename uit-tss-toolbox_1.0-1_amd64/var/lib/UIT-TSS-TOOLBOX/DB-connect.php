<?php
$servername = 'db-mysql-nyc1-21857-do-user-11961075-0.b.db.ondigitalocean.com:25060';
$username = 'doadmin';
$password = 'AVNS_ncJzgkTeIG6wBd4OSQs';
$dbname = "laptopDB";

global $conn;
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn -> connect_errno) {
  echo "Failed to connect to MySQL: " . $conn -> connect_error;
  exit();
}

?>