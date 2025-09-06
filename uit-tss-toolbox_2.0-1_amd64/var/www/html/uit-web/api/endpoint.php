<?php
require("/var/www/html/uit-web/php/include.php");

if ($_POST["password"] !== "UIT_WEB_SVC_PASSWD") {
  exit();
}

$db = new db();

//ORDER OF THESE ENTRIES MATTER
unset($acceptableTables);
$acceptableTables = array();
$db->select("SHOW TABLES");
foreach ($db->get() as $key => $value) {
  array_push($acceptableTables, $value["Tables_in_laptopDB"]);
}
unset($value);

if (array_search($_POST["table"], $acceptableTables) === false) {
  //echo "ERR: Bad table name" . PHP_EOL;
  exit();
}

//ORDER OF THESE ENTRIES MATTER
unset($acceptableCols);
$acceptableCols = array();
$db->select("DESCRIBE " . $_POST["table"]);
foreach ($db->get() as $key => $value) {
  array_push($acceptableCols, $value["Field"]);
}
unset($value);

$columns = array();
foreach (json_decode($_POST["columns"]) as $nums => $cols) {
  if (array_search($cols, $acceptableCols) === false) {
    //echo "ERR: Bad column name" . PHP_EOL;
    exit();
  }
  array_push($columns, $cols);
}
$columns = implode(", ", $columns);

// Same logic as columns
$notnull = array();
foreach (json_decode($_POST["notnull"]) as $nums => $cols) {
  if (array_search($cols, $acceptableCols) === false) {
    //echo "ERR: Bad column name" . PHP_EOL;
    exit();
  }
  array_push($notnull, $cols);
}
$notnull = implode(" IS NOT NULL AND ", $notnull);

// Same logic as columns
foreach (json_decode($_POST["preparedCols"]) as $key => $value) {
        $preparedColsArr = array_chunk(explode('=', $value), 2, true);
        var_dump($preparedColsArr);
        foreach ($preparedColsArr as $key1 => $value1) {
                echo $key1 . " => " . $value1;
        }
}

unset($sqlArr);
unset($returnArr);
$returnArr = array();
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["queryType"] == "select") {
  $sql = "SELECT " . $columns . " FROM " . $_POST["table"] . " WHERE $preparedCols AND $notnull IS NOT NULL ORDER BY time DESC LIMIT 1";
  #foreach (json_decode($_POST["preparedCols"]) as $key => $value) {
  #  $sqlArr += [ ":" . $key => $value ];
  #}
  unset($value);
  $db->AssocPSelect($sql, $sqlArr);
  foreach ($db->nested_get() as $key => $value) {
    $returnArr += [ $key => $value ];
  }
  echo json_encode($returnArr) . PHP_EOL;
}
?>