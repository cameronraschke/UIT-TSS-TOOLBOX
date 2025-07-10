<?php
require('/var/www/html/uit-web/header.php');
require('/var/www/html/uit-web/php/include.php');

if ($_SESSION['authorized'] != "yes") {
  die();
}

$db = new db();

$sql = "SELECT * FROM (SELECT checkouts.time, checkouts.tagnumber, checkouts.customer_name, checkouts.customer_psid, checkouts.checkout_date, checkouts.return_date, checkouts.checkout_bool, checkouts.note,
    ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'row_nums' 
    FROM checkouts) t1
    WHERE (t1.checkout_date IS NOT NULL OR t1.return_date) IS NOT NULL 
        AND t1.row_nums <= 1 AND NOT t1.row_nums IS NULL AND t1.checkout_bool = 1 ORDER BY t1.customer_name ASC, t1.checkout_date DESC, t1.tagnumber DESC";

if (isset($sqlArr)) {
    $db->Pselect($sql, $sqlArr);
} else {
    $db->select($sql);
}
unset($sql);
$rowCount = count($db->get());
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>Checkout History - UIT Client Mgmt</title>
        <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    </head>
    <body>
        <div class='menubar'>
            <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
            <p><span style='float: right;'>Logged in as <b><?php echo htmlspecialchars($login_user); ?></b>.</span></p>
            <br>
            <p><span style='float: right;'>Not <b><?php echo htmlspecialchars($login_user); ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
        </div>

        <div class='pagetitle'><h1>Checkout History</h1></div>
        <div class='pagetitle'><h2>These clients are currently checked out.</h2></div>

        <div class='pagetitle'>Clients checked out: <?php echo $rowCount; ?></div>
        <div class='styled-form2'>
            <input type="text" id="myInput" onkeyup="myFunction()" autocapitalize='none' autocomplete='off' autocorrect='off' spellcheck='false' placeholder="Search tag number...">
            <input type="text" id="myInputName" onkeyup="myFunctionName()" autocapitalize='none' autocomplete='off' autocorrect='off' spellcheck='false' placeholder="Search customer name...">
        </div>
        <div class='styled-table'>
            <table id='myTable' style='min-width: 50%;'>
                <thead>
                <tr>
                    <th>Tag Number</th>
                    <th>Customer Name</th>
                    <th>Checkout Date</th>
                    <th>Return Date</th>
                    <th>Note</th>
                </tr>
                </thead>
                <tbody>
<?php
unset($sqlArr);
unset($sql);
// $sql = "SELECT checkouts.time, checkouts.tagnumber, checkouts.customer_name, checkouts.customer_psid, checkouts.checkout_date, checkouts.return_date, checkouts.checkout_bool, checkouts.note
//     FROM checkout
//     LEFT JOIN (SELECT time, tagnumber, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'row_nums' FROM checkouts WHERE checkout_bool = 1) t1
//         ON t1.time = checkouts.time
//     LEFT JOIN (SELECT time, tagnumber, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'row_nums' FROM checkouts WHERE (checkout_bool IS NULL OR checkout_bool = 0)) t2
//         ON t2.time = checkouts.time
//     WHERE (checkouts.checkout_date IS NOT NULL OR checkouts.return_date) IS NOT NULL 
//         AND (t1.row_nums <= 1 OR t1.row_nums IS NULL) AND (t2.row_nums <= 1 OR t2.row_nums IS NULL) 
//         AND NOT (t1.row_nums IS NULL AND t2.row_nums IS NULL) ORDER BY checkouts.customer_name, checkouts.tagnumber DESC, checkouts.time DESC";


if (arrFilter($db->get()) === 0) {
  foreach ($db->get() as $key => $value) {
    echo "<tr>";
    //tagnumber
    echo "<td>";
        echo "<b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "'>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b>" . PHP_EOL;
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

    //note
    echo "<td>";
    if (strFilter($value["note"]) === 0) {
        echo htmlspecialchars($value["note"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
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

    <script>
          function myFunction() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("myInput");
        filter = input.value.toUpperCase();
        table = document.getElementById("myTable");
        tr = table.getElementsByTagName("tr");

        for (i = 0; i < tr.length; i++) {
          td = tr[i].getElementsByTagName("td")[0];
          if (td) {
            txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
              tr[i].style.display = "";
            } else {
              tr[i].style.display = "none";
            }
          }
        }
      }

    function myFunctionName() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("myInputName");
        filter = input.value.toUpperCase();
        table = document.getElementById("myTable");
        tr = table.getElementsByTagName("tr");

        for (i = 0; i < tr.length; i++) {
          td = tr[i].getElementsByTagName("td")[1];
          if (td) {
            txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
              tr[i].style.display = "";
            } else {
              tr[i].style.display = "none";
            }
          }
        }
      }
    </script>
</html>