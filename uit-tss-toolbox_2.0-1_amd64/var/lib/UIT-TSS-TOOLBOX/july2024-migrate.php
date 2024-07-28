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
    echo "Working on" . $tagNum . PHP_EOL;

    // Check if tag already exists, if not then insert it
    dbSelectVal("SELECT tagnumber AS 'result' FROM system_data WHERE tagnumber = '" . $tagNum . "'");
    if (filter($result) == 1) {
        dbInsertSystemData("$tagNum");
    }

    // wifi_mac
    dbSelectVal("SELECT wifi_mac AS 'result' FROM jobstats WHERE wifi_mac IS NOT NULL AND tagnumber = '" . $tagNum . "' ORDER BY time DESC LIMIT 1");
    dbUpdateSystemData("$tagNum", "wifi_mac", $result);

    // system_manufacturer
    dbSelectVal("SELECT system_manufacturer AS 'result' FROM jobstats WHERE system_manufacturer IS NOT NULL AND tagnumber = '" . $tagNum . "' ORDER BY time DESC LIMIT 1");
    dbUpdateSystemData("$tagNum", "system_manufacturer", $result);

    // system_model
    dbSelectVal("SELECT system_model AS 'result' FROM jobstats WHERE system_model IS NOT NULL AND tagnumber = '" . $tagNum . "' ORDER BY time DESC LIMIT 1");
    dbUpdateSystemData("$tagNum", "system_model", $result);

    // system_uuid
    dbSelectVal("SELECT system_uuid AS 'result' FROM jobstats WHERE system_uuid IS NOT NULL AND tagnumber = '" . $tagNum . "' ORDER BY time DESC LIMIT 1");
    dbUpdateSystemData("$tagNum", "system_uuid", $result);

    // system_sku
    dbSelectVal("SELECT system_sku AS 'result' FROM jobstats WHERE system_sku IS NOT NULL AND tagnumber = '" . $tagNum . "' ORDER BY time DESC LIMIT 1");
    dbUpdateSystemData("$tagNum", "system_sku", $result);

    // chassis_type
    dbSelectVal("SELECT chassis_type AS 'result' FROM jobstats WHERE chassis_type IS NOT NULL AND tagnumber = '" . $tagNum . "' ORDER BY time DESC LIMIT 1");
    dbUpdateSystemData("$tagNum", "chassis_type", $result);

    // cpu_manufacturer
    dbSelectVal("SELECT cpu_manufacturer AS 'result' FROM jobstats WHERE cpu_manufacturer IS NOT NULL AND tagnumber = '" . $tagNum . "' ORDER BY time DESC LIMIT 1");
    dbUpdateSystemData("$tagNum", "cpu_manufacturer", $result);

    // cpu_model
    dbSelectVal("SELECT cpu_model AS 'result' FROM jobstats WHERE cpu_model IS NOT NULL AND tagnumber = '" . $tagNum . "' ORDER BY time DESC LIMIT 1");
    dbUpdateSystemData("$tagNum", "cpu_model", $result);
    
    // cpu_maxspeed
    dbSelectVal("SELECT cpu_maxspeed AS 'result' FROM jobstats WHERE cpu_maxspeed IS NOT NULL AND tagnumber = '" . $tagNum . "' ORDER BY time DESC LIMIT 1");
    dbUpdateSystemData("$tagNum", "cpu_maxspeed", $result);

    // cpu_cores
    dbSelectVal("SELECT cpu_cores AS 'result' FROM jobstats WHERE cpu_cores IS NOT NULL AND tagnumber = '" . $tagNum . "' ORDER BY time DESC LIMIT 1");
    dbUpdateSystemData("$tagNum", "cpu_cores", $result);

    // cpu_threads
    dbSelectVal("SELECT cpu_threads AS 'result' FROM jobstats WHERE cpu_threads IS NOT NULL AND tagnumber = '" . $tagNum . "' ORDER BY time DESC LIMIT 1");
    dbUpdateSystemData("$tagNum", "cpu_threads", $result);

    // motherboard_manufacturer
    dbSelectVal("SELECT motherboard_manufacturer AS 'result' FROM jobstats WHERE motherboard_manufacturer IS NOT NULL AND tagnumber = '" . $tagNum . "' ORDER BY time DESC LIMIT 1");
    dbUpdateSystemData("$tagNum", "motherboard_manufacturer", $result);

    // motherboard_serial
    dbSelectVal("SELECT motherboard_serial AS 'result' FROM jobstats WHERE motherboard_serial IS NOT NULL AND tagnumber = '" . $tagNum . "' ORDER BY time DESC LIMIT 1");
    dbUpdateSystemData("$tagNum", "motherboard_serial", $result);
}


?>