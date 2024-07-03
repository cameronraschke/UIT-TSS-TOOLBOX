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
        <p><span style='float: right;'>Not <b><?php echo "$login_user"; ?></b>? <a href='logout.php'>Click Here to Logout</a></p>
        </p>
        <br>
        <div class='pagetitle'>
                <h2>Welcome to Plutomail Web, <?php echo("$login_user"); ?>!</h2>
        </div>
        <h1>UIT-TSS-Toolbox Management</h1>
        <br>
        <h3><a href="remote.php">Live Overview</a></h3>
        <h3><a href="serverstats.php">Overview of All Laptops</a></h3>
        <h3><a href="clientstats.php">Per-Client Overview</a></h3>
    </body>
</html>