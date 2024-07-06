<?php
require('header.php');
include('mysql/mysql-functions');
$dt = new DateTimeImmutable();
$date = $dt->format('Y-m-d');
$time = $dt->format('Y-m-d H:i:s.v');

function getTime() {
        dbSelectVal("SELECT CURTIME() AS result");
        echo $result;
}
?>

<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>TechComm Laptop Managment</title>
    </head>
    <body onload="refresh()">
    <div id="div1"></div>
    <script>
        let nIntervId;

        function refresh() {
            if (!nIntervId) {
                nIntervId = setInterval(addElement, 1000);
            }
        }

        function addElement() {
                document.getElementById('div1').innerHTML = "<p><?php echo getTime(); ?></p>";
        }
    </script>
    </body>
</html>