<?php
require('/var/www/html/management/header.php');
include('/var/www/html/management/mysql/mysql-functions');
$dt = new DateTimeImmutable();
$date = $dt->format('Y-m-d');
$time = $dt->format('Y-m-d H:i:s.v');
?>

<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>TechComm Laptop Managment</title>
        <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
    </head>
    <body>
        <div class='menubar'>
            <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
            <p><span style='float: right;'>Logged in as <b><?php echo "$login_user"; ?></b>.</span></p>
            <br>
            <p><span style='float: right;'>Not <b><?php echo "$login_user"; ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
        </div>

        <div class='pagetitle'><h1>Client Lookup (<?php echo $_GET['tagnumber']; ?>)</h1></div>
        <div class='pagetitle'><h2>Lookup data for a specific client.</h2></div>
        <div class='pagetitle'><h3>Last updated: <?php dbSelectVal("SELECT DATE_FORMAT(CONCAT(CURDATE(), ' ', CURTIME()), '%b %D %Y, %r') AS 'result'"); echo $result; ?></h3></div>
        
        <div class='pagetitle'>
            <h3>General Client Info</h3>
        </div>

        <div class='styled-table' style="width: auto; height:10%; overflow:auto; margin: 1% 1% 0% 1%;">
        <table width="100%">
            <thead>
                <tr>
                <th>Ethernet MAC Address</th>
                <th>Wi-Fi MAC Address</th>
                <th>Department</th>
                <th>System Manufacturer</th>
                <th>System Model</th>
                </tr>
            </thead>
            <tbody>
                <?php
                dbSelect("SELECT etheraddress, wifi_mac, department, system_manufacturer, system_model FROM jobstats WHERE tagnumber = '" . $_GET['tagnumber'] . "' AND host_connected = '1' ORDER BY time DESC LIMIT 1");
                foreach ($arr as $key => $value) {
                   echo "<tr>" . PHP_EOL;
                   echo "<td>" . $value['etheraddress'] . "</td>" . PHP_EOL;
                   echo "<td>" . $value['wifi_mac'] . "</td>" . PHP_EOL;
                   echo "<td>" . $value['department'] . "</td>" . PHP_EOL;
                   echo "<td>" . $value['system_manufacturer'] . "</td>" . PHP_EOL;
                   echo "<td>" . $value['system_model'] . "</td>" . PHP_EOL;
                   echo "</tr>" . PHP_EOL;
                }
                ?>
            </tbody>

        </table>
        </div>

    </body>
</html>
?>