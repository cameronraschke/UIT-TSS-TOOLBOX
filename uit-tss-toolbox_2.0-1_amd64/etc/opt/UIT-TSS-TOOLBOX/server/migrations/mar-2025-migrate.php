#!/bin/php
<?php
// update image name on most recent entries
require('/var/www/html/management/php/include.php');

$db = new db();

$sql = "SELECT 
    jobstats.time, jobstats.tagnumber, jobstats.uuid,
    system_data.system_model, static_image_names.image_name, static_image_names.image_name_readable 
    FROM jobstats 
    INNER JOIN system_data ON jobstats.tagnumber = system_data.tagnumber
    INNER JOIN static_image_names ON system_data.system_model = static_image_names.image_platform_model
    WHERE jobstats.time 
            IN (SELECT MAX(jobstats.time) FROM jobstats WHERE jobstats.clone_completed = 1 GROUP BY jobstats.tagnumber) 
        AND jobstats.tagnumber IS NOT NULL 
        AND NOT static_image_names.image_name = 'Ubuntu-Desktop' 
    ORDER BY jobstats.time"

$db->select($sql);
foreach ($db->get() as $key => $value) {
    $db->updateJob("clone_image", $value["image_name"], $value["uuid"]);
}


?>