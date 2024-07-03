<h1>Remote Table</h1>
<h3>The remote table monitors laptops currently plugged into the UIT-TSS-TOOLBOX server.</h3>
<?php
include('mysql-functions');
dbSelect("SELECT tagnumber FROM remote");
foreach ($arr as $key => $value) {
    echo "<p>$value</p>";
}
?>
<?php
include('mysql/mysql-functions');

header("refresh: 10");

ob_start();

echo "<table style='border-collapse: collapse' border='1'>";
echo "<tr>";
echo "<th>Name</th>";
echo "<th>Date</th>";
echo "<th>Price</th>";
echo "<th>Payed</th>";
echo "<th>Category</th>";
echo "</tr>";

$sql = "SELECT * FROM remote WHERE present_bool = '1' ORDER BY date DESC";
$results = $pdo->query($sql);
while ($row = $results->fetch_array(MYSQLI_ASSOC)) {
        $tagnumber = $row["tagnumber"];
        #$price = $row["price"];
        #$date = $row["date"];
        #$payed = $row["payed"];
        #$category = $row["category"];

if ($payed == "No") {
	echo "<tr>";
	echo "<td>$name</td>";
	echo "<td>$date</td>";
	echo "<td>\$$price</td>";
	echo "<td>$payed</td>";
	echo "<td>$category</td>";
	echo "</tr>";
}

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
$stdout = ob_get_contents();

ob_end_clean();
?>
