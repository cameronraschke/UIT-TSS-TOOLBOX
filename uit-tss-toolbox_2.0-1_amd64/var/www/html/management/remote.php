<?php
require('/var/www/html/management/header.php');
require('/var/www/html/management/php/include.php');

if (isset($_POST["refresh-stats"])) {
    include('/var/www/html/management/php/uit-sql-refresh-remote');
    unset($_POST["refresh-stats"]);
}

$db = new db();
?>

<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <link rel="stylesheet" href="/jquery/jquery-ui/jquery-ui-1.14.0/jquery-ui.min.css">
        <script src="/jquery/jquery-3.7.1.min.js"></script>
        <script src="/jquery/jquery-ui/jquery-ui-1.14.0/jquery-ui.min.js"></script>
        <title>UIT Client Mgmt - Remote Jobs</title>
        <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    </head>
    <body onload="fetchHTML()">
        <script>
            function popup(tag) {
                    $( "#popup-" + tag ).dialog({
                        modal: true,
                        width: 900,
                        height: 500,
                        position: { my: "right+50%", at: "top+50%", of: window }
                    });
                document.getElementById('popup-' + tag).style.display = {style: "block"};
            }
        </script>

        <div class='menubar'>
            <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
            <p><span style='float: right;'>Logged in as <b><?php echo htmlspecialchars($login_user); ?></b>.</span></p>
            <br>
            <p><span style='float: right;'>Not <b><?php echo htmlspecialchars($login_user); ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
        </div>

        <div class='pagetitle'><h1>Remote Management</h1></div>
        <div class='pagetitle'><h2>The remote table allows you to remotely control laptops currently plugged into the server.</h2></div>
        <div class='pagetitle' id='time'><h3>Last updated: <?php $db->select("SELECT DATE_FORMAT(CONCAT(CURDATE(), ' ', CURTIME()), '%b %D %Y, %r') AS 'time_formatted'"); if (arrFilter($db->get()) === 0) { foreach ($db->get() as $key => $sqlUpdatedTime) { echo $sqlUpdatedTime["time_formatted"]; } } ?></h3></div>

        <div class='styled-table'>
            <table id='remoteStats'>
                <thead>
                <tr>
                    <th>Total Laptops Present</th>
                    <th>Average Battery Charge</th>
                    <th>Average CPU Temp</th>
                    <th>Average Disk Temp</th>
                    <th>Average Actual Power Draw</th>
                    <th>Total Actual Power Draw</th>
                    <th>Total Power Draw From Wall</th>
                    <th>Sum of OS's Installed</th>
                </tr>
                </thead>
<?php
$db->select("CALL selectRemoteStats");
if (arrFilter($db->get()) === 0) {
    foreach ($db->get() as $key => $value) {
        echo "<tbody>";
        echo "<tr>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value['Present Laptops'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value['Avg. Battery Charge'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value['Avg. CPU Temp'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value['Avg. Disk Temp'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value['Avg. Actual Power Draw'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value['Actual Power Draw'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value['Power Draw from Wall'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value['OS Installed Sum'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
        echo "</tr>" . PHP_EOL;
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
    }
}
?>

<div class='pagetitle'>
    <h3>Update Jobs for a Given Location</h3>
</div>
<div class='styled-table'>
<table>
    <thead>
        <tr>
        <th>Location</th>
        <th>Pending Job</th>
        <th>Submit</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <form method="post">
                <select name="location" id="location">
                <option>--Please Select--</option>
                <?php
                    $db->select("SELECT location FROM locations WHERE time IN (SELECT MAX(time) FROM locations WHERE tagnumber IS NOT NULL AND location IS NOT NULL AND tagnumber IN (SELECT tagnumber FROM remote WHERE present_bool = 1 AND task IS NULL GROUP BY tagnumber) GROUP BY tagnumber) GROUP BY location ORDER BY location ASC");
                    if (arrFilter($db->get()) === 0) {
                        foreach ($db->get() as $key => $value) {
                            if (preg_match("/^[a-zA-Z]$/", $value["location"])) {
                                echo "<option value='" . htmlspecialchars($value["location"]) . "'>" . htmlspecialchars(strtoupper($value["location"]), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</option>" . PHP_EOL;
                            } else {
                                echo "<option value='" . htmlspecialchars($value["location"]) . "'>" . htmlspecialchars($value["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</option>" . PHP_EOL;
                            }
                        }
                    }
                ?>
                </select>
            </td>
            <td>
                <select name="location-action" id="location-action">
                    <option value=' '>No Job</option>
                    <option value='update'>Update</option>
                    <option value='nvmeErase'>Erase Only</option>
                    <option value='hpCloneOnly'>Clone Only</option>
                    <option value='hpEraseAndClone'>Erase + Clone</option>
                    <option value='findmy'>Play Sound</option>
                    <option value=' '>Clear Pending Jobs</option>
                </select>
            </td>
            <td><input type="submit" value="Submit"></td>
            </form>
        </tr>
    </tbody>
</table>

<?php
if (isset($_POST['location']) && isset($_POST['location-action'])) {
    $db->Pselect("SELECT tagnumber FROM locations WHERE time IN (SELECT MAX(time) FROM locations WHERE tagnumber IS NOT NULL AND location IS NOT NULL AND tagnumber IN (SELECT tagnumber FROM remote WHERE present_bool = 1 AND bios_updated IS NULL AND kernel_updated = '1' AND task IS NULL GROUP BY tagnumber) GROUP BY tagnumber) AND location = :location GROUP BY tagnumber", array(':location' => htmlspecialchars_decode($_POST['location'])));

    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value) {
            $db->updateRemote($value["tagnumber"], "task", $_POST['location-action']);
        }
    }
    unset($sql);
    unset($stmt);
    unset($sqlLocation);
    unset($_POST['location']);
    unset($_POST['location-action']);
}
?>
</div>



<div id="runningJobs" style='height: 10%; width: 99%; padding: 2% 1% 5% 1%; margin: 2% 1% 5% 1%;'>
    <?php
        $db->select("SELECT COUNT(tagnumber) AS 'count', status FROM remote WHERE (task IS NOT NULL OR NOT status = 'waiting for job') AND present_bool = 1 GROUP BY status");
        if (arrFilter($db->get()) === 0) {
            foreach ($db->get() as $ley => $value) {
                echo "<h3><b>Running Jobs:</b> " . htmlspecialchars($value["count"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . " (" . htmlspecialchars($value["status"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . ")" . "</h3>";
            }
        } else {
            echo "<h3><b>Running Jobs:</b>0</h3>";
        }
    ?>
</div>



<div id='updatediv'>
<?php
$db->select("SELECT tagnumber, DATE_FORMAT(present, '%b %D %Y, %r') AS 'time_formatted', DATE_FORMAT(last_job_time, '%b %D %Y, %r') AS 'last_job_time_formatted', (CASE WHEN task = 'update' THEN 'Update' WHEN task = 'nvmeErase' THEN 'Erase Only' WHEN task = 'hpEraseAndClone' THEN 'Erase + Clone' WHEN task = 'findmy' THEN 'Play Sound' WHEN task = 'hpCloneOnly' THEN 'Clone Only' WHEN task = 'cancel' THEN 'Cancel Running Jobs' WHEN task IS NULL THEN 'No Job' END) AS 'task_formatted', task, status, IF (os_installed = 1, 'Yes', 'No') AS 'os_installed', IF (bios_updated = '1', 'No', 'Yes') AS 'bios_updated', kernel_updated, CONCAT(battery_charge, '%') AS 'battery_charge', battery_status, SEC_TO_TIME(uptime) AS 'uptime', CONCAT(cpu_temp, '°C') AS 'cpu_temp',  CONCAT(disk_temp, '°C') AS 'disk_temp', CONCAT(watts_now, ' Watts') AS 'watts_now' FROM remote WHERE present_bool = '1' ORDER BY bios_updated DESC, kernel_updated DESC, FIELD(task, 'data collection', 'update', 'nvmeVerify', 'nvmeErase', 'hpCloneOnly', 'hpEraseAndClone', 'findmy', 'shutdown', 'fail-test') DESC, FIELD (status, 'waiting for job', '%') ASC, os_installed DESC, last_job_time DESC");
if (arrFilter($db->get()) === 0) {
    foreach ($db->get() as $key => $value) {
        echo "<div style='display: none;' id='popup-" . $value["tagnumber"] . "' title='Change Job - " . $value["tagnumber"] . "'>" . PHP_EOL;
        echo "<table class='styled-table'>";
        echo "<tr>";
        echo "<th>Last Job Time</th>";
        echo "<th>Pending Job</th>";
        echo "<th>Current Status</th>";
        echo "<th>OS Installed</th>";
        echo "<th>Bios Updated</th>";
        echo "<th>Kernel Updated</th>";
        echo "<th>Uptime</th>";
        echo "</tr>";
        echo "<tr>";
        echo "<td>" . htmlspecialchars($value["last_job_time_formatted"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>";
        echo "<td>" . htmlspecialchars($value["task_formatted"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>";
        echo "<td>" . htmlspecialchars($value["status"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>";
        echo "<td>" . htmlspecialchars($value["os_installed"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>";
        echo "<td>" . htmlspecialchars($value["bios_updated"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>";
        if ($value["kernel_updated"] === 1) {
            echo "<td>Yes</td>";
        } else {
            echo "<td>No</td>";
        }
        echo "<td>" . htmlspecialchars($value["uptime"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>";
        echo "</tr>";
        echo "</table>";
        echo "<form name='task' method='post'>" . PHP_EOL;
        echo "<select name='task'>" . PHP_EOL;
        if (strFilter($value["task"]) === 1) {
            echo "<option id='pendingJob' value='" . $value["tagnumber"] . "|NULL'>No Job Queued</option>" . PHP_EOL;
        } else {
            echo "<option id='pendingJob' value='" . $value["tagnumber"] . "|" . $value["task"] . "'><b>In progress: </b>" . $value["task_formatted"] . "</option>" . PHP_EOL;
        }
        echo "<option value='" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|update'>Update</option>";
        echo "<option value='" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|nvmeErase'>Erase Only</option>";
        echo "<option value='" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|hpCloneOnly'>Clone Only</option>";
        echo "<option value='" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|hpEraseAndClone'>Erase + Clone</option>";
        echo "<option value='" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|findmy'>Play Sound</option>";
        echo "<option value='" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "| '>Clear Pending Jobs</option>";
        echo "</select>" . PHP_EOL;
        echo "<input type='submit' value='Submit'></form>";
        echo "</div>";
    }
}
?>
</div>



<div class='pagetitle'>
    <h3>Laptops Currently Present</h3>
</div>
<div class='styled-form'>
    <form method='post'>
        <div>
            <button type="submit">Refresh Table</button>
        </div>
        <input type="hidden" id="refresh-stats" name="refresh-stats" value="refresh-stats" />
    </form>
</div>
        <div class='styled-table' style="width: auto; height:50%; overflow:auto; margin: 1% 1% 0% 1%;">
            <table id="myTable" width="100%">
            <thead>
                <tr>
                <th>Tag Number</th>
                <th>Last Job Time</th>
                <th>Location</th>
                <th>Pending Job</th>
                <th>Current Status</th>
                <th>OS Installed</th>
                <th>Battery Charge</th>
                <th>Uptime</th>
                <th>CPU Temp</th>
                <th>Disk Temp</th>
                <th>Actual Power Draw</th>
                </tr>
            </thead>
            <tbody>

<?php
if (isset($_POST['task'])) {
    $arrTask = explode('|', $_POST['task']);
    if (strFilter($arrTask[0]) === 0) {
        $db->updateRemote(htmlspecialchars_decode($arrTask[0]), "task", htmlspecialchars_decode($arrTask[1]));
    }
    unset($_POST['task']);
}
$db->select("SELECT tagnumber, DATE_FORMAT(present, '%b %D %Y, %r') AS 'time_formatted', DATE_FORMAT(last_job_time, '%b %D %Y, %r') AS 'last_job_time_formatted', (CASE WHEN task = 'update' THEN 'Update' WHEN task = 'nvmeErase' THEN 'Erase Only' WHEN task = 'hpEraseAndClone' THEN 'Erase + Clone' WHEN task = 'findmy' THEN 'Play Sound' WHEN task = 'hpCloneOnly' THEN 'Clone Only' WHEN task = 'cancel' THEN 'Cancel Running Jobs' WHEN task IS NULL THEN 'No Job' END) AS 'task_formatted', task, status, IF (os_installed = 1, 'Yes', 'No') AS 'os_installed', IF (bios_updated = '1', 'No', 'Yes') AS 'bios_updated', kernel_updated, CONCAT(battery_charge, '%') AS 'battery_charge', battery_status, SEC_TO_TIME(uptime) AS 'uptime', CONCAT(cpu_temp, '°C') AS 'cpu_temp',  CONCAT(disk_temp, '°C') AS 'disk_temp', CONCAT(watts_now, ' Watts') AS 'watts_now' FROM remote WHERE present_bool = '1' ORDER BY bios_updated DESC, kernel_updated DESC, FIELD(task, 'data collection', 'update', 'nvmeVerify', 'nvmeErase', 'hpCloneOnly', 'hpEraseAndClone', 'findmy', 'shutdown', 'fail-test') DESC, FIELD (status, 'waiting for job', '%') ASC, os_installed DESC, last_job_time DESC");
if (arrFilter($db->get()) === 0) {
    foreach ($db->get() as $key => $value) {
        echo "<tr>" . PHP_EOL;
        echo "<td>";
        if ($value["status"] !== "waiting for job" && preg_match("/^fail\ \-.*$/i", $value["status"]) !== 1) {
            echo "<b>In Progress: </b>";
        }

        $db->Pselect("SELECT present_bool, kernel_updated FROM remote WHERE tagnumber = :tagnumber", array(':tagnumber' => $value["tagnumber"]));
        if (arrFilter($db->get()) === 0) {
            foreach ($db->get() as $key => $value1) {
                if ($value1["present_bool"] === 1 && $value1["kernel_updated"] === 1) {
                    echo "<b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b> <span style='color: #00B388'>&#10004;&#65039;</span>" . PHP_EOL;
                } elseif ($value1["present_bool"] !== 1 && $value1["kernel_updated"] !== 1) {
                    echo "<b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b> <span style='color: #C8102E'>&#10060;</span>" . PHP_EOL;
                } elseif ($value1["present_bool"] === 1 && $value1["kernel_updated"] !== 1) {
                    echo "<b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b> <span style='color: #F6BE00'>&#9888;&#65039;</span>" . PHP_EOL;
                } elseif ($value1["present_bool"] !== 1 && $value1["kernel_updated"] === 1) {
                    echo "<b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b> <span style='color: #C8102E'>&#10060;</span>" . PHP_EOL;
                } else {
                    echo "<b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b>" . PHP_EOL;
                }
            }
        } else {
            echo "<b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b>" . PHP_EOL;
        }
        unset($value1);
        echo "</td>" . PHP_EOL;
        $_POST['tagnumber'] = $value["tagnumber"];
        echo "<td id='lastJobTime'>" . $value["last_job_time_formatted"] . "</td>" . PHP_EOL;
        $db->Pselect("SELECT location FROM locations WHERE tagnumber = :tagnumber AND location IS NOT NULL ORDER BY time DESC LIMIT 1", array(':tagnumber' => $value["tagnumber"]));
        if (arrFilter($db->get()) === 0) {
            foreach ($db->get() as $key => $value1) {
                if (preg_match("/^[a-zA-Z]$/", $value1["location"])) { 
                    echo "<td id='presentLocation'><b><a href='locations.php?location=" . htmlspecialchars($value1["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars(strtoupper($value1["location"]), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b></td>" . PHP_EOL;
                } elseif (preg_match("/^checkout$/", $value1["location"])) {
                    echo "<td id='presentLocation'><b><a href='locations.php?location=" . htmlspecialchars($value1["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . "Checkout" . "</a></b></td>" . PHP_EOL;
                } else {
                    echo "<td id='presentLocation'><b><a href='locations.php?location=" . htmlspecialchars($value1["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value1["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b></td>" . PHP_EOL;
                }
            }
        } else {
            echo "<td><b>" . "<i>No Location</i>" . "</b></td>" . PHP_EOL;
        }
        unset($value1);

        if ($value["bios_updated"] === "Yes" && strFilter($value["kernel_updated"]) === 0) {
            // echo "<td><form name='task' method='post'><select name='task' onchange='this.form.submit()'>";
            // if (strFilter($value["task"]) === 1) {
            //     echo "<option id='pendingJob' value='" . $value["tagnumber"] . "|NULL'>No Job</option>";
            // } else {
            //     echo "<option id='pendingJob' value='" . $value["tagnumber"] . "|" . $value["task"] . "'>" . $value["task_formatted"] . "</option>";
            // }
            // echo "<option value='" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|update'>Update</option>";
            // echo "<option value='" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|nvmeErase'>Erase Only</option>";
            // echo "<option value='" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|hpCloneOnly'>Clone Only</option>";
            // echo "<option value='" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|hpEraseAndClone'>Erase + Clone</option>";
            // echo "<option value='" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|findmy'>Play Sound</option>";
            // echo "<option value='" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "| '>Clear Pending Jobs</option>";
            // echo "</select></form></td>" . PHP_EOL;
            echo "<td>";
        if (strFilter($value["task"]) === 1) {
                echo "<button onclick='popup(" . $value["tagnumber"] . ")'>Change Job</button>";
            } else {
                echo "<a style='cursor: pointer' onclick='popup(" . $value["tagnumber"] . ")'><b><u>" . $value["task_formatted"] . "</u></b></a>";
            }
            echo "</select></td>";
        } elseif ($value["bios_updated"] !== "Yes" && strFilter($value["kernel_updated"]) === 1) {
            echo "<td><i>BIOS and Kernel Out of Date</i></td>" . PHP_EOL;
        } elseif ($value["bios_updated"] !== "Yes") {
            echo "<td><i><a style='color: gray;' href='/documentation/bios-update.php' target='_blank'>BIOS Out of Date</a></i></td>" . PHP_EOL;
        } elseif (strFilter($value["kernel_updated"]) === 1) {
            echo "<td><i><a style='color: gray;' href='/documentation/kernel-update.php' target='_blank'>Kernel Out of Date</a></i></td>" . PHP_EOL;
        } else {
            echo "<td><i>Cannot Start Job - Unknown Error</i></td>" . PHP_EOL;
            echo "<td style='color: gray;'><i>Cannot Start Job - Unknown Error</i></td>" . PHP_EOL;
        }
        echo "<td id='presentStatus'>" . htmlspecialchars($value["status"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
        echo "<td id='osInstalled'>" . htmlspecialchars($value["os_installed"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value["battery_charge"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . " (" . $value["battery_status"] . ")" . "</td>" . PHP_EOL;
        echo "<td id='uptime'>" . htmlspecialchars($value["uptime"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
        echo "<td id='presentCPUTemp'>" . htmlspecialchars($value["cpu_temp"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
        echo "<td id='presentDiskTemp'>" . htmlspecialchars($value["disk_temp"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
        echo "<td id='presentPowerDraw'> " . htmlspecialchars($value["watts_now"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
        echo "</tr>";
    }
}
echo "</tbody>";
echo "</table>";
echo "</div>";
?>

<div class='pagetitle' style="margin: 5% 0% 0% 0%;">
    <h3>Laptops <u>NOT</u> Currently Present</h3>
</div>
<div class='styled-form2'>
    <input type="text" id="myInput1" onkeyup="myFunction1()" placeholder="Search tag number...">
</div>
        <div class='styled-table' style="width: auto; height:50%; overflow:auto; margin: 1% 1% 0% 1%;">
            <table id="myTable1" width="100%">
            <thead>
                <tr>
                <th>Tag Number</th>
                <th>Last Heard</th>
                <th>Last Location</th>
                <th>Current Status</th>
                <th>Battery Charge</th>
                <th>CPU Temp</th>
                <th>Disk Temp</th>
                <th>Actual Power Draw</th>
                </tr>
            </thead>

<?php
$db->select("SELECT tagnumber, DATE_FORMAT(present, '%b %D %Y, %r') AS 'time_formatted', status, CONCAT(battery_charge, '%') AS 'battery_charge', battery_status, CONCAT(cpu_temp, '°C') AS 'cpu_temp',  CONCAT(disk_temp, '°C') AS 'disk_temp', CONCAT(watts_now, ' Watts') AS 'watts_now' FROM remote WHERE present_bool IS NULL ORDER BY present DESC, task DESC, status ASC, tagnumber DESC");
if (arrFilter($db->get()) === 0) {
    foreach ($db->get() as $key => $value) {
        echo "<tr>";
        $db->Pselect("SELECT present_bool, kernel_updated FROM remote WHERE tagnumber = :tagnumber", array(':tagnumber' => $value["tagnumber"]));
        if (arrFilter($db->get()) === 0) {
            foreach ($db->get() as $key => $value1) {
                if ($value1["present_bool"] === 1 && $value1["kernel_updated"] === 1) {
                    echo "<td><b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b> <span style='color: #00B388'>&#10004;&#65039;</span></td>" . PHP_EOL;
                } elseif ($value1["present_bool"] !== 1 && $value1["kernel_updated"] !== 1) {
                    echo "<td><b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b> <span style='color: #C8102E'>&#10060;</span></td>" . PHP_EOL;
                } elseif ($value1["present_bool"] === 1 && $value1["kernel_updated"] !== 1) {
                    echo "<td><b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b> <span style='color: #F6BE00'>&#9888;&#65039;</span></td>" . PHP_EOL;
                } elseif ($value1["present_bool"] !== 1 && $value1["kernel_updated"] === 1) {
                    echo "<td><b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b> <span style='color: #C8102E'>&#10060;</span></td>" . PHP_EOL;
                } else {
                    echo "<td><b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b></td>" . PHP_EOL;
                }
            }
        } else {
            echo "<td><b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b></td>" . PHP_EOL;
        }
        unset($value1);
        echo "<td>" . $value["time_formatted"] . "</td>" . PHP_EOL;
        $db->Pselect("SELECT location FROM locations WHERE tagnumber = :tagnumber AND location IS NOT NULL ORDER BY time DESC LIMIT 1", array(':tagnumber' => $value["tagnumber"]));
        if (arrFilter($db->get()) === 0) {
            foreach ($db->get() as $key => $value1) {
                if (preg_match("/^[a-zA-Z]$/", $value1["location"])) { 
                    echo "<td id='absentLocation'><b><a href='locations.php?location=" . htmlspecialchars($value1["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars(strtoupper($value1["location"]), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b></td>" . PHP_EOL;
                } elseif (preg_match("/^checkout$/", $value1["location"])) {
                    echo "<td id='absentLocation'><b><a href='locations.php?location=" . htmlspecialchars($value1["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . "Checkout" . "</a></b></td>" . PHP_EOL;
                } else {
                    echo "<td id='absentLocation'><b><a href='locations.php?location=" . htmlspecialchars($value1["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value1["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b></td>" . PHP_EOL;
                }
            }
        } else {
            echo "<td><b>" . "<i>No Location</i>" . "</b></td>" . PHP_EOL;
        }
        unset($value1);

        echo "<td id='absentStatus'>" . htmlspecialchars($value["status"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
        if (strFilter($value["battery_charge"]) === 0) {
            echo "<td>" . htmlspecialchars($value["battery_charge"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
            if (strFilter($value["battery_status"]) === 0) {
                " (" . htmlspecialchars($value["battery_status"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . ")";
            }
            echo "</td>" . PHP_EOL;
        } else {
            echo "<td></td>";
        }
        echo "<td>" . htmlspecialchars($value["cpu_temp"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value["disk_temp"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
        echo "<td> " . htmlspecialchars($value["watts_now"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
        echo "</tr>";
    }
}
echo "</tbody>";
echo "</table>";
echo "</div>";
?>

    <script>
        if ( window.history.replaceState ) {
            window.history.replaceState( null, null, window.location.href );
        }
    </script>

    <script>
        var i = 0;
        function fetchHTML() {
            const var1 = setTimeout(function() {
            fetch('/remote.php')
            .then((response) => {
                    return response.text();
            })
            .then((html) => {
                //document.body.innerHTML = html
                const parser = new DOMParser()
                const doc = parser.parseFromString(html, "text/html")
                //Update time at the top
                const time = doc.getElementById('time').innerHTML
                document.getElementById("time").innerHTML = time
                //Update remote stats 
                const remoteStats = doc.getElementById('remoteStats').innerHTML
                document.getElementById("remoteStats").innerHTML = remoteStats
                //Update client table
                const myTable = doc.getElementById('myTable').innerHTML
                document.getElementById("myTable").innerHTML = myTable
                //absentStatus
                const absentStatus = doc.getElementById('absentStatus').innerHTML
                document.getElementById("absentStatus").innerHTML = absentStatus
                //absentLocation (WIP)
                //Update DIVs
                const updatediv = doc.getElementById('updatediv').innerHTML
                document.getElementById("updatediv").innerHTML = updatediv
            });
            fetchHTML();
        }, 3000)}


        function myFunction1() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("myInput1");
        filter = input.value.toUpperCase();
        table = document.getElementById("myTable1");
        tr = table.getElementsByTagName("tr");

        for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td")[0];
            if (td) {
            txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
            }
        }
        }

    </script>
    <div class="uit-footer">
        <img src="/images/uh-footer.svg">
    </div>
    </body>
</html>