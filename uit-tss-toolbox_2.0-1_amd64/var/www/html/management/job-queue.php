<?php
require('/var/www/html/management/header.php');
require('/var/www/html/management/php/include.php');

session_start();
if ($_SESSION['authorized'] != "yes") {
  die();
}

$db = new db();

// Job by location form
if (strFilter($_POST['location']) === 0 && strFilter($_POST['location-action']) === 0) {
  $db->Pselect("SELECT locations.tagnumber 
    FROM locations 
    INNER JOIN (SELECT MAX(time) AS 'time' FROM locations WHERE location IS NOT NULL GROUP BY tagnumber) t1 
      ON locations.time = t1.time 
    INNER JOIN (SELECT tagnumber FROM remote WHERE present_bool = 1 AND kernel_updated = 1 AND job_queued IS NULL) t2 
      ON locations.tagnumber = t2.tagnumber AND location = :location GROUP BY locations.tagnumber", array(':location' => htmlspecialchars_decode($_POST["location"])));
  if (arrFilter($db->get()) === 0) {
    foreach ($db->get() as $key => $value) {
      $db->updateRemote(trim($value["tagnumber"]), "job_queued", $_POST['location-action']);
    }
  }
}
unset($value);
unset($_POST);
?>

<html>
  <head>
    <meta charset='UTF-8'>
    <link rel='stylesheet' type='text/css' href='/css/main.css' />
    <title>Job Queue - UIT Client Mgmt</title>
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
  </head>
  <body onload="fetchHTML()">
      <div class='menubar'>
        <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
        <p><span style='float: right;'>Logged in as <b><?php echo htmlspecialchars($login_user); ?></b>.</span></p>
        <br>
        <p><span style='float: right;'>Not <b><?php echo htmlspecialchars($login_user); ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
      </div>

    <div class='pagetitle'><h1>Job Queue</h1></div>
    <div class='pagetitle'><h2>This page allows you to queue and view active jobs for clients plugged into the server.</h2></div>
    <div class='pagetitle' id='time'><h3>Page last updated: <?php $db->select("SELECT DATE_FORMAT(CONCAT(CURDATE(), ' ', CURTIME()), '%m/%d/%y, %r') AS 'time_formatted'"); if (arrFilter($db->get()) === 0) { foreach ($db->get() as $key => $sqlUpdatedTime) { echo $sqlUpdatedTime["time_formatted"]; } } ?></h3></div>

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
              $db->select("SELECT MAX(remote.present) AS 'present', locationFormatting(locations.location) AS 'location'
                FROM remote 
                  INNER JOIN locations ON remote.tagnumber = locations.tagnumber 
                  INNER JOIN (SELECT MAX(time) AS 'time' FROM locations GROUP BY tagnumber) t1 
                    ON locations.time = t1.time 
                WHERE remote.present IS NOT NULL
                  AND TIMESTAMPDIFF(DAY, present, NOW()) < 1
                GROUP BY location 
                ORDER BY present DESC");
              if (arrFilter($db->get()) === 0) {
                foreach ($db->get() as $key => $value) {
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
              $db->select("SELECT job, job_readable FROM static_job_names WHERE job_html_bool = 1 ORDER BY job_rank ASC");
                foreach($db->get() as $key => $value) {
                  echo "<option value='" . htmlspecialchars($value["job"]) . "'>" . htmlspecialchars($value["job_readable"]) . "</option>";
                }
              ?>
            </select>

            <button style='background-color:rgba(0, 179, 136, 0.30);' type="submit">Queue Job</button>
          </form>

        </div>
      </div>

      <div class='column'>
        <div class='styled-table'>
          <table id='remoteStats'>
            <thead>
              <tr>
                <th>Status of Online Clients</th>
                <th></th>
              </tr>
            </thead>

            <tbody>
              <?php
              $db->select("CALL selectRemoteStats");
                if (arrFilter($db->get()) === 0) {
                foreach ($db->get() as $key => $value) {
              ?>
              <tr>
              <td>Total Clients Present</td>
              <td><?php echo htmlspecialchars($value['Present Laptops']); ?> </td>
              </tr>
              <tr>
              <td>Average Battery Charge</td>
              <td><?php echo htmlspecialchars($value['Avg. Battery Charge']); ?></td>
              </tr>
              <tr>
              <td>Average CPU Temp</td>
              <td><?php echo htmlspecialchars($value['Avg. CPU Temp']); ?></td>
              </tr>
              <tr>
              <td>Average Disk Temp</td>
              <td><?php echo htmlspecialchars($value['Avg. Disk Temp']); ?></td>
              </tr>
              <tr>
              <td>Average Actual Power Draw</td>
              <td><?php echo htmlspecialchars($value['Avg. Actual Power Draw']); ?></td>
              </tr>
              <tr>
              <td>Total Actual Power Draw</td>
              <td><?php echo htmlspecialchars($value['Actual Power Draw']); ?></td>
              </tr>
              <tr>
              <td>Total Power Draw From Wall</td>
              <td><?php echo htmlspecialchars($value['Power Draw from Wall']); ?></td>
              </tr>
              <tr>
              <td>Sum of OS's Installed</td>
              <td><?php echo htmlspecialchars($value['OS Installed Sum']); ?></td>
              </tr>
              <?php }} unset($value); ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>


    <div class='row'>
      <div id="runningJobs" style='max-height: 20%; width: auto; margin: 1% 1% 1% 1%;'>
        <?php
        $db->select("SELECT COUNT(tagnumber) AS 'count' FROM remote WHERE job_queued IS NOT NULL AND NOT status = NULL AND NOT status = 'Waiting for job' AND present_bool = 1");
        if (arrFilter($db->get()) === 0) {
          foreach ($db->get() as $ley => $value) {
            echo "<h3><b>Queued Jobs:</b> " . htmlspecialchars($value["count"]) . "</h3>";
          }
        }
        ?>
      </div>
    </div>

    <?php
    $db->select("SELECT remote.tagnumber, 
        IF (remote.status LIKE 'fail%', 1, 0) AS 'failstatus', t1.domain, 
        DATE_FORMAT(remote.present, '%m/%d/%y, %r') AS 'time_formatted', locationFormatting(t3.location) AS 'location_formatted', 
        DATE_FORMAT(remote.last_job_time, '%m/%d/%y, %r') AS 'last_job_time_formatted', 
        remote.job_queued, remote.status, t2.queue_position, remote.present_bool, 
        client_health.os_name AS 'os_installed_formatted', client_health.os_installed , 
        client_health.bios_updated, IF (client_health.bios_updated = 1, 'Yes', 'No') AS 'bios_updated_formatted', 
        remote.kernel_updated, CONCAT(remote.battery_charge, '%') AS 'battery_charge', remote.battery_status, 
        CONCAT(FLOOR(remote.uptime / 3600 / 24), 'd ' , FLOOR(MOD(remote.uptime, 3600 * 24) / 3600), 'h ' , FLOOR(MOD(remote.uptime, 3600) / 60), 'm ' , FLOOR(MOD(remote.uptime, 60)), 's') AS 'uptime', 
        CONCAT(remote.cpu_temp, '°C') AS 'cpu_temp', CONCAT(remote.disk_temp, '°C') AS 'disk_temp', 
        CONCAT(remote.watts_now, ' Watts') AS 'watts_now', remote.job_active
      FROM remote 
      LEFT JOIN (SELECT s1.time, s1.tagnumber, s1.domain FROM (SELECT time, tagnumber, domain, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'row_nums' FROM locations) s1 WHERE s1.row_nums = 1) t1
        ON remote.tagnumber = t1.tagnumber
      LEFT JOIN client_health ON remote.tagnumber = client_health.tagnumber
      LEFT JOIN (SELECT tagnumber, location, row_nums FROM (SELECT tagnumber, location, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'row_nums' FROM locations) s3 WHERE s3.row_nums = 1) t3
        ON t3.tagnumber = remote.tagnumber
      LEFT JOIN (SELECT tagnumber, queue_position FROM (SELECT tagnumber, ROW_NUMBER() OVER (ORDER BY tagnumber ASC) AS 'queue_position' FROM remote WHERE job_queued IS NOT NULL) s2) t2
        ON remote.tagnumber = t2.tagnumber
      WHERE remote.present_bool = 1
      ORDER BY
        failstatus DESC, ISNULL(job_queued) ASC, job_active DESC, queue_position ASC,
        FIELD (job_queued, 'data collection', 'update', 'nvmeVerify', 'nvmeErase', 'hpCloneOnly', 'hpEraseAndClone', 'findmy', 'shutdown', 'fail-test') DESC, 
        FIELD (status, 'Waiting for job', '%') ASC, os_installed DESC, kernel_updated DESC, bios_updated DESC, last_job_time DESC");
    ?>
    <div class='pagetitle'>
      <h3>Clients Currently Present</h3>
    </div>
      <div class='styled-table' style="width: auto; overflow:auto; margin: 1% 1% 0% 1%;">

      <table id="myTable" width="100%">
        <thead>
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
        </tr>
        </thead>
        <tbody>
        <?php
        if (arrFilter($db->get()) === 0) {
          foreach ($db->get() as $key => $value) {
        ?>
          <tr>
            <?php
            // Keep this td completely in PHP to avoid weird spacing issues.
            echo "<td>";
            if (strFilter($value["status"]) === 1) {
              echo "<b>New Entry: </b>";
            } else if (strFilter($value["status"]) === 0) {
              if (($value["status"] !== "Waiting for job" || strFilter($value["job_queued"]) === 0) && preg_match("/^fail\ \-.*$/i", $value["status"]) !== 1) {
                echo "<b>In Progress: </b>";
              }
            }
            echo "<b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"]) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"]) . "</a></b>";
            if ($value["present_bool"] === 1 && ($value["kernel_updated"] === 1 && $value["bios_updated"] === 1)) {
              echo "<span style='color:rgb(0, 120, 50)'><b>&#10004;</b></span>";
              // BIOS out of date, kernel not updated (x)
            } elseif ($value["present_bool"] === 1 && ($value["kernel_updated"] !== 1 && $value["bios_updated"] !== 1)) {
              echo "<span>&#10060;</span>";
              //BIOS out of date, kernel updated (warning sign)
            } elseif ($value["present_bool"] === 1 && ($value["kernel_updated"] === 1 && $value["bios_updated"] !== 1)) {
              echo "<span>&#9888;&#65039;</span>";
              //BIOS updated, kernel out of date (x)
            } elseif ($value["present_bool"] === 1 && ($value["kernel_updated"] !== 1 && $value["bios_updated"] === 1)) {
              echo "<span>&#10060;</span>";
            }
            echo "</td>";
            ?>

            <td id='lastJobTime'><?php echo $value["last_job_time_formatted"]; ?></td>
            <td id='presentLocation'><b><a href='locations.php?location=<?php echo htmlspecialchars($value["location_formatted"]); ?>' target='_blank'><?php echo htmlspecialchars($value["location_formatted"]); ?></a></b></td>
            <td id='presentStatus'><?php echo htmlspecialchars($value["status"]); ?></td>
            <td id='osInstalled'><?php echo htmlspecialchars($value["os_installed_formatted"]); if ($value["os_installed"] === 1 && strFilter($value["domain"]) === 0) { echo "<img style='width: auto; height: 1.5em;' src='/images/azure-ad-logo.png'>"; }?>
            </td>
            <td><?php echo htmlspecialchars($value["battery_charge"]); ?>(<?php echo $value["battery_status"]; ?>)</td>
            <td id='uptime'><?php echo htmlspecialchars($value["uptime"]); ?></td>
            <td id='presentCPUTemp'><?php echo htmlspecialchars($value["cpu_temp"]); ?></td>
            <td id='presentDiskTemp'><?php echo htmlspecialchars($value["disk_temp"]); ?></td>
          </tr>
          <?php 
          //Close Loop
          } }
          ?>
        </tbody>
      </table>
    </div>

    <div class='pagetitle'>
      <h3>Clients <u>NOT</u> Currently Present</h3>
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
      // Clients not present
      $db->select("SELECT tagnumber, DATE_FORMAT(present, '%m/%d/%y, %r') AS 'time_formatted', status, CONCAT(battery_charge, '%') AS 'battery_charge', battery_status, CONCAT(cpu_temp, '°C') AS 'cpu_temp',  CONCAT(disk_temp, '°C') AS 'disk_temp', CONCAT(watts_now, ' Watts') AS 'watts_now' FROM remote WHERE present_bool IS NULL ORDER BY present DESC, tagnumber DESC");
      if (arrFilter($db->get()) === 0) {
      foreach ($db->get() as $key => $value) {
      echo "<tr>". PHP_EOL;
      echo "<td>" . PHP_EOL;
      echo "<b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value["tagnumber"]) . "' target='_blank'>" . htmlspecialchars($value["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b>" . PHP_EOL;
      echo "</td>";
      echo "<td>" . $value["time_formatted"] . "</td>" . PHP_EOL;
      $db->Pselect("SELECT locationFormatting(location) AS 'location_formatted' FROM locations WHERE tagnumber = :tagnumber AND location IS NOT NULL ORDER BY time DESC LIMIT 1", array(':tagnumber' => $value["tagnumber"]));
      if (arrFilter($db->get()) === 0) {
      foreach ($db->get() as $key => $value1) {
      echo "<td id='absentLocation'><b><a href='locations.php?location=" . htmlspecialchars($value1["location"]) . "' target='_blank'>" . htmlspecialchars($value1["location"]) . "</a></b></td>" . PHP_EOL;
      }
      } else {
      echo "<td><b>" . "<i>No Location</i>" . "</b></td>" . PHP_EOL;
      }
      unset($value1);

      echo "<td>" . htmlspecialchars($value["status"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
      if (strFilter($value["battery_charge"]) === 0) {
      echo "<td>" . htmlspecialchars($value["battery_charge"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
      if (strFilter($value["battery_status"]) === 0) {
      " (" . htmlspecialchars($value["battery_status"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . ")";
      }
      echo "</td>" . PHP_EOL;
      } else {
      echo "<td></td>";
      }
      echo "<td>" . htmlspecialchars($value["cpu_temp"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
      echo "<td>" . htmlspecialchars($value["disk_temp"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
      echo "<td> " . htmlspecialchars($value["watts_now"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
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
    <div class="uit-footer">
    <img src="/images/uh-footer.svg">
    </div>
  </body>
</html>