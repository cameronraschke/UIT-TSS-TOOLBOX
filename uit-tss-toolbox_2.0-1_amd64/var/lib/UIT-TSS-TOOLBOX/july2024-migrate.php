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

dbSelect("SELECT tagnumber, wifi_mac, system_manufacturer, system_model, system_uuid, system_sku, chassis_type, cpu_manufacturer, cpu_model, cpu_maxspeed, cpu_cores, cpu_threads, motherboard_manufacturer, motherboard_serial FROM jobstats WHERE tagnumber IS NOT NULL GROUP BY tagnumber");
foreach ($arr as $key=>$value) {
    $tagNum = $value['tagnumber'];
    dbInsertSystemData("$tagNum");
    dbUpdateSystemData("$tagNum", "wifi_mac", $value["wifi_mac"]);
}


?>