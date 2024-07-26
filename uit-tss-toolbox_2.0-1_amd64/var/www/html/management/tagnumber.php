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

        <div class='laptop-images'>
        <?php
            dbSelectVal("SELECT system_model AS 'result' FROM jobstats WHERE tagnumber = '" . $_GET['tagnumber'] . "' AND host_connected = '1' ORDER BY time DESC LIMIT 1");
            if ($result == "HP ProBook 450 G6") {
                echo "<img src='/images/hpProBook450G6.avif'>" . PHP_EOL;
            }
        ?>
        </div>
        
        <div class='pagetitle'><h3>General Client Info</h3></div>

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
                dbSelect("SELECT etheraddress, wifi_mac, (CASE WHEN department='techComm' THEN 'Tech Commons (TSS)' WHEN department='property' THEN 'Property' WHEN department='shrl' THEN 'SHRL' ELSE '' END) AS 'department', system_manufacturer, system_model FROM jobstats WHERE tagnumber = '" . $_GET['tagnumber'] . "' AND host_connected = '1' ORDER BY time DESC LIMIT 1");
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

        <div class='pagetitle'><h3>General Client Info</h3></div>

        <div class='styled-table' style="width: auto; height:10%; overflow:auto; margin: 1% 1% 0% 1%;">
        <table width="100%">
            <thead>
                <tr>
                <th>Time</th>
                <th>Location</th>
                <th>Status</th>
                <th>Disk Removed</th>
                <th>Note</th>
                </tr>
            </thead>
            <tbody>
                <?php
                dbSelect("SELECT DATE_FORMAT(time, '%b %D %Y, %r') AS 'time_formatted', location, IF (status='0' OR status IS NULL, 'Working', 'Broken') AS 'status', IF (disk_removed = 1, 'Yes', 'No') AS 'disk_removed', note FROM locations WHERE tagnumber = '" . $_GET['tagnumber'] . "' ORDER BY time DESC LIMIT 10");
                foreach ($arr as $key => $value) {
                echo "<tr>" . PHP_EOL;
                echo "<td>" . $value['time_formatted'] . "</td>" . PHP_EOL;
                echo "<td>" . $value['location'] . "</td>" . PHP_EOL;
                echo "<td>" . $value['status'] . "</td>" . PHP_EOL;
                echo "<td>" . $value['disk_removed'] . "</td>" . PHP_EOL;
                echo "<td>" . $value['note'] . "</td>" . PHP_EOL;
                echo "</tr>" . PHP_EOL;
                }
                ?>
            </tbody>
        </table>
        </div>

        <div class='pagetitle'><h3>Job Info</h3></div>
        <div class='styled-table' style="width: auto; height:20%; overflow:auto; margin: 1% 1% 0% 1%;">
        <table width="100%">
            <thead>
                <tr>
                <th>Time</th>
                <th>UUID</th>
                <th>CPU Usage</th>
                <th>Network Usage</th>
                <th>Boot Time</th>
                <th>Erase Completed</th>
                <th>Erase Mode</th>
                <th>Erase Time Elapsed</th>
                <th>Clone Completed</th>
                <th>Clone Master</th>
                <th>Clone Time</th>
                </tr>
            </thead>
            <tbody>
                <?php
                dbSelect("SELECT DATE_FORMAT(time, '%b %D %Y, %r') AS 'time_formatted', uuid, CONCAT(cpu_usage, '%') AS 'cpu_usage', CONCAT(network_usage, ' mbps') AS 'network_usage', CONCAT(boot_time, 's'), IF (erase_completed = 1, 'Yes', 'No') AS 'erase_completed', erase_mode, SEC_TO_TIME(erase_time) AS 'erase_time', IF (clone_completed = 1, 'Yes', 'No') AS clone_completed, IF (clone_master = 1, 'Yes', 'No') AS clone_master, SEC_TO_TIME(clone_time) AS 'clone_time' FROM jobstats WHERE tagnumber = '" . $_GET['tagnumber'] . "' AND host_connected = '1' AND (erase_completed = '1' OR clone_completed = '1') ORDER BY time DESC LIMIT 10");
                foreach ($arr as $key=>$value) {
                    echo "<tr>" . PHP_EOL;
                    echo "<td>" . $value['time_formatted'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['uuid'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['cpu_usage'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['network_usage'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['boot_time'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['erase_completed'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['erase_mode'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['erase_time'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['clone_completed'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['clone_master'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['clone_time'] . "</td>" . PHP_EOL;
                    echo "</tr>" . PHP_EOL;
                }
                ?>
            </tbody>
        </table>
        </div>

    <div class="uit-footer">
        <img src="https://uh.edu/infotech/_images/_reorg-images/uh-2ndry-uit-artboard_horiz-reverse_black.svg">
    </div>
    </body>
</html>