<?php
include('/usr/sbin/DB-connect-local.php');

$date = date('Y-m-d',time());
$time = date('Y-m-d H:i:s', time());

$sql = "SELECT tagnumber FROM jobstats WHERE NOT tagnumber = '000000'";
$results = $conn->query($sql);
$lineCount = mysqli_num_rows($sql);
$sql = "SELECT tagnumber FROM jobstats WHERE NOT tagnumber = '000000' AND date = '$date'";
$lineCountToday = mysqli_num_rows($sql);
while ($row = $results->fetch_array(MYSQLI_ASSOC)) {
    $sql = "SELECT tagnumber FROM jobstats WHERE tagnumber = '" . $row['tagnumber'] . "'";
    $results = $conn->query($sql);

    if ($row['tagnumber'] !== $results) {
        $sql = "INSERT INTO clientstats(tagnumber,device_type,last_job_date,all_lastuuid,all_time,";
        $sql .= "all_avgtime,all_avgtimetoday,erase_avgtime,erase_avgtimetoday,clone_avgtime,clone_avgtimetoday,";
        $sql .= "all_jobs,all_jobstoday,erase_jobs,erase_jobstoday,clone_jobs,disk,clone_jobstoday)";
        $sql .= " VALUES ('" . $row['tagnumber'] . "','N/A','N/A','N/A','N/A','N/A','N/A','N/A','N/A','N/A','N/A','N/A','N/A',";
        $sql .= "'N/A','N/A','N/A','N/A','N/A')";
        $conn->query($sql);
    }
}


?>