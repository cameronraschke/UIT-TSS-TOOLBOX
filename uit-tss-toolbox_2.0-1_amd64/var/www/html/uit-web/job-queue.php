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
  </head>
  <body onload="fetchHTML()">
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

            <button style='background-color:rgba(0, 179, 136, 0.30);' type="submit">Queue Job</button>
          </form>

        </div>
      </div>

      <div class='column'>
      </div>
    </div>


    <div class='row'>
      <div id="runningJobs" style='max-height: 20%; width: auto; margin: 1% 1% 1% 1%;'>
        <?php
        $dbPSQL->select("SELECT COUNT(tagnumber) AS count FROM remote WHERE job_queued IS NOT NULL AND NOT status = NULL AND NOT status = 'Waiting for job' AND present_bool = TRUE");
        if (arrFilter($dbPSQL->get()) === 0) {
          foreach ($dbPSQL->get() as $ley => $value) {
            echo "<h3><b>Queued Jobs:</b> " . htmlspecialchars($value["count"]) . "</h3>";
          }
        }
        ?>
      </div>
    </div>

    <div id='remoteStats' class='pagetitle'>
        <?php
          $dbPSQL->select("SELECT CONCAT('(', COUNT(remote.tagnumber), ')') AS tagnumber_count, CONCAT('(', MIN(remote.battery_charge), '%', '/', MAX(remote.battery_charge), '%', '/', ROUND(AVG(remote.battery_charge), 2), '%', ')') AS battery_charge_formatted, CONCAT('(', MIN(remote.cpu_temp), '°C', '/', MAX(remote.cpu_temp), '°C', '/', ROUND(AVG(remote.cpu_temp), 2), '°C', ')') AS cpu_temp_formatted, CONCAT('(', MIN(remote.disk_temp), '°C',  '/', MAX(remote.disk_temp), '°C' , '/', ROUND(AVG(remote.disk_temp), 2), '°C' , ')') AS disk_temp_formatted, CONCAT('(', COUNT(client_health.os_installed), ')') AS os_installed_formatted, CONCAT('(', SUM(remote.watts_now), ' ', 'watts', ')') AS power_usage_formatted FROM remote LEFT JOIN client_health ON remote.tagnumber = client_health.tagnumber WHERE remote.present_bool = TRUE");
          foreach ($dbPSQL->get() as $key => $value1) {
        ?>
      <h3>Online Clients <?php echo htmlspecialchars($value1["tagnumber_count"]); ?></h3>
    </div>
      <div class='styled-table' style="width: auto; overflow:auto; margin: 1% 1% 0% 1%;">

      <table id="myTable" width="100%">
        <thead>
        <tr>
        <th>Tag Number</th>
        <th>Last Job Time</th>
        <th>Location</th>
        <th>Current Status</th>
        <th>OS Installed <?php echo htmlspecialchars($value1["os_installed_formatted"]); ?></th>
        <th>Battery Charge <?php echo htmlspecialchars($value1["battery_charge_formatted"]); ?></th>
        <th>Uptime</th>
        <th>CPU Temp <?php echo htmlspecialchars($value1["cpu_temp_formatted"]); ?></th>
        <th>Disk Temp <?php echo htmlspecialchars($value1["disk_temp_formatted"]); ?></th>
        <th>Power Usage <?php echo htmlspecialchars($value1["power_usage_formatted"]); ?></th>
        </tr>
        </thead>
        <tbody>
          <?php } 
          unset($value1);?>
        <?php
      $dbPSQL->select("SELECT remote.tagnumber, 
        (CASE WHEN remote.status LIKE 'fail%' THEN 1 ELSE 0 END) AS failstatus, t1.domain, 
        TO_CHAR(remote.present, 'MM/DD/YY HH12:MI:SS AM') AS time_formatted, locationFormatting(t3.location) AS location_formatted, 
        TO_CHAR(remote.last_job_time, 'MM/DD/YY HH12:MI:SS AM') AS last_job_time_formatted, 
        remote.job_queued, remote.status, t2.queue_position, remote.present_bool, 
        client_health.os_name AS os_installed_formatted, client_health.os_installed, 
        client_health.bios_updated, (CASE WHEN client_health.bios_updated = TRUE THEN 'Yes' ELSE 'No' END) AS bios_updated_formatted, 
        remote.kernel_updated, CONCAT(remote.battery_charge, '%', ' - ', remote.battery_status) AS battery_charge_formatted, 
        CONCAT(FLOOR(remote.uptime / 3600 / 24), 'd ' , FLOOR(MOD(remote.uptime, 3600 * 24) / 3600), 'h ' , FLOOR(MOD(remote.uptime, 3600) / 60), 'm ' , FLOOR(MOD(remote.uptime, 60)), 's') AS uptime, 
        CONCAT(remote.cpu_temp, '°C') AS cpu_temp, CONCAT(remote.disk_temp, '°C') AS disk_temp, 
        CONCAT(remote.watts_now, ' watts') AS watts_now, remote.job_active
      FROM remote 
      LEFT JOIN (SELECT s1.time, s1.tagnumber, s1.domain FROM (SELECT time, tagnumber, domain, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM locations) s1 WHERE s1.row_nums = 1) t1
        ON remote.tagnumber = t1.tagnumber
      LEFT JOIN client_health ON remote.tagnumber = client_health.tagnumber
      LEFT JOIN (SELECT tagnumber, location, row_nums FROM (SELECT tagnumber, location, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM locations) s3 WHERE s3.row_nums = 1) t3
        ON t3.tagnumber = remote.tagnumber
      LEFT JOIN (SELECT tagnumber, queue_position FROM (SELECT tagnumber, ROW_NUMBER() OVER (ORDER BY tagnumber ASC) AS queue_position FROM remote WHERE job_queued IS NOT NULL) s2) t2
        ON remote.tagnumber = t2.tagnumber
      WHERE remote.present_bool = TRUE
      ORDER BY
      failstatus DESC,
      (CASE WHEN remote.status LIKE 'fail%' THEN 1 ELSE 0 END) DESC, job_queued IS NULL ASC, job_active DESC, queue_position ASC,
        (CASE WHEN job_queued = 'data collection' THEN 20 WHEN job_queued = 'update' THEN 15 WHEN job_queued = 'nvmeVerify' THEN 14 WHEN job_queued =  'nvmeErase' THEN 12 WHEN job_queued =  'hpCloneOnly' THEN 11 WHEN job_queued = 'hpEraseAndClone' THEN 10 WHEN job_queued = 'findmy' THEN 8 WHEN job_queued = 'shutdown' THEN 7 WHEN job_queued = 'fail-test' THEN 5 END) DESC, 
        (CASE WHEN status = 'Waiting for job' THEN 1 ELSE 0 END) ASC, (CASE WHEN client_health.os_installed = TRUE THEN 1 ELSE 0 END) DESC, (CASE WHEN remote.kernel_updated = TRUE THEN 1 ELSE 0 END) DESC, (CASE WHEN client_health.bios_updated = TRUE THEN 1 ELSE 0 END) DESC, remote.last_job_time DESC");

        if (arrFilter($dbPSQL->get()) === 0) {
          foreach ($dbPSQL->get() as $key => $value) {
        ?>
          <tr>
            <?php
            // Keep this td completely in PHP to avoid weird spacing issues.
            echo "<td>";
            if (strFilter($value["status"]) === 1) {
              echo "<b>New Entry: </b>";
            } else if (strFilter($value["status"]) === true) {
              if (($value["status"] !== "Waiting for job" || strFilter($value["job_queued"]) === 0) && preg_match("/^fail\ \-.*$/i", $value["status"]) !== 1) {
                echo "<b>In Progress: </b>";
              }
            }
            echo "<b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"]) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"]) . "</a></b>";
            if ($value["present_bool"] === true && ($value["kernel_updated"] === true && $value["bios_updated"] === true)) {
              echo "<span style='color:rgb(0, 120, 50)'><b>&#10004;</b></span>";
              // BIOS out of date, kernel not updated (x)
            } elseif ($value["present_bool"] === true && ($value["kernel_updated"] !== true && $value["bios_updated"] !== true)) {
              echo "<span>&#10060;</span>";
              //BIOS out of date, kernel updated (warning sign)
            } elseif ($value["present_bool"] === true && ($value["kernel_updated"] === true && $value["bios_updated"] !== true)) {
              echo "<span>&#9888;&#65039;</span>";
              //BIOS updated, kernel out of date (x)
            } elseif ($value["present_bool"] === true && ($value["kernel_updated"] !== true && $value["bios_updated"] === true)) {
              echo "<span>&#10060;</span>";
            }
            echo "</td>";
            ?>

            <td id='lastJobTime'><?php echo $value["last_job_time_formatted"]; ?></td>
            <td id='presentLocation'><b><a href='locations.php?location=<?php echo htmlspecialchars($value["location_formatted"]); ?>' target='_blank'><?php echo htmlspecialchars($value["location_formatted"]); ?></a></b></td>
            <td id='presentStatus'><?php echo htmlspecialchars($value["status"]); ?></td>
            <td id='osInstalled'><?php echo htmlspecialchars($value["os_installed_formatted"]); if ($value["os_installed"] === 1 && strFilter($value["domain"]) === 0) { echo "<img style='width: auto; height: 1.5em;' src='/images/azure-ad-logo.png'>"; }?>
            </td>
            <td><?php echo htmlspecialchars($value["battery_charge_formatted"]); ?></td>
            <td id='uptime'><?php echo htmlspecialchars($value["uptime"]); ?></td>
            <td id='presentCPUTemp'><?php echo htmlspecialchars($value["cpu_temp"]); ?></td>
            <td id='presentDiskTemp'><?php echo htmlspecialchars($value["disk_temp"]); ?></td>
            <td><?php echo htmlspecialchars($value["watts_now"]); ?></td>
          </tr>
          <?php 
          //Close Loop
          } }
          ?>
        </tbody>
      </table>
    </div>

    <div class='pagetitle'>
      <h3>Offline Clients</h3>
    </div>
    <div class='styled-table' style="width: auto; max-height: 70%; overflow:auto; margin: 1% 1% 0% 1%;">
      <table id="myTable1" width="100%">
      <thead>
      <tr>
      <th>Tag Number</th>
      <th>Last Heard</th>
      <th>Last Location</th>
      <th>Last Known Status</th>
      <th>Battery Charge</th>
      <th>CPU Temp</th>
      <th>Disk Temp</th>
      <th>Actual Power Draw</th>
      </tr>
      </thead>

      <?php
      unset($value);
      // Clients not present
      $dbPSQL->select("SELECT tagnumber, TO_CHAR(present, 'MM/DD/YY HH12:MI:SS AM') AS time_formatted, status, CONCAT(battery_charge, '%') AS battery_charge, battery_status, CONCAT(cpu_temp, '°C') AS cpu_temp,  CONCAT(disk_temp, '°C') AS disk_temp, CONCAT(watts_now, ' Watts') AS watts_now FROM remote WHERE present_bool IS FALSE AND present IS NOT NULL ORDER BY present DESC, tagnumber DESC");
      if (arrFilter($dbPSQL->get()) === 0) {
      foreach ($dbPSQL->get() as $key => $value) {
      echo "<tr>". PHP_EOL;
      echo "<td>" . PHP_EOL;
      echo "<b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"]) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b>" . PHP_EOL;
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

      echo "<td>" . htmlspecialchars($value["status"]) . "</td>" . PHP_EOL;
      if (strFilter($value["battery_charge"]) === 0) {
      echo "<td>" . htmlspecialchars($value["battery_charge"]);
      if (strFilter($value["battery_status"]) === 0) {
      " (" . htmlspecialchars($value["battery_status"]) . ")";
      }
      echo "</td>" . PHP_EOL;
      } else {
      echo "<td></td>";
      }
      echo "<td>" . htmlspecialchars($value["cpu_temp"]) . "</td>" . PHP_EOL;
      echo "<td>" . htmlspecialchars($value["disk_temp"]) . "</td>" . PHP_EOL;
      echo "<td> " . htmlspecialchars($value["watts_now"]) . "</td>" . PHP_EOL;
      echo "</tr>";
      }
      }
      echo "</tbody>";
      echo "</table>";
    echo "</div>";
    ?>
    <script src="/js/include.js?<?php echo filemtime('js/include.js'); ?>"></script>
    <script>
    if ( window.history.replaceState ) {
    window.history.replaceState( null, null, window.location.href );
    }
    </script>

    <script>
    var i = 0;
    function fetchHTML() {
    const var1 = setTimeout(function() {
    fetch('/job-queue.php')
    .then((response) => {
    return response.text();
    })
    .then((html) => {
    //document.body.innerHTML = html
    const parser = new DOMParser()
    const doc = parser.parseFromString(html, "text/html")
    //Update time at the top
    const time = doc.getElementById('time').innerHTML
    document.getElementById("time").innerHTML = time
    //Update remote stats 
    const remoteStats = doc.getElementById('remoteStats').innerHTML
    document.getElementById("remoteStats").innerHTML = remoteStats
    //Update client table
    const myTable = doc.getElementById('myTable').innerHTML
    document.getElementById("myTable").innerHTML = myTable
    //Runing jobs overview
    const runningJobs = doc.getElementById('runningJobs').innerHTML
    document.getElementById("runningJobs").innerHTML = runningJobs
    //myTable1
    const myTable1 = doc.getElementById('myTable1').innerHTML
    document.getElementById("myTable1").innerHTML = myTable1
    });
    fetchHTML();
    }, 3000)}
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