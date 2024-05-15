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
(SELECT 'UUID','Tag','Ethernet MAC','WiFi MAC','Date','Datetime','Department','BIOS Vendor','BIOS Version','BIOS Date',
    'BIOS Revision','BIOS Firmware','System Manufacturer','System Model','System Serial','System UUID',
    'System SKU','Motherboard Manufacturer','Motherboard Serial','Chassis Manufcaturer', 'Chassis Type','Chassis Serial',
    'Chassis Tag','Network Usage','CPU Usage','CPU Manufacturer', 'CPU Model','CPU Max Speed',
    'CPU Cores','CPU Threads','CPU Temp','RAM Serial','RAM Capacity','RAM Speed','Battery Manufacturer',
    'Battery Name','Battery Capacity','Battery Serial Number','Battery Manufacture Date','Battery Max Charge','Battery Charge Cycles',
    'Boot Time','Did Sleep','Disk','Disk Type','Disk Size','Disk Model','Disk Serial','Disk Firmware',
    'Disk Power on Hours','Disk Temperature','Disk Reads','Disk Writes',
    'Erase Successful','Erase Mode','Total Erase Time','Disk Erased',
    'Clone Successful','Total Clone Time','Master Image')
UNION
(SELECT uuid, tagnumber, etheraddress, wifi_mac, date, time, department, bios_vendor, bios_version, bios_date,
    bios_revision, bios_firmware, system_manufacturer, system_model, system_serial, system_uuid,
    system_sku, motherboard_manufacturer, motherboard_serial, chassis_manufacturer, chassis_type, chassis_serial,
    chassis_tag, CONCAT(network_usage, ' Mbps'), CONCAT(cpu_usage, '%'), cpu_manufacturer, cpu_model, CONCAT(ROUND(cpu_maxspeed / 1000, 2), ' GHz'),
    cpu_cores, cpu_threads, CONCAT(cpu_temp, ' C'), ram_serial, CONCAT(ram_capacity, ' GB'), CONCAT(ram_speed, ' MHz'), battery_manufacturer,
    battery_name, CONCAT(battery_capacity, ' MWh'), battery_serial,
    battery_manufacturedate, CONCAT(battery_health, '%'), battery_charge_cycles, CONCAT(boot_time, 's'), hibernate, disk, disk_type, 
    CONCAT(disksizegb, ' GB'), disk_model, disk_serial, disk_firmware,
    CONCAT(disk_power_on_hours, ' hrs'), CONCAT(disk_temp, ' C'), CONCAT(disk_reads, ' TB/R'), CONCAT(disk_writes, ' TB/W'), 
    erase_completed, erase_mode, CONCAT(erase_time, 's'), erase_diskpercent, clone_completed, CONCAT(clone_time, 's'),
    clone_master
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
    'Notes',
    'Most Recent Entry')
UNION
(SELECT tagnumber,
    system_serial,
    location,
    status,
    note,
    time 
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
GRANT INSERT, SELECT, UPDATE ON laptopDB.* TO 'laptops'@'10.0.0.0/255.0.0.0';

CREATE USER IF NOT EXISTS 'shrl'@'10.0.0.0/255.0.0.0' IDENTIFIED BY 'UHouston!';
GRANT INSERT, SELECT, UPDATE ON shrl.* TO 'shrl'@'10.0.0.0/255.0.0.0';

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

