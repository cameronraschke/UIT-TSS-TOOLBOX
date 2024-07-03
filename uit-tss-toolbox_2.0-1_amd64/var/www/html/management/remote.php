<h1>Remote Table</h1>
<h3>The remote table monitors laptops currently plugged into the UIT-TSS-TOOLBOX server.</h3>
<?php
require('./uit-sql-refresh-remote');
include('./mysql-functions');
dbSelect("SELECT tagnumber FROM remote");
foreach ($arr as $key => $value) {
    echo "<p>$value</p>";
}
?>
<?php
include('mysql/mysql-functions');

ob_start();

$due_sum = array();
$sql = "select SUM(price) as due_sum from billing WHERE requester = 'cameron@plutomail.io' AND payed = 'No'";
$results = $conn->query($sql);
while ($row = $results->fetch_array(MYSQLI_ASSOC)) {
        $due_sum = $row['due_sum'];
}

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
        $name = $row["name"];
        $price = $row["price"];
        $date = $row["date"];
        $payed = $row["payed"];
        $category = $row["category"];

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
echo "<p><a href='https://web.plutomail.io/billing.php'>View Billing Console</a></p>";

echo "</body></html>";
$stdout = ob_get_contents();

$conn->close();

        $to_2fa = "billing@plutomail.io";
        $subject_2fa = "Daily Billing Report";
        $body_2fa = "<p>Here are the unpaid charges on the billing console:</p><br>$stdout<br><p>Want to dispute a charge? <a href='mailto:cameron@plutomail.io?Subject=Billing Charge Dispute'>Click Here</a>";
        $headers_2fa = "From: \"Plutomail Billing\" <webmaster@plutomail.io> \r\nReply-To: cameron@plutomail.io \r\nMIME-Version: 1.0 \r\nContent-Type: text/html; charset=UTF-8 \r\n";

	mail($to_2fa, $subject_2fa, $body_2fa, $headers_2fa);
ob_end_clean();
?>
