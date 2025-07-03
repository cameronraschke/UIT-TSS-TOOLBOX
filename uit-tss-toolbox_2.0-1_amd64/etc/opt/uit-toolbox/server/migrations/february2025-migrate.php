#!/bin/php
<?php
// Migrate department date to a new table called "departments" and rename the current departments table to static_departments

require('/var/www/html/uit-web/php/include.php');

$db = new db();

$db->select("SELECT time, tagnumber, system_serial, department FROM jobstats ORDER BY time DESC");
foreach ($db->get() as $key => $value) {
    $db->insertDepartments($value["time"]);
    $db->updateDepartments("tagnumber", $value["tagnumber"], $value["time"]);
    $db->updateDepartments("system_serial", $value["system_serial"], $value["time"]);
    $db->updateDepartments("department", $value["department"], $value["time"]);
}

?>