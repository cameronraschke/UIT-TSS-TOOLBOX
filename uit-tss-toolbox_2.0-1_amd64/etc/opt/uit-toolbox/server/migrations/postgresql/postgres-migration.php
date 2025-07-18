#!/bin/php
<?php
require('/var/www/html/uit-web/php/include.php');

$db = new db();
$dbPSQL = new dbPSQL();

$db->select("SHOW TABLES");
foreach ($db->get() as $tablesKey => $tablesValue) {

$db->AssocSelect("SELECT * FROM " . $tablesValue["Tables_in_laptopDB"]);
$dbPSQL->select("DELETE FROM " . $tablesValue["Tables_in_laptopDB"]);
if (preg_match('/archive.*/', $tablesValue["Tables_in_laptopDB"]) === 0) {
foreach ($db->get() as $key => $value) {
        $arrKeys = array_keys($value);
        $arrKeysStr = implode(',', $arrKeys);
        $arrVals = array();
        $arrPreparedVals = array();
        foreach ($arrKeys as $key2 => $value2) {
                array_push($arrVals, $value[$value2]);
                array_push($arrPreparedVals, ":" . $value2 . "");
        }
        $arrPreparedVals = implode(', ', $arrPreparedVals);
        $sql = "INSERT INTO " . $tablesValue["Tables_in_laptopDB"] . " (" . $arrKeysStr . ") VALUES (" . $arrPreparedVals  . ")";
        $sqlArr = array_combine(array_keys($value), $arrVals);
        if ($sqlArr === $value) {
        $dbPSQL->Pselect($sql, $sqlArr);
        } else {
        echo "ERR: " . $arrVals . PHP_EOL;
        }
}

}
}
unset($value);

?>