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
<h3>Updating tables</h3>
<?php
    echo "<p>Updating Client Statistics...</p>" . PHP_EOL;
    include('/var/www/html/management/php/uit-sql-refresh-client');
    echo "<p><i>Done!</i></p>" . PHP_EOL;
    echo "<p>Updating Location Data... </p>" . PHP_EOL;
    include('/var/www/html/management/php/uit-sql-refresh-location');
    echo "<p><i>Done!</i></p>" . PHP_EOL;
    echo "<p>Updating Remote Job Data... </p>" . PHP_EOL;
    include('/var/www/html/management/php/uit-sql-refresh-remote');
    echo "<p><i>Done!</i></p>" . PHP_EOL;
    echo "<p>Updating Daily Reports Data... </p>" . PHP_EOL;
    include('/var/www/html/management/php/uit-sql-refresh-server');
    echo "<p><i>Done!</i></p>" . PHP_EOL;
?>
</body>

</html>