<?php
require('/var/www/html/management/header.php');
require('/var/www/html/management/php/include.php');

$db = new db();
?>

<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>Daily Reports - UIT Client Mgmt</title>
        <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    </head>
    <body>
        <div class='menubar'>
            <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
            <p><span style='float: right;'>Logged in as <b><?php echo htmlspecialchars($login_user); ?></b>.</span></p>
            <br>
            <p><span style='float: right;'>Not <b><?php echo htmlspecialchars($login_user); ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
        </div>

        <div class='pagetitle'><h1>Daily Reports</h1></div>
        <div class='pagetitle'><h2>The daily reports show aggregated statistics for each day the server has been online, going back to Jan 9th, 2023.</h2></div>

        <div class='styled-table'>
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Computer Count</th>
                    <th>Total Clients w/ OS Installed</th>
                    <th>Battery Health</th>
                    <th>Disk Health</th>
                    <th>Total Jobs</th>
                    <th>Average Clone Time</th>
                    <th>Average Erase Time</th>
                    <th>Last Image Update</th>
                </tr>
                </thead>
                <tbody>
<?php
$db->select("SELECT date, client_count, total_os_installed, battery_health, disk_health, total_job_count, clone_job_count, erase_job_count, avg_clone_time, avg_erase_time, last_image_update FROM serverstats ORDER BY date DESC");
if (arrFilter($db->get()) === 0) {
    foreach ($db->get() as $key => $value) {
        echo "<tr>" . PHP_EOL;

        //date
        echo "<td>";
        if (strFilter($value["date"]) === 0) {
            echo htmlspecialchars($value["date"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
        }
        echo "</td>" . PHP_EOL;

        //client_count
        echo "<td>";
        if (strFilter($value["client_count"]) === 0) {
            echo htmlspecialchars($value["client_count"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
        }
        echo "</td>" . PHP_EOL;

        echo "<td>" . htmlspecialchars($value["total_os_installed"] ) . "</td>" . PHP_EOL;

        //battery_health
        echo "<td>";
        if (strFilter($value["battery_health"]) === 0) {
            echo htmlspecialchars($value["battery_health"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "%";
        }
        echo "</td>" . PHP_EOL;

        //disk_health
        echo "<td>";
        if (strFilter($value["disk_health"]) === 0) {
            echo htmlspecialchars($value["disk_health"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "%";
        }
        echo "</td>" . PHP_EOL;

        //total_job_count
        echo "<td>";
        if (strFilter($value["total_job_count"]) === 0) {
            echo htmlspecialchars($value["total_job_count"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
        }
        echo "</td>" . PHP_EOL;

        //avg_clone_time
        echo "<td>";
        if (strFilter($value["avg_clone_time"]) === 0) {
            echo htmlspecialchars($value["avg_clone_time"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . " minutes";
        }
        echo "</td>" . PHP_EOL;

        //avg_erase_time
        echo "<td>";
        if (strFilter($value["avg_erase_time"]) === 0) {
            echo htmlspecialchars($value["avg_erase_time"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . " minutes";
        }
        echo "</td>" . PHP_EOL;

        //last_image_update
        echo "<td>";
        if (strFilter($value["last_image_update"]) === 0) {
            echo htmlspecialchars($value["last_image_update"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
        }
        echo "</td>" . PHP_EOL;

        echo "</tr>" . PHP_EOL;
    }
}
?>

            </tbody>
        </table>
        </div>
        <div class="uit-footer">
            <img src="/images/uh-footer.svg">
        </div>
    </body>
</html>