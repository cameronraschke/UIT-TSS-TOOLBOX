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
        <title>Home - UIT Client Management</title>
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
                <div id="jobTimes" style='height: auto; width: 99%; margin: 2% 1% 2% 1%;'></div>
            </div>
            <div style='width: 50%; float: right;'>
                <div id="runningJobs" style='height: 10%; width: 99%; padding: 2% 1% 5% 1%; margin: 2% 1% 5% 1%;'>
                            <?php
                            $db->select("SELECT COUNT(tagnumber) AS 'count' FROM remote WHERE job_queued IS NOT NULL AND NOT status = 'Waiting for job' AND present_bool = 1");
                            if (arrFilter($db->get()) === 0) {
                              foreach ($db->get() as $key => $value) {
                                echo "<h3><b>Queued Jobs:</b> " . htmlspecialchars($value["count"]) . "</h3>";
                              }
                            } else {
                              echo "<h3><b>Queued Jobs: </b>None</h3>";
                            }
                            ?>
                </div>
                <div id="numberImaged" style='height: auto; width: 99%; margin: 2% 1% 2% 1%;'></div>
                <div id="biosUpdated" style='height: auto; width: 99%; margin: 2% 1% 2% 1%;'></div>
            </div>
        </div>

        <script src="/google-charts/loader.js"></script>

        <script>
            google.charts.load('current',{packages:['corechart']});
            google.charts.setOnLoadCallback(jobTimes);

            function jobTimes() {
                var data = google.visualization.arrayToDataTable([ ['Date', 'Clone Time', 'Erase Time'],
                <?php
                $db->select("SELECT t1.dateByMonth FROM (SELECT DATE_FORMAT(date, '%Y-%m') AS 'dateByMonth' 
                FROM serverstats 
                WHERE date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY dateByMonth) t1
                INNER JOIN (SELECT DATE_FORMAT(date, '%Y-%m'),
                ROW_NUMBER() OVER (PARTITION BY DATE_FORMAT(date, '%Y-%m') ORDER BY avg_clone_time ASC) AS 'avg_clone_time'
                FROM serverstats
                WHERE date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY) t2
                    ON t1.dateByMonth = t2.dateByMonth");
                $db->select("SELECT * FROM 
                (SELECT date, DATE_FORMAT(date, '%Y-%m') AS 'dateByMonth', avg_clone_time, 
                    ROW_NUMBER() OVER (PARTITION BY DATE_FORMAT(date, '%Y-%m') ORDER BY avg_clone_time ASC) AS 'avg_clone_time' 
                FROM serverstats) t1 
                INNER JOIN 
                (SELECT DATE_FORMAT(date, '%Y-%m') AS 'dateByMonthErase', avg_erase_time, 
                    ROW_NUMBER() OVER (PARTITION BY DATE_FORMAT(date, '%Y-%m') ORDER BY avg_erase_time ASC) AS 'avg_erase_time' 
                FROM serverstats) t2 
                ON t1.dateByMonth = t2.dateByMonthErase 
                WHERE t1.avg_clone_time = 1 AND t2.avg_erase_time = 1 AND t1.date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)");
                if (arrFilter($db->get()) === 0)
                    foreach ($db->get() as $key => $value) {
                        echo "['" . htmlspecialchars($value["dateByMonth"]) . "', " . htmlspecialchars($value["avg_clone_time"]) . ", " . htmlspecialchars($value["avg_erase_time"]) . "], ";                    }
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
                $db->select("SELECT 
                    (SELECT COUNT(tagnumber) FROM remote 
                        WHERE os_installed = 1 
                            AND (NOT present_bool = 1 OR present_bool IS NULL)) AS 'os_installed_not_present', 
                    (SELECT COUNT(tagnumber) FROM remote 
                        WHERE (NOT os_installed = 1 OR os_installed IS NULL) 
                            AND (NOT present_bool = 1 OR present_bool IS NULL)) AS 'os_not_installed_not_present', 
                    (SELECT COUNT(tagnumber) FROM remote 
                        WHERE os_installed = 1 
                        AND present_bool = 1) AS 'os_installed_present', 
                    (SELECT COUNT(tagnumber) FROM remote 
                        WHERE (NOT os_installed = 1 OR os_installed IS NULL) 
                        AND present_bool = 1) AS 'os_not_installed_present'");
                    if (arrFilter($db->get()) === 0) {
                    foreach ($db->get() as $key => $value) {
                        echo "['OS NOT Installed, NOT online'," . htmlspecialchars($value["os_not_installed_not_present"]) . "], ";
                        echo "['OS NOT Installed, online'," . htmlspecialchars($value["os_not_installed_present"]) . "], ";
                        echo "['OS Installed, NOT online'," . htmlspecialchars($value["os_installed_not_present"]) . "], ";
                        echo "['OS Installed, online'," . htmlspecialchars($value["os_installed_present"]) . "], ";
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
            google.charts.load('current',{packages:['corechart']});
            google.charts.setOnLoadCallback(biosUpdated);

            function biosUpdated() {
                var data = google.visualization.arrayToDataTable([ ['OS Status', 'Client Count'],
                <?php
                $db->select("SELECT 
                    (SELECT COUNT(bios_updated) FROM clientstats 
                        WHERE bios_updated = 1) AS 'bios_updated', 
                    (SELECT SUM(IF(bios_updated IS NULL, 1, 0)) FROM clientstats 
                        WHERE bios_updated IS NULL) AS 'bios_not_updated'");
                    if (arrFilter($db->get()) === 0) {
                    foreach ($db->get() as $key => $value) {
                        echo "['BIOS Updated'," . $value["bios_updated"] . "], ";
                        echo "['BIOS out of Date'," . $value["bios_not_updated"] . "], ";
                    }
                }
                ?>
                ]);

                var options = {title: 'BIOS Status' };
                var chart = new google.visualization.PieChart(document.getElementById('biosUpdated'));
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