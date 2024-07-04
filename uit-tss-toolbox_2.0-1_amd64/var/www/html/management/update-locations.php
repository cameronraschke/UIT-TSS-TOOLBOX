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
        <?php
        if (!empty($_POST['tagnumber'])) {
            dbSelect("SELECT * FROM locations WHERE tagnumber = '" . $_POST['tagnumber'] . "' ORDER BY time DESC LIMIT 1");
            if (!empty($arr)) {
                foreach ($arr as $key => $value) {
                    echo "<form method='post'>" . PHP_EOL;
                    echo "<label for='tagnumber'>Tag Number</label>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<input type='text' id='tagnumber' name='tagnumber' value='" . $_POST['tagnumber'] . "' readonly>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<label for='serial'>Serial Number</label>";
                    echo "<br>" . PHP_EOL;
                    echo "<input type='text' id='serial' name='serial' value='" . $value['system_serial'] . "' readonly>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<label for='department'>Department</label>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<select name='department' id='department'>" . PHP_EOL;
                    echo "<option value='techComm'>Tech Comms (Default)</option>" . PHP_EOL;
                    echo "<option value='property'>Property Management</option>" . PHP_EOL;
                    echo "<option value='shrl'>SHRL (Kirven)</option>" . PHP_EOL;
                    echo "</select>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<label for='location'>Location</label>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<input type='text' id='location' name='location'>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<label for='note'>Problem</label>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<input type='text' id='note' name='note'>" . PHP_EOL;
                    echo "<br>" . PHP_EOL;
                    echo "<input type='submit' value='Update Location'>" . PHP_EOL;
                    echo "</form>" . PHP_EOL;
                }
            } else {
                echo "<form method='post'>" . PHP_EOL;
                echo "<label for='tagnumber'>Tag Number</label>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<input type='text' id='tagnumber' name='tagnumber' value='" . $_POST['tagnumber'] . "' readonly>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<label for='serial'>Serial Number</label>";
                echo "<br>" . PHP_EOL;
                echo "<input type='text' id='serial' name='serial'" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<label for='department'>Department</label>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<select name='department' id='department'>" . PHP_EOL;
                echo "<option value='techComm'>Tech Comms (Default)</option>" . PHP_EOL;
                echo "<option value='property'>Property Management</option>" . PHP_EOL;
                echo "<option value='shrl'>SHRL (Kirven)</option>" . PHP_EOL;
                echo "</select>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<label for='location'>Location</label>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<input type='text' id='location' name='location'>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<label for='note'>Problem</label>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<input type='text' id='note' name='note'>" . PHP_EOL;
                echo "<br>" . PHP_EOL;
                echo "<input type='submit' value='Update Location'>" . PHP_EOL;
                echo "</form>" . PHP_EOL;
            }
            unset($_POST);
        } else {
            echo "<form method='post'>" . PHP_EOL;
            echo "<label for='tagnumber'>Tag Number</label>" . PHP_EOL;
            echo "<br>" . PHP_EOL;
            echo "<input type='text' id='tagnumber' name='tagnumber'>";
            echo "<br>" . PHP_EOL;
            echo "<p>Please enter the status:</p>" . PHP_EOL;
            echo "<input type='radio' id='status1' name='status' value='0'>" . PHP_EOL;
            echo "<label for='status'>Working</label>" . PHP_EOL;
            echo "<br>" . PHP_EOL;
            echo "<input type='radio' id='status2' name='status' value='1'>" . PHP_EOL;
            echo "<label for='status'>Broken</label>" . PHP_EOL;
            echo "<br>" . PHP_EOL;
            echo "<input type='submit' value='Search'>";
            echo "<br>" . PHP_EOL;
            echo "</form>" . PHP_EOL;
        }

        ?>
        
        </div>
    <div class="uit-footer">
        <img src="https://uh.edu/infotech/_images/_reorg-images/uh-2ndry-uit-artboard_horiz-reverse_black.svg">
    </div>

    <script>
        if ( window.history.replaceState ) {
            window.history.replaceState( null, null, window.location.href );
        }
    </script>
    </body>
</html>