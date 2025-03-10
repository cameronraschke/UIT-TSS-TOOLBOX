<?php
require('/var/www/html/management/header.php');
require('/var/www/html/management/php/include.php');

$db = new db();
?>

<?php
//POST data
if (isset($_POST["job_queued_tagnumber"])) {
    if (strFilter($_POST["job_queued"]) === 0) {
        $db->updateRemote($_POST["job_queued_tagnumber"], "job_queued", $_POST["job_queued"]);
    }
    unset($_POST["job_queued_form"]);
}

if (isset($_POST['department'])) {
  $uuid = uniqid("location-", true);
  $tagNum = $_GET["tagnumber"];
  $serial = $_POST["serial"];
  $department = $_POST['department'];
  $location = $_POST['location'];
  $status = $_POST["status"];
  $note = $_POST['note'];
  $diskRemoved = $_POST['disk_removed'];

  #Not the same insert statment as client parse code, ether address is DEFAULT here.
  $db->insertJob($uuid);
  $db->updateJob("tagnumber", $tagNum, $uuid);
  $db->updateJob("system_serial", $serial, $uuid);
  $db->updateJob ("date", $date, $uuid);
  $db->updateJob ("time", $time, $uuid);

  // Insert department data
  $db->insertDepartments($time);
  $db->updateDepartments("tagnumber", $tagNum, $time);
  $db->updateDepartments("system_serial", $serial, $time);
  $db->updateDepartments("department", $department, $time);


  $db->Pselect("SELECT erase_completed, clone_completed FROM jobstats WHERE tagnumber = :tagnumber AND (erase_completed = '1' OR clone_completed = '1') ORDER BY time DESC LIMIT 1", array(':tagnumber' => $_GET["tagnumber"]));
  if (arrFilter($db->get()) === 0) {
    foreach ($db->get() as $key => $value2) {
      if ($value2["erase_completed"] === 1 && $value2["clone_completed"] === 1) {
        $osInstalled = 1;
      } elseif ($value2["erase_completed"] === 1 && $value2["clone_completed"] !== 1) {
        $osInstalled = 0;
      } elseif ($value2["erase_completed"] !== 1 && $value2["clone_completed"] === 1) {
        $osInstalled = 1;
      } else {
        $osInstalled = 0;
      }

      $db->Pselect("SELECT MAX(time) FROM locations WHERE tagnumber = :tagnumber", array(':tagnumber' => $_GET["tagnumber"]));
      if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value5) {
          $db->updateLocation("os_installed", $osInstalled, $value5["max_time"]);
        }
      }
    }
  }
    unset($value2);
    unset($value5);

    $db->Pselect("SELECT t1.bios_version, t2.system_model FROM jobstats t1 INNER JOIN system_data t2 ON t1.tagnumber = t2.tagnumber WHERE t1.bios_version IS NOT NULL AND t2.system_model IS NOT NULL AND t1.tagnumber = :tagnumber ORDER BY t1.time DESC LIMIT 1", array(':tagnumber' => $tagNum));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value2) {
            $db->select("SELECT bios_version FROM static_bios_stats WHERE system_model = '" . $value2["system_model"] . "'");
            if (arrFilter($db->get()) == 0) {
                foreach ($db->get() as $key => $value3) {
                    if ($value2["bios_version"] === $value3["bios_version"]) {
                        $biosBool = 1;
                    } else {
                        $biosBool = 0;
                    }
                }
            } else { $biosBool = 0; }
        }
    } else { $biosBool = 0; }
    unset($value2);
    unset($value3);

    $db->insertLocation($time);
    $db->updateLocation("tagnumber", $tagNum, $time);
    $db->updateLocation("system_serial", $serial, $time);
    $db->updateLocation("location", $location, $time);
    $db->updateLocation("status", $status, $time);
    $db->updateLocation("disk_removed", $diskRemoved, $time);
    $db->updateLocation("note", $note, $time);
    if (isset($osInstalled)) {
        $db->updateLocation("os_installed", $osInstalled, $time);
    }
    if (isset($biosBool)) {
        $db->updateLocation("bios_updated", $biosBool, $time);
    }
    unset($biosBool);
    unset($osInstalled);

    unset($_POST);
    header("Location: " . $_SERVER['REQUEST_URI']);
}
unset($_POST);
?>

<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <link rel="stylesheet" href="/jquery/jquery-ui/jquery-ui-1.14.0/jquery-ui.min.css">
        <script src="/jquery/jquery-3.7.1.min.js"></script>
        <script src="/jquery/jquery-ui/jquery-ui-1.14.0/jquery-ui.min.js"></script>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title><?php echo htmlspecialchars($_GET['tagnumber'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . " - UIT Client Mgmt"; ?></title>
        <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
        <style>
        .ui-autocomplete {
            max-height: 100px;
            overflow-y: auto;
            /* prevent horizontal scrollbar */
            overflow-x: hidden;
        }
        </style>
    </head>
    <body onload="fetchHTML()">
    <script>
        $( function() {
            var availableTags = [
            <?php
            if (!isset($_POST['serial'])) {
                $db->select("SELECT tagnumber FROM locations GROUP BY tagnumber");
                if (arrFilter($db->get()) === 0) {
                    foreach ($db->get() as $key => $value) {
                        echo "'" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "',";
                    }
                }
            }
            ?>
            ];
            $( "#tagnumber" ).autocomplete({
                source: availableTags
            });
        } );

        $( function() {
            var availableLocations = [
            <?php
                $db->select("CALL selectLocationAutocomplete()");
                if (arrFilter($db->get()) === 0) {
                    foreach ($db->get() as $key => $value) {
                        echo "'" . $value["location"] . "',";
                    }
                }
            ?>
            ];
            $( "#location" ).autocomplete({
                source: availableLocations
            });
        } );
    </script>
        <div class='menubar'>
            <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
            <p><span style='float: right;'>Logged in as <b><?php echo htmlspecialchars($login_user, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></b>.</span></p>
            <br>
            <p><span style='float: right;'>Not <b><?php echo htmlspecialchars($login_user, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
        </div>

        <div class='pagetitle'><h1>Client Lookup (<?php echo htmlspecialchars($_GET['tagnumber'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?>)</h1></div>
        <div class='pagetitle'><h2>Lookup data for a specific client.</h2></div>
        <div name='curTime' id='curTime' class='pagetitle'><h3>Data on this page was last updated at: <?php $db->select("SELECT DATE_FORMAT(CONCAT(CURDATE(), ' ', CURTIME()), '%b %D %Y, %r') AS 'time_formatted'"); if (arrFilter($db->get()) === 0) { foreach ($db->get() as $key => $sqlUpdatedTime) { echo $sqlUpdatedTime["time_formatted"]; } } ?></h3></div>

        <div class='laptop-images'>
        <?php
            $db->Pselect("SELECT system_model FROM system_data WHERE tagnumber = :tagnumber", array(':tagnumber' => htmlspecialchars_decode($_GET['tagnumber'])));
            if (arrFilter($db->get()) === 0) {
                foreach ($db->get() as $key => $value) {
                    if ($value["system_model"] === "Aspire T3-710") {
                        echo "<img src='/images/aspireT3710.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "HP ProBook 450 G6") {
                        echo "<img src='/images/hpProBook450G6.avif'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 3500") {
                        echo "<img src='/images/Latitude3500.avif'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 3560") {
                        echo "<img src='/images/Latitude3560.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 3590") {
                        echo "<img src='/images/latitude3590.jpeg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 5289") {
                        echo "<img src='/images/latitude5289.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 5480") {
                        echo "<img src='/images/latitude5480.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 5590") {
                        echo "<img src='/images/latitude5590.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 7400") {
                        echo "<img src='/images/latitude7400.avif'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 7430") {
                        echo "<img src='/images/latitude7430.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 7480") {
                        echo "<img src='/images/latitude7480.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 7490") {
                        echo "<img src='/images/latitude7490.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude E6430") {
                        echo "<img src='/images/latitudeE6430.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude E7470") {
                        echo "<img src='/images/latitudeE7470.webp'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "OptiPlex 7000") {
                        echo "<img src='/images/optiplex7000.avif'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "OptiPlex 7460 AIO") {
                        echo "<img src='/images/optiplex7460AIO.avif'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "OptiPlex 780") {
                        echo "<img src='/images/optiplex780.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "OptiPlex 790") {
                        echo "<img src='/images/optiplex790.avif'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "OptiPlex 9010 AIO") {
                        echo "<img src='/images/optiplex9010AIO.webp'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Surface Book") {
                        echo "<img src='/images/surfaceBook.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Surface Pro") {
                        echo "<img src='/images/surfacePro.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Surface Pro 4") {
                        echo "<img src='/images/surfacePro4.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "XPS 15 9560") {
                        echo "<img src='/images/xps15-9560.jpg'>" . PHP_EOL;
                    }
                }
            }
        ?>
        </div>

  <?php
  // Get most client data - main sql query
  unset($sql);
  $sql = "SELECT DATE_FORMAT(t10.time, '%b %D %Y, %r') AS 'location_time_formatted',
  t9.time AS 'jobstatsTime', jobstats.tagnumber, jobstats.system_serial, t1.department, 
  locations.location, IF(locations.status = 1, 'Broken', 'Working') AS 'status', t2.department_readable, 
  t3.note, DATE_FORMAT(t3.time, '%b %D %Y, %r') AS 'note_time_formatted', 
  IF(locations.disk_removed = 1, 'Yes', 'No') AS 'disk_removed', IF(locations.os_installed = 1, 'Yes', 'No') AS 'os_installed',
  jobstats.etheraddress, system_data.wifi_mac, 
  system_data.chassis_type, 
  system_data.system_manufacturer, system_data.system_model, 
  system_data.cpu_model, system_data.cpu_cores, CONCAT(ROUND((system_data.cpu_maxspeed / 1000), 2), ' Ghz') AS 'cpu_maxspeed', system_data.cpu_threads, 
  CONCAT(t8.ram_capacity, ' GB') AS 'ram_capacity', CONCAT(t8.ram_speed, ' MHz') AS 'ram_speed',
  t4.disk_model, t4.disk_size, t4.disk_type,
  t5.identifier, t5.recovery_key, 
  CONCAT(clientstats.battery_health, '%') AS 'battery_health', CONCAT(clientstats.disk_health, '%') AS 'disk_health', 
  CONCAT(clientstats.erase_avgtime, ' mins') AS 'erase_avgtime', CONCAT(clientstats.clone_avgtime, ' mins') AS 'clone_avgtime',
  DATE_FORMAT(remote.present, '%b %D %Y, %r') AS 'remote_time_formatted', remote.status AS 'remote_status', remote.present_bool, 
  remote.kernel_updated, IF (remote.bios_updated = 1 OR (t11.bios_version = static_bios_stats.bios_version), 'Yes', 'No') AS 'bios_updated', 
  t11.bios_version, SEC_TO_TIME(remote.uptime) AS 'uptime_formatted', CONCAT(remote.network_speed, ' mbps') AS 'network_speed',
  CONCAT(t4.disk_writes, ' TBW') AS 'disk_writes', CONCAT(t4.disk_reads, ' TBR') AS 'disk_reads', CONCAT(t4.disk_power_on_hours, ' hrs') AS 'disk_power_on_hours'
FROM jobstats
LEFT JOIN clientstats ON jobstats.tagnumber = clientstats.tagnumber
LEFT JOIN locations ON jobstats.tagnumber = locations.tagnumber
LEFT JOIN system_data ON jobstats.tagnumber = system_data.tagnumber
LEFT JOIN (SELECT tagnumber, department FROM departments WHERE time IN (SELECT MAX(time) FROM departments WHERE tagnumber IS NOT NULL GROUP BY tagnumber)) t1 
  ON jobstats.tagnumber = t1.tagnumber
LEFT JOIN remote ON jobstats.tagnumber = remote.tagnumber
LEFT JOIN (SELECT department, department_readable FROM static_departments) t2
  ON t1.department = t2.department
LEFT JOIN (SELECT tagnumber, time, note FROM locations WHERE time IN (SELECT MAX(time) FROM locations WHERE note IS NOT NULL GROUP BY tagnumber)) t3
  ON jobstats.tagnumber = t3.tagnumber
LEFT JOIN (SELECT tagnumber, disk_model, disk_size, disk_type, disk_writes, disk_reads, disk_power_on_hours FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE disk_type IS NOT NULL AND tagnumber IS NOT NULL GROUP BY tagnumber)) t4 
  ON jobstats.tagnumber = t4.tagnumber
LEFT JOIN (SELECT tagnumber, identifier, recovery_key FROM bitlocker) t5 
  ON jobstats.tagnumber = t5.tagnumber
LEFT JOIN (SELECT tagnumber, ram_capacity, ram_speed FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE ram_capacity IS NOT NULL AND ram_speed IS NOT NULL AND tagnumber IS NOT NULL GROUP BY tagnumber)) t8
  ON jobstats.tagnumber = t8.tagnumber
LEFT JOIN (SELECT tagnumber, bios_version FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE host_connected = 1 GROUP BY tagnumber)) t11
  ON jobstats.tagnumber = t11.tagnumber
LEFT JOIN static_bios_stats ON system_data.system_model = static_bios_stats.system_model
INNER JOIN (SELECT MAX(time) AS 'time' FROM jobstats WHERE tagnumber IS NOT NULL AND system_serial IS NOT NULL GROUP BY tagnumber) t9
  ON jobstats.time = t9.time
INNER JOIN (SELECT MAX(time) AS 'time' FROM locations WHERE tagnumber IS NOT NULL AND system_serial IS NOT NULL GROUP BY tagnumber) t10
  ON locations.time = t10.time
WHERE jobstats.tagnumber IS NOT NULL and jobstats.system_serial IS NOT NULL
  AND jobstats.tagnumber = :tagnumber";

  $sqlArr = array();
  $db->Pselect($sql, array(':tagnumber' => htmlspecialchars_decode($_GET["tagnumber"])));
  if (arrFilter($db->get()) === 0) {
      $sqlArr = $db->get();
  }
?>

        <div>
        <div class="styled-table" style="width: 40%; height: auto; overflow:auto; margin: 1% 1% 5% 1%; float: left;">
            <table>
                <thead>
                    <tr>
                        <th>Queued Job</th>
                        <th>Current Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                        <form name="job_queued_form" method="post">
                            <input type='hidden' id='job_queued_tagnumber' name='job_queued_tagnumber' value='<?php echo htmlspecialchars($_GET["tagnumber"]); ?>'>
                            <select name="job_queued" onchange='this.form.submit()'>
  <?php
  // Get/set current jobs.
  if ($_GET['tagnumber']) {
    $db->Pselect("SELECT tagnumber FROM remote WHERE tagnumber = :tagnumber", array(':tagnumber' => $_GET["tagnumber"]));
    if (arrFilter($db->get()) === 0 ) {
      $db->Pselect("SELECT IF (remote.job_queued IS NOT NULL, remote.job_queued, '') AS 'job_queued',
          IF (remote.job_queued IS NOT NULL, static_job_names.job_readable, 'No Job') AS 'job_queued_formatted'
        FROM remote 
        INNER JOIN static_job_names 
          ON remote.job_queued = static_job_names.job 
        WHERE remote.tagnumber = :tagnumber", array(':tagnumber' => htmlspecialchars_decode($_GET['tagnumber'])));
      if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
          echo "<option name='curJob' id='curJob' value='" . htmlspecialchars($value1["job_queued"]) . "'>" . htmlspecialchars($value1["job_queued_formatted"]) . "</option>";
        }
        unset($value1);
      }
      $db->select("SELECT job, job_readable FROM static_job_names WHERE job_html_bool = 1 ORDER BY job_rank ASC");
      foreach ($db->get() as $key => $value2) {
        echo "<option value='" . htmlspecialchars($value2["job"]) . "'>" . htmlspecialchars($value2["job_readable"]) . "</option>";
      }
      unset($value2);
      unset($value);
    }
  } else {
    echo "<option>ERR: " . htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . " is not in the DB :(</option>";
  }
  ?>
    </select>
  </form>
  </td>
  <td name='curStatus' id='curStatus'>
  <?php
  if (arrFilter($sqlArr) === 0) {
    foreach ($sqlArr as $key => $value) {
      // BIOS and kernel updated (check mark)
      if ($value["present_bool"] === 1 && ($value["kernel_updated"] === 1 && $value["bios_updated"] === "Yes")) {
        echo "Online, no errors <span>&#10004;&#65039;</span> (" . $value["uptime_formatted"] . ")";
      // BIOS and kernel out of date (x)
      } elseif ($value["present_bool"] === 1 && ($value["kernel_updated"] !== 1 && $value["bios_updated"] !== "Yes")) {
        echo "Online, kernel and BIOS out of date <span>&#10060;</span>";
      // BIOS out of date, kernel updated (warning sign)
      } elseif ($value["present_bool"] === 1 && ($value["kernel_updated"] === 1 && $value["bios_updated"] !== "Yes")) {
        echo "Online, please update BIOS <span>&#9888;&#65039;</span> (" . $value["uptime_formatted"] . ")";
      // BIOS updated, kernel out of date (x)
      } elseif ($value["present_bool"] === 1 && ($value["kernel_updated"] !== 1 && $value["bios_updated"] === "Yes")) {
        echo "Online, kernel out of date <span>&#10060;</span>)";
      // Offline (x)
      } elseif ($value["present_bool"] !== 1) {
        echo "Offline <span>&#9940;</span>";
      } else {
        echo "Unknown <span>&#9940;&#65039;</span>";
      }

      if (strFilter($value["remote_status"]) === 0) {
          echo "<p><b>'" . htmlspecialchars($value["remote_status"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "'</b> at " . htmlspecialchars($value["remote_time_formatted"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</p>" . PHP_EOL;
      }
    }
  }
  ?>
  </td>
  </tr>
  </table>
</div>


<div style="width: 40%; float: right;">
<form name="location-form" id="location-form" method="POST">
<?php
if (isset($_GET["tagnumber"])) {
  echo "<div class='location-form'>" . PHP_EOL;
  if (arrFilter($sqlArr) === 0) {
    foreach ($sqlArr as $key => $value) {
      $serial = $value["system_serial"];
      echo "<input type='hidden' name='serial' id='serial' value='" . htmlspecialchars($serial, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "'>";
    }
  }
  // department
  echo "<div><label for='department'>Department</label></div>" . PHP_EOL;
  echo "<div><select name='department' id='department'>" . PHP_EOL;
  if (strFilter($value["department"]) === 1) {
    echo "<option value=''>--Please Select--</option>";
  } else {
    $db->Pselect("SELECT department, department_readable FROM static_departments WHERE NOT department = :department", array(':department' => $value["department"]));
    if (arrFilter($db->get()) === 0) {
      echo "<option value='" . htmlspecialchars($value["department"]) . "'>" . htmlspecialchars($value["department_readable"]) . "</option>";
      foreach ($db->get() as $key => $value1) {
        echo "<option value='" . htmlspecialchars($value1["department"]) . "'>" . htmlspecialchars($value1["department_readable"]) . "</option>";
      }
    }
  }
  echo "</select></div>" . PHP_EOL;
  // location
    if (strFilter($value["location"]) === 0) {
        echo "<div><label for='location'>Location (Last Updated: " . htmlspecialchars($value["location_time_formatted"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . ")</label></div>" . PHP_EOL;
        echo "<div><input type='text' id='location' name='location' value='" . htmlspecialchars($value["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' required style='width: 50%; height: 4%;'></div>" . PHP_EOL;
    } else {
        echo "<div><label for='location'>Location</label></div>" . PHP_EOL;
        echo "<div><input type='text' id='location' name='location' required style='width: 50%; height: 4%;'></div>" . PHP_EOL;
    }

  // Get most recent note
  echo "<div>" . PHP_EOL;
  if (strFilter($value["note"]) === 0) {
    if ($value["status"] === 1) {
      echo "<div><label for='note'>Note (" . $value["note_time_formatted"] . ")</label></div>" . PHP_EOL;
      echo "<textarea id='note' name='note' style='width: 70%;'>" . htmlspecialchars($value["note"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) .  "</textarea>" . PHP_EOL;
    } else {
      if (strFilter($value["note"]) === 0 && strFilter($value["note_time_formatted"]) === 0) {
        echo "<div><label for='note'>Note</label></div>" . PHP_EOL;
        echo "<textarea id='note' name='note' style='width: 70%;' placeholder='(Last note written at " . htmlspecialchars($value["note_time_formatted"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "): ". htmlspecialchars($value["note"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) .  "'></textarea>" . PHP_EOL;
      } else {
        echo "<div><label for='note'>Note</label></div>" . PHP_EOL;
        echo "<textarea id='note' name='note' style='width: 70%;' placeholder='Enter Note...'></textarea>" . PHP_EOL;
      }
    }
  } else {
    echo "<textarea id='note' name='note' style='width: 70%;'></textarea>" . PHP_EOL;
  }

  echo "</div>" . PHP_EOL;

    echo "<div style='width: 60%;'>" . PHP_EOL;
    echo "<div style='float: left;'>" . PHP_EOL;
    echo "<div><label for='disk_removed'>Disk removed?</label></div>" . PHP_EOL;
    echo "<div><select name='disk_removed' id='disk_removed'>" . PHP_EOL;
    if ($value["disk_removed"] === 1) {
      echo "<option value='1'>Yes</option>" . PHP_EOL;
      echo "<option value='0'>No</option>" . PHP_EOL;
    } else {
      echo "<option value='0'>No</option>" . PHP_EOL;
      echo "<option value='1'>Yes</option>" . PHP_EOL;
    }
      echo "</select></div>" . PHP_EOL;
      echo "</div>" . PHP_EOL;
      echo "<div style='float: right;'>" . PHP_EOL;
      echo "<div><label for='status'>Working or Broken?</label></div>" . PHP_EOL;
      echo "<div><select name='status' id='status'>" . PHP_EOL;
      if ($value["status"] === 1) {
        echo "<option value='1'>Broken</option>" . PHP_EOL;
        echo "<option value='0'>Working</option>" . PHP_EOL;
      } else {
        echo "<option value='0'>Working</option>" . PHP_EOL;
        echo "<option value='1'>Broken</option>" . PHP_EOL;
      }
        echo "</select></div>" . PHP_EOL;
        echo "</div>" . PHP_EOL;
        echo "</div>" . PHP_EOL;

        echo "<div style='margin: 3% 0% 0% 0%;' class='page-content'><input type='submit' value='Update Location Data'></div>" . PHP_EOL;

        echo "<div style='margin: 1% 0% 0% 0%;' class='page-content'><a href='locations.php' target='_blank'>Update a different client's location.</a></div>" . PHP_EOL;
        unset($value);
}
?>
            </form>
        </div>
        </div>

        
        <div class='pagetitle'><h3>General Client Info - <u><?php echo htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></u></h3></div>
        <div name='updateDiv1' id='updateDiv1' class='styled-table' style="width: auto; height: auto; overflow:auto; margin: 1% 1% 5% 1%;">
        <table width="100%">
            <thead>
                <tr>
                <th>System Serial</th>
                <th>MAC Address</th>
                <th>Department</th>
                <th>System Manufacturer</th>
                <th>System Model</th>
                <th>BIOS Version</th>
                <th>CPU Model</th>
                <th>Disk Type</th>
                <th>Link Speed</th>
                </tr>
            </thead>
            <tbody>
  <?php
  if (arrFilter($sqlArr) === 0) {
    foreach ($sqlArr as $key => $value) {
      echo "<tr>" . PHP_EOL;
      echo "<td>" . $value['system_serial'] . "</td>" . PHP_EOL;
      echo "<td>";
      // Latitude 7400 does not have ethernet ports, we use the USB ethernet ports for them, but the USB ethernet MAC address is still associated with their tagnumbers.
      if ($value["system_model"] !== "Latitude 7400") {
        if (strFilter($value["wifi_mac"]) === 0 && strFilter($value["etheraddress"]) === 0) {
          echo "<table><tr><td>" . $value["wifi_mac"] . " (Wi-Fi)</td></tr><tr><td>" . $value["etheraddress"] . " (Ethernet)</td></tr></table>" . PHP_EOL;
        } elseif (strFilter($value["wifi_mac"]) === 0 && strFilter($value["etheraddress"]) === 1) {
          echo $value["wifi_mac"] . " (Wi-Fi)";
        } elseif (strFilter($value["wifi_mac"]) === 1 && strFilter($value["etheraddress"]) === 0) {
          echo $value["etheraddress"] . " (Ethernet)";
        }
      } elseif ($value["system_model"] === "Latitude 7400") {
        if (strFilter($value["wifi_mac"]) === 0 && strFilter($value["etheraddress"]) === 0) {
          echo $value["wifi_mac"] . " (Wi-Fi)";
        } elseif (strFilter($value["wifi_mac"]) === 0 && strFilter($value["etheraddress"]) === 1) {
          echo $value["wifi_mac"] . " (Wi-Fi)";
        }
      }
      echo "</td>" . PHP_EOL;
      echo "<td>" . $value['department'] . "</td>" . PHP_EOL;
      echo "<td>" . $value['system_manufacturer'] . "</td>" . PHP_EOL;
      echo "<td>" . $value['system_model'] . "</td>" . PHP_EOL;
      if ($value["bios_updated"] === "Yes") {
      echo "<td>" . $value["bios_version"] . " (Up to date)</td>" . PHP_EOL;
      } else {
      echo "<td>" . $value["bios_version"] . " (Out of date)</td>" . PHP_EOL;
      }
      echo "<td>" . $value['cpu_model'] . "</td>" . PHP_EOL;
      echo "<td>" . $value['disk_type'] . "</td>" . PHP_EOL;
      echo "<td>" . $value['network_speed'] . "</td>" . PHP_EOL;
      echo "</tr>" . PHP_EOL;
    }
  }
  unset($value);
  ?>
</tbody>
</table>
</div>


        <div class='pagetitle'><h3>Other Client Info - <u><?php echo htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></u></h3></div>
        <div class='styled-table' style="width: auto; height: auto; overflow:auto; margin: 1% 1% 5% 1%;">
        <table width="100%">
            <thead>
                <tr>
                <th>Disk Model</th>
                <th>Disk Size</th>
                <th>RAM Capacity</th>
                <th>RAM Speed</th>
                <th>CPU Max Speed</th>
                <th>CPU Cores</th>
                <th>CPU Threads</th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($sqlArr as $key => $value) {
              echo "<tr>" . PHP_EOL;
              echo "<td>" . htmlspecialchars($value["disk_model"]) . "</td>" . PHP_EOL;
              echo "<td>" . htmlspecialchars($value["disk_size"]) . "</td>" . PHP_EOL;
              echo "<td>" . htmlspecialchars($value["ram_capacity"]) . "</td>" . PHP_EOL;
              echo "<td>" . htmlspecialchars($value["ram_speed"]) . "</td>" . PHP_EOL;
              echo "<td>" . htmlspecialchars($value["cpu_maxspeed"]) . "</td>" . PHP_EOL;
              echo "<td>" . htmlspecialchars($value["cpu_cores"]) . "</td>" . PHP_EOL;
              echo "<td>" . htmlspecialchars($value["cpu_threads"]) . "</td>" . PHP_EOL;
              echo "</tr>" . PHP_EOL;
            }
            unset($value);
            ?>
            </tbody>
        </table>
        </div>


        <div class='pagetitle'><h3>Location Info</h3></div>

        <div name='updateDiv2' id='updateDiv2' class='styled-table' style="width: auto; height: auto; overflow:auto; margin: 1% 1% 5% 1%;">
        <table width="100%">
            <thead>
                <tr>
                <th>Time</th>
                <th>Current Location</th>
                <th>Status</th>
                <th>OS Installed</th>
                <th>BIOS Updated</th>
                <th>Disk Removed</th>
                <th>Note</th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($sqlArr as $key => $value) {
              echo "<tr>" . PHP_EOL;
              echo "<td>" . htmlspecialchars($value['location_time_formatted'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
              if (preg_match("/^[a-zA-Z]$/", $value["location"])) { 
                echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars(strtoupper($value["location"]), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b></td>" . PHP_EOL;
              } elseif (preg_match("/^checkout$/", $value["location"])) {
                echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . "Checkout" . "</a></b></td>" . PHP_EOL;
              } else {
                echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b></td>" . PHP_EOL;
              }
              echo "<td>" . htmlspecialchars($value['status'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
              echo "<td>" . htmlspecialchars($value['os_installed'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
              echo "<td>" . htmlspecialchars($value['bios_updated'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
              echo "<td>" . htmlspecialchars($value['disk_removed'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
              echo "<td>" . htmlspecialchars($value['note'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
              echo "</tr>" . PHP_EOL;
            }
            unset($value);
            ?>
            </tbody>
        </table>
        </div>

        <div class='pagetitle'><h3>Client Health</h3></div>
        <div name='updateDiv3' id='updateDiv3' class='styled-table' style="width: auto; overflow:auto; margin: 1% 1% 5% 1%;">
        <table width="100%">
            <thead>
                <tr>
                <th>Erase Avg. Time</th>
                <th>Clone Avg. Time</th>
                <th>Battery Health</th>
                <th>Disk TBW/TBR</th>
                <th>Disk Power on Hours</th>
                <th>Disk Health</th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($sqlArr as $key => $value) {
              echo "<tr>" . PHP_EOL;
              echo "<td>" . htmlspecialchars($value['erase_avgtime'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
              echo "<td>" . htmlspecialchars($value['clone_avgtime'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
              echo "<td>" . htmlspecialchars($value['battery_health'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
              echo "<td>" . htmlspecialchars($value['disk_writes'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "/" . htmlspecialchars($value['disk_reads'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
              echo "<td>" . htmlspecialchars($value['disk_power_on_hours'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
              echo "<td>" . htmlspecialchars($value['disk_health'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
              echo "</tr>" . PHP_EOL;
            }
            unset($value);
            ?>
            </tbody>
        </table>
        </div>


        <?php
            $db->Pselect("SELECT identifier, recovery_key FROM bitlocker WHERE tagnumber = :tagnumber", array(':tagnumber' => htmlspecialchars_decode($_GET["tagnumber"])));
            if (arrFilter($db->get()) === 0) {
                foreach ($db->get() as $key => $value1) {
                    echo "<div class='pagetitle'><h3>Bitlocker Info</h3></div>" . PHP_EOL;
                    echo "<div class='styled-table' style='width: auto; overflow:auto; margin: 1% 1% 5% 1%;'>" . PHP_EOL;
                    echo "<table>" . PHP_EOL;
                    echo "<thead><tr><th>Identifier</th><th>Recovery Key</th></tr></thead>" . PHP_EOL;
                    echo "<tbody><tr><td>" . htmlspecialchars($value1['identifier'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td><td>" . htmlspecialchars($value1['recovery_key'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td></tr></tbody>" . PHP_EOL;
                    echo "</table>" . PHP_EOL;
                    echo "</div>" . PHP_EOL;
                }
            }
            unset($value1);
        ?>

        <div class='pagetitle'><h3>Job Log</h3></div>
        <div name='updateDiv4' id='updateDiv4' class='styled-table' style="width: auto; max-height: 40%; overflow:auto; margin: 1% 1% 5% 1%;">
        <table width="100%">
            <thead>
                <tr>
                <th>Time</th>
                <th>CPU Usage</th>
                <th>Network Usage</th>
                <th>Erase Completed</th>
                <th>Erase Mode</th>
                <th>Erase Time Elapsed</th>
                <th>Clone Completed</th>
                <th>Clone Master</th>
                <th>Clone Time</th>
                <th>BIOS Version</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $db->select("SELECT DATE_FORMAT(time, '%b %D %Y, %r') AS 'time_formatted', CONCAT(cpu_usage, '%') AS 'cpu_usage', CONCAT(network_usage, ' mbps') AS 'network_usage', IF (erase_completed = 1, 'Yes', 'No') AS 'erase_completed', erase_mode, SEC_TO_TIME(erase_time) AS 'erase_time', IF (clone_completed = 1, 'Yes', 'No') AS clone_completed, IF (clone_master = 1, 'Yes', 'No') AS clone_master, SEC_TO_TIME(clone_time) AS 'clone_time', bios_version FROM jobstats WHERE tagnumber = '" . htmlspecialchars_decode($_GET['tagnumber']) . "' AND (erase_completed = '1' OR clone_completed = '1') ORDER BY time DESC");
                if (arrFilter($db->get()) === 0) {
                    foreach ($db->get() as $key => $value1) {
                        echo "<tr>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value1['time_formatted'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value1['cpu_usage'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value1['network_usage'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value1['erase_completed'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value1['erase_mode'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value1['erase_time'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value1['clone_completed'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value1['clone_master'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value1['clone_time'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value1['bios_version'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "</tr>" . PHP_EOL;
                    }
                }
                unset($value1);
                ?>
            </tbody>
        </table>
        </div>

        <div class='pagetitle'><h3>Location Log</h3></div>
        <div name='updateDiv5' id='updateDiv5' class='styled-table' style="width: auto; max-height: 40%; overflow:auto; margin: 1% 1% 5% 1%;">
        <table width="100%">
            <thead>
                <tr>
                <th>Timestamp</th>
                <th>Location</th>
                <th>Status</th>
                <th>OS Installed</th>
                <th>BIOS Updated</th>
                <th>Disk Removed</th>
                <th>Note</th>
                <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $db->Pselect("SELECT * FROM (SELECT time, DATE_FORMAT(time, '%b %D %Y, %r') AS 'time_formatted', location, ROW_NUMBER() OVER (PARTITION BY location ORDER BY time DESC) AS 'location_num', IF (status = 1, 'Broken', 'Working') AS 'status', IF (os_installed = 1, 'Yes', 'No') AS 'os_installed', IF (bios_updated = 1, 'Yes', 'No') AS 'bios_updated', IF (disk_removed = 1, 'Yes', 'No') AS 'disk_removed', note FROM locations WHERE tagnumber = :tagnumber AND NOT location = 'Plugged in and booted on laptop table.' AND NOT location = 'Finished work on laptop table.' ORDER BY time DESC) t2 WHERE t2.location IS NOT NULL and t2.location_num <= 3 ORDER BY t2.time DESC", array(':tagnumber' => htmlspecialchars_decode($_GET["tagnumber"])));
                if (arrFilter($db->get()) === 0) {
                    foreach ($db->get() as $key => $value1) {
                        echo "<tr>" . PHP_EOL;
                        //Time formatted
                        echo "<td>" . htmlspecialchars($value1['time_formatted'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        //Location formatted. Single letters to uppercase, checkout regex matching to just "Checkout"
                        if (preg_match("/^[a-zA-Z]$/", $value1["location"])) { 
                            echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value1["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars(strtoupper($value1["location"]), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b></td>" . PHP_EOL;
                        } elseif (preg_match("/^checkout$/i", $value1["location"])) {
                            echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value1["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . "Checkout" . "</a></b></td>" . PHP_EOL;
                        } else {
                            echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value1["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value1["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b></td>" . PHP_EOL;
                        }
                        echo "<td>" . htmlspecialchars($value1['status'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value1['os_installed'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value1['bios_updated'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value1['disk_removed'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value1['note'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "</tr>" . PHP_EOL;
                    }
                }
                unset($value1);
                ?>
            </tbody>
        </table>
        </div>

        <script>
            if ( window.history.replaceState ) {
                window.history.replaceState( null, null, window.location.href );
            }

            var i = 0;
            function fetchHTML() {
                const var1 = setTimeout(function() {
                fetch('/tagnumber.php?tagnumber=<?php echo $_GET['tagnumber']; ?>')
                .then((response) => {
                        return response.text();
                })
                .then((html) => {
                    //document.body.innerHTML = html
                    const parser = new DOMParser()
                    const doc = parser.parseFromString(html, "text/html")
                    // update current time
                    const curTime = doc.getElementById('curTime').innerHTML
                    document.getElementById("curTime").innerHTML = curTime
                    // update current job in dropdown menu
                    const curJob = doc.getElementById('curJob').innerHTML
                    document.getElementById("curJob").innerHTML = curJob
                    // update current status
                    const curStatus = doc.getElementById('curStatus').innerHTML
                    document.getElementById("curStatus").innerHTML = curStatus
                    // update all other tables
                    const updateDiv1 = doc.getElementById('updateDiv1').innerHTML
                    document.getElementById("updateDiv1").innerHTML = updateDiv1
                    const updateDiv2 = doc.getElementById('updateDiv2').innerHTML
                    document.getElementById("updateDiv2").innerHTML = updateDiv2
                    const updateDiv3 = doc.getElementById('updateDiv3').innerHTML
                    document.getElementById("updateDiv3").innerHTML = updateDiv3
                    const updateDiv4 = doc.getElementById('updateDiv4').innerHTML
                    document.getElementById("updateDiv4").innerHTML = updateDiv4
                    const updateDiv5 = doc.getElementById('updateDiv5').innerHTML
                    document.getElementById("updateDiv5").innerHTML = updateDiv5
                    const location = doc.getElementById('location').innerHTML
                    document.getElementById("location").innerHTML = location
                    const note = doc.getElementById('note').innerHTML
                    document.getElementById("note").innerHTML = note
                });
                fetchHTML();
            }, 3000)}
        </script>

    <div class="uit-footer">
        <img src="/images/uh-footer.svg">
    </div>
    </body>
</html>