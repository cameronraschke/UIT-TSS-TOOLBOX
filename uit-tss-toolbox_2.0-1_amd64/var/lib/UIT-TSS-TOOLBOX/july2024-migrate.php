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

include('/var/www/html/management/php/include.php');

$db = new db();

$db->select("SELECT tagnumber FROM jobstats WHERE tagnumber IS NOT NULL GROUP BY tagnumber");
foreach ($db->get() as $key=>$value) {

    // Check if tag already exists, if not then insert it
    $db->Pselect("SELECT tagnumber FROM system_data WHERE tagnumber = :tagnumber", array(':tagnumber' => $value["tagnumber"]));
    if (is_array($db->get()) === FALSE) {
        $db->insertSystemData($value["tagnumber"]);
    }

    // wifi_mac
    $db->Pselect("SELECT wifi_mac FROM jobstats WHERE wifi_mac IS NOT NULL AND tagnumber = :tagnumber ORDER BY time DESC LIMIT 1", array(':tagnumber' => $value["tagnumber"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->updateSystemData($value["tagnumber"], "wifi_mac", $value1["wifi_mac"]);
        }
    }
    unset($value1);

    // system_manufacturer
    $db->Pselect("SELECT system_manufacturer FROM jobstats WHERE system_manufacturer IS NOT NULL AND tagnumber = :tagnumber ORDER BY time DESC LIMIT 1", array(':tagnumber' => $value["tagnumber"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->updateSystemData($value["tagnumber"], "system_manufacturer", $value1["system_manufacturer"]);
        }
    }
    unset($value1);

    // system_model
    $db->Pselect("SELECT system_model FROM jobstats WHERE system_model IS NOT NULL AND tagnumber = :tagnumber ORDER BY time DESC LIMIT 1", array(':tagnumber' => $value["tagnumber"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->updateSystemData($value["tagnumber"], "system_model", $value1["system_model"]);
        }
    }
    unset($value1);

    // system_uuid
    $db->Pselect("SELECT system_uuid FROM jobstats WHERE system_uuid IS NOT NULL AND tagnumber = :tagnumber ORDER BY time DESC LIMIT 1", array(':tagnumber' => $value["tagnumber"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->updateSystemData($value["tagnumber"], "system_uuid", $value1["system_uuid"]);
        }
    }
    unset($value1);

    // system_sku
    $db->Pselect("SELECT system_sku FROM jobstats WHERE system_sku IS NOT NULL AND tagnumber = :tagnumber ORDER BY time DESC LIMIT 1", array(':tagnumber' => $value["tagnumber"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->updateSystemData($value["tagnumber"], "system_sku", $value1["system_sku"]);
        }
    }
    unset($value1);

    // chassis_type
    $db->Pselect("SELECT chassis_type FROM jobstats WHERE chassis_type IS NOT NULL AND tagnumber = :tagnumber ORDER BY time DESC LIMIT 1", array(':tagnumber' => $value["tagnumber"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->updateSystemData($value["tagnumber"], "chassis_type", $value1["chassis_type"]);
        }
    }
    unset($value1);

    // cpu_manufacturer
    $db->Pselect("SELECT cpu_manufacturer FROM jobstats WHERE cpu_manufacturer IS NOT NULL AND tagnumber = :tagnumber ORDER BY time DESC LIMIT 1", array(':tagnumber' => $value["tagnumber"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->updateSystemData($value["tagnumber"], "cpu_manufacturer", $value1["cpu_manufacturer"]);
        }
    }
    unset($value1);

    // cpu_model
    $db->Pselect("SELECT cpu_model FROM jobstats WHERE cpu_model IS NOT NULL AND tagnumber = :tagnumber ORDER BY time DESC LIMIT 1", array(':tagnumber' => $value["tagnumber"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->updateSystemData($value["tagnumber"], "cpu_model", $value1["cpu_model"]);
        }
    }
    unset($value1);
    
    // cpu_maxspeed
    $db->Pselect("SELECT cpu_maxspeed FROM jobstats WHERE cpu_maxspeed IS NOT NULL AND tagnumber = :tagnumber ORDER BY time DESC LIMIT 1", array(':tagnumber' => $value["tagnumber"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->updateSystemData($value["tagnumber"], "cpu_maxspeed", $value1["cpu_maxspeed"]);
        }
    }
    unset($value1);

    // cpu_cores
    $db->Pselect("SELECT cpu_cores FROM jobstats WHERE cpu_cores IS NOT NULL AND tagnumber = :tagnumber ORDER BY time DESC LIMIT 1", array(':tagnumber' => $value["tagnumber"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->updateSystemData($value["tagnumber"], "cpu_cores", $value1["cpu_cores"]);
        }
    }
    unset($value1);

    // cpu_threads
    $db->Pselect("SELECT cpu_threads FROM jobstats WHERE cpu_threads IS NOT NULL AND tagnumber = :tagnumber ORDER BY time DESC LIMIT 1", array(':tagnumber' => $value["tagnumber"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->updateSystemData($value["tagnumber"], "cpu_threads", $value1["cpu_threads"]);
        }
    }
    unset($value1);

    // motherboard_manufacturer
    $db->Pselect("SELECT motherboard_manufacturer FROM jobstats WHERE motherboard_manufacturer IS NOT NULL AND tagnumber = :tagnumber ORDER BY time DESC LIMIT 1", array(':tagnumber' => $value["tagnumber"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->updateSystemData($value["tagnumber"], "motherboard_manufacturer", $value1["motherboard_manufacturer"]);
        }
    }
    unset($value1);

    // motherboard_serial
    $db->Pselect("SELECT motherboard_serial FROM jobstats WHERE motherboard_serial IS NOT NULL AND tagnumber = :tagnumber ORDER BY time DESC LIMIT 1", array(':tagnumber' => $value["tagnumber"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->updateSystemData($value["tagnumber"], "motherboard_serial", $value1["motherboard_serial"]);
        }
    }
    unset($value1);
}


?>