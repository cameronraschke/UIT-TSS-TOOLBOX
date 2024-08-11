<?php require('/var/www/html/management/header.php'); ?>
<html>
<head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>UIT Laptop Managment</title>
        <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
    </head>
    <body>
        <div class='menubar'>
            <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
            <p><span style='float: right;'>Logged in as <b><?php echo "$login_user"; ?></b>.</span></p>
            <br>
            <p><span style='float: right;'>Not <b><?php echo "$login_user"; ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
        </div>

        <div class='pagetitle' style="text-align:center;"><h1 style="margin:auto;">TechComm Laptop Management Site</h1></div>
        <div class='pagetitle'><h2>Welcome, <?php echo $login_user; ?>.</h2></div>
        <div class='page-content'><h3><a href="remote.php">Remote Management and Live Overview</a></h3></div>
        <div class='page-content'><h3><a href="locations.php">Update and View Client Locations</a></h3></div>
        <div class='page-content'><h3><a href="serverstats.php">Date Report</a></h3></div>
        <div class='page-content'><h3><a href="clientstats.php">Client Report(WIP)</a></h3></div>
        <div class='page-content'><h3><a href="reports.php">Generate and Download Reports (WIP)</a></h3></div>

        <div class="uit-footer">
            <img src="https://uh.edu/infotech/_images/_reorg-images/uh-2ndry-uit-artboard_horiz-reverse_black.svg">
        </div>
    </body>
</html>