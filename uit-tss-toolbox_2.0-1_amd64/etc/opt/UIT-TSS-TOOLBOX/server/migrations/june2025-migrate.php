#!/bin/php
<?php
//Migrate departments back into the locaitons table

require('/var/www/html/management/php/include.php');

$db = new db();

$db->select("select departments.time AS 'deptTime', locations.time AS 'locTime', locations.tagnumber, departments.department from departments inner join
locations on departments.time = locations.time order by locations.time asc");

foreach ($db->get() as $key => $value) {
    $db->updateLocation("department", $value["department"], $value["locTime"]);
}



?>