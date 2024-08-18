<?php
require('/var/www/html/management/header.php');
require('/var/www/html/management/php/include.php');

$db = new db();
?>

<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>UIT Client Mgmt - Client Reports</title>
        <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    </head>
    <body>
        <div class='menubar'>
            <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
            <p><span style='float: right;'>Logged in as <b><?php echo htmlspecialchars($login_user); ?></b>.</span></p>
            <br>
            <p><span style='float: right;'>Not <b><?php echo htmlspecialchars($login_user); ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
        </div>

        <div class='pagetitle'><h1>Client Report</h1></div>
        <div class='pagetitle'><h2>The client report shows aggregated statistics and information about every client.</h2></div>

        <div class='styled-table'>
            <table>
                <thead>
                <tr>
                    <th>Tag Number</th>
                    <th>System Serial</th>
                    <th>System Model</th>
                    <th>Last Job</th>
                    <th>Battery Health</th>
                    <th>Disk Health</th>
                    <th>Disk Type</th>
                    <th>Bios Updated</th>
                    <th>Avg. Erase Time</th>
                    <th>Avg. Clone Time</th>
                    <th>Total Jobs</th>
                </tr>
                </thead>
                <tbody>
<?php
$db->select("SELECT tagnumber, system_serial, system_model, last_job_time, battery_health, disk_health, disk_type, IF (bios_updated = '1', 'Yes', 'No') AS 'bios_updated', erase_avgtime, clone_avgtime, all_jobs FROM clientstats WHERE tagnumber IS NOT NULL ORDER BY tagnumber ASC");
if (arrFilter($db->get()) === 0) {
    foreach ($db->get() as $key => $value) {
        echo "<tr>" . PHP_EOL;

        //tagnumber
        echo "<td>";
        if (strFilter($value["tagnumber"]) === 0) {
            echo htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
        }
        echo "</td>" . PHP_EOL;

        //system_serial
        echo "<td>";
        if (strFilter($value["system_serial"]) === 0) {
            echo htmlspecialchars($value["system_serial"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
        }
        echo "</td>" . PHP_EOL;

        //system_model
        echo "<td>";
        if (strFilter($value["system_model"]) === 0) {
            echo htmlspecialchars($value["system_model"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
        }
        echo "</td>" . PHP_EOL;

        //last_job_time
        echo "<td>";
        if (strFilter($value["last_job_time"]) === 0) {
            echo htmlspecialchars($value["last_job_time"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
        }
        echo "</td>" . PHP_EOL;

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

        //disk_type
        echo "<td>";
        if (strFilter($value["disk_type"]) === 0) {
            echo htmlspecialchars($value["disk_type"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
        }
        echo "</td>" . PHP_EOL;

        //bios_updated
        echo "<td>";
        if (strFilter($value["bios_updated"]) === 0) {
            echo htmlspecialchars($value["bios_updated"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
        }
        echo "</td>" . PHP_EOL;


        //erase_avgtime
        echo "<td>";
        if (strFilter($value["erase_avgtime"]) === 0) {
            echo htmlspecialchars($value["erase_avgtime"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . " minutes";
        }
        echo "</td>" . PHP_EOL;

        //clone_avgtime
        echo "<td>";
        if (strFilter($value["clone_avgtime"]) === 0) {
            echo htmlspecialchars($value["clone_avgtime"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . " minutes";
        }
        echo "</td>" . PHP_EOL;


        //all_jobs
        echo "<td>";
        if (strFilter($value["all_jobs"]) === 0) {
            echo htmlspecialchars($value["all_jobs"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
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