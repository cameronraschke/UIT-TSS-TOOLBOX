<?php
require('/var/www/html/management/header.php');
require('/var/www/html/management/php/include.php');

if ($_SESSION['authorized'] != "yes") {
  die();
}

$db = new db();

if (isset($_POST["note"]) && isset($_GET["note-type"])) {
  $db->insertToDo($time);
  $db->Pselect("SELECT COMPRESS(:note) AS 'note_compressed'", array(':note' => $_POST["note"]));
  foreach ($db->get() as $key => $value) {
    $db->updateToDo($_GET["note-type"], $value["note_compressed"], $time);
  }
  unset($_POST);
  unset($value);
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
  <meta charset='UTF-8'>
  <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
  <link rel='stylesheet' type='text/css' href='/css/main.css' />
  <title>Home - UIT Client Management</title>
</head>
<body onload="fetchHTML();charCount();">
  <div class='menubar'>
    <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
    <p><span style='float: right;'>Logged in as <b><?php echo htmlspecialchars($login_user); ?></b>.</span></p>
    <br>
    <p><span style='float: right;'>Not <b><?php echo htmlspecialchars($login_user); ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
  </div>

  <div class='pagetitle' style="text-align:center;"><h1 style="margin:auto;">TechComm Laptop Management Site</h1></div>
  <div class='pagetitle'><h2>Welcome, <?php echo $login_user; ?>.</h2></div>

  <div class='row'>
    <div class='column'>
      <div><h3 class='page-content'><a href="/job-queue.php">Remote Management and Live Overview</a></h3></div>
      <div><h3 class='page-content'><a href="/locations.php">Update and View Client Locations</a></h3></div>
      <div><h3 class='page-content'><a href="/checkouts.php">View Checkout History (WIP)</a></h3></div>
      <div><h3 class='page-content'><a href="/serverstats.php">Daily Reports</a></h3></div>


  <div class='location-form' style='height: auto;'>
    <form method='get'>
      <select name='note-type' id='note-type' onchange='this.form.submit()'>
        <?php
          if (strFilter($_GET["note-type"]) === 0) {
            $db->Pselect("SELECT note, note_readable FROM static_notes WHERE note = :curNote ORDER BY sort_order ASC", array(':curNote' => $_GET["note-type"]));
            foreach ($db->get() as $key => $value1) {
              if (strFilter($value1["note"]) === 0) {
                $sql = "SELECT time, DATE_FORMAT(time, '%m/%d/%y, %r') AS 'timeFormatted', CAST(IF (UNCOMPRESSED_LENGTH(" . $value1["note"] . ") < 67108864 AND UNCOMPRESS(" . $value1["note"] . ") IS NOT NULL, UNCOMPRESS(" . $value1["note"] . "), " . $value1["note"] . ") AS CHAR) AS 'note' FROM notes WHERE " . $value1["note"] . " IS NOT NULL ORDER BY time DESC LIMIT 1";
              } else {
                $sql = "SELECT NULL AS 'time', NULL AS 'timeFormatted', NULL AS 'note'"; 
              }
                echo "<option value='" . htmlspecialchars($value1["note"]) . "'>Editing: " . htmlspecialchars($value1["note_readable"]) . "</option>";
              $db->Pselect("SELECT note, note_readable FROM static_notes WHERE NOT note = :curNote ORDER BY sort_order ASC", array(':curNote' => $_GET["note-type"]));
              foreach ($db->get() as $key => $value2) {
                echo "<option value='" . htmlspecialchars($value2["note"]) . "'>" . htmlspecialchars($value2["note_readable"]) . "</option>";
              }
              unset($value2);
            }
            unset($value1);
            $db->select($sql);
            foreach ($db->get() as $key => $value1) {
              $note = $value1["note"];
              $noteTime = $value1["timeFormatted"];
            }
            unset($sql);
            unset($value1);
          } else {
            echo "<option value=''>--Select Notes to Edit--</option>";
            $db->select("SELECT note, note_readable FROM static_notes ORDER BY sort_order ASC");
            foreach ($db->get() as $key => $value1) {
                echo "<option value='" . htmlspecialchars($value1["note"]) . "'>" . htmlspecialchars($value1["note_readable"]) . "</option>";
            }
          }
        ?>
      </select>
    </form>

        <div class='location-form' style='height: 100%;'>
          <form method="post">

            <label for='note'>To-Do List (Last Updated: <?php echo htmlspecialchars($noteTime); ?>)</label>
            <p id='charLen' name='charLen'>Charater count: 0</p>
            <div class="tooltip">?
              <span class="tooltiptext">
              <?php echo htmlspecialchars("Press ** before and after a word to create a heading. Ex) **Bugs** "); ?> <br><br>
              <?php echo htmlspecialchars("Press * to create a bulleted item."); ?> <br><br>
              <?php echo htmlspecialchars("Keep pressing '>' to indent up to four times."); ?> <br><br>
              <p>Enter <a href='/documentation/emojis.php' target='_blank'>supported emoticons</a>, key words preceeded by a colon, or emojis from your keyboard to get an emoji: </p> <br><br>
              <?php echo htmlspecialchars(":check :x :cancel :waiting :pin :warning :alert"); ?> <br>
              <?php echo htmlspecialchars(":like :dislike :star :info :heart :fire :shrug :clap :celebrate :hmm :mindblown "); ?> <br>
              <?php echo htmlspecialchars(":clock :bug :arrow :poop"); ?><br>
              --------
              <br>
              <?php echo htmlspecialchars(" :) :P :( :| ;( :< :O"); ?>
              </span>
            </div>
            <div name="unsaved-changes" id="unsaved-changes" style="color: #C8102E;"></div>
            <div><textarea id='note' name='note' onkeyup='replaceAsterisk();replaceEmoji();replaceHeaders();' onchange onpropertychange onkeyuponpaste oninput="input_changed();replaceEmoji();" autocorrect="false" spellcheck="false" style='width: 100%; height: 30em; white-space: pre-wrap; overflow: auto;' contenteditable="true"><?php echo htmlspecialchars($note); ?></textarea></div>
              <div style='overflow:hidden'>
                  <div name='edit-button' id='edit-button'></div>
                  <div name='cancel-button' id='cancel-button'></div>
              </div>
          </form>
        </div>
      </div>

      <div id="jobTimes" style='height: auto; width: 99%; margin: 2% 1% 2% 1%;'></div>
    </div>


    <div class='column'>
      <div id="runningJobs" style='height: 10%; width: 99%; padding: 2% 1% 5% 1%; margin: 2% 1% 5% 1%;'>
        <?php
        $db->select("SELECT COUNT(tagnumber) AS 'count' FROM remote WHERE job_queued IS NOT NULL AND NOT status = 'Waiting for job' AND present_bool = 1");
        if (arrFilter($db->get()) === 0) {
          foreach ($db->get() as $key => $value) {
            echo "<h3><b>Queued Jobs:</b> " . htmlspecialchars($value["count"]) . "</h3>";
          }
        } else {
          echo "<h3><b>Queued Jobs: </b>None</h3>";
        }
        ?>
        </div>
      <div id="numberImaged" style='height: auto; width: 99%; margin: 2% 1% 2% 1%;'></div>
      <div id="numberJoinedDomain" style='height: auto; width: 99%; margin: 2% 1% 2% 1%;'></div>
      <div id="biosUpdated" style='height: auto; width: 99%; margin: 2% 1% 2% 1%;'></div>
    </div>
  </div>

<script>
  // Get the current URL
  const url = window.location.href;

  // Create a URLSearchParams object
  let params = new URLSearchParams(document.location.search);
  //let note = params.get("note-type");

  // Check if the "get" parameter exists
  if (params.has('note-type')) {
    var myElement = document.getElementById('note');
    document.getElementById("note").readOnly = false;
    document.getElementById('edit-button').innerHTML = "<button style='background-color:rgba(0, 179, 136, 0.30);' type='submit'>Update Checklist</button>";
    document.getElementById('edit-button').style.float = "left";
    document.getElementById('cancel-button').innerHTML = "<button type='button' onclick='jsRedirect();'>Cancel</button>";
  } else {
    //console.log("nothing in the URL.");
    document.getElementById("note").readOnly = true;
    document.getElementById('note').style.height = "10%";
    document.getElementById("note").value = "Please edit the note by selecting a type of note in the dropdown menu above.";
    document.getElementById("note").style.backgroundColor = "rgb(187, 185, 185) ";
    document.getElementById("note").style.color = "rgb(0, 0, 0)";

    //document.getElementById('edit-button').innerHTML = "<a href='/index.php'>Cancel Edit</a>";
  }

  function jsRedirect() {
    window.location.href = "/index.php";
  }

        function input_changed() {
            document.getElementById("note").style.border = "medium dashed #C8102E";
            document.getElementById('unsaved-changes').innerHTML = "Unsaved Changes... ";
        }
        function charCount() {
            myElement.focus();
            var len = myElement.value.length;

            myElement.addEventListener("keyup", function(){
                var len = myElement.value.length;
                document.getElementById('charLen').innerHTML = "Character count: " + len;
            },false);

            //console.log(len + " characters");
            document.getElementById('charLen').innerHTML = "Character count: " + len;

            return(len);
        }
        function setCursorPos(pos) {
            myElement.focus();
            myElement.setSelectionRange(pos, pos);
            
            //console.log("Changing position of the cursor to (" + pos + "/" + myElement.value.length + ")");
            return(pos);
        }
        function getCursorPos() {
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

        function replaceHeaders() {
            let str = myElement.value;
            let origPos = getCursorPos();
            let newStr = str;
            let pos = 0;
            let offset = 0;
            // Replace
            newStr = newStr.replaceAll(/^\*\*(.+)\*\* /gi, "$1\n---------- \n");
            newStr = newStr.replaceAll(/\n\*\*(.+)\*\* /gi, "\n\n$1\n---------- \n");

            if (str != newStr) {
                let newPos = getCursorPos();
                const regex = /\n\-\-.*\-\-/;
                const match = str.match(regex);

                offset = origPos;

                if (match) {
                    const substring = match[0];
                    const substringLength = substring.length;
                    //console.log("SUBSTRING Len: " + substring.length + 9)
                    offset = origPos - substringLength + substring.length + 9;
                }

                //console.log("Offset: " + offset);
                myElement.value = newStr;
                setCursorPos(offset);
            }
            return(offset);
        }

        function replaceEmoji() {
            let str = myElement.value;
            let origPos = getCursorPos();
            let newStr = str;
            let newPos = 0;
            let offset = 0;
            // Replace smiley face with emoji

            <?php 
            unset($jsConst);
            //newStr = newStr.replaceAll(/\:\\ /g, "😕 ");
            $db->select("SELECT keyword, regex, replacement, text_bool, case_sensitive_bool FROM static_emojis");
            foreach ($db->get() as $key => $value) {
              if ($value["case_sensitive_bool"] === 1) {
                echo "newStr = newStr.replaceAll(/" .  $value["regex"] . " /gi, '" . $value["replacement"] . " ');" . PHP_EOL;
              } else {
                echo "newStr = newStr.replaceAll(/" .  $value["regex"] . " /g, '" . $value["replacement"] . " ');" . PHP_EOL;
              }

              //if ($value["text_bool"] === 1) {
                $jsConst .= "|(" . $value["regex"] . ")";
              //}
            }
            unset($value);
            $jsConst = $str = ltrim($jsConst, '|');

            ?>
          
            if (str != newStr) {
                const regex = /<?php echo $jsConst; ?>/g;
                const match = str.match(regex);

                if (match) {
                    let newPos = getCursorPos();
                    myElement.value = newStr;
                    const substring = match[0];
                    const substringLength = substring.length;
                    //offset = origPos - substringLength + 1;
                    offset = newPos - substringLength + 1;
                    //console.log("Offset: " + offset);
                    setCursorPos(offset);
                }
            }
            return(offset);
        }

        function replaceAsterisk() {
            let str = myElement.value;
            let newStr = str;
            let pos = 0;
            
            // Replace first bullet point
            newStr = newStr.replaceAll(/^\* /g, "● ");
            // Replace subsequent bullet points on enter or space
            newStr = newStr.replaceAll(/\n\* /g, "\n● ");
            //newStr = newStr.replaceAll(/\n\*\n/g, "\n\n● ");
            // Replace indents on either enter or space
            newStr = newStr.replaceAll(/\n\> /g, "\n\t> ");
            newStr = newStr.replaceAll(/\n\>\>/g, "\n\t> ");
            // Double indent
            newStr = newStr.replaceAll(/\n\t\> \>/g, "\n\t\t> ");
            newStr = newStr.replaceAll(/\n\t\>\>/g, "\n\t\t> ");
            // Triple indent
            newStr = newStr.replaceAll(/\n\t\t\> \>/g, "\n\t\t\t> ");
            // Quadruple indent
            newStr = newStr.replaceAll(/\n\t\t\t\> \>/g, "\n\t\t\t\t> ");


            if (str != newStr) {
                let pos = getCursorPos();
                //console.log("New string: " + newStr);
                myElement.value = newStr;
                //console.log("Position + Offset: " + pos + offset)
                setCursorPos(pos);
                let endOfLine = newStr.indexOf('\n', pos);
                if (endOfLine === -1) {
                    endOfLine = newStr.length;
                }
                setCursorPos(endOfLine);
            }
        }

        </script>
        <script src="/google-charts/loader.js"></script>

        <script>
            google.charts.load('current',{packages:['corechart']});
            google.charts.setOnLoadCallback(jobTimes);

            function jobTimes() {
                var data = google.visualization.arrayToDataTable([ ['Date', 'Clone Time', 'Erase Time'],
                <?php
                $db->select("SELECT t1.dateByMonth, t1.avg_clone_time, t2.avg_erase_time FROM 
                (SELECT date, DATE_FORMAT(date, '%Y-%m') AS 'dateByMonth', avg_clone_time, 
                    ROW_NUMBER() OVER (PARTITION BY DATE_FORMAT(date, '%Y-%m') ORDER BY date DESC) AS 'avg_clone_time_rows'
                FROM serverstats ORDER BY dateByMonth ASC) t1 
                INNER JOIN 
                (SELECT date, DATE_FORMAT(date, '%Y-%m') AS 'dateByMonth', avg_erase_time, 
                    ROW_NUMBER() OVER (PARTITION BY DATE_FORMAT(date, '%Y-%m') ORDER BY date DESC) AS 'avg_erase_time_rows'
                FROM serverstats ORDER BY dateByMonth ASC) t2 
                ON t1.dateByMonth = t2.dateByMonth 
                WHERE t1.avg_clone_time_rows = 1 AND t2.avg_erase_time_rows = 1 AND t1.date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)");
                if (arrFilter($db->get()) === 0)
                    foreach ($db->get() as $key => $value) {
                        echo "['" . htmlspecialchars($value["dateByMonth"]) . "', " . htmlspecialchars($value["avg_clone_time"]) . ", " . htmlspecialchars($value["avg_erase_time"]) . "], ";
                    }
                ?>
                ]);

                var options = {title: 'Avg. Clone and Erase Time', hAxis: {title: 'Date'}, vAxis: {title: 'Time (minutes)'}, legend: 'none', colors: ['#f1ca3a', '#6f9654']  };
                var chart = new google.visualization.LineChart(document.getElementById('jobTimes'));
                chart.draw(data, options);
            }
        </script>
        <script>
            google.charts.load('current',{packages:['corechart']});
            google.charts.setOnLoadCallback(numberImaged);

            function numberImaged() {
                var data = google.visualization.arrayToDataTable([ ['OS Status', 'Client Count'],
                <?php
                $db->select("SELECT 
                    (SELECT COUNT(client_health.tagnumber) FROM client_health INNER JOIN (SELECT locations.tagnumber, locations.department FROM locations LEFT JOIN static_departments ON static_departments.department = locations.department WHERE time IN (SELECT MAX(time) FROM locations GROUP BY tagnumber) AND static_departments.department_bool = 1 AND locations.department = 'techComm') t1 ON client_health.tagnumber = t1.tagnumber WHERE client_health.os_installed = 1)
                        AS 'os_installed',
                    (SELECT COUNT(client_health.tagnumber) FROM client_health INNER JOIN (SELECT locations.tagnumber, locations.department FROM locations LEFT JOIN static_departments ON static_departments.department = locations.department WHERE time IN (SELECT MAX(time) FROM locations GROUP BY tagnumber) AND static_departments.department_bool = 1 AND locations.department = 'techComm') t1 ON client_health.tagnumber = t1.tagnumber WHERE client_health.os_installed IS NULL)
                        AS 'os_not_installed'");
                    if (arrFilter($db->get()) === 0) {
                        foreach ($db->get() as $key => $value) {
                            echo "['OS Installed'," . htmlspecialchars($value["os_installed"]) . "], ";
                            echo "['OS NOT Installed'," . htmlspecialchars($value["os_not_installed"]) . "], ";
                        }
                    }
                ?>
                ]);

                var options = {title: 'Percent of OS\'s Installed', colors: ['#e7711b', '#6f9654'] };
                var chart = new google.visualization.PieChart(document.getElementById('numberImaged'));
                chart.draw(data, options);
            }
        </script>
        
        <script>
            google.charts.load('current',{packages:['corechart']});
            google.charts.setOnLoadCallback(numberJoinedDomain);

            function numberJoinedDomain() {
                var data = google.visualization.arrayToDataTable([ ['Joined to AD', 'Not Joined to AD'],
                <?php
                $db->select("SELECT 
                    (SELECT COUNT(locations.tagnumber) FROM locations INNER JOIN (SELECT MAX(time) AS 'time' 
                    FROM locations GROUP BY tagnumber) t1 ON locations.time = t1.time
                        INNER JOIN (SELECT MAX(time) AS 'time' FROM locations GROUP BY tagnumber) t2 ON locations.time = t2.time
                        WHERE locations.department = 'techComm' AND locations.status IS NULL AND locations.domain IS NOT NULL)
                        AS 'domain_joined',
                    (SELECT COUNT(locations.tagnumber) FROM locations INNER JOIN (SELECT MAX(time) AS 'time' FROM locations GROUP BY tagnumber) t1 ON locations.time = t1.time
                        INNER JOIN (SELECT MAX(time) AS 'time' FROM locations GROUP BY tagnumber) t2 ON locations.time = t2.time
                        WHERE locations.department = 'techComm' AND locations.status IS NULL AND locations.domain IS NULL)
                        AS 'domain_not_joined'");
                    if (arrFilter($db->get()) === 0) {
                        foreach ($db->get() as $key => $value) {
                            echo "['Joined to AD Domain'," . htmlspecialchars($value["domain_joined"]) . "], ";
                            echo "['Not Joined to AD Domain'," . htmlspecialchars($value["domain_not_joined"]) . "], ";
                        }
                    }
                ?>
                ]);

                var options = {title: 'Computers Joined to AD Domain', colors: ['#1c91c0', '#43459d'] };
                var chart = new google.visualization.PieChart(document.getElementById('numberJoinedDomain'));
                chart.draw(data, options);
            }
        </script>
        

        <script>
            google.charts.load('current',{packages:['corechart']});
            google.charts.setOnLoadCallback(biosUpdated);

            function biosUpdated() {
                var data = google.visualization.arrayToDataTable([ ['OS Status', 'Client Count'],
                <?php
                $db->select("SELECT 
                    (SELECT COUNT(client_health.bios_updated) 
                    FROM client_health 
                    INNER JOIN (SELECT locations.tagnumber, locations.department FROM locations LEFT JOIN static_departments ON static_departments.department = locations.department WHERE time IN (SELECT MAX(time) FROM locations GROUP BY tagnumber) AND static_departments.department_bool = 1 AND locations.department = 'techComm') t1 ON client_health.tagnumber = t1.tagnumber
                        WHERE client_health.bios_updated = 1) AS 'bios_updated', 
                    (SELECT SUM(IF(client_health.bios_updated IS NULL, 1, 0)) 
                    FROM client_health 
                    INNER JOIN (SELECT locations.tagnumber, locations.department FROM locations LEFT JOIN static_departments ON static_departments.department = locations.department WHERE time IN (SELECT MAX(time) FROM locations GROUP BY tagnumber) AND static_departments.department_bool = 1 AND locations.department = 'techComm') t1 ON client_health.tagnumber = t1.tagnumber
                        WHERE bios_updated IS NULL) AS 'bios_not_updated'");
                    if (arrFilter($db->get()) === 0) {
                    foreach ($db->get() as $key => $value) {
                        echo "['BIOS Updated'," . htmlspecialchars($value["bios_updated"]) . "], ";
                        echo "['BIOS out of Date'," . htmlspecialchars($value["bios_not_updated"]) . "], ";
                    }
                }
                ?>
                ]);

                var options = {title: 'BIOS Status', colors: ['#6f9654', '#1c91c0'] };
                var chart = new google.visualization.PieChart(document.getElementById('biosUpdated'));
                chart.draw(data, options);
            }
        </script>
        <script>
            function fetchHTML() {
                setTimeout(function() {
                fetch('/index.php')
                .then((response) => {
                        return response.text();
                })
                .then((html) => {
                    //document.body.innerHTML = html
                    const parser = new DOMParser()
                    const doc = parser.parseFromString(html, "text/html")
                    //Update running jobs
                    const runningJobs = doc.getElementById('runningJobs').innerHTML
                    document.getElementById("runningJobs").innerHTML = runningJobs
                });
                fetchHTML();
            }, 3000)}
        </script>
        <script>
            if ( window.history.replaceState ) {
                window.history.replaceState( null, null, window.location.href );
            }
    </script>
    </body>
</html>