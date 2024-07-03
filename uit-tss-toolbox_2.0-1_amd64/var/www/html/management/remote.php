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
        <h1>Remote Table</h1>
        <h3>The remote table monitors laptops currently plugged into the UIT-TSS-TOOLBOX server.</h3>
        <h4>Last updated: <?php echo $time; ?></h4>

<?php
dbSelect("CALL selectRemoteStats");
foreach ($arr as $key => $value) {
    $present = $value['Present Laptops'];
    echo "<table>";
    echo "<tr>";
    echo "<th>Total Laptops Present</th>";
    echo "</tr>";

    echo "<tr>";
    echo "<td>$present</td>" . PHP_EOL;
    echo "</tr>";
    echo "</table>";
}

echo "<br><br>";
echo "<table style='border-collapse: collapse' border='1'>";
echo "<tr>";
echo "<th>Tagnumber</th>";
echo "<th>Last Job Data</th>";
echo "</tr>";

dbSelect("SELECT * FROM remote ORDER BY date DESC");
foreach ($arr as $key => $value) {
    $tagnumber = $value["tagnumber"];
    $lastJob = $value["date"];

	echo "<tr>";
	echo "<td>$tagnumber</td>" . PHP_EOL;
	echo "<td>$lastJob</td>" . PHP_EOL;
	echo "</tr>";

}

echo "</table>";

echo "</body></html>";

?>
