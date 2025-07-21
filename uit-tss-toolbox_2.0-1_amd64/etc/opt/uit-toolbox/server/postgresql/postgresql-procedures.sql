-- -- Iterate through dates
-- CREATE OR REPLACE PROCEDURE iterateDate(date1 DATE, date2 DATE)
-- LANGUAGE plpgsql
-- BEGIN ATOMIC
--   CREATE TEMPORARY TABLE tbl_results (date DATE);
--   WHILE date1 < date2 LOOP
--   INSERT INTO tbl_results (date) VALUES(date1);
--   SET date1 = ADDDATE(date1, INTERVAL 1 DAY);
--   ITERATE label1;
--   END LOOP;

--   INSERT INTO tbl_results (date) VALUES(date2);

--   SELECT date FROM tbl_results;

-- END;


-- -- Job table CSV
-- CREATE OR REPLACE PROCEDURE iterateJobCSV()
-- LANGUAGE SQL
-- BEGIN ATOMIC
-- (SELECT 
--   'UUID', 'Tag', 'System Serial', 'Datetime', 'Ethernet MAC', 'Wi-Fi MAC', 'System Manufacturer', 'Model', 'System UUID',
--   'System SKU', 'Chassis Type', 'Disk', 'Disk Model', 'Disk Type', 'Disk Size', 'Disk Serial', 'Disk Writes', 'Disk Reads',
--   'Disk Power on Hours', 'Disk Temp', 'Disk Firmware', 'Battery Model', 'Battery Serial', 'Battery Health', 'Battery Charge Cycles',
--   'Battery Capacity', 'Battery Manufacture Date', 'CPU Manufacturer', 'CPU Model', 'CPU Max Speed', 'CPU Cores', 'CPU Threads', 'CPU Temp',
--   'Motherboard Manufacturer', 'Motherboard Serial', 'BIOS Version', 'BIOS Date', 'BIOS Firmware', 'RAM Serial', 'RAM Capacity', 'RAM Speed', 'CPU Usage',
--   'Network Usage', 'Boot Time', 'Erase Completed', 'Erase Mode', 'Erase Disk Percent', 'Erase Time',
--   'Clone Completed', 'Clone Master', 'Clone Time', 'Connected to Host'
-- )
-- UNION
-- (SELECT
--   jobstats.uuid, CAST(jobstats.tagnumber AS VARCHAR), jobstats.system_serial, CAST(jobstats.time AS TIMESTAMP), jobstats.etheraddress, system_data.wifi_mac, system_data.system_manufacturer, system_data.system_model, system_data.system_uuid, 
--   system_data.system_sku, system_data.chassis_type, jobstats.disk, jobstats.disk_model, jobstats.disk_type, CONCAT(jobstats.disk_size, ' GB'), jobstats.disk_serial, CONCAT(jobstats.disk_writes, ' TB'), CONCAT(jobstats.disk_reads, ' TB'), 
--   CONCAT(jobstats.disk_power_on_hours, 'hrs'), CONCAT(jobstats.disk_temp, ' C'), jobstats.disk_firmware, jobstats.battery_model, jobstats.battery_serial, CONCAT(jobstats.battery_health, '%'), jobstats.battery_charge_cycles, 
--   CONCAT(jobstats.battery_capacity, ' Wh'), jobstats.battery_manufacturedate, system_data.cpu_manufacturer, system_data.cpu_model, CONCAT(round(system_data.cpu_maxspeed / 1000, 2), ' Ghz'), system_data.cpu_cores, system_data.cpu_threads, CONCAT(jobstats.cpu_temp, ' C'), 
--   system_data.motherboard_manufacturer, system_data.motherboard_serial, jobstats.bios_version, jobstats.bios_date, jobstats.bios_firmware, jobstats.ram_serial, CONCAT(jobstats.ram_capacity, ' GB') ,CONCAT(jobstats.ram_speed, 'Mhz'), CONCAT(jobstats.cpu_usage, '%'), 
--   CONCAT(jobstats.network_usage, 'mbps'), CONCAT(jobstats.boot_time, 's'), REPLACE(CAST(jobstats.erase_completed AS VARCHAR), CAST('1' AS VARCHAR), CAST('Yes' AS VARCHAR)), jobstats.erase_mode, CONCAT(jobstats.erase_diskpercent, '%'), CONCAT(jobstats.erase_time, 's'), 
--   REPLACE(jobstats.clone_completed, '1', 'Yes'), REPLACE(jobstats.clone_master, '1', 'Yes'), CONCAT(jobstats.clone_time, 's'), (CASE WHEN jobstats.host_connected='1', 'Yes', '')
-- FROM jobstats
-- LEFT JOIN system_data ON jobstats.tagnumber = system_data.tagnumber
-- ORDER BY jobstats.time DESC);
-- END;


-- -- Client table CSV
-- CREATE OR REPLACE PROCEDURE iterateClientCSV()
-- LANGUAGE SQL
-- BEGIN ATOMIC
-- (SELECT 
--   'Tag',
--   'Serial Number',
--   'System Model',
--   'Last Job Time',
--   'Battery Health',
--   'Disk Health',
--   'Disk Type',
--   'BIOS Updated',
--   'Avg. Erase Time',
--   'Avg. Clone Time',
--   'Total Jobs'
-- )
-- UNION
-- (SELECT 
--   CAST(clientstats.tagnumber AS VARCHAR),
--   clientstats.system_serial,
--   clientstats.system_model,
--   clientstats.last_job_time,
--   CONCAT(clientstats.battery_health, '%'),
--   CONCAT(clientstats.disk_health, '%'),
--   clientstats.disk_type,
--   (CASE WHEN client_health.bios_updated = 1, "Yes", "No"),
--   CONCAT(clientstats.erase_avgtime, ' minutes'),
--   CONCAT(clientstats.clone_avgtime, ' minutes'),
--   all_jobs
-- FROM clientstats 
-- LEFT JOIN client_health ON clientstats.tagnumber = client_health.tagnumber
-- WHERE clientstats.tagnumber IS NOT NULL 
-- ORDER BY clientstats.last_job_time DESC);
-- END;


-- -- Server table CSV
-- CREATE OR REPLACE PROCEDURE iterateServerCSV()
-- LANGUAGE SQL
-- BEGIN ATOMIC

-- (SELECT 
--   'Date',
--   'Client Count',
--   'Battery Health',
--   'Disk Health',
--   'Total Jobs',
--   'Clone Jobs',
--   'Erase Jobs',
--   'Avg. Clone Time',
--   'Avg. Erase Time',
--   'Last Image Update'
-- )
-- UNION
-- (SELECT
--   date,
--   client_count,
--   CONCAT(battery_health, '%'),
--   CONCAT(disk_health, '%'),
--   total_job_count,
--   clone_job_count,
--   erase_job_count,
--   CONCAT(avg_clone_time, ' mins'),
--   CONCAT(avg_erase_time, ' mins'),
--   last_image_update
-- FROM serverstats
-- ORDER BY date DESC);

-- END;


-- -- Location table CSV
-- CREATE OR REPLACE PROCEDURE iterateLocationsCSV()
-- LANGUAGE SQL
-- BEGIN ATOMIC
-- (SELECT 
--   'Tag',
--   'Serial Number',
--   'System Model',
--   'Department',
--   'Location',
--   'Status',
--   'OS Insalled',
--   'BIOS Updated',
--   'Notes',
--   'Most Recent Entry')
-- UNION
-- (SELECT
--   CAST(locations.tagnumber AS VARCHAR),
--   locations.system_serial,
--   system_data.system_model,
--   locations.department,
--   locations.location,
--   (CASE WHEN locations.status = 0 OR status IS NULL, "Functional", "Broken"),
--   (CASE WHEN client_health.os_installed = 1 , "Yes", "No"),
--   (CASE WHEN client_health.bios_updated = 1 , "Yes", "No"),
--   locations.note,
--   CAST(locations.time AS TIMESTAMP) 
-- FROM locations 
-- LEFT JOIN client_health ON locations.tagnumber = client_health.tagnumber
-- LEFT JOIN system_data ON locations.tagnumber = system_data.tagnumber
-- INNER JOIN (SELECT MAX(time) AS 'time' FROM locations GROUP BY tagnumber) t2 ON locations.time = t2.time
-- ORDER BY locations.time DESC);
-- END;


-- -- Location table for clients sent to property
-- CREATE OR REPLACE PROCEDURE iterateLocationsPropertyCSV()
-- LANGUAGE SQL
-- BEGIN ATOMIC

-- (SELECT
--   'Tag',
--   'Serial Number',
--   'Location',
--   'Disk Removed',
--   'Notes',
--   'Most Recent Entry')
-- UNION
-- (SELECT
--   CAST(locations.tagnumber AS VARCHAR),
--   locations.system_serial,
--   locations.location,
--   (CASE WHEN locations.disk_removed='1', "Yes", "No"),
--   locations.note,
--   CAST(locations.time AS TIMESTAMP) 
-- FROM locations 
-- INNER JOIN (SELECT MAX(time) AS 'time' FROM locations GROUP BY tagnumber) t1 ON locations.time = t1.time
-- WHERE locations.department = 'property'
-- AND locations.tagnumber IS NOT NULL
-- ORDER BY locations.time DESC
-- );
-- END;


-- SQL permissions and user creation
CREATE OR REPLACE PROCEDURE sqlPermissions()
LANGUAGE SQL
AS $$

CREATE USER cameron WITH SUPERUSER CREATEDB CREATEROLE PASSWORD 'UHouston!';
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO cameron WITH GRANT OPTION;
GRANT EXECUTE ON ALL PROCEDURES IN SCHEMA public TO cameron;
GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA public TO cameron;

CREATE USER uitclient PASSWORD 'UHouston!';
GRANT SELECT, INSERT, UPDATE ON ALL TABLES IN SCHEMA public TO uitclient;
GRANT EXECUTE ON ALL PROCEDURES IN SCHEMA public TO uitclient;
GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA public TO uitclient;

CREATE USER uitweb PASSWORD 'UHouston!';
GRANT SELECT, INSERT, UPDATE ON ALL TABLES IN SCHEMA public TO uitweb;
GRANT DELETE ON client_images TO uitweb;
GRANT EXECUTE ON ALL PROCEDURES IN SCHEMA public TO uitweb;
GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA public TO uitweb;

$$;



-- -- Select info about a tag
-- CREATE OR REPLACE PROCEDURE selectTag(tag VARCHAR(8))
-- LANGUAGE SQL
-- BEGIN ATOMIC
-- SELECT t1.time,
--     t1.tagnumber,
--     t1.system_serial,
--     t1.bios_version,
--     t2.system_model,
--     t1.cpu_usage,
--     t1.network_usage,
--     t1.battery_health,
--     t1.disk_power_on_hours,
--     t1.ram_serial,
--     t1.host_connected
--     FROM jobstats t1 INNER JOIN system_data t2 ON t1.tagnumber = t2.tagnumber WHERE t1.tagnumber = tag ORDER BY t1.time DESC LIMIT 5;

-- SELECT * FROM locations WHERE tagnumber = tag ORDER BY time DESC LIMIT 7;
-- END;



-- -- Select DISK info about a tag
-- CREATE OR REPLACE PROCEDURE selectTagDisk(tag VARCHAR(8))
-- LANGUAGE SQL
-- BEGIN ATOMIC
-- SELECT time, 
--     disk_model, 
--     disk_serial, 
--     disk_type, 
--     disk_writes, 
--     disk_reads, 
--     disk_power_on_hours 
--     FROM jobstats WHERE tagnumber = tag ORDER BY time DESC LIMIT 20;
-- END;


-- -- Select job info about a tag
-- CREATE OR REPLACE PROCEDURE selectTagJob(tag VARCHAR(8))
-- LANGUAGE SQL
-- BEGIN ATOMIC
-- SELECT time, 
--     tagnumber, 
--     erase_completed, 
--     clone_completed, 
--     clone_master,
--     host_connected
--     FROM jobstats WHERE tagnumber = tag ORDER BY time DESC LIMIT 20;
--     END;


-- -- Select battery info about a tag
-- CREATE OR REPLACE PROCEDURE selectTagBattery(tag VARCHAR(8))
-- LANGUAGE SQL
-- BEGIN ATOMIC
-- SELECT time, 
--     tagnumber, 
--     battery_model, 
--     battery_serial, 
--     battery_capacity, 
--     battery_charge_cycles,
--     battery_health,
--     battery_manufacturedate 
--     FROM jobstats WHERE tagnumber = tag ORDER BY time DESC LIMIT 20;
--     END;


-- Select remote table data
CREATE OR REPLACE FUNCTION selectRemote()
RETURNS TABLE (
  "Tag" INTEGER,
  "Last Heard" TIMESTAMP,
  "Location" VARCHAR,
  "Last Job Time" TIMESTAMP,
  "Pending Job" VARCHAR,
  "Status" VARCHAR,
  "Kernel/BIOS Updated" VARCHAR,
  "OS Name" VARCHAR,
  "Battery Status" VARCHAR,
  

)
LANGUAGE plpgsql
$$
BEGIN ATOMIC
    RETURN QUERY SELECT remote.tagnumber , 
        TO_CHAR(remote.present, 'MM/DD/YY HH12:MI:SS AM'), locationFormatting(t3.location) AS "Location", 
        TO_CHAR(remote.last_job_time, 'MM/DD/YY HH12:MI:SS AM') AS "Last Job Time", 
        remote.job_queued AS "Pending Job", remote.status AS "Status",
        CONCAT((CASE WHEN remote.kernel_updated = TRUE THEN 'Yes' ELSE 'No' END), '/', ( CASE WHEN client_health.bios_updated = TRUE THEN 'Yes' ELSE 'No' END)) AS "Kernel/BIOS Updated", client_health.os_name AS "OS Name", 
        CONCAT(remote.battery_charge, '% (', remote.battery_status, ')') AS "Battery Status", 
        CONCAT(FLOOR(remote.uptime / 3600 / 24), 'd ' , FLOOR(MOD(remote.uptime, 3600 * 24) / 3600), 'h ' , FLOOR(MOD(remote.uptime, 3600) / 60), 'm ' , FLOOR(MOD(remote.uptime, 60)), 's') AS "Uptime", 
        CONCAT(remote.cpu_temp, '°C', '/' , remote.disk_temp, '°C', '/', remote.watts_now, ' Watts') AS "CPU Temp/Disk Temp/Watts"
      FROM remote 
      LEFT JOIN (SELECT s1.time, s1.tagnumber FROM (SELECT time, tagnumber, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS "row_nums" FROM locations) s1 WHERE s1.row_nums = 1) t1
        ON remote.tagnumber = t1.tagnumber
      LEFT JOIN client_health ON remote.tagnumber = client_health.tagnumber
      LEFT JOIN (SELECT tagnumber, location, row_nums FROM (SELECT tagnumber, location, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS "row_nums" FROM locations) s3 WHERE s3.row_nums = 1) t3
        ON t3.tagnumber = remote.tagnumber
      LEFT JOIN (SELECT tagnumber, queue_position FROM (SELECT tagnumber, ROW_NUMBER() OVER (ORDER BY tagnumber ASC) AS "queue_position" FROM remote WHERE job_queued IS NOT NULL) s2) t2
        ON remote.tagnumber = t2.tagnumber
      WHERE remote.present_bool = TRUE
      ORDER BY
        (CASE WHEN remote.status LIKE 'fail%' THEN 1 ELSE 0 END) DESC, job_queued IS NULL ASC, job_active DESC, queue_position ASC,
        (CASE WHEN job_queued = 'data collection' THEN 20 WHEN job_queued = 'update' THEN 15 WHEN job_queued = 'nvmeVerify' THEN 14 WHEN job_queued =  'nvmeErase' THEN 12 WHEN job_queued =  'hpCloneOnly' THEN 11 WHEN job_queued = 'hpEraseAndClone' THEN 10 WHEN job_queued = 'findmy' THEN 8 WHEN job_queued = 'shutdown' THEN 7 WHEN job_queued = 'fail-test' THEN 5 ELSE NULL END) DESC, 
        (CASE WHEN status = 'Waiting for job' THEN 1 ELSE 0 END) ASC, (CASE WHEN client_health.os_installed = TRUE THEN 1 ELSE 0 END) DESC, (CASE WHEN remote.kernel_updated = TRUE THEN 1 ELSE 0 END) DESC, (CASE WHEN client_health.bios_updated = TRUE THEN 1 ELSE 0 END) DESC, remote.last_job_time DESC;
    END
    $$;


-- Select missing remote table data
CREATE OR REPLACE PROCEDURE selectRemoteMissing()
LANGUAGE SQL
BEGIN ATOMIC
    SELECT remote.tagnumber AS "Tag", 
        TO_CHAR(remote.present, 'MM/DD/YY HH12:MI:SS AM') AS "Last Heard", locationFormatting(t3.location) AS "Location", 
        TO_CHAR(remote.last_job_time, 'MM/DD/YY HH12:MI:SS AM') AS "Last Job Time", 
        remote.job_queued AS "Pending Job", remote.status AS "Status",
        (CASE WHEN client_health.bios_updated = TRUE THEN 'Yes' ELSE 'No' END) AS "BIOS Updated", client_health.os_name AS "OS Name", 
        CONCAT(remote.battery_charge, '% (', remote.battery_status, ')') AS "Battery Status", 
        CONCAT(FLOOR(remote.uptime / 3600 / 24), 'd ' , FLOOR(MOD(remote.uptime, 3600 * 24) / 3600), 'h ' , FLOOR(MOD(remote.uptime, 3600) / 60), 'm ' , FLOOR(MOD(remote.uptime, 60)), 's') AS "Uptime", 
        CONCAT(remote.cpu_temp, '°C', '/' , remote.disk_temp, '°C', '/', remote.watts_now, ' Watts') AS "CPU Temp/Disk Temp/Watts"
      FROM remote 
      LEFT JOIN (SELECT s1.time, s1.tagnumber FROM (SELECT time, tagnumber, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS "row_nums" FROM locations) s1 WHERE s1.row_nums = 1) t1
        ON remote.tagnumber = t1.tagnumber
      LEFT JOIN client_health ON remote.tagnumber = client_health.tagnumber
      LEFT JOIN (SELECT tagnumber, location, row_nums FROM (SELECT tagnumber, location, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS "row_nums" FROM locations) s3 WHERE s3.row_nums = 1) t3
        ON t3.tagnumber = remote.tagnumber
      LEFT JOIN (SELECT tagnumber, queue_position FROM (SELECT tagnumber, ROW_NUMBER() OVER (ORDER BY tagnumber ASC) AS "queue_position" FROM remote WHERE job_queued IS NOT NULL) s2) t2
        ON remote.tagnumber = t2.tagnumber
      WHERE remote.present_bool = FALSE OR remote.present_bool IS NULL
      ORDER BY remote.present DESC
      LIMIT 10;
    END;


-- Select remote table stats
CREATE OR REPLACE PROCEDURE selectRemoteStats()
LANGUAGE SQL
BEGIN ATOMIC
SELECT 
    (SELECT COUNT(remote.tagnumber) FROM remote WHERE remote.present_bool = '1') AS "Present Laptops",
    CONCAT(ROUND(AVG(remote.battery_charge), 0), '%') AS "Avg. Battery Charge",
    CONCAT(ROUND(AVG(remote.cpu_temp), 1), '°C') AS "Avg. CPU Temp",
    CONCAT(ROUND(AVG(remote.disk_temp), 1), '°C') AS "Avg. Disk Temp",
    CONCAT(ROUND(AVG(remote.watts_now), 1), ' Watts') AS "Avg. Actual Power Draw",
    CONCAT(ROUND(SUM(remote.watts_now), 0), ' Watts') AS "Actual Power Draw",
    CONCAT(ROUND(SUM((CASE WHEN remote.battery_status NOT IN ('Discharging') AND remote.present_bool = TRUE THEN 55 ELSE 0 END)), 0), ' Cur. Watts', '/' , ROUND(SUM((CASE WHEN remote.present_bool = TRUE THEN 55 ELSE 0 END)), 0), ' Watts') AS "Power Draw from Wall",
    COUNT(client_health.os_installed) AS "OS Installed Sum"
    FROM remote 
    LEFT JOIN client_health ON remote.tagnumber = client_health.tagnumber 
    WHERE remote.present_bool = TRUE;
    END;


CREATE OR REPLACE PROCEDURE selectLocationAutocomplete()
LANGUAGE SQL
BEGIN ATOMIC
  SELECT MAX(t1.time) AS "time", t1.location, MAX(t1.row_nums) AS "row_nums" FROM (SELECT time, locationFormatting(REPLACE(REPLACE(REPLACE(location, '\\', '\\\\'), '''', '\\'''), '\"','\\"')) AS "location", ROW_NUMBER() OVER (PARTITION BY location ORDER BY time DESC) AS "row_nums" FROM locations WHERE time IN (SELECT MAX(time) FROM locations GROUP BY tagnumber)) t1 GROUP BY t1.location ORDER BY row_nums DESC;
END;

-- -- SHRL Report
-- CREATE OR REPLACE PROCEDURE iterateSHRLCSV()
-- LANGUAGE SQL
-- BEGIN ATOMIC

-- (SELECT 'Last Entry', 'Tag Number', 'Serial Number', 'System Model', 'Location', 'CPU Model', 'CPU Cores', 'RAM Capacity', 'Disk Size', 'Disk Type', 'Disk Health', 'Note')
-- UNION
-- (SELECT 
--   CAST(locations.time AS VARCHAR), CAST(jobstats.tagnumber AS VARCHAR), jobstats.system_serial, 
--   system_data.system_model,
--   locations.location, system_data.cpu_model, system_data.cpu_cores,
--   CONCAT(t1.ram_capacity, ' GB'), CONCAT(t1.disk_size, ' GB'), t1.disk_type,
--   CONCAT(clientstats.disk_health, '%'), locations.note
-- FROM jobstats 
-- LEFT JOIN locations ON jobstats.tagnumber = locations.tagnumber 
-- LEFT JOIN clientstats ON jobstats.tagnumber = clientstats.tagnumber
-- LEFT JOIN system_data ON jobstats.tagnumber = system_data.tagnumber
-- LEFT JOIN (SELECT tagnumber, ram_capacity, disk_type, disk_size FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE host_connected = TRUE AND tagnumber IS NOT NULL GROUP BY tagnumber)) t1
--   ON jobstats.tagnumber = t1.tagnumber
-- INNER JOIN (SELECT MAX(time) AS "time" FROM jobstats WHERE tagnumber IS NOT NULL AND system_serial IS NOT NULL GROUP BY tagnumber) t2
--   ON jobstats.time = t2.time
-- INNER JOIN (SELECT MAX(time) AS "time" FROM locations WHERE tagnumber IS NOT NULL AND system_serial IS NOT NULL GROUP BY tagnumber) t3
--   ON locations.time = t3.time
-- WHERE locations.department = 'shrl'
-- ORDER BY locations.location ASC, jobstats.tagnumber ASC, locations.time ASC);

-- END;



CREATE OR REPLACE FUNCTION locationFormatting(location VARCHAR(128)) 
RETURNS VARCHAR(128) AS $$
BEGIN
  RETURN CASE
    WHEN REGEXP_MATCH(location, '^.{1}$') IS NOT NULL THEN UPPER(location)
    WHEN REGEXP_MATCH(location, '^(checkout|check-out|check out)$') IS NOT NULL THEN 'Check Out'
    WHEN REGEXP_MATCH(location, '^(cam desk|cams desk|cam''s desk)$') IS NOT NULL THEN 'Cam''s Desk'
    WHEN REGEXP_MATCH(location, '^(matthew desk|matthews desk|matthew''s desk)$') IS NOT NULL THEN 'Matthew''s Desk'
    ELSE location
  END;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE PROCEDURE longestCloneTimes()
LANGUAGE SQL
BEGIN ATOMIC

SELECT clientstats.tagnumber, clientstats.clone_avgtime, locationFormatting(locations.location) AS "location" 
FROM locations 
LEFT JOIN clientstats ON locations.tagnumber = clientstats.tagnumber 
INNER JOIN (SELECT MAX(time) AS "time" FROM locations GROUP BY tagnumber) t1 
  on locations.time = t1.time 
WHERE location IN ('c', 'a', 'b', 'q', 'f', 'On top of Z') 
  AND clientstats.clone_avgtime IS NOT NULL ORDER BY location, clone_avgtime;

END;


-- CREATE OR REPLACE PROCEDURE longestEraseTimes()
-- LANGUAGE SQL
-- BEGIN ATOMIC

-- SELECT clientstats.tagnumber, clientstats.erase_avgtime, locationFormatting(locations.location) AS "location" 
-- FROM locations 
-- LEFT JOIN clientstats ON locations.tagnumber = clientstats.tagnumber 
-- INNER JOIN (SELECT MAX(time) AS "time" FROM locations GROUP BY tagnumber) t1 
--   on locations.time = t1.time 
-- WHERE location IN ('c', 'a', 'b', 'q', 'f', 'On top of Z') 
--   AND clientstats.erase_avgtime IS NOT NULL ORDER BY location, erase_avgtime;

-- END;


-- CREATE OR REPLACE PROCEDURE iterateCustomReport()
-- LANGUAGE SQL
-- BEGIN ATOMIC

-- (SELECT 'Tag Number', 'Serial Number', 'System Model', 'AD Domain', 'Location', 'Note', 'Last Update')
-- UNION ALL
-- (
--   SELECT CAST(locations.tagnumber AS VARCHAR), locations.system_serial, system_data.system_model, static_domains.domain_readable, 
--     locationFormatting(locations.location) AS "location", locations.note, CAST(jobstats.tagnumber AS VARCHAR)
--   FROM locations
--   LEFT JOIN system_data ON locations.tagnumber = system_data.tagnumber
--   LEFT JOIN client_health ON locations.tagnumber = client_health.tagnumber
--   LEFT JOIN static_domains ON locations.domain = static_domains.domain
--   WHERE locations.time IN (SELECT MAX(time) FROM locations GROUP BY tagnumber)
--     AND client_health.os_installed = TRUE
--     AND locations.department = 'techComm'
-- );

-- END;


-- CREATE OR REPLACE PROCEDURE iteratePreProperty()
-- LANGUAGE SQL
-- BEGIN ATOMIC

-- (SELECT 'Tag Number', 'Serial Number', 'System Model', 'Disk Removed', 'Location', 'Note', 'Last Update')
-- UNION ALL
-- (SELECT (CASE WHEN locations.tagnumber LIKE '77204%' OR locations.tagnumber LIKE '999%' THEN 'NO TAG' ELSE CAST(locations.tagnumber AS VARCHAR) END) AS "tagnumber", locations.system_serial, system_data.system_model, 
-- 	(CASE WHEN locations.disk_removed = 1 THEN 'Yes' ELSE 'No' END) AS "disk_removed", (CASE WHEN locationFormatting(locations.location) = 'On top of Z' THEN 'Z' ELSE locationFormatting(locations.location END)) AS "location", locations.note, locations.time
-- FROM locations
-- LEFT JOIN system_data ON locations.tagnumber = system_data.tagnumber
-- INNER JOIN (SELECT MAX(time) AS "time" FROM locations GROUP BY tagnumber) t1 ON locations.time = t1.time
-- WHERE locations.department IN ('pre-property')
-- ORDER BY location, tagnumber);

-- END;


-- CREATE OR REPLACE PROCEDURE iterateCheckoutName()
-- LANGUAGE SQL
-- BEGIN ATOMIC

-- (SELECT 'Tag Number', 'Serial Number', 'System Model', 'Customer Name', 'Checkout Date', 'Return Date', 'Note', 'Last Entry')
-- UNION ALL
-- (SELECT CAST(t1.tagnumber AS VARCHAR), t2.system_serial, system_data.system_model, t1.customer_name, t1.checkout_date, t1.return_date, t1.note, t1.time FROM (SELECT checkouts.time, checkouts.tagnumber, checkouts.customer_name, checkouts.customer_psid, checkouts.checkout_date, checkouts.return_date, checkouts.checkout_bool, checkouts.note,
--     ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS "row_nums" 
--     FROM checkouts) t1
--     INNER JOIN (SELECT s2.tagnumber, s2.time, s2.system_serial, s2.row_nums FROM (SELECT tagnumber, time, system_serial, ROW_NUMBER() OVER (PARTITION BY tagnumber ORDER BY time DESC) AS "row_nums" FROM locations) s2 WHERE s2.row_nums = 1) t2 ON t1.tagnumber = t2.tagnumber
--     LEFT JOIN system_data ON t1.tagnumber = system_data.tagnumber
--     WHERE (t1.checkout_date IS NOT NULL OR t1.return_date IS NOT NULL)
--         AND t1.row_nums <= 1 AND NOT t1.row_nums IS NULL AND t1.checkout_bool = TRUE
--     ORDER BY t1.customer_name ASC, t1.tagnumber DESC);

-- END;



-- CREATE OR REPLACE PROCEDURE selectNullTagnumbers()
-- LANGUAGE SQL
-- BEGIN ATOMIC

-- select etheraddress, count(etheraddress) AS "count" from jobstats where tagnumber is null group by etheraddress order by count asc;

-- END;