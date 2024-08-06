<?php
require('header.php');
include('/var/www/html/management/mysql/mysql-functions');
$dt = new DateTimeImmutable();
$date = $dt->format('Y-m-d');
$time = $dt->format('Y-m-d H:i:s.v');
?>

<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>UIT Laptop Managment - Lost Clients</title>
        <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
    </head>
    <body>
        <div class='menubar'>
            <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
            <p><span style='float: right;'>Logged in as <b><?php echo "$login_user"; ?></b>.</span></p>
            <br>
            <p><span style='float: right;'>Not <b><?php echo "$login_user"; ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
        </div>

        <div class='pagetitle'><h1>Lost Table</h1></div>
        <div class='pagetitle'><h2>This table shows clients that haven't had a location update in over 60 days.</h2></div>
        <div class='pagetitle'><h3>Last updated: <?php dbSelectVal("SELECT DATE_FORMAT(time, '%b %D %Y, %r') AS 'result' FROM locations ORDER BY time DESC LIMIT 1"); echo $result; ?></h3></div>

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
                    <th style='cursor: default;'>Location</th>
                    <th onclick="sortTable(3)">Department</th>
                    <th style='cursor: default;'>Status</th>
                    <th style='cursor: default;'>Note</th>
                    <th style='cursor: default;'>Time</th>
                </tr>
                </thead>
                <tbody>
<?php
dbSelect("SELECT tagnumber, system_serial, location, IF ((status='0' OR status IS NULL), 'Working', 'Broken') AS 'status', note, DATE_FORMAT(time, '%b %D %Y, %r') AS 'time_formatted' FROM locations WHERE tagnumber IN (SELECT tagnumber FROM locations WHERE tagnumber IN (SELECT tagnumber FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE tagnumber IS NOT NULL AND department IS NOT NULL GROUP BY tagnumber) AND department IN ('techComm', 'property', 'shrl'))) AND time IN (SELECT MAX(time) FROM locations WHERE tagnumber IS NOT NULL GROUP BY tagnumber) AND time >= NOW() + INTERVAL 3 MONTH ORDER BY time DESC");
foreach ($arr as $key => $value) {
    echo "<tr>" . PHP_EOL;
    dbSelectVal("SELECT present_bool AS 'result' FROM remote WHERE tagnumber = '" . $value["tagnumber"] . "'");
    if ($result == "1") {
        echo "<td><b><a href='tagnumber.php?tagnumber=" . $value["tagnumber"] . "' target='_blank'>" . $value["tagnumber"] . "</a></b> <span style='color: #00B388'>&#10004;</span></td>" . PHP_EOL;
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