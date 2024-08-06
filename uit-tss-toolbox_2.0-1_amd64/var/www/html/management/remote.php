<?php
require('/var/www/html/management/header.php');
include('/var/www/html/management/mysql/mysql-functions');
//include('/var/www/html/management/php/uit-sql-refresh-remote');
$dt = new DateTimeImmutable();
$date = $dt->format('Y-m-d');
$time = $dt->format('Y-m-d H:i:s.v');
?>

<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>UIT Laptop Managment - Remote Management</title>
        <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
    </head>
    <body>
        <div class='menubar'>
            <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
            <p><span style='float: right;'>Logged in as <b><?php echo "$login_user"; ?></b>.</span></p>
            <br>
            <p><span style='float: right;'>Not <b><?php echo "$login_user"; ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
        </div>

        <div class='pagetitle'><h1>Remote Management</h1></div>
        <div class='pagetitle'><h2>The remote table allows you to remotely control laptops currently plugged into the server.</h2></div>
        <div class='pagetitle'><h3>Last updated: <?php dbSelectVal("SELECT DATE_FORMAT(CONCAT(CURDATE(), ' ', CURTIME()), '%b %D %Y, %r') AS 'result'"); echo $result; ?></h3></div>

        <div class='styled-table'>
            <table>
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
dbSelect("CALL selectRemoteStats");
foreach ($arr as $key => $value) {
    $presentLaptops = $value['Present Laptops'];
    $avgBatteryCharge = $value['Avg. Battery Charge'];
    $avgCPUTemp = $value['Avg. CPU Temp'];
    $avgDiskTemp = $value['Avg. Disk Temp'];
    $avgRealPowerDraw = $value['Avg. Actual Power Draw'];
    $totalRealPowerDraw = $value['Actual Power Draw'];
    $totalWallPowerDraw = $value['Power Draw from Wall'];
    //$osInstalledSum

    echo "<tbody>";
    echo "<tr>" . PHP_EOL;
    echo "<td>$presentLaptops</td>" . PHP_EOL;
    echo "<td>$avgBatteryCharge</td>" . PHP_EOL;
    echo "<td>$avgCPUTemp</td>" . PHP_EOL;
    echo "<td>$avgDiskTemp</td>" . PHP_EOL;
    echo "<td>$avgRealPowerDraw</td>" . PHP_EOL;
    echo "<td>$totalRealPowerDraw</td>" . PHP_EOL;
    echo "<td>$totalWallPowerDraw</td>" . PHP_EOL;
    echo "<td>" . $value['OS Installed Sum'] . "</td>" . PHP_EOL;
    echo "</tr>" . PHP_EOL;
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
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
        <form method="post">
        <tr>
            <td>
                <select name="location" id="location">
                <option>--Please Select--</option>
                <?php
                    dbSelect("SELECT location FROM locations WHERE time IN (SELECT MAX(time) FROM locations WHERE tagnumber IS NOT NULL AND location IS NOT NULL AND tagnumber IN (SELECT tagnumber FROM remote WHERE present_bool = 1 AND task IS NULL GROUP BY tagnumber) GROUP BY tagnumber) GROUP BY location ORDER BY location ASC");
                    foreach ($arr as $key => $value) {
                        if (preg_match("/^[a-zA-Z]$/", $value["location"])) {
                            echo "<option value='" . htmlspecialchars($value["location"]) . "'>" . strtoupper($value["location"]) . "</option>" . PHP_EOL;
                        } else {
                            echo "<option value='" . htmlspecialchars($value["location"]) . "'>" . $value["location"] . "</option>" . PHP_EOL;
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
        </tr>
        </form>
    </tbody>
</table>

<?php
if (isset($_POST['location']) && isset($_POST['location-action'])) {
    $sql="SELECT tagnumber AS result FROM locations WHERE time IN (SELECT MAX(time) FROM locations WHERE tagnumber IS NOT NULL AND location IS NOT NULL AND tagnumber IN (SELECT tagnumber FROM remote WHERE present_bool = 1 AND task IS NULL GROUP BY tagnumber) GROUP BY tagnumber) AND location = :location GROUP BY tagnumber";
    $stmt = $pdo->prepare($sql);
    $sqlLocation = htmlspecialchars_decode($_POST['location']);
    $stmt->bindParam(':location', $sqlLocation, PDO::PARAM_STR);
    $stmt->execute();
    $arr = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (filterArr($arr) == 0) {
        foreach ($arr as $key => $value) {
            dbUpdateRemote($value["result"], "task", $_POST['location-action']);
        }
    }
    unset($_POST['location']);
    unset($_POST['location-action']);
}
?>
</div>

<div class='pagetitle'>
    <h3>Laptops Currently Present</h3>
</div>
<div class='styled-form2'>
    <input type="text" id="myInput" onkeyup="myFunction()" placeholder="Search tagnumber..." autofocus>
</div>
        <div class='styled-table' style="width: auto; height:50%; overflow:auto; margin: 1% 1% 0% 1%;">
            <table id="myTable" width="100%">
            <thead>
                <tr>
                <th onclick="sortTable(0)">Tag Number</th>
                <th style='cursor: default;'>Last Heard</th>
                <th style='cursor: default;'>Location</th>
                <th style='cursor: default;'>Pending Job</th>
                <th style='cursor: default;'>Current Status</th>
                <th style='cursor: default;'>OS Installed</th>
                <th style='cursor: default;'>Battery Charge</th>
                <th style='cursor: default;'>Uptime</th>
                <th style='cursor: default;'>CPU Temp</th>
                <th style='cursor: default;'>Disk Temp</th>
                <th style='cursor: default;'>Actual Power Draw</th>
                </tr>
            </thead>
            <tbody>

<?php
if (isset($_POST['task'])) {
    $arrTask = explode('|', $_POST['task']);
    if (filter($arrTask[0]) == 0) {
        dbUpdateRemote($arrTask[0], "task", $arrTask[1]);
    }
    unset($_POST['task']);
}
dbSelect("SELECT tagnumber, DATE_FORMAT(present, '%b %D %Y, %r') AS 'time_formatted', (CASE WHEN task = 'update' THEN 'Update' WHEN task = 'nvmeErase' THEN 'Erase Only' WHEN task = 'hpEraseAndClone' THEN 'Erase + Clone' WHEN task = 'findmy' THEN 'Play Sound' WHEN task = 'hpCloneOnly' THEN 'Clone Only' WHEN task IS NULL THEN 'No Job' END) AS 'task_formatted', task, status, IF (os_installed = 1, 'Yes', 'No') AS 'os_installed', IF (bios_updated = '1', 'No', 'Yes') AS 'bios_updated', CONCAT(battery_charge, '%') AS 'battery_charge', battery_status, SEC_TO_TIME(uptime) AS 'uptime', CONCAT(cpu_temp, '°C') AS 'cpu_temp',  CONCAT(disk_temp, '°C') AS 'disk_temp', CONCAT(watts_now, ' Watts') AS 'watts_now' FROM remote WHERE present_bool = '1' ORDER BY FIELD(task, 'data collection', 'update', 'nvmeVerify', 'nvmeErase', 'hpCloneOnly', 'hpEraseAndClone', 'findmy', 'shutdown', 'fail-test') DESC, NOT FIELD (status, 'waiting for job') DESC, os_installed DESC, present DESC");
foreach ($arr as $key => $value) {
    echo "<tr>";
    if ($value["status"] != "waiting for job") {
        echo "<td><b>Working: <a href='tagnumber.php?tagnumber=" . $value["tagnumber"] . "' target='_blank'>" . $value["tagnumber"] . "</a></b></td>" . PHP_EOL;
    } else {
        echo "<td><b><a href='tagnumber.php?tagnumber=" . $value["tagnumber"] . "' target='_blank'>" . $value["tagnumber"] . "</a></b></td>" . PHP_EOL;
    }
    $_POST['tagnumber'] = $value["tagnumber"];
    echo "<td>" . $value["time_formatted"] . "</td>" . PHP_EOL;
    dbSelectVal("SELECT location AS 'result' FROM locations WHERE tagnumber = '" . $value['tagnumber'] . "' AND location IS NOT NULL ORDER BY time DESC LIMIT 1");
    if (preg_match("/^[a-zA-Z]$/", $result)) { 
        echo "<td><b><a href='locations.php?location=" . htmlspecialchars($result) . "' target='_blank'>" . strtoupper($result) . "</a></b></td>" . PHP_EOL;
    } else {
        echo "<td><b><a href='locations.php?location=" . htmlspecialchars($result) . "' target='_blank'>" . $result . "</a></b></td>" . PHP_EOL;
    }
    if ($value['bios_updated'] == "Yes") {
        echo "<td><form name='task' method='post'><select name='task' onchange='this.form.submit()'>";
        if (filter($value["task"]) == 1) {
            echo "<option value='" . $value["tagnumber"] . "|NULL'>No Job</option>";
        } else {
            echo "<option value='" . $value["tagnumber"] . "|" . $value["task"] . "'>" . $value["task_formatted"] . "</option>";
        }
        echo "<option value='" . $value["tagnumber"] . "|update'>Update</option>";
        echo "<option value='" . $value["tagnumber"] . "|nvmeErase'>Erase Only</option>";
        echo "<option value='" . $value["tagnumber"] . "|hpCloneOnly'>Clone Only</option>";
        echo "<option value='" . $value["tagnumber"] . "|hpEraseAndClone'>Erase + Clone</option>";
        echo "<option value='" . $value["tagnumber"] . "|findmy'>Play Sound</option>";
        echo "<option value='" . $value["tagnumber"] . "| '>Clear Pending Jobs</option>";
        echo "</select></form></td>" . PHP_EOL;
    } else {
        echo "<td><i>BIOS Out of Date</i></td>";
    }
    echo "<td>" . $value["status"] . "</td>" . PHP_EOL;
    echo "<td>" . $value["os_installed"] . "</td>" . PHP_EOL;
    echo "<td>" . $value["battery_charge"] . " (" . $value["battery_status"] . ")" . "</td>" . PHP_EOL;
    echo "<td>" . $value["uptime"] . "</td>" . PHP_EOL;
    echo "<td>" . $value["cpu_temp"] . "</td>" . PHP_EOL;
    echo "<td>" . $value["disk_temp"] . "</td>" . PHP_EOL;
    echo "<td> " . $value["watts_now"] . "</td>" . PHP_EOL;
    echo "</tr>";
}
echo "</tbody>";
echo "</table>";
echo "</div>";
?>

<div class='pagetitle' style="margin: 5% 0% 0% 0%;">
    <h3>Laptops <u>NOT</u> Currently Present</h3>
</div>
<div class='styled-form2'>
    <input type="text" id="myInput1" onkeyup="myFunction1()" placeholder="Search tagnumber...">
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
dbSelect("SELECT tagnumber, DATE_FORMAT(present, '%b %D %Y, %r') AS 'time_formatted', status, CONCAT(battery_charge, '%') AS 'battery_charge', battery_status, CONCAT(cpu_temp, '°C') AS 'cpu_temp',  CONCAT(disk_temp, '°C') AS 'disk_temp', CONCAT(watts_now, ' Watts') AS 'watts_now' FROM remote WHERE present_bool IS NULL ORDER BY present DESC, task DESC, status ASC, tagnumber DESC");
foreach ($arr as $key => $value) {
    echo "<tr>";
    echo "<td><b><a href='tagnumber.php?tagnumber=" . $value["tagnumber"] . "' target='_blank'>" . $value["tagnumber"] . "</a></b></td>" . PHP_EOL;
    echo "<td>" . $value["time_formatted"] . "</td>" . PHP_EOL;
    dbSelectVal("SELECT location AS 'result' FROM locations WHERE tagnumber = '" . $value['tagnumber'] . "' AND location IS NOT NULL ORDER BY time DESC LIMIT 1");
    if (preg_match("/^[a-zA-Z]$/", $result)) { 
        echo "<td><b><a href='locations.php?location=" . htmlspecialchars($result) . "' target='_blank'>" . strtoupper($result) . "</a></b></td>" . PHP_EOL;
    } else {
        echo "<td><b><a href='locations.php?location=" . htmlspecialchars($result) . "' target='_blank'>" . $result . "</a></b></td>" . PHP_EOL;
    }
    echo "<td>" . $value["status"] . "</td>" . PHP_EOL;
    echo "<td>" . $value["battery_charge"] . " (" . $value["battery_status"] . ")" . "</td>" . PHP_EOL;
    echo "<td>" . $value["cpu_temp"] . "</td>" . PHP_EOL;
    echo "<td>" . $value["disk_temp"] . "</td>" . PHP_EOL;
    echo "<td> " . $value["watts_now"] . "</td>" . PHP_EOL;
    echo "</tr>";
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
        function myFunction() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("myInput");
        filter = input.value.toUpperCase();
        table = document.getElementById("myTable");
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

        function sortTable(n) {
            var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
            table = document.getElementById("myTable");
            switching = true;
            // Set the sorting direction to ascending:
            dir = "asc";
            /* Make a loop that will continue until
            no switching has been done: */
            while (switching) {
                // Start by saying: no switching is done:
                switching = false;
                rows = table.rows;
                /* Loop through all table rows (except the
                first, which contains table headers): */
                for (i = 1; i < (rows.length - 1); i++) {
                // Start by saying there should be no switching:
                shouldSwitch = false;
                /* Get the two elements you want to compare,
                one from current row and one from the next: */
                x = rows[i].getElementsByTagName("TD")[n];
                y = rows[i + 1].getElementsByTagName("TD")[n];
                /* Check if the two rows should switch place,
                based on the direction, asc or desc: */
                if (dir == "asc") {
                    if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                    // If so, mark as a switch and break the loop:
                    shouldSwitch = true;
                    break;
                    }
                } else if (dir == "desc") {
                    if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                    // If so, mark as a switch and break the loop:
                    shouldSwitch = true;
                    break;
                    }
                }
                }
                if (shouldSwitch) {
                /* If a switch has been marked, make the switch
                and mark that a switch has been done: */
                rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                switching = true;
                // Each time a switch is done, increase this count by 1:
                switchcount ++;
                } else {
                /* If no switching has been done AND the direction is "asc",
                set the direction to "desc" and run the while loop again. */
                if (switchcount == 0 && dir == "asc") {
                    dir = "desc";
                    switching = true;
                }
                }
            }
        }
    </script>
    <div class="uit-footer">
        <img src="https://uh.edu/infotech/_images/_reorg-images/uh-2ndry-uit-artboard_horiz-reverse_black.svg">
    </div>
    </body>
</html>