<?php
require('/var/www/html/uit-web/header.php');
require('/var/www/html/uit-web/php/include.php');

if (strFilter($_GET["tagnumber"]) === 1) {
  http_response_code(500);
  exit();
}

if (preg_match('/^[0-9]{6}$/', $_GET["tagnumber"]) !== 1) {
  http_response_code(500);
  exit();
}

session_start();
if ($_SESSION['authorized'] != "yes") {
  exit();
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://localhost:1411/api/refresh-client.php?password=CLIENT_PASSWD&tagnumber=" . htmlspecialchars($_GET["tagnumber"]) . "");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$db = new db();
?>

<?php
//POST data
if (isset($_POST["delete-image"]) && $_POST["delete-image"] == "1") {
  $db->deleteImage($_POST["delete-image-uuid"], $_POST["delete-image-time"], $_POST["delete-image-tagnumber"]);
}

if (isset($_FILES["userfile"]) && strFilter($_FILES["userfile"]["tmp_name"]) === 0) {
  //Check for accepted mime types
  $fileMimeType = mime_content_type($_FILES["userfile"]["tmp_name"]);
  $fileAllowedMimes = ['image/png', 'image/jpeg', 'image/webp', 'image/avif', 'video/mp4', 'video/quicktime'];
  if (!in_array($fileMimeType, $fileAllowedMimes)) {
    $imageUploadError = 2;
  } else {
    $fh = fopen($_FILES["userfile"]["tmp_name"], 'rb');
    //Get raw file data
    $rawFileData = file_get_contents($_FILES["userfile"]["tmp_name"]);
    //Get file hash
    $imageHash = md5($rawFileData);
    //Check if hash already in DB
    $db->Pselect("SELECT uuid FROM client_images WHERE md5_hash = :md5_hash", array(':md5_hash' => $imageHash));
    if (strFilter($db->get()) === 1) {
      $imageUUID = uniqid("image-", true);
      //Convert all images to jpeg
      if (preg_match('/^image.*/', $fileMimeType) === 1) {
        // //Read image exif data
        // $exifArr = exif_read_data($fh);
        // fclose($fh);
        //Create jpeg and check if can convert. Create base64 string.
        $imageObject = imagecreatefromstring($rawFileData);
        if ($imageObject !== false) {
          ob_start();
          imagejpeg($imageObject, NULL, 90);
          $imageFileConverted = base64_encode(ob_get_clean());
          imagedestroy($imageObject);
        }
        //Convert all videos to mp4. Outputs base64 string.
      } elseif (preg_match('/^video.*/', $fileMimeType) === 1) {
        $transcodeFile = uniqid("uit-transcode-", true);
        $file = fopen("/var/www/html/uit-web/transcode/" . $transcodeFile, 'c');
        fwrite($file, $rawFileData);
        fclose($file);
        $imageFileConverted = passthru("bash /var/www/html/uit-web/bash/convert-to-mp4" . " " . escapeshellarg("WEB_SVC_PASSWD") . " " . $transcodeFile);
      }

      if ($imageObject !== false) {
        $db->insertImage($imageUUID, $time, $_GET["tagnumber"]);
        $db->updateImage("image", $imageFileConverted, $imageUUID);
        $db->updateImage("md5_hash", $imageHash, $imageUUID);
        $db->updateImage("filename", $_FILES["userfile"]["name"], $imageUUID);
        $db->updateImage("filesize", round($_FILES["userfile"]["size"] / 1048576, 3), $imageUUID);
        $db->updateImage("note", $_POST["image-note"], $imageUUID);
        $db->updateImage("mime_type", $fileMimeType, $imageUUID);
        //$db->updateImage("hidden", "0", $_POST["delete-image"]);
        if (preg_match('/^image.*/', $fileMimeType) === 1) {
          //$db->updateImage("exif_timestamp", date("Y-m-d H:i:s.v", $exifArr["DateTimeOriginal"]), $imageUUID);
          $db->updateImage("resolution", imagesx($imageObject) . "x" . imagesx($imageObject), $imageUUID);
        }
        unset($imageObject);
        unset($imageFileConverted);
      } else {
        $imageUploadError = 2;
      }
    } else {
      $imageUploadError = 1;
    }
  }
}
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

    //Insert location data
    $db->insertLocation($time);
    $db->updateLocation("tagnumber", $tagNum, $time);
    $db->updateLocation("system_serial", $serial, $time);
    $db->updateLocation("location", $location, $time);
    $db->updateLocation("status", $status, $time);
    $db->updateLocation("disk_removed", $diskRemoved, $time);
    $db->updateLocation("note", $note, $time);
    $db->updateLocation("domain", $domain, $time);
    $db->updateLocation("department", $department, $time);


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

<!DOCTYPE html>
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

  <div class='row'>
    <div class='column'>
  <div class='pagetitle'><h1>Client Lookup (<?php echo htmlspecialchars($_GET['tagnumber'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?>)</h1></div>
</div>
<div class='column'>
  <div class='location-form' style='position: relative; width: 20em; top:5%; right:2%;'><form method='GET'><input type='text' name='tagnumber' style='width: 100%;' autocapitalize='none' autocomplete='off' autocorrect='off' spellcheck='false' placeholder='Enter Tag Number...'></form></div>
</div>
</div>


<?php
// Get most client data - main sql query
unset($sql);
$sql = "SELECT DATE_FORMAT(t10.time, '%m/%d/%y, %r') AS 'location_time_formatted',
IF (t3.time = t10.time, 1, 0) AS 'placeholder_bool',
jobstats.time AS 'jobstatsTime', locations.tagnumber, locations.system_serial, locations.department, 
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
system_data.cpu_model, system_data.cpu_cores, CONCAT(ROUND((system_data.cpu_maxspeed / 1000), 2), ' Ghz') AS 'cpu_maxspeed', IF(system_data.cpu_threads > system_data.cpu_cores, CONCAT(system_data.cpu_cores, ' cores/', system_data.cpu_threads, ' threads (Multithreaded)'), CONCAT(system_data.cpu_cores, ' cores (Not Multithreaded)')) AS 'multithreaded', 
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
checkouts.customer_name, checkouts.checkout_date, checkouts.checkout_bool, client_health.tpm_version
FROM locations
LEFT JOIN clientstats ON locations.tagnumber = clientstats.tagnumber
LEFT JOIN client_health ON locations.tagnumber = client_health.tagnumber
LEFT JOIN jobstats ON (locations.tagnumber = jobstats.tagnumber AND jobstats.time IN (SELECT MAX(time) AS 'time' FROM jobstats WHERE tagnumber IS NOT NULL AND system_serial IS NOT NULL AND (host_connected = 1 or (uuid LIKE 'techComm-%' AND etheraddress IS NOT NULL)) GROUP BY tagnumber))
LEFT JOIN system_data ON locations.tagnumber = system_data.tagnumber
LEFT JOIN remote ON locations.tagnumber = remote.tagnumber
LEFT JOIN (SELECT department, department_readable FROM static_departments) t2
ON locations.department = t2.department
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
LEFT JOIN checkouts ON locations.tagnumber = checkouts.tagnumber AND checkouts.time IN (SELECT s11.time FROM (SELECT time, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'row_nums' FROM checkouts) s11 WHERE s11.row_nums = 1)
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
            <p>Real-time Job Status: </p>
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
          
          <div>
            <form name="job_queued_form" method="post">
              <div><label for='tagnumber'>Enter a job to queue: </label></div>
              <input type='hidden' id='job_queued_tagnumber' name='job_queued_tagnumber' value='<?php echo htmlspecialchars($_GET["tagnumber"]); ?>'>
              <div><select name="job_queued">
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
              <button style='background-color:rgba(0, 179, 136, 0.30);' type="submit">Queue Job</button></div>
            </form>
          </div>
        </div>

        <div class='location-form'>
          <form enctype="multipart/form-data" method="POST">
            <div><p>Upload Image: </p></div>
            <!--<div><input name="userfile" type="file" onchange='this.form.submit();' accept="image/png, image/jpeg, image/webp, image/avif" /></div>-->
            <div><input name="userfile" type="file" accept="image/png, image/jpeg, image/webp, image/avif, video/mp4, video/quicktime" /></div>
            <div><input name="image-note" type="text" autocapitalize='sentences' autocomplete='off' autocorrect='off' spellcheck='false' placeholder="Add Image Description..."></div>
            <div><button style="background-color:rgba(0, 179, 136, 0.30);" type="submit">Upload Image</button></div>
          </form>
            <?php 
            if ($imageUploadError === 1) {
              echo "<div><p style='color: red;'><b>Error: File already uploaded.</b></p></div>";
            } elseif ($imageUploadError == 2) {
              echo "<div><p style='color: red;'><b>Error: Incorrect file format.</b></p></div>";
            }
            ?>
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
                    if ($value["locations_status"] === 1) {
                      echo "<p><b style='color: #C8102E'>[REPORTED BROKEN]</b> on " . htmlspecialchars($value["location_time_formatted"]) . "</b></p>";
                    }
                    ?>
                    <?php
                    if ($value["checkout_bool"] === 1) {
                      echo "<p><b>[CHECKOUT]</b> - Checked out to <b>" . htmlspecialchars($value["customer_name"]) . "</b> on <b>" . htmlspecialchars($value["checkout_date"]) . "</b></p>";
                    }
                    ?>
                    <p>"<?php echo trim(htmlspecialchars($value["location"])); ?>"</p><p><a style='cursor: pointer;' onclick='newLocationWindow("<?php echo trim(htmlspecialchars($value["location"])); ?>", "<?php echo trim(htmlspecialchars($value["tagnumber"])); ?>");'><img class='new-tab-image' src='/images/new-tab.svg'></img><i>(Click to Update Location)</i></a></p>
                    <?php
                    if (strFilter($value["note"]) === 0) {
                      echo "<p><b>Note:</b> \"" . trim(htmlspecialchars($value["note"])) . "\"</p>";
                    }
                    ?>
                  </td>
                </tr>
                <tr>
                  <td>Department</td>
                  <td>
                    <p>"<?php echo trim(htmlspecialchars($value["department_readable"])); ?>"</p><p><a style='cursor: pointer;' onclick='newLocationWindow("<?php echo trim(htmlspecialchars($value["location"])); ?>", "<?php echo trim(htmlspecialchars($value["tagnumber"])); ?>", "<?php echo trim(htmlspecialchars($value["department"])); ?>");'><img class='new-tab-image' src='/images/new-tab.svg'></img><i>(Click to Update Department)</i></a></p>
                  </td>
                </tr>
                <tr>
                  <td>AD Domain</td>
                  <td>
                    <p>"<?php echo trim(htmlspecialchars($value["domain_readable"])); ?>"</p><p><a style='cursor: pointer;' onclick='newLocationWindow("<?php echo trim(htmlspecialchars($value["location"])); ?>", "<?php echo trim(htmlspecialchars($value["tagnumber"])); ?>", "", "<?php echo trim(htmlspecialchars($value["domain"])); ?>");'><img class='new-tab-image' src='/images/new-tab.svg'></img><i>(Click to Update Domain)</i></a></p>
                  </td>
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
                  <td>TPM Version</td>
                  <td><?php echo htmlspecialchars($value["tpm_version"]); ?></td>
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
        <?php
        $db->Pselect("SELECT uuid, time, tagnumber, filename, filesize, resolution, mime_type, image, note, DATE_FORMAT(time, '%m/%d/%y, %r') AS 'time_formatted', ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'row_nums' FROM client_images WHERE tagnumber = :tagnumber AND hidden = 0 ORDER BY time DESC", array(':tagnumber' => $_GET["tagnumber"]));
        if (strFilter($db->get()) === 0) {
          echo "<div class='page-content'><b>[<a href='/view-images.php?tagnumber=" . htmlspecialchars($_GET["tagnumber"]) . "' target='_blank'>View All Images</a>]</b></div>";
          echo "<div class='grid-container' style='width: 100%;'>";
          foreach ($db->get() as $key => $image) {
            if ($image["row_nums"] <= 6) {
              echo "<div class='grid-box'>";
              echo "<div style='margin: 0 0 1em 0; padding: 0; width: fit-content;'>";
              echo "<form method='post'>";
              echo "<input type='hidden' name='delete-image' value='1'>";
              echo "<input type='hidden' name='delete-image-uuid' value='" . $image["uuid"] . "'>";
              echo "<input type='hidden' name='delete-image-time' value='" . $image["time"] . "'>";
              echo "<input type='hidden' name='delete-image-tagnumber' value='" . $image["tagnumber"] . "'>";
              echo "<div style='position: relative; top: 0; left: 0;'>";
              echo "[<input type=submit style='font-size: 1em; background-color: transparent; text-decoration: underline; color: #C8102E; border: none; margin: 0; padding: 0; cursor: pointer; font-weight: bold;' onclick='this.form.submit()' value='delete'></input>]</form></div></div>";
              echo "<div><p>(" . htmlspecialchars($image["row_nums"]) . "/" . htmlspecialchars($db->get_rows()) . ") Upload Timestamp: " . htmlspecialchars($image["time_formatted"]) . "</p>";
              echo "<div><p>File Info: \"" . htmlspecialchars($image["filename"]) . "\" (" . htmlspecialchars($image["resolution"]) . ", " . htmlspecialchars($image["filesize"]) . " MB" . ")</p></div>";
              if (strFilter($image["note"]) === 0) {
                echo "<div><p><b>Note: </b> " . htmlspecialchars($image["note"]) . "</p></div>";
              }
              echo "<div style='padding: 1em 1px 1px 1px;'>";
              if (preg_match('/^image.*/', $image["mime_type"]) === 1) {
                echo "<img style='max-height:100%; max-width:100%; cursor: pointer;' onclick=\"openImage('" . $image["image"] . "')\" src='data:image/jpeg;base64," . $image["image"] . "'></img>";
              } elseif (preg_match('/^video\/mp4/', $image["mime_type"]) === 1) {
                echo "<video preload='metadata' style='max-height:100%; max-width:100%;' controls><source type='video/mp4' src='data:video/mp4;base64," . $image["image"] . "' /></video>";
              } elseif (preg_match('/^video\/quicktime/', $image["mime_type"]) === 1) {
                echo "<video preload='metadata' style='max-height:100%; max-width:100%;' controls><source type='video/quicktime' src='data:video/quicktime;base64," . $image["image"] . "' /></video>";
              }
              echo "</div>";
              echo "</div>";
              echo "</div>";
            }
          }
          unset($image);
        } else {
          $db->Pselect("SELECT system_model FROM system_data WHERE tagnumber = :tagnumber", array(':tagnumber' => htmlspecialchars_decode($_GET['tagnumber'])));
          if (arrFilter($db->get()) === 0) {
            echo "<div class='laptop-images'>";
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
        }
        ?>
          </div>

          <!--Close col div-->
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
                  <td>CPU Cores</td>
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
          <div class='styled-table' style='width: auto; overflow:auto; margin: 1% 1% 5% 2%;'>
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
$db->Pselect("SELECT time, DATE_FORMAT(time, '%m/%d/%y, %r') AS 'time_formatted', customer_name, checkout_date, return_date, note FROM checkouts WHERE tagnumber = :tag ORDER BY time DESC", array(':tag' => $_GET["tagnumber"]));
if (arrFilter($db->get()) === 0) {
foreach ($db->get() as $key => $value1) {
echo "<tr>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['time_formatted']) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['customer_name']) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['checkout_date']) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['return_date']) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['note']) . "</td>" . PHP_EOL;
echo "</tr>" . PHP_EOL;
}
}
unset($value1);
?>
</tbody>
</table>
</div>

<div class='pagetitle'><h3>Job Log <i><a href='<?php if ($_GET["full-job-log"] == "1") { echo removeUrlVar($_SERVER["REQUEST_URI"], "full-job-log"); } else { echo addUrlVar($_SERVER["REQUEST_URI"], "full-job-log", "1"); } ?>'> <?php if ($_GET["full-job-log"] == "1") { echo "(Collapse Log View)"; } else { echo "(Expand Log View)"; } ?></a></i> - <u><?php echo htmlspecialchars($_GET["tagnumber"]); ?></u></h3></div>
<div class='styled-table' style="width: auto; max-height: 40%; overflow:auto; margin: 1% 1% 5% 1%;">
<table width="100%">
<thead>
<tr>
<th>Time</th>
<th>CPU Usage</th>
<th>Network Usage</th>
<th>Erase Mode</th>
<th>Erase Time Elapsed</th>
<th>Clone Time</th>
<th>BIOS Version</th>
</tr>
</thead>
<tbody>
<?php
$jobLogSQL = "SELECT DATE_FORMAT(time, '%m/%d/%y, %r') AS 'time_formatted', CONCAT(cpu_usage, '%') AS 'cpu_usage', CONCAT(network_usage, ' mbps') AS 'network_usage', 
    IF (erase_completed = 1, 'Yes', 'No') AS 'erase_completed', erase_mode, SEC_TO_TIME(erase_time) AS 'erase_time', IF (clone_completed = 1, 'Yes', 'No') AS 'clone_completed',
    SEC_TO_TIME(clone_time) AS 'clone_time', IF (clone_master = 1, '(Master Image)', '') AS 'clone_master_formatted', bios_version 
  FROM jobstats 
  WHERE tagnumber = :tagnumber AND (erase_completed = '1' OR clone_completed = '1') ";
if (isset($_GET["full-job-log"]) && $_GET["full-job-log"] == "1") {
  $jobLogSQL .= "ORDER BY time DESC ";
} else {
  $jobLogSQL .= "AND jobstats.time >= DATE_SUB(NOW(), INTERVAL 1 YEAR) ORDER BY time DESC LIMIT 10 ";
}

$db->Pselect($jobLogSQL, array(':tagnumber' =>  $_GET['tagnumber']));
if (arrFilter($db->get()) === 0) {
foreach ($db->get() as $key => $value1) {
echo "<tr>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['time_formatted']) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['cpu_usage']) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['network_usage']) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['erase_mode']) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['erase_time']) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['clone_time']) . " " . $value1["clone_master_formatted"] . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['bios_version']) . "</td>" . PHP_EOL;
echo "</tr>" . PHP_EOL;
}
}
unset($value1);
?>
</tbody>
</table>
</div>

<div class='pagetitle'><h3>Location Log <i><a href='<?php if ($_GET["full-loc-log"] == "1") { echo removeUrlVar($_SERVER["REQUEST_URI"], "full-loc-log"); } else { echo addUrlVar($_SERVER["REQUEST_URI"], "full-loc-log", "1"); } ?>'> <?php if ($_GET["full-loc-log"] == "1") { echo "(Collapse Log View)"; } else { echo "(Expand Log View)"; } ?></a></i> - <u><?php echo htmlspecialchars($_GET["tagnumber"]); ?></u></h3></div>
<div class='styled-table' style="width: auto; max-height: 40%; overflow:auto; margin: 1% 1% 5% 1%;">
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
$locLogSQL = "SELECT t1.time, DATE_FORMAT(t1.time, '%m/%d/%y, %r') AS 'time_formatted', locationFormatting(t1.location) AS 'location_formatted', 
  static_departments.department_readable, IF (t1.status = 1, 'No', 'Yes') AS 'status_formatted', IF (t1.disk_removed = 1, 'No', 'Yes') AS 'disk_removed_formatted',
  t1.note
  FROM (SELECT locations.time, locations.location, locations.department, locations.status, locations.disk_removed, locations.note, 
    ROW_NUMBER() OVER (PARTITION BY location ORDER BY time DESC) AS 'row_nums' FROM locations WHERE locations.tagnumber = :tagnumber ORDER BY locations.time DESC) t1 
  LEFT JOIN static_departments ON t1.department = static_departments.department ";
if (!isset($_GET["full-loc-log"]) || $_GET["full-loc-log"] != "1") {
  $locLogSQL .= "WHERE t1.row_nums <= 3 AND t1.time >= DATE_SUB(NOW(), INTERVAL 1 YEAR) ";
}

$db->Pselect($locLogSQL, array(':tagnumber' => htmlspecialchars_decode($_GET["tagnumber"])));
if (arrFilter($db->get()) === 0) {
foreach ($db->get() as $key => $value1) {
echo "<tr>" . PHP_EOL;
//Time formatted
echo "<td>" . htmlspecialchars($value1['time_formatted'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value1["location_formatted"]) . "'>" . htmlspecialchars($value1["location_formatted"]) . "</a></b></td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['department_readable'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['status_formatted'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['disk_removed_formatted'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
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
});
fetchHTML();
}, 3000)}
</script>

<div class="uit-footer">
<img src="/images/uh-footer.svg">
</div>
</body>
</html>