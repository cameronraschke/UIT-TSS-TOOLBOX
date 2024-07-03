<?php
include('header.php');
include('mysql/mysql-functions');
?>
<html>
<body>
<h1>Remote Table</h1>
<h3>The remote table monitors laptops currently plugged into the UIT-TSS-TOOLBOX server.</h3>
<?php

dbSelect("SELECT tagnumber FROM remote");
foreach ($arr as $key => $value) {
    echo "<p>$value[tagnumber]</p>" . PHP_EOL;
}
?>
<?php

echo "<table style='border-collapse: collapse' border='1'>";
echo "<tr>";
echo "<th>Name</th>";
echo "<th>Date</th>";
echo "<th>Price</th>";
echo "<th>Payed</th>";
echo "<th>Category</th>";
echo "</tr>";

dbSelect("SELECT * FROM remote WHERE present_bool = '1' ORDER BY date DESC");
foreach ($arr as $key => $value) {
    $tagnumber = $row["tagnumber"];
    #$price = $row["price"];
    #$date = $row["date"];
    #$payed = $row["payed"];
    #$category = $row["category"];

	echo "<tr>";
	echo "<td>$tagnumber</td>";
	echo "<td>$date</td>";
	echo "<td>\$$price</td>";
	echo "<td>$payed</td>";
	echo "<td>$category</td>";
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
