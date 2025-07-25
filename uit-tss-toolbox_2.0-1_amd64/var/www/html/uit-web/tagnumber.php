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

if ($_SESSION['authorized'] != "yes") {
  exit();
}

$dbPSQL = new dbPSQL();

$dbPSQL->Pselect("SELECT tagnumber FROM locations WHERE tagnumber = :tagnumber LIMIT 1", array(':tagnumber' => $_GET["tagnumber"]));
if (strFilter($dbPSQL->get()) === 1) {
  exit();
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://localhost:1411/api/refresh-client.php?password=CLIENT_PASSWD&tagnumber=" . htmlspecialchars($_GET["tagnumber"]) . "");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
?>

<?php
//POST data
if (isset($_POST["delete-image"]) && $_POST["delete-image"] == "1") {
  $dbPSQL->deleteImage($_POST["delete-image-uuid"], $_POST["delete-image-time"], $_POST["delete-image-tagnumber"]);
}

if (isset($_POST["image-primary"]) && $_POST["image-primary"] == "1" && strFilter($_POST["image-primary-uuid"]) === 0 && strFilter($_POST["image-primary-tagnumber"]) === 0) {
  $dbPSQL->Pselect("SELECT uuid FROM client_images WHERE primary_image = TRUE AND tagnumber = :tagnumber", array(':tagnumber' => $_POST["image-primary-tagnumber"]));
  if (strFilter($dbPSQL->get()) === 0) {
    foreach ($dbPSQL->get() as $key => $value) {
      $dbPSQL->updateImage("primary_image", false, $value["uuid"]);
    }
  }
  $dbPSQL->updateImage("primary_image", true, trim($_POST["image-primary-uuid"]));
  unset($value);
}

if (isset($_POST["rotate-image"]) && $_POST["rotate-image"] == "1") {
  $dbPSQL->Pselect("SELECT image FROM client_images WHERE uuid = :uuid AND time = :time AND tagnumber = :tagnumber", array(':uuid' => $_POST["rotate-image-uuid"], ':time' => $_POST["rotate-image-time"], ':tagnumber' => $_POST["rotate-image-tagnumber"]));
  foreach ($dbPSQL->get() as $key => $value) {
    //Rotate original
    $rotateImageData = base64_decode($value["image"]);
    $rotateImageObject = imagecreatefromstring($rotateImageData);
    imageinterlace($rotateImageObject, true);
    $rotatedImage = imagerotate($rotateImageObject, -90, 0);
    ob_start();
    imagejpeg($rotatedImage, NULL, 100);
    $rotateImageEncoded = base64_encode(ob_get_clean());
    imagedestroy($rotateImageObject);
    $dbPSQL->updateImage("image", $rotateImageEncoded, $_POST["rotate-image-uuid"]);
    unset($value);
  }

    $dbPSQL->Pselect("SELECT image FROM client_images WHERE uuid = :uuid AND time = :time AND tagnumber = :tagnumber", array(':uuid' => $_POST["rotate-image-uuid"], ':time' => $_POST["rotate-image-time"], ':tagnumber' => $_POST["rotate-image-tagnumber"]));
    foreach ($dbPSQL->get() as $key => $value) {
      //Rotate thumbnail
      $rotateThumbnailData = base64_decode($value["image"]);
      $rotateThumbnailObject = imagecreatefromstring($rotateThumbnailData);
      imageinterlace($rotateThumbnailObject, true);
      ob_start();
      imagejpeg($rotateThumbnailObject, NULL, 30);
      $rotateThumbnailEncoded = base64_encode(ob_get_clean());
      imagedestroy($rotateThumbnailObject);

      $dbPSQL->updateImage("thumbnail", $rotateThumbnailEncoded, $_POST["rotate-image-uuid"]);
      unset($value);
    }
  unset($value);
}

if (isset($_FILES) && strFilter($_FILES) === 0) {
  foreach ($_FILES["userfile"]["tmp_name"] as $key => $tmp_name) {
    //Check for accepted mime types
    $uploadFileMimeType = mime_content_type($_FILES["userfile"]["tmp_name"][$key]);
    $fileAllowedMimes = ['image/png', 'image/jpeg', 'image/webp', 'image/avif', 'video/mp4', 'video/quicktime'];
    if (!in_array($uploadFileMimeType, $fileAllowedMimes)) {
      $imageUploadError = array(2, $_FILES["userfile"]["name"][$key]);
    } else {
      $fh = fopen($_FILES["userfile"]["tmp_name"][$key], 'rb');
      //Get raw file data
      $rawFileData = file_get_contents($_FILES["userfile"]["tmp_name"][$key]);
      //Get file hash
      $imageHash = md5($rawFileData);
      //Check if hash already in DB
      $dbPSQL->Pselect("SELECT uuid FROM client_images WHERE md5_hash = :md5_hash AND tagnumber = :tagnumber", array(':md5_hash' => $imageHash, ':tagnumber' => $_GET["tagnumber"]));
      if (strFilter($dbPSQL->get()) === 1) {
        unset($imageUUID);
        $imageUUID = uniqid("image-", true);
        //Convert all images to jpeg
        if (preg_match('/^image.*/', $uploadFileMimeType) === 1) {
          // //Read image exif data
          // $exifArr = exif_read_data($fh);
          // fclose($fh);
          //Create jpeg and check if can convert. Create base64 string.
          $imageObject = imagecreatefromstring($rawFileData);
          if ($imageObject !== false) {
            $imageResolution = imagesx($imageObject) . "x" . imagesy($imageObject);

            imageinterlace($imageObject, true);

            //Main jpeg
            ob_start();
            imagejpeg($imageObject, NULL, 100);
            $imageBuffer = ob_get_clean();
            $imageFileConverted = base64_encode($imageBuffer);
            unset($imageBuffer);

            //Thumbnail
            ob_start();
            imagejpeg($imageObject, NULL, 30);
            $thumbnailBuffer = ob_get_clean();
            $imageFileCompressed = base64_encode($thumbnailBuffer);
            $finfo = new finfo(FILEINFO_MIME);
            $mimeType = explode(';', $finfo->buffer($thumbnailBuffer));
            $mimeType = trim($mimeType[0]);
            $finfo = null;
            unset($thumbnailBuffer);
          } else {
            $imageUploadError = array(2, $_FILES["userfile"]["name"][$key]);
          }
          imagedestroy($imageObject);
          //Convert all videos to mp4. Outputs base64 string.
        } elseif (preg_match('/^video.*/', $uploadFileMimeType) === 1) {
          $transcodeFile = uniqid("uit-transcode-", true);
          $file = fopen("/var/www/html/uit-web/transcode/" . $transcodeFile, 'c');
          fwrite($file, $rawFileData);
          fclose($file);
          $imageFileConverted = shell_exec("bash /var/www/html/uit-web/bash/convert-to-mp4" . " " . escapeshellarg("WEB_SVC_PASSWD") . " " . $transcodeFile . " " . "normal-quality");
          $mimeType = mime_content_type('/var/www/html/uit-web/transcode/' . $transcodeFile);
          //$imageFileCompressed = shell_exec("bash /var/www/html/uit-web/bash/convert-to-mp4" . " " . escapeshellarg("WEB_SVC_PASSWD") . " " . $transcodeFile . " " . "low-quality");
        }

          $dbPSQL->insertImage($imageUUID, $time, $_GET["tagnumber"]);
          $dbPSQL->updateImage("image", $imageFileConverted, $imageUUID);
          $dbPSQL->updateImage("thumbnail", $imageFileCompressed, $imageUUID);
          $dbPSQL->updateImage("md5_hash", $imageHash, $imageUUID);
          $dbPSQL->updateImage("filename", $_FILES["userfile"]["name"][$key], $imageUUID);
          $dbPSQL->updateImage("filesize", round($_FILES["userfile"]["size"][$key] / 1000000, 3), $imageUUID);
          $dbPSQL->updateImage("note", $_POST["image-note"], $imageUUID);
          $dbPSQL->updateImage("mime_type", $mimeType, $imageUUID);
          //$dbPSQL->updateImage("hidden", "0", $_POST["delete-image"]);
          if (preg_match('/^image.*/', $uploadFileMimeType) === 1) {
            //$dbPSQL->updateImage("exif_timestamp", date("Y-m-d H:i:s.v", $exifArr["DateTimeOriginal"]), $imageUUID);
            $dbPSQL->updateImage("resolution", $imageResolution, $imageUUID);
          }
          unset($imageObject);
          unset($imageFileConverted);
      } else {
        $imageUploadError = array(1, $_FILES["userfile"]["name"][$key]);
      }
    }
  }
}


if (isset($_POST["job_queued_tagnumber"])) {
  if (strFilter($_POST["job_queued"]) === 0) {
    $dbPSQL->updateRemote($_POST["job_queued_tagnumber"], "job_queued", $_POST["job_queued"]);
  }
  unset($_POST["job_queued_form"]);
}

if ($_POST) {
  header( "Location: {$_SERVER['REQUEST_URI']}", true, 303 );
  unset($_POST);
}
?>

<!DOCTYPE html>
  <head>
    <meta charset='UTF-8'>
    <link rel='stylesheet' type='text/css' href='/css/main.css' />
    <title><?php echo htmlspecialchars($_GET['tagnumber']) . " - UIT Client Mgmt"; ?></title>
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    <script src="/js/init.js?<?php echo filemtime('js/init.js'); ?>"></script>
  </head>
  <!--<body onload="fetchHTML()">-->
  <body>
  <?php include('/var/www/html/uit-web/php/navigation-bar.php'); ?>

  <div class='row'>
    <div class='column'>
  <div class='pagetitle'><h1>Client Lookup (<?php echo htmlspecialchars($_GET['tagnumber']); ?>)</h1></div>
</div>
</div>


<?php
// Get most client data - main sql query
unset($sql);
$sql = "SELECT TO_CHAR(t10.time, 'MM/DD/YY HH12:MI:SS AM') AS location_time_formatted,
(CASE WHEN t3.time = t10.time THEN 1 ELSE 0 END) AS placeholder_bool,
jobstats.time AS jobstatsTime, locations.tagnumber, locations.system_serial, locations.department, 
locationFormatting(locations.location) AS location, 
(CASE WHEN locations.status = TRUE THEN 'Broken' ELSE 'Yes' END) AS status_formatted, locations.status AS locations_status, t2.department_readable, t3.note AS most_recent_note,
locations.note, TO_CHAR(t3.time, 'MM/DD/YY HH12:MI:SS AM') AS note_time_formatted, 
(CASE WHEN locations.disk_removed = TRUE THEN 'Yes' ELSE 'No' END) AS disk_removed_formatted, locations.disk_removed,
(CASE 
  WHEN jobstats.etheraddress IS NOT NULL AND system_data.system_model NOT IN ('Latitude 7400', 'Latitude 5289') THEN jobstats.etheraddress 
  WHEN jobstats.etheraddress IS NOT NULL AND system_data.system_model IN ('Latitude 7400', 'Latitude 5289') THEN 'No ethernet NIC' 
  ELSE 'Unknown' 
  END) AS etheraddress_formatted, 
(CASE WHEN system_data.wifi_mac IS NOT NULL THEN system_data.wifi_mac ELSE 'Unknown' END) AS wifi_mac_formatted, 
system_data.chassis_type, 
(CASE
  WHEN system_data.system_manufacturer IS NOT NULL AND system_data.system_model IS NOT NULL THEN CONCAT(system_data.system_manufacturer, ' - ', system_data.system_model)
  WHEN system_data.system_manufacturer IS NULL AND system_data.system_model IS NOT NULL THEN system_data.system_model
  WHEN system_data.system_manufacturer IS NOT NULL AND system_data.system_model IS NULL THEN system_data.system_manufacturer
  ELSE NULL
  END) AS system_model_formatted,
system_data.cpu_model,
(CASE 
  WHEN system_data.cpu_maxspeed IS NOT NULL THEN CONCAT('(Max ', ROUND((system_data.cpu_maxspeed / 1000), 2), ' Ghz)') 
  ELSE NULL 
  END) AS cpu_maxspeed_formatted, 
(CASE 
  WHEN system_data.cpu_threads > system_data.cpu_cores THEN CONCAT(system_data.cpu_cores, ' cores/', system_data.cpu_threads, ' threads (Multithreaded)') 
  WHEN system_data.cpu_threads = system_data.cpu_cores THEN CONCAT(system_data.cpu_cores, ' cores (Not Multithreaded)')
  ELSE NULL
  END) AS multithreaded, 
(CASE 
WHEN t8.ram_capacity IS NOT NULL AND t8.ram_speed IS NOT NULL THEN CONCAT(t8.ram_capacity, ' GB (', t8.ram_speed, ' MHz)')
WHEN t8.ram_capacity IS NOT NULL AND t8.ram_speed IS NULL THEN CONCAT(t8.ram_capacity, ' GB')
END) AS ram_capacity_formatted,
t4.disk_model, CONCAT(t4.disk_size, 'GB') AS disk_size, t4.disk_type, t4.disk_serial, 
t5.identifier, t5.recovery_key, 
(CASE WHEN client_health.battery_health IS NOT NULL THEN CONCAT(client_health.battery_health, '%') ELSE NULL END) AS battery_health_formatted, (CASE WHEN client_health.disk_health IS NOT NULL THEN CONCAT(client_health.disk_health, '%') ELSE NULL END) AS disk_health, 
(CASE 
  WHEN client_health.avg_erase_time IS NOT NULL THEN CONCAT(client_health.avg_erase_time, ' mins')
  ELSE NULL 
  END) AS avg_erase_time, 
(CASE 
  WHEN client_health.avg_clone_time IS NOT NULL THEN CONCAT(client_health.avg_clone_time, ' mins')
  ELSE NULL
  END) AS avg_clone_time,
client_health.all_jobs, 
(CASE 
  WHEN remote.network_speed IS NOT NULL 
  THEN CONCAT(remote.network_speed, ' mbps') 
  ELSE NULL 
  END) AS network_speed_formatted, 
client_health.bios_updated, 
(CASE 
  WHEN client_health.bios_updated = TRUE AND client_health.bios_version IS NOT NULL THEN CONCAT('Updated ', '(', client_health.bios_version, ')') 
  WHEN client_health.bios_updated = FALSE AND client_health.bios_version IS NOT NULL THEN CONCAT('Out of date ', '(', client_health.bios_version, ')') 
  ELSE 'Unknown BIOS Version' 
  END) AS bios_updated_formatted, 
(CASE
WHEN t4.disk_writes IS NOT NULL AND t4.disk_reads IS NOT NULL THEN CONCAT(t4.disk_writes, ' TBW/', t4.disk_reads, 'TBR')
WHEN t4.disk_writes IS NOT NULL AND t4.disk_reads IS NULL THEN CONCAT(t4.disk_writes, ' TBW')
WHEN t4.disk_reads IS NULL AND t4.disk_reads IS NOT NULL THEN CONCAT(t4.disk_reads, ' TBW')
ELSE NULL
END) AS disk_tbw_formatted,
CONCAT(t4.disk_writes, ' TBW') AS disk_writes, CONCAT(t4.disk_reads, ' TBR') AS disk_reads, CONCAT(t4.disk_power_on_hours, ' hrs') AS disk_power_on_hours,
t4.disk_power_cycles, t4.disk_errors, locations.domain, (CASE WHEN locations.domain IS NOT NULL THEN static_domains.domain_readable ELSE 'Not Joined' END) AS domain_readable,
(CASE 
  WHEN client_health.os_installed = TRUE AND client_health.os_name IS NOT NULL AND NOT client_health.os_name = 'Unknown OS' THEN CONCAT(client_health.os_name, ' (Imaged on ', TO_CHAR(t6.time, 'MM/DD/YY HH12:MI:SS AM'), ')') 
  WHEN client_health.os_installed = TRUE AND NOT client_health.os_name = 'Unknown OS' THEN client_health.os_name 
  ELSE client_health.os_name 
  END) AS os_installed_formatted,
checkouts.customer_name, checkouts.checkout_date, checkouts.checkout_bool, client_health.tpm_version
FROM locations
LEFT JOIN client_health ON locations.tagnumber = client_health.tagnumber
LEFT JOIN jobstats ON (locations.tagnumber = jobstats.tagnumber AND jobstats.time IN (SELECT MAX(time) AS time FROM jobstats WHERE tagnumber IS NOT NULL AND system_serial IS NOT NULL AND (host_connected = TRUE OR (uuid LIKE 'techComm-%' AND etheraddress IS NOT NULL)) GROUP BY tagnumber))
LEFT JOIN system_data ON locations.tagnumber = system_data.tagnumber
LEFT JOIN remote ON locations.tagnumber = remote.tagnumber
LEFT JOIN (SELECT department, department_readable FROM static_departments) t2
ON locations.department = t2.department
LEFT JOIN (SELECT tagnumber, time, note FROM locations WHERE time IN (SELECT MAX(time) FROM locations WHERE note IS NOT NULL GROUP BY tagnumber)) t3
ON locations.tagnumber = t3.tagnumber
LEFT JOIN (SELECT tagnumber, disk_model, disk_serial, disk_size, disk_type, disk_writes, disk_reads, disk_power_on_hours, disk_power_cycles, (CASE WHEN disk_errors IS NOT NULL THEN disk_errors ELSE 0 END) AS disk_errors FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE disk_type IS NOT NULL AND tagnumber IS NOT NULL GROUP BY tagnumber)) t4 
ON locations.tagnumber = t4.tagnumber
LEFT JOIN (SELECT tagnumber, identifier, recovery_key FROM bitlocker) t5 
ON locations.tagnumber = t5.tagnumber
LEFT JOIN (SELECT time, tagnumber, clone_image, row_nums FROM (SELECT time, tagnumber, clone_image, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM jobstats WHERE tagnumber IS NOT NULL AND clone_completed = TRUE AND clone_image IS NOT NULL) s6 WHERE s6.row_nums = 1) t6
ON locations.tagnumber = t6.tagnumber
LEFT JOIN static_image_names ON t6.clone_image = static_image_names.image_name
LEFT JOIN (SELECT tagnumber, ram_capacity, ram_speed FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE ram_capacity IS NOT NULL AND ram_speed IS NOT NULL AND tagnumber IS NOT NULL GROUP BY tagnumber)) t8
ON locations.tagnumber = t8.tagnumber
INNER JOIN (SELECT MAX(time) AS time FROM locations WHERE tagnumber IS NOT NULL AND system_serial IS NOT NULL GROUP BY tagnumber) t10
ON locations.time = t10.time
LEFT JOIN static_domains ON locations.domain = static_domains.domain
LEFT JOIN checkouts ON locations.tagnumber = checkouts.tagnumber AND checkouts.time IN (SELECT s11.time FROM (SELECT time, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM checkouts) s11 WHERE s11.row_nums = 1)
WHERE locations.tagnumber IS NOT NULL and locations.system_serial IS NOT NULL
AND locations.tagnumber = :tagnumber";

$dbPSQL->Pselect($sql, array(':tagnumber' => htmlspecialchars_decode($_GET["tagnumber"])));
if (arrFilter($dbPSQL->get()) === 0) {
foreach ($dbPSQL->get() as $key => $value) {
?>



    <?php echo "<div class='page-content'><h3>Update Queued Job and Location Data - <u>" . htmlspecialchars($_GET["tagnumber"]) . "</u></h3></div>"; ?>

    <div class='row'>
      <div class='column'>
        <div class='location-form'>
          <div class='row'>
            <div class='column' style='border-right: 1px solid black;'>
              <div name='curJob' id='curJob'>
                <p>Real-time Job Status: </p>
                <?php
                if (strFilter($value["tagnumber"]) === 0) {
                  echo "<p id='bios_kernel_updated'></p>";
                  echo "<p id='remote_status'></p>";
                } else {
                  echo "<p>Missing required info. Please plug into laptop server to gather information.
                  To update the location, please update it from the <a href='/locations.php'>locations page</a></p>";
                }
                ?>
              </div>
              <div>
                <form name="job_queued_form" method="post">
                  <div style='padding-left: 0;'><label for='tagnumber'>Enter a job to queue: </label></div>
                  <input type='hidden' id='job_queued_tagnumber' name='job_queued_tagnumber' value='<?php echo htmlspecialchars($_GET["tagnumber"]); ?>'>
                  <div style='padding-left: 0;'><select name="job_queued">
                    <?php
                    // Get/set current jobs.
                    if ($_GET['tagnumber'] && strFilter($value["tagnumber"]) === 0) {
                      $dbPSQL->Pselect("SELECT tagnumber FROM remote WHERE tagnumber = :tagnumber", array(':tagnumber' => $_GET["tagnumber"]));
                      if (arrFilter($dbPSQL->get()) === 0 ) {
                        $dbPSQL->Pselect("SELECT (CASE WHEN remote.job_queued IS NOT NULL THEN remote.job_queued ELSE '' END) AS job_queued,
                          (CASE WHEN remote.job_queued IS NOT NULL THEN static_job_names.job_readable ELSE 'No Job' END) AS job_queued_formatted,
                          (CASE WHEN remote.job_active = TRUE THEN 'In Progress: ' ELSE 'Queued: ' END) AS job_status_formatted
                          FROM remote 
                          INNER JOIN static_job_names 
                          ON remote.job_queued = static_job_names.job 
                          WHERE remote.tagnumber = :tagnumber", array(':tagnumber' => htmlspecialchars_decode($_GET['tagnumber'])));
                        if (arrFilter($dbPSQL->get()) === 0) {
                          foreach ($dbPSQL->get() as $key => $value1) {
                            echo "<option value='" . htmlspecialchars($value1["job_queued"]) . "'>" . htmlspecialchars($value1["job_status_formatted"]) . htmlspecialchars($value1["job_queued_formatted"]) . "</option>";
                          }
                          unset($value1);
                        }
                        echo "<option value=''>--Select Job Below--</option>" . PHP_EOL;
                        $dbPSQL->Pselect("SELECT job, job_readable FROM static_job_names WHERE job_html_bool = TRUE AND NOT job IN (SELECT (CASE WHEN remote.job_queued IS NULL THEN '' ELSE remote.job_queued END) FROM remote WHERE remote.tagnumber = :tagnumber) ORDER BY job_rank ASC", array(':tagnumber' => $_GET["tagnumber"]));
                        foreach ($dbPSQL->get() as $key => $value2) {
                          echo "<option value='" . htmlspecialchars($value2["job"]) . "'>" . htmlspecialchars($value2["job_readable"]) . "</option>";
                        }
                        unset($value2);
                      }
                    } else {
                      echo "<option>ERR: " . htmlspecialchars($_GET["tagnumber"]) . " missing info :((</option>";
                    }
                    ?>
                  </select>
                  <button style='background-color:rgba(0, 179, 136, 0.30);' type="submit">Queue Job</button></div>
                </form>
              </div>
            </div>
            <div class='column' style='width: 50%; height: 100%;'>
              <div><p id='live_image_time'></p></div>
              <div><img id='live_image' style='position: relative; cursor: pointer; max-width: 100%; max-height: 100%;' onclick="window.open('/view-images.php?live_image=1&tagnumber=<?php echo htmlspecialchars($_GET['tagnumber']); ?>', '_blank')" src='' loading='lazy'/></div>
            </div>
          </div>
        </div>
          

        <div class='location-form'>
          <form enctype="multipart/form-data" method="POST">
            <div><p>Upload Image: </p></div>
            <!--<div><input name="userfile" type="file" onchange='this.form.submit();' accept="image/png, image/jpeg, image/webp, image/avif" /></div>-->
            <div><input name="userfile[]" type="file" accept="image/png, image/jpeg, image/webp, image/avif, video/mp4, video/quicktime" multiple /></div>
            <div><input name="image-note" type="text" autocapitalize='sentences' autocomplete='off' placeholder="Add Image Description..."></div>
            <div><button style="background-color:rgba(0, 179, 136, 0.30);" type="submit">Upload Image(s)</button></div>
          </form>
            <?php 
            if ($imageUploadError[0] === 1) {
              echo "<div><p style='color: red;'><b>Error: File already uploaded - \"" . htmlspecialchars($imageUploadError[1]) . "\"</b></p></div>";
            } elseif ($imageUploadError[0] == 2) {
              echo "<div><p style='color: red;'><b>Error: Incorrect file format - \"" . htmlspecialchars($imageUploadError[1]) . "\"</b></p></div>";
            }
            ?>
        </div>

        <div class='pagetitle'><h3></u></h3></div>
        <div name='updateDiv1' id='updateDiv1' class='styled-table' style='width: auto; height: auto; overflow:auto; margin: 1% 1% 3% 2%;'>
          <table width='100%;'>
            <thead>
              <tr>
              <th>General Client Info - <u><?php echo htmlspecialchars($_GET["tagnumber"]); ?></th>
              <th></th>
              </tr>
            </thead>
            <tbody>
                <tr>
                  <td>Current Location</td>
                  <td>
                    <?php
                    if ($value["locations_status"] === 1) {
                      echo "<p><b style='color: #C8102E'>[REPORTED BROKEN]</b> on " . htmlspecialchars($value["location_time_formatted"]) . "</b></p>";
                    }
                    ?>
                    <?php
                    // CHANGE TYPE LATER //
                    if ($value["checkout_bool"] === 1) {
                      echo "<p><b>[CHECKOUT]</b> - Checked out to <b>" . htmlspecialchars($value["customer_name"]) . "</b> on <b>" . htmlspecialchars($value["checkout_date"]) . "</b></p>";
                    }
                    ?>
                    <p>"<?php echo trim(htmlspecialchars($value["location"])); ?>"</p><p><a style='cursor: pointer;' onclick='newLocationWindow("<?php echo trim(htmlspecialchars($value["location"])); ?>", "<?php echo trim(htmlspecialchars($value["tagnumber"])); ?>");'><img class='icon' src='/images/new-tab.svg'></img><i>(Click to Update Location)</i></a></p>
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
                    <p>"<?php echo trim(htmlspecialchars($value["department_readable"])); ?>"</p><p><a style='cursor: pointer;' onclick='newLocationWindow("<?php echo trim(htmlspecialchars($value["location"])); ?>", "<?php echo trim(htmlspecialchars($value["tagnumber"])); ?>", "<?php echo trim(htmlspecialchars($value["department"])); ?>");'><img class='icon' src='/images/new-tab.svg'></img><i>(Click to Update Department)</i></a></p>
                  </td>
                </tr>
                <tr>
                  <td>AD Domain</td>
                  <td>
                    <p>"<?php echo trim(htmlspecialchars($value["domain_readable"])); ?>"</p><p><a style='cursor: pointer;' onclick='newLocationWindow("<?php echo trim(htmlspecialchars($value["location"])); ?>", "<?php echo trim(htmlspecialchars($value["tagnumber"])); ?>", "", "<?php echo trim(htmlspecialchars($value["domain"])); ?>");'><img class='icon' src='/images/new-tab.svg'></img><i>(Click to Update Domain)</i></a></p>
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
                        echo "<table><tr><td>" . htmlspecialchars($value["wifi_mac_formatted"]) . " (Wi-Fi)</td></tr><tr><td>" . htmlspecialchars($value["etheraddress_formatted"]) . " (Ethernet)</td></tr></table>";
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
                  <td><?php echo htmlspecialchars($value['network_speed_formatted']); ?></td>
                </tr>
            </tbody>
          </table>
        </div>
        <!-- Close column div-->
      </div>

      <div class='column'>
        <?php
        $dbPSQL->Pselect("SELECT COUNT(tagnumber) AS count FROM client_images WHERE hidden = FALSE AND tagnumber = :tagnumber", array(':tagnumber' => $_GET["tagnumber"]));
        $totalImages = $dbPSQL->nested_get()["count"];
        $dbPSQL->Pselect("SELECT uuid, time, tagnumber, filename, filesize, resolution, mime_type, (CASE WHEN mime_type LIKE 'video%' THEN image ELSE thumbnail END) AS thumbnail, note, primary_image, TO_CHAR(time, 'MM/DD/YY HH12:MI:SS AM') AS time_formatted, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM client_images WHERE tagnumber = :tagnumber ORDER BY primary_image DESC, time DESC LIMIT 6", array(':tagnumber' => $_GET["tagnumber"]));
        if (strFilter($dbPSQL->get()) === 0) {
          echo "<div class='page-content'><a style='color: black;' href='/view-images.php?view-all=1&tagnumber=" . htmlspecialchars($_GET["tagnumber"]) . "' target='_blank'>[<b style='color: #C8102E;'>View All " . htmlspecialchars($totalImages)  . " Images</b>]</a></div>";
          echo "<div class='grid-container'>";
          foreach ($dbPSQL->get() as $key => $image) {
              $dbPSQL->Pselect("SELECT ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM client_images WHERE tagnumber = :tagnumber AND hidden = FALSE", array(':tagnumber' => $_GET["tagnumber"]));
              $totalRows = $dbPSQL->get_rows();
              echo "<div class='grid-box'>";
              echo "<div style='display: table; clear: both; width: 100%;'>";
              //Delete image form
              echo "<div style='margin: 0 0 1em 0; padding: 0; width: fit-content; float: left;'>";
              echo "<form method='post'>";
              echo "<input type='hidden' name='delete-image' value='1'>";
              echo "<input type='hidden' name='delete-image-uuid' value='" . htmlspecialchars($image["uuid"]) . "'>";
              echo "<input type='hidden' name='delete-image-time' value='" . htmlspecialchars($image["time"]) . "'>";
              echo "<input type='hidden' name='delete-image-tagnumber' value='" . htmlspecialchars($image["tagnumber"]) . "'>";
              echo "<div style='position: relative; top: 0; left: 0;'>";
              echo "<button type=submit style='font-size: 1em; background-color: transparent; text-decoration: underline; border: none; margin: 0; padding: 0; cursor: pointer;' onclick='this.form.submit()' value='delete'><img class='icon' src='/images/trash.svg'>[<b style='color: #C8102E;'>delete</b>]</button></form></div></div>";

              
              //Rotate image form
              if (preg_match('/^image\/.*/', $image["mime_type"]) === 1) {
                echo "<div style='margin: 0 0 1em 0; padding: 0; width: fit-content; float: right;'>";
                echo "<div style='margin: 0 0 1em 0;'><a style='color: black;' href='/view-images.php?download=1&tagnumber=" . htmlspecialchars($_GET["tagnumber"]) . "&uuid=" . $image["uuid"] . "' target='_blank'><img class='icon' src='/images/download.svg'></img>[<b style='color: #C8102E;'>download</b>]</a></div>";
                echo "<div style='margin: 0 0 1em 0;'><form method='post'>";
                echo "<input type='hidden' name='rotate-image' value='1'>";
                echo "<input type='hidden' name='rotate-image-uuid' value='" . htmlspecialchars($image["uuid"]) . "'>";
                echo "<input type='hidden' name='rotate-image-time' value='" . htmlspecialchars($image["time"]) . "'>";
                echo "<input type='hidden' name='rotate-image-tagnumber' value='" . htmlspecialchars($image["tagnumber"]) . "'>";
                echo "<div style='position: relative; top: 0; right: 0;'>";
                echo "<button type=submit style='font-size: 1em; background-color: transparent; text-decoration: underline; border: none; margin: 0; padding: 0; cursor: pointer;' onclick='this.form.submit()' value='rotate'><img class='icon' src='/images/rotate.svg'>[<b style='color: #C8102E;'>rotate</b>]</button></form></div></div>";
              }
              if (preg_match('/^video\/.*/', $image["mime_type"]) === 1) {
                echo "<div style='margin: 0 0 1em 0; padding: 0; width: fit-content; float: right;'>";
                echo "<div style='position: relative; top: 0; right: 0; margin: 0 0 1em 0;'>";
                echo "<a style='color: black;' href='/view-images.php?download=1&tagnumber=" . htmlspecialchars($_GET["tagnumber"]) . "&uuid=" . $image["uuid"] . "' target='_blank'><img class='icon' src='/images/download.svg'></img>[<b style='color: #C8102E;'>download</b>]</a></div>";
              }


              if ($image["primary_image"] === false) {
                echo "<div><form method='post'>";
                echo "<input type='hidden' name='image-primary-uuid' value='" . htmlspecialchars($image["uuid"]) . "'>";
                echo "<input type='hidden' name='image-primary-tagnumber' value='" . htmlspecialchars($image["tagnumber"]) . "'>";
                echo "<input type='hidden' name='image-primary' value='1'>";
                echo "<button type=submit style='font-size: 1em; background-color: transparent; text-decoration: underline; border: none; margin: 0; padding: 0; cursor: pointer;' onclick='this.form.submit()'><img class='icon' src='/images/pin.svg'></img>[<b style='color: #C8102E;'>pin</b>]</button>";
                echo "</form></div>";
              } else {
                echo "<div><p>[<u><b>pinned</b></u>]</p></div>";
              }

              echo "</div></div>";

              echo "<div><p>(" . htmlspecialchars($image["row_nums"]) . "/" . htmlspecialchars($totalRows) . ") Upload Timestamp: " . htmlspecialchars($image["time_formatted"]) . "</p>";
              echo "<div><p>File Info: \"" . htmlspecialchars($image["filename"]) . "\" (" . htmlspecialchars($image["resolution"]) . ", " . htmlspecialchars($image["filesize"]) . " MB" . ")</p></div>";
              if (strFilter($image["note"]) === 0) {
                echo "<div><p><b>Note: </b> " . htmlspecialchars($image["note"]) . "</p></div>";
              }
              echo "<div style='padding: 1em 1px 1px 1px;'>";
              if (preg_match('/^image\/.*/', $image["mime_type"]) === 1) {
                echo "<img id='" . htmlspecialchars($image["uuid"]) . "' style='max-height:100%; max-width:100%; cursor: pointer;' onclick=\"window.open('/view-images.php?view=1&tagnumber=" . $image["tagnumber"] . "&uuid=" . htmlspecialchars($image["uuid"]) . "', '_blank');\" src='data:image/jpeg;base64," . $image["thumbnail"] . "' loading='lazy'/>";
              } elseif (preg_match('/^video\/.*/', $image["mime_type"]) === 1) {
                echo "<video preload='metadata' style='max-height:100%; max-width:100%;' controls><source type='video/mp4' src='data:video/mp4;base64," . $image["thumbnail"] . "' /></video>";
              }

              echo "</div>";
              echo "</div>";
              echo "</div>";
            }
          unset($image);
        } else {
          $dbPSQL->Pselect("SELECT system_model FROM system_data WHERE tagnumber = :tagnumber", array(':tagnumber' => htmlspecialchars_decode($_GET['tagnumber'])));
          if (arrFilter($dbPSQL->get()) === 0) {
            echo "<div class='laptop-images'>";
            foreach ($dbPSQL->get() as $key => $image) {
              if ($image["system_model"] === "Aspire T3-710") {
                echo "<img src='/images/aspireT3710.jpg'>" . PHP_EOL;
              } elseif ($image["system_model"] === "HP ProBook 450 G6") {
                echo "<img src='/images/hpProBook450G6.avif'>" . PHP_EOL;
              } elseif ($image["system_model"] === "Latitude 3500") {
                echo "<img src='/images/Latitude3500.avif'>" . PHP_EOL;
              } elseif ($image["system_model"] === "Latitude 3560") {
                echo "<img src='/images/Latitude3560.jpg'>" . PHP_EOL;
              } elseif ($image["system_model"] === "Latitude 3590") {
                echo "<img src='/images/latitude3590.jpeg'>" . PHP_EOL;
              } elseif ($image["system_model"] === "Latitude 5289") {
                echo "<img src='/images/latitude5289.jpg'>" . PHP_EOL;
              } elseif ($image["system_model"] === "Latitude 5480") {
                echo "<img src='/images/latitude5480.jpg'>" . PHP_EOL;
              } elseif ($image["system_model"] === "Latitude 5590") {
                echo "<img src='/images/latitude5590.jpg'>" . PHP_EOL;
              } elseif ($image["system_model"] === "Latitude 7400") {
                echo "<img src='/images/latitude7400.avif'>" . PHP_EOL;
              } elseif ($image["system_model"] === "Latitude 7430") {
                echo "<img src='/images/latitude7430.jpg'>" . PHP_EOL;
              } elseif ($image["system_model"] === "Latitude 7480") {
                echo "<img src='/images/latitude7480.jpg'>" . PHP_EOL;
              } elseif ($image["system_model"] === "Latitude 7490") {
                echo "<img src='/images/latitude7490.jpg'>" . PHP_EOL;
              } elseif ($image["system_model"] === "Latitude E6430") {
                echo "<img src='/images/latitudeE6430.jpg'>" . PHP_EOL;
              } elseif ($image["system_model"] === "Latitude E7470") {
                echo "<img src='/images/latitudeE7470.webp'>" . PHP_EOL;
              } elseif ($image["system_model"] === "OptiPlex 7000") {
                echo "<img src='/images/optiplex7000.avif'>" . PHP_EOL;
              } elseif ($image["system_model"] === "OptiPlex 7460 AIO") {
                echo "<img src='/images/optiplex7460AIO.avif'>" . PHP_EOL;
              } elseif ($image["system_model"] === "OptiPlex 780") {
                echo "<img src='/images/optiplex780.jpg'>" . PHP_EOL;
              } elseif ($image["system_model"] === "OptiPlex 790") {
                echo "<img src='/images/optiplex790.avif'>" . PHP_EOL;
              } elseif ($image["system_model"] === "OptiPlex 9010 AIO") {
                echo "<img src='/images/optiplex9010AIO.webp'>" . PHP_EOL;
              } elseif ($image["system_model"] === "Surface Book") {
                echo "<img src='/images/surfaceBook.jpg'>" . PHP_EOL;
              } elseif ($image["system_model"] === "Surface Pro") {
                echo "<img src='/images/surfacePro.jpg'>" . PHP_EOL;
              } elseif ($image["system_model"] === "Surface Pro 4") {
                echo "<img src='/images/surfacePro4.jpg'>" . PHP_EOL;
              } elseif ($image["system_model"] === "XPS 15 9560") {
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
                  <td><?php echo htmlspecialchars($value["multithreaded"]) . " " . htmlspecialchars($value["cpu_maxspeed_formatted"]); ?></td>
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
                  <td><?php echo htmlspecialchars($value['all_jobs']); ?></td>
                </tr>
                <tr>
                  <td>Erase Avg. Time</td>
                  <td><?php echo htmlspecialchars($value['avg_erase_time']); ?></td>
                </tr>
                <tr>
                  <td>Clone Avg. Time</td>
                  <td><?php echo htmlspecialchars($value['avg_clone_time']); ?></td>
                </tr>
                <tr>
                  <td>Battery Health</td>
                  <td><?php echo htmlspecialchars($value['battery_health_formatted']); ?></td>
                </tr>
                <tr>
                  <td>Disk TBW/TBR</td>
                  <td><?php echo htmlspecialchars($value['disk_tbw_formatted']); ?></td>
                </tr>
                <tr>
                  <td>Disk Power on Hours</td>
                  <td><?php echo htmlspecialchars($value['disk_power_on_hours']); ?></td>
                </tr>
                <tr>
                  <td>Disk Power Cycles</td>
                  <td><?php echo htmlspecialchars($value['disk_power_cycles']); ?></td>
                </tr>
                <tr>
                  <td>Disk Errors</td>
                  <td><?php echo htmlspecialchars($value['disk_errors']); ?></td>
                </tr>
                <tr>
                  <td>Disk Health</td>
                  <td><?php echo htmlspecialchars($value['disk_health']); ?></td>

                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
  <?php
  // Close if & foreach statements for this row
  }
}
  unset($value);
  ?>


<div class='pagetitle'><h3>Checkout Log <i><a href='<?php if ($_GET["full-checkout-log"] == "1") { echo removeUrlVar($_SERVER["REQUEST_URI"], "full-checkout-log"); } else { echo addUrlVar($_SERVER["REQUEST_URI"], "full-checkout-log", "1"); } ?>'> <?php if ($_GET["full-checkout-log"] == "1") { echo "(Collapse Log View)"; } else { echo "(Expand Log View)"; } ?></a></i> - <u><?php echo htmlspecialchars($_GET["tagnumber"]); ?></u></h3></div>
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
$checkoutLogSql = "SELECT time, TO_CHAR(time, 'MM/DD/YY HH12:MI:SS AM') AS time_formatted, customer_name, checkout_date, return_date, note FROM checkouts WHERE tagnumber = :tag ";

if (isset($_GET["full-checkout-log"]) || $_GET["full_checkout_log"] == "1") {
    $checkoutLogSql .= "ORDER BY time DESC ";
} else {
    $checkoutLogSql .= "AND (NOW()::date - time::date) <= 365 ORDER BY time DESC LIMIT 10 ";
}


$dbPSQL->Pselect($checkoutLogSql, array(':tag' => $_GET["tagnumber"]));
if (arrFilter($dbPSQL->get()) === 0) {
foreach ($dbPSQL->get() as $key => $value1) {
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
$jobLogSQL = "SELECT TO_CHAR(time, 'MM/DD/YY HH12:MI:SS AM') AS time_formatted, CONCAT(cpu_usage, '%') AS cpu_usage, CONCAT(network_usage, ' mbps') AS network_usage, 
    (CASE WHEN erase_completed = TRUE THEN 'Yes' ELSE 'No' END) AS erase_completed, erase_mode, TO_CHAR((erase_time || ' second')::interval, 'HH24:MI:SS') AS erase_time, (CASE WHEN clone_completed = TRUE THEN 'Yes' ELSE 'No' END) AS clone_completed,
    TO_CHAR((clone_time || ' second')::interval, 'HH24:MI:SS') AS clone_time, (CASE WHEN clone_master = TRUE THEN '(Master Image)' ELSE '' END) AS clone_master_formatted, bios_version 
  FROM jobstats 
  WHERE tagnumber = :tagnumber AND (erase_completed = TRUE OR clone_completed = TRUE) ";
if (isset($_GET["full-job-log"]) && $_GET["full-job-log"] == "1") {
  $jobLogSQL .= "ORDER BY time DESC ";
} else {
  $jobLogSQL .= "AND (NOW()::date - jobstats.time::date) <= 365 ORDER BY time DESC LIMIT 10 ";
}

$dbPSQL->Pselect($jobLogSQL, array(':tagnumber' =>  $_GET['tagnumber']));
if (arrFilter($dbPSQL->get()) === 0) {
foreach ($dbPSQL->get() as $key => $value1) {
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
$locLogSQL = "SELECT t1.time, TO_CHAR(t1.time, 'MM/DD/YY HH12:MI:SS AM') AS time_formatted, locationFormatting(t1.location) AS location_formatted, 
  static_departments.department_readable, (CASE WHEN t1.status = TRUE THEN 'No' ELSE 'Yes' END) AS status_formatted, (CASE WHEN t1.disk_removed = TRUE THEN 'Yes' ELSE 'No' END) AS disk_removed_formatted,
  t1.note
  FROM (SELECT locations.time, locations.location, locations.department, locations.status, locations.disk_removed, locations.note, 
    ROW_NUMBER() OVER (PARTITION BY location ORDER BY time DESC) AS row_nums FROM locations WHERE locations.tagnumber = :tagnumber ORDER BY locations.time DESC) t1 
  LEFT JOIN static_departments ON t1.department = static_departments.department ";
if (!isset($_GET["full-loc-log"]) || $_GET["full-loc-log"] != "1") {
  $locLogSQL .= "WHERE t1.row_nums <= 3 AND (NOW()::date - t1.time::date) <= 365 ";
}

$locLogSQL .= "ORDER BY t1.time DESC ";

$dbPSQL->Pselect($locLogSQL, array(':tagnumber' => htmlspecialchars_decode($_GET["tagnumber"])));
if (arrFilter($dbPSQL->get()) === 0) {
foreach ($dbPSQL->get() as $key => $value1) {
echo "<tr>" . PHP_EOL;
//Time formatted
echo "<td>" . htmlspecialchars($value1['time_formatted']) . "</td>" . PHP_EOL;
echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value1["location_formatted"]) . "'>" . htmlspecialchars($value1["location_formatted"]) . "</a></b></td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['department_readable']) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['status_formatted']) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['disk_removed_formatted']) . "</td>" . PHP_EOL;
echo "<td>" . htmlspecialchars($value1['note']) . "</td>" . PHP_EOL;
echo "</tr>" . PHP_EOL;
}
}
unset($value1);
?>
</tbody>
</table>
</div>
<script src="/js/include.js?<?php echo filemtime('js/include.js'); ?>"></script>
<script>
if ( window.history.replaceState ) {
window.history.replaceState( null, null, window.location.href );
}


async function parseSSE() { 
  try {
    const jobQueue = await fetchSSE("job_queue", <?php echo htmlspecialchars($_GET["tagnumber"]); ?>);
    newHTML = '';
    Object.entries(jobQueue).forEach(([key, value]) => {
      // BIOS and kernel updated (check mark)
      if (jobQueue["present_bool"] === true && (jobQueue["kernel_updated"] === true && jobQueue["bios_updated"] === true)) {
        newHTML = "Online, no errors <span>&#10004;&#65039;</span>";
      // BIOS and kernel out of date (x)
      } else if (jobQueue["present_bool"] === true && (jobQueue["kernel_updated"] !== true && jobQueue["bios_updated"] !== true)) {
        newHTML = "Online, kernel and BIOS out of date <span>&#10060;</span>";
      // BIOS out of date, kernel updated (warning sign)
      } else if (jobQueue["present_bool"] === true && (jobQueue["kernel_updated"] === true && jobQueue["bios_updated"] !== true)) {
        newHTML = "Online, please update BIOS <span>&#9888;&#65039;</span>";
      // BIOS updated, kernel out of date (x)
      } else if (jobQueue["present_bool"] === true && (jobQueue["kernel_updated"] !== true && jobQueue["bios_updated"] === true)) {
        newHTML = "Online, kernel out of date <span>&#10060;</span>)";
      // Offline (x)
      } else if (jobQueue["present_bool"] !== true) {
        newHTML = "Offline <span>&#9940;</span>";
      } else {
        newHTML = "Unknown <span>&#9940;&#65039;</span>";
      }
      
      document.getElementById('bios_kernel_updated').innerHTML = newHTML;

      newHTML = '';
      newHTML = '<b>"' + jobQueue["remote_status"] + '"</b> at ' + jobQueue["remote_time_formatted"];
      document.getElementById('remote_status').innerHTML = newHTML;
    });

  } catch (error) {
  }


  try {
    const liveImage = await fetchSSE("live_image", <?php echo htmlspecialchars($_GET["tagnumber"]); ?>);
    newHTML = '';
        Object.entries(liveImage).forEach(([key, value]) => {
          newSRC = '';
          newSRC = "data:image/jpeg;base64," + liveImage['screenshot'];
          document.getElementById('live_image').src = newSRC;

          newHTML = '';
          newHTML = "Screenshot Time: " + liveImage["time_formatted"];
          document.getElementById('live_image_time').innerHTML = newHTML;

        });
  } catch (error) {
    newSRC = 'https://i.pinimg.com/originals/30/0c/af/300caff45dddad3fa83e8fe6fcd390a2.gif';
    document.getElementById('live_image').src = newSRC;
    newHTML = "No Screenshot in DB :(" ;
    document.getElementById('live_image_time').innerHTML = newHTML;
  }
};

parseSSE();
setInterval(parseSSE, 3000);


</script>

<script>
  <?php
  $dbPSQL->select("SELECT t1.tagnumber FROM (SELECT time, tagnumber, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM locations) t1 WHERE t1.row_nums = 1 ORDER BY t1.time DESC");
  if (arrFilter($dbPSQL->get()) === 0) {
    foreach ($dbPSQL->get() as $key => $value) {
      $tagStr .= htmlspecialchars($value["tagnumber"]) . "|";
    }
  }
  unset($value);
  ?>
  document.getElementById('dropdown-search').style.display = "none";
  document.getElementById('dropdown-search').innerHTML = "";
  autoFillTags(<?php echo "'" . substr($tagStr, 0, -1) . "'"; ?>);
</script>

<div class="uit-footer">
<img src="/images/uh-footer.svg">
</div>
</body>
</html>