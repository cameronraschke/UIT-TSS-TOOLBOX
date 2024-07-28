#!/bin/php
<?php
/* Migrate the following columns to a static table:
wifi_mac
system_manufacturer
system_model
system_uuid
system_sku
chassis_type
cpu_manufacturer
cpu_model
cpu_maxspeed
cpu_cores
cpu_threads
motherboard_manufacturer
motherboard_serial
*/
include('/var/lib/UIT-TSS-TOOLBOX/mysql-functions');

dbSelect("SELECT tagnumber FROM jobstats WHERE tagnumber IS NOT NULL GROUP BY tagnumber");
foreach ($arr as $key=>$value) {
    $tagNum = $value['tagnumber'];
    dbSelectVal("SELECT tagnumber FROM system_data WHERE tagnumber = '" . $tagNum . "'");
    if ($result == "NULL") {
        dbInsertSystemData("$tagNum");
    }
    dbSelectVal("SELECT wifi_mac FROM jobstats WHERE wifi_mac IS NOT NULL AND tagnumber = '" . $tagNum . "' ORDER BY time DESC LIMIT 1");
    dbUpdateSystemData("$tagNum", "wifi_mac", $result);
}


?>