<?php require('header.php'); ?>
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

        <div class='pagetitle'><h1>Laptop Management Site</h1></div>
        <div class='pagetitle'><h2>Welcome, <?php echo $login_user; ?>.</h2></div>
        
        <div class='page-content'><h1>UIT-TSS-Toolbox Management</h1></div>
        <div class='page-content'><h3><a href="remote.php">Live Overview</a></h3></div>
        <div class='page-content'><h3><a href="serverstats.php">Overview of All Laptops (WIP)</a></h3></div>
        <div class='page-content'><h3><a href="clientstats.php">Per-Client Overview (WIP)</a></h3></div>
        <div class='page-content'><h3><a href="clientstats.php">Generate and Download Reports (WIP)</a></h3></div>
    </body>

    <div class="uit-logo">
            <img src="https://uh.edu/infotech/_images/_reorg-images/uh-2ndry-uit-artboard_horiz-reverse_black.svg">
    </div>
</html>