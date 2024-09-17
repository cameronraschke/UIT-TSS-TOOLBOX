<?php
require('/var/www/html/management/header.php');
include('/var/www/html/management/php/include.php');

$db = new db();
?>
<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <link rel="stylesheet" href="/jquery/jquery-ui/jquery-ui-1.14.0/jquery-ui.min.css">
        <script src="/jquery/jquery-3.7.1.min.js"></script>
        <script src="/jquery/jquery-ui/jquery-ui-1.14.0/jquery-ui.min.js"></script>
        <title>UIT Client Managment</title>
        <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    </head>
    <body>
        <div class='menubar'>
            <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
            <p><span style='float: right;'>Logged in as <b><?php echo htmlspecialchars($login_user); ?></b>.</span></p>
            <br>
            <p><span style='float: right;'>Not <b><?php echo htmlspecialchars($login_user); ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
        </div>

        <div class='pagetitle' style="text-align:center;"><h1 style="margin:auto;">TechComm Laptop Management Site</h1></div>
        <div class='pagetitle'><h2>Welcome, <?php echo $login_user; ?>.</h2></div>

        <div>
            <div style='width: 50%; float: left;'>
                <div><h3 class='page-content'><a href="/remote.php">Remote Management and Live Overview</a></h3></div>
                <div><h3 class='page-content'><a href="/locations.php">Update and View Client Locations</a></h3></div>
                <div><h3 class='page-content'><a href="/serverstats.php">Daily Reports</a></h3></div>
                <div><h3 class='page-content'><a href="/clientstats.php">Client Report</a></h3></div>
                <div><h3 class='page-content'><a href="/reports.php">Generate and Download Reports (WIP)</a></h3></div>
            </div>
            <div style='width: 50%; float: right;'>
                <div id="jobTimes" style='height: auto; width: 99%;'></div>
                <div id="numberImaged" style='height: auto; width: 99%;'></div>
            </div>
        </div>

        <script src="/google-charts/loader.js"></script>

        <script>
            google.charts.load('current',{packages:['corechart']});
            google.charts.setOnLoadCallback(jobTimes);

            function jobTimes() {
                var data = google.visualization.arrayToDataTable([ ['Date', 'Clone Time', 'Erase Time'],
                <?php
                $db->select("SELECT DATE_FORMAT(date, '%Y-%m') AS 'dateByMonth', ROUND(AVG(clone_avgtime), 0) AS 'clone_avgtime', ROUND((AVG(nvme_erase_avgtime) + AVG(sata_erase_avgtime)) / 2, 0) AS 'erase_avgtime' FROM serverstats GROUP BY dateByMonth ORDER BY dateByMonth ASC");
                if (arrFilter($db->get()) === 0)
                    foreach ($db->get() as $key => $value) {
                        echo "['" . $value["dateByMonth"] . "', " . $value["clone_avgtime"] . ", " . $value["erase_avgtime"] . "], ";
                    }
                ?>
                ]);

                var options = {title: 'Avg. Clone and Erase Time', hAxis: {title: 'Date'}, vAxis: {title: 'Time (minutes)'}, legend: 'none'  };
                var chart = new google.visualization.LineChart(document.getElementById('jobTimes'));
                chart.draw(data, options);
            }
        </script>
        <script>
            google.charts.load('current',{packages:['corechart']});
            google.charts.setOnLoadCallback(numberImaged);

            function numberImaged() {
                var data = google.visualization.arrayToDataTable([ ['OS Installed', 'Total Client Count'],
                <?php
                $db->select("SELECT (SELECT COUNT(tagnumber) FROM remote WHERE NOT os_installed = 1 OR os_installed IS NULL) AS 'client_count', (SELECT COUNT(tagnumber) FROM remote WHERE os_installed = 1 AND present_bool = 1) AS 'os_installed'");
                if (arrFilter($db->get()) === 0) {
                    foreach ($db->get() as $key => $value) {
                        echo "['No OS Installed'," . $value["client_count"] . "], ";
                        echo "['OS Installed'," . $value["os_installed"] . "], ";
                    }
                }
                ?>
                ]);

                var options = {title: 'Number of OS\'s Installed' };
                var chart = new google.visualization.LineChart(document.getElementById('numberImaged'));
                chart.draw(data, options);
            }
        </script>
    </body>
</html>