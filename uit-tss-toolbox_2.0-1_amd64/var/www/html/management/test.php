<?php
require('header.php');
include('mysql/mysql-functions');
$dt = new DateTimeImmutable();
$date = $dt->format('Y-m-d');
$time = $dt->format('Y-m-d H:i:s.v');
?>

<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>TechComm Laptop Managment</title>
        <script src="js/script.js"></script>
    </head>
    <body>
    <div id="div1">The text above has been created dynamically.</div>
    </body>
</html>