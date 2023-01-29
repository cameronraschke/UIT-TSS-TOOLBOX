<?php
include('/var/lib/UIT-TSS-TOOLBOX/DB-connect-local.php');

$date = date('Y-m-d',time());
$time = date('Y-m-d H:i:s', time());

##### clientstats #####
$sql = "SELECT tagnumber FROM jobstats WHERE NOT tagnumber = '000000'";
#Update linecount
$lineCount = mysqli_num_rows($sql);
$results = $conn->query($sql);
while ($row = $results->fetch_array(MYSQLI_ASSOC)) {
    global $tagNum = $row['tagnumber'];
    $sql = "SELECT tagnumber FROM jobstats WHERE tagnumber = '$tagNum' LIMIT 1";
    $results = $conn->query($sql);

    if ($row['tagnumber'] !== $tagNum) {
        $sql = "INSERT INTO clientstats(tagnumber,device_type,last_job_date,all_lastuuid,all_time,";
        $sql .= "all_avgtime,all_avgtimetoday,erase_avgtime,erase_avgtimetoday,clone_avgtime,clone_avgtimetoday,";
        $sql .= "all_jobs,all_jobstoday,erase_jobs,erase_jobstoday,clone_jobs,disk,clone_jobstoday)";
        $sql .= " VALUES ('" . $row['tagnumber'] . "','N/A','N/A','N/A','N/A','N/A','N/A','N/A','N/A','N/A','N/A','N/A','N/A',";
        $sql .= "'N/A','N/A','N/A','N/A','N/A')";
        $conn->query($sql);
    }

    #Update disks
    $sql = "SELECT disk FROM jobstats WHERE tagnumber = '" . $row['tagnumber'] . "'";
    $disks = $conn->query($sql);
    while ($row = $disks->fetch_array(MYSQLI_ASSOC)) {
        $sql = "UPDATE clientstats SET disk = '" . $row['disk'] . "' WHERE tagnumber = '$tagNum'";
        $conn->query($sql);
    }

    # Erase times
    $sql = "SELECT ROUND(SUM(erase_time), 2) AS erase_time FROM jobstats WHERE tagnumber = '$tagNum' AND erase_completed = 'Yes'";
    $results = $conn->query($sql);
    $eraseLineCount = mysqli_num_rows($sql);
    # Update total erase jobs
    $sql = "UPDATE clientstats SET erase_jobs = '$eraseLineCount' WHERE tagnumber = '$tagNum'";
    $conn->query($sql);
    while ($row = $results->fetch_array(MYSQLI_ASSOC)) {
        $eraseTime = $row['erase_time']
        $sql = "UPDATE clientstats SET erase_time = '" . $row['erase_time'] . "' WHERE tagnumber = '$tagNum'";
        $conn->query($sql);
        # Avg. Times
        $sql = "SELECT $eraseTime DIV $eraseLineCount AS erase_avgtime";
        $results = $conn->query($sql);
        while ($row = $results->fetch_array(MYSQLI_ASSOC)) {
            $avgTimeSec = $row['erase_avgtime'];
            $avgTimeMin = $row['erase_avgtime'] / 60 . " minutes";
            $sql = "UPDATE clientstats SET erase_avgtime = '$avgTimeMin' WHERE tagnumber = '$tagNum'";
            $conn->query($sql);
        }
    }

    # Clone times
    $sql = "SELECT ROUND(SUM(clone_time), 2) AS clone_time FROM jobstats WHERE tagnumber = '$tagNum' AND clone_completed = 'Yes'";
    $results = $conn->query($sql);
    $cloneLineCount = mysqli_num_rows($sql);
    # Update total clone jobs
    $sql = "UPDATE clientstats SET erase_jobs = '$cloneLineCount' WHERE tagnumber = '$tagNum'";
    $conn->query($sql);
    while ($row = $results->fetch_array(MYSQLI_ASSOC)) {
        $cloneTime = $row['clone_time']
        $sql = "UPDATE clientstats SET clone_time = '" . $row['clone_time'] . "' WHERE tagnumber = '$tagNum'";
        $conn->query($sql);
        # Avg. Times
        $sql = "SELECT $cloneTime DIV $cloneLineCount AS clone_avgtime";
        $results = $conn->query($sql);
        while ($row = $results->fetch_array(MYSQLI_ASSOC)) {
            $avgTimeSec = $row['clone_avgtime'];
            $avgTimeMin = $row['clone_avgtime'] / 60 . " minutes";
            $sql = "UPDATE clientstats SET clone_avgtime = '$avgTimeMin' WHERE tagnumber = '$tagNum'";
            $conn->query($sql);
        }
    }

    # All (clone + erase) times
    $sql = "SELECT ROUND(SUM(erase_time + clone_time), 2) AS all_time FROM jobstats WHERE tagnumber = '$tagNum' AND (erase_completed = 'Yes' OR clone_completed = 'Yes')";
    $results = $conn->query($sql);
    $allLineCount = mysqli_num_rows($sql);
    # Update total jobs
    $sql = "UPDATE clientstats SET erase_jobs = '$eraseLineCount' WHERE tagnumber = '$tagNum'";
    $conn->query($sql);
    while ($row = $results->fetch_array(MYSQLI_ASSOC)) {
        $allTime = $row['all_time']
        $sql = "UPDATE clientstats SET all_time = '" . $row['all_time'] . "' WHERE tagnumber = '$tagNum'";
        $conn->query($sql);
        # Avg. Times
        $sql = "SELECT $allTime DIV $allLineCount AS all_avgtime";
        $results = $conn->query($sql);
        while ($row = $results->fetch_array(MYSQLI_ASSOC)) {
            $avgTimeSec = $row['all_avgtime'];
            $avgTimeMin = $row['all_avgtime'] / 60 . " minutes";
            $sql = "UPDATE clientstats SET all_avgtime = '$avgTimeMin' WHERE tagnumber = '$tagNum'";
            $conn->query($sql);
        }
        # Dates
        $sql = "SELECT date FROM jobstats WHERE tagnumber = '$tagNum' ORDER BY date DESC LIMIT 1";
        $results = $conn->query($sql);
        while ($row = $results->fetch_array(MYSQLI_ASSOC)) {
            $sql = "UPDATE clientstats SET last_job_date = '".$row['date']."' WHERE tagnumber = '$tagNum'"
            $conn->query($sql);
        }
        # Device Type
        $sql = "SELECT tagnumber FROM jobstats WHERE clone_image LIKE '%HP' AND tagnumber = '$tagNum'";
        $results = $conn->query($sql);
        while ($row = $results->fetch_array(MYSQLI_ASSOC)) {
            $sql = "UPDATE clientstats SET device_type = 'HP' WHERE tagnumber = '$tagNum'"
            $conn->query($sql);
        }
    }

    
}



##### serverstats #####
# Update date of report
$sql = "SELECT date FROM serverstats";
$results = $conn->query($sql);
while ($row = $results->fetch_array(MYSQLI_ASSOC)) {
    if ($date !== $row['date']) {
        $sql = "INSERT INTO serverstats(date,laptop_count,last_image_update,all_jobs,clone_jobs,erase_jobs,";
        $sql .= "all_avgtime,clone_avgtime,nvme_erase_avgtime,ssd_erase_avgtime) VALUES ('$date',";
        $sql .= "'N/A','N/A','N/A','N/A','N/A','N/A','N/A','N/A','N/A')";
        $conn->query($sql);
    }
}

# Update laptop count
$sql = "SELECT tagnumber FROM clientstats WHERE NOT tagnumber = '000000'";
$laptopCount = mysqli_num_rows($sql);
$sql = "UPDATE serverstats SET laptop_count = '$laptopCount' WHERE date = '$date'";
$conn->query($sql);

# Update clone_avgtime
$sql = "SELECT ROUND(SUM(clone_avgtime), 2) AS clone_avgtime FROM clientstats WHERE NOT tagnumber = '000000' AND NOT clone_avgtime = '0 minutes'";
$cloneLineCount = mysqli_num_rows($sql);
$results = $conn->query($sql);
while ($row = $results->fetch_array(MYSQLI_ASSOC)) {
    $cloneAvgTime = $row['clone_avgtime'] / $cloneLineCount;
    $sql = "UPDATE serverstats SET clone_avgtime = '$cloneAvgTime minutes' WHERE date = '$date'";
    $conn->query($sql);
}

$mysqli->close();
?>