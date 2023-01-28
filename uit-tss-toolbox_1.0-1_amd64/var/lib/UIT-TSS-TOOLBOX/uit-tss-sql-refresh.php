<?php
include('/var/lib/UIT-TSS-TOOLBOX/DB-connect-local.php');

$date = date('Y-m-d',time());
$time = date('Y-m-d H:i:s', time());

$sql = "SELECT tagnumber FROM jobstats WHERE NOT tagnumber = '000000'";
#Update linecount
$lineCount = mysqli_num_rows($sql);
$results = $conn->query($sql);
while ($row = $results->fetch_array(MYSQLI_ASSOC)) {
    $sql = "SELECT tagnumber FROM jobstats WHERE tagnumber = '" . $row['tagnumber'] . "' LIMIT 1";
    $tagNum = $conn->query($sql);

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
        $sql = "UPDATE clientstats SET disk = '" . $row['disk'] . "' WHERE tagnumber = '" . $row['tagnumber'] . "'";
        $conn->query($sql);
    }

    # Image and Erase Times
    $sql = "SELECT ROUND(SUM(erase_time), 2) AS erase_time FROM jobstats WHERE tagnumber = '" . $row['tagnumber'] . "'";
    $results = $conn->query($sql);
    while ($row = $results->fetch_array(MYSQLI_ASSOC)) {
        $eraseTime = $row['erase_time']
    }

    $sql = "SELECT ROUND(SUM(clone_time), 2) AS clone_time FROM jobstats WHERE tagnumber = '" . $row['tagnumber'] . "'";
    $results = $conn->query($sql);
    while ($row = $results->fetch_array(MYSQLI_ASSOC)) {
        $cloneTime = $row['clone_time']
    }

    $sql = "SELECT ROUND(SUM(erase_time + clone_time), 2) AS all_time FROM jobstats WHERE tagnumber = '" . $row['tagnumber'] . "'";
    $results = $conn->query($sql);
    while ($row = $results->fetch_array(MYSQLI_ASSOC)) {
        $allTime = $row['all_time']
        $sql = "UPDATE clientstats SET all_time = '" . $row['all_time'] . "' WHERE tagnumber = '" . $row['tagnumber'] . "'";
    }

    # Avg. Times
    $sql = "SELECT $allTime DIV $lineCount AS all_avgtime";
    $results = $conn->query($sql);
    while ($row = $results->fetch_array(MYSQLI_ASSOC)) {

        $conn->query($sql);
    }

    
}

?>