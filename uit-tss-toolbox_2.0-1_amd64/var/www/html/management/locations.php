<?php
require('header.php');
include('/var/www/html/management/mysql/mysql-functions');
if ($_POST['refresh-stats']) {
    include('/var/www/html/management/php/uit-sql-refresh-location');
}
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

        <div class='pagetitle'><h1>Locations Table</h1></div>
        <div class='pagetitle'><h2>The locations table displays the location and status of every client.</h2></div>
        <div class='pagetitle'><h3>Last updated: <?php dbSelectVal("SELECT DATE_FORMAT(time, '%b %D %Y, %r') AS 'result' FROM locations ORDER BY time DESC LIMIT 1"); echo $result; ?></h3></div>

        <?php
        if (!empty($_POST['tagnumber'])) {
            echo "<div class='location-form'>" . PHP_EOL;
            dbSelect("SELECT system_serial, location, DATE_FORMAT(time, '%b %D %Y, %r') AS 'time_formatted' FROM locations WHERE tagnumber = '" . $_POST['tagnumber'] . "' AND (NOT note = 'Unattended' OR NOTE IS NULL) ORDER BY time DESC LIMIT 1");
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
                    dbSelectVal("SELECT department AS result FROM jobstats WHERE tagnumber = '" . $_POST['tagnumber'] . "' AND department IS NOT NULL ORDER BY time DESC LIMIT 1");
                    echo "<select name='department' id='department'>" . PHP_EOL;
                    echo "<option value='$result'>$result</option>" . PHP_EOL;
                    echo "<option value='techComm'>Tech Commons (TSS)</option>" . PHP_EOL;
                    echo "<option value='property'>Property Management</option>" . PHP_EOL;
                    echo "<option value='shrl'>SHRL (Kirven)</option>" . PHP_EOL;
                    echo "</select>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<label for='location'>Location (" . $value['time_formatted'] . ")</label>" . PHP_EOL;
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
                dbSelectVal("SELECT department AS result FROM jobstats WHERE tagnumber = '" . $_POST['tagnumber'] . "' AND department IS NOT NULL ORDER BY time DESC LIMIT 1");
                echo "<select name='department' id='department'>" . PHP_EOL;
                echo "<option value='$result'>$result</option>" . PHP_EOL;
                echo "<option value='techComm'>Tech Commons (TSS)</option>" . PHP_EOL;
                echo "<option value='property'>Property Management</option>" . PHP_EOL;
                echo "<option value='shrl'>SHRL (Kirven)</option>" . PHP_EOL;
                echo "</select>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<label for='location'>Location (" . $value['time_formatted'] . ")</label>" . PHP_EOL;
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
            echo "<input type='text' id='tagnumber' name='tagnumber' autofocus>" . PHP_EOL;
            echo "<label for='status'>Status</label>" . PHP_EOL;
            echo "<select name='status' id='status' required>" . PHP_EOL;
            echo "<option value='0'>Working</option>" . PHP_EOL;
            echo "<option value='1'>Broken</option>" . PHP_EOL;
            echo "</select>" . PHP_EOL;
            echo "<input type='submit' value='Submit'>" . PHP_EOL;
            echo "</form>" . PHP_EOL;
            echo "</div>";
        }
        ?>

        <div class='page-content'><h2>View and Search Current Locations</h2></div>
        <div class='styled-form'>
            <form method='post'>
                <div>
                    <button type="submit">Refresh Location Data</button>
                </div>
                <input type="hidden" id="refresh-stats" name="refresh-stats" value="refresh-stats" />
            </form>
            <div style='margin: 1% 0% 0% 0%'><a href='locations.php'><button>Reset Filters</button></a></div>
        </div>
        <div class='styled-form2'>
            <input type="text" id="myInput" onkeyup="myFunction()" placeholder="Search tagnumber...">
            <input type="text" id="myInputLocations" onkeyup="myFunctionLocations()" placeholder="Search locations...">
        </div>

        <div class='styled-table'>
            <table id="myTable">
                <thead>
                <tr>
                    <th onclick="sortTable(0)">Tagnumber</th>
                    <th style='cursor: default;'>System Serial</th>
                    <th onclick="sortTable(2)">Location</th>
                    <th onclick="sortTable(3)">Department</th>
                    <th onclick="sortTable(4)">Status</th>
                    <th onclick="sortTable(5)">OS Installed</th>
                    <th onclick="sortTable(6)">BIOS Updated</th>
                    <th onclick="sortTable(7)">Note</th>
                    <th style='cursor: default;'>Time</th>
                </tr>
                </thead>
                <tbody>
<?php
if ($_GET["location"]) {
    $sql = "SELECT tagnumber, system_serial, location, IF ((status='0' OR status IS NULL), 'Working', 'Broken') AS 'status', IF (os_installed='1', 'Yes', 'No') AS 'os_installed', note, DATE_FORMAT(time, '%b %D %Y, %r') AS 'time_formatted' FROM locations WHERE tagnumber IN (SELECT tagnumber FROM locations WHERE tagnumber IN (SELECT tagnumber FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE tagnumber IS NOT NULL AND department IS NOT NULL GROUP BY tagnumber) AND department IN ('techComm', 'property', 'shrl'))) AND time IN (SELECT MAX(time) FROM locations WHERE tagnumber IS NOT NULL GROUP BY tagnumber) AND location = :location ORDER BY time DESC";
    $stmt = $pdo->prepare($sql);
    $sqlLocation = htmlspecialchars_decode($_GET['location']);
    $stmt->bindParam(':location', $sqlLocation, PDO::PARAM_STR);
    $stmt->execute();
    $arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    dbSelect("SELECT tagnumber, system_serial, location, IF ((status='0' OR status IS NULL), 'Working', 'Broken') AS 'status', IF (os_installed='1', 'Yes', 'No') AS 'os_installed', note, DATE_FORMAT(time, '%b %D %Y, %r') AS 'time_formatted' FROM locations WHERE tagnumber IN (SELECT tagnumber FROM locations WHERE tagnumber IN (SELECT tagnumber FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE tagnumber IS NOT NULL AND department IS NOT NULL GROUP BY tagnumber) AND department IN ('techComm', 'property', 'shrl'))) AND time IN (SELECT MAX(time) FROM locations WHERE tagnumber IS NOT NULL GROUP BY tagnumber) ORDER BY time DESC");
}
foreach ($arr as $key => $value) {
    echo "<tr>" . PHP_EOL;
    dbSelectVal("SELECT present_bool FROM remote WHERE tagnumber = '" . $value["tagnumber"] . "'");
    if (filter($result) == "0") {
        echo "<td><b><a href='tagnumber.php?tagnumber=" . $value["tagnumber"] . "' target='_blank'>" . $value["tagnumber"] . "</a></b> <span style='color: #008282'> &#10004;</span></td>" . PHP_EOL;
    } else {
        echo "<td><b><a href='tagnumber.php?tagnumber=" . $value["tagnumber"] . "' target='_blank'>" . $value["tagnumber"] . "</a></b></td>" . PHP_EOL;
    }
    echo "<td>" . $value['system_serial'] . "</td>" . PHP_EOL;
    if (preg_match("/^[a-zA-Z]$/", $value["location"])) { 
        echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value["location"]) . "' target='_blank'>" . strtoupper($value["location"]) . "</a></b></td>" . PHP_EOL;
    } else {
        echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value["location"]) . "' target='_blank'>" . $value["location"] . "</a></b></td>" . PHP_EOL;
    }
    dbSelectVal("SELECT (CASE WHEN department='techComm' THEN 'Tech Commons (TSS)' WHEN department='property' THEN 'Property' WHEN department='shrl' THEN 'SHRL' ELSE '' END) AS result FROM jobstats WHERE tagnumber = '" . $value['tagnumber'] . "' AND department IS NOT NULL ORDER BY time DESC LIMIT 1");
    echo "<td>" . $result . "</td>" . PHP_EOL;
    echo "<td>" . $value['status'] . "</td>" . PHP_EOL;
    echo "<td>" . $value['os_installed'] . "</td>" . PHP_EOL;
    dbSelectVal("SELECT IF (bios_updated = '1', 'Yes', 'No')  AS 'result' FROM clientstats WHERE tagnumber = '" . $value['tagnumber'] . "'");
    echo "<td>" . $result . "</td>" . PHP_EOL;
    echo "<td>" . $value['note'] . " </td>" . PHP_EOL;
    echo "<td>" . $value['time_formatted'] . " </td>" . PHP_EOL;

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

        if ( window.history.replaceState ) {
            window.history.replaceState( null, null, window.location.href );
        }
    </script>
    <div class="uit-footer">
        <img src="https://uh.edu/infotech/_images/_reorg-images/uh-2ndry-uit-artboard_horiz-reverse_black.svg">
    </div>
    </body>
</html>