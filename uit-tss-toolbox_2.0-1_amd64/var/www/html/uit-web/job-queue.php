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
          foreach ($dbPSQL->get() as $key => $value) {
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
            <th>Screenshot</th>
            <th>Last Job Time</th>
            <th>Location</th>
            <th>Current Status</th>
            <th>OS Installed</th>
            <th>Battery Charge</th>
            <th>Uptime</th>
            <th>CPU Temp</th>
            <th>Disk Temp</th>
            <th>Power Usage</th>
          </tr>
        </thead>
        <tbody id="onlineTableBody"></tbody>
      </table>
    </div>

    <div class='pagetitle'>
      <h3>Offline Clients</h3>
    </div>
    <div>
      <table id="remoteOfflineTable" width="100%">
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
      </table>
    </div>

    <script>
    if ( window.history.replaceState ) {
      window.history.replaceState( null, null, window.location.href );
    }
    </script>

    <script src="/js/include.js?<?php echo filemtime('js/include.js'); ?>"></script>
    <script src="/js/job-queue.js?<?php echo filemtime('js/job-queue.js'); ?>"></script>

    <script>
      autoFillTags();
    </script>

    <div class="uit-footer">
    <img src="/images/uh-footer.svg">
    </div>
  </body>
</html>