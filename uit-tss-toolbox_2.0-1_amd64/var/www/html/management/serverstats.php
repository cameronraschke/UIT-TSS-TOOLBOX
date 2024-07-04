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

        <div class='pagetitle'><h1>Serverstats Table</h1></div>
        <div class='pagetitle'><h2>The serverstats table takes statistics over all jobs.</h2></div>
        <div class='pagetitle'><h3>Last updated: <?php dbSelectVal("SELECT date AS result FROM serverstats ORDER BY date DESC LIMIT 1"); echo $result; ?></h3></div>

        <div class='styled-table'>
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Laptop Count</th>
                    <th>Battery Health</th>
                    <th>Disk Health</th>
                    <th>Total Jobs</th>
                    <th>Average Clone Time</th>
                    <th>Average NVMe Erase Time</th>
                    <th>Average HDD Erase Time</th>
                    <th>Last Image Update</th>
                </tr>
                </thead>
                <tbody>
<?php
dbSelect("SELECT * from serverstats ORDER BY date DESC");
foreach ($arr as $key => $value) {
    $srvDate = $value['date'];
    $srvLaptopCount = $value['laptop_count'];
    $srvBatteryHealth = $value['battery_health'];
    $srvDiskHealth = $value['disk_health'];
    $srvTotalJobs = $value['all_jobs'];
    $srvAvgCloneTime = $value['clone_avgtime'];
    $srvAvgNvmeEraseTime = $value['nvme_erase_avgtime'];
    $srvAvgHddEraseTime = $value['hdd_erase_avgtime'];
    $srvLastImgUpdate = $value['last_image_update'];


    echo "<tr>" . PHP_EOL;
    echo "<td>$srvDate</td>" . PHP_EOL;
    echo "<td>$srvLaptopCount</td>" . PHP_EOL;
    echo "<td>$srvBatteryHealth</td>" . PHP_EOL;
    echo "<td>$srvDiskHealth</td>" . PHP_EOL;
    echo "<td>$srvTotalJobs</td>" . PHP_EOL;
    echo "<td>$srvAvgCloneTime</td>" . PHP_EOL;
    echo "<td>$srvAvgNvmeEraseTime</td>" . PHP_EOL;
    echo "<td>$srvAvgHddEraseTime</td>" . PHP_EOL;
    echo "<td>$srvLastImgUpdate</td>" . PHP_EOL;
    echo "</tr>" . PHP_EOL;

}
?>

            </tbody>
        </table>
        </div>
        <div class="uit-footer">
                <img src="https://uh.edu/infotech/_images/_reorg-images/uh-2ndry-uit-artboard_horiz-reverse_black.svg">
        </div>
    </body>
</html>