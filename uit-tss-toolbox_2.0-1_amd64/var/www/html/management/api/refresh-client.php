<?php
require('/var/www/html/management/php/include.php');
$db = new db();

if ($_GET["password"] !== "CLIENT_PASSWD") {
  exit();
}

if (strFilter($_GET["tagnumber"]) === 1) {
  exit();
}

unset($sql);
$sql = "SELECT tagnumber, client_health_tag, remote_tag, present_bool, last_job_time, disk_temp, max_disk_temp, system_serial, bios_version, bios_updated, image_name_readable, os_installed,
   checkout_time, checkout_bool, image_time FROM 
    (SELECT locations.tagnumber, locations.system_serial, client_health.tagnumber AS 'client_health_tag', remote.tagnumber AS 'remote_tag', 
    IF(TIME_TO_SEC(TIMEDIFF(NOW(), remote.present)) < 30, 1, 0) AS 'present_bool', t2.time AS 'last_job_time', remote.disk_temp, remote.max_disk_temp, 
    (CASE 
      WHEN locations.disk_removed = 1 THEN 'No OS'
      WHEN t2.clone_completed IS NULL AND t2.erase_completed = 1 THEN 'No OS'
      WHEN t2.clone_completed = 1 THEN static_image_names.image_name_readable
      ELSE 'Unknown OS'
    END) AS 'image_name_readable', 
    (CASE 
      WHEN locations.disk_removed = 1 THEN 0
      WHEN t4.checkout_bool = 1 THEN 1
      WHEN t2.erase_completed = 1 AND t2.clone_completed IS NULL THEN 0
      WHEN t2.erase_completed IS NULL AND t2.clone_completed = 1 THEN 1
      WHEN t2.erase_completed = 1 AND t2.clone_completed = 1 THEN 1 
      ELSE 1 
    END) AS 'os_installed',
    t1.time AS 'image_time',
    IF (t3.bios_version = static_bios_stats.bios_version, 1, 0) AS 'bios_updated',
    t3.bios_version, 
    t4.time AS 'checkout_time',
    (CASE 
      WHEN t4.checkout_date IS NOT NULL AND t4.checkout_date > DATE(NOW()) THEN 0
      WHEN t4.checkout_date IS NOT NULL AND t4.checkout_date <= DATE(NOW()) AND (t4.return_date IS NULL OR DATE(NOW()) < t4.return_date) THEN 1
      WHEN t4.checkout_date IS NOT NULL AND t4.checkout_date <= DATE(NOW()) AND (t4.return_date IS NULL OR DATE(NOW()) >= t4.return_date) THEN 0
      ELSE 0 
    END) AS 'checkout_bool', 
    ROW_NUMBER() OVER (PARTITION BY locations.tagnumber ORDER BY locations.time DESC) AS 'row_nums'
    FROM locations
    LEFT JOIN system_data ON locations.tagnumber = system_data.tagnumber
    LEFT JOIN client_health ON locations.tagnumber = client_health.tagnumber
    LEFT JOIN remote ON locations.tagnumber = remote.tagnumber
    LEFT JOIN static_bios_stats ON system_data.system_model = static_bios_stats.system_model
    LEFT JOIN (SELECT time, tagnumber, clone_image, row_nums FROM (SELECT time, tagnumber, clone_image, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'row_nums' FROM jobstats WHERE tagnumber IS NOT NULL AND clone_completed = 1 AND clone_image IS NOT NULL) s1 WHERE s1.row_nums = 1) t1
      ON locations.tagnumber = t1.tagnumber
    LEFT JOIN static_image_names ON t1.clone_image = static_image_names.image_name
    LEFT JOIN (SELECT time, tagnumber, erase_completed, clone_completed FROM (SELECT time, tagnumber, erase_completed, clone_completed, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'row_nums' FROM jobstats WHERE tagnumber IS NOT NULL AND (erase_completed = 1 OR clone_completed = 1)) s2 WHERE s2.row_nums = 1) t2
      ON locations.tagnumber = t2.tagnumber
    LEFT JOIN (SELECT tagnumber, bios_version, row_nums FROM (SELECT tagnumber, bios_version, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'row_nums' FROM jobstats WHERE tagnumber IS NOT NULL AND bios_version IS NOT NULL) s3 WHERE s3.row_nums = 1) t3
      ON locations.tagnumber = t3.tagnumber
    LEFT JOIN (SELECT tagnumber, time, checkout_bool, checkout_date, return_date FROM (SELECT tagnumber, time, checkout_bool, checkout_date, return_date, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'row_nums' FROM checkouts) s4 WHERE s4.row_nums = 1) t4
      ON locations.tagnumber = t4.tagnumber
    WHERE locations.tagnumber IS NOT NULL) table1
    WHERE table1.row_nums = 1 
    ";

if ($_GET["tagnumber"] === "refresh-all") {
  $db->select($sql);
} elseif (preg_match('/^[0-9]{6}$/', $_GET["tagnumber"]) === 1) {
  $sql .= "AND table1.tagnumber = :tagnumber ";
  $db->Pselect($sql, array(':tagnumber' => htmlspecialchars($_GET["tagnumber"])));
} else {
  http_response_code(500);
  exit();
}

foreach ($db->get() as $key => $value) {
  if (strFilter($value["client_health_tag"]) === 1) {
    $db->insertClientHealth($value["tagnumber"]);
  }

  $db->updateClientHealth($value["tagnumber"], "system_serial",  $value["system_serial"]);
  $db->updateClientHealth($value["tagnumber"], "bios_version",  $value["bios_version"]);
  $db->updateClientHealth($value["tagnumber"], "bios_updated",  $value["bios_updated"]);
  $db->updateClientHealth($value["tagnumber"], "os_name", $value["image_name_readable"]);
  $db->updateClientHealth($value["tagnumber"], "os_installed", $value["os_installed"]);
  $db->updateClientHealth($value["tagnumber"], "time", $time);

  $db->updateCheckout("checkout_bool", $value["checkout_bool"], $value["checkout_time"]);

  if (strFilter($value["remote_tag"]) === 1) {
    $db->insertRemote($value["tagnumber"]);
  }

  // Update presence
  $db->updateRemote($value["tagnumber"], "present_bool", $value["present_bool"]);

  // Update Last Job Time
  $db->updateRemote($value["tagnumber"], "last_job_time", $value["last_job_time"]);

  // Disk Temp
  if ($value["disk_temp"] >= $value["max_disk_temp"]) {
    $db->updateRemote($value["tagnumber"], "status", "fail - high disk temp");
    $db->updateRemote($value["tagnumber"], "job_queued", "shutdown");
  }
}
unset($value);