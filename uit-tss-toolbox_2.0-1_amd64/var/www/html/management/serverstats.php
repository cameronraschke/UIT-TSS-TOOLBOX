<?php
require('/var/www/html/management/header.php');
require('/var/www/html/management/php/include.php');

$db = new db();
?>

<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>UIT Laptop Managment - Reports</title>
        <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
    </head>
    <body>
        <div class='menubar'>
            <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
            <p><span style='float: right;'>Logged in as <b><?php echo htmlspecialchars($login_user); ?></b>.</span></p>
            <br>
            <p><span style='float: right;'>Not <b><?php echo htmlspecialchars($login_user); ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
        </div>

        <div class='pagetitle'><h1>Server Stats Table (Date Report)</h1></div>
        <div class='pagetitle'><h2>The date report shows aggregated statistics about every client and job.</h2></div>

        <div class='styled-table'>
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Computer Count</th>
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
$db->select("SELECT * from serverstats ORDER BY date DESC");
if (arrFilter($db->get()) === 0) {
    foreach ($db->get() as $key => $value) {
        echo "<tr>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value["date"]) . "</td>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value["laptop_count"]) . "</td>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value["battery_health"]) . "%</td>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value["disk_health"]) . "%</td>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value["all_jobs"]) . "</td>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value["clone_avgtime"]) . " minutes</td>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value["nvme_erase_avgtime"]) . " minutes</td>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value["hdd_erase_avgtime"]) . " minutes</td>" . PHP_EOL;
        echo "<td>" . htmlspecialchars($value["last_image_update"]) . "</td>" . PHP_EOL;
        echo "</tr>" . PHP_EOL;
    }
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