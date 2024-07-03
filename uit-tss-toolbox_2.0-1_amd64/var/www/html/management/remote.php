<?php
include('header.php');
include('mysql/mysql-functions');
?>

<html>
<body>
<h1>Remote Table</h1>
<h3>The remote table monitors laptops currently plugged into the UIT-TSS-TOOLBOX server.</h3>

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
