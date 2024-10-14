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
    <body onload="fetchHTML()">
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
                <div><h3 class='page-content'><a href="/update-tables.php" target="_blank">Update Tables (will take around 2-3 mins)</a></h3></div>
            </div>
            <div style='width: 50%; float: right;'>
                <div id="runningJobs" style='height: 10%; width: 99%; padding: 2% 1% 5% 1%; margin: 2% 1% 5% 1%;'>
                            <?php
                                $db->select("SELECT COUNT(tagnumber) AS 'count', status FROM remote WHERE (task IS NOT NULL OR NOT status = 'Waiting for job') AND present_bool = 1 GROUP BY status");
                                if (arrFilter($db->get()) === 0) {
                                    foreach ($db->get() as $ley => $value) {
                                        echo "<h3><b>Running Jobs:</b> " . htmlspecialchars($value["count"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . " (" . htmlspecialchars($value["status"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . ")" . "</h3>";
                                    }
                                } else {
                                    echo "<h3><b>Running Jobs:</b>0</h3>";
                                }
                            ?>
                </div>
                <div id="jobTimes" style='height: auto; width: 99%; margin: 2% 1% 2% 1%;'></div>
                <div id="numberImaged" style='height: auto; width: 99%; margin: 2% 1% 2% 1%;'></div>
            </div>
        </div>

        <script src="/google-charts/loader.js"></script>

        <script>
            google.charts.load('current',{packages:['corechart']});
            google.charts.setOnLoadCallback(jobTimes);

            function jobTimes() {
                var data = google.visualization.arrayToDataTable([ ['Date', 'Clone Time', 'Erase Time'],
                <?php
                $db->select("SELECT * FROM (SELECT date, DATE_FORMAT(date, '%Y-%m') AS 'dateByMonth', clone_avgtime, ROW_NUMBER() OVER (PARTITION BY DATE_FORMAT(date, '%Y-%m') ORDER BY clone_avgtime ASC) AS 'clone_time' FROM serverstats) t1 INNER JOIN (SELECT DATE_FORMAT(date, '%Y-%m') AS 'dateByMonthErase', nvme_erase_avgtime, ROW_NUMBER() OVER (PARTITION BY DATE_FORMAT(date, '%Y-%m') ORDER BY nvme_erase_avgtime ASC) AS 'erase_time' FROM serverstats) t2 ON t1.dateByMonth = t2.dateByMonthErase WHERE t1.clone_time = 1 AND t2.erase_time = 1 AND t1.date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)");
                if (arrFilter($db->get()) === 0)
                    foreach ($db->get() as $key => $value) {
                        echo "['" . $value["dateByMonth"] . "', " . $value["clone_avgtime"] . ", " . $value["nvme_erase_avgtime"] . "], ";
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
                var data = google.visualization.arrayToDataTable([ ['OS Status', 'Client Count'],
                <?php
                $db->select("SELECT (SELECT COUNT(tagnumber) FROM remote WHERE os_installed = 1 AND (NOT present_bool = 1 OR present_bool IS NULL)) AS 'os_installed_not_present', (SELECT COUNT(tagnumber) FROM remote WHERE (NOT os_installed = 1 OR os_installed IS NULL) AND (NOT present_bool = 1 OR present_bool IS NULL)) AS 'os_not_installed_not_present', (SELECT COUNT(tagnumber) FROM remote WHERE os_installed = 1 AND present_bool = 1) AS 'os_installed_present', (SELECT COUNT(tagnumber) FROM remote WHERE (NOT os_installed = 1 OR os_installed IS NULL) AND present_bool = 1) AS 'os_not_installed_present'");
                if (arrFilter($db->get()) === 0) {
                    foreach ($db->get() as $key => $value) {
                        echo "['OS NOT Installed, NOT online'," . $value["os_not_installed_not_present"] . "], ";
                        echo "['OS NOT Installed, online'," . $value["os_not_installed_present"] . "], ";
                        echo "['OS Installed, NOT online'," . $value["os_installed_not_present"] . "], ";
                        echo "['OS Installed, online'," . $value["os_installed_present"] . "], ";
                    }
                }
                ?>
                ]);

                var options = {title: 'Number of OS\'s Installed' };
                var chart = new google.visualization.PieChart(document.getElementById('numberImaged'));
                chart.draw(data, options);
            }
        </script>
        <script>
            function fetchHTML() {
                setTimeout(function() {
                fetch('/index.php')
                .then((response) => {
                        return response.text();
                })
                .then((html) => {
                    //document.body.innerHTML = html
                    const parser = new DOMParser()
                    const doc = parser.parseFromString(html, "text/html")
                    //Update running jobs
                    const runningJobs = doc.getElementById('runningJobs').innerHTML
                    document.getElementById("runningJobs").innerHTML = runningJobs
                });
                fetchHTML();
            }, 3000)}
        </script>
    </body>
</html>