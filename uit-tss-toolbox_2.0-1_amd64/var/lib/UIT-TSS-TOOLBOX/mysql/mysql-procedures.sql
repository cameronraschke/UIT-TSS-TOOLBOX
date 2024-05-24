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
CONCAT(battery_capacity, ' Wh'), battery_manufacturedate, cpu_manufacturer, cpu_model, CONCAT(cpu_maxspeed, ' Ghz'), cpu_cores, cpu_threads, CONCAT(cpu_temp, ' C'), 
motherboard_manufacturer, motherboard_serial, bios_version, bios_date, bios_firmware, ram_serial, ram_capacity ,CONCAT(ram_speed, 'Mhz'), CONCAT(cpu_usage, '%'), 
CONCAT(network_usage, 'mbps'), CONCAT(boot_time, 's'), REPLACE(erase_completed, '1', 'Yes'), erase_mode, CONCAT(erase_diskpercent, '%'), CONCAT(erase_time, 's'), 
REPLACE(clone_completed, '1', 'Yes'), REPLACE(clone_master, '1', 'Yes'), CONCAT(erase_time, 's')
FROM jobstats WHERE department = 'techComm' ORDER BY time DESC);
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
    'BIOS Version',
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
    bios_version,
    CONCAT(erase_avgtime, ' minutes'),
    CONCAT(clone_avgtime, ' minutes'),
    all_jobs
    FROM clientstats ORDER BY last_job_time DESC);
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
    'Disk Health',
    'Battery Health',
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
    CONCAT(disk_health, '%'),
    CONCAT(battery_health, '%'),
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
    WHERE time IN (SELECT MAX(time) FROM locations GROUP BY tagnumber));

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
    bios_version,
    system_model,
    cpu_usage,
    network_usage,
    battery_health,
    disk_power_on_hours
    FROM jobstats WHERE tagnumber = tag AND department = 'techComm' ORDER BY time DESC LIMIT 5;

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
    disk_type, 
    disk_writes, 
    disk_reads, 
    disk_power_on_hours 
    FROM jobstats WHERE tagnumber = tag AND department = 'techComm' ORDER BY time DESC;
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
    FROM jobstats WHERE tagnumber = tag AND department = 'techComm' ORDER BY time DESC;
    END; //

DELIMITER ;

