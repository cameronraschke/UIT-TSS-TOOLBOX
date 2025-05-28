<?php
require("/var/www/html/management/php/include.php");

if ($_POST["password"] !== "UHouston!") {
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


$notnull = array();
foreach (json_decode($_POST["notnull"]) as $nums => $cols) {
  if (array_search($cols, $acceptableCols) === false) {
    //echo "ERR: Bad column name" . PHP_EOL;
    exit();
  }
  array_push($notnull, $cols);
}
$notnull = implode(" IS NOT NULL AND ", $notnull);


unset($returnArr);
$returnArr = array();
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["queryType"] == "select") {
  $sql = "SELECT " . $columns . " FROM " . $_POST["table"] . " WHERE tagnumber = :tagnumber AND $notnull IS NOT NULL ORDER BY time DESC LIMIT 1";
  $sqlArr = array(
    ':tagnumber' => $_POST["tagnumber"]
  );
  $db->AssocPSelect($sql, $sqlArr);
  foreach ($db->nested_get() as $key => $value) {
    $returnArr += [ $key => $value ];
  }
  echo json_encode($returnArr) . PHP_EOL;
}
?>