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
$sql = "SELECT checkout.time, t1.bold_bool, checkout.tagnumber, checkout.customer_name, checkout.customer_psid, checkout.checkout_date, checkout.return_date, checkout.checkout_bool, checkout.note
    FROM checkout
    LEFT JOIN (SELECT time, '1' AS 'bold_bool', ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'row_nums' FROM checkout WHERE checkout_bool = 1) t1
        ON t1.time = checkout.time
    LEFT JOIN (SELECT time, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'row_nums' FROM checkout WHERE (checkout_bool IS NULL OR checkout_bool = 0)) t2
        ON t2.time = checkout.time
    WHERE (checkout.checkout_date IS NOT NULL OR checkout.return_date) IS NOT NULL 
        AND (t1.row_nums <= 1 OR t1.row_nums IS NULL) AND (t2.row_nums <= 1 OR t2.row_nums IS NULL) 
        AND NOT (t1.row_nums IS NULL AND t2.row_nums IS NULL) ORDER BY checkout.tagnumber DESC, checkout.time DESC";

if (isset($sqlArr)) {
    $db->Pselect($sql, $sqlArr);
} else {
    $db->select($sql);
}
unset($sql);


if (arrFilter($db->get()) === 0) {
  foreach ($db->get() as $key => $value) {
    echo "<tr>";
    //tagnumber
    echo "<td>";
    // $sql = "SELECT t1.checkout_bool FROM (SELECT tagnumber, checkout_bool, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'row_nums' FROM checkout) t1 WHERE t1.tagnumber = :tag AND t1.row_nums = 1 AND t1.checkout_bool = 1";
    // $db->Pselect($sql, array(':tag' => $value["tagnumber"]));
    // if (strFilter($db->get()) === 0) {
    if ($value["bold_bool"] == 1) {
        if (strFilter($value["tagnumber"]) === 0) {
            echo "<b>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</b>";
        }
    } else {
        if (strFilter($value["tagnumber"]) === 0) {
            echo htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
        }
    }
    echo "</td>" . PHP_EOL;


    //customer name
    echo "<td>";
    if (strFilter($value["customer_name"]) === 0) {
        echo htmlspecialchars($value["customer_name"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
    }
    echo "</td>" . PHP_EOL;

    // //customer psid
    // echo "<td>";
    // if (strFilter($value["customer_psid"]) === 0) {
    //     echo htmlspecialchars($value["customer_psid"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
    // }
    // echo "</td>" . PHP_EOL;

    //checkout_date
    echo "<td>";
    if (strFilter($value["checkout_date"]) === 0) {
        echo htmlspecialchars($value["checkout_date"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
    }
    echo "</td>" . PHP_EOL;

    //return_date
    echo "<td>";
    if (strFilter($value["return_date"]) === 0) {
        echo htmlspecialchars($value["return_date"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
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