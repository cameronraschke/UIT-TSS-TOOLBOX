<?php
require('/var/www/html/uit-web/header.php');
require('/var/www/html/uit-web/php/include.php');

if ($_SESSION['authorized'] != "yes") {
  die();
}

$dbPSQL = new dbPSQL();

if (isset($_POST["note"]) && isset($_GET["note-type"])) {
  $dbPSQL->insertToDo($time);
  $dbPSQL->updateToDo($_GET["note-type"], $_POST["note"], $time);
  unset($_POST);
  unset($value);
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
  <meta charset='UTF-8'>
  <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
  <link rel="stylesheet" type="text/css" href="css/main.css?<?php echo filemtime('css/main.css'); ?>" />
  <script src="/js/init.js?<?php echo filemtime('js/init.js'); ?>"></script>
  <title>Home - UIT Client Management</title>
</head>
<body onload="fetchHTML();charCount();">
  <?php include('/var/www/html/uit-web/php/navigation-bar.php'); ?>
  <div class='index-grid-container'>

    <div class='index-grid-box'>
      <div><h2>Welcome, <?php echo $login_user; ?>.</h2></div>
      <div><h2 id='server_time'></h2></div>
      <div>
        <div>
          <form method='get'>
            <select name='note-type' id='note-type' onchange='this.form.submit()'>
              <?php
              if (strFilter($_GET["note-type"]) === 0) {
              $dbPSQL->Pselect("SELECT note, note_readable FROM static_notes WHERE note = :curNote ORDER BY sort_order ASC", array(':curNote' => $_GET["note-type"]));
              foreach ($dbPSQL->get() as $key => $value1) {
              if (strFilter($value1["note"]) === 0) {
              $sql = "SELECT time, TO_CHAR(time, 'MM/DD/YY HH12:MI:SS AM') AS timeFormatted, " . $value1["note"] . " AS note FROM notes WHERE " . $value1["note"] . " IS NOT NULL ORDER BY time DESC LIMIT 1";
              } else {
              $sql = "SELECT NULL AS time, NULL AS timeFormatted, NULL AS note"; 
              }

              echo "<option value='" . htmlspecialchars($value1["note"]) . "'>Editing: " . htmlspecialchars($value1["note_readable"]) . "</option>";
              $dbPSQL->Pselect("SELECT note, note_readable FROM static_notes WHERE NOT note = :curNote ORDER BY sort_order ASC", array(':curNote' => $_GET["note-type"]));
              foreach ($dbPSQL->get() as $key => $value2) {
              echo "<option value='" . htmlspecialchars($value2["note"]) . "'>" . htmlspecialchars($value2["note_readable"]) . "</option>";
              }
              unset($value2);
              }
              unset($value1);
              $dbPSQL->select($sql);
              foreach ($dbPSQL->get() as $key => $value1) {
              $note = $value1["note"];
              $noteTime = $value1["timeFormatted"];
              }
              unset($sql);
              unset($value1);
              } else {
              echo "<option value=''>--Select Notes to Edit--</option>";
              $dbPSQL->select("SELECT note, note_readable FROM static_notes ORDER BY sort_order ASC");
              foreach ($dbPSQL->get() as $key => $value1) {
              echo "<option value='" . htmlspecialchars($value1["note"]) . "'>" . htmlspecialchars($value1["note_readable"]) . "</option>";
              }
              }
              ?>
            </select>
          </form>
        </div>

        <div>
          <form method="post">
            <div>
              <label for='note'>To-Do List (Last Updated: <?php echo htmlspecialchars($noteTime); ?>)</label>
              <p id='charLen' name='charLen'>Charater count: 0</p>
              <div class="tooltip">?
                <span class="tooltiptext">
                  <?php echo htmlspecialchars("Press ** before and after a word to create a heading. Ex) **Bugs** "); ?> <br><br>
                  <?php echo htmlspecialchars("Press * to create a bulleted item."); ?> <br><br>
                  <?php echo htmlspecialchars("Keep pressing '>' to indent up to four times."); ?> <br><br>
                  <p>Enter <a href='/documentation/pages.php?page=supported-emojis' target='_blank'>supported emoticons</a>, key words preceeded by a colon, or emojis from your keyboard to get an emoji: </p> <br><br>
                  <?php echo htmlspecialchars(":check :x :cancel :waiting :pin :warning :alert"); ?> <br>
                  <?php echo htmlspecialchars(":like :dislike :star :info :heart :fire :shrug :clap :celebrate :hmm :mindblown "); ?> <br>
                  <?php echo htmlspecialchars(":clock :bug :arrow :poop"); ?><br>
                  --------
                  <br>
                  <?php echo htmlspecialchars(" :) :P :( :| ;( :< :O"); ?>
                </span>
              </div>
            </div>
            <div name="unsaved-changes" id="unsaved-changes" style="color: #C8102E;"></div>
            <div><textarea id='note' name='note' onkeyup='replaceAsterisk();replaceEmoji();replaceHeaders();' onchange onpropertychange onkeyuponpaste oninput="input_changed();replaceEmoji();" autocorrect="false" spellcheck="false" contenteditable="true"><?php echo htmlspecialchars($note); ?></textarea></div>
            <div name='edit-button' id='edit-button'></div>
            <div name='cancel-button' id='cancel-button'></div>
            </div>
          </form>
        </div>
      </div>

    <div class='index-grid-box'>
      <div id="runningJobs">
        <?php
          $dbPSQL->select("SELECT (CASE WHEN COUNT(tagnumber) >= 1 THEN CAST(COUNT(tagnumber) AS VARCHAR(3)) ELSE 'None' END) AS count FROM remote WHERE job_queued IS NOT NULL AND NOT status = 'Waiting for job' AND present_bool = TRUE");
        ?>
        <h3><b>Queued Jobs: </b><?php echo htmlspecialchars($dbPSQL->nested_get()["count"]); ?></h3>
      </div>
      <div id="jobTimes"></div>
      <div id="numberImaged"></div>
      <div id="numberJoinedDomain"></div>
      <div id="biosUpdated"></div>
    </div>
  </div>

  <div class="uit-footer">
    <img src="/images/uh-footer.svg">
  </div>


  <script src="/js/include.js?<?php echo filemtime('js/include.js'); ?>"></script>
  
<script>
  // Get the current URL
  const url = window.location.href;

  // Create a URLSearchParams object
  let params = new URLSearchParams(document.location.search);
  //let note = params.get("note-type");

  // Check if the "get" parameter exists
  if (params.has('note-type') && document.getElementById('note')) {
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
          if (params.has('note-type') && document.getElementById('note')) {
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
        }
        function setCursorPos(pos) {
          if (params.has('note-type') && document.getElementById('note')) {
            myElement.focus();
            myElement.setSelectionRange(pos, pos);
            
            //console.log("Changing position of the cursor to (" + pos + "/" + myElement.value.length + ")");
            return(pos);
          }
        }
        function getCursorPos() {
          if (params.has('note-type') && document.getElementById('note')) {
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
        }

        function replaceHeaders() {
          if (params.has('note-type') && document.getElementById('note')) {
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
        }

        function replaceEmoji() {
          if (params.has('note-type') && document.getElementById('note')) {
            let str = myElement.value;
            let origPos = getCursorPos();
            let newStr = str;
            let newPos = 0;
            let offset = 0;
            // Replace smiley face with emoji

            <?php 
            unset($jsConst);
            //newStr = newStr.replaceAll(/\:\\ /g, "😕 ");
            $dbPSQL->select("SELECT keyword, regex, replacement, text_bool, case_sensitive_bool FROM static_emojis");
            foreach ($dbPSQL->get() as $key => $value) {
              if ($value["case_sensitive_bool"] === true) {
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
        }

        function replaceAsterisk() {
          if (params.has('note-type') && document.getElementById('note')) {
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

  <script>

async function parseSSE() { 
  const response = await fetchSSE("server_time", <?php echo htmlspecialchars($_GET["tagnumber"]); ?>);
    newHTML = '';
    Object.entries(response).forEach(([key, value]) => {
      newHTML = "Server time: " + response["server_time"];
      document.getElementById('server_time').innerHTML = newHTML;
    });
  };

parseSSE();
setInterval(parseSSE, 3000);
  </script>

    </body>
</html>