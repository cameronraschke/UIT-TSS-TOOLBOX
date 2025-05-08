<?php
require('/var/www/html/management/header.php');
include('/var/www/html/management/php/include.php');

$db = new db();

if (isset($_POST["todo"])) {
    $db->insertToDo($time);
    $db->updateToDo("note", $_POST["todo"], $time);
    unset($_POST);
}
?>
<!DOCTYPE html>
<html>
<meta charset="UTF-8">
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <link rel="stylesheet" href="/jquery/jquery-ui/jquery-ui-1.14.0/jquery-ui.min.css">
        <script src="/jquery/jquery-3.7.1.min.js"></script>
        <script src="/jquery/jquery-ui/jquery-ui-1.14.0/jquery-ui.min.js"></script>
        <title>Home - UIT Client Management</title>
        <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
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

        <div>
            <div style='width: 50%; height: 50%; float: left;'>
                <div><h3 class='page-content'><a href="/remote.php">Remote Management and Live Overview</a></h3></div>
                <div><h3 class='page-content'><a href="/locations.php">Update and View Client Locations</a></h3></div>
                <div><h3 class='page-content'><a href="/serverstats.php">Daily Reports</a></h3></div>
                <!--<div><h3 class='page-content'><a href="/clientstats.php">Client Report</a></h3></div> -->
                <div class='location-form' style='height: 100%;'>
                    <form method="post">
                        <?php
                            //$db->select("SELECT IF (time IS NOT NULL, DATE_FORMAT(time, '%m/%d/%y, %r'), 'N/A') AS 'time', IF (note IS NOT NULL, REGEXP_REPLACE(note, '^[*]', '&#9679;'), 'Enter List...') AS 'note' FROM todo");
                            $db->select("SELECT IF (time IS NOT NULL, DATE_FORMAT(time, '%m/%d/%y, %r'), 'N/A') AS 'time', IF (note IS NOT NULL, note, 'Enter List...') AS 'note' FROM todo");
                            foreach ($db->get() as $key => $value1) {
                                $todo = $value1["note"];
                                $todoTime = $value1["time"];
                            }
                            unset($value1);
                        ?>
                        <div>
                            <label for='todo'>To-Do List (Last Updated: <?php echo htmlspecialchars($todoTime); ?>)</label>
                            <p id='charLen' name='charLen'>Charater count: 0</p>
                            <div class="tooltip">?
                                <span class="tooltiptext">
                                <?php echo htmlspecialchars("Press ** before and after a word to create a heading. Ex) **Bugs** "); ?> <br><br>
                                <?php echo htmlspecialchars("Press * to create a bulleted item."); ?> <br><br>
                                <?php echo htmlspecialchars("Keep pressing '>' to indent up to four times."); ?> <br><br>
                                <?php echo htmlspecialchars("Enter emoticons OR key words preceeded by a colon to get an emoji:  "); ?> <br><br>
                                <?php echo htmlspecialchars(":check :x :cancel :waiting :warning :done"); ?> <br>
                                <?php echo htmlspecialchars(":like :dislike :star :pin :info :heart :fire :shrug :clap :celebrate :hmm"); ?> <br>
                                <?php echo htmlspecialchars(":clock :bug :arrow :poop"); ?><br>
                                --------
                                <br>
                                <?php echo htmlspecialchars(" :) :P :( :| ;( "); ?>
                            </span>
                            </div>
                        </div>
                        <div name="unsaved-changes" id="unsaved-changes" style="color: #C8102E;"></div>
                        <div><textarea id='todo' name='todo' onkeyup='replaceAsterisk();replaceEmoji();replaceHeaders();' onchange onpropertychange onkeyuponpaste oninput="input_changed()" autocorrect="false" spellcheck="false" style='width: 100%; height: 30em; white-space: pre-wrap; overflow: auto;' contenteditable="true"><?php echo htmlspecialchars($todo); ?> </textarea></div>
                        <div><button style='background-color:rgba(0, 179, 136, 0.30);' type="submit">Update To-Do List</button></div>
                    </form>
                </div>
                <div id="jobTimes" style='height: auto; width: 99%; margin: 2% 1% 2% 1%;'></div>
            </div>
            <div style='width: 50%; float: right;'>
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
        function input_changed() {
            document.getElementById("todo").style.border = "medium dashed #C8102E";
            document.getElementById('unsaved-changes').innerHTML = "Unsaved Changes... ";
        }
        function charCount() {
            var myElement = document.getElementById('todo');
            myElement.focus();
            var len = document.getElementById('todo').value.length;

            document.getElementById("todo").addEventListener("keydown", function(){
                var myElement = document.getElementById('todo').value;
                var len = myElement.length;
                document.getElementById('charLen').innerHTML = "Character count: " + len;
            },false);

            //console.log(len + " characters");
            document.getElementById('charLen').innerHTML = "Character count: " + len;

            return(len);
        }
        function setCursorPos(pos) {
            var myElement = document.getElementById('todo');

            myElement.focus();
            myElement.setSelectionRange(pos, pos);
            
            //console.log("Changing position of the cursor to (" + pos + "/" + myElement.value.length + ")");
            return(pos);
        }
        function getCursorPos() {
                let myElement = document.getElementById('todo');
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
            let str = document.getElementById('todo').value;
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
                    console.log("SUBSTRING Len: " + substring.length + 10)
                    offset = origPos - substringLength + substring.length + 10;
                }

                console.log("Offset: " + offset);
                document.getElementById('todo').value = newStr;
                setCursorPos(offset);
            }
            return(offset);
        }

        function replaceEmoji() {
            let str = document.getElementById('todo').value;
            let origPos = getCursorPos();
            let newStr = str;
            let pos = 0;
            let offset = 0;
            // Replace smiley face with emoji
            newStr = newStr.replaceAll(/\:\) /g, "😀 ");
            newStr = newStr.replaceAll(/\:D /g, "😁 ");
            newStr = newStr.replaceAll(/\;\) /g, "😉 ");
            newStr = newStr.replaceAll(/\:P /g, "😋 ");
            newStr = newStr.replaceAll(/\:\| /g, "😑 ");
            newStr = newStr.replaceAll(/\:0 /g, "😲 ");
            newStr = newStr.replaceAll(/\:O /g, "😲 ");
            newStr = newStr.replaceAll(/\:o /g, "😲 ");
            newStr = newStr.replaceAll(/\:\( /g, "😞 ");
            newStr = newStr.replaceAll(/\:\< /g, "😡 ");
            newStr = newStr.replaceAll(/\:\\ /g, "😕 ");
            newStr = newStr.replaceAll(/\;\( /g, "😢 ");
            newStr = newStr.replaceAll(/\:check /gi, "✅ ");
            newStr = newStr.replaceAll(/\:done /gi, "✅ ");
            newStr = newStr.replaceAll(/\:x /gi, "❌ ");
            newStr = newStr.replaceAll(/\:cancel /gi, "🚫 ");
            newStr = newStr.replaceAll(/\:working /gi, "⏳ ");
            newStr = newStr.replaceAll(/\:waiting /gi, "⏳ ");
            newStr = newStr.replaceAll(/\:inprogress /gi, "⏳ ");
            newStr = newStr.replaceAll(/\:shrug /gi, "🤷 ");
            newStr = newStr.replaceAll(/\:clock /gi, "🕓 ");
            newStr = newStr.replaceAll(/\:warning /gi, "⚠️ ");
            newStr = newStr.replaceAll(/\:arrow /gi, "⏩ ");
            newStr = newStr.replaceAll(/\:bug /gi, "🐛 ");
            newStr = newStr.replaceAll(/\:poop /gi, "💩 ");
            newStr = newStr.replaceAll(/\:star /gi, "⭐ ");
            newStr = newStr.replaceAll(/\:heart /gi, "❤️ ");
            newStr = newStr.replaceAll(/\:love /gi, "❤️ ");
            newStr = newStr.replaceAll(/\:fire /gi, "🔥 ");
            newStr = newStr.replaceAll(/\:like /gi, "👎 ");
            newStr = newStr.replaceAll(/\:dislike /gi, "👍 ");
            newStr = newStr.replaceAll(/\:info /gi, "ℹ️ ");
            newStr = newStr.replaceAll(/\:pin /gi, "📌 ");
            newStr = newStr.replaceAll(/\:clap /gi, "👏 ");
            newStr = newStr.replaceAll(/\:celebrate /gi, "🥳 ");
            newStr = newStr.replaceAll(/\:hmm /gi, "🤔 ");
            

            if (str != newStr) {
                let newPos = getCursorPos();
                const regex = /(\:inprogress)|(\:working)|(\:cancel)|(\:check)|(\:done)|(\:x)|(\:waiting)|(\:shrug)|(\:clock)|(\:warning)|(\:arrow)|(\:bug)|(\:poop)|(\:star)|(\:heart)|(\:love)|(\:fire)|(\:like)|(\:dislike)|(\:info)|(\:pin)|(\:clap)|(\:celebrate)|(\:hmm)/gi;
                const match = str.match(regex);

                offset = origPos;

                if (match) {
                    const substring = match[0];
                    const substringLength = substring.length;
                    //console.log("SUBSTRING Len: " + substring.length)
                    offset = origPos - substringLength + 1;
                }

                //console.log("Offset: " + offset);
                document.getElementById('todo').value = newStr;
                setCursorPos(offset);
            }
            return(offset);
        }

        function replaceAsterisk() {
            let str = document.getElementById('todo').value;
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
                document.getElementById('todo').value = newStr;
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
                    (SELECT COUNT(os_stats.tagnumber) FROM os_stats INNER JOIN (SELECT departments.tagnumber, departments.department FROM departments LEFT JOIN static_departments ON static_departments.department = departments.department WHERE time IN (SELECT MAX(time) FROM departments GROUP BY tagnumber) AND static_departments.department_bool = 1 AND departments.department = 'techComm') t1 ON os_stats.tagnumber = t1.tagnumber WHERE os_stats.os_installed = 1)
                        AS 'os_installed',
                    (SELECT COUNT(os_stats.tagnumber) FROM os_stats INNER JOIN (SELECT departments.tagnumber, departments.department FROM departments LEFT JOIN static_departments ON static_departments.department = departments.department WHERE time IN (SELECT MAX(time) FROM departments GROUP BY tagnumber) AND static_departments.department_bool = 1 AND departments.department = 'techComm') t1 ON os_stats.tagnumber = t1.tagnumber WHERE os_stats.os_installed IS NULL)
                        AS 'os_not_installed'");
                    if (arrFilter($db->get()) === 0) {
                        foreach ($db->get() as $key => $value) {
                            echo "['OS Installed'," . htmlspecialchars($value["os_installed"]) . "], ";
                            echo "['OS NOT Installed'," . htmlspecialchars($value["os_not_installed"]) . "], ";
                        }
                    }
                ?>
                ]);

                var options = {title: 'Number of OS\'s Installed', colors: ['#e7711b', '#6f9654'] };
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
                    (SELECT COUNT(locations.tagnumber) FROM locations INNER JOIN (SELECT MAX(time) AS 'time' FROM locations GROUP BY tagnumber) t1 ON locations.time = t1.time
                        INNER JOIN departments ON locations.tagnumber = departments.tagnumber 
                        INNER JOIN (SELECT MAX(time) AS 'time' FROM departments GROUP BY tagnumber) t2 ON departments.time = t2.time
                        WHERE departments.department = 'techComm' AND locations.status IS NULL AND locations.domain IS NOT NULL)
                        AS 'domain_joined',
                    (SELECT COUNT(locations.tagnumber) FROM locations INNER JOIN (SELECT MAX(time) AS 'time' FROM locations GROUP BY tagnumber) t1 ON locations.time = t1.time
                        INNER JOIN departments ON locations.tagnumber = departments.tagnumber 
                        INNER JOIN (SELECT MAX(time) AS 'time' FROM departments GROUP BY tagnumber) t2 ON departments.time = t2.time
                        WHERE departments.department = 'techComm' AND locations.status IS NULL AND locations.domain IS NULL)
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
                    (SELECT COUNT(bios_stats.bios_updated) 
                    FROM bios_stats 
                    INNER JOIN (SELECT departments.tagnumber, departments.department FROM departments LEFT JOIN static_departments ON static_departments.department = departments.department WHERE time IN (SELECT MAX(time) FROM departments GROUP BY tagnumber) AND static_departments.department_bool = 1 AND departments.department = 'techComm') t1 ON bios_stats.tagnumber = t1.tagnumber
                        WHERE bios_stats.bios_updated = 1) AS 'bios_updated', 
                    (SELECT SUM(IF(bios_stats.bios_updated IS NULL, 1, 0)) 
                    FROM bios_stats 
                    INNER JOIN (SELECT departments.tagnumber, departments.department FROM departments LEFT JOIN static_departments ON static_departments.department = departments.department WHERE time IN (SELECT MAX(time) FROM departments GROUP BY tagnumber) AND static_departments.department_bool = 1 AND departments.department = 'techComm') t1 ON bios_stats.tagnumber = t1.tagnumber
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