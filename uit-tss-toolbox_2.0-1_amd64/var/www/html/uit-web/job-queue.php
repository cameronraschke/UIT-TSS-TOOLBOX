<?php
require('/var/www/html/uit-web/header.php');
require('/var/www/html/uit-web/php/include.php');

if ($_SESSION['authorized'] != "yes") {
  die();
}

$dbPSQL = new dbPSQL();

// Job by location form
if (strFilter($_POST['location']) === 0 && strFilter($_POST['location-action']) === 0) {
  $dbPSQL->Pselect("SELECT locations.tagnumber 
    FROM locations 
    INNER JOIN (SELECT MAX(time) AS time FROM locations WHERE location IS NOT NULL GROUP BY tagnumber) t1 
      ON locations.time = t1.time 
    INNER JOIN (SELECT tagnumber FROM remote WHERE present_bool = TRUE AND kernel_updated = TRUE AND job_queued IS NULL) t2 
      ON locations.tagnumber = t2.tagnumber AND location = :location GROUP BY locations.tagnumber", array(':location' => htmlspecialchars_decode($_POST["location"])));
  if (arrFilter($dbPSQL->get()) === 0) {
    foreach ($dbPSQL->get() as $key => $value) {
      $dbPSQL->updateRemote(trim($value["tagnumber"]), "job_queued", $_POST['location-action']);
    }
  }
}
unset($value);
unset($_POST);
?>

<!DOCTYPE html>
  <head>
    <meta charset='UTF-8'>
    <link rel='stylesheet' type='text/css' href='/css/main.css' />
    <title>Job Queue - UIT Client Mgmt</title>
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    <script src="/js/init.js?<?php echo filemtime('js/init.js'); ?>"></script>
  </head>
  <!-- <body onload="fetchHTML()"> -->
    <body>
    <?php include('/var/www/html/uit-web/php/navigation-bar.php'); ?>

    <div class='pagetitle' id='time'><h3>Page last updated: <?php $dbPSQL->select("SELECT TO_CHAR(NOW(), 'MM/DD/YY HH12:MI:SS AM') AS time_formatted"); echo $dbPSQL->nested_get()["time_formatted"]; ?></h3></div>

    <div class='row'>
      <div class='column'>
        <div class='location-form'>
          <form name="job_queued_form" method="post">
            <div>
              <label for='location'>Enter a location and a job to queue:
                <div class="tooltip">?
                  <span class="tooltiptext">
                    <p>A checkmark (<span style='color:rgb(0, 120, 50)'><b>&#10004;</b></span>) means a client is currently online and ready for a job.</p><br>
                    <p>A warning sign (<span>&#9888;&#65039;</span>) means a client has an out of date BIOS. Running jobs is not advised.</p><br>
                    <p>An X (<span>&#10060;</span>) means a client has an out of date kernel. Do not run jobs on these clients.</p><br>
                  </span>
                </div>
              </label>
            </div>
            <select name="location" id="location">
              <option>--Select Location--</option>
              <?php
              //Get list of recent locations
              $dbPSQL->select("SELECT MAX(remote.present) AS present, locationFormatting(locations.location) AS location
                FROM remote 
                  INNER JOIN locations ON remote.tagnumber = locations.tagnumber 
                  INNER JOIN (SELECT MAX(time) AS time FROM locations GROUP BY tagnumber) t1 
                    ON locations.time = t1.time 
                WHERE remote.present IS NOT NULL
                  AND (NOW()::date - remote.present::date) <= 1
                GROUP BY locations.location 
                ORDER BY present DESC");
              if (arrFilter($dbPSQL->get()) === 0) {
                foreach ($dbPSQL->get() as $key => $value) {
                  echo "<option value='" . htmlspecialchars($value["location"]) . "'>" . htmlspecialchars($value["location"]) . "</option>" . PHP_EOL;
                }
              }
              unset($value);
              ?>
            </select>
            <select name="location-action" id="location-action">
              <option value=' '>No Job</option>
              <?php
              //Get list of available jobs
              $dbPSQL->select("SELECT job, job_readable FROM static_job_names WHERE job_html_bool = TRUE ORDER BY job_rank ASC");
                foreach($dbPSQL->get() as $key => $value) {
                  echo "<option value='" . htmlspecialchars($value["job"]) . "'>" . htmlspecialchars($value["job_readable"]) . "</option>";
                }
              ?>
            </select>

            <button style='' type="submit">Queue Job</button>
          </form>

        </div>
      </div>

      <div class='column'>
      </div>
    </div>


    <div class='row'>
      <div id="runningJobs">
        <?php
        $dbPSQL->select("SELECT COUNT(tagnumber) AS count FROM remote WHERE job_queued IS NOT NULL AND status IS NOT NULL AND NOT status = 'Waiting for job' AND present_bool = TRUE");
        if (arrFilter($dbPSQL->get()) === 0) {
          foreach ($dbPSQL->get() as $ley => $value) {
            echo "<h3><b>Queued Jobs:</b> " . htmlspecialchars($value["count"]) . "</h3>";
          }
        }
        ?>
      </div>
    </div>

    <div id='remoteStats' class='pagetitle'>
      <h3>Online Clients <?php echo htmlspecialchars($value1["tagnumber_count"]); ?></h3>
    </div>
      <div>

      <table id='remotePresentTable' width="100%">
        <thead id="onlineTableHeader">
        <tr>
        <th>Tag Number</th>
        <th>Last Job Time</th>
        <th>Location</th>
        <th>Current Status</th>
        <th>OS Installed</th>
        <th>Battery Charge</th>
        <th>Uptime</th>
        <th>CPU Temp</th>
        <th>Disk Temp</th>
        <th>Power Usage></th>
        </tr>
        </thead>
        <tbody id="onlineTableBody">
          <tr>
            <?php
            // // Keep this td completely in PHP to avoid weird spacing issues.
            // echo "<td id='tagnumber-" . htmlspecialchars($value["tagnumber"]) . "'>";
            // if (strFilter($value["status"]) === 1) {
            //   echo "<b>New Entry: </b>";
            // } else if (strFilter($value["status"]) === 0) {
            //   if (($value["status"] !== "Waiting for job" || strFilter($value["job_queued"]) === 0) && preg_match("/^fail\ \-.*$/i", $value["status"]) !== 1) {
            //     echo "<b>In Progress: </b>";
            //   }
            // }
            // echo "<b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"]) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"]) . "</a></b>";
            // if ($value["present_bool"] === true && ($value["kernel_updated"] === true && $value["bios_updated"] === true)) {
            //   echo "<span style='color:rgb(0, 120, 50)'><b>&#10004;</b></span>";
            //   // BIOS out of date, kernel not updated (x)
            // } elseif ($value["present_bool"] === true && ($value["kernel_updated"] !== true && $value["bios_updated"] !== true)) {
            //   echo "<span>&#10060;</span>";
            //   //BIOS out of date, kernel updated (warning sign)
            // } elseif ($value["present_bool"] === true && ($value["kernel_updated"] === true && $value["bios_updated"] !== true)) {
            //   echo "<span>&#9888;&#65039;</span>";
            //   //BIOS updated, kernel out of date (x)
            // } elseif ($value["present_bool"] === true && ($value["kernel_updated"] !== true && $value["bios_updated"] === true)) {
            //   echo "<span>&#10060;</span>";
            // }
            // echo "</td>";
            ?>

            <!-- <td id='lastJobTime'></td>
            <td id='presentLocation'><b><a href='locations.php?location=</a></b></td>
            <td id='presentStatus'></td>
            <td id='osInstalled'></td>
            <td></td>
            <td id='uptime'></td>
            <td class='presentCPUTemp' id='presentCPUTemp'></td>
            <td class='presentDiskTemp' id='presentDiskTemp-'></td>
            <td></td>
          </tr> -->
        </tbody>
      </table>
    </div>

    <script>
      const cpuTemps = document.querySelectorAll('.presentCPUTemp');
      cpuTemps.forEach(function(item) {
        parseCPUTemp(item.id.replace(/\D/g, ""), item.textContent.replace(/\D/g, ""));
      });

      const diskTemps = document.querySelectorAll('.presentDiskTemp');
      diskTemps.forEach(function(item) {
        parseDiskTemp(item.id.replace(/\D/g, ""), item.textContent.replace(/\D/g, ""));
      });
    </script>

    <div class='pagetitle'>
      <h3>Offline Clients</h3>
    </div>
    <div>
      <table id="myTable1" width="100%">
      <thead>
      <tr>
      <th>Tag Number</th>
      <th>Last Heard</th>
      <th>Last Location</th>
      <th>Last Known Status</th>
      <th>OS Installed</th>
      <th>Battery Charge</th>
      <th>CPU Temp</th>
      <th>Disk Temp</th>
      <th>Power Draw</th>
      </tr>
      </thead>

      <?php
      unset($value);
      // Clients not present
      $dbPSQL->select("SELECT remote.tagnumber, TO_CHAR(remote.present, 'MM/DD/YY HH12:MI:SS AM') AS time_formatted, 
          remote.status, CONCAT(remote.battery_charge, '%') AS battery_charge, 
          remote.battery_status, remote.cpu_temp, CONCAT(remote.cpu_temp, '°C') AS cpu_temp_formatted, 
          CONCAT(remote.disk_temp, '°C') AS disk_temp, CONCAT(remote.watts_now, ' Watts') AS watts_now,
          client_health.os_name AS os_installed_formatted, client_health.os_installed, 
          (CASE WHEN locations.domain IS NOT NULL THEN TRUE ELSE FALSE END) AS domain_joined
        FROM remote 
        LEFT JOIN client_health ON remote.tagnumber = client_health.tagnumber
        LEFT JOIN locations ON remote.tagnumber = locations.tagnumber AND locations.time IN (SELECT time FROM (SELECT time, tagnumber, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM locations) s1 WHERE s1.row_nums = 1)
        WHERE remote.present_bool IS FALSE 
          AND remote.present IS NOT NULL 
        ORDER BY remote.present DESC, remote.tagnumber DESC");
      if (arrFilter($dbPSQL->get()) === 0) {
      foreach ($dbPSQL->get() as $key => $value) {
      echo "<tr>". PHP_EOL;
      echo "<td>" . PHP_EOL;
      echo "<b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"]) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"]) . "</a></b>" . PHP_EOL;
      echo "</td>";
      echo "<td>" . $value["time_formatted"] . "</td>" . PHP_EOL;
      $dbPSQL->Pselect("SELECT locationFormatting(location) AS location_formatted FROM locations WHERE tagnumber = :tagnumber AND location IS NOT NULL ORDER BY time DESC LIMIT 1", array(':tagnumber' => $value["tagnumber"]));
      foreach ($dbPSQL->get() as $key => $value1) {
        if (strFilter($value1["location_formatted"]) === 0) {
      echo "<td id='absentLocation'><b><a href='locations.php?location=" . htmlspecialchars($value1["location_formatted"]) . "' target='_blank'>" . htmlspecialchars($value1["location_formatted"]) . "</a></b></td>" . PHP_EOL;
        } else {
      echo "<td><b>" . "<i>No Location</i>" . "</b></td>" . PHP_EOL;
        }
      }
      unset($value1);
      ?>


      <td><?php echo htmlspecialchars($value["status"]); ?></td>

      <td><?php echo htmlspecialchars($value["os_installed_formatted"]); if ($value["os_installed"] === true && $value["domain_joined"] === true) { echo "<img class='icon' src='/images/azure-ad-logo.png'>"; }?>

      <?php
      if (strFilter($value["battery_charge"]) === 0) {
      echo "<td>" . htmlspecialchars($value["battery_charge"]);
      if (strFilter($value["battery_status"]) === 0) {
      " (" . htmlspecialchars($value["battery_status"]) . ")";
      }
      echo "</td>" . PHP_EOL;
      } else {
      echo "<td></td>";
      }
      ?>
      

      <?php
      echo "<td>" . htmlspecialchars($value["cpu_temp_formatted"]) . "</td>" . PHP_EOL;
      echo "<td>" . htmlspecialchars($value["disk_temp"]) . "</td>" . PHP_EOL;
      echo "<td> " . htmlspecialchars($value["watts_now"]) . "</td>" . PHP_EOL;
      echo "</tr>";
      }
      }
      echo "</tbody>";
      echo "</table>";
    echo "</div>";
    ?>
    <script>
    if ( window.history.replaceState ) {
      window.history.replaceState( null, null, window.location.href );
    }
    </script>

    <script src="/js/include.js?<?php echo filemtime('js/include.js'); ?>"></script>

    <script>
      setInterval(function() {
        updateRemotePresentTable();
      }, 3000);
      updateRemotePresentTable();
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