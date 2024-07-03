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
                    <th>Average Real Power Draw</th>
                    <th>Total Real Power Draw</th>
                    <th>Total Power Draw From Wall</th>
                </tr>
<?php
while true {
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

    echo "<br><br>";
    echo "<div class='styled-table'>";
    echo "<table style='border-collapse: collapse' border='1'>";
    echo "<tr>";
    echo "<th>Tagnumber</th>";
    echo "<th>Last Job Data</th>";
    echo "</tr>";

    dbSelect("SELECT * FROM remote ORDER BY date DESC");
    foreach ($arr as $key => $value) {
        $tagnumber = $value["tagnumber"];
        $lastJob = $value["date"];

        echo "<tr>";
        echo "<td>$tagnumber</td>" . PHP_EOL;
        echo "<td>$lastJob</td>" . PHP_EOL;
        echo "</tr>";

    }

    echo "</table>";
sleep 5
}
?>

    </body>
</html>

?>
