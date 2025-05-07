-- Iterate through dates
DROP PROCEDURE IF EXISTS iterateDate;
DELIMITER //
CREATE PROCEDURE iterateDate(date1 DATE, date2 DATE)
DETERMINISTIC
BEGIN
DROP TEMPORARY TABLE IF EXISTS tbl_results;
CREATE TEMPORARY TABLE IF NOT EXISTS tbl_results (date DATE);

label1: LOOP
    IF date1 < date2 THEN
        INSERT INTO tbl_results (date) VALUES(date1);
        SET date1 = ADDDATE(date1, INTERVAL 1 DAY);
        ITERATE label1;
    END IF;
    LEAVE label1;
END LOOP label1;

INSERT INTO tbl_results (date) VALUES(date2);

SELECT date FROM tbl_results;

END; //

DELIMITER ;


-- One week ago
DROP FUNCTION IF EXISTS oneWeekAgo;
DELIMITER //
CREATE FUNCTION oneWeekAgo(date1 DATE)
    RETURNS DATE
    DETERMINISTIC
    BEGIN
        DECLARE t DATE;
        SET t = (SELECT DATE_SUB(date1, INTERVAL 1 WEEK));
        RETURN t;
    END //

DELIMITER ;


-- One week future
DROP FUNCTION IF EXISTS oneWeekAhead;
DELIMITER //
CREATE FUNCTION oneWeekAhead(date1 DATE)
    RETURNS DATE
    DETERMINISTIC
    BEGIN
        DECLARE t DATE;
        SET t = (SELECT ADDDATE(date1, INTERVAL 1 WEEK));
        RETURN t;
    END //

DELIMITER ;


-- Job table CSV
DROP PROCEDURE IF EXISTS iterateJobCSV;
DELIMITER //
CREATE PROCEDURE iterateJobCSV()
DETERMINISTIC
BEGIN
(SELECT 
  'UUID', 'Tag', 'System Serial', 'Datetime', 'Ethernet MAC', 'Wi-Fi MAC', 'System Manufacturer', 'Model', 'System UUID',
  'System SKU', 'Chassis Type', 'Disk', 'Disk Model', 'Disk Type', 'Disk Size', 'Disk Serial', 'Disk Writes', 'Disk Reads',
  'Disk Power on Hours', 'Disk Temp', 'Disk Firmware', 'Battery Model', 'Battery Serial', 'Battery Health', 'Battery Charge Cycles',
  'Battery Capacity', 'Battery Manufacture Date', 'CPU Manufacturer', 'CPU Model', 'CPU Max Speed', 'CPU Cores', 'CPU Threads', 'CPU Temp',
  'Motherboard Manufacturer', 'Motherboard Serial', 'BIOS Version', 'BIOS Date', 'BIOS Firmware', 'RAM Serial', 'RAM Capacity', 'RAM Speed', 'CPU Usage',
  'Network Usage', 'Boot Time', 'Erase Completed', 'Erase Mode', 'Erase Disk Percent', 'Erase Time',
  'Clone Completed', 'Clone Master', 'Clone Time', 'Connected to Host'
)
UNION
(SELECT
  jobstats.uuid, jobstats.tagnumber, jobstats.system_serial, CONVERT(jobstats.time, DATETIME), jobstats.etheraddress, system_data.wifi_mac, system_data.system_manufacturer, system_data.system_model, system_data.system_uuid, 
  system_data.system_sku, system_data.chassis_type, jobstats.disk, jobstats.disk_model, jobstats.disk_type, CONCAT(jobstats.disk_size, ' GB'), jobstats.disk_serial, CONCAT(jobstats.disk_writes, ' TB'), CONCAT(jobstats.disk_reads, ' TB'), 
  CONCAT(jobstats.disk_power_on_hours, 'hrs'), CONCAT(jobstats.disk_temp, ' C'), jobstats.disk_firmware, jobstats.battery_model, jobstats.battery_serial, CONCAT(jobstats.battery_health, '%'), jobstats.battery_charge_cycles, 
  CONCAT(jobstats.battery_capacity, ' Wh'), jobstats.battery_manufacturedate, system_data.cpu_manufacturer, system_data.cpu_model, CONCAT(round(system_data.cpu_maxspeed / 1000, 2), ' Ghz'), system_data.cpu_cores, system_data.cpu_threads, CONCAT(jobstats.cpu_temp, ' C'), 
  system_data.motherboard_manufacturer, system_data.motherboard_serial, jobstats.bios_version, jobstats.bios_date, jobstats.bios_firmware, jobstats.ram_serial, CONCAT(jobstats.ram_capacity, ' GB') ,CONCAT(jobstats.ram_speed, 'Mhz'), CONCAT(jobstats.cpu_usage, '%'), 
  CONCAT(jobstats.network_usage, 'mbps'), CONCAT(jobstats.boot_time, 's'), REPLACE(jobstats.erase_completed, '1', 'Yes'), jobstats.erase_mode, CONCAT(jobstats.erase_diskpercent, '%'), CONCAT(jobstats.erase_time, 's'), 
  REPLACE(jobstats.clone_completed, '1', 'Yes'), REPLACE(jobstats.clone_master, '1', 'Yes'), CONCAT(jobstats.clone_time, 's'), IF (jobstats.host_connected='1', 'Yes', '')
FROM jobstats
LEFT JOIN system_data ON jobstats.tagnumber = system_data.tagnumber
LEFT JOIN departments ON jobstats.time = departments.time
ORDER BY jobstats.time DESC);
END; //
DELIMITER ;


-- Client table CSV
DROP PROCEDURE IF EXISTS iterateClientCSV;
DELIMITER //
CREATE PROCEDURE iterateClientCSV()
DETERMINISTIC
BEGIN
(SELECT 
  'Tag',
  'Serial Number',
  'System Model',
  'Last Job Time',
  'Battery Health',
  'Disk Health',
  'Disk Type',
  'BIOS Updated',
  'Avg. Erase Time',
  'Avg. Clone Time',
  'Total Jobs'
)
UNION
(SELECT 
  clientstats.tagnumber,
  clientstats.system_serial,
  clientstats.system_model,
  clientstats.last_job_time,
  CONCAT(clientstats.battery_health, '%'),
  CONCAT(clientstats.disk_health, '%'),
  clientstats.disk_type,
  IF (bios_stats.bios_updated = 1, "Yes", "No"),
  CONCAT(clientstats.erase_avgtime, ' minutes'),
  CONCAT(clientstats.clone_avgtime, ' minutes'),
  all_jobs
FROM clientstats 
LEFT JOIN bios_stats ON clientstats.tagnumber = bios_stats.tagnumber
WHERE clientstats.tagnumber IS NOT NULL 
ORDER BY clientstats.last_job_time DESC);
END; //
DELIMITER ;


-- Server table CSV
DROP PROCEDURE IF EXISTS iterateServerCSV;
DELIMITER //
CREATE PROCEDURE iterateServerCSV()
DETERMINISTIC
BEGIN

(SELECT 
  'Date',
  'Client Count',
  'Battery Health',
  'Disk Health',
  'Total Jobs',
  'Clone Jobs',
  'Erase Jobs',
  'Avg. Clone Time',
  'Avg. Erase Time',
  'Last Image Update'
)
UNION
(SELECT
  date,
  client_count,
  CONCAT(battery_health, '%'),
  CONCAT(disk_health, '%'),
  total_job_count,
  clone_job_count,
  erase_job_count,
  CONCAT(avg_clone_time, ' mins'),
  CONCAT(avg_erase_time, ' mins'),
  last_image_update
FROM serverstats
ORDER BY date DESC);

END; //
DELIMITER ;


-- Location table CSV
DROP PROCEDURE IF EXISTS iterateLocationsCSV;
DELIMITER //
CREATE PROCEDURE iterateLocationsCSV()
DETERMINISTIC
BEGIN
(SELECT 
  'Tag',
  'Serial Number',
  'System Model',
  'Department',
  'Location',
  'Status',
  'OS Insalled',
  'BIOS Updated',
  'Notes',
  'Most Recent Entry')
UNION
(SELECT
  locations.tagnumber,
  locations.system_serial,
  system_data.system_model,
  departments.department,
  locations.location,
  IF (locations.status = 0 OR status IS NULL, "Functional", "Broken"),
  IF (os_stats.os_installed = 1 , "Yes", "No"),
  IF (bios_stats.bios_updated = 1 , "Yes", "No"),
  locations.note,
  CONVERT(locations.time, DATETIME) 
FROM locations 
LEFT JOIN bios_stats ON locations.tagnumber = bios_stats.tagnumber
LEFT JOIN system_data ON locations.tagnumber = system_data.tagnumber
LEFT JOIN departments ON locations.tagnumber = departments.tagnumber
LEFT JOIN os_stats ON locations.tagnumber = os_stats.tagnumber
INNER JOIN (SELECT MAX(time) AS 'time' FROM departments GROUP BY tagnumber) t1 ON departments.time = t1.time
INNER JOIN (SELECT MAX(time) AS 'time' FROM locations GROUP BY tagnumber) t2 ON locations.time = t2.time
ORDER BY locations.time DESC);
END; //
DELIMITER ;


-- Location table for clients sent to property
DROP PROCEDURE IF EXISTS iterateLocationsPropertyCSV;
DELIMITER //
CREATE PROCEDURE iterateLocationsPropertyCSV()
DETERMINISTIC
BEGIN

(SELECT
  'Tag',
  'Serial Number',
  'Location',
  'Disk Removed',
  'Notes',
  'Most Recent Entry')
UNION
(SELECT
  locations.tagnumber,
  locations.system_serial,
  locations.location,
  IF (locations.disk_removed='1', "Yes", "No"),
  locations.note,
  CONVERT(locations.time, DATETIME) 
FROM locations 
INNER JOIN departments ON locations.tagnumber = departments.tagnumber
INNER JOIN static_departments ON departments.department = static_departments.department
INNER JOIN (SELECT MAX(time) AS 'time' FROM locations GROUP BY tagnumber) t1 ON locations.time = t1.time
WHERE departments.department = 'property'
AND locations.tagnumber IS NOT NULL
ORDER BY locations.time DESC
);
END; //
DELIMITER ;


-- SQL permissions and user creation
DROP PROCEDURE IF EXISTS sqlPermissions;
DELIMITER //
CREATE PROCEDURE sqlPermissions()
DETERMINISTIC
BEGIN

DROP USER IF EXISTS 'cameron'@'10.0.0.0/255.0.0.0';
CREATE USER IF NOT EXISTS 'cameron'@'localhost' IDENTIFIED BY 'UHouston!';
GRANT ALL ON *.* TO 'cameron'@'localhost' WITH GRANT OPTION;

CREATE USER IF NOT EXISTS 'laptops'@'10.0.0.0/255.0.0.0' IDENTIFIED BY 'UHouston!';
GRANT INSERT, SELECT, UPDATE, EXECUTE ON laptopDB.* TO 'laptops'@'10.0.0.0/255.0.0.0';

CREATE USER IF NOT EXISTS 'shrl'@'10.0.0.0/255.0.0.0' IDENTIFIED BY 'UHouston!';
GRANT INSERT, SELECT, UPDATE, EXECUTE ON shrl.* TO 'shrl'@'10.0.0.0/255.0.0.0';

CREATE USER IF NOT EXISTS 'management'@'localhost' IDENTIFIED BY 'UHouston!';
GRANT SELECT, UPDATE ON laptopDB.remote TO 'management'@'localhost';
GRANT SELECT, EXECUTE ON laptopDB.* TO 'management'@'localhost';

END; //

DELIMITER ;


-- Select info about a tag
DROP PROCEDURE IF EXISTS selectTag;
DELIMITER //
CREATE PROCEDURE selectTag(tag VARCHAR(8))
DETERMINISTIC
BEGIN
SELECT t1.time,
    t1.tagnumber,
    t1.system_serial,
    t1.bios_version,
    t2.system_model,
    t1.cpu_usage,
    t1.network_usage,
    t1.battery_health,
    t1.disk_power_on_hours,
    t1.ram_serial,
    t1.host_connected
    FROM jobstats t1 INNER JOIN system_data t2 ON t1.tagnumber = t2.tagnumber WHERE t1.tagnumber = tag ORDER BY t1.time DESC LIMIT 5;

SELECT * FROM locations WHERE tagnumber = tag ORDER BY time DESC LIMIT 7;
END; //

DELIMITER ;


-- Select DISK info about a tag
DROP PROCEDURE IF EXISTS selectTagDisk;
DELIMITER //
CREATE PROCEDURE selectTagDisk(tag VARCHAR(8))
DETERMINISTIC
BEGIN
SELECT time, 
    disk_model, 
    disk_serial, 
    disk_type, 
    disk_writes, 
    disk_reads, 
    disk_power_on_hours 
    FROM jobstats WHERE tagnumber = tag ORDER BY time DESC LIMIT 20;
END; //

DELIMITER ;

-- Select job info about a tag
DROP PROCEDURE IF EXISTS selectTagJob;
DELIMITER //
CREATE PROCEDURE selectTagJob(tag VARCHAR(8))
DETERMINISTIC
BEGIN
SELECT time, 
    tagnumber, 
    erase_completed, 
    clone_completed, 
    clone_master,
    host_connected
    FROM jobstats WHERE tagnumber = tag ORDER BY time DESC LIMIT 20;
    END; //

DELIMITER ;

-- Select battery info about a tag
DROP PROCEDURE IF EXISTS selectTagBattery;
DELIMITER //
CREATE PROCEDURE selectTagBattery(tag VARCHAR(8))
DETERMINISTIC
BEGIN
SELECT time, 
    tagnumber, 
    battery_model, 
    battery_serial, 
    battery_capacity, 
    battery_charge_cycles,
    battery_health,
    battery_manufacturedate 
    FROM jobstats WHERE tagnumber = tag ORDER BY time DESC LIMIT 20;
    END; //

DELIMITER ;

-- Select most recent job time
DROP FUNCTION IF EXISTS mostRecentTime;
DELIMITER //
CREATE FUNCTION mostRecentTime(tag MEDIUMINT)
    RETURNS DATETIME(3)
    DETERMINISTIC
    BEGIN
        DECLARE t DATETIME(3);
        SET t = (SELECT MAX(time) FROM jobstats WHERE tagnumber = tag);
        RETURN t;
    END //

DELIMITER ;

-- Select most recent department
DROP FUNCTION IF EXISTS mostRecentDepartment;
DELIMITER //
CREATE FUNCTION mostRecentDepartment(tag MEDIUMINT)
    RETURNS VARCHAR(8)
    DETERMINISTIC
    BEGIN
        DECLARE t VARCHAR(8);
        SET t = (SELECT department FROM jobstats WHERE tagnumber = tag AND time = mostRecentTime(tag));
        RETURN t;
    END //

DELIMITER ;

-- Select remote table data
DROP PROCEDURE IF EXISTS selectRemote;
DELIMITER //
CREATE PROCEDURE selectRemote()
DETERMINISTIC
BEGIN
SELECT tagnumber AS 'Tag',
    present AS 'Last Heard',
    job_queued AS 'Pending Job',
    status AS 'Status',
    CONCAT(battery_charge, '%') AS 'Battery Charge',
    battery_status AS 'Battery Status',
    CONCAT(cpu_temp, '°C') AS 'CPU Temp',
    CONCAT(disk_temp, '°C') AS 'Disk Temp',
    CONCAT(watts_now, ' Watts') AS 'Power Draw'
    FROM remote WHERE present_bool = '1' OR status LIKE 'fail%' ORDER BY present DESC LIMIT 20;
    END; //

DELIMITER ;

-- Select missing remote table data
DROP PROCEDURE IF EXISTS selectRemoteMissing;
DELIMITER //
CREATE PROCEDURE selectRemoteMissing()
DETERMINISTIC
BEGIN
SELECT tagnumber AS 'Tag',
    present AS 'Last Heard',
    job_queued AS 'Pending Job',
    status AS 'Status',
    CONCAT(battery_charge, '%') AS 'Battery Charge',
    battery_status AS 'Battery Status',
    CONCAT(cpu_temp, '°C') AS 'CPU Temp',
    CONCAT(disk_temp, '°C') AS 'Disk Temp'
    FROM remote WHERE present_bool IS NULL ORDER BY present DESC LIMIT 10;
    END; //

DELIMITER ;

-- Select remote table stats
DROP PROCEDURE IF EXISTS selectRemoteStats;
DELIMITER //
CREATE PROCEDURE selectRemoteStats()
DETERMINISTIC
BEGIN
SELECT 
    (SELECT COUNT(remote.tagnumber) FROM remote WHERE remote.present_bool = '1') AS 'Present Laptops',
    CONCAT(ROUND(AVG(remote.battery_charge), 0), '%') AS 'Avg. Battery Charge',
    CONCAT(ROUND(AVG(remote.cpu_temp), 1), '°C') AS 'Avg. CPU Temp',
    CONCAT(ROUND(AVG(remote.disk_temp), 1), '°C') AS 'Avg. Disk Temp',
    CONCAT(ROUND(AVG(remote.watts_now), 1), ' Watts') AS 'Avg. Actual Power Draw',
    CONCAT(ROUND(SUM(remote.watts_now), 0), ' Watts') AS 'Actual Power Draw',
    CONCAT(ROUND(SUM(IF (remote.battery_status NOT IN ('Discharging') AND remote.present_bool = 1, 55, 0)), 0), ' Cur. Watts', '/' , ROUND(SUM(IF (remote.present_bool = 1, 55, 0)), 0), ' Watts') AS 'Power Draw from Wall',
    SUM(os_stats.os_installed) AS 'OS Installed Sum'
    FROM remote 
    LEFT JOIN os_stats ON remote.tagnumber = os_stats.tagnumber 
    WHERE remote.present_bool = 1;
    END; //

DELIMITER ;

DROP PROCEDURE IF EXISTS selectLocationAutocomplete;
DELIMITER //
CREATE PROCEDURE selectLocationAutocomplete()
        DETERMINISTIC
        BEGIN
          SELECT locationFormatting(REPLACE(REPLACE(REPLACE(location, '\\', '\\\\'), '''', '\\'''), '\"','\\"')) AS 'location' FROM locations WHERE time IN (SELECT MAX(time) FROM locations WHERE tagnumber IS NOT NULL GROUP BY tagnumber) GROUP BY location;
        END //
DELIMITER ;

-- SHRL Report
DROP PROCEDURE IF EXISTS iterateSHRLCSV;
DELIMITER //
CREATE PROCEDURE iterateSHRLCSV()
DETERMINISTIC
BEGIN

(SELECT 'Last Entry', 'Tag Number', 'Serial Number', 'System Model', 'Department', 'Location', 'CPU Model', 'CPU Cores', 'RAM Capacity', 'Disk Size', 'Disk Type', 'Disk Health', 'Note')
UNION
(SELECT 
  locations.time, jobstats.tagnumber, jobstats.system_serial, 
  system_data.system_model, static_departments.department_readable, 
  locations.location, system_data.cpu_model, system_data.cpu_cores,
  CONCAT(t1.ram_capacity, ' GB'), CONCAT(t1.disk_size, ' GB'), t1.disk_type,
  CONCAT(clientstats.disk_health, '%'), locations.note
FROM jobstats 
LEFT JOIN locations ON jobstats.tagnumber = locations.tagnumber 
LEFT JOIN departments ON jobstats.tagnumber = departments.tagnumber
LEFT JOIN clientstats ON jobstats.tagnumber = clientstats.tagnumber
LEFT JOIN static_departments ON departments.department = static_departments.department_readable
LEFT JOIN system_data ON jobstats.tagnumber = system_data.tagnumber
LEFT JOIN (SELECT tagnumber, ram_capacity, disk_type, disk_size FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE host_connected = 1 AND tagnumber IS NOT NULL GROUP BY tagnumber)) t1
  ON jobstats.tagnumber = t1.tagnumber
INNER JOIN (SELECT MAX(time) AS 'time' FROM jobstats WHERE tagnumber IS NOT NULL AND system_serial IS NOT NULL GROUP BY tagnumber) t2
  ON jobstats.time = t2.time
INNER JOIN (SELECT MAX(time) AS 'time' FROM locations WHERE tagnumber IS NOT NULL AND system_serial IS NOT NULL GROUP BY tagnumber) t3
  ON locations.time = t3.time
INNER JOIN (SELECT MAX(time) AS 'time' FROM departments WHERE tagnumber IS NOT NULL AND system_serial IS NOT NULL GROUP BY tagnumber) t4
  ON departments.time = t4.time
WHERE departments.department = 'shrl'
ORDER BY locations.location ASC, jobstats.tagnumber ASC, locations.time ASC);

END; //
DELIMITER ;



DROP FUNCTION IF EXISTS locationFormatting;
DELIMITER //
CREATE FUNCTION locationFormatting(location VARCHAR(64))
RETURNS VARCHAR(64)
DETERMINISTIC
BEGIN
  DECLARE ret VARCHAR(64);
  SET ret = (SELECT 
    (CASE 
      WHEN location REGEXP '^.{1}$' THEN UPPER(location) 
      WHEN location REGEXP 'checkout|check-out|check out' THEN 'Check Out'
      WHEN location REGEXP 'cam desk|cams desk|cam''s desk' THEN 'Cam''s Desk'
      WHEN location REGEXP 'matthew desk|matthews desk|matthew''s desk' THEN 'Matthew''s Desk'
      ELSE location END)
  );
  RETURN ret;
END //
DELIMITER ;

DROP PROCEDURE IF EXISTS longestCloneTimes;
DELIMITER //
CREATE PROCEDURE longestCloneTimes()
DETERMINISTIC
BEGIN

SELECT clientstats.tagnumber, clientstats.clone_avgtime, locationFormatting(locations.location) AS 'location' 
FROM locations 
LEFT JOIN clientstats ON locations.tagnumber = clientstats.tagnumber 
INNER JOIN (SELECT MAX(time) as 'time' FROM locations GROUP BY tagnumber) t1 
  on locations.time = t1.time 
WHERE location IN ('c', 'a', 'b', 'q', 'f', 'On top of Z') 
  AND clientstats.clone_avgtime IS NOT NULL ORDER BY location, clone_avgtime;

END; //
DELIMITER ;


DROP PROCEDURE IF EXISTS longestEraseTimes;
DELIMITER //
CREATE PROCEDURE longestEraseTimes()
DETERMINISTIC
BEGIN

SELECT clientstats.tagnumber, clientstats.erase_avgtime, locationFormatting(locations.location) AS 'location' 
FROM locations 
LEFT JOIN clientstats ON locations.tagnumber = clientstats.tagnumber 
INNER JOIN (SELECT MAX(time) as 'time' FROM locations GROUP BY tagnumber) t1 
  on locations.time = t1.time 
WHERE location IN ('c', 'a', 'b', 'q', 'f', 'On top of Z') 
  AND clientstats.erase_avgtime IS NOT NULL ORDER BY location, erase_avgtime;

END; //
DELIMITER ;


DROP PROCEDURE IF EXISTS iterateCustomReport;
DELIMITER //
CREATE PROCEDURE iterateCustomReport()
DETERMINISTIC
BEGIN

(SELECT 'Tag Number', 'Serial Number', 'System Model', 'AD Domain', 'Location', 'Note', 'Last Update')
UNION ALL
(
  SELECT locations.tagnumber, locations.system_serial, system_data.system_model, static_domains.domain_readable, 
    locationFormatting(locations.location) AS 'location', locations.note, locations.time
  FROM locations
  LEFT JOIN system_data ON locations.tagnumber = system_data.tagnumber
  LEFT JOIN os_stats ON locations.tagnumber = os_stats.tagnumber
  LEFT JOIN static_domains ON locations.domain = static_domains.domain
  LEFT JOIN departments ON (locations.tagnumber = departments.tagnumber AND departments.time IN (SELECT MAX(time) FROM departments GROUP BY tagnumber))
  WHERE locations.time IN (SELECT MAX(time) FROM locations GROUP BY tagnumber)
    AND os_stats.os_installed = 1
    AND departments.department = 'techComm'
);

END; //
DELIMITER ;


DROP PROCEDURE IF EXISTS iteratePreProperty;
DELIMITER //
CREATE PROCEDURE iteratePreProperty()
DETERMINISTIC
BEGIN

(SELECT 'Tag Number', 'Serial Number', 'System Model', 'Disk Removed', 'Location', 'Note', 'Last Update')
UNION ALL
(SELECT IF (locations.tagnumber LIKE '77204%' OR locations.tagnumber LIKE '999%', 'NO TAG', locations.tagnumber) AS 'tagnumber', locations.system_serial, system_data.system_model, 
	IF (locations.disk_removed = 1, 'Yes', 'No') AS 'disk_removed', IF (locationFormatting(locations.location) = 'On top of Z', 'Z', locationFormatting(locations.location)) AS 'location', locations.note, locations.time
FROM locations
LEFT JOIN departments ON locations.tagnumber = departments.tagnumber
INNER JOIN (SELECT MAX(time) AS 'time' FROM departments GROUP BY tagnumber) t2 ON departments.time = t2.time
LEFT JOIN system_data ON locations.tagnumber = system_data.tagnumber
INNER JOIN (SELECT MAX(time) AS 'time' FROM locations GROUP BY tagnumber) t1 ON locations.time = t1.time
WHERE departments.department IN ('pre-property')
ORDER BY location, tagnumber);

END; //
DELIMITER ;



DROP PROCEDURE IF EXISTS selectNullTagnumbers;
DELIMITER //
CREATE PROCEDURE selectNullTagnumbers()
DETERMINISTIC
BEGIN

select etheraddress, count(etheraddress) AS 'count' from jobstats where tagnumber is null group by etheraddress order by count asc;

END; //
DELIMITER ;