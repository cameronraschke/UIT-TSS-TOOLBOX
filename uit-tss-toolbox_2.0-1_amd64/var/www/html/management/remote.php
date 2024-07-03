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
            <p><span style='float: right;'>Not <b><?php echo "$login_user"; ?></b>? <a href='logout.php'>Click Here to Logout</a></p>
            <br>
            <br>
        </div>
        <div class='pagetitle'>
        <h3>The remote table monitors laptops currently plugged into the UIT-TSS-TOOLBOX server.</h3>
        </div>
        <h4>Last updated: <?php echo $time; ?></h4>

        <div class='styled-table'>
            <table style='border-collapse: collapse' border='1'>
                <tr>
                    <th>Total Laptops Present</th>
                    <th>Average Battery Charge</th>
                    <th>Average CPU Temp</th>
                    <th>Average Disk Temp</th>
                    <th>Average Actual Power Draw</th>
                    <th>Total Actual Power Draw</th>
                    <th>Total Power Draw From Wall</th>
                </tr>
<?php
dbSelect("CALL selectRemoteStats");
foreach ($arr as $key => $value) {
    $presentLaptops = $value['Present Laptops'];
    $avgBatteryCharge = $value['Avg. Battery Charge'];
    $avgCPUTemp = $value['Avg. CPU Temp'];
    $avgDiskTemp = $value['Avg. Disk Temp'];
    $avgRealPowerDraw = $value['Avg. Real Power Draw'];
    $totalRealPowerDraw = $value['Real Power Draw'];
    $totalWallPowerDraw = $value['Power Draw from Wall'];


    echo "<tr>" . PHP_EOL;
    echo "<td>$presentLaptops</td>" . PHP_EOL;
    echo "<td>$avgBatteryCharge</td>" . PHP_EOL;
    echo "<td>$avgCPUTemp</td>" . PHP_EOL;
    echo "<td>$avgDiskTemp</td>" . PHP_EOL;
    echo "<td>$avgRealPowerDraw</td>" . PHP_EOL;
    echo "<td>$totalRealPowerDraw</td>" . PHP_EOL;
    echo "<td>$totalWallPowerDraw</td>" . PHP_EOL;
    echo "</tr>" . PHP_EOL;
    echo "</table>";
    echo "</div>";
}
?>

        <br><br>
        <div class='styled-table'>
        <table style='border-collapse: collapse' border='1'>
        <tr>
        <th>Tagnumber</th>
        <th>Last Heard</th>
        <th>Pending Job</th>
        <th>Current Status</th>
        <th>Battery Charge</th>
        <th>Battery Status</th>
        <th>CPU Temp</th>
        <th>Disk Temp</th>
        <th>Actual Power Draw</th>
        </tr>

<?php

dbSelect("SELECT * FROM remote WHERE present_bool = '1' ORDER BY present DESC");
foreach ($arr as $key => $value) {
    $tagNum = $value["tagNum"];
    $lastHeard = $value["present"];
    $task = $value["task"];
    $status = $value["status"];
    $batteryCharge = $value["battery_charge"];
    $batteryStatus = $value["battery_status"];
    $cpuTemp = $value["cpu_temp"];
    $diskTemp = $value["disk_temp"];
    $powerDraw = $value["watts_now"];

    if (isset($_POST['task'])) {
        dbUpdateRemote("$tagNum", "task", $_POST['task']);
        unset($_POST['task']);
    }

    echo "<tr>";
    echo "<td>$tagNum</td>" . PHP_EOL;
    echo "<td>$lastHeard</td>" . PHP_EOL;
    echo "<td><form name='task' method='post'><select name='task' onchange='this.form.submit()'>";
    if (filter($task) == 1) {
        echo "<option value='NULL'>No Task</option>";
    } else {
        echo "<option value='$task'>$task</option>";
    }
    echo "<option value='update'>Update</option>";
    echo "</select></form></td>" . PHP_EOL;
    echo "<td>$status</td>" . PHP_EOL;
    echo "<td>$batteryCharge" . "%" . "</td>" . PHP_EOL;
    echo "<td>$batteryStatus</td>" . PHP_EOL;
    echo "<td>$cpuTemp" . "°C</td>" . PHP_EOL;
    echo "<td>$diskTemp" . "°C</td>" . PHP_EOL;
    echo "<td>$powerDraw" . " Watts</td>" . PHP_EOL;
    echo "</tr>";
}
echo "</table>";
echo "</div>";
?>
    </body>
</html>