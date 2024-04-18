DROP PROCEDURE IF EXISTS iterateDate;
DELIMITER //
CREATE PROCEDURE iterateDate(date1 date, date2 date)
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

DROP PROCEDURE IF EXISTS iterateJobLabels;
DELIMITER //
CREATE PROCEDURE iterateJobLabels()
DETERMINISTIC
BEGIN
SELECT 'Job UUID','Tag Number','Ethernet Address','WiFi Address','Date','Datetime','Department','BIOS Vendor','BIOS Version','BIOS Last Update',
    'BIOS Revision','BIOS Firmware','System Manufacturer','System Model','System Serial','System UUID',
    'System SKU','Motherboard Manufacturer','Motherboard Serial','Chassis Manufcaturer',
    'Chassis Type','Chassis Serial','Chassis Tag','CPU Manufacturer',
    'CPU Model','CPU Max Speed','CPU Cores','CPU Threads','CPU Temp','RAM Serial','Battery Manufacturer',
    'Battery Name','Battery Capacity','Battery Serial Number','Battery Manufacture Date','Battery Max Charge %','Battery Charge Cycles',
    'Boot Time','Job Type','Did Sleep (Boolean)','Disk','Disk Type','Disk Size (GB)','Disk Model','Disk Serial','Disk Firmware',
    'Disk Power on Hours','Disk Temperature','Disk Reads (TB)','Disk Writes (TB)',
    'Total Time for Jobs','Erase Successful','Erase Mode','Total Erase Time','Disk Erased %',
    'Clone Successful','Clone Mode','Total Clone Time','Master Image','Clone Server','Clone Image','Last Image Update';
END; //

DELIMITER ;

DROP PROCEDURE IF EXISTS iterateClientLabels;
DELIMITER //
CREATE PROCEDURE iterateClientLabels()
DETERMINISTIC
BEGIN
SELECT 'Tag Number','Serial Number','System Manufacturer','System Model','Last Job Time',
    'Battery Max Charge','Terabytes Written (TBW)',
    'Erase Time','Clone Time','Total Jobs';
END; //

DELIMITER ;


DROP PROCEDURE IF EXISTS iterateServerLabels;
DELIMITER //
CREATE PROCEDURE iterateServerLabels()
DETERMINISTIC
BEGIN

SELECT 'Date','Computer Count','Disk TBW/R','Disk MTBF','Battery Max Charge Level',
    'Total Jobs','Clone Jobs','Erase Jobs',
    'Clone Time','NVME Erase Time','SATA Erase Time','Last Image Update';

END; //

DELIMITER ;


DROP PROCEDURE IF EXISTS iterateLocationLabels;
DELIMITER //
CREATE PROCEDURE iterateLocationLabels()
DETERMINISTIC
BEGIN

SELECT 'Tagnumber', 'Serial Number', 'Location', 'Status', 'Description of Problem', 'Most Recent Entry';

END; //

DELIMITER ;