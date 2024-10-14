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
<h3>Updating tables<h3>
    <p>Updating Client Statistics...</p>
    <?php include('/var/www/html/management/php/uit-sql-refresh-client'); ?>
    <p><i>Done!</i></p>
    <p>Updating Location Data... </p>
    <?php include('/var/www/html/management/php/uit-sql-refresh-location'); ?>
    <p><i>Done!</i></p>
    <p>Updating Remote Job Data... </p>
    <?php include('/var/www/html/management/php/uit-sql-refresh-remote'); ?>
    <p><i>Done!</i></p>
    <p>Updating Daily Reports Data... </p>
    <?php include('/var/www/html/management/php/uit-sql-refresh-server'); ?>
    <p><i>Done!</i></p>
</body>

</html>