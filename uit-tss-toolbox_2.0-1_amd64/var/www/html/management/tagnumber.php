<?php
require('/var/www/html/management/header.php');
require('/var/www/html/management/php/include.php');

$db = new db();

if (isset($_POST["task"])) {
    $arrTask = explode('|', $_POST["task"]);
    if (strFilter($arrTask[0]) === 0) {
        $db->updateRemote($arrTask[0], "task", $arrTask[1]);
    }
    unset($_POST["task"]);
}
?>

<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>UIT Laptop Management - <?php echo htmlspecialchars($_GET['tagnumber']); ?></title>
        <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
    </head>
    <body>
        <div class='menubar'>
            <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
            <p><span style='float: right;'>Logged in as <b><?php echo "$login_user"; ?></b>.</span></p>
            <br>
            <p><span style='float: right;'>Not <b><?php echo "$login_user"; ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
        </div>

        <div class='pagetitle'><h1>Client Lookup (<?php echo htmlspecialchars($_GET['tagnumber']); ?>)</h1></div>
        <div class='pagetitle'><h2>Lookup data for a specific client.</h2></div>
        <div class='pagetitle'><h3>Last updated: <?php $db->select("SELECT DATE_FORMAT(CONCAT(CURDATE(), ' ', CURTIME()), '%b %D %Y, %r') AS 'time_formatted'"); if (arrFilter($db->get()) === 0) { foreach ($db->get() as $key => $sqlUpdatedTime) { echo $sqlUpdatedTime["time_formatted"]; } } ?></h3></div>

        <div class='laptop-images'>
        <?php
            $db->select("SELECT system_model FROM system_data WHERE tagnumber = '" . htmlspecialchars_decode($_GET['tagnumber']) . "'");
            if (arrFilter($db->get()) === 0) {
                foreach ($db->get() as $key => $value) {
                    if ($result === "HP ProBook 450 G6") {
                        echo "<img src='/images/hpProBook450G6.avif'>" . PHP_EOL;
                    } elseif ($result === "Latitude 7400") {
                        echo "<img src='/images/dellLatitude7400.avif'>" . PHP_EOL;
                    } elseif ($result === "Latitude 3560") {
                        echo "<img src='/images/Latitude3560.jpg'>" . PHP_EOL;
                    } elseif ($result === "Latitude 3500") {
                        echo "<img src='/images/Latitude3500.avif'>" . PHP_EOL;
                    }
                }
            }
        ?>
        </div>

        <div style='margin: 5% 0% 12% 1%'>
            <div style='width: 25%; float: left;'>
            <div class="pagetitle"><h3>Update Job</h3></div>
            <div class="page-content">
                <form name="task" method="post">
                    <select name="task" onchange='this.form.submit()'>
                        <?php
                        if ($_GET['tagnumber']) {
                            dbSelect("SELECT (CASE WHEN task = 'update' THEN 'Update' WHEN task = 'nvmeErase' THEN 'Erase Only' WHEN task = 'hpEraseAndClone' THEN 'Erase + Clone' WHEN task = 'findmy' THEN 'Play Sound' WHEN task = 'hpCloneOnly' THEN 'Clone Only' WHEN task IS NULL THEN 'No Job' END) AS 'formatted_task', task FROM remote WHERE tagnumber = '" . htmlspecialchars_decode($_GET['tagnumber']) . "'");
                            foreach ($arr as $key => $value) {
                                echo "<option value='" . $_GET["tagnumber"] . "|" . $value["task"] . "'>" . $value["formatted_task"] . "</option>";
                                echo "<option value='" . $_GET["tagnumber"] . "|update'>Update</option>";
                                echo "<option value='" . $_GET["tagnumber"] . "|nvmeErase'>Erase Only</option>";
                                echo "<option value='" . $_GET["tagnumber"] . "|hpCloneOnly'>Clone Only</option>";
                                echo "<option value='" . $_GET["tagnumber"] . "|hpEraseAndClone'>Erase + Clone</option>";
                                echo "<option value='" . $_GET["tagnumber"] . "|findmy'>Play Sound</option>";
                                echo "<option value='" . $_GET["tagnumber"] . "| '>Clear Pending Jobs</option>";
                            }
                        }
                        ?>
                    </select>
                </form>
            </div>
            </div>
            
            <div style='width: 40%; float: left;'>

                <?php
                dbSelect("SELECT DATE_FORMAT(present, '%b %D %Y, %r') AS 'time_formatted', status, present_bool FROM remote WHERE tagnumber = '" . $_GET["tagnumber"] . "'");
                foreach ($arr as $key=>$value) {
                    if ($value["present_bool"] == 1) {
                        $presentBool = "Online <span style='color: #00B388'>&#10004;</span>";
                    } else {
                        $presentBool = "Offline <span style='color: #C8102E'>&#10007;</span>";
                    }
                    echo "<div class='pagetitle'><h3>Status (" . $presentBool . ")</h3></div>";
                    echo "<div class='page-content'>";
                    echo "<p><b>'" . $value["status"] . "'</b> at " . $value["time_formatted"] . "</p>" . PHP_EOL;
                }
                ?>
            </div>
            </div>
        </div>

        
        <div class='pagetitle'><h3>General Client Info - <u><?php echo $_GET["tagnumber"]; ?></u></h3></div>
        <div class='styled-table' style="width: auto; height:10%; overflow:auto; margin: 1% 1% 0% 1%;">
        <table width="100%">
            <thead>
                <tr>
                <th>System Serial</th>
                <th>Wi-Fi MAC Address</th>
                <th>Department</th>
                <th>System Manufacturer</th>
                <th>System Model</th>
                <th>BIOS Version</th>
                <th>CPU Model</th>
                </tr>
            </thead>
            <tbody>
                <?php
                dbSelect("SELECT t1.system_serial, t2.wifi_mac, (CASE WHEN t1.department='techComm' THEN 'Tech Commons (TSS)' WHEN t1.department='property' THEN 'Property' WHEN t1.department='shrl' THEN 'SHRL' ELSE '' END) AS 'department', t2.system_manufacturer, t2.system_model, t2.cpu_model FROM jobstats t1 INNER JOIN system_data t2 ON t1.tagnumber = t2.tagnumber WHERE t1.tagnumber = '" . htmlspecialchars_decode($_GET['tagnumber']) . "' AND t2.tagnumber = '" . htmlspecialchars_decode($_GET['tagnumber']) . "' AND host_connected = '1' ORDER BY t1.time DESC LIMIT 1");
                foreach ($arr as $key => $value) {
                   echo "<tr>" . PHP_EOL;
                   echo "<td>" . $value['system_serial'] . "</td>" . PHP_EOL;
                   echo "<td>" . $value['wifi_mac'] . "</td>" . PHP_EOL;
                   echo "<td>" . $value['department'] . "</td>" . PHP_EOL;
                   echo "<td>" . $value['system_manufacturer'] . "</td>" . PHP_EOL;
                   echo "<td>" . $value['system_model'] . "</td>" . PHP_EOL;
                   dbSelectVal("SELECT bios_version AS 'result' FROM jobstats WHERE bios_version IS NOT NULL AND tagnumber = '" . $_GET["tagnumber"] . "' ORDER BY time DESC LIMIT 1");
                   echo "<td>" . $result . "</td>" . PHP_EOL;
                   echo "<td>" . $value['cpu_model'] . "</td>" . PHP_EOL;
                   echo "</tr>" . PHP_EOL;
                }
                ?>
            </tbody>
        </table>
        </div>

        <div class='pagetitle'><h3>Location Info</h3></div>

        <div class='styled-table' style="width: auto; height:10%; overflow:auto; margin: 1% 1% 0% 1%;">
        <table width="100%">
            <thead>
                <tr>
                <th>Time</th>
                <th>Location</th>
                <th>Status</th>
                <th>OS Installed</th>
                <th>Disk Removed</th>
                <th>Note</th>
                </tr>
            </thead>
            <tbody>
                <?php
                dbSelect("SELECT DATE_FORMAT(time, '%b %D %Y, %r') AS 'time_formatted', location, IF (status='0' OR status IS NULL, 'Working', 'Broken') AS 'status', IF (disk_removed = 1, 'Yes', 'No') AS 'disk_removed', IF (os_installed = 1, 'Yes', 'No') AS 'os_installed', note FROM locations WHERE tagnumber = '" . htmlspecialchars_decode($_GET['tagnumber']) . "' ORDER BY time DESC LIMIT 1");
                foreach ($arr as $key => $value) {
                echo "<tr>" . PHP_EOL;
                echo "<td>" . $value['time_formatted'] . "</td>" . PHP_EOL;
                echo "<td>" . $value['location'] . "</td>" . PHP_EOL;
                echo "<td>" . $value['status'] . "</td>" . PHP_EOL;
                echo "<td>" . $value['os_installed'] . "</td>" . PHP_EOL;
                echo "<td>" . $value['disk_removed'] . "</td>" . PHP_EOL;
                echo "<td>" . $value['note'] . "</td>" . PHP_EOL;
                echo "</tr>" . PHP_EOL;
                }
                ?>
            </tbody>
        </table>
        </div>

        <div class='pagetitle'><h3>Job Info</h3></div>
        <div class='styled-table' style="width: auto; height:30%; overflow:auto; margin: 1% 1% 0% 1%;">
        <table width="100%">
            <thead>
                <tr>
                <th>Time</th>
                <th>CPU Usage</th>
                <th>Network Usage</th>
                <th>Erase Completed</th>
                <th>Erase Mode</th>
                <th>Erase Time Elapsed</th>
                <th>Clone Completed</th>
                <th>Clone Master</th>
                <th>Clone Time</th>
                <th>BIOS Version</th>
                </tr>
            </thead>
            <tbody>
                <?php
                dbSelect("SELECT DATE_FORMAT(time, '%b %D %Y, %r') AS 'time_formatted', CONCAT(cpu_usage, '%') AS 'cpu_usage', CONCAT(network_usage, ' mbps') AS 'network_usage', IF (erase_completed = 1, 'Yes', 'No') AS 'erase_completed', erase_mode, SEC_TO_TIME(erase_time) AS 'erase_time', IF (clone_completed = 1, 'Yes', 'No') AS clone_completed, IF (clone_master = 1, 'Yes', 'No') AS clone_master, SEC_TO_TIME(clone_time) AS 'clone_time', bios_version FROM jobstats WHERE tagnumber = '" . htmlspecialchars_decode($_GET['tagnumber']) . "' AND host_connected = '1' AND (erase_completed = '1' OR clone_completed = '1') ORDER BY time DESC LIMIT 10");
                foreach ($arr as $key=>$value) {
                    echo "<tr>" . PHP_EOL;
                    echo "<td>" . $value['time_formatted'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['cpu_usage'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['network_usage'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['erase_completed'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['erase_mode'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['erase_time'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['clone_completed'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['clone_master'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['clone_time'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['bios_version'] . "</td>" . PHP_EOL;
                    echo "</tr>" . PHP_EOL;
                }
                ?>
            </tbody>
        </table>
        </div>

        <script>
            if ( window.history.replaceState ) {
                window.history.replaceState( null, null, window.location.href );
            }
        </script>

    <div class="uit-footer">
        <img src="https://uh.edu/infotech/_images/_reorg-images/uh-2ndry-uit-artboard_horiz-reverse_black.svg">
    </div>
    </body>
</html>