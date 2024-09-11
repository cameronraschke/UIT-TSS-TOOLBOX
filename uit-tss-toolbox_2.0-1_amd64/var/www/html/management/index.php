<?php
require('/var/www/html/management/header.php');
include('/var/www/html/management/php/include.php');

$db = new db();

$db->select("SELECT date, erase_avgtime, clone_avgtime FROM serverstats ORDER BY date ASC");
?>
<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
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
            <div style='width: 50%;'>
                <div class='page-content'><h3><a href="/remote.php">Remote Management and Live Overview</a></h3></div>
                <div class='page-content'><h3><a href="/locations.php">Update and View Client Locations</a></h3></div>
                <div class='page-content'><h3><a href="/serverstats.php">Daily Reports</a></h3></div>
                <div class='page-content'><h3><a href="/clientstats.php">Client Report</a></h3></div>
                <div class='page-content'><h3><a href="/reports.php">Generate and Download Reports (WIP)</a></h3></div>
            </div>
            <div style='width: 40%; float: right;'>
                <div id="jobTimes" style='height: auto; width: 50%;'></div>
            </div>
        </div>

        <script src="https://www.gstatic.com/charts/loader.js"></script>

        <script>
        google.charts.load('current',{packages:['corechart']});
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
            const data = google.visualization.arrayToDataTable([ ['Date', 'Time (minutes)'],
            <?php
            foreach ($db->get() as $key => $value) {
                echo "['" . $value["date"] . "', " . $value["clone_avgtime"] . "], ";
            }
            ?>
            ]);

            const options = {title: 'Avg. Clone Time', hAxis: {title: 'Date'}, vAxis: {title: 'Time (minutes)'}, legend: 'none'  };
            const chart = new google.visualization.LineChart(document.getElementById('myChart'));
            chart.draw(data, options);
        }
</script>

        <div class="uit-footer">
            <img src="/images/uh-footer.svg">
        </div>
    </body>
</html>