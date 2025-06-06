<?php
require('/var/www/html/management/header.php');
require('/var/www/html/management/php/include.php');

session_start();
if ($_SESSION['authorized'] != "yes") {
  die();
}

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

if (strFilter($_POST) === 0) {
  if (strFilter($_POST['department']) === 0 && strFilter($_GET["tagnumber"]) === 0 && strFilter($_POST["serial"]) === 0) {
    $uuid = uniqid("location-", true);
    $tagNum = trim($_GET["tagnumber"]);
    $serial = trim($_POST["serial"]);
    $department = $_POST['department'];
    $location = trim($_POST['location']);
    $status = $_POST["status"];
    $note = $_POST['note'];
    $diskRemoved = $_POST['disk_removed'];
    $domain = $_POST["domain"];

    //Insert jobstats data
    //$db->insertJob($uuid);
    //$db->updateJob("tagnumber", $tagNum, $uuid);
    //$db->updateJob("system_serial", $serial, $uuid);
    //$db->updateJob ("date", $date, $uuid);
    //$db->updateJob ("time", $time, $uuid);

    // Insert department data
    $db->insertDepartments($time);
    $db->updateDepartments("tagnumber", $tagNum, $time);
    $db->updateDepartments("system_serial", $serial, $time);
    $db->updateDepartments("department", $department, $time);

    //Insert location data
    $db->insertLocation($time);
    $db->updateLocation("tagnumber", $tagNum, $time);
    $db->updateLocation("system_serial", $serial, $time);
    $db->updateLocation("location", $location, $time);
    $db->updateLocation("status", $status, $time);
    $db->updateLocation("disk_removed", $diskRemoved, $time);
    $db->updateLocation("note", $note, $time);
    $db->updateLocation("domain", $domain, $time);


    unset($_POST);
    header("Location: " . $_SERVER['REQUEST_URI']);
  } else {
    if (strFilter($_POST["job_queued"]) === 1 || strFilter($_POST["job_queued_tagnumber"]) === 1) {
      $formErr = "<div style='color: red;'><b>Missing required data, please go to <a href='/locations.php'>here</a> to update the client</b></div>";
    }
  }
}
unset($_POST);
?>

<html>
  <head>
    <meta charset='UTF-8'>
    <link rel='stylesheet' type='text/css' href='/css/main.css' />
    <title><?php echo htmlspecialchars($_GET['tagnumber'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . " - UIT Client Mgmt"; ?></title>
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    <script src="/js/include.js"></script>
  </head>
  <body onload="fetchHTML()">

  <div class='menubar'>
  <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
  <p><span style='float: right;'>Logged in as <b><?php echo htmlspecialchars($login_user, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></b>.</span></p>
  <br>
  <p><span style='float: right;'>Not <b><?php echo htmlspecialchars($login_user, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
  </div>

  <div class='pagetitle'><h1>Client Lookup (<?php echo htmlspecialchars($_GET['tagnumber'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?>)</h1></div>


<?php
// Get most client data - main sql query
unset($sql);
$sql = "SELECT DATE_FORMAT(t10.time, '%m/%d/%y, %r') AS 'location_time_formatted',
IF (t3.time = t10.time, 1, 0) AS 'placeholder_bool',
jobstats.time AS 'jobstatsTime', locations.tagnumber, locations.system_serial, t1.department, 
locationFormatting(locations.location) AS 'location', 
IF(locations.status = 1, 'Broken', 'Yes') AS 'status_formatted', locations.status AS 'locations_status', t2.department_readable, t3.note AS 'most_recent_note',
locations.note, DATE_FORMAT(t3.time, '%m/%d/%y, %r') AS 'note_time_formatted', 
IF(locations.disk_removed = 1, 'Yes', 'No') AS 'disk_removed_formatted', locations.disk_removed,
jobstats.etheraddress, system_data.wifi_mac, 
system_data.chassis_type, 
(CASE
WHEN system_data.system_manufacturer IS NOT NULL AND system_data.system_model IS NOT NULL THEN CONCAT(system_data.system_manufacturer, ' - ', system_data.system_model)
WHEN system_data.system_manufacturer IS NULL AND system_data.system_model IS NOT NULL THEN system_data.system_model
WHEN system_data.system_manufacturer IS NOT NULL AND system_data.system_model IS NULL THEN system_data.system_manufacturer
END) AS 'system_model_formatted',
system_data.cpu_model, system_data.cpu_cores, CONCAT(ROUND((system_data.cpu_maxspeed / 1000), 2), ' Ghz') AS 'cpu_maxspeed', IF(system_data.cpu_threads > system_data.cpu_cores, CONCAT(system_data.cpu_cores, '/', system_data.cpu_threads, ' (Multithreaded)'), system_data.cpu_cores) AS 'multithreaded', 
(CASE 
WHEN t8.ram_capacity IS NOT NULL AND t8.ram_speed IS NOT NULL THEN CONCAT(t8.ram_capacity, ' GB (', t8.ram_speed, ' MHz)')
WHEN t8.ram_capacity IS NOT NULL AND t8.ram_speed IS NULL THEN CONCAT(t8.ram_capacity, ' GB')
END) AS 'ram_capacity_formatted',
t4.disk_model, CONCAT(t4.disk_size, 'GB') AS 'disk_size', t4.disk_type, t4.disk_serial, 
t5.identifier, t5.recovery_key, 
CONCAT(clientstats.battery_health, '%') AS 'battery_health', CONCAT(clientstats.disk_health, '%') AS 'disk_health', 
CONCAT(clientstats.erase_avgtime, ' mins') AS 'erase_avgtime', CONCAT(clientstats.clone_avgtime, ' mins') AS 'clone_avgtime',
clientstats.all_jobs, 
DATE_FORMAT(remote.present, '%m/%d/%y, %r') AS 'remote_time_formatted', remote.status AS 'remote_status', remote.present_bool, 
remote.kernel_updated, CONCAT(remote.network_speed, ' mbps') AS 'network_speed', client_health.bios_updated, 
IF (client_health.bios_updated = 1, CONCAT('Updated ', '(', client_health.bios_version, ')'), CONCAT('Out of date ', '(', client_health.bios_version, ')')) AS 'bios_updated_formatted', 
(CASE
WHEN t4.disk_writes IS NOT NULL AND t4.disk_reads IS NOT NULL THEN CONCAT(t4.disk_writes, ' TBW/', t4.disk_reads, 'TBR')
WHEN t4.disk_writes IS NOT NULL AND t4.disk_reads IS NULL THEN CONCAT(t4.disk_writes, ' TBW')
WHEN t4.disk_reads IS NULL AND t4.disk_reads IS NOT NULL THEN CONCAT(t4.disk_reads, ' TBW')
END) AS 'disk_tbw_formatted',
CONCAT(t4.disk_writes, ' TBW') AS 'disk_writes', CONCAT(t4.disk_reads, ' TBR') AS 'disk_reads', CONCAT(t4.disk_power_on_hours, ' hrs') AS 'disk_power_on_hours',
t4.disk_power_cycles, t4.disk_errors, locations.domain, IF (locations.domain IS NOT NULL, static_domains.domain_readable, 'Not Joined') AS 'domain_readable',
IF (client_health.os_installed = 1, CONCAT(client_health.os_name, ' (Imaged on ', DATE_FORMAT(t6.time, '%m/%d/%y, %r'), ')'), client_health.os_name) AS 'os_installed_formatted',
checkout.customer_name, checkout.checkout_date, checkout.checkout_bool
FROM locations
LEFT JOIN clientstats ON locations.tagnumber = clientstats.tagnumber
LEFT JOIN client_health ON locations.tagnumber = client_health.tagnumber
LEFT JOIN jobstats ON (locations.tagnumber = jobstats.tagnumber AND jobstats.time IN (SELECT MAX(time) AS 'time' FROM jobstats WHERE tagnumber IS NOT NULL AND system_serial IS NOT NULL AND (host_connected = 1 or (uuid LIKE 'techComm-%' AND etheraddress IS NOT NULL)) GROUP BY tagnumber))
LEFT JOIN system_data ON locations.tagnumber = system_data.tagnumber
LEFT JOIN (SELECT tagnumber, department FROM departments WHERE time IN (SELECT MAX(time) FROM departments WHERE tagnumber IS NOT NULL GROUP BY tagnumber)) t1 
ON locations.tagnumber = t1.tagnumber
LEFT JOIN remote ON locations.tagnumber = remote.tagnumber
LEFT JOIN (SELECT department, department_readable FROM static_departments) t2
ON t1.department = t2.department
LEFT JOIN (SELECT tagnumber, time, note FROM locations WHERE time IN (SELECT MAX(time) FROM locations WHERE note IS NOT NULL GROUP BY tagnumber)) t3
ON locations.tagnumber = t3.tagnumber
LEFT JOIN (SELECT tagnumber, disk_model, disk_serial, disk_size, disk_type, disk_writes, disk_reads, disk_power_on_hours, disk_power_cycles, IF(disk_errors IS NOT NULL, disk_errors, 0) AS 'disk_errors' FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE disk_type IS NOT NULL AND tagnumber IS NOT NULL GROUP BY tagnumber)) t4 
ON locations.tagnumber = t4.tagnumber
LEFT JOIN (SELECT tagnumber, identifier, recovery_key FROM bitlocker) t5 
ON locations.tagnumber = t5.tagnumber
LEFT JOIN (SELECT time, tagnumber, clone_image, row_nums FROM (SELECT time, tagnumber, clone_image, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'row_nums' FROM jobstats WHERE tagnumber IS NOT NULL AND clone_completed = 1 AND clone_image IS NOT NULL) s6 WHERE s6.row_nums = 1) t6
ON locations.tagnumber = t6.tagnumber
LEFT JOIN static_image_names ON t6.clone_image = static_image_names.image_name
LEFT JOIN (SELECT tagnumber, ram_capacity, ram_speed FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE ram_capacity IS NOT NULL AND ram_speed IS NOT NULL AND tagnumber IS NOT NULL GROUP BY tagnumber)) t8
ON locations.tagnumber = t8.tagnumber
INNER JOIN (SELECT MAX(time) AS 'time' FROM locations WHERE tagnumber IS NOT NULL AND system_serial IS NOT NULL GROUP BY tagnumber) t10
ON locations.time = t10.time
LEFT JOIN static_domains ON locations.domain = static_domains.domain
LEFT JOIN checkout ON locations.tagnumber = checkout.tagnumber AND checkout.time IN (SELECT s11.time FROM (SELECT time, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'row_nums' FROM checkout) s11 WHERE s11.row_nums = 1)
WHERE locations.tagnumber IS NOT NULL and locations.system_serial IS NOT NULL
AND locations.tagnumber = :tagnumber";

$sqlArr = array();
$db->Pselect($sql, array(':tagnumber' => htmlspecialchars_decode($_GET["tagnumber"])));
if (arrFilter($db->get()) === 0) {
$sqlArr = $db->get();
}
?>



    <?php echo "<div class='page-content'><h3>Update Queued Job and Location Data - <u>" . htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</u></h3></div>"; ?>

    <div class='row'>

      <div class='column'>
        <div class='location-form'>

        <div name='curJob' id='curJob'>
          <p>Current Status: </p>
          <?php
          if (arrFilter($sqlArr) === 0) {
            foreach ($sqlArr as $key => $value) {
              // BIOS and kernel updated (check mark)
              if ($value["present_bool"] === 1 && ($value["kernel_updated"] === 1 && $value["bios_updated"] === 1)) {
              echo "Online, no errors <span>&#10004;&#65039;</span>";
              // BIOS and kernel out of date (x)
              } elseif ($value["present_bool"] === 1 && ($value["kernel_updated"] !== 1 && $value["bios_updated"] !== 1)) {
              echo "Online, kernel and BIOS out of date <span>&#10060;</span>";
              // BIOS out of date, kernel updated (warning sign)
              } elseif ($value["present_bool"] === 1 && ($value["kernel_updated"] === 1 && $value["bios_updated"] !== 1)) {
              echo "Online, please update BIOS <span>&#9888;&#65039;</span>";
              // BIOS updated, kernel out of date (x)
              } elseif ($value["present_bool"] === 1 && ($value["kernel_updated"] !== 1 && $value["bios_updated"] === 1)) {
              echo "Online, kernel out of date <span>&#10060;</span>)";
              // Offline (x)
              } elseif ($value["present_bool"] !== 1) {
              echo "Offline <span>&#9940;</span>";
              } else {
              echo "Unknown <span>&#9940;&#65039;</span>";
              }

              if (strFilter($value["remote_status"]) === 0) {
                echo "<p><b>'" . htmlspecialchars($value["remote_status"]) . "'</b> at " . htmlspecialchars($value["remote_time_formatted"]) . "</p>" . PHP_EOL;
              }
            }
          } else {
            echo "Missing required info. Please plug into laptop server to gather information.<br>
            To update the location, please update it from the <a href='/locations.php'>locations page</a>";
          }
          ?>
        </div>

          <form name="job_queued_form" method="post">
            <div><label for='tagnumber'>Enter a job to queue: </label></div>
            <input type='hidden' id='job_queued_tagnumber' name='job_queued_tagnumber' value='<?php echo htmlspecialchars($_GET["tagnumber"]); ?>'>
            <select name="job_queued">
              <?php
              // Get/set current jobs.
              if ($_GET['tagnumber'] && arrFilter($sqlArr) === 0) {
                $db->Pselect("SELECT tagnumber FROM remote WHERE tagnumber = :tagnumber", array(':tagnumber' => $_GET["tagnumber"]));
                if (arrFilter($db->get()) === 0 ) {
                  $db->Pselect("SELECT IF (remote.job_queued IS NOT NULL, remote.job_queued, '') AS 'job_queued',
                    IF (remote.job_queued IS NOT NULL, static_job_names.job_readable, 'No Job') AS 'job_queued_formatted',
                    IF (remote.job_active = 1, 'In Progress: ', 'Queued: ') AS 'job_status_formatted'
                    FROM remote 
                    INNER JOIN static_job_names 
                    ON remote.job_queued = static_job_names.job 
                    WHERE remote.tagnumber = :tagnumber", array(':tagnumber' => htmlspecialchars_decode($_GET['tagnumber'])));
                  if (arrFilter($db->get()) === 0) {
                    foreach ($db->get() as $key => $value1) {
                      echo "<option value='" . htmlspecialchars($value1["job_queued"]) . "'>" . htmlspecialchars($value1["job_status_formatted"]) . htmlspecialchars($value1["job_queued_formatted"]) . "</option>";
                    }
                    unset($value1);
                  }
                  echo "<option value=''>--Select Job Below--</option>" . PHP_EOL;
                  $db->Pselect("SELECT job, job_readable FROM static_job_names WHERE job_html_bool = 1 AND NOT job IN (SELECT IF (remote.job_queued IS NULL, '', remote.job_queued) FROM remote WHERE remote.tagnumber = :tagnumber) ORDER BY job_rank ASC", array(':tagnumber' => $_GET["tagnumber"]));
                  foreach ($db->get() as $key => $value2) {
                    echo "<option value='" . htmlspecialchars($value2["job"]) . "'>" . htmlspecialchars($value2["job_readable"]) . "</option>";
                  }
                  unset($value2);
                  unset($value);
                }
              } else {
                echo "<option>ERR: " . htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . " missing info :((</option>";
              }
              ?>
            </select>
            <button style='background-color:rgba(0, 179, 136, 0.30);' type="submit">Queue Job</button>
          </form>


        </div>

        <div class='pagetitle'><h3></u></h3></div>
        <div name='updateDiv1' id='updateDiv1' class='styled-table' style='width: auto; height: auto; overflow:auto; margin: 1% 1% 3% 2%;'>
          <table width='100%;'>
            <thead>
              <tr>
              <th>General Client Info - <u><?php echo htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></th>
              <th></th>
              </tr>
            </thead>
            <tbody>
              <?php
                foreach ($sqlArr as $key => $value) {
              ?>
                <tr>
                  <td>Current Location</td>
                  <td>
                    <?php
                    if ($value["checkout_bool"] === 1) {
                      echo "<p>Currently checked out to <b>" . htmlspecialchars($value["customer_name"]) . "</b> on <b>" . htmlspecialchars($value["checkout_date"]) . "</b></p>";
                    }
                    ?>
                    <p>"<?php echo trim(htmlspecialchars($value["location"])); ?>"</p><p><a href='#' onclick='newLocationWindow("<?php echo trim(htmlspecialchars($value["location"])); ?>", "<?php echo trim(htmlspecialchars($value["tagnumber"])); ?>");'><img class='new-tab-image' src='/images/new-tab.svg'></img><i>(Click to Update Location)</i></a></p>
                  </td>
                </tr>
                <tr>
                  <td>Department</td>
                  <td><?php echo "<p>" . trim(htmlspecialchars($value["department_readable"])) . "</p><p><a href='/locations.php?location=" . trim(htmlspecialchars($value["location"])) . "&tagnumber=" . trim(htmlspecialchars($value["tagnumber"])) . "&department=" . trim(htmlspecialchars($value["department"])) . "'><img class='new-tab-image' src='/images/new-tab.svg'></img><i>(Click to Update Department)</i></a></p>"; ?></td>
                </tr>
                <tr>
                  <td>AD Domain</td>
                  <td><?php echo "<p>" . trim(htmlspecialchars($value["domain_readable"])) . "</p><p><a href='/locations.php?location=" . trim(htmlspecialchars($value["location"])) . "&tagnumber=" . trim(htmlspecialchars($value["tagnumber"])) . "&domain=" . trim(htmlspecialchars($value["domain"])) . "'><img class='new-tab-image' src='/images/new-tab.svg'></img><i>(Click to Update Domain)</i></a></p>"; ?></td>
                </tr>
                <tr>
                  <td>System Serial</td>
                  <td><?php echo htmlspecialchars($value['system_serial'])?></td>
                </tr>
                <tr>
                  <td>MAC Address</td>
                  <td>
                    <?php
                    // Latitude 7400 does not have ethernet ports, we use the USB ethernet ports for them, but the USB ethernet MAC address is still associated with their tagnumbers.
                    if ($value["system_model"] !== "Latitude 7400" && $value["system_model"] !== "Latitude 5289") {
                      if (strFilter($value["wifi_mac"]) === 0 && strFilter($value["etheraddress"]) === 0) {
                        echo "<table><tr><td>" . htmlspecialchars($value["wifi_mac"]) . " (Wi-Fi)</td></tr><tr><td>" . htmlspecialchars($value["etheraddress"]) . " (Ethernet)</td></tr></table>" . PHP_EOL;
                      } elseif (strFilter($value["wifi_mac"]) === 0 && strFilter($value["etheraddress"]) === 1) {
                        echo htmlspecialchars($value["wifi_mac"]) . " (Wi-Fi)";
                      } elseif (strFilter($value["wifi_mac"]) === 1 && strFilter($value["etheraddress"]) === 0) {
                        echo htmlspecialchars($value["etheraddress"]) . " (Ethernet)";
                      }
                    } elseif ($value["system_model"] === "Latitude 7400" || $value["system_model"] === "Latitude 5289") {
                      if (strFilter($value["wifi_mac"]) === 0 && strFilter($value["etheraddress"]) === 0) {
                        echo htmlspecialchars($value["wifi_mac"]) . " (Wi-Fi)";
                      } elseif (strFilter($value["wifi_mac"]) === 0 && strFilter($value["etheraddress"]) === 1) {
                        echo htmlspecialchars($value["wifi_mac"]) . " (Wi-Fi)";
                      }
                    }
                  ?>
                  </td>
                </tr>
                <tr>
                  <td>System Model</td>
                  <td><?php echo htmlspecialchars($value["system_model_formatted"]); ?></td>
                </tr>
                <tr>
                  <td>OS Version</td>
                  <td><?php echo htmlspecialchars($value["os_installed_formatted"]); ?></td>
                </tr>
                <tr>
                  <td>Bitlocker Identifier</td>
                  <td><?php echo htmlspecialchars($value["identifier"]); ?></td>
                </tr>
                <tr>
                  <td>Bitlocker Recovery Key</td>
                  <td><?php echo htmlspecialchars($value["recovery_key"]); ?></td>
                <tr>
                  <td>BIOS Version</td>
                  <td><?php echo htmlspecialchars($value["bios_updated_formatted"]); ?></td>
                </tr>
                <tr>
                  <td>Network Link Speed</td>
                  <td><?php echo htmlspecialchars($value['network_speed']); ?></td>
                </tr>
                <?php
                  //Close foreach
                  }
                  unset($value);
              ?>
            </tbody>
          </table>
        </div>
        <!-- Close column div-->
      </div>

      <div class='column'>
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

      </div>

      <!--Close row div-->
    </div>





      <div class='row' style='margin: 1% 0% 0% 1%;'>
        <div class='column'>
          <div class='styled-table' style='height: auto; overflow:auto;'>
            <table width='100%'>
              <thead>
                <tr>
                <th>Disk Info - <u><?php echo htmlspecialchars($_GET["tagnumber"]); ?></th>
                <th></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <?php
                  if (isset($_GET["tagnumber"]) && arrFilter($sqlArr) === 0) {
                    foreach ($sqlArr as $key => $value) {
                  ?>
                  <td>Disk Model</td>
                  <td><?php echo htmlspecialchars($value["disk_model"]); ?></td>
                </tr>
                <tr>
                  <td>Disk Serial</td>
                  <td><?php echo htmlspecialchars($value["disk_serial"]); ?></td>
                </tr>
                <tr>
                  <td>Disk Type</td>
                  <td><?php echo htmlspecialchars($value["disk_type"]); ?></td>
                </tr>
                <tr>
                  <td>Disk Size</td>
                  <td><?php echo htmlspecialchars($value["disk_size"]); ?></td>
                </tr>

              </tbody>
            </table>
          </div>

          <div class='styled-table' style='height: auto; overflow:auto;'>
            <table width='100%'>
              <thead>
                <tr>
                <th>CPU/RAM Info - <u><?php echo htmlspecialchars($_GET["tagnumber"]); ?></th>
                <th></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>CPU Model</td>
                  <td><?php echo htmlspecialchars($value["cpu_model"]); ?></td>
                </tr>
                <tr>
                  <td>Multithreaded</td>
                  <td><?php echo htmlspecialchars($value["multithreaded"]); ?></td>
                </tr>
                <tr>
                  <td>RAM Capacity</td>
                  <td><?php echo htmlspecialchars($value["ram_capacity_formatted"]); ?></td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>

        <div class='column'>
          <div name='updateDiv3' id='updateDiv3' class='styled-table' style='width: auto; overflow:auto; margin: 1% 1% 5% 2%;'>
            <table width='100%'>
              <thead>
                <tr>
                <th>Client Health - <u><?php echo htmlspecialchars($_GET["tagnumber"]); ?></th>
                <th></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Total Jobs</td>
                  <td><?php echo htmlspecialchars($value['all_jobs'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></td>
                </tr>
                <tr>
                  <td>Erase Avg. Time</td>
                  <td><?php echo htmlspecialchars($value['erase_avgtime'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></td>
                </tr>
                <tr>
                  <td>Clone Avg. Time</td>
                  <td><?php echo htmlspecialchars($value['clone_avgtime'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></td>
                </tr>
                <tr>
                  <td>Battery Health</td>
                  <td><?php echo htmlspecialchars($value['battery_health'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></td>
                </tr>
                <tr>
                  <td>Disk TBW/TBR</td>
                  <td><?php echo htmlspecialchars($value['disk_tbw_formatted'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></td>
                </tr>
                <tr>
                  <td>Disk Power on Hours</td>
                  <td><?php echo htmlspecialchars($value['disk_power_on_hours'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></td>
                </tr>
                <tr>
                  <td>Disk Power Cycles</td>
                  <td><?php echo htmlspecialchars($value['disk_power_cycles'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></td>
                </tr>
                <tr>
                  <td>Disk Errors</td>
                  <td><?php echo htmlspecialchars($value['disk_errors'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></td>
                </tr>
                <tr>
                  <td>Disk Health</td>
                  <td><?php echo htmlspecialchars($value['disk_health'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></td>

                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
  <?php
  // Close if & foreach statements for this row
  }}
  unset($value);
  ?>


<div class='pagetitle'><h3>Checkout Log - <u><?php echo htmlspecialchars($_GET["tagnumber"]); ?></u></h3></div>
<div name='checkoutLog' id='checkoutLog' class='styled-table' style="width: auto; max-height: 40%; overflow:auto; margin: 1% 1% 5% 1%;">
<table width="100%">
<thead>
<tr>
<th>Time of Entry</th>
<th>Customer Name</th>
<th>Checkout Date</th>
<th>Return Date</th>
<th>Note</th>
</tr>
</thead>
<tbody>
<?php
$db->Pselect("SELECT time, DATE_FORMAT(time, '%m/%d/%y, %r') AS 'time_formatted', customer_name, checkout_date, return_date, note FROM checkout WHERE tagnumber = :tag ORDER BY time DESC", array(':tag' => $_GET["tagnumber"]));
if (arrFilter($db->get()) === 0) {
foreach ($db->get() as $key => $value1) {
echo "<tr>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['time_formatted'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['customer_name'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['checkout_date'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['return_date'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['note'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
echo "</tr>" . PHP_EOL;
}
}
unset($value1);
?>
</tbody>
</table>
</div>

<div class='pagetitle'><h3>Job Log - <u><?php echo htmlspecialchars($_GET["tagnumber"]); ?></u></h3></div>
<div name='updateDiv4' id='updateDiv4' class='styled-table' style="width: auto; max-height: 40%; overflow:auto; margin: 1% 1% 5% 1%;">
<table width="100%">
<thead>
<tr>
<th>Time</th>
<th>CPU Usage</th>
<th>Network Usage</th>
<th>Erase Mode</th>
<th>Erase Time Elapsed</th>
<th>Clone Master</th>
<th>Clone Time</th>
<th>BIOS Version</th>
</tr>
</thead>
<tbody>
<?php
$db->select("SELECT DATE_FORMAT(time, '%m/%d/%y, %r') AS 'time_formatted', CONCAT(cpu_usage, '%') AS 'cpu_usage', CONCAT(network_usage, ' mbps') AS 'network_usage', IF (erase_completed = 1, 'Yes', 'No') AS 'erase_completed', erase_mode, SEC_TO_TIME(erase_time) AS 'erase_time', IF (clone_completed = 1, 'Yes', 'No') AS clone_completed, IF (clone_master = 1, 'Yes', 'No') AS clone_master, SEC_TO_TIME(clone_time) AS 'clone_time', bios_version FROM jobstats WHERE tagnumber = '" . htmlspecialchars_decode($_GET['tagnumber']) . "' AND (erase_completed = '1' OR clone_completed = '1') ORDER BY time DESC");
if (arrFilter($db->get()) === 0) {
foreach ($db->get() as $key => $value1) {
echo "<tr>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['time_formatted'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['cpu_usage'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['network_usage'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['erase_mode'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['erase_time'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
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

<div class='pagetitle'><h3>Location Log - <u><?php echo htmlspecialchars($_GET["tagnumber"]); ?></u></h3></div>
<div name='updateDiv5' id='updateDiv5' class='styled-table' style="width: auto; max-height: 40%; overflow:auto; margin: 1% 1% 5% 1%;">
<table width="100%">
<thead>
<tr>
<th>Timestamp</th>
<th>Location</th>
<th>Department</th>
<th>Functional</th>
<th>Disk Removed</th>
<th>Note</th>
<th></th>
</tr>
</thead>
<tbody>
<?php
//Use this if you want range of department data instead of equal times: --INNER JOIN departments ON (locations.tagnumber = :tagnumber AND departments.tagnumber = :tagnumber AND departments.time IN (SELECT MAX(time) FROM departments WHERE time <= locations.time))
$db->Pselect("SELECT t2.* FROM 
(SELECT static_departments.department_readable, departments.time AS 'deptTime', locations.time, DATE_FORMAT(locations.time, '%m/%d/%y, %r') AS 'time_formatted', 
locationFormatting(locations.location) AS 'location', 
ROW_NUMBER() OVER (PARTITION BY locations.location ORDER BY locations.time DESC) AS 'location_num', 
IF (locations.status = 1, 'No, Broken', 'Yes') AS 'status_formatted', locations.status, 
IF (client_health.os_installed = 1, 'Yes', 'No') AS 'os_installed', 
IF (locations.disk_removed = 1, 'Yes', 'No') AS 'disk_removed', 
note 
FROM locations 
LEFT JOIN client_health ON (locations.tagnumber = :tagnumber AND client_health.tagnumber = :tagnumber)
INNER JOIN departments ON (locations.tagnumber = :tagnumber AND departments.tagnumber = :tagnumber AND departments.time = locations.time)
INNER JOIN static_departments ON departments.department = static_departments.department
WHERE 
NOT locations.location = 'Plugged in and booted on laptop table.' 
AND NOT locations.location = 'Finished work on laptop table.' 
AND locations.tagnumber = :tagnumber
AND departments.tagnumber = :tagnumber
AND locations.location IS NOT NULL) t2
WHERE t2.location_num <= 3
ORDER BY t2.time DESC", array(':tagnumber' => htmlspecialchars_decode($_GET["tagnumber"])));
if (arrFilter($db->get()) === 0) {
foreach ($db->get() as $key => $value1) {
echo "<tr>" . PHP_EOL;
//Time formatted
echo "<td>" . htmlspecialchars($value1['time_formatted'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value1["location"]) . "' target='_blank'>" . htmlspecialchars($value1["location"]) . "</a></b></td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['department_readable'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['status_formatted'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
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
// update current job and status
const curJob = doc.getElementById('curJob').innerHTML
document.getElementById("curJob").innerHTML = curJob
// update all other tables
const updateDiv1 = doc.getElementById('updateDiv1').innerHTML
document.getElementById("updateDiv1").innerHTML = updateDiv1
//const updateDiv2 = doc.getElementById('updateDiv2').innerHTML
//document.getElementById("updateDiv2").innerHTML = updateDiv2
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