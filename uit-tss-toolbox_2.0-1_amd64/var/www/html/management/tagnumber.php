<?php
require('/var/www/html/management/header.php');
require('/var/www/html/management/php/include.php');

$db = new db();

if (isset($_POST["task"])) {
    $arrTask = explode('|', $_POST["task"]);
    if (strFilter($arrTask[0]) === 0) {
        $db->updateRemote($arrTask[0], "task", $arrTask[1]);
    }
    unset($_POST["task"]);
}

if (isset($_POST['department'])) {
    $uuid = uniqid("location-", true);
    $tagNum = $_GET["tagnumber"];
    $serial = $_POST["serial"];
    $department = $_POST['department'];
    $location = $_POST['location'];
    $status = $_POST["status"];
    $note = $_POST['note'];
    $diskRemoved = $_POST['disk_removed'];

    #Not the same insert statment as client parse code, ether address is DEFAULT here.
    $db->insertJob($uuid);
    $db->updateJob("tagnumber", $tagNum, $uuid);
    $db->updateJob("system_serial", $serial, $uuid);
    $db->updateJob ("date", $date, $uuid);
    $db->updateJob ("time", $time, $uuid);
    $db->updateJob ("department", $department, $uuid);


    $db->Pselect("SELECT erase_completed, clone_completed FROM jobstats WHERE tagnumber = :tagnumber AND (erase_completed = '1' OR clone_completed = '1') ORDER BY time DESC LIMIT 1", array(':tagnumber' => $tagNum));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value2) {
            if ($value2["erase_completed"] === 1 && $value2["clone_completed"] === 1) {
                $osInstalled = 1;
            } elseif ($value2["erase_completed"] === 1 && $value2["clone_completed"] !== 1) {
                $osInstalled = 0;
            } elseif ($value2["erase_completed"] !== 1 && $value2["clone_completed"] === 1) {
                $osInstalled = 1;
            } else {
                $osInstalled = 0;
            }
    
            $db->updateLocation("os_installed", $osInstalled, $value1["max_time"]);
        }
    }
    unset($value2);

    $db->Pselect("SELECT t1.bios_version, t2.system_model FROM jobstats t1 INNER JOIN system_data t2 ON t1.tagnumber = t2.tagnumber WHERE t1.bios_version IS NOT NULL AND t2.system_model IS NOT NULL AND t1.tagnumber = :tagnumber ORDER BY t1.time DESC LIMIT 1", array(':tagnumber' => $tagNum));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value2) {
            $db->select("SELECT bios_version FROM static_bios_stats WHERE system_model = '" . $value2["system_model"] . "'");
            if (arrFilter($db->get()) == 0) {
                foreach ($db->get() as $key => $value3) {
                    if ($value2["bios_version"] === $value3["bios_version"]) {
                        $biosBool = 1;
                    } else {
                        $biosBool = 0;
                    }
                }
            } else { $biosBool = 0; }
        }
    } else { $biosBool = 0; }
    unset($value2);
    unset($value3);

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

    unset($_POST);
    header("Location: " . $_SERVER['REQUEST_URI']);
}
unset($_POST);
?>

<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <link rel="stylesheet" href="/jquery/jquery-ui/jquery-ui-1.14.0/jquery-ui.min.css">
        <script src="/jquery/jquery-3.7.1.min.js"></script>
        <script src="/jquery/jquery-ui/jquery-ui-1.14.0/jquery-ui.min.js"></script>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>UIT Client Mgmt - <?php echo htmlspecialchars($_GET['tagnumber'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></title>
        <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
        <style>
        .ui-autocomplete {
            max-height: 100px;
            overflow-y: auto;
            /* prevent horizontal scrollbar */
            overflow-x: hidden;
        }
        </style>
    </head>
    <body onload="fetchHTML()">
    <script>
        $( function() {
            var availableTags = [
            <?php
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

        <div class='pagetitle'><h1>Client Lookup (<?php echo htmlspecialchars($_GET['tagnumber'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?>)</h1></div>
        <div class='pagetitle'><h2>Lookup data for a specific client.</h2></div>
        <div name='curTime' id='curTime' class='pagetitle'><h3>Data on this page was last updated at: <?php $db->select("SELECT DATE_FORMAT(CONCAT(CURDATE(), ' ', CURTIME()), '%b %D %Y, %r') AS 'time_formatted'"); if (arrFilter($db->get()) === 0) { foreach ($db->get() as $key => $sqlUpdatedTime) { echo $sqlUpdatedTime["time_formatted"]; } } ?></h3></div>

        <div class='laptop-images'>
        <?php
            $db->Pselect("SELECT system_model FROM system_data WHERE tagnumber = :tagnumber", array(':tagnumber' => htmlspecialchars_decode($_GET['tagnumber'])));
            if (arrFilter($db->get()) === 0) {
                foreach ($db->get() as $key => $value) {
                    if ($value["system_model"] === "Aspire T3-710") {
                        echo "<img src='/images/aspireT3710.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "HP ProBook 450 G6") {
                        echo "<img src='/images/hpProBook450G6.avif'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 3500") {
                        echo "<img src='/images/Latitude3500.avif'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 3560") {
                        echo "<img src='/images/Latitude3560.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 3590") {
                        echo "<img src='/images/latitude3590.jpeg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 5289") {
                        echo "<img src='/images/latitude5289.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 5480") {
                        echo "<img src='/images/latitude5480.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 5590") {
                        echo "<img src='/images/latitude5590.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 7400") {
                        echo "<img src='/images/latitude7400.avif'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 7430") {
                        echo "<img src='/images/latitude7430.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 7480") {
                        echo "<img src='/images/latitude7480.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude 7490") {
                        echo "<img src='/images/latitude7490.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude E6430") {
                        echo "<img src='/images/latitudeE6430.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Latitude E7470") {
                        echo "<img src='/images/latitudeE7470.webp'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "OptiPlex 7000") {
                        echo "<img src='/images/optiplex7000.avif'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "OptiPlex 7460 AIO") {
                        echo "<img src='/images/optiplex7460AIO.avif'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "OptiPlex 780") {
                        echo "<img src='/images/optiplex780.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "OptiPlex 790") {
                        echo "<img src='/images/optiplex790.avif'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "OptiPlex 9010 AIO") {
                        echo "<img src='/images/optiplex9010AIO.webp'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Surface Book") {
                        echo "<img src='/images/surfaceBook.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Surface Pro") {
                        echo "<img src='/images/surfacePro.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "Surface Pro 4") {
                        echo "<img src='/images/surfacePro4.jpg'>" . PHP_EOL;
                    } elseif ($value["system_model"] === "XPS 15 9560") {
                        echo "<img src='/images/xps15-9560.jpg'>" . PHP_EOL;
                    }
                }
            }
        ?>
        </div>

        <div>
        <div class="styled-table" style="width: 40%; height: auto; overflow:auto; margin: 1% 1% 5% 1%; float: left;">
            <table>
                <thead>
                    <tr>
                        <th>Update Job</th>
                        <th>Current Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                        <form name="task" method="post">
                            <select name="task" onchange='this.form.submit()'>
                    <?php
                    if ($_GET['tagnumber']) {
                        $db->select("SELECT (CASE WHEN task = 'update' THEN 'Update' WHEN task = 'nvmeErase' THEN 'Erase Only' WHEN task = 'hpEraseAndClone' THEN 'Erase + Clone' WHEN task = 'findmy' THEN 'Play Sound' WHEN task = 'hpCloneOnly' THEN 'Clone Only' WHEN task = 'cancel' THEN 'Cancel Running Jobs' WHEN task IS NULL THEN 'No Job' END) AS 'formatted_task', task, status, present_bool FROM remote WHERE tagnumber = '" . htmlspecialchars_decode($_GET['tagnumber']) . "'");
                        if (arrFilter($db->get()) === 0) {
                            foreach ($db->get() as $key => $value) {
                                echo "<option name='curJob' id='curJob' value='" . htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|" . htmlspecialchars($value["task"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "'>" . htmlspecialchars($value["formatted_task"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</option>";
                                echo "<option value='" . htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|update'>Update</option>";
                                echo "<option value='" . htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|nvmeErase'>Erase Only</option>";
                                echo "<option value='" . htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|hpCloneOnly'>Clone Only</option>";
                                echo "<option value='" . htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|hpEraseAndClone'>Erase + Clone</option>";
                                echo "<option value='" . htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|findmy'>Play Sound</option>";
                                if ($value["status"] !== "Waiting for job" || $value["present_bool"] !== 1) {
                                    echo "<option value='" . htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "| '>Clear Pending Jobs</option>";
                                } else {
                                    echo "<option value='" . htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|cancel'>Cancel Running Job</option>";
                                }
                            }
                        } else {
                            echo "<option>ERR: " . htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . " is not in the DB :(</option>";
                        }
                    }
                    ?>
                            </select>
                        </form>
                        </td>

                        <td name='curStatus' id='curStatus'>
                <?php
                $db->Pselect("SELECT DATE_FORMAT(present, '%b %D %Y, %r') AS 'time_formatted', status, present_bool, kernel_updated, SEC_TO_TIME(uptime) AS 'uptime_formatted' FROM remote WHERE tagnumber = :tagnumber", array(':tagnumber' => htmlspecialchars_decode($_GET["tagnumber"])));
                if (arrFilter($db->get()) === 0) {
                    foreach ($db->get() as $key => $value) {
                        if ($value["present_bool"] === 1 && $value["kernel_updated"] === 1) {
                            echo "Online <span style='color: #00B388'>&#10004;&#65039;</span> (" . $value["uptime_formatted"] . ")";
                        } elseif ($value["present_bool"] !== 1 && $value["kernel_updated"] !== 1) {
                            echo "Offline <span style='color: #C8102E'>&#10060;</span>";
                        } elseif ($value["present_bool"] === 1 && $value["kernel_updated"] !== 1) {
                            echo "Warning <span style='color: #F6BE00'>&#9888;&#65039;</span> (" . $value["uptime_formatted"] . ")";
                        } elseif ($value["present_bool"] !== 1 && $value["kernel_updated"] === 1) {
                            echo "Offline <span style='color: #C8102E'>&#10060;</span>)";
                        } else {
                            echo "Unknown <span style='color: #C8102E'>&#8265;&#65039;</span>";
                        }

                        if (strFilter($value["status"]) === 0) {
                            echo "<p><b>'" . htmlspecialchars($value["status"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "'</b> at " . htmlspecialchars($value["time_formatted"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</p>" . PHP_EOL;
                        }
                    }
                }
                ?>
                </td>
                </tr>
            </table>
        </div>


        <div style="width: 40%; float: right;">
            <form name="location-form" id="location-form" method="POST">
            <?php
                if (isset($_GET["tagnumber"])) {
                    echo "<div class='location-form'>" . PHP_EOL;
                    $db->Pselect("SELECT system_serial, location, DATE_FORMAT(time, '%b %D %Y, %r') AS 'time_formatted' FROM locations WHERE tagnumber = :tagnumber ORDER BY time DESC LIMIT 1", array(':tagnumber' => $_GET["tagnumber"]));
                    if ($db->get() === "NULL") {
                        $arr = array( array( "system_serial" => "NULL", "location" => "NULL", "time_formatted" => "NULL") );
                    } else {
                        $arr = $db->get();
                    }

                    foreach ($arr as $key => $value) {
                        $serial = $value["system_serial"];
                        echo "<input type='hidden' name='serial' id='serial' value='" . htmlspecialchars($serial, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "'>";
                        // Get the department
                        if (arrFilter($db->get()) === 0) {
                            // Get a human readable department
                            $db->Pselect("SELECT department, (CASE WHEN department='techComm' THEN 'Tech Commons (TSS)' WHEN department='property' THEN 'Property' WHEN department='shrl' THEN 'SHRL' WHEN department = 'execSupport' THEN 'Exec Support' ELSE '' END) AS 'department_formatted' FROM jobstats WHERE tagnumber = :tagnumber AND department IS NOT NULL ORDER BY time DESC LIMIT 1", array(':tagnumber' => $_GET["tagnumber"]));
                            if (arrFilter($db->get()) === 0) {
                                foreach ($db->get() as $key =>$value1) {
                                    $department = $value1["department"];
                                    $departmentHTML = htmlspecialchars($value1["department"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
                                    $departmentFormatted = htmlspecialchars($value1["department_formatted"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE);
                                }
                            }
                            unset($value1);
                        } else {
                            $department = "";
                            $departmentHTML = "";
                            $departmentFormatted = "NULL";
                        }
                        echo "<label for='department'>Department</label>" . PHP_EOL;
                        echo "<br>" . PHP_EOL;
                        echo "<select name='department' id='department'>" . PHP_EOL;
                        if ($department === "techComm") {
                            echo "<option value='$departmentHTML'>$departmentFormatted</option>" . PHP_EOL;
                            echo "<option value='property'>Property Management</option>" . PHP_EOL;
                            echo "<option value='shrl'>SHRL (Kirven)</option>" . PHP_EOL;
                        } elseif ($department === "property") {
                            echo "<option value='$departmentHTML'>$departmentFormatted</option>" . PHP_EOL;
                            echo "<option value='techComm'>Tech Commons (TSS)</option>" . PHP_EOL;
                            echo "<option value='shrl'>SHRL (Kirven)</option>" . PHP_EOL;
                        } elseif ($department === "shrl") {
                            echo "<option value='$departmentHTML'>$departmentFormatted</option>" . PHP_EOL;
                            echo "<option value='property'>Property Management</option>" . PHP_EOL;
                            echo "<option value='techComm'>Tech Commons (TSS)</option>" . PHP_EOL;
                        } else {
                            echo "<option>NULL (new entry)</option>" . PHP_EOL;
                            echo "<option value='techComm'>Tech Commons (TSS)</option>" . PHP_EOL;
                            echo "<option value='property'>Property Management</option>" . PHP_EOL;
                            echo "<option value='shrl'>SHRL (Kirven)</option>" . PHP_EOL;
                        }
                        echo "</select>" . PHP_EOL;
                        echo "<br>" . PHP_EOL;
                        if (arrFilter($db->get()) === 0) {
                            echo "<label for='location'>Location (Last Updated: " . htmlspecialchars($value["time_formatted"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . ")</label>" . PHP_EOL;
                            echo "<br>" . PHP_EOL;
                            echo "<input type='text' id='location' name='location' value='" . htmlspecialchars($value["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' required style='width: 20%; height: 4%;'>" . PHP_EOL;
                        } else {
                            echo "<label for='location'>Location</label>" . PHP_EOL;
                            echo "<br>" . PHP_EOL;
                            echo "<input type='text' id='location' name='location' required style='width: 20%; height: 4%;'>" . PHP_EOL;
                        }
                        echo "<br>" . PHP_EOL;
                        echo "<label for='note'>Note</label>" . PHP_EOL;
                        echo "<br>" . PHP_EOL;

                        if (arrFilter($db->get()) === 0) {
                            // Get the most recent note that's not null
                            
                            $db->Pselect("SELECT DATE_FORMAT(time, '%b %D %Y, %r') AS 'time_formatted', note, status FROM locations WHERE tagnumber = :tagnumber AND note IS NOT NULL ORDER BY time DESC LIMIT 1", array(':tagnumber' => $_GET["tagnumber"]));
                            if (arrFilter($db->get()) === 0) {
                                foreach ($db->get() as $key => $value1) {
                                    if ($value1["status"] === 1) {
                                        echo "<textarea name='note' id='note' style='width: 70%;'>" . htmlspecialchars($value1["note"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) .  "</textarea>" . PHP_EOL;
                                    } else {
                                        echo "<textarea name='note' id='note' style='width: 70%;' placeholder='(" . htmlspecialchars($value1["time_formatted"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "): ". htmlspecialchars($value1["note"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) .  "'></textarea>" . PHP_EOL;
                                    }
                                }
                            } else {
                                echo "<textarea name='note' id='note' style='width: 70%;'></textarea>" . PHP_EOL;
                            }
                            unset($value1);
                        } else {
                            echo "<textarea name='note' id='note' style='width: 70%;'></textarea>" . PHP_EOL;
                        }

                        echo "<br>" . PHP_EOL;
                        echo "<div>" . PHP_EOL;
                        echo "<div style='float: left;'>" . PHP_EOL;
                        echo "<label for='disk_removed'>Disk removed?</label>" . PHP_EOL;
                        echo "<br>" . PHP_EOL;
                        echo "<select name='disk_removed' id='disk_removed'>" . PHP_EOL;
                        if (arrFilter($db->get()) === 0) {
                            $db->Pselect("SELECT disk_removed FROM locations WHERE tagnumber = :tagnumber ORDER BY time DESC LIMIT 1", array(':tagnumber' => $_GET["tagnumber"]));
                            if (arrFilter($db->get()) === 0) {
                                foreach ($db->get() as $key => $value1) {
                                    if ($value1["disk_removed"] === 1) {
                                        echo "<option value='1'>Yes</option>" . PHP_EOL;
                                        echo "<option value='0'>No</option>" . PHP_EOL;
                                    } else {
                                        echo "<option value='0'>No</option>" . PHP_EOL;
                                        echo "<option value='1'>Yes</option>" . PHP_EOL;
                                    }
                                }
                            }
                            unset($value1);
                        } else {
                            echo "<option value='0'>No</option>" . PHP_EOL;
                            echo "<option value='1'>Yes</option>" . PHP_EOL;
                        }
                        echo "</select>" . PHP_EOL;
                        echo "</div>" . PHP_EOL;
                        echo "<div style='float: right;'>" . PHP_EOL;
                        echo "<label for='status'>Working or Broken?</label>" . PHP_EOL;
                        echo "<br>" . PHP_EOL;
                        echo "<select name='status' id='status'>" . PHP_EOL;
                        if (arrFilter($db->get()) === 0) {
                            $db->Pselect("SELECT status FROM locations WHERE tagnumber = :tagnumber ORDER BY time DESC LIMIT 1", array(':tagnumber' => $_GET["tagnumber"]));
                            if (arrFilter($db->get()) === 0) {
                                foreach ($db->get() as $key => $value1) {
                                    if ($value1["status"] !== 1) {
                                        echo "<option value='0'>Working</option>" . PHP_EOL;
                                        echo "<option value='1'>Broken</option>" . PHP_EOL;
                                    } else {
                                        echo "<option value='1'>Broken</option>" . PHP_EOL;
                                        echo "<option value='0'>Working</option>" . PHP_EOL;
                                    }
                                }
                            }
                            unset($value1);
                        } else {
                            echo "<option value='1'>Yes</option>" . PHP_EOL;
                            echo "<option value='0'>No</option>" . PHP_EOL;
                        }
                        echo "</select>" . PHP_EOL;
                        echo "</div>" . PHP_EOL;
                        echo "</div>" . PHP_EOL;
                        echo "<br>" . PHP_EOL;

                        echo "<br>" . PHP_EOL;

                        echo "<input class='page-content' type='submit' value='Update Location Data'>" . PHP_EOL;

                        echo "<div class='page-content'><a href='locations.php' target='_blank'>Update a different client's location.</a></div>" . PHP_EOL;
                    }
                }
                unset($arr);
                ?>
            </form>
        </div>
        </div>

        
        <div class='pagetitle'><h3>General Client Info - <u><?php echo htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></u></h3></div>
        <div name='updateDiv1' id='updateDiv1' class='styled-table' style="width: auto; height: auto; overflow:auto; margin: 1% 1% 5% 1%;">
        <table width="100%">
            <thead>
                <tr>
                <th>System Serial</th>
                <th>MAC Address</th>
                <th>Department</th>
                <th>System Manufacturer</th>
                <th>System Model</th>
                <th>BIOS Version</th>
                <th>CPU Model</th>
                <th>Disk Type</th>
                <th>Link Speed</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $db->Pselect("SELECT t1.system_serial, t1.disk_type, t1.etheraddress, t2.chassis_type, t2.wifi_mac, t2.system_manufacturer, t2.system_model, t2.cpu_model FROM jobstats t1 INNER JOIN system_data t2 ON t1.tagnumber = t2.tagnumber WHERE t1.tagnumber = :tagnumber1 AND t2.tagnumber = :tagnumber2 AND t1.uuid NOT LIKE 'location-%' AND t2.system_model IS NOT NULL ORDER BY t1.time DESC LIMIT 1", array(':tagnumber1' => htmlspecialchars_decode($_GET['tagnumber']), ':tagnumber2' => htmlspecialchars_decode($_GET['tagnumber'])));
                if (arrFilter($db->get()) === 0) {
                    foreach ($db->get() as $key => $value) {
                    echo "<tr>" . PHP_EOL;
                    echo "<td>" . $value['system_serial'] . "</td>" . PHP_EOL;
                    echo "<td>";
                    // Latitude 7400 does not have ethernet ports, we use the USB ethernet ports for them, but the USB ethernet MAC address is still associated with their tagnumbers.
                    if ($value["system_model"] !== "Latitude 7400") {
                        if (strFilter($value["wifi_mac"]) === 0 && strFilter($value["etheraddress"]) === 0) {
                            echo "<table><tr><td>" . $value["wifi_mac"] . " (Wi-Fi)</td></tr><tr><td>" . $value["etheraddress"] . " (Ethernet)</td></tr></table>" . PHP_EOL;
                        } elseif (strFilter($value["wifi_mac"]) === 0 && strFilter($value["etheraddress"]) === 1) {
                            echo $value["wifi_mac"] . " (Wi-Fi)";
                        } elseif (strFilter($value["wifi_mac"]) === 1 && strFilter($value["etheraddress"]) === 0) {
                            echo $value["etheraddress"] . " (Ethernet)";
                        }
                    } elseif ($value["system_model"] === "Latitude 7400") {
                        if (strFilter($value["wifi_mac"]) === 0 && strFilter($value["etheraddress"]) === 0) {
                            echo $value["wifi_mac"] . " (Wi-Fi)";
                        } elseif (strFilter($value["wifi_mac"]) === 0 && strFilter($value["etheraddress"]) === 1) {
                            echo $value["wifi_mac"] . " (Wi-Fi)";
                        }
                    }
                    echo "</td>" . PHP_EOL;
                    $db->Pselect("SELECT (CASE WHEN department='techComm' THEN 'Tech Commons (TSS)' WHEN department='property' THEN 'Property' WHEN department='shrl' THEN 'SHRL' ELSE '' END) AS 'department' FROM jobstats WHERE tagnumber = :tagnumber ORDER BY time DESC LIMIT 1", array(':tagnumber' => htmlspecialchars_decode($_GET["tagnumber"])));
                    if (arrFilter($db->get()) === 0) {
                        foreach ($db->get() as $key => $value1) {
                            echo "<td>" . $value1['department'] . "</td>" . PHP_EOL;
                        }
                    } else {
                        echo "<td>NULL</td>" . PHP_EOL;
                    }
                    unset($value1);
                    echo "<td>" . $value['system_manufacturer'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['system_model'] . "</td>" . PHP_EOL;
                    $db->select("SELECT bios_version FROM jobstats WHERE bios_version IS NOT NULL AND tagnumber = '" . htmlspecialchars_decode($_GET["tagnumber"]) . "' ORDER BY time DESC LIMIT 1");
                    if (arrFilter($db->get()) === 0) {
                        foreach ($db->get() as $key => $value1) {
                            echo "<td>" . $value1["bios_version"] . "</td>" . PHP_EOL;
                        }
                    }
                    unset($value1);
                    echo "<td>" . $value['cpu_model'] . "</td>" . PHP_EOL;
                    echo "<td>" . $value['disk_type'] . "</td>" . PHP_EOL;
                    $db->Pselect("SELECT CONCAT(network_speed, 'mbps') AS 'network_speed' FROM remote WHERE tagnumber = :tagnumber", array(':tagnumber' => htmlspecialchars_decode($_GET['tagnumber'])));
                    if (arrFilter($db->get()) === 0) {
                        foreach ($db->get() as $key => $value1) {
                            echo "<td>" . $value1['network_speed'] . "</td>" . PHP_EOL;
                        }
                    }
                    unset($value1);
                    echo "</tr>" . PHP_EOL;
                    }
                }
                ?>
            </tbody>
        </table>
        </div>

        <div class='pagetitle'><h3>Location Info</h3></div>

        <div name='updateDiv2' id='updateDiv2' class='styled-table' style="width: auto; height: auto; overflow:auto; margin: 1% 1% 5% 1%;">
        <table width="100%">
            <thead>
                <tr>
                <th>Time</th>
                <th>Location</th>
                <th>Status</th>
                <th>OS Installed</th>
                <th>Disk Removed</th>
                <th>Note</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $db->select("SELECT DATE_FORMAT(time, '%b %D %Y, %r') AS 'time_formatted', location, IF (status='0' OR status IS NULL, 'Working', 'Broken') AS 'status', IF (disk_removed = 1, 'Yes', 'No') AS 'disk_removed', IF (os_installed = 1, 'Yes', 'No') AS 'os_installed', note FROM locations WHERE tagnumber = '" . htmlspecialchars_decode($_GET['tagnumber']) . "' ORDER BY time DESC LIMIT 1");
                if (arrFilter($db->get()) === 0) {
                    foreach ($db->get() as $key => $value) {
                    echo "<tr>" . PHP_EOL;
                    echo "<td>" . htmlspecialchars($value['time_formatted'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                    if (preg_match("/^[a-zA-Z]$/", $value["location"])) { 
                        echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars(strtoupper($value["location"]), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b></td>" . PHP_EOL;
                    } elseif (preg_match("/^checkout$/", $value["location"])) {
                        echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . "Checkout" . "</a></b></td>" . PHP_EOL;
                    } else {
                        echo "<td><b><a href='locations.php?location=" . htmlspecialchars($value["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "' target='_blank'>" . htmlspecialchars($value["location"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</a></b></td>" . PHP_EOL;
                    }
                    echo "<td>" . htmlspecialchars($value['status'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                    echo "<td>" . htmlspecialchars($value['os_installed'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                    echo "<td>" . htmlspecialchars($value['disk_removed'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                    echo "<td>" . htmlspecialchars($value['note'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                    echo "</tr>" . PHP_EOL;
                    }
                }
                ?>
            </tbody>
        </table>
        </div>

        <div class='pagetitle'><h3>Client Health</h3></div>
        <div name='updateDiv3' id='updateDiv3' class='styled-table' style="width: auto; overflow:auto; margin: 1% 1% 5% 1%;">
        <table width="100%">
            <thead>
                <tr>
                <th>Erase Avg. Time</th>
                <th>Clone Avg. Time</th>
                <th>Battery Health</th>
                <th>Disk Health</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $db->Pselect("SELECT CONCAT(erase_avgtime, ' mins') AS 'erase_avgtime', CONCAT(clone_avgtime, ' mins') AS 'clone_avgtime', CONCAT(battery_health, '%') AS 'battery_health', CONCAT(disk_health, '%') AS 'disk_health' FROM clientstats WHERE tagnumber = :tagnumber", array(':tagnumber' => htmlspecialchars_decode($_GET["tagnumber"])));
                if (arrFilter($db->get()) === 0) {
                    foreach ($db->get() as $key=>$value) {
                        echo "<tr>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value['erase_avgtime'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value['clone_avgtime'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value['battery_health'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value['disk_health'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "</tr>" . PHP_EOL;
                    }
                }
                ?>
            </tbody>
        </table>
        </div>

        <div class='pagetitle'><h3>Job Log</h3></div>
        <div name='updateDiv4' id='updateDiv4' class='styled-table' style="width: auto; overflow:auto; margin: 1% 1% 5% 1%;">
        <table width="100%">
            <thead>
                <tr>
                <th>Time</th>
                <th>CPU Usage</th>
                <th>Network Usage</th>
                <th>Erase Completed</th>
                <th>Erase Mode</th>
                <th>Erase Time Elapsed</th>
                <th>Clone Completed</th>
                <th>Clone Master</th>
                <th>Clone Time</th>
                <th>BIOS Version</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $db->select("SELECT DATE_FORMAT(time, '%b %D %Y, %r') AS 'time_formatted', CONCAT(cpu_usage, '%') AS 'cpu_usage', CONCAT(network_usage, ' mbps') AS 'network_usage', IF (erase_completed = 1, 'Yes', 'No') AS 'erase_completed', erase_mode, SEC_TO_TIME(erase_time) AS 'erase_time', IF (clone_completed = 1, 'Yes', 'No') AS clone_completed, IF (clone_master = 1, 'Yes', 'No') AS clone_master, SEC_TO_TIME(clone_time) AS 'clone_time', bios_version FROM jobstats WHERE tagnumber = '" . htmlspecialchars_decode($_GET['tagnumber']) . "' AND (erase_completed = '1' OR clone_completed = '1') ORDER BY time DESC");
                if (arrFilter($db->get()) === 0) {
                    foreach ($db->get() as $key=>$value) {
                        echo "<tr>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value['time_formatted'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value['cpu_usage'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value['network_usage'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value['erase_completed'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value['erase_mode'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value['erase_time'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value['clone_completed'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value['clone_master'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value['clone_time'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "<td>" . htmlspecialchars($value['bios_version'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</td>" . PHP_EOL;
                        echo "</tr>" . PHP_EOL;
                    }
                }
                ?>
            </tbody>
        </table>
        </div>

        <script>
            if ( window.history.replaceState ) {
                window.history.replaceState( null, null, window.location.href );
            }

            var i = 0;
            function fetchHTML() {
                const var1 = setTimeout(function() {
                fetch('/tagnumber.php?tagnumber=<?php echo $_GET['tagnumber']; ?>')
                .then((response) => {
                        return response.text();
                })
                .then((html) => {
                    //document.body.innerHTML = html
                    const parser = new DOMParser()
                    const doc = parser.parseFromString(html, "text/html")
                    // update current time
                    const curTime = doc.getElementById('curTime').innerHTML
                    document.getElementById("curTime").innerHTML = curTime
                    // update current job in dropdown menu
                    const curJob = doc.getElementById('curJob').innerHTML
                    document.getElementById("curJob").innerHTML = curJob
                    // update current status
                    const curStatus = doc.getElementById('curStatus').innerHTML
                    document.getElementById("curStatus").innerHTML = curStatus
                    // update all other tables
                    const updateDiv1 = doc.getElementById('updateDiv1').innerHTML
                    document.getElementById("updateDiv1").innerHTML = updateDiv1
                    const updateDiv2 = doc.getElementById('updateDiv2').innerHTML
                    document.getElementById("updateDiv2").innerHTML = updateDiv2
                    const updateDiv3 = doc.getElementById('updateDiv3').innerHTML
                    document.getElementById("updateDiv3").innerHTML = updateDiv3
                    const updateDiv4 = doc.getElementById('updateDiv4').innerHTML
                    document.getElementById("updateDiv4").innerHTML = updateDiv4
                    const location = doc.getElementById('location').innerHTML
                    document.getElementById("location").innerHTML = location
                    const note = doc.getElementById('note').innerHTML
                    document.getElementById("note").innerHTML = note
                });
                fetchHTML();
            }, 3000)}
        </script>

    <div class="uit-footer">
        <img src="/images/uh-footer.svg">
    </div>
    </body>
</html>