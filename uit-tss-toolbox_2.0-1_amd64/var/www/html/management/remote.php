<?php
include('header.php');
include('mysql/mysql-functions');
?>

<html>
<body>
<h1>Remote Table</h1>
<h3>The remote table monitors laptops currently plugged into the UIT-TSS-TOOLBOX server.</h3>

<?php
echo "<table style='border-collapse: collapse' border='1'>";
echo "<tr>";
echo "<th>Tagnumber</th>";
echo "<th>Last Job Data</th>";
echo "</tr>";

dbSelect("SELECT * FROM remote WHERE present_bool = '1' ORDER BY date DESC");
foreach ($arr as $key => $value) {
    $tagnumber = $value["tagnumber"];
    $lastJob = $value["date"];

	echo "<tr>";
	echo "<td>$tagnumber</td>";
	echo "<td>$lastJob</td>";
	echo "</tr>";

}

echo "</table>";

echo "<table>";
echo "<th>Total Due</th>";
echo "<br>";
echo "<tr>";
echo "<td><b style='color: red;'>\$$due_sum<b></td>";
echo "</tr>";
echo "</table>";

echo "</body></html>";

?>
