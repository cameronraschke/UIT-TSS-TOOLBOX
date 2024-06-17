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


-- Job table CSV
DROP PROCEDURE IF EXISTS iterateJobCSV;
DELIMITER //
CREATE PROCEDURE iterateJobCSV()
DETERMINISTIC
BEGIN
(SELECT 'UUID','Tag','System Serial','Date','Datetime','Department','Ethernet MAC','WiFi MAC','System Manufacturer','Model','System UUID',
'System SKU', 'Chassis Type', 'Disk','Disk Model','Disk Type','Disk Size','Disk Serial','Disk Writes','Disk Reads',
'Disk Power on Hours','Disk Temp','Disk Firmware','Battery Model','Battery Serial','Battery Health','Battery Charge Cycles',
'Battery Capacity','Battery Manufacture Date','CPU Manufacturer','CPU Model','CPU Max Speed','CPU Cores','CPU Threads','CPU Temp',
'Motherboard Manufacturer','Motherboard Serial','BIOS Version','BIOS Date','BIOS Firmware','RAM Serial','RAM Capacity','RAM Speed','CPU Usage',
'Network Usage','Boot Time','Erase Completed','Erase Mode','Erase Disk Percent','Erase Time',
'Clone Completed','Clone Master','Clone Time')
UNION
(SELECT uuid, tagnumber, system_serial, date, CONVERT(time, DATETIME), department, etheraddress, wifi_mac, system_manufacturer, system_model, system_uuid, 
system_sku, chassis_type, disk, disk_model, disk_type, CONCAT(disk_size, ' GB'), disk_serial, CONCAT(disk_writes, ' TB'), CONCAT(disk_reads, ' TB'), 
CONCAT(disk_power_on_hours, 'hrs'), CONCAT(disk_temp, ' C'), disk_firmware, battery_model, battery_serial, CONCAT(battery_health, '%'), battery_charge_cycles, 
CONCAT(battery_capacity, ' Wh'), battery_manufacturedate, cpu_manufacturer, cpu_model, CONCAT(round(cpu_maxspeed / 1000, 2), ' Ghz'), cpu_cores, cpu_threads, CONCAT(cpu_temp, ' C'), 
motherboard_manufacturer, motherboard_serial, bios_version, bios_date, bios_firmware, ram_serial, CONCAT(ram_capacity, ' GB') ,CONCAT(ram_speed, 'Mhz'), CONCAT(cpu_usage, '%'), 
CONCAT(network_usage, 'mbps'), CONCAT(boot_time, 's'), REPLACE(erase_completed, '1', 'Yes'), erase_mode, CONCAT(erase_diskpercent, '%'), CONCAT(erase_time, 's'), 
REPLACE(clone_completed, '1', 'Yes'), REPLACE(clone_master, '1', 'Yes'), CONCAT(clone_time, 's')
FROM jobstats ORDER BY time DESC);
END; //

DELIMITER ;


-- Client table CSV
DROP PROCEDURE IF EXISTS iterateClientCSV;
DELIMITER //
CREATE PROCEDURE iterateClientCSV()
DETERMINISTIC
BEGIN
(SELECT 'Tag',
    'Serial Number',
    'System Model',
    'Last Job Time',
    'Battery Health',
    'Disk Health',
    'Disk Type',
    'BIOS Updated',
    'Erase Time',
    'Clone Time',
    'Total Jobs')
UNION
(SELECT tagnumber,
    system_serial,
    system_model,
    last_job_time,
    CONCAT(battery_health, '%'),
    CONCAT(disk_health, '%'),
    disk_type,
    IF (bios_updated='1', "Yes", "No"),
    CONCAT(erase_avgtime, ' minutes'),
    CONCAT(clone_avgtime, ' minutes'),
    all_jobs
    FROM clientstats WHERE tagnumber IS NOT NULL ORDER BY last_job_time DESC);
END; //

DELIMITER ;


-- Server table CSV
DROP PROCEDURE IF EXISTS iterateServerCSV;
DELIMITER //
CREATE PROCEDURE iterateServerCSV()
DETERMINISTIC
BEGIN

(SELECT 'Date',
    'Computer Count',
    'Battery Health',
    'Disk Health',
    'Total Jobs',
    'Clone Jobs',
    'Erase Jobs',
    'Clone Time',
    'NVME Erase Time',
    'HDD Erase Time',
    'Last Image Update')
UNION
(SELECT date,
    laptop_count,
    CONCAT(battery_health, '%'),
    CONCAT(disk_health, '%'),
    all_jobs,
    clone_jobs,
    erase_jobs,
    CONCAT(clone_avgtime, ' mins'),
    CONCAT(nvme_erase_avgtime, ' mins'),
    CONCAT(hdd_erase_avgtime, ' mins'),
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

(SELECT 'Tag',
    'Serial Number',
    'Location',
    'Status',
    'OS Insalled',
    'Notes',
    'Most Recent Entry')
UNION
(SELECT tagnumber,
    system_serial,
    location,
    IF (status='0', "Working", "Broken"),
    IF (os_installed='1', "Yes", "No"),
    note,
    CONVERT(time, DATETIME) 
    FROM locations 
    WHERE tagnumber IN (SELECT tagnumber FROM locations WHERE tagnumber IN (SELECT tagnumber FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE tagnumber IS NOT NULL AND department IS NOT NULL GROUP BY tagnumber) AND department = 'techComm')) AND time IN (SELECT MAX(time) FROM locations WHERE tagnumber IS NOT NULL GROUP BY tagnumber)
);
END; //

DELIMITER ;

DROP PROCEDURE IF EXISTS iterateLocationsPropertyCSV;
DELIMITER //
CREATE PROCEDURE iterateLocationsPropertyCSV()
DETERMINISTIC
BEGIN

(SELECT 'Tag',
    'Serial Number',
    'Location',
    'Disk Removed',
    'Notes',
    'Most Recent Entry')
UNION
(SELECT tagnumber,
    system_serial,
    location,
    IF (disk_removed='1', "Yes", "No"),
    note,
    CONVERT(time, DATETIME) 
    FROM locations 
    WHERE time IN (SELECT time FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE department IS NOT NULL GROUP BY tagnumber) AND department = 'property')
);
END; //

DELIMITER ;


-- SQL permissions and user creation
DROP PROCEDURE IF EXISTS sqlPermissions;
DELIMITER //
CREATE PROCEDURE sqlPermissions()
DETERMINISTIC
BEGIN

CREATE USER IF NOT EXISTS 'cameron'@'localhost' IDENTIFIED BY 'UHouston!';
GRANT ALL ON *.* TO 'cameron'@'localhost' WITH GRANT OPTION;

CREATE USER IF NOT EXISTS 'laptops'@'10.0.0.0/255.0.0.0' IDENTIFIED BY 'UHouston!';
GRANT INSERT, SELECT, UPDATE, EXECUTE ON laptopDB.* TO 'laptops'@'10.0.0.0/255.0.0.0';

CREATE USER IF NOT EXISTS 'shrl'@'10.0.0.0/255.0.0.0' IDENTIFIED BY 'UHouston!';
GRANT INSERT, SELECT, UPDATE, EXECUTE ON shrl.* TO 'shrl'@'10.0.0.0/255.0.0.0';

END; //

DELIMITER ;


-- Select info about a tag
DROP PROCEDURE IF EXISTS selectTag;
DELIMITER //
CREATE PROCEDURE selectTag(tag VARCHAR(8))
DETERMINISTIC
BEGIN
SELECT time,
    tagnumber,
    system_serial,
    bios_version,
    system_model,
    cpu_usage,
    network_usage,
    battery_health,
    disk_power_on_hours,
    ram_serial
    FROM jobstats WHERE tagnumber = tag ORDER BY time DESC LIMIT 5;

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
    clone_master 
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
