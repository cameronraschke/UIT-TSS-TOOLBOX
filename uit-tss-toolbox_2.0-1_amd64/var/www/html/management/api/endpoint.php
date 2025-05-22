<?php
require("/var/www/html/management/php/include.php");

if ($_POST["password"] !== "UHouston!") {
  exit();
}

$db = new db();

unset($acceptableTables);
$acceptableTables = array();
$db->select("SHOW TABLES");
foreach ($db->get() as $key => $value) {
  array_push($acceptableTables, $value["Tables_in_laptopDB"]);
}
unset($value);

if (array_search($_POST["table"], $acceptableTables) === false) {
  echo "ERR: Bad table name" . PHP_EOL;
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

unset($cols);
unset($columns);
$columnsArr = array();
$postCols = json_decode($_POST["columns"]);
foreach ($postCols as $nums => $cols) {
  if (array_search($cols, $acceptableCols) === false) {
    echo "ERR: Bad column name" . PHP_EOL;
    exit();
  }
  array_push($columnsArr, $cols);
}
$columns = implode(", ", $columnsArr);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $sql = "SELECT " . $columns . " FROM " . $_POST["table"] . " WHERE tagnumber = :tagnumber";
  $sqlArr = array(
    ':tagnumber' => $_POST["tagnumber"],
  );
  $db->Pselect($sql, $sqlArr);
  foreach ($db->get() as $key => $value) {
    $colCount = count($value) / 2;
    $colArr = explode(", ", implode(', ', range(1, $colCount)));
    foreach ($colArr as $num => $count) {
      $count = $count - 1;
      echo $columnsArr[$count] . " => ";
      echo $value[$count] . PHP_EOL;
    }
  }
}

?>