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
?>

<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>UIT Client Mgmt - <?php echo htmlspecialchars($_GET['tagnumber'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></title>
        <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    </head>
    <body>
        <div class='menubar'>
            <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
            <p><span style='float: right;'>Logged in as <b><?php echo htmlspecialchars($login_user, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></b>.</span></p>
            <br>
            <p><span style='float: right;'>Not <b><?php echo htmlspecialchars($login_user, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
        </div>

        <div class='pagetitle'><h1>Client Lookup (<?php echo htmlspecialchars($_GET['tagnumber'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?>)</h1></div>
        <div class='pagetitle'><h2>Lookup data for a specific client.</h2></div>
        <div class='pagetitle'><h3>Data on this page was last updated at: <?php $db->select("SELECT DATE_FORMAT(CONCAT(CURDATE(), ' ', CURTIME()), '%b %D %Y, %r') AS 'time_formatted'"); if (arrFilter($db->get()) === 0) { foreach ($db->get() as $key => $sqlUpdatedTime) { echo $sqlUpdatedTime["time_formatted"]; } } ?></h3></div>

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

        <div class="styled-table" style="width: auto; height: auto; overflow:auto; margin: 1% 1% 5% 1%;">
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
                        $db->select("SELECT (CASE WHEN task = 'update' THEN 'Update' WHEN task = 'nvmeErase' THEN 'Erase Only' WHEN task = 'hpEraseAndClone' THEN 'Erase + Clone' WHEN task = 'findmy' THEN 'Play Sound' WHEN task = 'hpCloneOnly' THEN 'Clone Only' WHEN task = 'cancel' THEN 'Cancel Running Jobs' WHEN task IS NULL THEN 'No Job' END) AS 'formatted_task', task, status FROM remote WHERE tagnumber = '" . htmlspecialchars_decode($_GET['tagnumber']) . "'");
                        if (arrFilter($db->get()) === 0) {
                            foreach ($db->get() as $key => $value) {
                                echo "<option value='" . htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|" . htmlspecialchars($value["task"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "'>" . htmlspecialchars($value["formatted_task"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "</option>";
                                echo "<option value='" . htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|update'>Update</option>";
                                echo "<option value='" . htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|nvmeErase'>Erase Only</option>";
                                echo "<option value='" . htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|hpCloneOnly'>Clone Only</option>";
                                echo "<option value='" . htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|hpEraseAndClone'>Erase + Clone</option>";
                                echo "<option value='" . htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE) . "|findmy'>Play Sound</option>";
                                if ($value["status"] === "Waiting for job") {
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

                        <td>
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

        
        <div class='pagetitle'><h3>General Client Info - <u><?php echo htmlspecialchars($_GET["tagnumber"], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></u></h3></div>
        <div class='styled-table' style="width: auto; height: auto; overflow:auto; margin: 1% 1% 5% 1%;">
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
                $db->Pselect("SELECT t1.system_serial, t1.disk_type, t1.etheraddress, t2.chassis_type, t2.wifi_mac, (CASE WHEN t1.department='techComm' THEN 'Tech Commons (TSS)' WHEN t1.department='property' THEN 'Property' WHEN t1.department='shrl' THEN 'SHRL' ELSE '' END) AS 'department', t2.system_manufacturer, t2.system_model, t2.cpu_model FROM jobstats t1 INNER JOIN system_data t2 ON t1.tagnumber = t2.tagnumber WHERE t1.tagnumber = :tagnumber1 AND t2.tagnumber = :tagnumber2 AND t1.uuid NOT LIKE 'location-%' AND t2.system_model IS NOT NULL ORDER BY t1.time DESC LIMIT 1", array(':tagnumber1' => htmlspecialchars_decode($_GET['tagnumber']), ':tagnumber2' => htmlspecialchars_decode($_GET['tagnumber'])));
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
                    echo "<td>" . $value['department'] . "</td>" . PHP_EOL;
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

        <div class='styled-table' style="width: auto; height: auto; overflow:auto; margin: 1% 1% 5% 1%;">
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
        <div class='styled-table' style="width: auto; overflow:auto; margin: 1% 1% 5% 1%;">
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
        <div class='styled-table' style="width: auto; overflow:auto; margin: 1% 1% 5% 1%;">
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
        </script>

    <div class="uit-footer">
        <img src="/images/uh-footer.svg">
    </div>
    </body>
</html>