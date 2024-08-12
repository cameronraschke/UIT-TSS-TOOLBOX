<?php
require('/var/www/html/management/header.php');
require('/var/www/html/management/php/include.php');

if (isset($_POST['refresh-stats'])) {
    include('/var/www/html/management/php/uit-sql-refresh-location');
}

$db = new db();
?>

<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>UIT Laptop Managment - Locations</title>
        <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
    </head>
    <body>
        <div class='menubar'>
            <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
            <p><span style='float: right;'>Logged in as <b><?php echo htmlspecialchars($login_user); ?></b>.</span></p>
            <br>
            <p><span style='float: right;'>Not <b><?php echo htmlspecialchars($login_user); ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
        </div>

        <div class='pagetitle'><h1>Locations Table (<i><a href='lost.php' target="_blank">View Lost Clients</a></i>)</h1></div>
        <div class='pagetitle'><h2>The locations table displays the location and status of every client.</h2></div>
        <div class='pagetitle'><h3>Last updated: <?php $db->select("SELECT DATE_FORMAT(time, '%b %D %Y, %r') AS 'time_formatted' FROM locations ORDER BY time DESC LIMIT 1"); if (arrFilter($db->get()) === 0) { foreach ($db->get() as $key => $sqlUpdatedTime) { echo $sqlUpdatedTime["time_formatted"]; } } ?></h3></div>

        <?php
        if (isset($_POST['tagnumber'])) {
            echo "<div class='location-form'>" . PHP_EOL;
            $db->select("SELECT system_serial, location, DATE_FORMAT(time, '%b %D %Y, %r') AS 'time_formatted' FROM locations WHERE tagnumber = '" . $_POST['tagnumber'] . "' ORDER BY time DESC LIMIT 1");
            if ($db->get() === "NULL") {
                $arr = array( array( "system_serial" => "NULL", "location" => "NULL", "time_formatted" => "NULL") );
            } else {
                $arr = $db->get();
            }

            foreach ($arr as $key => $value) {
                echo "<form method='post'>" . PHP_EOL;
                echo "<label for='tagnumber'>Tag Number</label>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<input type='text' style='background-color:#888B8D;' id='tagnumber' name='tagnumber' value='" . htmlspecialchars($_POST['tagnumber']) . "' readonly required>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<label for='serial'>Serial Number</label>";
                echo "<br>" . PHP_EOL;

                // Change appearance of serial number field based on sql data
                if (arrFilter($db->get()) === 0) {
                    echo "<input type='text' style='background-color:#888B8D;' id='serial' name='serial' value='" . $value['system_serial'] . "' readonly required>" . PHP_EOL;
                } else {
                    echo "<input type='text' id='serial' name='serial' autocomplete='off' autofocus required>" . PHP_EOL;
                }
                echo "<br>" . PHP_EOL;

                // Get the department
                if (arrFilter($db->get()) === 0) {
                    // Get a human readable department
                    $db->select("SELECT department, (CASE WHEN department='techComm' THEN 'Tech Commons (TSS)' WHEN department='property' THEN 'Property' WHEN department='shrl' THEN 'SHRL' ELSE '' END) AS 'department_formatted' FROM jobstats WHERE tagnumber = '" . $_POST['tagnumber'] . "' AND department IS NOT NULL ORDER BY time DESC LIMIT 1");
                    if (arrFilter($db->get()) === 0) {
                        foreach ($db->get() as $key =>$value1) {
                            $department = $value1["department"];
                            $departmentFormatted = $value1["department_formatted"];
                        }
                    }
                    unset($value1);
                } else {
                    $department = "";
                    $departmentFormatted = "NULL";
                }
                echo "<label for='department'>Department</label>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<select name='department' id='department'>" . PHP_EOL;
                if ($department === "techComm") {
                    echo "<option value='$department'>$departmentFormatted</option>" . PHP_EOL;
                    echo "<option value='property'>Property Management</option>" . PHP_EOL;
                    echo "<option value='shrl'>SHRL (Kirven)</option>" . PHP_EOL;
                } elseif ($department === "property") {
                    echo "<option value='$department'>$departmentFormatted</option>" . PHP_EOL;
                    echo "<option value='techComm'>Tech Commons (TSS)</option>" . PHP_EOL;
                    echo "<option value='shrl'>SHRL (Kirven)</option>" . PHP_EOL;
                } elseif ($department === "shrl") {
                    echo "<option value='$department'>$departmentFormatted</option>" . PHP_EOL;
                    echo "<option value='property'>Property Management</option>" . PHP_EOL;
                    echo "<option value='techComm'>Tech Commons (TSS)</option>" . PHP_EOL;
                } else {
                    echo "<option>NULL (new entry)</option>" . PHP_EOL;
                    echo "<option value='techComm'>Tech Commons (TSS)</option>" . PHP_EOL;
                    echo "<option value='property'>Property Management</option>" . PHP_EOL;
                    echo "<option value='shrl'>SHRL (Kirven)</option>" . PHP_EOL;
                }
                echo "</select>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                if (arrFilter($db->get()) === 0) {
                    echo "<label for='location'>Location (Last Updated: " . $value['time_formatted'] . ")</label>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<input type='text' id='location' name='location' value='" . htmlspecialchars($value['location']) . "' autofocus required style='width: 20%; height: 4%;'>" . PHP_EOL;
                } else {
                    echo "<label for='location'>Location</label>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<input type='text' id='location' name='location' required style='width: 20%; height: 4%;'>" . PHP_EOL;
                }
                echo "<br>" . PHP_EOL;
                echo "<label for='note'>Note</label>" . PHP_EOL;
                echo "<br>" . PHP_EOL;

                if (arrFilter($db->get()) === 0) {
                    // Get the most recent note that's not null
                    
                    $db->select("SELECT note FROM locations WHERE tagnumber = '" . $_POST["tagnumber"] . "' AND note IS NOT NULL ORDER BY time DESC LIMIT 1");
                    if (arrFilter($db->get()) === 0) {
                        foreach ($db->get() as $key => $value1) {
                            if ($_POST["status"] == "1") {
                                echo "<textarea id='note' name='note'>" . htmlspecialchars($value1["note"]) .  "</textarea>" . PHP_EOL;
                            } else {
                                echo "<textarea id='note' name='note' placeholder='" . htmlspecialchars($value1["note"]) .  "'></textarea>" . PHP_EOL;
                            }
                        }
                    }
                    unset($value1);
                } else {
                    echo "<textarea id='note' name='note'></textarea>" . PHP_EOL;
                }

                echo "<br>" . PHP_EOL;
                echo "<input type='hidden' name='status' value='" . htmlspecialchars($_POST["status"]) . "'>";
                echo "<label for='disk_removed'>Disk removed?</label>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<select name='disk_removed' id='disk_removed'>" . PHP_EOL;
                if (arrFilter($db->get()) === 0) {
                    $db->select("SELECT disk_removed FROM locations WHERE tagnumber = '" . $_POST['tagnumber'] . "' ORDER BY time DESC LIMIT 1");
                    if (arrFilter($db->get()) === 0) {
                        foreach ($db->get() as $key => $value1) {
                            if ($value1["disk_removed"] == "1") {
                                echo "<option value='1'>Yes</option>" . PHP_EOL;
                                echo "<option value='0'>No</option>" . PHP_EOL;
                            } else {
                                echo "<option value='0'>No</option>" . PHP_EOL;
                                echo "<option value='1'>Yes</option>" . PHP_EOL;
                            }
                        }
                    }
                    unset($value1);
                } else {
                    echo "<option value='0'>No</option>" . PHP_EOL;
                    echo "<option value='1'>Yes</option>" . PHP_EOL;
                }

                echo "</select>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                if ($_POST["status"] == "1") {
                    echo "<input class='page-content' type='submit' value='Update Location Data (Broken)'>" . PHP_EOL;
                } else {
                    echo "<input class='page-content' type='submit' value='Update Location Data'>" . PHP_EOL;
                }
                echo "</form>" . PHP_EOL;
                echo "<div class='page-content'><a href='locations.php'>Update a different laptop.</a></div>" . PHP_EOL;
                echo "</div>";
            }
            unset($arr);

            if (isset($_POST['serial'])) {
                $uuid = uniqid("location-", true);
                $tagNum = $_POST['tagnumber'];
                $serial = $_POST['serial'];
                $department = $_POST['department'];
                $location = $_POST['location'];
                $status = $_POST["status"];
                $note = $_POST['note'];
                $diskRemoved = $_POST['disk_removed'];

                #Not the same insert statment as client parse code, ether address is DEFAULT here.
                $db->insertJob($uuid);
                $db->updateJob("tagnumber", $tagNum, $uuid);
                $db->updateJob("system_serial", $serial, $uuid);
                $db->updateJob ("date", $date, $uuid);
                $db->updateJob ("time", $time, $uuid);
                $db->updateJob ("department", $department, $uuid);

                # INSERT statement
                $db->insertLocation($time);
                $db->updateLocation("tagnumber", $tagNum, $time);
                $db->updateLocation("system_serial", $serial, $time);
                $db->updateLocation("location", $location, $time);
                $db->updateLocation("status", $status, $time);
                $db->updateLocation("disk_removed", $diskRemoved, $time);
                $db->updateLocation("note", $note, $time);
                unset($_POST);
                header("Location: locations.php");
            }
            unset($_POST);
        } else {
            echo "<div class='page-content' style='margin: 5% 0% 0% 0%;'><h2>Update Laptop Locations</h2></div>" . PHP_EOL;
            echo "<div class='location-form'>";
            echo "<tr>" . PHP_EOL;
            echo "<form method='post'>" . PHP_EOL;
            echo "<label for='tagnumber'>Enter tag number and status: </label>" . PHP_EOL;
            echo "<input type='text' id='tagnumber' name='tagnumber' placeholder='Tag Number' autofocus required>" . PHP_EOL;
            echo "<select name='status' id='status' required>" . PHP_EOL;
            echo "<option value='0'>Working</option>" . PHP_EOL;
            echo "<option value='1'>Broken</option>" . PHP_EOL;
            echo "</select>" . PHP_EOL;
            echo "<input type='submit' value='Submit'>" . PHP_EOL;
            echo "</form>" . PHP_EOL;
            echo "</div>";
        }
        ?>

<?php
if (isset($_GET["location"])) {
    $sql = "SELECT tagnumber FROM locations WHERE tagnumber IN (SELECT tagnumber FROM locations WHERE tagnumber IN (SELECT tagnumber FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE tagnumber IS NOT NULL AND department IS NOT NULL GROUP BY tagnumber) AND department IN ('techComm', 'property', 'shrl'))) AND time IN (SELECT MAX(time) FROM locations WHERE tagnumber IS NOT NULL GROUP BY tagnumber) AND location = :location ORDER BY time DESC";
    $conn = new MySQLConn();
    $pdo = $conn->dbObj();
    $arr = array();
    $stmt = $pdo->prepare($sql);
    $sqlLocation = htmlspecialchars_decode($_GET['location']);
    $stmt->bindParam(':location', $sqlLocation, PDO::PARAM_STR);
    $stmt->execute();
    $arr = $stmt->fetchAll();
    $rowCount = $stmt->rowCount();
    $onlineRowCount = 0;
    if (arrFilter($arr) === 0) {
        foreach ($arr as $key => $value1) {
            $db->select("SELECT tagnumber FROM remote WHERE present_bool = 1 AND tagnumber ='" . $value1["tagnumber"] . "'");
            if (arrFilter($db->get()) === 0) {
                $onlineRowCount = $onlineRowCount + 1;
            }
        }
    } else { $onlineRowCount = 0; }
} else {
    $onlineRowCount = 0;
    $db->select("SELECT tagnumber FROM locations WHERE tagnumber IN (SELECT tagnumber FROM locations WHERE tagnumber IN (SELECT tagnumber FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE tagnumber IS NOT NULL AND department IS NOT NULL GROUP BY tagnumber) AND department IN ('techComm', 'property', 'shrl'))) AND time IN (SELECT MAX(time) FROM locations WHERE tagnumber IS NOT NULL GROUP BY tagnumber) ORDER BY time DESC");
    $rowCount = count($db->get());
    $onlineRowCount = 0;
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->select("SELECT tagnumber FROM remote WHERE present_bool = 1 AND tagnumber ='" . $value1["tagnumber"] . "'");
            if (arrFilter($db->get()) === 0) {
                $onlineRowCount = $onlineRowCount + 1;
            }
        }
    } else { $onlineRowCount = 0; }
}
unset($arr);
unset($value1);
?>
        <div class='page-content'><h2>View and Search Current Locations</h2></div>
        <div class='page-content'><h3>A checkmark (<span style='color: #00B388'>&#10004;</span>) means a client is currently on and attached to the server.</h3></div>
        <?php
        if (isset($_GET["location"])) {
            echo "<div class='page-content'><h3><u>" . $onlineRowCount . "/" . $rowCount . "</u> clients are online from location '" . htmlspecialchars($_GET["location"]) . "'.</h3></div>";
        }
        ?>
        <div class='styled-form'>
            <form method='post'>
                <div>
                    <button type="submit">Refresh OS/BIOS Data</button>
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
                    <th onclick="sortTable(0)">Tag Number</th>
                    <th style='cursor: default;'>System Serial</th>
                    <th onclick="sortTable(2)">Location</th>
                    <th onclick="sortTable(3)">Department</th>
                    <th onclick="sortTable(4)">Status</th>
                    <th onclick="sortTable(5)">OS Installed</th>
                    <th onclick="sortTable(6)">BIOS Updated</th>
                    <th style='cursor: default;'>Note</th>
                    <th style='cursor: default;'>Time</th>
                </tr>
                </thead>
                <tbody>
<?php
if (isset($_GET["location"])) {
    $sql = "SELECT tagnumber, system_serial, location, IF ((status='0' OR status IS NULL), 'Working', 'Broken') AS 'status', IF (os_installed='1', 'Yes', 'No') AS 'os_installed', note, DATE_FORMAT(time, '%b %D %Y, %r') AS 'time_formatted' FROM locations WHERE tagnumber IN (SELECT tagnumber FROM locations WHERE tagnumber IN (SELECT tagnumber FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE tagnumber IS NOT NULL AND department IS NOT NULL GROUP BY tagnumber) AND department IN ('techComm', 'property', 'shrl'))) AND time IN (SELECT MAX(time) FROM locations WHERE tagnumber IS NOT NULL GROUP BY tagnumber) AND location = :location ORDER BY time DESC";
    $conn = new MySQLConn();
    $pdo = $conn->dbObj();
    $arr = array();
    $stmt = $pdo->prepare($sql);
    $sqlLocation = htmlspecialchars_decode($_GET['location']);
    $stmt->bindParam(':location', $sqlLocation, PDO::PARAM_STR);
    $stmt->execute();
    $arr = $stmt->fetchAll();
} else {
    $db->select("SELECT tagnumber, system_serial, location, IF ((status='0' OR status IS NULL), 'Working', 'Broken') AS 'status', IF (os_installed='1', 'Yes', 'No') AS 'os_installed', note, DATE_FORMAT(time, '%b %D %Y, %r') AS 'time_formatted' FROM locations WHERE tagnumber IN (SELECT tagnumber FROM locations WHERE tagnumber IN (SELECT tagnumber FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE tagnumber IS NOT NULL AND department IS NOT NULL GROUP BY tagnumber) AND department IN ('techComm', 'property', 'shrl'))) AND time IN (SELECT MAX(time) FROM locations WHERE tagnumber IS NOT NULL GROUP BY tagnumber) ORDER BY time DESC");
    $arr = $db->get();
}
foreach ($arr as $key => $value) {
    echo "<tr>" . PHP_EOL;
    $db->select("SELECT present_bool FROM remote WHERE tagnumber = '" . $value["tagnumber"] . "'");
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            if ($value1["present_bool"] == "1") {
                echo "<td><b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b> <span style='color: #00B388'>&#10004;</span></td>" . PHP_EOL;
            } else {
                echo "<td><b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b></td>" . PHP_EOL;
            }
        }
    } else {
        echo "<td><b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b></td>" . PHP_EOL;
    }
    unset($value1);

    echo "<td>" . $value['system_serial'] . "</td>" . PHP_EOL;
    if (preg_match("/^[a-zA-Z]$/", $value["location"])) { 
        echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . strtoupper($value["location"]) . "</a></b></td>" . PHP_EOL;
    } else {
        echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . $value["location"] . "</a></b></td>" . PHP_EOL;
    }

    $db->select("SELECT (CASE WHEN department='techComm' THEN 'Tech Commons (TSS)' WHEN department='property' THEN 'Property' WHEN department='shrl' THEN 'SHRL' ELSE '' END) AS 'department_formatted' FROM jobstats WHERE tagnumber = '" . $value['tagnumber'] . "' AND department IS NOT NULL ORDER BY time DESC LIMIT 1");
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            echo "<td>" . $value1["department_formatted"] . "</td>" . PHP_EOL;
        }
    } else {
        echo "<td>NULL</td>" . PHP_EOL;
    }
    unset($value1);

    echo "<td>" . $value['status'] . "</td>" . PHP_EOL;
    echo "<td>" . $value['os_installed'] . "</td>" . PHP_EOL;

    $db->select("SELECT IF (bios_updated = '1', 'Yes', 'No') AS 'bios_updated' FROM clientstats WHERE tagnumber = '" . $value['tagnumber'] . "'");
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            echo "<td>" . $value1["bios_updated"] . "</td>" . PHP_EOL;
        }
    } else {
        echo "<td>NULL</td>" . PHP_EOL;
    }

    echo "<td>" . $value['note'] . " </td>" . PHP_EOL;
    echo "<td>" . $value['time_formatted'] . " </td>" . PHP_EOL;

}
unset($arr);
unset($value1);
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