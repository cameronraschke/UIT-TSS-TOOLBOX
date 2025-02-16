<?php
require('/var/www/html/management/header.php');
require('/var/www/html/management/php/include.php');

if (isset($_POST['refresh-stats'])) {
  include('/var/www/html/management/php/uit-sql-refresh-location');
  unset($_POST["refresh-stats"]);
}

$db = new db();
?>


<?php
//POST stuff
if (isset($_POST['serial'])) {
  $uuid = uniqid("location-", true);
  $tagNum = $_POST["tagnumber"];
  $serial = $_POST['serial'];
  $location = $_POST['location'];
  $status = $_POST["status"];
  $diskRemoved = $_POST['disk_removed'];
  $department = $_POST['department'];
  $note = $_POST['note'];
  
  //Not the same insert statment as client parse code, ether address is DEFAULT here.
  $db->insertJob($uuid);
  $db->updateJob("tagnumber", $tagNum, $uuid);
  $db->updateJob("system_serial", $serial, $uuid);
  $db->updateJob ("date", $date, $uuid);
  $db->updateJob ("time", $time, $uuid);
  $db->updateJob ("department", $department, $uuid);
  
  // See if OS is installed
  $db->Pselect("SELECT erase_completed, clone_completed FROM jobstats WHERE tagnumber = :tagnumber AND (erase_completed = '1' OR clone_completed = '1') ORDER BY time DESC LIMIT 1", array(':tagnumber' => $tagNum));
  if (arrFilter($db->get()) === 0) {
    foreach ($db->get() as $key => $value1) {
      if ($value1["erase_completed"] === 1 && $value1["clone_completed"] === 1) {
        $osInstalled = 1;
      } elseif ($value1["erase_completed"] === 1 && $value1["clone_completed"] !== 1) {
        $osInstalled = 0;
      } elseif ($value1["erase_completed"] !== 1 && $value1["clone_completed"] === 1) {
        $osInstalled = 1;
      } else {
        $osInstalled = 1;
      }
    }
  }
  unset($value1);
  
  // BIOS updated
  unset($sql);
  $sql = "SELECT jobstats.bios_version, static_bios_stats.bios_version AS 'static_bios_version'
    FROM jobstats
    INNER JOIN system_data ON jobstats.tagnumber = system_data.tagnumber
    INNER JOIN static_bios_stats ON system_data.system_model = static_bios_stats.system_model 
    WHERE jobstats.tagnumber = :tagnumber AND jobstats.bios_version IS NOT NULL 
    ORDER BY jobstats.time DESC LIMIT 1";
  
  $db->Pselect($sql, array(':tagnumber' => $_POST["tagnumber"]));
    if (arrFilter($db->get()) === 0) {
      foreach ($db->get() as $key => $value1) {
        if (strFilter($value1["bios_version"])) {
          if ($value1["bios_version"] == $value1["static_bios_version"]) {
            $biosBool = 1;
          } else {
            $biosBool = 0;
          }
        }
      }
    } else {
      $biosBool = 0;
    }
  unset($value1);
  
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

  //Printing
  if ($_POST["print"] == "1") {
    $tagNum = escapeshellcmd($_POST["tagnumber"]);
    $customerName = escapeshellcmd($_POST["customer_name"]);
    $checkoutDate = escapeshellcmd($_POST["checkout_date"]);
    $customerPSID = escapeshellcmd($_POST["customer_psid"]);
    $returnDate = escapeshellcmd($_POST["return_date"]);

    $db->Pselect("SELECT DATE_FORMAT(:checkoutDate, '%b %D, %Y') AS 'checkout_date'", array(':checkoutDate' => $checkoutDate));
    foreach ($db->get() as $key => $value1) {
      $checkoutDate = $value1["checkout_date"];
    }
    unset($value1);

    $db->Pselect("SELECT DATE_FORMAT(:returnDate, '%b %D, %Y') AS 'return_date'", array(':returnDate' => $returnDate));
    foreach ($db->get() as $key => $value1) {
      $returnDate = $value1["return_date"];
    }
    unset($value1);

    System("bash /var/www/html/management/bash/uit-print-pdf" . " " . escapeshellarg("UHouston!") . " " . escapeshellarg($tagNum) . " " . escapeshellarg($customerName) . " " . escapeshellarg($checkoutDate) . " " . escapeshellarg($customerPSID) . " " . escapeshellarg($returnDate));
  }
  
  unset($_POST);
  header("Location: " . $_SERVER['REQUEST_URI']);
  unset($_POST);
}
?>


<html>
  <head>
    <meta charset='UTF-8'>
    <link rel='stylesheet' type='text/css' href='/css/main.css' />
    <link rel="stylesheet" href="/jquery/jquery-ui/jquery-ui-1.14.0/jquery-ui.min.css">
    <script src="/jquery/jquery-3.7.1.min.js"></script>
    <script src="/jquery/jquery-ui/jquery-ui-1.14.0/jquery-ui.min.js"></script>
    <title>Locations - UIT Client Mgmt</title>
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    <style>
      .ui-autocomplete {
      max-height: 100px;
      overflow-y: auto;
      overflow-x: hidden;
      }
    </style>
  </head>
  <body>
    <script>
      $( function() {
      var availableTags = [
        <?php
        //Select all distinct tagnumbers and put them into a JavaScript array
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
    
    <div class='pagetitle'><h1>Locations Table</h1></div>

    <div class='pagetitle'><h2>The locations table displays the location and status of every client.</h2></div>

    <div class="row">
      <div class="column">
      <?php
      //If tagnumber is POSTed, show data in the location form.
      if (isset($_POST["tagnumber"])) {
        unset($formSql);
        $formSql = "SELECT 
          jobstats.system_serial, locations.location, 
          DATE_FORMAT(locations.time, '%b %D %Y, %r') AS 'time_formatted', 
          jobstats.department, locations.disk_removed, locations.status, t3.note, t4.department_readable, 
          DATE_FORMAT(t3.time, '%b %D %Y, %r') AS 'note_time_formatted'
          FROM locations 
          INNER JOIN jobstats ON jobstats.tagnumber = locations.tagnumber 
          INNER JOIN (SELECT time, ROW_NUMBER() OVER(PARTITION BY tagnumber ORDER BY time DESC) AS 'row_count' FROM locations) t1 
            ON t1.time = locations.time 
          INNER JOIN (SELECT time, ROW_NUMBER() OVER(PARTITION BY tagnumber ORDER BY time DESC) AS 'row_count' FROM locations) t2 
            ON t2.time = jobstats.time 
          LEFT JOIN (SELECT tagnumber, time, note, ROW_NUMBER() OVER(PARTITION BY tagnumber ORDER BY time DESC) AS 'row_count' FROM locations WHERE note IS NOT NULL) t3 
            ON t3.tagnumber = locations.tagnumber
          LEFT JOIN (SELECT department, department_readable FROM departments) t4 
            ON t4.department = jobstats.department
          WHERE t1.row_count = 1 AND t2.row_count = 1 AND (t3.row_count = 1 OR t3.row_count IS NULL)
            AND jobstats.tagnumber = :tagnumberJob AND locations.tagnumber = :tagnumberLoc";
        
        unset($formArr);
        $db->Pselect($formSql, array(':tagnumberJob' => htmlspecialchars_decode($_POST["tagnumber"]), ':tagnumberLoc' => htmlspecialchars_decode($_POST["tagnumber"])));
        if ($db->get() === "NULL") {
          $formArr = array( array( "system_serial" => "NULL", "location" => "NULL", "time_formatted" => "NULL") );
          $tagDataExists = 0;
        } else {
          $formArr = $db->get();
          $tagDataExists = 1;
        }

        echo "
        <div class='page-content'><h2>Update Client Locations</h2></div>
        <div class='location-form'>" . PHP_EOL;


        foreach ($formArr as $key => $value) {
          //Tag number
          echo "
          <form method='post'>
            <div class='row'>
              <div class='column'>
                <div><label for='tagnumber'>Tag Number</label></div>
                <input type='text' style='background-color:#888B8D;' id='tagnumber' name='tagnumber' value='" . htmlspecialchars($_POST["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' readonly required>
              </div>";


          // Change appearance of serial number field based on sql data
            echo "
              <div class='column'>
                <div><label for='serial'>Serial Number</label></div>";
          if ($tagDataExists === 1) {
            echo "<input type='text' style='background-color:#888B8D;' id='serial' name='serial' value='" . htmlspecialchars($value["system_serial"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' readonly required>" . PHP_EOL;
          } else {
            echo "<input type='text' id='serial' name='serial' autocomplete='off' autofocus required>" . PHP_EOL;
          }
          echo "</div>";

          // Close first row DIV
          echo "</div>";


          echo "
          <div class='row'>
            <div class='column'>";
          // Location data
          if ($tagDataExists === 1) {
            echo "<div><label for='location'>Location (Last Updated: " . htmlspecialchars($value["time_formatted"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . ")</label></div>" . PHP_EOL;
            echo "<input type='text' id='location' name='location' value='" . htmlspecialchars($value["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' autofocus required>" . PHP_EOL;
          } else {
            echo "<div><label for='location'>Location</label></div>" . PHP_EOL;
            echo "<input type='text' id='location' name='location' required>" . PHP_EOL;
          }
            echo "</div>";

          // Department data in the form
          echo "<div class='column'>";
          echo "<div><label for='department'>Department</label></div>" . PHP_EOL;
          echo "<select name='department' id='department'>" . PHP_EOL;
          if ($tagDataExists === 1) {
            echo "<option value='" . htmlspecialchars($value["department"]) . "'>" . htmlspecialchars($value["department_readable"]) . "</option>" . PHP_EOL;
            $db->Pselect("SELECT department, department_readable 
              FROM departments WHERE NOT department = :department", array(':department' => $value["department"]));
            foreach ($db->get() as $key => $value1) {
              echo "<option value='" . htmlspecialchars($value1["department"]) . "'>" . htmlspecialchars($value1["department_readable"]) . "</option>";
            }
            unset($value1);
          } else {
            echo "<option value=''>--Please Select--</option>";
            $db->select("SELECT department, department_readable FROM departments");
            foreach ($db->get() as $key => $value1) {
              echo "<option value='" . htmlspecialchars($value1["department"]) . "'>" . htmlspecialchars($value1["department_readable"]) . "</option>";
            }
            unset($value1);
          }
          echo "</select>" . PHP_EOL;
          echo "</div></div>";

          // POST if the disk is removed
          echo "
          <div class='row'>";

          // POST status (working or broken) of the client
          echo "<div class='column'>
          <div><label for='status'>Working or Broken?</label></div>
          <select name='status' id='status'>";
          if ($value["status"] === 1) {
            echo "
            <option value='1'>Broken</option>
            <option value='0'>Working</option>";
          } else {
            echo "
            <option value='0'>Working</option>
            <option value='1'>Broken</option>";
          }
          echo "</select></div>";


          echo "<div class='column'>
            <div><label for='disk_removed'>Disk removed?</label></div>
              <select name='disk_removed' id='disk_removed'>" . PHP_EOL;
                if ($value["disk_removed"] === 1) {
                  echo "<option value='1'>Yes</option>" . PHP_EOL;
                  echo "<option value='0'>No</option>" . PHP_EOL;
                } else {
                  echo "<option value='0'>No</option>" . PHP_EOL;
                  echo "<option value='1'>Yes</option>" . PHP_EOL;
                }
          echo "</select></div>" . PHP_EOL;



          //Close row div
          echo "</div>";




          // Most recent note
          echo "<div class='row'>";

          // Get most recent note that's not NULL
          if ($tagDataExists === 1) {
            if ($value["status"] === 1) {
              echo "<div><label for='note'>Note (" . $value["note_time_formatted"] . ")</label></div>" . PHP_EOL;
              echo "<textarea id='note' name='note'>" . htmlspecialchars($value["note"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) .  "</textarea>" . PHP_EOL;
            } else {
              echo "<div><label for='note'>Note</label></div>" . PHP_EOL;
              echo "<textarea id='note' name='note' placeholder='(" . htmlspecialchars($value["note_time_formatted"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "): ". htmlspecialchars($value["note"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) .  "'></textarea>" . PHP_EOL;
            }
          } else {
              echo "<textarea id='note' name='note'></textarea>" . PHP_EOL;
          }
          echo "</div>" . PHP_EOL;


          // Print customer form
          echo "
          <div class='row'>
            <label for='print'>Print Customer Form</label>
            <select name='print' id='print'>
              <option value='0'>No</option>
              <option value='1'>Yes</option>
            </select>
            </div>
            <div class='row'>
              <div class='column'>";
                  $db->select("SELECT DATE_FORMAT(NOW(), '%Y-%m-%d') AS 'cur_date', DATE_FORMAT(NOW() + INTERVAL 1 WEEK, '%Y-%m-%d') AS 'next_date'");
                  foreach ($db->get() as $key => $value1) {
                    echo "<div><div><label for='checkout_date'>Checkout date: </label></div>";
                    echo "<input type='date' id='checkout_date' name='checkout_date' value='" . htmlspecialchars($value1["cur_date"]) . "' min='2020-01-01' /></div>";
                    echo "<div><div><label for='return_date'>Return date: </label></div>";
                    echo "<input type='date' id='return_date' name='return_date' value='" . htmlspecialchars($value1["next_date"]) . "' min='2020-01-01' /></div>";
                  }
                  unset($value1);      
              echo "</div>";
              echo "<div class='column'>";
              echo "<div><div><label for='customer_name'>Customer name: </label></div>";
              echo "<input type='text' name='customer_name' id='customer_name' placeholder='Customer Name'></div>";
              echo "<div><div><label for='customer_psid'>Customer PSID: </label></div>";
              echo "<input type='text' name='customer_psid' id='customer_psid' placeholder='Customer PSID'></div>";
              echo "</div>";
        echo "</div>";

          echo "<div class='row'>";
          echo "<a href='/locations.php'><button>Cancel</button></a>";
          if ($value["status"] === 1) {
            echo "<button style='background-color:rgba(200, 16, 47, 0.30); margin-left: 1em;' type='submit' value='Update Location Data (Broken)'>Update Location Data (Broken)</button>" . PHP_EOL;
          } else {
            echo "<button style='background-color:rgba(0, 179, 136, 0.30); margin-left: 1em;' type='submit' value='Update Location Data'>Update Location Data</button>" . PHP_EOL;
          }
          echo "</div>";
          echo "</form>" . PHP_EOL;
          echo "</div>";
          }
          unset($formArr);
      } else {
        echo "
          <div class='page-content'><h2>Update Client Locations</h2></div>
          <div class='location-form'>
            <form method='post'>
              <div><label for='tagnumber'>Enter a Tag Number: </label></div>
                <input type='text' id='tagnumber' name='tagnumber' placeholder='Tag Number' autofocus required>
              <button type='submit' value='Continue'>Continue</button>
            </form>
        </div>";
      }
      ?>
      <!-- this div closes the first "column" div -->
    </div>

<?php
// Dynamic SQL query for the main table.
unset($sql);
$tableArr = array();
$sqlArr = array();
$rowCount = 0;
$onlineRowCount = 0;
$sql="SELECT locations.tagnumber, remote.present_bool, locations.system_serial, system_data.system_model, locations.location,
  (CASE 
    WHEN jobstats.department = 'techComm' THEN 'Tech Commons (TSS)'
    WHEN jobstats.department = 'property' THEN 'Property'
    WHEN jobstats.department = 'shrl' THEN 'SHRL'
    WHEN jobstats.department = 'execSupport' THEN 'Exec Support'
    ELSE '' 
  END) AS 'department_formatted', jobstats.department,
  IF ((locations.status = 0 OR locations.status IS NULL), 'Working', 'Broken') AS 'status',
  IF (locations.os_installed = 1, 'Yes', 'No') AS 'os_installed_formatted', locations.os_installed,
  IF (locations.bios_updated = 1, 'Yes', 'No') AS 'bios_updated_formatted', locations.bios_updated,
  IF (remote.kernel_updated = 1, 'Yes', 'No') AS 'kernel_updated_formatted', remote.kernel_updated,
  locations.note AS 'note', DATE_FORMAT(locations.time, '%b %D %Y, %r') AS 'time_formatted'
  FROM locations
  INNER JOIN jobstats ON jobstats.tagnumber = locations.tagnumber
  INNER JOIN remote ON remote.tagnumber = locations.tagnumber
  LEFT JOIN system_data ON system_data.tagnumber = locations.tagnumber
  WHERE locations.tagnumber IS NOT NULL AND jobstats.tagnumber IS NOT NULL
  AND locations.time in (select MAX(time) from locations group by tagnumber)
  AND jobstats.time in (select MAX(time) from jobstats group by tagnumber)";

// Location filter
if (strFilter($_GET["location"]) === 0) {
  if ($_GET["not-location"] == "1") {
    $sql .= "AND NOT locations.location = :location ";
    $sqlArr[":location"] = $_GET["location"];
  } else {
    $sql .= "AND locations.location = :location ";
    $sqlArr[":location"] = $_GET["location"];
  }
}

// department filter
if (strFilter($_GET["department"]) === 0) {
  if ($_GET["not-department"] == "1") {
    $sql .= "AND NOT jobstats.department = :department ";
    $sqlArr[":department"] = $_GET["department"];
  } else {
    $sql .= "AND jobstats.department = :department ";
    $sqlArr[":department"] = $_GET["department"];
  }
}

// System model filter
if (strFilter($_GET["system_model"]) === 0) {
  if ($_GET["not-system_model"] == "1") {
    $sql .= "AND NOT system_data.system_model = :systemmodel ";
    $sqlArr[":systemmodel"] = $_GET["system_model"];
  } else {
    $sql .= "AND system_data.system_model = :systemmodel ";
    $sqlArr[":systemmodel"] = $_GET["system_model"];
  }
}

// Lost filter
if ($_GET["lost"] == "0") {
  $sql .= "AND NOT (locations.time <= NOW() - INTERVAL 3 MONTH
    OR (locations.location = 'Stolen' OR locations.location = 'Lost' OR locations.location = 'Missing' OR locations.location = 'Unknown')) ";
} elseif ($_GET["lost"] == "1") {
  $sql .= "AND (locations.time <= NOW() - INTERVAL 3 MONTH
    OR (locations.location = 'Stolen' OR locations.location = 'Lost' OR locations.location = 'Missing' OR locations.location = 'Unknown')) ";
}

// Broken filter
if ($_GET["broken"] == "0") {
  $sql .= "AND (locations.status IS NULL OR locations.status = 0) ";
} elseif ($_GET["broken"] == "1") {
  $sql .= "AND (locations.status = 1 OR locations.status IS NOT NULL) ";
}

// Disk removed filter
if ($_GET["disk_removed"] == "0") {
  $sql .= "AND (locations.disk_removed IS NULL OR locations.disk_removed = 0) ";
} elseif ($_GET["disk_removed"] == "1") {
  $sql .= "AND (locations.disk_removed = 1 OR locations.disk_removed IS NOT NULL) ";
}

// OS Installed filter
if ($_GET["os_installed"] == "0") {
  $sql .= "AND (locations.os_installed IS NULL OR locations.os_installed = 0) ";
} elseif ($_GET["os_installed"] == "1") {
  $sql .= "AND (locations.os_installed = 1 OR locations.os_installed IS NOT NULL) ";
}

// Order by modifiers
if (isset($_GET["order_by"])) {
  $sql .= "ORDER BY ";
  if ($_GET["order_by"] == "tag_desc") {
    $sql .= "locations.tagnumber DESC, ";
  }
  if ($_GET["order_by"] == "tag_asc") {
    $sql .= "locations.tagnumber ASC, ";
  }
  if($_GET["order_by"] == "time_desc") {
    $sql .= "locations.time DESC, ";
  }
  if($_GET["order_by"] == "time_asc") {
    $sql .= "locations.time ASC, ";
  }
  if($_GET["order_by"] == "os_desc") {
    $sql .= "locations.os_installed DESC, ";
  }
  if($_GET["order_by"] == "os_asc") {
    $sql .= "locations.os_installed ASC, ";
  }
  if($_GET["order_by"] == "bios_desc") {
    $sql .= "locations.bios_updated DESC, ";
  }
  if($_GET["order_by"] == "bios_asc") {
    $sql .= "locations.bios_updated ASC, ";
  }
  if($_GET["order_by"] == "location_desc") {
    $sql .= "locations.location DESC, ";
  }
  if($_GET["order_by"] == "location_asc") {
    $sql .= "locations.location ASC, ";
  }
  if($_GET["order_by"] == "model_desc") {
    $sql .= "system_data.system_model DESC, ";
  }
  if($_GET["order_by"] == "model_asc") {
    $sql .= "system_data.system_model ASC, ";
  }
  $sql .= "locations.time DESC ";
} else {
  $sql .= "ORDER BY locations.time DESC";
}

// Do the query
if (isset($_GET["location"]) || isset($_GET["system_model"]) || isset($_GET["department"])) {
  $db->Pselect($sql, $sqlArr);
} else {
  $db->select($sql);
}

// Put results of query into a PHP array
if (arrFilter($db->get()) === 0) {
  $tableArr = $db->get();
  foreach ($db->get() as $key => $value) {
    $rowCount = count($db->get());
    $onlineRowCount = $value["present_bool"] + $onlineRowCount;
  }
}
?>


    <div class="column">
    <div class='page-content'><h2>View and Search Current Locations</h2></div>
    <div class='filtering-form'>
      <form method="GET" action="">
        <div class='filtering-form'>

          <div>
            <label for="location-filter">
              <input type="checkbox" id="not-location" name="not-location" value="1"> NOT
            </label>
            <select name="location" id="location-filter">
              <option value="">--Filter By Location--</option>
              <?php
              $db->select("SELECT COUNT(location) AS location_rows, location FROM locations WHERE time IN (SELECT MAX(time) FROM locations WHERE tagnumber IS NOT NULL GROUP BY tagnumber) GROUP BY location ORDER BY location ASC");
              if (arrFilter($db->get()) === 0) {
                foreach ($db->get() as $key => $value1) {
                  echo "<option value='" . htmlspecialchars($value1["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "'>" . htmlspecialchars($value1["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . " (" . htmlspecialchars($value1["location_rows"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . ")" . "</option>" . PHP_EOL;
                }
              }
              unset($value1);
              ?>
            </select>
          </div>

          <div>
            <label for="department">
              <input type="checkbox" id="not-department" name="not-department" value="1"> NOT
            </label>
            <select id="department" name="department">
            <option value=''>--Filter By Department--</option>
              <?php
              $db->select("SELECT department, department_readable, owner, department_bool FROM departments ORDER BY department ASC");
              if (arrFilter($db->get()) === 0) {
                foreach ($db->get() as $key => $value1) {
                  $db->Pselect("SELECT COUNT(tagnumber) AS 'department_rows' FROM jobstats WHERE department = :department AND time IN (SELECT MAX(time) FROM jobstats WHERE department IS NOT NULL GROUP BY tagnumber)", array(':department' => $value1["department"]));
                  if (arrFilter($db->get()) === 0) {
                    foreach ($db->get() as $key => $value2) {
                      echo "<option value='" . htmlspecialchars($value1["department"]) . "'>" . htmlspecialchars($value1["department_readable"]) . " (" . $value2["department_rows"] . ")</option>" . PHP_EOL;
                    }
                  }
                }
              }
              unset($value1);
              unset($value2);
              ?>
            </select>
          </div>

          <div>
            <label for="system_model">
              <input type="checkbox" id="not-system_model" name="not-system_model" value="1"> NOT
            </label>
            <select id="system_model" name="system_model">
              <option value=''>--Filter By Model--</option>
              <?php
              $db->select("SELECT system_model,
                COUNT(system_model) AS 'system_model_rows'
                FROM system_data
                WHERE system_model IS NOT NULL
                GROUP BY system_model
                ORDER BY system_model ASC");
              if (arrFilter($db->get()) === 0) {
                foreach ($db->get() as $key => $value1) {
                  echo "<option value='" . htmlspecialchars($value1["system_model"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "'>" . htmlspecialchars($value1["system_model"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . " (" . $value1["system_model_rows"] . ")" . "</option>" . PHP_EOL;
                }
              }
              unset($value1);
              ?>
            </select>
          </div>

          <div>
            <select id="order_by" name="order_by">
              <option value=''>--Order By--</option>
              <option value="time_desc">Time &#8595;</option>
              <option value="time_asc">Time &#8593;</option>
              <option value="tag_desc">Tagnumber &#8595;</option>
              <option value="tag_asc">Tagnumber &#8593;</option>
              <option value="location_desc">Location &#8595;</option>
              <option value="location_asc">Location &#8593;</option>
              <option value="model_desc">Model &#8595;</option>
              <option value="model_asc">Model &#8593;</option>
              <option value="os_desc">OS Installed &#8595;</option>
              <option value="os_asc">OS Installed &#8593;</option>
              <option value="bios_desc">BIOS Updated &#8595;</option>
              <option value="bios_asc">BIOS Updated &#8593;</option>
            </select>
          </div>
        </div>

        <div class="row">
          <div class="dense-column">
            <p>Device Lost?</p>
            <div class="column">
              <label for="lost_yes">Yes</label>
              <input type="radio" id="lost_yes" name="lost" value="1">
            </div>
            <div class="column">
              <label for="lost_no">No</label>
              <input type="radio" id="lost_no" name="lost" value="0">
            </div>
          </div>

          <div class="dense-column">
            <p>Device Broken?</p>
            <div class="column">
              <label for="broken_yes">Yes</label>
              <input type="radio" id="broken_yes" name="broken" value="1">
            </div>
            <div class="column">
              <label for="broken_no">No</label>
              <input type="radio" id="broken_no" name="broken" value="0">
            </div>
          </div>
        </div>

        <div class="row">
          <div class="dense-column">
            <p>Disk Removed?</p>
            <div class="column">
              <label for="disk_removed_yes">Yes</label>
              <input type="radio" id="disk_removed_yes" name="disk_removed" value="1">
            </div>
            <div class="column">
              <label for="disk_removed_no">No</label>
              <input type="radio" id="disk_removed_no" name="disk_removed" value="0">
            </div>
          </div>
          
          <div class="dense-column">
              <p>OS Installed?</p>
            <div class="column">
              <label for="os_installed_yes">Yes</label>
              <input type="radio" id="os_installed_yes" name="os_installed" value="1">
            </div>
            <div class="column">
              <label for="os_installed_no">No</label>
              <input type="radio" id="os_installed_no" name="os_installed" value="0">
            </div>
          </div>
        </div>

        <div class='filtering-form'>
            <a href='/locations.php'><button>Reset Filters</button></a>
            <button style='background-color:rgba(0, 179, 136, 0.30);' type="submit">Filter</button>
        </div>

        <div class='filtering-form'>
            <?php echo "Results: <b>" . $rowCount . "</b>" . PHP_EOL; ?>
        </div>
      </form>
    </div>
            </div>
            </div>

    <div class='page-content'><h3>A checkmark (<span style='color: #00B388'>&#10004;</span>) means a client is currently online and ready for a job.</h3></div>

<?php
if (strFilter($_GET["location"]) === 0) {
    echo "<div class='page-content'><h3><u>" . htmlspecialchars($onlineRowCount, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "/" . htmlspecialchars($rowCount, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</u> clients are online from location '" . htmlspecialchars($_GET["location"]) . "'.</h3></div>";
} else {
    echo "<div class='page-content'><h3><u>" . htmlspecialchars($onlineRowCount, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "/" . htmlspecialchars($rowCount, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</u> queried clients are online.</h3></div>";
}
?>


    <div class='styled-form'>
      <form method='post'>
        <div>
          <button type="submit">Refresh OS/BIOS Data</button>
        </div>
          <input type="hidden" id="refresh-stats" name="refresh-stats" value="refresh-stats" />
      </form>  
    </div>

    <div class='styled-form2'>
      <input type="text" id="myInput" onkeyup="myFunction()" placeholder="Search tag number...">
      <input type="text" id="myInputSerial" onkeyup="myFunctionSerial()" placeholder="Search serial number...">
      <input type="text" id="myInputLocations" onkeyup="myFunctionLocations()" placeholder="Search locations...">
    </div>

    <div class='styled-table'>
      <table id="myTable">
        <thead>
          <tr>
            <th>Tag Number</th>
            <th>System Serial</th>
            <th>System Model</th>
            <th>Location</th>
            <th>Department</th>
            <th>Status</th>
            <th>OS Installed</th>
            <th>BIOS Updated</th>
            <th>Note</th>
            <th>Time</th>
          </tr>
        </thead>
        <tbody>

<?php
//Main Table
foreach ($tableArr as $key => $value1) {

  echo "<tr>" . PHP_EOL;
  // Tagnumber
  echo "<td>" . PHP_EOL;
  // kernel and bios up to date (check mark)
  if ($value1["present_bool"] === 1 && ($value1["kernel_updated"] === 1 && $value1["bios_updated"] === 1)) {
    echo "<b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value1["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value1["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b> <span>&#10004;&#65039;</span>" . PHP_EOL;
  // BIOS out of date, kernel not updated (x)
  } elseif ($value1["present_bool"] === 1 && ($value1["kernel_updated"] !== 1 && $value1["bios_updated"] !== 1)) {
    echo "<b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value1["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value1["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b> <span>&#10060;</span>" . PHP_EOL;
  //BIOS out of date, kernel updated (warning sign)
  } elseif ($value1["present_bool"] === 1 && ($value1["kernel_updated"] === 1 && $value1["bios_updated"] !== 1)) {
    echo "<b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value1["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value1["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b> <span>&#9888;&#65039;</span>" . PHP_EOL;
  //BIOS updated, kernel out of date (x)
  } elseif ($value1["present_bool"] === 1 && ($value1["kernel_updated"] !== 1 && $value1["bios_updated"] === 1)) {
    echo "<b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value1["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value1["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b> <span>&#10060;</span>" . PHP_EOL;
  } else {
    echo "<b><a href='tagnumber.php?tagnumber=" . htmlspecialchars($value1["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value1["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b> <span>&#9940;</span>" . PHP_EOL;
  }
  echo "</td>" . PHP_EOL;

  // Serial Number
  echo "<td>" . htmlspecialchars($value1['system_serial'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;

  // System Model
  echo "<td><b><a href='locations.php?system_model=" . htmlspecialchars($value1['system_model'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "'>" . htmlspecialchars($value1['system_model'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b></td>" . PHP_EOL;

  // Location
  if (preg_match("/^[a-zA-Z]$/", $value1["location"])) {
    echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value1["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars(strtoupper($value1["location"]), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b></td>" . PHP_EOL;
  } elseif (preg_match("/^checkout$/i", $value1["location"])) {
    echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value1["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . "Checkout" . "</a></b></td>" . PHP_EOL;
  } else {
    echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value1["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value1["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b></td>" . PHP_EOL;
  }

  // Department
  echo "<td>" . htmlspecialchars($value1["department_formatted"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;

  // Status (working/broken)
  echo "<td>" . htmlspecialchars($value1['status'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;

  // Os installed
  echo "<td>" . htmlspecialchars($value1['os_installed_formatted'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;

  //BIOS updated
  echo "<td>" . htmlspecialchars($value1["bios_updated_formatted"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;

  // Note
  echo "<td>" . htmlspecialchars($value1['note'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;

  // Timestamp
  echo "<td>" . htmlspecialchars($value1['time_formatted'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . " </td>" . PHP_EOL;
}

echo "</tr>" . PHP_EOL;

unset($tableArr);
unset($value1);
?>

        </tbody>
      </table>
    </div>

    <script>
      function myFunction() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("myInput");
        filter = input.value.toUpperCase();
        table = document.getElementById("myTable");
        tr = table.getElementsByTagName("tr");

        for (i = 0; i < tr.length; i++) {
          td = tr[i].getElementsByTagName("td")[0];
          if (td) {
            txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
              tr[i].style.display = "";
            } else {
              tr[i].style.display = "none";
            }
          }
        }
      }

      function myFunctionSerial() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("myInputSerial");
        filter = input.value.toUpperCase();
        table = document.getElementById("myTable");
        tr = table.getElementsByTagName("tr");

        for (i = 0; i < tr.length; i++) {
          td = tr[i].getElementsByTagName("td")[1];
          if (td) {
            txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
              tr[i].style.display = "";
            } else {
              tr[i].style.display = "none";
            }
          }
        }
      }

      function myFunctionLocations() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("myInputLocations");
        filter = input.value.toUpperCase();
        table = document.getElementById("myTable");
        tr = table.getElementsByTagName("tr");

        for (i = 0; i < tr.length; i++) {
          td = tr[i].getElementsByTagName("td")[2];
          if (td) {
            txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
              tr[i].style.display = "";
            } else {
              tr[i].style.display = "none";
            }
          }
        }
      }

      if ( window.history.replaceState ) {
        window.history.replaceState( null, null, window.location.href );
      }
    </script>

    <div class="uit-footer">
        <img src="/images/uh-footer.svg">
    </div>

  </body>
</html>