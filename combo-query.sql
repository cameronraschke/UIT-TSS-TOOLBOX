SELECT
    COUNT(DISTINCT jobstats.tagnumber) AS 'client_count',
    ROUND(AVG(t1.clone_time / 60), 0)
FROM jobstats
INNER JOIN (select tagnumber, time, clone_time, ROW_NUMBER() OVER(PARTITION BY jobstats.tagnumber ORDER BY time DESC) as 'row_count' from jobstats WHERE date BETWEEN '2023-01-09' AND '2025-01-01') t1 ON t1.tagnumber = jobstats.tagnumber AND t1.time = jobstats.time
INNER JOIN departments ON departments.department = jobstats.department
WHERE date BETWEEN '2023-01-09' AND '2025-01-01'
AND t1.row_count <= 3
    
    
    # Update client count. A client counts as a distinct tagnumber in jobstats which is in the Tech Commons or SHRL department.
    $db->Pselect("SELECT tagnumber FROM jobstats WHERE department IN ('techComm', 'shrl', 'execSupport') AND date BETWEEN :startdate AND :itdate GROUP BY tagnumber", array(':startdate' => $startDate, ':itdate' => $value["date"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->updateSS("client_count", count($db->get()), $value["date"]);
        }
    }
    unset($value1);

    # Update the average time taken to clone a computer.
    $db->Pselect("SELECT ROUND(AVG(clone_time_minutes), 0) AS 'clone_time_formatted' FROM (SELECT clone_time / 60 AS 'clone_time_minutes', ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'n' FROM jobstats WHERE clone_completed = 1 AND clone_time IS NOT NULL AND date <= :itdate AND (disk_type = 'nvme' OR disk like 'nvme%')) t2 WHERE t2.n <= 3", array(':itdate' => $value["date"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->updateSS("clone_avgtime", $value1["clone_time_formatted"], $value["date"]);
        }
    }
    unset($value1);


    # Update the average erase time of NVMe and SSD drives based on the previous three jobs per client.
    $db->Pselect("SELECT ROUND(AVG(erase_time_minutes), 0) AS 'erase_time_formatted' FROM (SELECT erase_time / 60 AS 'erase_time_minutes', ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'n' FROM jobstats WHERE erase_completed = 1 AND erase_time IS NOT NULL AND date <= :itdate AND (disk_type = 'nvme' OR disk like 'nvme%')) t2 WHERE t2.n <= 3", array(':itdate' => $value["date"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->updateSS("nvme_erase_avgtime", $value1["erase_time_formatted"], $value["date"]);
        }
    }
    unset($value1);


    # Update the average erase time of hard drives based on the previous three jobs per client.
    $db->Pselect("SELECT ROUND(AVG(erase_time_minutes), 0) AS 'erase_time_formatted' FROM (SELECT erase_time / 60 AS 'erase_time_minutes', ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS 'n' FROM jobstats WHERE erase_completed = 1 AND erase_time IS NOT NULL AND date <= :itdate AND (disk_type = 'hdd' OR disk_type = 'ssd' OR disk like 'sd%')) t2 WHERE t2.n <= 3", array(':itdate' => $value["date"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->updateSS("sata_erase_avgtime", $value1["erase_time_formatted"], $value["date"]);
        }
    }
    unset($value1);


    # Update the number of erase jobs that have completed
    $db->Pselect("SELECT COUNT(erase_completed) as 'erase_completed' FROM jobstats WHERE erase_completed = 1 AND date = :itdate", array(':itdate' => $value["date"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $arrEraseJobsLC[] = $value1["erase_completed"];
        }
        $eraseJobsLC = array_sum(array_filter($arrEraseJobsLC));
    }
    if (numFilter($eraseJobsLC) === 0) {
        $db->updateSS("erase_jobs", $eraseJobsLC, $value["date"]);
    }
    unset($value1);


    # Update the number of clone jobs that have completed
    $db->Pselect("SELECT COUNT(clone_completed) AS 'clone_completed' FROM jobstats WHERE clone_completed = 1 AND date = :itdate", array(':itdate' => $value["date"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $arrCloneJobsLC[] = $value1["clone_completed"];
        }
        $cloneJobsLC = array_sum(array_filter($arrCloneJobsLC));
    }
    if (numFilter($cloneJobsLC) === 0) {
        $db->updateSS("clone_jobs", $cloneJobsLC, $value["date"]);
    }
    unset($value1);


    # Update count of all jobs (erase jobs + clone jobs)
    $allJobsLC = $eraseJobsLC + $cloneJobsLC;
    $db->updateSS("all_jobs", $allJobsLC, $value["date"]);


    # Update date of last image update
    $db->Pselect("SELECT MAX(date) AS 'date' FROM jobstats WHERE clone_master = 1 AND date BETWEEN :startdate AND :itdate", array(':startdate' => $startDate, ':itdate' => $value["date"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->updateSS("last_image_update", $value1["date"], $value["date"]);
        }
    }
    unset($value1);


    # Get average TBW of non-HDD drives (writes only, SSD and NVMe included). This average takes into account drive model and specifications of the drive.
    $db->select("SELECT disk_model, disk_type, disk_tbw, disk_tbr, disk_mtbf FROM static_disk_stats");
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            if ($value1["disk_type"] === "hdd" && numFilter($value1["disk_tbw"]) === 0) {
                $sql = "SELECT (disk_writes + disk_reads) / :disktbw AS 'tbw' FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE date = :itdate AND disk_model = :diskmodel AND disk_writes IS NOT NULL AND disk_reads IS NOT NULL GROUP BY tagnumber)";
            } elseif (($value1["disk_type"] === "nvme" || $value1["disk_type"] === "ssd") && numFilter($value1["disk_tbw"]) === 0) {
                $sql = "SELECT disk_writes / :disktbw AS 'tbw' FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE date = :itdate AND disk_model = :diskmodel AND disk_writes IS NOT NULL AND disk_reads IS NOT NULL GROUP BY tagnumber)";
            } else {
                unset($sql);
            }

            if (isset($sql) === TRUE && numFilter($value1["disk_tbw"]) === 0 && strFilter($value1["disk_model"]) === 0) {
                $db->Pselect($sql, array(':disktbw' => $value1["disk_tbw"], ':itdate' => $value["date"], ':diskmodel' => $value1["disk_model"]));
                if (arrFilter($db->get()) === 0) {
                    foreach ($db->get() as $key => $value2) {
                        if (numFilter($value2["tbw"]) === 0) {
                            $tbw[] = $value2["tbw"];
                        }
                    }
                }
            }
            unset($value2);

            if (numFilter($value1["disk_mtbf"]) === 0) {
                $db->Pselect("SELECT disk_power_on_hours / :diskmtbf AS 'mtbf' FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE date = :itdate AND disk_model = :diskmodel AND disk_power_on_hours IS NOT NULL GROUP BY tagnumber)", array(':diskmtbf' => $value1["disk_mtbf"], ':itdate' => $value["date"], ':diskmodel' => $value1["disk_model"]));
                if (arrFilter($db->get()) === 0) {
                    foreach ($db->get() as $key => $value2) {
                        if (numFilter($value2["mtbf"]) === 0) {
                            $mtbf[] = $value2["mtbf"];
                        }
                    }
                }
            }
            unset($value2);

            if (isset($tbw) === TRUE || isset($mtbf) === TRUE) {
                if (isset($tbw) === TRUE && isset($mtbf) === TRUE && count($tbw) > 0 && count($mtbf) > 0) {
                    $diskHealth = 100 - round((((array_sum($tbw) / count($tbw)) + (array_sum($mtbf) / count($mtbf))) / 2), 2);
                } elseif (isset($tbw) === TRUE && isset($mtbf) === FALSE && count($tbw) > 0) {
                    $diskHealth = 100 - round(array_sum($tbw) / $count($tbw), 2);
                } elseif (isset($tbw) === FALSE && isset($mtbf) === TRUE && count($mtbf) > 0) {
                    $diskHealth = 100 - round(array_sum($mtbf) / count($mtbf), 2);
                }

                if (isset($diskHealth) === TRUE) {
                    $db->updateSS("disk_health", $diskHealth, $value["date"]);
                } else {
                    $db->updateSS("disk_health", 0, $value["date"]);
                }
            } else {
                $db->updateSS("disk_health", 0, $value["date"]);
            }
        }
    }
    unset($sql);
    unset($diskHealth);
    unset($value1);
    unset($value2);


    #Update the average battery health by selecting the most recent distinct tagnumber
    $db->Pselect("SELECT battery_health FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE date = :itdate AND battery_health IS NOT NULL GROUP BY tagnumber)", array(':itdate' => $value["date"]));
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            if (numFilter($value1["battery_health"]) === 0) {
                $arrBatteryCharge[] = $value1["battery_health"];
            }
        }
    }
    unset($value1);

    # Get battery charge cycle stats
    $db->select("SELECT battery_model, battery_charge_cycles FROM static_battery_stats");
    if (arrFilter($db->get()) === 0) {
        foreach ($db->get() as $key => $value1) {
            $db->Pselect("SELECT battery_charge_cycles FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE date = :itdate AND battery_model = :batterymodel GROUP BY tagnumber)", array(':itdate' => $value["date"], ':batterymodel' => $value1["battery_model"]));
            if (arrFilter($db->get()) === 0) {
                foreach ($db->get() as $key => $value2) {
                    if (numFilter($value2["battery_charge_cycles"]) === 0) {
                        $arrBatteryCycles[] = ($value2["battery_charge_cycles"] / $value1["battery_charge_cycles"]) * 100;
                    }
                }
            }
        }
    }
    unset($value1);
    unset($value2);

    $batteryCharge = array_sum(array_filter($arrBatteryCharge));
    $batteryChargeCycles = array_sum(array_filter($arrBatteryCycles));

    if (arrFilter($arrBatteryCharge) === 0 && arrFilter($arrBatteryCycles) === 0) {
        $batteryHealth = round((($batteryCharge / count($arrBatteryCharge)) + (100 - ($batteryChargeCycles / count($arrBatteryCycles)))) / 2, 2);
    } elseif (arrFilter($arrBatteryCharge) === 0 && arrFilter($arrBatteryCycles) === 1) {
        $batteryHealth = round(($batteryCharge / count($arrBatteryCharge)), 2);
    } elseif (arrFilter($arrBatteryCharge) === 1 && arrFilter($arrBatteryCycles) === 0) {
        $batteryHealth = round(100 - ($batteryChargeCycles / count($arrBatteryCycles)), 2);
    } else {
        $batteryHealth = 0;
    }
    $db->updateSS("battery_health", $batteryHealth, $value["date"]);
