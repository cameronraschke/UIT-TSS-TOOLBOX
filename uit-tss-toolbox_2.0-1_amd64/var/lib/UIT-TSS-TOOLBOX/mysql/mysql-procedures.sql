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
DROP PROCEDURE IF EXISTS iterateJobLabels;
DROP PROCEDURE IF EXISTS iterateJobCSV;
DELIMITER //
CREATE PROCEDURE iterateJobCSV()
DETERMINISTIC
BEGIN
(SELECT 'UUID','Tag','Ethernet MAC','WiFi MAC','Date','Datetime','Department','BIOS Vendor','BIOS Version','BIOS Last Update',
    'BIOS Revision','BIOS Firmware','System Manufacturer','System Model','System Serial','System UUID',
    'System SKU','Motherboard Manufacturer','Motherboard Serial','Chassis Manufcaturer',
    'Chassis Type','Chassis Serial','Chassis Tag','CPU Manufacturer',
    'CPU Model','CPU Max Speed','CPU Cores','CPU Threads','CPU Temp','RAM Serial','Battery Manufacturer',
    'Battery Name','Battery Capacity','Battery Serial Number','Battery Manufacture Date','Battery Max Charge %','Battery Charge Cycles',
    'Boot Time','Job Type','Did Sleep (Boolean)','Disk','Disk Type','Disk Size (GB)','Disk Model','Disk Serial','Disk Firmware',
    'Disk Power on Hours','Disk Temperature','Disk Reads (TB)','Disk Writes (TB)',
    'Total Time for Jobs','Erase Successful','Erase Mode','Total Erase Time','Disk Erased %',
    'Clone Successful','Clone Mode','Total Clone Time','Master Image','Clone Server','Clone Image','Last Image Update')
UNION
(SELECT * FROM jobstats WHERE department = 'techComm' ORDER BY time DESC);
END; //

DELIMITER ;


-- Client table CSV
DROP PROCEDURE IF EXISTS iterateClientLabels;
DROP PROCEDURE IF EXISTS iterateClientCSV;
DELIMITER //
CREATE PROCEDURE iterateClientCSV()
DETERMINISTIC
BEGIN
(SELECT 'Tag',
    'Serial Number',
    'System Manufacturer',
    'System Model',
    'Last Job Time',
    'Battery Max Charge',
    'Terabytes Written (TBW)',
    'Erase Time',
    'Clone Time',
    'Total Jobs')
UNION
(SELECT tagnumber,
    system_serial,
    system_manufacturer,
    system_model,
    last_job_time,
    CONCAT(battery_health, '%'),
    CONCAT(tbw_pcnt, '%'),
    CONCAT(erase_avgtime, ' minutes'),
    CONCAT(clone_avgtime, ' minutes'),
    all_jobs
    FROM clientstats ORDER BY last_job_time DESC);
END; //

DELIMITER ;


-- Server table CSV
DROP PROCEDURE IF EXISTS iterateServerLabels;
DROP PROCEDURE IF EXISTS iterateServerCSV;
DELIMITER //
CREATE PROCEDURE iterateServerCSV()
DETERMINISTIC
BEGIN

(SELECT 'Date',
    'Computer Count',
    'Disk Wear',
    'Disk MTBF',
    'Battery Max Charge Level',
    'Battery Charge Cycles',
    'Total Jobs',
    'Clone Jobs',
    'Erase Jobs',
    'Clone Time',
    'NVME Erase Time',
    'SATA Erase Time',
    'Last Image Update')
UNION
(SELECT date,
    laptop_count,
    CONCAT(tbw_pcnt, '%'),
    CONCAT(disk_mtbf, '%'),
    CONCAT(battery_health, '%'),
    CONCAT(battery_charge_cycles, '%'),
    all_jobs,
    clone_jobs,
    erase_jobs,
    CONCAT(clone_avgtime, ' minutes'),
    CONCAT(nvme_erase_avgtime, ' minutes'),
    CONCAT(sata_erase_avgtime, ' minutes'),
    last_image_update
    FROM serverstats
    ORDER BY date DESC);

END; //

DELIMITER ;


-- Location table CSV
DROP PROCEDURE IF EXISTS iterateLocationLabels;
DROP PROCEDURE IF EXISTS iterateLocationsCSV;
DELIMITER //
CREATE PROCEDURE iterateLocationsCSV()
DETERMINISTIC
BEGIN

(SELECT 'Tagnumber',
    'Serial Number',
    'Location',
    'Status',
    'Description of Problem',
    'Most Recent Entry')
UNION
(SELECT tagnumber,
    system_serial,
    location,
    status,
    problem,
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
CREATE USER IF NOT EXISTS 'laptops'@'10.0.0.0/255.0.0.0' IDENTIFIED BY 'UHouston!';
GRANT INSERT, SELECT, UPDATE ON laptopDB.* TO 'laptops'@'10.0.0.0/255.0.0.0';

CREATE USER IF NOT EXISTS 'shrl'@'10.0.0.0/255.0.0.0' IDENTIFIED BY 'UHouston!';
GRANT INSERT, SELECT, UPDATE ON shrl.* TO 'shrl'@'10.0.0.0/255.0.0.0';

END; //

DELIMITER ;

