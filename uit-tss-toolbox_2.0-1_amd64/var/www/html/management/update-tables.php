<?php
    require('/var/www/html/management/header.php');
    require('/var/www/html/management/php/include.php');
?>

<!DOCTYPE html>
<head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>Update Table Data - UIT Client Mgmt</title>
        <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
        <style>
            h3 {
                margin: 2% 1% 1% 2%;
            }
            p {
                margin: 1% 1% 1% 2%;
            }
        </style>
</head>
<body>
<h3>Update tables</h3>
<form id="refresh-form" name="refresh-form" method="post">
    <select id="refresh-value" name="refresh-value">
        <option value="0">--Please Select an Option--</option>
        <option value="1">Refresh Database Data</option>
    </select>
    <input type="submit" value="Submit">
</form>
<?php
    if ($_POST["refresh-value"] == "1") {
        echo "<p>Updating Client Statistics...</p>" . PHP_EOL;
        include('/var/www/html/management/php/uit-sql-refresh-client');
        echo "<p><i>Done!</i></p>" . PHP_EOL;
        echo "<p>Updating Location Data... </p>" . PHP_EOL;
        include('/var/www/html/management/php/uit-sql-refresh-location');
        echo "<p><i>Done!</i></p>" . PHP_EOL;
        echo "<p>Updating Remote Job Data... </p>" . PHP_EOL;
        include('/var/www/html/management/php/uit-sql-refresh-remote');
        echo "<p><i>Done!</i></p>" . PHP_EOL;
        echo "<p>Daily Reports Not Updating. </p>" . PHP_EOL;
        //echo "<p>Updating Daily Reports Data... </p>" . PHP_EOL;
        //include('/var/www/html/management/php/uit-sql-refresh-server');
        //echo "<p><i>Done!</i></p>" . PHP_EOL;
    }
?>
</body>

</html>