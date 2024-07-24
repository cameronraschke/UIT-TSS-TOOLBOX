<?php
require('/var/www/html/management/header.php');
include('/var/www/html/management/mysql/mysql-functions');
$dt = new DateTimeImmutable();
$date = $dt->format('Y-m-d');
$time = $dt->format('Y-m-d H:i:s.v');

function getTime() {
    dbSelectVal("SELECT CURTIME() AS result");
    global $result;
    return $result;
}
?>

<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>TechComm Laptop Managment</title>
    </head>
    <body onload="refresh()">
    <script>
        let nIntervId;

        function refresh() {
            if (!nIntervId) {
                nIntervId = setInterval(addElement, 1000);
            }
        }

        <?php
            echo "function addElement() {" . PHP_EOL;
            echo "              document.getElementById('div1').innerHTML = '<p>" . getTime() . "</p>';" . PHP_EOL;
            echo "      }" . PHP_EOL;
        ?>
    </script>
    <div id="div1"></div>
    </body>
</html>