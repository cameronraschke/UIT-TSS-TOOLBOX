<?php
require('/var/www/html/uit-web/php/include.php');

$dbPSQL = new dbPSQL();

if ($_GET["password"] !== "CLIENT_PASSWD") {
  exit();
}

if (strFilter($_GET["tagnumber"]) === 1) {
  exit();
}


unset($sql);
$sql = "SELECT tagnumber, client_health_tag, remote_tag, present_bool, last_job_time, disk_temp, max_disk_temp, system_serial, bios_version, bios_updated, CAST(image_name_readable AS VARCHAR(36)) AS image_name_readable, os_installed,
   checkout_time, checkout_bool, image_time, disk_type, disk_health, battery_health, avg_erase_time, avg_clone_time, all_jobs FROM 
    (SELECT locations.tagnumber, locations.system_serial, client_health.tagnumber AS client_health_tag, remote.tagnumber AS remote_tag,  
    static_disk_stats.disk_type, t3.avg_erase_time, t3.avg_clone_time, 
    (CASE WHEN ROUND((EXTRACT(EPOCH FROM (NOW()::timestamp - remote.present::timestamp))), 0) < 30 THEN TRUE ELSE FALSE END) AS present_bool, t2.time AS last_job_time, remote.disk_temp, remote.max_disk_temp, 
    (CASE 
      WHEN locations.disk_removed = TRUE THEN 'No OS'
      WHEN t2.clone_completed IS FALSE AND t2.erase_completed = TRUE THEN 'No OS'
      WHEN t2.clone_completed = TRUE THEN static_image_names.image_name_readable
      ELSE 'Unknown OS'
    END) AS image_name_readable, 
    (CASE
      WHEN static_disk_stats.disk_type = 'hdd' AND static_disk_stats.disk_tbw IS NOT NULL AND static_disk_stats.disk_mtbf IS NOT NULL THEN ROUND(100 - (((((jobstats.disk_reads + jobstats.disk_writes) / static_disk_stats.disk_tbw) + (jobstats.disk_power_on_hours / static_disk_stats.disk_mtbf)) / 2) * 100), 3)
      WHEN static_disk_stats.disk_type = 'hdd' AND static_disk_stats.disk_tbw IS NOT NULL AND static_disk_stats.disk_mtbf IS NULL THEN ROUND(100 - (((jobstats.disk_reads + jobstats.disk_writes) / static_disk_stats.disk_tbw) * 100), 3)
      WHEN static_disk_stats.disk_type = 'hdd' AND static_disk_stats.disk_tbw IS NULL AND static_disk_stats.disk_mtbf IS NOT NULL THEN ROUND(100 - ((jobstats.disk_power_on_hours / static_disk_stats.disk_mtbf) * 100), 3)
      WHEN (static_disk_stats.disk_type = 'ssd' OR static_disk_stats.disk_type = 'nvme') THEN ROUND(100 - (((((jobstats.disk_writes / static_disk_stats.disk_tbw) + (jobstats.disk_power_on_hours / static_disk_stats.disk_mtbf)) / 2) * 100)), 3)
      ELSE NULL
    END
    ) AS disk_health,
    (CASE
      WHEN jobstats.battery_health IS NOT NULL AND jobstats.battery_charge_cycles IS NOT NULL THEN ROUND(((jobstats.battery_health + (100 - ((jobstats.battery_charge_cycles / static_battery_stats.battery_charge_cycles) * 100))) / 2), 3)
      WHEN jobstats.battery_health IS NOT NULL AND jobstats.battery_charge_cycles IS NULL THEN ROUND((jobstats.battery_health), 3)
      WHEN jobstats.battery_health IS NULL AND jobstats.battery_charge_cycles IS NOT NULL THEN ROUND((100 - (((jobstats.battery_charge_cycles / static_battery_stats.battery_charge_cycles) * 100))), 3)
      ELSE NULL
    END
    ) AS battery_health, 
    (CASE 
      WHEN locations.disk_removed = TRUE THEN FALSE
      WHEN t4.checkout_bool = TRUE THEN TRUE
      WHEN t2.erase_completed = TRUE AND t2.clone_completed = FALSE THEN FALSE
      WHEN t2.erase_completed = FALSE AND t2.clone_completed = TRUE THEN TRUE
      WHEN t2.erase_completed = TRUE AND t2.clone_completed = TRUE THEN TRUE 
      ELSE TRUE
    END) AS os_installed,
    t1.time AS image_time,
    (CASE WHEN jobstats.bios_version = static_bios_stats.bios_version THEN TRUE ELSE FALSE END) AS bios_updated,
    jobstats.bios_version, 
    t4.time AS checkout_time,
    (CASE 
      WHEN t4.checkout_date IS NOT NULL AND t4.checkout_date > DATE(NOW()) THEN FALSE
      WHEN t4.checkout_date IS NOT NULL AND t4.checkout_date <= DATE(NOW()) AND (t4.return_date IS NULL OR DATE(NOW()) < t4.return_date) THEN TRUE
      WHEN t4.checkout_date IS NOT NULL AND t4.checkout_date <= DATE(NOW()) AND (t4.return_date IS NULL OR DATE(NOW()) >= t4.return_date) THEN FALSE
      ELSE FALSE
      END) AS checkout_bool, 
    t5.all_jobs, 
    ROW_NUMBER() OVER (PARTITION BY locations.tagnumber ORDER BY locations.time DESC) AS row_nums
    FROM locations
    LEFT JOIN jobstats ON locations.tagnumber = jobstats.tagnumber AND jobstats.time IN (SELECT time FROM (SELECT time, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM jobstats WHERE jobstats.time IS NOT NULL) s1 WHERE s1.row_nums = 1)
    LEFT JOIN static_disk_stats ON jobstats.disk_model = static_disk_stats.disk_model
    LEFT JOIN static_battery_stats ON jobstats.battery_model = static_battery_stats.battery_model
    LEFT JOIN system_data ON locations.tagnumber = system_data.tagnumber
    LEFT JOIN client_health ON locations.tagnumber = client_health.tagnumber
    LEFT JOIN remote ON locations.tagnumber = remote.tagnumber
    LEFT JOIN static_bios_stats ON system_data.system_model = static_bios_stats.system_model
    LEFT JOIN (SELECT time, tagnumber, clone_image, row_nums FROM (SELECT time, tagnumber, clone_image, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM jobstats WHERE tagnumber IS NOT NULL AND clone_completed = TRUE AND clone_image IS NOT NULL AND jobstats.time IS NOT NULL) s1 WHERE s1.row_nums = 1) t1
      ON locations.tagnumber = t1.tagnumber
    LEFT JOIN static_image_names ON t1.clone_image = static_image_names.image_name
    LEFT JOIN (SELECT time, tagnumber, erase_completed, clone_completed FROM (SELECT time, tagnumber, erase_completed, clone_completed, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM jobstats WHERE tagnumber IS NOT NULL AND (erase_completed = TRUE OR clone_completed = TRUE) AND jobstats.time IS NOT NULL) s2 WHERE s2.row_nums = 1) t2
      ON locations.tagnumber = t2.tagnumber
    LEFT JOIN (SELECT tagnumber, CAST(ROUND(AVG(erase_time / 60), 0) AS SMALLINT) AS avg_erase_time, CAST(ROUND(AVG(clone_time / 60), 0) AS SMALLINT) AS avg_clone_time FROM (SELECT tagnumber, clone_time, erase_time, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM jobstats WHERE (erase_completed = TRUE OR clone_completed = TRUE) AND jobstats.time IS NOT NULL) s3 WHERE s3.row_nums <= 3 GROUP BY s3.tagnumber) t3
      ON locations.tagnumber = t3.tagnumber
    LEFT JOIN (SELECT tagnumber, time, checkout_bool, checkout_date, return_date FROM (SELECT tagnumber, time, checkout_bool, checkout_date, return_date, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM checkouts) s4 WHERE s4.row_nums = 1) t4
      ON locations.tagnumber = t4.tagnumber
    LEFT JOIN (SELECT tagnumber, SUM((CASE WHEN erase_completed = TRUE THEN 1 ELSE 0 END) + (CASE WHEN clone_completed = TRUE THEN 1 ELSE 0 END)) AS all_jobs FROM jobstats WHERE (erase_completed = TRUE OR clone_completed = TRUE) GROUP BY tagnumber) t5
      ON locations.tagnumber = t5.tagnumber
    WHERE locations.tagnumber IS NOT NULL AND locations.time IS NOT NULL AND locations.department NOT IN ('property')
    ) table1
    WHERE table1.row_nums = 1
    ";

if ($_GET["tagnumber"] === "refresh-all") {
  $dbPSQL->select($sql);
} elseif (preg_match('/^[0-9]{6}$/', $_GET["tagnumber"]) === 1) {
  $sql .= "AND table1.tagnumber = :tagnumber ";
  $dbPSQL->Pselect($sql, array(':tagnumber' => htmlspecialchars($_GET["tagnumber"])));
} else {
  http_response_code(500);
  exit();
}

foreach ($dbPSQL->get() as $key => $value) {
  if (strFilter($value["client_health_tag"]) === 1) {
    $dbPSQL->insertClientHealth($value["tagnumber"]);
  }

  $dbPSQL->updateClientHealth($value["tagnumber"], "system_serial",  $value["system_serial"]);
  $dbPSQL->updateClientHealth($value["tagnumber"], "bios_version",  $value["bios_version"]);
  $dbPSQL->updateClientHealth($value["tagnumber"], "bios_updated",  boolval($value["bios_updated"]));
  $dbPSQL->updateClientHealth($value["tagnumber"], "os_name", $value["image_name_readable"]);
  $dbPSQL->updateClientHealth($value["tagnumber"], "os_installed", boolval($value["os_installed"]));
  $dbPSQL->updateClientHealth($value["tagnumber"], "disk_type", $value["disk_type"]);
  $dbPSQL->updateClientHealth($value["tagnumber"], "disk_health", $value["disk_health"]);
  $dbPSQL->updateClientHealth($value["tagnumber"], "battery_health", $value["battery_health"]);
  $dbPSQL->updateClientHealth($value["tagnumber"], "avg_erase_time", $value["avg_erase_time"]);
  $dbPSQL->updateClientHealth($value["tagnumber"], "avg_clone_time", $value["avg_clone_time"]);
  $dbPSQL->updateClientHealth($value["tagnumber"], "all_jobs", $value["all_jobs"]);
  $dbPSQL->updateClientHealth($value["tagnumber"], "time", $time);

  $dbPSQL->updateCheckout("checkout_bool", boolval($value["checkout_bool"]), $value["checkout_time"]);

  if (strFilter($value["remote_tag"]) === 1) {
    $dbPSQL->insertRemote($value["tagnumber"]);
  }

  // Update presence
  $dbPSQL->updateRemote($value["tagnumber"], "present_bool", boolval($value["present_bool"]));

  // Update Last Job Time
  $dbPSQL->updateRemote($value["tagnumber"], "last_job_time", $value["last_job_time"]);

  // Disk Temp
  if (strFilter($value["max_disk_temp"]) === 0 && $value["disk_temp"] >= $value["max_disk_temp"]) {
    $dbPSQL->updateRemote($value["tagnumber"], "status", "fail - high disk temp");
    $dbPSQL->updateRemote($value["tagnumber"], "job_queued", "shutdown");
  }
}
unset($value);