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
        <title>UIT-TSS-Managment Site</title>
    </head>
    <body>
        <div class='menubar'>
            <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
            <p><span style='float: right;'>Logged in as <b><?php echo "$login_user"; ?></b>.</span></p>
            <br>
            <p><span style='float: right;'>Not <b><?php echo "$login_user"; ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
        </div>

        <div class='pagetitle'><h1>Locations Table</h1></div>
        <div class='pagetitle'><h2>See the location and status of all clients.</h2></div>
        <div class='pagetitle'><h3>Last updated: <?php dbSelectVal("SELECT CONVERT(time, DATETIME(0)) AS result FROM locations ORDER BY time DESC LIMIT 1"); echo $result; ?></h3></div>

        <div class='styled-form2'>
            <input type="text" id="myInput" onkeyup="myFunction()" placeholder="Search tagnumber..." autofocus>
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
dbSelect("SELECT tagnumber, system_serial, location, IF ((status='0' OR status IS NULL), 'Working', 'Broken') AS 'status', IF (os_installed='1', 'Yes', 'No') AS 'os_installed', note, CONVERT(time, DATETIME) AS 'time' FROM locations WHERE tagnumber IN (SELECT tagnumber FROM locations WHERE tagnumber IN (SELECT tagnumber FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE tagnumber IS NOT NULL AND department IS NOT NULL GROUP BY tagnumber) AND department = 'techComm')) AND time IN (SELECT MAX(time) FROM locations WHERE tagnumber IS NOT NULL GROUP BY tagnumber) ORDER BY time DESC");
foreach ($arr as $key => $value) {
    echo "<tr>" . PHP_EOL;
    echo "<td>" . $value['tagnumber'] . "</td>" . PHP_EOL;
    echo "<td>" . $value['system_serial'] . "</td>" . PHP_EOL;
    echo "<td>" . $value['location'] . "</td>" . PHP_EOL;
    echo "<td>" . $value['status'] . "</td>" . PHP_EOL;
    echo "<td>" . $value['os_installed'] . "</td>" . PHP_EOL;
    echo "<td>" . $value['note'] . " </td>" . PHP_EOL;
    echo "<td>" . $value['time'] . " </td>" . PHP_EOL;
    echo "</tr>" . PHP_EOL;

}
?>

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
    <div class="uit-footer">
            <img src="https://uh.edu/infotech/_images/_reorg-images/uh-2ndry-uit-artboard_horiz-reverse_black.svg">
    </div>
    </body>
</html>