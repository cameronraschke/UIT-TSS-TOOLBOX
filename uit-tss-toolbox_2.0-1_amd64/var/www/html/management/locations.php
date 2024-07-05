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
    </head>
    <body>
        <div class='menubar'>
            <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
            <p><span style='float: right;'>Logged in as <b><?php echo "$login_user"; ?></b>.</span></p>
            <br>
            <p><span style='float: right;'>Not <b><?php echo "$login_user"; ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
        </div>

        <div class='pagetitle'><h1>Locations Table</h1></div>
        <div class='pagetitle'><h2>The locations table displays the location and status of every client.</h2></div>
        <div class='pagetitle'><h3>Last updated: <?php dbSelectVal("SELECT CONVERT(time, DATETIME(0)) AS result FROM locations ORDER BY time DESC LIMIT 1"); echo $result; ?></h3></div>

        <?php
        if (!empty($_POST['tagnumber'])) {
            echo "<div class='location-form'>" . PHP_EOL;
            dbSelect("SELECT * FROM locations WHERE tagnumber = '" . $_POST['tagnumber'] . "' ORDER BY time DESC LIMIT 1");
            if (!empty($arr)) {
                foreach ($arr as $key => $value) {
                    echo "<form method='post'>" . PHP_EOL;
                    echo "<label for='tagnumber'>Tag Number</label>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<input type='text' style='background-color:#888B8D;' id='tagnumber' name='tagnumber' value='" . $_POST['tagnumber'] . "' readonly required>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<label for='serial'>Serial Number</label>";
                    echo "<br>" . PHP_EOL;
                    echo "<input type='text' style='background-color:#888B8D;' id='serial' name='serial' value='" . $value['system_serial'] . "' readonly>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<label for='department'>Department</label>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    dbSelectVal("SELECT department AS result FROM jobstats WHERE tagnumber = '" . $_POST['tagnumber'] . "' ORDER BY time DESC LIMIT 1");
                    echo "<select name='department' id='department'>" . PHP_EOL;
                    echo "<option value='$result'>$result</option>" . PHP_EOL;
                    echo "<option value='techComm'>Tech Comms (Default)</option>" . PHP_EOL;
                    echo "<option value='property'>Property Management</option>" . PHP_EOL;
                    echo "<option value='shrl'>SHRL (Kirven)</option>" . PHP_EOL;
                    echo "</select>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<label for='location'>Location (" . $value['time'] . ")</label>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<input type='text' id='location' name='location' value='" . htmlspecialchars($value['location']) . "' required>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<label for='note'>Note</label>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    if ($_POST['status'] == "1") {
                        dbSelectVal("SELECT note AS result FROM locations WHERE tagnumber = '" . $_POST['tagnumber'] . "' AND note IS NOT NULL AND NOT note = 'Unattended' ORDER BY time DESC LIMIT 1");
                        echo "<input type='text' id='note' name='note' value = '" . htmlspecialchars($result) . "'>" . PHP_EOL;
                    } else {
                        echo "<input type='text' id='note' name='note'>" . PHP_EOL;
                    }
                    echo "<br>" . PHP_EOL;
                    echo "<input type='hidden' name='status' value='" . $_POST['status'] . "'>";
                    if ($_POST['status'] == "1") {
                        dbSelectVal("SELECT disk_removed AS result FROM locations WHERE tagnumber = '" . $_POST['tagnumber'] . "' ORDER BY time DESC LIMIT 1");
                        echo "<label for='disk_removed'>Disk removed?</label>" . PHP_EOL;
                        echo "<br>" . PHP_EOL;
                        echo "<select name='disk_removed' id='disk_removed'>" . PHP_EOL;
                        echo "<option value='0'>No</option>" . PHP_EOL;
                        echo "<option value='1'>Yes</option>" . PHP_EOL;
                        echo "</select>" . PHP_EOL;
                        echo "<br>" . PHP_EOL;
                        echo "<input type='submit' value='Update Location (Broken)'>" . PHP_EOL;
                    } else {
                        echo "<input type='hidden' name='disk_removed' value='0'>";
                        echo "<input type='submit' value='Update Location (Working)'>" . PHP_EOL;
                    }
                    echo "</form>" . PHP_EOL;
                    echo "<div class='page-content'><a href='locations.php'>Update a different laptop.</a></div>" . PHP_EOL;
                    echo "</div>";
                }
            } else {
                echo "<form method='post'>" . PHP_EOL;
                echo "<label for='tagnumber'>Tag Number</label>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<input type='text' style='background-color:#888B8D;' id='tagnumber' name='tagnumber' value='" . $_POST['tagnumber'] . "' readonly required>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<label for='serial'>Serial Number</label>";
                echo "<br>" . PHP_EOL;
                echo "<input type='text' id='serial' name='serial'>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<label for='department'>Department</label>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                dbSelectVal("SELECT department AS result FROM jobstats WHERE tagnumber = '" . $_POST['tagnumber'] . "' ORDER BY time DESC LIMIT 1");
                echo "<select name='department' id='department'>" . PHP_EOL;
                echo "<option value='$result'>$result</option>" . PHP_EOL;
                echo "<option value='techComm'>Tech Comms (Default)</option>" . PHP_EOL;
                echo "<option value='property'>Property Management</option>" . PHP_EOL;
                echo "<option value='shrl'>SHRL (Kirven)</option>" . PHP_EOL;
                echo "</select>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<label for='location'>Location (" . $value['time'] . ")</label>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<input type='text' id='location' name='location' value='" . htmlspecialchars($value['location']) . "' required>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<label for='note'>Note</label>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                if ($_POST['status'] == "1") {
                    dbSelectVal("SELECT note AS result FROM locations WHERE tagnumber = '" . $_POST['tagnumber'] . "' AND note IS NOT NULL AND NOT note = 'Unattended' ORDER BY time DESC LIMIT 1");
                    echo "<input type='text' id='note' name='note' value = '" . htmlspecialchars($result) . "'>" . PHP_EOL;
                } else {
                    echo "<input type='text' id='note' name='note'>" . PHP_EOL;
                }
                echo "<br>" . PHP_EOL;
                echo "<input type='hidden' name='status' value='" . $_POST['status']. "'>";
                if ($_POST['status'] == "1") {
                    dbSelectVal("SELECT disk_removed AS result FROM locations WHERE tagnumber = '" . $_POST['tagnumber'] . "' ORDER BY time DESC LIMIT 1");
                    echo "<label for='disk_removed'>Disk removed?</label>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<select name='disk_removed' id='disk_removed'>" . PHP_EOL;
                    echo "<option value='0'>No</option>" . PHP_EOL;
                    echo "<option value='1'>Yes</option>" . PHP_EOL;
                    echo "</select>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<input type='submit' value='Update Location (Broken)'>" . PHP_EOL;
                } else {
                    echo "<input type='hidden' name='disk_removed' value='0'>";
                    echo "<input type='submit' value='Update Location (Working)'>" . PHP_EOL;
                }
                echo "</form>" . PHP_EOL;
                echo "<div class='page-content'><a href='locations.php'>Update a different laptop.</a></div>" . PHP_EOL;
                echo "</div>";
            }
            $uuid = uniqid("location-", true);
            $tagNum = $_POST['tagnumber'];
            $serial = $_POST['serial'];
            $department = $_POST['department'];
            $location = $_POST['location'];
            $status = $_POST['status'];
            $note = $_POST['note'];
            $diskRemoved = $_POST['disk_removed'];

            if (isset($_POST['serial'])) {
                #Not the same insert statment as client parse code, ether address is DEFAULT here.
                dbInsertJob($uuid);
                dbUpdateJob("tagnumber", "$tagNum", "$uuid");
                dbUpdateJob("system_serial", "$serial", "$uuid");
                dbUpdateJob ("date", "$date", "$uuid");
                dbUpdateJob ("time", "$time", "$uuid");
                dbUpdateJob ("department", "$department", "$uuid");

                # INSERT statement
                dbInsertLocation($time);
                dbUpdateLocation("tagnumber", "$tagNum", "$time");
                dbUpdateLocation("system_serial", "$serial", "$time");
                dbUpdateLocation("location", "$location", "$time");
                dbUpdateLocation("status", "$status", "$time");
                dbUpdateLocation("disk_removed", "$diskRemoved", "$time");
                dbUpdateLocation("note", "$note", "$time");
                echo "<div class='page-content'><h3>$tagNum is updated at $time. </h3></div>" . PHP_EOL;
                unset($_POST);
                header("Location: locations.php");
            }
            unset($_POST);
        } else {
            echo "<div class='page-content'><h2>Update Laptop Locations</h2></div>" . PHP_EOL;
            echo "<div class='location-form'>";
            echo "<tr>" . PHP_EOL;
            echo "<form method='post'>" . PHP_EOL;
            echo "<label for='tagnumber'>Tag Number</label>" . PHP_EOL;
            echo "<br>" . PHP_EOL;
            echo "<input type='text' id='tagnumber' name='tagnumber' autofocus>" . PHP_EOL;
            echo "<select name='status' id='status' required>" . PHP_EOL;
            echo "<option value='0'>Working</option>" . PHP_EOL;
            echo "<option value='1'>Broken</option>" . PHP_EOL;
            echo "</select>" . PHP_EOL;
            echo "<input type='submit' value='Submit'>" . PHP_EOL;
            echo "</form>" . PHP_EOL;
            echo "</div>";
        }

        ?>
        </div>

        <div class='page-content'><h2>View and Search Current Locations</h2></div>
        <div class='styled-form2'>
            <input type="text" id="myInput" onkeyup="myFunction()" placeholder="Search tagnumber...">
            <input type="text" id="myInputLocations" onkeyup="myFunctionLocations()" placeholder="Search locations...">
        </div>

        <div class='styled-table'>
            <table id="myTable">
                <thead>
                <tr>
                    <th>Tagnumber</th>
                    <th>System Serial</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>OS Installed</th>
                    <th>Note</th>
                    <th>Time</th>
                </tr>
                </thead>
                <tbody>
<?php
dbSelect("SELECT tagnumber, system_serial, location, IF ((status='0' OR status IS NULL), 'Working', 'Broken') AS 'status', IF (os_installed='1', 'Yes', 'No') AS 'os_installed', note, CONVERT(time, DATETIME) AS 'time' FROM locations WHERE tagnumber IN (SELECT tagnumber FROM locations WHERE tagnumber IN (SELECT tagnumber FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE tagnumber IS NOT NULL AND department IS NOT NULL GROUP BY tagnumber) AND department IN ('techComm', 'property', 'shrl'))) AND time IN (SELECT MAX(time) FROM locations WHERE tagnumber IS NOT NULL GROUP BY tagnumber) ORDER BY time DESC");
foreach ($arr as $key => $value) {
    echo "<tr>" . PHP_EOL;
    echo "<td>" . $value['tagnumber'] . "</td>" . PHP_EOL;
    echo "<td>" . $value['system_serial'] . "</td>" . PHP_EOL;
    echo "<td>" . $value['location'] . "</td>" . PHP_EOL;
    echo "<td>" . $value['status'] . "</td>" . PHP_EOL;
    echo "<td>" . $value['os_installed'] . "</td>" . PHP_EOL;
    echo "<td>" . $value['note'] . " </td>" . PHP_EOL;
    echo "<td>" . $value['time'] . " </td>" . PHP_EOL;
    dbSelectVal("SELECT department AS result WHERE tagnumber = '" . $value['tagnumber'] . "' AND department IS NOT NULL ORDER BY time DESC LIMIT 1");
    echo "<td>$result</td>" . PHP_EOL;
}
?>
</tr>

            </tbody>
        </table>
        </div>
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
    </script>
    <script>
        function myFunctionLocations() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("myInputLocations");
        filter = input.value.toUpperCase();
        table = document.getElementById("myTable");
        tr = table.getElementsByTagName("tr");

        for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td")[2];
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