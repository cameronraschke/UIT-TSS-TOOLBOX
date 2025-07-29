<?php
require('/var/www/html/uit-web/header.php');
require('/var/www/html/uit-web/php/include.php');

if (strFilter($_GET["tagnumber"]) === 0) {
  if (preg_match('/^[0-9]{6}$/', trim($_GET["tagnumber"])) !== 1) {
    http_response_code(500);
    exit();
  }
}

if ($_SESSION['authorized'] != "yes") {
  die();
}

$dbPSQL = new dbPSQL();

if ($_GET["refresh"] == "1") {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://localhost:1411/api/refresh-client.php?password=DB_CLIENT_PASSWD&tagnumber=refresh-all");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($ch);
  curl_close($ch);
  header("Location: " . removeUrlVar($_SERVER["REQUEST_URI"], "refresh"));
}

if ($_GET["redirect"] == "1") {
  $queryString = getUrlVar($_SERVER["REQUEST_URI"]);
  $queryString = removeUrlVar($queryString, "tagnumber");
  $queryString = removeUrlVar($queryString, "edit");
  $queryString = removeUrlVar($queryString, "redirect");
  header("Location: " . $queryString);
}

if (isset($_GET["tagnumber"]) && $_GET["tagnumber"] == "") {
  $dbPSQL->select("SELECT (MAX(tagnumber) + 1) AS tagnumber FROM locations WHERE CAST(tagnumber AS VARCHAR) LIKE '999%'");
  foreach ($dbPSQL->get() as $key => $value) {
    $placeholderTag = $value["tagnumber"];
    $dbPSQL->Pselect("SELECT tagnumber FROM locations WHERE tagnumber = :tagnumber", array(':tagnumber' => $placeholderTag));
    foreach ($dbPSQL->get() as $key => $value1) {
      if (strFilter($value1["tagnumber"]) === 1) {
        header("Location: /locations.php");
        exit();
      }
    }
  }
  header("Location: " . addUrlVar($_SERVER["REQUEST_URI"], "tagnumber", $placeholderTag));
}
?>


<?php
//POST stuff

if (isset($_POST["tagnumber"]) && isset($_POST['serial']) && isset($_POST["location"])) {
  $updatedHTMLConfirmation = "<p>Updated <span style='color:rgb(0, 120, 50)'><b>&#10004;</b></span></p>";
  $uuid = uniqid("location-", true);
  $tagNum = trim($_POST["tagnumber"]);
  $serial = trim($_POST['serial']);
  $location = trim($_POST['location']);
  $status = boolval($_POST["status"]) ? true : false;
  $diskRemoved = boolval($_POST['disk_removed']) ? true : false;
  $department = trim($_POST['department']);
  $note = trim($_POST['note']);
  $domain = trim($_POST["domain"]);
  $checkoutDate = $_POST["checkout_date"];
  $returnDate = $_POST["return_date"];
  $customerName = trim($_POST["customer_name"]);
  //$customerPSID = trim($_POST["customer_psid"]);
  $systemModel = trim($_POST["model"]);
  $systemManufacturer = trim($_POST["system_manufacturer"]);


  // Insert & update system_data
  $dbPSQL->Pselect("SELECT tagnumber FROM system_data WHERE tagnumber = :tag", array(':tag' => $tagNum));
  if (strFilter($dbPSQL->get()) === 0) {
    foreach ($dbPSQL->get() as $key => $value1) {
      if (strFilter($value1["tagnumber"]) === 0) {
        $dbPSQL->updateSystemData($tagNum, "system_manufacturer", $systemManufacturer);
        $dbPSQL->updateSystemData($tagNum, "system_model", $systemModel);
        $dbPSQL->updateSystemData($tagNum, "time", $time);
      } else {
        $dbPSQL->insertSystemData($tagNum);
        $dbPSQL->updateSystemData($tagNum, "system_manufacturer", $systemManufacturer);
        $dbPSQL->updateSystemData($tagNum, "system_model", $systemModel);
        $dbPSQL->updateSystemData($tagNum, "time", $time);
      }
    }
  } else {
    $dbPSQL->insertSystemData($tagNum);
    $dbPSQL->updateSystemData($tagNum, "system_manufacturer", $systemManufacturer);
    $dbPSQL->updateSystemData($tagNum, "system_model", $systemModel);
    $dbPSQL->updateSystemData($tagNum, "time", $time);
  }

  unset($value1);
  
  //Insert location data
  $dbPSQL->insertLocation($time);
  $dbPSQL->updateLocation("tagnumber", $tagNum, $time);
  $dbPSQL->updateLocation("system_serial", $serial, $time);
  $dbPSQL->updateLocation("location", $location, $time);
  $dbPSQL->updateLocation("status", $status, $time);
  $dbPSQL->updateLocation("disk_removed", $diskRemoved, $time);
  $dbPSQL->updateLocation("note", $note, $time);
  $dbPSQL->updateLocation("domain", $domain, $time);
  $dbPSQL->updateLocation("department", $department, $time);

  //Insert checkout data
  if (strFilter($_POST["return_date"]) === 0 || strFilter($_POST["checkout_date"]) === 0) {
    $dbPSQL->insertCheckout($time);
    $dbPSQL->updateCheckout("tagnumber", $tagNum, $time);
    $dbPSQL->updateCheckout("customer_name", $customerName, $time);
    //$dbPSQL->updateCheckout("customer_psid", $customerPSID, $time);

    $postDate1 = new \DateTimeImmutable($_POST["return_date"]);
    $postDate2 = new \DateTimeImmutable($_POST["checkout_date"]);
    $returnDateDT = $postDate1->format('Y-m-d');
    $checkoutDateDT = $postDate2->format('Y-m-d');
    
    if ($date >= $returnDateDT && strFilter($_POST["return_date"]) === 0) {
      $dbPSQL->updateCheckout("checkout_bool", false, $time);
    } else {
      $dbPSQL->updateCheckout("checkout_bool", true, $time);
    }

    $dbPSQL->updateCheckout("checkout_date", $checkoutDate, $time);
    $dbPSQL->updateCheckout("return_date", $returnDate, $time);
    $dbPSQL->updateCheckout("note", $note, $time);
  }
  unset($value1);

  //Printing
  if ($_POST["print"] == "1") {
    $tagNum = ($_POST["tagnumber"]);
    $customerName = escapeshellarg(htmlspecialchars($_POST["customer_name"]));
    $checkoutDate = escapeshellarg(htmlspecialchars($_POST["checkout_date"]));
    $returnDate = escapeshellarg(htmlspecialchars($_POST["return_date"]));

    $dbPSQL->Pselect("SELECT TO_CHAR(:checkoutDate, 'MM/DD/YY') AS checkout_date", array(':checkoutDate' => $checkoutDate));
    foreach ($dbPSQL->get() as $key => $value1) {
      $checkoutDate = $value1["checkout_date"];
    }
    unset($value1);

    $dbPSQL->Pselect("SELECT TO_CHAR(:returnDate, 'MM/DD/YY') AS return_date", array(':returnDate' => $returnDate));
    foreach ($dbPSQL->get() as $key => $value1) {
      $returnDate = $value1["return_date"];
    }
    unset($value1);

    System("bash /var/www/html/uit-web/bash/uit-print-pdf" . " " . escapeshellarg("WEB_SVC_PASSWD") . " " . $tagNum . " " . $customerName . " " . $checkoutDate . " " . $returnDate;
  }

  unset($_POST);

  header("Location: " . getUrlVar($_SERVER["REQUEST_URI"]));
}

?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset='UTF-8'>
    <link rel='stylesheet' type='text/css' href='/css/main.css' />
    <?php
    if (strFilter($_GET["tagnumber"]) === 0) {
      echo "<title>Update Client " . htmlspecialchars($_GET["tagnumber"]) . " - UIT Client Mgmt</title>";
    } else {
      echo "<title>Locations - UIT Client Mgmt</title>";
    }
    
    ?>
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    <script src="/js/init.js?<?php echo filemtime('js/init.js'); ?>"></script>
  </head>
  <body>
  <?php include('/var/www/html/uit-web/php/navigation-bar.php'); ?>
    
    <div class="row">
      <div class="column">
      <?php
      //If tagnumber is POSTed, show data in the location form.
      if (isset($_GET["tagnumber"])) {
        unset($formSql);
        $formSql = "SELECT locations.tagnumber, TRIM(locations.system_serial) AS system_serial, TRIM(system_data.system_model) AS system_model, 
          TRIM(system_data.system_manufacturer) AS system_manufacturer, locationFormatting(locations.location) AS location_formatted, 
          TO_CHAR(locations.time, 'MM/DD/YY HH12:MI:SS AM') AS time_formatted, 
          locations.department, locations.disk_removed, locations.status, TRIM(t3.note) AS most_recent_note, t5.department_readable, 
          TO_CHAR(t3.time, 'MM/DD/YY HH12:MI:SS AM') AS note_time_formatted, locations.domain, locations.status, (CASE WHEN t3.time = locations.time THEN TRUE ELSE FALSE END) AS placeholder_bool
          FROM locations 
          LEFT JOIN jobstats ON jobstats.tagnumber = locations.tagnumber 
          INNER JOIN (SELECT time, ROW_NUMBER() OVER(PARTITION BY tagnumber ORDER BY time DESC) AS row_count FROM locations) t1 
            ON t1.time = locations.time 
          LEFT JOIN (SELECT time, ROW_NUMBER() OVER(PARTITION BY tagnumber ORDER BY time DESC) AS row_count FROM jobstats) t2 
            ON t2.time = jobstats.time 
          LEFT JOIN (SELECT tagnumber, time, note, ROW_NUMBER() OVER(PARTITION BY tagnumber ORDER BY time DESC) AS row_count FROM locations WHERE note IS NOT NULL) t3 
            ON t3.tagnumber = locations.tagnumber
          LEFT JOIN (SELECT department, department_readable FROM static_departments) t5
            ON locations.department = t5.department
          LEFT JOIN system_data ON locations.tagnumber = system_data.tagnumber
          WHERE t1.row_count = 1 AND (t2.row_count = 1 OR t2.row_count IS NULL) AND (t3.row_count = 1 OR t3.row_count IS NULL)
            AND locations.tagnumber = :tagnumberLoc";
        
        unset($formArr);
        $dbPSQL->Pselect($formSql, array(':tagnumberLoc' => $_GET["tagnumber"]));
        if ($dbPSQL->get() === "NULL") {
          $formArr = array( array( "system_serial" => "NULL", "location" => "NULL", "time_formatted" => "NULL") );
          $tagDataExists = 0;
        } else {
          $formArr = $dbPSQL->get();
          $tagDataExists = 1;
        }

        echo "
        <div class='page-content'><h2>Update Client Locations - " . trim(htmlspecialchars($_GET["tagnumber"])) . " <a href='/tagnumber.php?tagnumber=" . trim(htmlspecialchars($_GET["tagnumber"])) . "' target='_blank'><img class='new-tab-image' src='/images/new-tab.svg'></img>Open client details</a></h2></div>
        <div class='location-form'>" . PHP_EOL;


        foreach ($formArr as $key => $value) {
          //Tag number
          echo "
          <form method='post'>
            <div class='row'>
              <div class='column'>
                <div><label for='tagnumber'>Tag Number*</label></div>
                <input type='text' style='background-color:#888B8D;' id='tagnumber' placeholder='Enter tag number...' name='tagnumber' value='" . trim(htmlspecialchars($_GET["tagnumber"])) . "' readonly required>
              </div>";
            // Line above this closes tag number data div

          // Change appearance of serial number field based on sql data
            echo "
              <div class='column'>
                <div><label for='serial'>Serial Number*</label></div>";
          if ($tagDataExists === 1) {
            echo "<input type='text' style='background-color:#888B8D;' id='serial' name='serial' placeholder='Enter serial number...' value='" . trim(htmlspecialchars($value["system_serial"])) . "' readonly required>" . PHP_EOL;
          } else {
            echo "<input type='text' id='serial' name='serial' autocapitalize='none' autocomplete='off' autocorrect='off' spellcheck='false' placeholder='Enter serial number...' autofocus required>" . PHP_EOL;
          }
          // Close serial number column
          echo "</div>";

          // Close first row DIV
          echo "</div>";


          echo "
          <div class='row'>
            <div class='column'>";
          // Location data
          if ($tagDataExists === 1) {
            echo "<div><label for='location'>Location* (Last Updated: " . htmlspecialchars($value["time_formatted"]) . ")</label></div>" . PHP_EOL;
            echo "<input type='text' id='location' name='location' value='" . htmlspecialchars($value["location_formatted"]) . "' autofocus autocomplete='off' autocorrect='off' spellcheck='false' placeholder='Enter location...' required>" . PHP_EOL;
          } else {
            echo "<div><label for='location'>Location*</label></div>" . PHP_EOL;
            echo "<input type='text' id='location' name='location' autocomplete='off' autocorrect='off' spellcheck='false' placeholder='Enter location...' required>" . PHP_EOL;
          }
            // close location data column
            echo "</div>";

          // Department data in the form
          echo "<div class='column'>";
          echo "<div><label for='department'>Department</label></div>" . PHP_EOL;
          echo "<select name='department' id='department'>" . PHP_EOL;
          if ($tagDataExists === 1) {
            if (strFilter($value["department"]) === 0) {
              echo "<option value='" . htmlspecialchars($value["department"]) . "'>Current dept.: " . htmlspecialchars($value["department_readable"]) . "</option>" . PHP_EOL;
              $dbPSQL->Pselect("SELECT department, department_readable 
                FROM static_departments WHERE NOT department = :department ORDER BY department_readable ASC", array(':department' => $value["department"]));
              foreach ($dbPSQL->get() as $key => $value1) {
                echo "<option value='" . htmlspecialchars($value1["department"]) . "'>" . htmlspecialchars($value1["department_readable"]) . "</option>";
              }
            } else {
              echo "<option value=''>--Please Select--</option>";
              $dbPSQL->select("SELECT department, department_readable FROM static_departments ORDER BY department_readable ASC");
              foreach ($dbPSQL->get() as $key => $value1) {
                echo "<option value='" . htmlspecialchars($value1["department"]) . "'>" . htmlspecialchars($value1["department_readable"]) . "</option>";
              }
              unset($value1);
            }
            unset($value1);
          } else {
            echo "<option value=''>--Please Select--</option>";
            $dbPSQL->select("SELECT department, department_readable FROM static_departments ORDER BY department_readable ASC");
            foreach ($dbPSQL->get() as $key => $value1) {
              echo "<option value='" . htmlspecialchars($value1["department"]) . "'>" . htmlspecialchars($value1["department_readable"]) . "</option>";
            }
            unset($value1);
          }
          echo "</select>" . PHP_EOL;
          // Close department div
          echo "</div>";
          // close row div
          echo "</div>";

          // New row
          echo "<div class='row'>";
          // System Model
          echo "<div class='column'>";
          echo "<div><label for='model'>System Manufacturer/Model</label></div>" . PHP_EOL;
					if ($tagDataExists === 1) {
						if (strFilter($value["system_model"]) === 0) {
              echo "<input type='text' id='system_manufacturer' name='system_manufacturer' placeholder='Enter manufacturer...'value='" . trim(htmlspecialchars($value["system_manufacturer"])) . "'><input type='text' id='model' name='model' placeholder='Enter model...' value='" . trim(htmlspecialchars($value["system_model"])) . "'>";
            }  else {
              echo "<input type='text' id='system_manufacturer' name='system_manufacturer' autocomplete='off' autocorrect='off' spellcheck='false' placeholder='Enter manufacturer...'><input type='text' id='model' name='model' autocomplete='off' autocorrect='off' spellcheck='false' placeholder='Enter system model...'>";
            }
          } else {
              echo "<input type='text' id='system_manufacturer' name='system_manufacturer' autocomplete='off' autocorrect='off' spellcheck='false' placeholder='Enter manufacturer...'><input type='text' id='model' name='model' autocomplete='off' autocorrect='off' spellcheck='false' placeholder='Enter system model...'>";
          }
          // Close system model div
          echo "</div>" . PHP_EOL;


					// Joined to domain
          echo "<div class='column'>";
					echo "<div><label for='domain'>AD Domain</label></div>" . PHP_EOL;
					echo "<select name='domain' id='domain'>" . PHP_EOL;
					if ($tagDataExists === 1) {
						if (strFilter($value["domain"]) === 0) {
							$dbPSQL->Pselect("SELECT static_domains.domain, static_domains.domain_readable FROM static_domains INNER JOIN (SELECT domain FROM locations WHERE tagnumber = :tagnumber ORDER BY time DESC LIMIT 1) t1 ON static_domains.domain = t1.domain ORDER BY domain ASC", array(':tagnumber' => $value["tagnumber"]));
							foreach ($dbPSQL->get() as $key => $value1) {	
								echo "<option value='" . htmlspecialchars($value1["domain"]) . "'>" . htmlspecialchars($value1["domain_readable"]) . "</option>";
							}
							$dbPSQL->Pselect("SELECT static_domains.domain, static_domains.domain_readable FROM static_domains WHERE NOT domain = :domain ORDER BY domain ASC", array(':domain' => $value["domain"]));
							foreach ($dbPSQL->get() as $key => $value2) {
								echo "<option value='" . htmlspecialchars($value2["domain"]) . "'>" . htmlspecialchars($value2["domain_readable"]) . "</option>";
							}	
						} else {
							echo "<option value=''>--Select Domain--</option>";
							$dbPSQL->select("SELECT static_domains.domain, static_domains.domain_readable FROM static_domains ORDER BY domain ASC");
							foreach ($dbPSQL->get() as $key => $value2) {
								echo "<option value='" . htmlspecialchars($value2["domain"]) . "'>" . htmlspecialchars($value2["domain_readable"]) . "</option>";
							}
						}
						unset($value1);
						unset($value2);
						echo "<option value=''>No domain</option>";
					} else {
						echo "<option value=''>--Select Domain--</option>";
						$dbPSQL->select("SELECT static_domains.domain, static_domains.domain_readable FROM static_domains ORDER BY domain ASC");
						foreach ($dbPSQL->get() as $key => $value1) {
							echo "<option value='" . htmlspecialchars($value1["domain"]) . "'>" . htmlspecialchars($value1["domain_readable"]) . "</option>";
						}
						unset($value1);
						echo "<option value=''>No domain</option>";
					}
					echo "</select>";
          // close domain div
					echo "</div>";
          // Close row
          echo "</div>";
      

					// New row
          echo "<div class='row'>";

          // POST status (working or broken) of the client
          echo "<div class='column'>
          <div><label for='status'>Functional</label></div>
          <select name='status' id='status'>";
          if ($value["status"] === 1) {
            echo "
            <option id='status-broken' style='rgba(200, 16, 47, 0.31);' value='1'>No, Broken</option>
            <option id='status-working' style='rgba(0, 179, 137, 0.31);' value='0'>Yes</option>";
          } else {
            echo "
            <option id='status-working' style='rgba(200, 16, 47, 0.31);' value='0'>Yes</option>
            <option id='status-broken' style='rgba(0, 179, 137, 0.31);' value='1'>No, Broken</option>";
          }
          // Close column div
          echo "</select></div>";


          echo "<div class='column'>
            <div><label for='disk_removed'>Disk removed?</label></div>
              <select name='disk_removed' id='disk_removed'>" . PHP_EOL;
                if ($value["disk_removed"] === true) {
                  echo "<option id='disk_removed-true' value='1'>Yes</option>" . PHP_EOL;
                  echo "<option id='disk_removed-false' value='0'>No</option>" . PHP_EOL;
                } else {
                  echo "<option id='disk_removed-false' value='0'>No</option>" . PHP_EOL;
                  echo "<option id='disk_removed-true' value='1'>Yes</option>" . PHP_EOL;
                }
          // close column div
          echo "</select></div>" . PHP_EOL;
					//Close row div
					echo "</div>";


          // Most recent note
          echo "<div class='row'>";
          if (strFilter($value["most_recent_note"]) === 0 && $value["locations_status"] === 1) {
            echo "<div><label for='note'>Note (Last Entry: " . trim(htmlspecialchars($value["note_time_formatted"])) . ")</label></div>" . PHP_EOL;
            echo "<textarea id='note' name='note' style='width: 70%;'>" . htmlspecialchars($value["most_recent_note"]) .  "</textarea>" . PHP_EOL;
          } elseif (strFilter($value["most_recent_note"]) === 0 && strFilter($value["locations_status"]) === 1 && $value["placeholder_bool"] === TRUE)  {
            echo "<div><label for='note'>Note (Last Entry: " . trim(htmlspecialchars($value["note_time_formatted"])) . ")</label></div>" . PHP_EOL;
            echo "<textarea id='note' name='note' style='width: 70%;'>" . htmlspecialchars($value["most_recent_note"]) .  "</textarea>" . PHP_EOL;
          } elseif (strFilter($value["most_recent_note"]) === 0 && strFilter($value["locations_status"]) === 1 && $value["placeholder_bool"] !== TRUE) {
            echo "<div><label for='note'>Note (Last Entry: " . trim(htmlspecialchars($value["note_time_formatted"])) . ")</label></div>" . PHP_EOL;
            echo "<textarea id='note' name='note' style='width: 70%;' placeholder='" . htmlspecialchars($value["most_recent_note"]) . "'></textarea>" . PHP_EOL;
          } else {
            echo "<div><label for='note'>Note</label></div>" . PHP_EOL;
            echo "<textarea id='note' name='note' style='width: 70%;' placeholder='Enter Note...'></textarea>" . PHP_EOL;
          }
        

          echo "</div>" . PHP_EOL;


          	// Get most recent non-null note


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
                  $dbPSQL->Pselect("SELECT customer_name, checkout_date, return_date, checkout_bool, row_count FROM (SELECT TRIM(customer_name) AS customer_name, checkout_date, return_date, checkout_bool, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_count FROM checkouts WHERE tagnumber = :tagnumber) t1 WHERE t1.checkout_bool = TRUE AND t1.row_count = 1", array(':tagnumber' => $_GET["tagnumber"]));
                  if (strFilter($dbPSQL->get()) === 0) {
                    foreach ($dbPSQL->get() as $key => $value1) {
                      echo "<div><div><label for='checkout_date'>Checkout date: </label></div>";
                      //echo "<input type='date' id='checkout_date' name='checkout_date' value='" . htmlspecialchars($value1["checkout_date"]) . "' min='2020-01-01' /></div>";
                      echo "<input type='date' id='checkout_date' name='checkout_date' value='" . $value1["checkout_date"] . "' min='2020-01-01' /></div>";
                      echo "<div><div><label for='return_date'>Return date: </label></div>";
                      //echo "<input type='date' id='return_date' name='return_date' value='" . htmlspecialchars($value1["return_date"]) . "' min='2020-01-01' /></div>";
                      echo "<input type='date' id='return_date' name='return_date' value='" . $value1["return_date"] . "' min='2020-01-01' /></div>";
                      echo "</div>";
                      echo "<div class='column'>";
                      echo "<div><div><label for='customer_name'>Customer name: </label></div>";
                      echo "<input type='text' name='customer_name' id='customer_name' autocapitalize='words' autocomplete='off' autocorrect='off' spellcheck='false' value='" . htmlspecialchars($value1["customer_name"]) . "'></div>";
                      echo "<div><div><label for='customer_psid'>Customer PSID: </label></div>";
                      //echo "<input type='text' name='customer_psid' id='customer_psid' autocapitalize='none' autocomplete='off' autocorrect='off' spellcheck='false' value='" . htmlspecialchars($value1["customer_psid"]) . "'></div>";
                      echo "<input type='text' name='customer_psid' id='customer_psid' autocapitalize='none' autocomplete='off' autocorrect='off' spellcheck='false' placeholder='Customer PSID' style='background-color:#888B8D;' readonly></div>";
                      echo "</div>";
                    }
                  } else {
                    $dbPSQL->select("SELECT TO_CHAR(NOW(), 'YYYY-MM-DD') AS cur_date, TO_CHAR(NOW() + INTERVAL '1 WEEK', 'YYYY-MM-DD') AS next_date");
                    foreach ($dbPSQL->get() as $key => $value2) {
                      echo "<div><div><label for='checkout_date'>Checkout date: </label></div>";
                      //echo "<input type='date' id='checkout_date' name='checkout_date' value='" . htmlspecialchars($value2["cur_date"]) . "' min='2020-01-01' /></div>";
                      echo "<input type='date' id='checkout_date' name='checkout_date' value='' min='2020-01-01' /></div>";
                      echo "<div><div><label for='return_date'>Return date: </label></div>";
                      //echo "<input type='date' id='return_date' name='return_date' value='" . htmlspecialchars($value2["next_date"]) . "' min='2020-01-01' /></div>";
                      echo "<input type='date' id='return_date' name='return_date' value='' min='2020-01-01' /></div>";
                      echo "</div>";
                      echo "<div class='column'>";
                      echo "<div><div><label for='customer_name'>Customer name: </label></div>";
                      echo "<input type='text' name='customer_name' id='customer_name' autocapitalize='words' autocomplete='off' autocorrect='off' spellcheck='false' placeholder='Customer Name'></div>";
                      echo "<div><div><label for='customer_psid'>Customer PSID: </label></div>";
                      echo "<input type='text' name='customer_psid' id='customer_psid' autocapitalize='none' autocomplete='off' autocorrect='off' spellcheck='false' placeholder='Customer PSID' style='background-color:#888B8D;' readonly></div>";
                      echo "</div>";        
                    }
                    unset($value1);
                    unset($value2);
                  }
        echo "</div>";

          echo "<div class='row'>";
          if ($value["status"] === 1) {
            //echo "<button style='background-color:rgba(200, 16, 47, 0.30); margin-left: 1em;' type='submit' value='Update Location Data (Broken)'>Update Location Data (Broken)</button>" . PHP_EOL;
            echo "<button style='background-color:rgba(0, 179, 136, 0.30); margin-left: 1em;' type='submit' value='Update Location Data'>Update Location Data</button>" . PHP_EOL;
          } else {
            echo "<button style='background-color:rgba(0, 179, 136, 0.30); margin-left: 1em;' type='submit' value='Update Location Data'>Update Location Data</button>" . PHP_EOL;
          }
          echo "</form>";
          if ($_GET["ref"] == 1) {
            echo "<button type='button' id='closeButton' onclick=\"window.location.href = '/tagnumber.php?tagnumber=" . $_GET["tagnumber"] . "'\">Go Back</button>";
          } else {
            echo "<button style='margin-left: 1em;' type='button' value='Cancel' onclick=\"window.location.href = '" . addUrlVar($_SERVER["REQUEST_URI"], "redirect", "1") . "'\">Cancel</button>" . PHP_EOL;
          }
          
          echo "<div>" . $updatedHTMLConfirmation . "</div>";

          echo "</div>";
          echo "</div>";
          }
          unset($formArr);
      } else {
        echo "
          <div class='page-content'><h2>Update Client Locations</h2></div>
          <div class='location-form'>
            <form method='get'>
              <div><label for='tagnumber'>Enter a Tag Number: </label></div>
                <input type='text' id='tagnumber' name='tagnumber' autocapitalize='none' autocomplete='off' autocorrect='off' spellcheck='false' placeholder='Tag Number' autofocus>";

                foreach($_GET as $name => $value) {
                  $get_name = htmlspecialchars($name);
                  $get_value = htmlspecialchars($value);
                  echo "<input type='hidden' name='". $get_name . "' value='" . $get_value . "'>";
                }
                echo "<button type='submit' value='Continue'>Continue</button>
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
$sql="SELECT locations.tagnumber, remote.present_bool, locations.system_serial, system_data.system_model, 
  locationFormatting(locations.location) AS location_formatted,
  static_departments.department_readable AS department_formatted, locations.department,
  (CASE WHEN (locations.status = FALSE) THEN 'Yes' ELSE 'Broken' END) AS status_formatted, locations.status AS locations_status, 
  client_health.os_name AS os_installed_formatted, client_health.os_installed, client_health.os_name, 
  (CASE WHEN client_health.bios_updated = TRUE THEN 'Yes' ELSE 'No' END) AS bios_updated_formatted, client_health.bios_updated,
  (CASE WHEN remote.kernel_updated = TRUE THEN 'Yes' ELSE 'No' END) AS kernel_updated_formatted, remote.kernel_updated,
  locations.note AS note, TO_CHAR(locations.time, 'MM/DD/YY HH12:MI:SS AM') AS time_formatted, locations.domain, t2.checkout_bool, t2.checkout_date, t2.return_date,
  client_health.last_imaged_time
  FROM locations
    LEFT JOIN system_data ON locations.tagnumber = system_data.tagnumber
    LEFT JOIN static_departments ON locations.department = static_departments.department
    LEFT JOIN client_health ON locations.tagnumber = client_health.tagnumber
    LEFT JOIN remote ON locations.tagnumber = remote.tagnumber
    LEFT JOIN (SELECT time, tagnumber, checkout_date, customer_name, return_date, checkout_bool, row_nums FROM (SELECT time, tagnumber, checkout_date, customer_name, return_date, checkout_bool, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM checkouts WHERE time IS NOT NULL) s2 WHERE s2.row_nums = 1) t2 ON locations.tagnumber = t2.tagnumber
    LEFT JOIN (SELECT tagnumber, clone_image, row_nums FROM (SELECT tagnumber, clone_image, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM jobstats WHERE tagnumber IS NOT NULL AND time IS NOT NULL AND clone_completed = TRUE AND clone_image IS NOT NULL) s1 WHERE s1.row_nums = 1) t1
      ON locations.tagnumber = t1.tagnumber
    LEFT JOIN static_image_names ON t1.clone_image = static_image_names.image_name
  WHERE locations.tagnumber IS NOT NULL 
  AND locations.time IN (SELECT MAX(time) FROM locations GROUP BY tagnumber) ";




// Location filter
if (strFilter($_GET["location"]) === 0) {
  if ($_GET["location-bool"] == "0") {
    $sql .= "AND NOT locations.location = :location ";
    $sqlArr[":location"] = $_GET["location"];
  } else {
    $sql .= "AND locations.location = :location ";
    $sqlArr[":location"] = $_GET["location"];
  }
}

// department filter
if (strFilter($_GET["department"]) === 0) {
  if ($_GET["department-bool"] == "0") {
    $sql .= "AND (NOT locations.department = :department OR locations.department IS NULL) ";
    $sqlArr[":department"] = $_GET["department"];
  } else {
    $sql .= "AND locations.department = :department ";
    $sqlArr[":department"] = $_GET["department"];
  }
}

// domain filter
if (strFilter($_GET["domain"]) === 0) {
  if ($_GET["domain-bool"] == "0") {
    $sql .= "AND (NOT locations.domain = :domain OR locations.domain IS NULL) ";
    $sqlArr[":domain"] = $_GET["domain"];
  } else {
    $sql .= "AND locations.domain = :domain ";
    $sqlArr[":domain"] = $_GET["domain"];
  }
}

// System model filter
if (strFilter($_GET["system_model"]) === 0) {
  if ($_GET["system_model-bool"] == "0") {
    $sql .= "AND NOT system_data.system_model = :systemmodel ";
    $sqlArr[":systemmodel"] = $_GET["system_model"];
  } else {
    $sql .= "AND system_data.system_model = :systemmodel ";
    $sqlArr[":systemmodel"] = $_GET["system_model"];
  }
}

if (strFilter($_GET["customer_checkout_name"]) === 0) {
  if ($_GET["checkouts-bool"] == "0") {
    $sql .= "AND t2.checkout_bool = TRUE AND NOT t2.customer_name = :customercheckoutname ";
    $sqlArr[":customercheckoutname"] = $_GET["customer_checkout_name"];
  } else {
    $sql .= "AND t2.checkout_bool = TRUE AND t2.customer_name = :customercheckoutname ";
    $sqlArr[":customercheckoutname"] = $_GET["customer_checkout_name"];
  }
}

// // Lost filter
// if ($_GET["lost"] == "0") {
//   $sql .= "AND NOT (locations.time <= NOW() - INTERVAL 3 MONTH
//     OR (locations.location = 'Stolen' OR locations.location = 'Lost' OR locations.location = 'Missing' OR locations.location = 'Unknown')) ";
// } elseif ($_GET["lost"] == "1") {
//   $sql .= "AND (locations.time <= NOW() - INTERVAL 3 MONTH
//     OR (locations.location = 'Stolen' OR locations.location = 'Lost' OR locations.location = 'Missing' OR locations.location = 'Unknown')) ";
// }

if ($_GET["checkout"] == "0") {
  $sql .= "AND t2.checkout_bool = FALSE ";
} elseif ($_GET["checkout"] == "1") {
  $sql .= "AND t2.checkout_bool = TRUE ";
}

// Broken filter
if ($_GET["broken"] == "0") {
  $sql .= "AND locations.status IS NOT NULL ";
  $sql .= "AND locations.status = FALSE ";
} elseif ($_GET["broken"] == "1") {
  $sql .= "AND locations.status = TRUE ";
}

// Disk removed filter
if ($_GET["disk_removed"] == "0") {
  $sql .= "AND locations.disk_removed = FALSE ";
} elseif ($_GET["disk_removed"] == "1") {
  $sql .= "AND locations.disk_removed = TRUE ";
}

if ($_GET["order_by"] == "os_asc" || $_GET["order_by"] == "os_desc") {
    $sql .= "AND client_health.os_installed IS NOT NULL AND client_health.last_imaged_time IS NOT NULL ";
}

// OS Installed filter
if ($_GET["os_installed"] == "0") {
  $sql .= "AND client_health.os_installed IS NOT NULL ";
  $sql .= "AND client_health.os_installed = FALSE ";
} elseif ($_GET["os_installed"] == "1") {
  $sql .= "AND client_health.os_installed IS NOT NULL AND client_health.last_imaged_time IS NOT NULL ";
  $sql .= "AND client_health.os_installed = TRUE ";
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
    $sql .= "(CASE WHEN client_health.os_installed = TRUE THEN 10 WHEN client_health.os_installed IS FALSE THEN 5 WHEN client_health.os_installed IS NULL THEN 1 ELSE 1 END) DESC, client_health.last_imaged_time DESC, client_health.os_name ASC, ";
  }
  if($_GET["order_by"] == "os_asc") {
    $sql .= "(CASE WHEN client_health.os_installed = TRUE THEN 10 WHEN client_health.os_installed IS FALSE THEN 5 WHEN client_health.os_installed IS NULL THEN 1 ELSE 1 END) ASC, client_health.last_imaged_time DESC, client_health.os_name ASC, ";
  }
  if($_GET["order_by"] == "bios_desc") {
    $sql .= "client_health.bios_updated DESC, ";
  }
  if($_GET["order_by"] == "bios_asc") {
    $sql .= "client_health.bios_updated ASC, ";
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
if (isset($sqlArr)) {
  $dbPSQL->Pselect($sql, $sqlArr);
} else {
  $dbPSQL->select($sql);
}

// Put results of query into a PHP array
if (arrFilter($dbPSQL->get()) === 0) {
  $tableArr = $dbPSQL->get();
  foreach ($dbPSQL->get() as $key => $value) {
    $rowCount = $dbPSQL->get_rows();
    $onlineRowCount = boolval($value["present_bool"]) + $onlineRowCount;
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
              <input type="checkbox" id="location-bool" name="location-bool" value="0"> NOT
            </label>
            <select name="location" id="location-filter">
              <option value="">--Filter By Location--</option>
              <?php
              $dbPSQL->select("SELECT COUNT(location) AS location_rows, locationFormatting(location) AS location_formatted FROM locations WHERE time IN (SELECT MAX(time) FROM locations WHERE tagnumber IS NOT NULL GROUP BY tagnumber) GROUP BY locations.location ORDER BY locations.location ASC");
              if (arrFilter($dbPSQL->get()) === 0) {
                foreach ($dbPSQL->get() as $key => $value1) {
                  echo "<option value='" . htmlspecialchars($value1["location_formatted"]) . "'>" . htmlspecialchars($value1["location_formatted"]) . " (" . htmlspecialchars($value1["location_rows"]) . ")" . "</option>" . PHP_EOL;
                }
              }
              unset($value1);
              ?>
            </select>
          </div>

          <div>
            <label for="department">
              <input type="checkbox" id="department-bool" name="department-bool" value="0"> NOT
            </label>
            <select id="department" name="department">
            <option value=''>--Filter By Department--</option>
              <?php
              $dbPSQL->select("SELECT department, department_readable, owner, department_bool FROM static_departments ORDER BY department_readable ASC");
              if (arrFilter($dbPSQL->get()) === 0) {
                foreach ($dbPSQL->get() as $key => $value1) {
                  $dbPSQL->Pselect("SELECT COUNT(tagnumber) AS department_rows FROM locations WHERE department = :department AND time IN (SELECT MAX(time) FROM locations WHERE department IS NOT NULL GROUP BY tagnumber)", array(':department' => $value1["department"]));
                  if (arrFilter($dbPSQL->get()) === 0) {
                    foreach ($dbPSQL->get() as $key => $value2) {
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
            <label for="domain">
              <input type="checkbox" id="domain-bool" name="domain-bool" value="0"> NOT
            </label>
            <select id="domain" name="domain">
            <option value=''>--Filter By AD Domain--</option>
              <?php
              $dbPSQL->select("SELECT domain, domain_readable FROM static_domains ORDER BY domain ASC");
              if (arrFilter($dbPSQL->get()) === 0) {
                foreach ($dbPSQL->get() as $key => $value1) {
                  $dbPSQL->Pselect("SELECT COUNT(tagnumber) AS domain_rows FROM locations WHERE domain = :domain AND time IN (SELECT MAX(time) FROM locations GROUP BY tagnumber)", array(':domain' => $value1["domain"]));
                  if (arrFilter($dbPSQL->get()) === 0) {
                    foreach ($dbPSQL->get() as $key => $value2) {
                      echo "<option value='" . htmlspecialchars($value1["domain"]) . "'>" . htmlspecialchars($value1["domain_readable"]) . " (" . $value2["domain_rows"] . ")</option>" . PHP_EOL;
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
              <input type="checkbox" id="system_model-bool" name="system_model-bool" value="0"> NOT
            </label>
            <select id="system_model" name="system_model">
              <option value=''>--Filter By Model--</option>
              <?php
              $dbPSQL->select("SELECT t1.system_model, t1.system_manufacturer, (CASE WHEN t1.system_manufacturer IS NOT NULL THEN CONCAT('(', t1.system_manufacturer, ')') ELSE NULL END) AS system_manufacturer_formatted, 
                 t3.row_nums AS system_model_rows
                  FROM (select time, system_manufacturer, system_model, ROW_NUMBER() OVER (PARTITION BY system_model ORDER BY time DESC) AS row_nums from system_data) t1 
                  LEFT JOIN (SELECT system_model, MAX(row_nums) AS row_nums FROM (SELECT system_model, ROW_NUMBER() OVER (PARTITION BY system_model ORDER BY time ASC) AS row_nums FROM system_data) s3 GROUP BY system_model) t3
                    ON t1.system_model = t3.system_model
                  WHERE t1.row_nums = 1 AND t1.system_model IS NOT NULL AND t3.system_model IS NOT NULL
                  ORDER BY t1.system_manufacturer ASC, t1.system_model ASC");
              if (arrFilter($dbPSQL->get()) === 0) {
                foreach ($dbPSQL->get() as $key => $value1) {
                  echo "<option value='" . htmlspecialchars($value1["system_model"]) . "'>" . htmlspecialchars($value1["system_manufacturer_formatted"]) . " " . htmlspecialchars($value1["system_model"]) . " (" . $value1["system_model_rows"] . ")" . "</option>" . PHP_EOL;
                }
              }
              unset($value1);
              ?>
            </select>
          </div>

          <div>
            <label for="checkouts">
              <input type="checkbox" id="checkouts-bool" name="checkouts-bool" value="0"> NOT
            </label>
            <select id="customer_checkout_name" name="customer_checkout_name">
              <option value=''>--Filter By Customer Name (Checkouts)--</option>
              <?php
              $dbPSQL->select("SELECT customer_name FROM checkouts WHERE customer_name IS NOT NULL AND checkout_bool = TRUE GROUP BY customer_name ORDER BY customer_name ASC");
              if (arrFilter($dbPSQL->get()) === 0) {
                foreach ($dbPSQL->get() as $key => $value1) {
                  $dbPSQL->Pselect("SELECT COUNT(tagnumber) FROM (SELECT tagnumber, customer_name, checkout_bool, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS row_nums FROM checkouts) t1 WHERE t1.row_nums = 1 AND t1.customer_name = :customerName AND t1.checkout_bool = TRUE", array(':customerName' => $value1["customer_name"]));
                  foreach ($dbPSQL->get() as $key => $value2) {
                    echo "<option value='" . htmlspecialchars($value1["customer_name"]) . "'>" . htmlspecialchars(strtoupper($value1["customer_name"])) . " (" . $value2["count"] . ")" . "</option>" . PHP_EOL;
                  }
                }
              }
              unset($value1);
              unset($value2);
              ?>
            </select>
          </div>

          <div>
            <select id="order_by" name="order_by">
              <option value=''>--Order By--</option>
              <option value="time_desc">Time &#8595; (new to old)</option>
              <option value="time_asc">Time &#8593; (old to new)</option>
              <option value="tag_desc">Tagnumber &#8595; (large to small)</option>
              <option value="tag_asc">Tagnumber &#8593; (small to large)</option>
              <option value="location_asc">Location &#8593; (a-z)</option>
              <option value="location_desc">Location &#8595; (z-a)</option>
              <option value="model_asc">Model &#8593; (a-z)</option>
              <option value="model_desc">Model &#8595; (z-a)</option>
              <option value="os_desc">OS Installed &#8595; (installed on top)</option>
              <option value="os_asc">OS Installed &#8593; (installed on bottom)</option>
              <option value="bios_desc">BIOS Updated &#8595; (updated on top)</option>
              <option value="bios_asc">BIOS Updated &#8593; (updated on bottom)</option>
            </select>
          </div>
        </div>

        <div class="row">
          <div class="dense-column">
            <!-- <p>Device Lost?</p>
            <div class="column">
              <label for="lost_yes">Yes</label>
              <input type="radio" id="lost_yes" name="lost" value="TRUE">
            </div>
            <div class="column">
              <label for="lost_no">No</label>
              <input type="radio" id="lost_no" name="lost" value="FALSE">
            </div> -->
            <p>Checked Out?</p>
            <div class="column">
              <label for="checkout_yes">Yes</label>
              <input type="radio" id="checkout_yes" name="checkout" value="1">
            </div>
            <div class="column">
              <label for="checkout_no">No</label>
              <input type="radio" id="checkout_no" name="checkout" value="0">
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
            <button style='background-color:rgba(0, 179, 136, 0.30);' type="submit">Filter</button>
            <button type='button' onclick="window.location.href = '/locations.php';">Reset Filters</button>
        </div>

        <div class='filtering-form'>
            <?php
              if ($_GET["checkout"] == "0" || $_GET["checkout"] == "1") { echo "<div><h3>Click <a href='/checkouts.php' target='_blank'>here</a> for a checkout overview.</h3></div>"; }
            ?>
            <?php echo "<div><p>Results: <b>" . $rowCount . "</b></p></div>" . PHP_EOL; ?>
        </div>
      </form>
    </div>
            </div>
            </div>


<?php
if (count($_GET) > 1) {
    echo "<div class='page-content'><h3><u>" . htmlspecialchars($onlineRowCount) . "/" . htmlspecialchars($rowCount) . "</u> queried clients are online.</h3></div>";
} else if (count($_GET) === 1) {
  if (strFilter($_GET["location"]) === 0) {
    echo "<div class='page-content'><h3><u>" . htmlspecialchars($onlineRowCount) . "/" . htmlspecialchars($rowCount) . "</u> clients are online from location '" . htmlspecialchars($_GET["location"]) . "'.</h3></div>";
  }
}
?>

    <div class='styled-form2'>
      <input type="text" id="myInput" onkeyup="myFunction()" autocapitalize='none' autocomplete='off' autocorrect='off' spellcheck='false' placeholder="Search tag number...">
      <input type="text" id="myInputSerial" onkeyup="myFunctionSerial()" autocapitalize='none' autocomplete='off' autocorrect='off' spellcheck='false' placeholder="Search serial number...">
      <input type="text" id="myInputLocations" onkeyup="myFunctionLocations()" autocapitalize='none' autocomplete='off' autocorrect='off' spellcheck='false' placeholder="Search locations...">
    </div>
    <div class='location-form'>
      <?php
        echo "<button onclick=\"window.location.href = '" . addUrlVar($_SERVER["REQUEST_URI"], "refresh", "1") . "';\">Refresh Clients</button>"; 
      ?>
    </div>
    <div>
      <table id="myTable">
        <thead>
          <tr>
            <th>Tag Number</th>
            <th>System Serial</th>
            <th>System Model</th>
            <th>Location</th>
            <th>Department</th>
            <th>Functional</th>
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
  echo "<b><a href='" . addUrlVar(addUrlVar($_SERVER["REQUEST_URI"], "edit", "1"), "tagnumber", htmlspecialchars($value1["tagnumber"])) . "'>" . htmlspecialchars($value1["tagnumber"]) . "</a></b>";
  // kernel and bios up to date (check mark)
  if ($value1["present_bool"] === true && ($value1["kernel_updated"] === true && $value1["bios_updated"] === true)) {
    echo " <span style='color:rgb(0, 120, 50)'><b>&#10004;</b></span>" . PHP_EOL;
  // BIOS out of date, kernel not updated (x)
  } elseif ($value1["present_bool"] === true && ($value1["kernel_updated"] !== true && $value1["bios_updated"] !== true)) {
    echo " <span>&#10060;</span>" . PHP_EOL;
  //BIOS out of date, kernel updated (warning sign)
  } elseif ($value1["present_bool"] === true && ($value1["kernel_updated"] === true && $value1["bios_updated"] !== true)) {
    echo " <span>&#9888;&#65039;</span>" . PHP_EOL;
  //BIOS updated, kernel out of date (x)
  } elseif ($value1["present_bool"] === true && ($value1["kernel_updated"] !== true && $value1["bios_updated"] === true)) {
    echo " <span>&#10060;</span>" . PHP_EOL;
  } else {
    echo "" . PHP_EOL;
  }

  // CHANGE TYPE LATER //
  if ($value1["checkout_bool"] === 1) {
    echo "<img style='width: auto; height: 1.5em;' src='/images/checkout.svg'>";
  }
  echo "</td>" . PHP_EOL;

  // Serial Number
  echo "<td>" . htmlspecialchars($value1['system_serial']) . "</td>" . PHP_EOL;

  // System Model
  echo "<td><b><a href='" . addUrlVar($_SERVER["REQUEST_URI"], "system_model", htmlspecialchars($value1['system_model'])) . "'>" . htmlspecialchars($value1['system_model']) . "</a></b></td>" . PHP_EOL;

  // Location
  echo "<td><b><a href='" . addUrlVar($_SERVER["REQUEST_URI"], "location", htmlspecialchars($value1['location_formatted'])) . "'>" . htmlspecialchars($value1["location_formatted"]) . "</a></b></td>" . PHP_EOL;

  // Department
  echo "<td>" . htmlspecialchars($value1["department_formatted"]) . "</td>" . PHP_EOL;

  // Status (working/broken)
  echo "<td>" . htmlspecialchars($value1['status_formatted']) . "</td>" . PHP_EOL;

  // Os installed
	if ($value1["os_installed"] === true && strFilter($value1["domain"]) === 0) {
		echo "<td>" . htmlspecialchars($value1['os_installed_formatted']) . "<img style='width: auto; height: 1.5em;' src='/images/azure-ad-logo.png'>" . "</td>" . PHP_EOL;
	} else {
		echo "<td>" . htmlspecialchars($value1['os_installed_formatted']) . "</td>" . PHP_EOL;
	}

  //BIOS updated
  echo "<td>" . htmlspecialchars($value1["bios_updated_formatted"]) . "</td>" . PHP_EOL;

  // Note
  echo "<td>" . htmlspecialchars($value1['note']) . "</td>" . PHP_EOL;

  // Timestamp
  echo "<td>" . htmlspecialchars($value1['time_formatted']) . " </td>" . PHP_EOL;
}

echo "</tr>" . PHP_EOL;

unset($tableArr);
unset($value1);
?>

        </tbody>
      </table>
    </div>
    <script src="/js/include.js?<?php echo filemtime('js/include.js'); ?>"></script>
    <script>
      function getCursorPos(myElement) {
        let startPosition = myElement.selectionStart;
        let endPosition = myElement.selectionEnd;

        myElement.focus();

        // Check if you've selected text
        //if(startPosition == endPosition){
            //console.log("The position of the cursor is (" + startPosition + "/" + myElement.value.length + ")");
        //}else{
            //console.log("Selected text from ("+ startPosition +" to "+ endPosition + " of " + myElement.value.length + ")");
        //}
        return(startPosition);
      }


      // Autofill tag numbers
          var availableTagnumbers = [
          <?php
          $dbPSQL->select("SELECT tagnumber FROM locations GROUP BY tagnumber");
          if (arrFilter($dbPSQL->get()) === 0) {
            foreach ($dbPSQL->get() as $key => $value) {
              echo "'" . $value["tagnumber"] . "',";
            }
          }
          unset($value);
          ?>
        ];

        var tagnumberField = document.getElementById('tagnumber');

        tagnumberField.addEventListener('keyup', (event) => {
          const inputText = tagnumberField.value;

          if (event.key === 'Backspace' || event.key === 'Delete') {
            tagnumberField.value = tagnumberField.value.substr(0, getCursorPos(tagnumberField));
            //console.log("Backspace Value: " + tagnumberField.value);
            //console.log("Backspace Position: " + getCursorPos(tagnumberField) + ", " + getCursorPos(tagnumberField));
            tagnumberField.setSelectionRange(getCursorPos(tagnumberField), getCursorPos(tagnumberField));
          }
        });

        tagnumberField.addEventListener('input', function() {
          const inputText = tagnumberField.value;
          var re = new RegExp('^' + inputText, 'gi');
          var re1 = new RegExp('^' + inputText + '$', 'gi');
          const matchingExact = availableTagnumbers.find(suggestion => suggestion.match(re1));
          const matchingSuggestion = availableTagnumbers.find(suggestion => suggestion.match(re));

          if (matchingSuggestion && inputText.length > 0) {
            if (matchingExact) {
              tagnumberField.value = matchingExact;
            } else {
              tagnumberField.value = matchingSuggestion;
            }
            tagnumberField.setSelectionRange(inputText.length, matchingSuggestion.length);
          }
        });


        // Autofill locations
        var availableLocations = [
          <?php
            $sql =<<<'EOD'
              SELECT MAX(t1.time) AS time, t1.location, MAX(t1.row_nums) AS row_nums FROM (SELECT time, locationFormatting(REPLACE(REPLACE(REPLACE(location, '\', '\\'), '''', '\'''), '\"','\"')) AS location, ROW_NUMBER() OVER (PARTITION BY location ORDER BY time DESC) AS row_nums FROM locations WHERE time IN (SELECT MAX(time) FROM locations GROUP BY tagnumber)) t1 GROUP BY t1.location ORDER BY LENGTH(t1.location), row_nums DESC;
            EOD;
          $dbPSQL->select($sql);
          if (arrFilter($dbPSQL->get()) === 0) {
            foreach ($dbPSQL->get() as $key => $value) {
              echo "'" . $value["location"] . "',";
            }
          }
          unset($value);
          unset($sql);
          ?>
        ];

        var locationField = document.getElementById('location');

        locationField.addEventListener('keyup', (event) => {
          const inputText = locationField.value;

          if (event.key === 'Backspace' || event.key === 'Delete') {
            locationField.value = locationField.value.substr(0, getCursorPos(locationField));
            //console.log("Backspace Value: " + locationField.value);
            //console.log("Backspace Position: " + getCursorPos(locationField) + ", " + getCursorPos(locationField));
            locationField.setSelectionRange(getCursorPos(locationField), getCursorPos(locationField));
          }
        });

        locationField.addEventListener('input', function() {
          const inputText = locationField.value;
          var re = new RegExp('^' + inputText, 'gi');
          var re1 = new RegExp('^' + inputText + '$', 'gi');
          const matchingExact = availableLocations.find(suggestion => suggestion.match(re1));
          const matchingSuggestion = availableLocations.find(suggestion => suggestion.match(re));


          if (matchingSuggestion && inputText.length > 0) {
            if (matchingExact) {
              locationField.value = matchingExact;
            } else {
              locationField.value = matchingSuggestion;
            }
            locationField.setSelectionRange(inputText.length, matchingSuggestion.length);
          }
        });
        
    </script>
    <script>

      // Change disk removed colors in form
      var DBdiskRemoved = document.getElementById('disk_removed');
      //var DBdiskRemovedFalse = document.getElementById('disk_removed-true');
      //var DBdiskRemovedTrue = document.getElementById('disk_removed-false');

      if (DBdiskRemoved.value == "1") {
        //console.log("red");
        DBdiskRemoved.style.background = "rgba(200, 16, 47, 0.31)";
        //DBdiskRemovedFalse.style.background = "rgba(0, 179, 137, 0.31)";
        //DBdiskRemovedTrue.style.background = "rgba(200, 16, 47, 0.31)";
      }

      function changeDiskRemovedColors() {
        if (DBdiskRemoved.value == "1") {
          //console.log("red");
          DBdiskRemoved.style.background = "rgba(200, 16, 47, 0.31)";
          //DBdiskRemovedTrue.style.background = "rgba(200, 16, 47, 0.31)";
          //DBdiskRemovedFalse.style.background = "rgba(0, 179, 137, 0.31)";
        } else {
          //console.log("green");
          DBdiskRemoved.style.background = "rgba(0, 179, 137, 0.31)";
          //DBdiskRemovedFalse.style.background = "rgba(0, 179, 137, 0.31)";
          //DBdiskRemovedTrue.style.background = "rgba(200, 16, 47, 0.31)";
        }
      }

      DBdiskRemoved.addEventListener("change", function () {changeDiskRemovedColors();},false);

      // Change status colors in form
      var DBstatus = document.getElementById('status');
      //var DBstatusWorking = document.getElementById('status-working');
      //var DBstatusBroken = document.getElementById('status-broken');

      if (DBstatus.value == "1") {
        //console.log("red");
        DBstatus.style.background = "rgba(200, 16, 47, 0.31)";
        //DBstatusWorking.style.background = "rgba(0, 179, 137, 0.31)";
        //DBstatusBroken.style.background = "rgba(200, 16, 47, 0.31)";
      }

      function changeStatusColors() {
        if (DBstatus.value == "1") {
          //console.log("red");
          DBstatus.style.background = "rgba(200, 16, 47, 0.31)";
          //DBstatusWorking.style.background = "rgba(0, 179, 137, 0.31)";
          //DBstatusBroken.style.background = "rgba(200, 16, 47, 0.31)";
        } else {
          //console.log("green");
          DBstatus.style.background = "rgba(0, 179, 137, 0.31)";
          //DBstatusWorking.style.background = "rgba(0, 179, 137, 0.31)";
          //DBstatusBroken.style.background = "rgba(200, 16, 47, 0.31)";
        }
      }

      DBstatus.addEventListener("change", function () {changeStatusColors();},false);

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
          td = tr[i].getElementsByTagName("td")[3];
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