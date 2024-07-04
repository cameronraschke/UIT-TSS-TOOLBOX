<?php
require('header.php');
include('mysql/mysql-functions');
$dt = new DateTimeImmutable();
$date = $dt->format('Y-m-d');
$time = $dt->format('Y-m-d H:i:s.v');
?>

<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>TechComm Laptop Managment</title>
    </head>
    <body>
        <div class='menubar'>
            <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
            <p><span style='float: right;'>Logged in as <b><?php echo "$login_user"; ?></b>.</span></p>
            <br>
            <p><span style='float: right;'>Not <b><?php echo "$login_user"; ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
        </div>

        <div class='pagetitle'><h1>Update Client Locations</h1></div>
        <div class='pagetitle'><h2>Here you can update all the information about a client's location and general status.</h2></div>


        <div class='location-form'>
        <form method="post">
        <?php
        if ($_POST['tagnumber']) {
            echo "<label for='tagnumber'>Tag Number</label>";
            echo "<input type='text' id='tagnumber' name='tagnumber'>";
            echo "<input type='submit' value='Search'>";
            echo "<label for='serial'>Serial Number</label>";
            echo "<input type='text' id='serial' name='serial' value='$systemSerial' readonly>";
            echo "<br>";
            echo "<label for='location'>Location</label>";
            echo "<input type='text' id='location' name='location'>";
        } else {
            echo "<label for='tagnumber'>Tag Number</label>";
            echo "<input type='text' id='tagnumber' name='tagnumber'>";
            echo "<input type='submit' value='Search'>";
            echo "<br>";
        }

        ?>
        </form>
        </div>
    <div class="uit-footer">
        <img src="https://uh.edu/infotech/_images/_reorg-images/uh-2ndry-uit-artboard_horiz-reverse_black.svg">
    </div>
    </body>
</html>