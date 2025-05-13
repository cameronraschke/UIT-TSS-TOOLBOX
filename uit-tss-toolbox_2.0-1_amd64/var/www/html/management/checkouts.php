<?php
require('/var/www/html/management/header.php');
require('/var/www/html/management/php/include.php');

$db = new db();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>Client Reports - UIT Client Mgmt</title>
        <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    </head>
    <body>
        <div class='menubar'>
            <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
            <p><span style='float: right;'>Logged in as <b><?php echo htmlspecialchars($login_user); ?></b>.</span></p>
            <br>
            <p><span style='float: right;'>Not <b><?php echo htmlspecialchars($login_user); ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
        </div>

        <div class='pagetitle'><h1>Client Report</h1></div>
        <div class='pagetitle'><h2>The client report shows aggregated statistics and information about every client.</h2></div>

        <div class='styled-table'>
            <table>
                <thead>
                <tr>
                    <th>Tag Number</th>
                    <th>Customer Name</th>
                    <th>Customer PSID</th>
                    <th>Checkout Date</th>
                    <th>Return Date</th>
                </tr>
                </thead>
                <tbody>
<?php
unset($sqlArr);
unset($sql);
$sql = "SELECT tagnumber, customer_name, customer_psid, checkout_date, return_date
    FROM checkout
        WHERE (checkout.checkout_date IS NOT NULL OR checkout.return_date) IS NOT NULL ";

if (isset($_GET["checkout_bool"])) {
    $sql .= "AND checkout.checkout_bool = :checkoutBool ";
    $sqlArr[":checkoutBool"] = $_GET["checkout_bool"];
}

$sql .= "ORDER BY checkout.time DESC";

if (isset($sqlArr)) {
    $db->Pselect($sql, $sqlArr);
} else {
    $db->select($sql);
}

if (arrFilter($db->get()) === 0) {
  foreach ($db->get() as $key => $value) {
    echo "<tr>" . PHP_EOL;

    //tag number
    echo "<td>";
    if (strFilter($value["tagnumber"]) === 0) {
        echo htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
    }
    echo "</td>" . PHP_EOL;

    //customer name
    echo "<td>";
    if (strFilter($value["customer_name"]) === 0) {
        echo htmlspecialchars($value["customer_name"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
    }
    echo "</td>" . PHP_EOL;

    //customer psid
    echo "<td>";
    if (strFilter($value["customer_psid"]) === 0) {
        echo htmlspecialchars($value["customer_psid"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
    }
    echo "</td>" . PHP_EOL;

    //checkout_date
    echo "<td>";
    if (strFilter($value["checkout_date"]) === 0) {
        echo htmlspecialchars($value["checkout_date"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "%";
    }
    echo "</td>" . PHP_EOL;

    //return_date
    echo "<td>";
    if (strFilter($value["return_date"]) === 0) {
        echo htmlspecialchars($value["return_date"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "%";
    }
    echo "</td>" . PHP_EOL;

        echo "</tr>" . PHP_EOL;
    }
}
?>

            </tbody>
        </table>
        </div>
        <div class="uit-footer">
            <img src="/images/uh-footer.svg">
        </div>
    </body>
</html>