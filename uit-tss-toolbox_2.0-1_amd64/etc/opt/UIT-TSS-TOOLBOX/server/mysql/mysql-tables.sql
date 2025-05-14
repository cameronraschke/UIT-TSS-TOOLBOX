DROP TABLE IF EXISTS serverstats;
CREATE TABLE serverstats (
    date DATE NOT NULL PRIMARY KEY,
    client_count SMALLINT DEFAULT NULL,
    total_os_installed SMALLINT DEFAULT NULL,
    battery_health DECIMAL(5,2) DEFAULT NULL,
    disk_health DECIMAL(5,2) DEFAULT NULL,
    total_job_count MEDIUMINT DEFAULT NULL,
    clone_job_count MEDIUMINT DEFAULT NULL,
    erase_job_count MEDIUMINT DEFAULT NULL,
    avg_clone_time SMALLINT DEFAULT NULL,
    avg_erase_time SMALLINT DEFAULT NULL,
    last_image_update DATE DEFAULT NULL
);


DROP TABLE IF EXISTS clientstats;
CREATE TABLE clientstats (
    tagnumber VARCHAR(8) NOT NULL PRIMARY KEY,
    system_serial VARCHAR(24) DEFAULT NULL,
    system_model VARCHAR(64) DEFAULT NULL,
    last_job_time DATETIME DEFAULT NULL,
    battery_health SMALLINT DEFAULT NULL,
    disk_health DECIMAL(5,2) DEFAULT NULL,
    disk_type VARCHAR(8) DEFAULT NULL,
    bios_updated BOOLEAN DEFAULT NULL,
    erase_avgtime SMALLINT DEFAULT NULL,
    clone_avgtime SMALLINT DEFAULT NULL,
    all_jobs SMALLINT DEFAULT NULL
);


CREATE TABLE IF NOT EXISTS jobstats (
    uuid VARCHAR(64) NOT NULL,
    tagnumber VARCHAR(8) DEFAULT NULL,
    etheraddress VARCHAR(17) DEFAULT NULL,
    date DATE DEFAULT NULL,
    time DATETIME(3) DEFAULT NULL,
    system_serial VARCHAR(24) DEFAULT NULL,
    disk VARCHAR(8) DEFAULT NULL,
    disk_model VARCHAR(36) DEFAULT NULL,
    disk_type VARCHAR(4) DEFAULT NULL,
    disk_size SMALLINT DEFAULT NULL,
    disk_serial VARCHAR(32) DEFAULT NULL,
    disk_writes DECIMAL(5,2) DEFAULT NULL,
    disk_reads DECIMAL(5,2) DEFAULT NULL,
    disk_power_on_hours MEDIUMINT DEFAULT NULL,
    disk_errors INT DEFAULT NULL,
    disk_power_cycles MEDIUMINT DEFAULT NULL,
    disk_temp TINYINT DEFAULT NULL,
    disk_firmware VARCHAR(10) DEFAULT NULL,
    battery_model VARCHAR(16) DEFAULT NULL,
    battery_serial VARCHAR(16) DEFAULT NULL,
    battery_health TINYINT DEFAULT NULL,
    battery_charge_cycles SMALLINT DEFAULT NULL,
    battery_capacity MEDIUMINT DEFAULT NULL,
    battery_manufacturedate DATE DEFAULT NULL,
    cpu_temp TINYINT DEFAULT NULL,
    bios_version VARCHAR(24) DEFAULT NULL,
    bios_date VARCHAR(12) DEFAULT NULL,
    bios_firmware VARCHAR(8) DEFAULT NULL,
    ram_serial VARCHAR(26) DEFAULT NULL,
    ram_capacity TINYINT DEFAULT NULL,
    ram_speed SMALLINT DEFAULT NULL,
    cpu_usage DECIMAL(6,2) DEFAULT NULL,
    network_usage DECIMAL(5,2) DEFAULT NULL,
    boot_time DECIMAL(5,2) DEFAULT NULL,
    erase_completed BOOLEAN DEFAULT NULL,
    erase_mode VARCHAR(24) DEFAULT NULL,
    erase_diskpercent TINYINT DEFAULT NULL,
    erase_time SMALLINT DEFAULT NULL,
    clone_completed BOOLEAN DEFAULT NULL,
    clone_image VARCHAR(36) DEFAULT NULL,
    clone_master BOOLEAN DEFAULT NULL,
    clone_time SMALLINT DEFAULT NULL,
    host_connected BOOLEAN DEFAULT NULL
);

ALTER TABLE jobstats
    DROP PRIMARY KEY,
    MODIFY COLUMN uuid VARCHAR(64) NOT NULL PRIMARY KEY FIRST,
    MODIFY COLUMN tagnumber VARCHAR(8) DEFAULT NULL AFTER uuid,
    MODIFY COLUMN etheraddress VARCHAR(17) DEFAULT NULL AFTER tagnumber,
    MODIFY COLUMN date DATE DEFAULT NULL AFTER etheraddress,
    MODIFY COLUMN time DATETIME(3) DEFAULT NULL AFTER date,
    MODIFY COLUMN system_serial VARCHAR(24) DEFAULT NULL AFTER time,
    MODIFY COLUMN disk VARCHAR(8) DEFAULT NULL AFTER system_serial,
    MODIFY COLUMN disk_model VARCHAR(36) DEFAULT NULL AFTER disk,
    MODIFY COLUMN disk_type VARCHAR(4) DEFAULT NULL AFTER disk_model,
    MODIFY COLUMN disk_size SMALLINT DEFAULT NULL AFTER disk_type,
    MODIFY COLUMN disk_serial VARCHAR(32) DEFAULT NULL AFTER disk_size,
    MODIFY COLUMN disk_writes DECIMAL(5,2) DEFAULT NULL AFTER disk_serial,
    MODIFY COLUMN disk_reads DECIMAL(5,2) DEFAULT NULL AFTER disk_writes,
    MODIFY COLUMN disk_power_on_hours MEDIUMINT DEFAULT NULL AFTER disk_reads,
    MODIFY COLUMN disk_errors INT DEFAULT NULL AFTER disk_power_on_hours,
    MODIFY COLUMN disk_power_cycles MEDIUMINT DEFAULT NULL AFTER disk_errors,
    MODIFY COLUMN disk_temp TINYINT DEFAULT NULL AFTER disk_power_cycles,
    MODIFY COLUMN disk_firmware VARCHAR(10) DEFAULT NULL AFTER disk_temp,
    MODIFY COLUMN battery_model VARCHAR(16) DEFAULT NULL AFTER disk_firmware,
    MODIFY COLUMN battery_serial VARCHAR(16) DEFAULT NULL AFTER battery_model,
    MODIFY COLUMN battery_health TINYINT DEFAULT NULL AFTER battery_serial,
    MODIFY COLUMN battery_charge_cycles SMALLINT DEFAULT NULL AFTER battery_health,
    MODIFY COLUMN battery_capacity MEDIUMINT DEFAULT NULL AFTER battery_charge_cycles,
    MODIFY COLUMN battery_manufacturedate DATE DEFAULT NULL AFTER battery_capacity,
    MODIFY COLUMN cpu_temp TINYINT DEFAULT NULL AFTER battery_manufacturedate,
    MODIFY COLUMN bios_version VARCHAR(24) DEFAULT NULL AFTER cpu_temp,
    MODIFY COLUMN bios_date VARCHAR(12) DEFAULT NULL AFTER bios_version,
    MODIFY COLUMN bios_firmware VARCHAR(8) DEFAULT NULL AFTER bios_date,
    MODIFY COLUMN ram_serial VARCHAR(26) DEFAULT NULL AFTER bios_firmware,
    MODIFY COLUMN ram_capacity TINYINT DEFAULT NULL AFTER ram_serial,
    MODIFY COLUMN ram_speed SMALLINT DEFAULT NULL AFTER ram_capacity,
    MODIFY COLUMN cpu_usage DECIMAL(6,2) DEFAULT NULL AFTER ram_speed,
    MODIFY COLUMN network_usage DECIMAL(5,2) DEFAULT NULL AFTER cpu_usage,
    MODIFY COLUMN boot_time DECIMAL(5,2) DEFAULT NULL AFTER network_usage,
    MODIFY COLUMN erase_completed BOOLEAN DEFAULT NULL AFTER boot_time,
    MODIFY COLUMN erase_mode VARCHAR(24) DEFAULT NULL AFTER erase_completed,
    MODIFY COLUMN erase_diskpercent TINYINT DEFAULT NULL AFTER erase_mode,
    MODIFY COLUMN erase_time SMALLINT DEFAULT NULL AFTER erase_diskpercent,
    MODIFY COLUMN clone_completed BOOLEAN DEFAULT NULL AFTER erase_time,
    MODIFY COLUMN clone_image VARCHAR(36) DEFAULT NULL AFTER clone_completed,
    MODIFY COLUMN clone_master BOOLEAN DEFAULT NULL AFTER clone_image,
    MODIFY COLUMN clone_time SMALLINT DEFAULT NULL AFTER clone_master,
    MODIFY COLUMN host_connected BOOLEAN DEFAULT NULL AFTER clone_time;


CREATE TABLE IF NOT EXISTS locations (
    time DATETIME(3) NOT NULL,
    tagnumber VARCHAR(8) DEFAULT NULL,
    system_serial VARCHAR(24) DEFAULT NULL,
    location VARCHAR(128) DEFAULT NULL,
    status BOOLEAN DEFAULT NULL,
    disk_removed BOOLEAN DEFAULT NULL,
    domain VARCHAR(24) DEFAULT NULL,
    note VARCHAR(256) DEFAULT NULL
);

ALTER TABLE locations
    DROP PRIMARY KEY,
    MODIFY COLUMN time DATETIME(3) NOT NULL PRIMARY KEY FIRST,
    MODIFY COLUMN tagnumber VARCHAR(8) DEFAULT NULL AFTER time,
    MODIFY COLUMN system_serial VARCHAR(24) DEFAULT NULL AFTER tagnumber,
    MODIFY COLUMN location VARCHAR(128) DEFAULT NULL AFTER system_serial,
    MODIFY COLUMN status BOOLEAN DEFAULT NULL AFTER location,
    MODIFY COLUMN disk_removed BOOLEAN DEFAULT NULL AFTER status,
    MODIFY COLUMN domain VARCHAR(24) DEFAULT NULL AFTER disk_removed,
    MODIFY COLUMN note VARCHAR(256) DEFAULT NULL AFTER domain;


DROP TABLE IF EXISTS static_disk_stats;
CREATE TABLE IF NOT EXISTS static_disk_stats (
    disk_model VARCHAR(36) NOT NULL PRIMARY KEY,
    disk_write_speed SMALLINT DEFAULT NULL,
    disk_read_speed SMALLINT DEFAULT NULL,
    disk_mtbf MEDIUMINT DEFAULT NULL,
    disk_tbw SMALLINT DEFAULT NULL,
    disk_tbr SMALLINT DEFAULT NULL,
    min_temp SMALLINT DEFAULT NULL,
    max_temp SMALLINT DEFAULT NULL,
    disk_interface VARCHAR(4) DEFAULT NULL,
    disk_type VARCHAR(4) DEFAULT NULL,
    spinning BOOLEAN DEFAULT NULL,
    spin_speed SMALLINT DEFAULT NULL,
    power_cycles MEDIUMINT DEFAULT NULL
);

INSERT INTO static_disk_stats
    (disk_model,
    disk_write_speed,
    disk_read_speed,
    disk_mtbf,
    disk_tbw,
    disk_tbr,
    min_temp,
    max_temp,
    disk_interface,
    disk_type,
    spinning,
    spin_speed,
    power_cycles)
VALUES 
    ('LITEON CV8-8E128-11 SATA 128GB','550','380','1500000','146',NULL,'0','70','m.2','ssd','0',NULL,NULL),
    ('MTFDHBA256TCK-1AS1AABHA','3000','1600','2000000','75',NULL,NULL,'82','m.2','nvme','0', NULL, NULL),
    ('SSDPEMKF256G8 NVMe INTEL 256GB','3210','1315','1600000','144',NULL,'0','70','m.2','nvme','0',NULL,NULL),
    ('ST500LM034-2GH17A','160','160',NULL,'55','55','0','60','sata','hdd','1','7200','600000'),
    ('TOSHIBA MQ01ACF050',NULL,NULL,'600000', 125, 125,'5','55','sata','hdd','1','7200',NULL),
    ('WDC PC SN520 SDAPNUW-256G-1006','1300','1700','1752000','200',NULL,'0','70','m.2','nvme','0',NULL,NULL),
    ('LITEON CV8-8E120-11 SATA 128GB','380','550','1500000','146',NULL,'0','70','m.2','ssd','0',NULL,'50000'),
    ('LITEON CV3-8D512-11 SATA 512GB','490','540','1500000','250',NULL,NULL,NULL,'m.2','ssd','0',NULL,NULL),
    ('TOSHIBA KSG60ZMV256G M.2 2280 256GB','340','550','1500000',NULL,NULL,'0','80','m.2','ssd','0',NULL,NULL),
    ('TOSHIBA THNSNK256GVN8 M.2 2280 256GB', 388, 545, 1500000, 150, NULL, 0, 70, 'm.2', 'ssd', 0, NULL, NULL),
    ('PC SN740 NVMe WD 512GB','4000','5000','1750000','300',NULL,'0','85','m.2','nvme','0',NULL,'3000'),
    ('SK hynix SC308 SATA 256GB',130,540,1500000,75,NULL,0,70,'m.2','ssd','0',NULL,NULL),
    ('ST500LM000-1EJ162', 100, 100, NULL, 125, 125, 0, 60, 'sata', 'hdd', 1, 5400, 25000),
    ('ST500DM002-1SB10A', 100, 100, NULL, 125, 125, 0, 60, 'sata', 'hdd', 1, 5400, 25000),
    ('SanDisk SSD PLUS 1000GB', 350, 535, 26280, 100, NULL, NULL, NULL, 'sata', 'ssd', 0, NULL, NULL),
    ('WDC WD5000LPLX-75ZNTT1', NULL, NULL, 43800, 125, 125, 0, 60, 'sata', 'hdd', 1, 7200, NULL)
    ;


DROP TABLE IF EXISTS static_battery_stats;
CREATE TABLE IF NOT EXISTS static_battery_stats (
    battery_model VARCHAR(24) NOT NULL PRIMARY KEY,
    battery_charge_cycles SMALLINT DEFAULT NULL
);

INSERT INTO static_battery_stats
    (battery_model,
    battery_charge_cycles
    )
VALUES 
    ('RE03045XL', 300),
    ('DELL VN3N047', 300),
    ('DELL N2K6205', 300),
    ('DELL 1VX1H93', 300),
    ('DELL W7NKD85', 300),
    ('DELL PGFX464', 300),
    ('DELL PGFX484', 300),
    ('DELL 4M1JN11', 300),
    ('X906972', 300),
    ('M1009169', 300),
    ('X910528', 300);


DROP TABLE IF EXISTS static_bios_stats;
CREATE TABLE IF NOT EXISTS static_bios_stats (
    system_model VARCHAR(64) NOT NULL PRIMARY KEY,
    bios_version VARCHAR(24) DEFAULT NULL
);

INSERT INTO static_bios_stats
    (
        system_model,
        bios_version
    )
    VALUES
    ('HP ProBook 450 G6', 'R71 Ver. 01.31.00'),
    ('Latitude 7400', '1.39.0'),
    ('OptiPlex 7000', '1.30.0'),
    ('Latitude 7420', '1.42.0'),
    ('Latitude 3500', '1.36.0'),
    ('Latitude 3560', 'A19'),
    ('Latitude 3590', '1.26.0'),
    ('Latitude 7430', '1.29.0'),
    ('Latitude 7490', '1.41.0'),
    ('Latitude 7480', '1.40.0'),
    ('Latitude E7470', '1.36.3'),
    ('OptiPlex 9010 AIO', 'A25'),
    ('Latitude E6430', 'A24'),
    ('OptiPlex 790', 'A22'),
    ('OptiPlex 780', 'A15'),
    ('OptiPlex 7460 AIO', '1.35.0'),
    ('Latitude 5590', '1.38.0'),
    ('XPS 15 9560', '1.24.0'),
    ('Latitude 5480', '1.39.0'),
    ('Latitude 5289', '1.35.0'),
    ('Surface Book', '92.3748.768'),
    ('Aspire T3-710', 'R01-B1'),
    ('Surface Pro', NULL),
    ('Surface Pro 4', '109.3748.768'),
    ('OptiPlex 5080', '1.28.1'),
    ('OptiPlex 7040', '1.24.0'),
    ('OptiPlex 7050', '1.27.0'),
    ('OptiPlex 5070', '1.31.1'),
    ('OptiPlex 7010', 'A29'),
    ('OptiPlex 7780', '1.36.1');


CREATE TABLE IF NOT EXISTS bios_stats (
    tagnumber VARCHAR(8) NOT NULL PRIMARY KEY,
    time DATETIME(3) DEFAULT NULL,
    bios_version VARCHAR(24) DEFAULT NULL,
    bios_updated BOOLEAN DEFAULT NULL
);

ALTER TABLE bios_stats
    DROP PRIMARY KEY,
    MODIFY COLUMN tagnumber VARCHAR(8) NOT NULL PRIMARY KEY FIRST,
    MODIFY COLUMN time DATETIME(3) DEFAULT NULL AFTER tagnumber,
    MODIFY COLUMN bios_version VARCHAR(24) DEFAULT NULL AFTER time,
    MODIFY COLUMN bios_updated BOOLEAN DEFAULT NULL AFTER bios_version
    ;


CREATE TABLE IF NOT EXISTS os_stats (
    tagnumber VARCHAR(8) NOT NULL PRIMARY KEY,
    time DATETIME(3) DEFAULT NULL,
    os_name VARCHAR(24) DEFAULT NULL,
    os_installed BOOLEAN DEFAULT NULL
);

ALTER TABLE os_stats
    DROP PRIMARY KEY,
    MODIFY COLUMN tagnumber VARCHAR(8) NOT NULL PRIMARY KEY FIRST,
    MODIFY COLUMN time DATETIME(3) DEFAULT NULL AFTER tagnumber,
    MODIFY COLUMN os_name VARCHAR(24) DEFAULT NULL AFTER time,
    MODIFY COLUMN os_installed BOOLEAN DEFAULT NULL AFTER os_name
    ;


CREATE TABLE IF NOT EXISTS remote (
    tagnumber VARCHAR(8) NOT NULL PRIMARY KEY,
    job_queued VARCHAR(24) DEFAULT NULL,
    job_queued_position SMALLINT DEFAULT NULL,
    job_active BOOLEAN DEFAULT NULL,
    clone_mode VARCHAR(24) DEFAULT NULL,
    erase_mode VARCHAR(24) DEFAULT NULL,
    last_job_time DATETIME(3) DEFAULT NULL,
    present DATETIME DEFAULT NULL,
    present_bool BOOLEAN DEFAULT NULL,
    status VARCHAR(128) DEFAULT NULL,
    kernel_updated BOOLEAN DEFAULT NULL,
    battery_charge TINYINT DEFAULT NULL,
    battery_status VARCHAR(20) DEFAULT NULL,
    uptime INT DEFAULT NULL,
    cpu_temp TINYINT DEFAULT NULL,
    disk_temp TINYINT DEFAULT NULL,
    watts_now SMALLINT DEFAULT NULL,
    network_speed SMALLINT DEFAULT NULL
);

ALTER TABLE remote
    DROP PRIMARY KEY,
    MODIFY COLUMN tagnumber VARCHAR(8) NOT NULL PRIMARY KEY FIRST,
    MODIFY COLUMN job_queued VARCHAR(24) DEFAULT NULL AFTER tagnumber,
    MODIFY COLUMN job_queued_position SMALLINT DEFAULT NULL AFTER job_queued,
    MODIFY COLUMN job_active BOOLEAN DEFAULT NULL AFTER job_queued_position,
    MODIFY COLUMN clone_mode VARCHAR(24) DEFAULT NULL AFTER job_active,
    MODIFY COLUMN erase_mode VARCHAR(24) DEFAULT NULL AFTER clone_mode,
    MODIFY COLUMN last_job_time DATETIME(3) DEFAULT NULL AFTER erase_mode,
    MODIFY COLUMN present DATETIME DEFAULT NULL AFTER last_job_time,
    MODIFY COLUMN present_bool BOOLEAN DEFAULT NULL AFTER present,
    MODIFY COLUMN status VARCHAR(128) DEFAULT NULL AFTER present_bool,
    MODIFY COLUMN battery_charge TINYINT DEFAULT NULL AFTER status,
    MODIFY COLUMN kernel_updated BOOLEAN DEFAULT NULL AFTER battery_charge,
    MODIFY COLUMN battery_status VARCHAR(20) DEFAULT NULL AFTER kernel_updated,
    MODIFY COLUMN uptime INT DEFAULT NULL AFTER battery_status,
    MODIFY COLUMN cpu_temp TINYINT DEFAULT NULL AFTER uptime,
    MODIFY COLUMN disk_temp TINYINT DEFAULT NULL AFTER cpu_temp,
    MODIFY COLUMN watts_now SMALLINT DEFAULT NULL AFTER disk_temp,
    MODIFY COLUMN network_speed SMALLINT DEFAULT NULL AFTER watts_now;


DROP table IF EXISTS logins;
CREATE TABLE IF NOT EXISTS logins (
    username VARCHAR(64) NOT NULL PRIMARY KEY,
    password VARCHAR(128) NOT NULL,
    name VARCHAR(36) NOT NULL
);

INSERT INTO logins
    (
        username,
        password,
        name
    )
    VALUES
    ('caraschk', 'UHouston!', 'Cameron'),
    ('mrharvey', 'UHouston!', 'Matthew'),
    ('ama', 'UHouston!', 'Amy'),
    ('kvu', 'UHouston!', 'Kevin'),
    ('rcarroyo', 'UHouston!', 'Rafael'),
    ('isdavis', 'UHouston!', 'Ivey'),
    ('hdtrin2', 'UHouston!', 'Haley');


CREATE TABLE IF NOT EXISTS system_data (
    tagnumber VARCHAR(8) NOT NULL PRIMARY KEY,
    wifi_mac VARCHAR(17) DEFAULT NULL,
    system_manufacturer VARCHAR(24) DEFAULT NULL,
    system_model VARCHAR(64) DEFAULT NULL,
    system_uuid VARCHAR(64) DEFAULT NULL,
    system_sku VARCHAR(20) DEFAULT NULL,
    chassis_type VARCHAR(16) DEFAULT NULL,
    cpu_manufacturer VARCHAR(20) DEFAULT NULL,
    cpu_model VARCHAR(46) DEFAULT NULL,
    cpu_maxspeed SMALLINT DEFAULT NULL,
    cpu_cores TINYINT DEFAULT NULL,
    cpu_threads TINYINT DEFAULT NULL,
    motherboard_manufacturer VARCHAR(24) DEFAULT NULL,
    motherboard_serial VARCHAR(24) DEFAULT NULL,
    time DATETIME(3) DEFAULT NULL
);

ALTER TABLE system_data
    DROP PRIMARY KEY,
    MODIFY COLUMN tagnumber VARCHAR(8) NOT NULL PRIMARY KEY FIRST,
    MODIFY COLUMN wifi_mac VARCHAR(17) DEFAULT NULL AFTER tagnumber,
    MODIFY COLUMN system_manufacturer VARCHAR(24) DEFAULT NULL AFTER wifi_mac,
    MODIFY COLUMN system_model VARCHAR(64) DEFAULT NULL AFTER system_manufacturer,
    MODIFY COLUMN system_uuid VARCHAR(64) DEFAULT NULL AFTER system_model,
    MODIFY COLUMN system_sku VARCHAR(20) DEFAULT NULL AFTER system_uuid,
    MODIFY COLUMN chassis_type VARCHAR(16) DEFAULT NULL AFTER system_sku,
    MODIFY COLUMN cpu_manufacturer VARCHAR(20) DEFAULT NULL AFTER chassis_type,
    MODIFY COLUMN cpu_model VARCHAR(46) DEFAULT NULL AFTER cpu_manufacturer,
    MODIFY COLUMN cpu_maxspeed SMALLINT DEFAULT NULL AFTER cpu_model,
    MODIFY COLUMN cpu_cores TINYINT DEFAULT NULL AFTER cpu_maxspeed,
    MODIFY COLUMN cpu_threads TINYINT DEFAULT NULL AFTER cpu_cores,
    MODIFY COLUMN motherboard_manufacturer VARCHAR(24) DEFAULT NULL AFTER cpu_threads,
    MODIFY COLUMN motherboard_serial VARCHAR(24) DEFAULT NULL AFTER motherboard_manufacturer,
    MODIFY COLUMN time DATETIME(3) DEFAULT NULL AFTER motherboard_serial
    ;

DROP TABLE IF EXISTS bitlocker;
CREATE TABLE IF NOT EXISTS bitlocker (
    tagnumber VARCHAR(8) NOT NULL PRIMARY KEY,
    identifier VARCHAR(128) NOT NULL,
    recovery_key VARCHAR(128) NOT NULL
);

INSERT INTO bitlocker
(
    tagnumber,
    identifier,
    recovery_key
) VALUES
('724020', '94A6BC65-EBC1-4BF7-9B6B-45FE14102851', '051139-186978-171864-593648-529529-634975-122133-502480'),
('724021', 'A6E98E82-2A03-4BF6-98C7-DF545A7745D5', '143429-284900-250481-657690-514668-500269-430419-492866'),
('724025', '18741871-6DE8-41CE-AF96-7026F4AF12B5', '313247-612755-597828-287078-399916-220363-401357-318516'),
('727005', '956EAC0D-1E93-4F8A-95EE-96F4B0CE9CC1', '568172-561781-653939-211431-673860-559493-507177-527901'),
('727006', '5FEB2D5C-5A3C-43D5-ABDD-2FF2D7F000E9', '209869-405218-331342-499939-399025-101068-711370-621940'),
('727007', 'E78C562F-A1D7-409E-9D1A-F8F4DAD7CAB3', '453189-289113-568480-026411-025366-143473-390137-104456'),
('727008', 'ABD175B0-F20B-4A04-BEA8-60D69214DDD2', '141570-153043-215072-647757-021956-291082-215149-107910'),
('727009', '6ECFDB34-5334-4452-8F86-E9043FED3C05', '346841-378015-041173-326898-155243-358083-076736-553894'),
('727010', 'E21543FA-CE92-4BDE-AE90-4C713E0ACFEF', '502821-236709-657030-654819-639243-351824-278762-099869'),
('727011', '3536669E-2D87-4F89-AA77-9289B987A97C', '123651-645084-470547-555709-391039-523380-469370-552761'),
('727012', 'C3373E7C-F19A-4AFA-BDE9-320431B1DD80', '655930-154462-345026-620345-020020-002761-693352-541145'),
('727013', '7C23FCCC-8430-405F-BDDE-23B149E1B926', '153736-395956-001419-128854-250800-440308-637593-212146'),
('727014', '0761161E-23A5-461B-822D-6D795B7253DB', '100870-342463-572495-557854-219527-118217-661925-117348'),
('625806', 'CBF86C66-7178-4708-BB33-E9377DA3FED1', '515812-612612-609510-498311-368049-512424-262372-073986')
;


DROP TABLE IF EXISTS static_tags;
CREATE TABLE IF NOT EXISTS static_tags (
    tag VARCHAR(128) NOT NULL,
    tag_readable VARCHAR(128) NOT NULL,
    owner VARCHAR(64) NOT NULL,
    department VARCHAR(128) NOT NULL
);

INSERT INTO static_tags (
    tag,
    tag_readable,
    owner,
    department
) VALUES 
    ('laptop-program', 'Laptop Program', 'Matthew Harvey', 'techComm'),
    ('checked-in', 'Checked In', 'Matthew Harvey', 'techComm'),
    ('checked-out', 'Checked Out', 'Matthew Harvey', 'techComm'),
    ('stolen', 'Stolen/Missing', 'Matthew Harvey', 'techComm'),
    ('ITSC-Computers', 'ITSC Team Leads', 'Kevin Vu', 'techComm'),
    ('tv', 'Televisions', 'Kevin Vu', 'techComm'),
    ('security-cameras', 'Security Cameras', 'Kevin Vu', 'techComm'),
    ('student-workstations', 'Student Workstations', 'Kevin Vu', 'techComm'),
    ('printers', 'Printers', 'Tom Carroll', 'o365'),
    ('uniprint-clients', 'Uniprint Release Stations', 'Tom Carroll', 'o365')
    ;


CREATE TABLE IF NOT EXISTS tags (
    tagnumber VARCHAR(128) NOT NULL,
    tag VARCHAR(128) NOT NULL
);


DROP TABLE IF EXISTS static_departments;
CREATE TABLE IF NOT EXISTS static_departments (
  department VARCHAR(128) NOT NULL PRIMARY KEY,
  department_readable VARCHAR(128) NOT NULL,
  owner VARCHAR(64) NOT NULL,
  department_bool BOOLEAN NOT NULL DEFAULT 0
);

INSERT INTO static_departments (
  department,
  department_readable,
  owner,
  department_bool
) VALUES
  ('techComm', 'Tech Commons', 'Matthew Harvey', 1),
  ('execSupport', 'Exec. Support', 'Kevin Vu', 1),
  ('shrl', 'SHRL', 'Alex Tran', 1),
  ('pre-property', 'Pre-Property', 'Matthew Harvey', 0),
  ('o365', 'Office 365', 'Andy Moon', 0),
  ('property', 'Property', 'Unknown', 0)
;

-- Departments table --
CREATE TABLE IF NOT EXISTS departments (
    time DATETIME(3) NOT NULL PRIMARY KEY,
    tagnumber VARCHAR(8) NOT NULL,
    system_serial VARCHAR(24) DEFAULT NULL,
    department VARCHAR(32) DEFAULT NULL,
    subdepartment VARCHAR(64) DEFAULT NULL
);

ALTER TABLE departments
    DROP PRIMARY KEY,
    MODIFY COLUMN time DATETIME(3) NOT NULL PRIMARY KEY FIRST,
    MODIFY COLUMN tagnumber VARCHAR(8) DEFAULT NULL AFTER time,
    MODIFY COLUMN system_serial VARCHAR(24) DEFAULT NULL AFTER tagnumber,
    MODIFY COLUMN department VARCHAR(32) DEFAULT NULL AFTER system_serial,
    MODIFY COLUMN subdepartment VARCHAR(64) DEFAULT NULL AFTER department;

DROP TABLE IF EXISTS static_job_names;
CREATE TABLE IF NOT EXISTS static_job_names (
  job VARCHAR(24) NOT NULL PRIMARY KEY,
  job_readable VARCHAR(24) DEFAULT NULL,
  job_rank TINYINT DEFAULT NULL,
  job_html_bool BOOLEAN DEFAULT NULL
);

INSERT INTO 
    static_job_names (job, job_readable, job_rank, job_html_bool)
VALUES 
    ('update', 'Update', 20, 1),
    ('findmy', 'Play Sound', 30, 1),
    ('hpEraseAndClone', 'Erase and Clone', 40, 1),
    ('generic-erase+clone', 'Erase and Clone (manual)', 41, 0),
    ('hpCloneOnly', 'Clone Only', 50, 1),
    ('generic-clone', 'Clone Only (manual)', 51, 0),
    ('nvmeErase', 'Erase Only', 60, 1),
    ('generic-erase', 'Erase Only (manual)', 61, 0),
    ('nvmeVerify', 'Verify Erase', 70, 0),
    ('data collection', 'Data Collection', 80, 0),
    ('shutdown', 'Shutdown', 90, 1),
    ('cancel', 'Cancel/Clear Job(s)', 95, 1)
    ;


DROP TABLE IF EXISTS static_domains;
CREATE TABLE IF NOT EXISTS static_domains (
  domain VARCHAR(24) NOT NULL PRIMARY KEY,
  domain_readable VARCHAR(24) DEFAULT NULL
);

INSERT INTO 
    static_domains (domain, domain_readable)
VALUES
    ('UIT-CheckOut', 'TSS Laptop Checkout')
    ;


DROP TABLE IF EXISTS static_image_names;
CREATE TABLE IF NOT EXISTS static_image_names (
    image_name VARCHAR(36) NOT NULL PRIMARY KEY,
    image_os_author VARCHAR(24) DEFAULT NULL,
    image_version VARCHAR(24) DEFAULT NULL,
    image_platform_vendor VARCHAR(24) DEFAULT NULL,
    image_platform_model VARCHAR(24) DEFAULT NULL,
    image_name_readable VARCHAR(36) DEFAULT NULL
);

INSERT INTO
    static_image_names (image_name, image_os_author, image_version, image_platform_vendor, image_platform_model, image_name_readable)
VALUES
    ('TechCommons-HP-LaptopsLZ4', 'Microsoft', 'Windows 11', 'HP', 'HP ProBook 450 G6', 'Windows 11'),
    ('TechCommons-Dell-Laptops', 'Microsoft', 'Windows 11', 'Dell', 'Latitude 7400', 'Windows 11'),
    ('TechCommons-Dell-Desktops', 'Microsoft', 'Windows 11', 'Dell', 'OptiPlex 7000', 'Windows 11'),
    ('TechCommons-Dell-HelpDesk', 'Microsoft', 'Windows 11', 'Dell', 'Latitude 7420', 'Windows 11'),
    ('SHRL-Dell-Desktops', 'Microsoft', 'Windows 11', 'Dell', NULL, 'Windows 11'),
    ('Ubuntu-Desktop', 'Canonical', '24.04.2 LTS', 'Dell', NULL, 'Ubuntu Desktop')
    ;


CREATE TABLE IF NOT EXISTS notes (
    time DATETIME(3) NOT NULL PRIMARY KEY,
    todo TEXT DEFAULT NULL,
    projects TEXT DEFAULT NULL,
    misc TEXT DEFAULT NULL,
    bugs TEXT DEFAULT NULL
);

DROP TABLE IF EXISTS static_notes;
CREATE TABLE IF NOT EXISTS static_notes (
    note VARCHAR(64) PRIMARY KEY,
    note_readable VARCHAR(64) NOT NULL,
    sort_order TINYINT DEFAULT NULL
);

INSERT INTO static_notes (note, note_readable, sort_order) VALUES 
    ('todo', 'Short-Term', 10),
    ('projects', 'Projects', 20),
    ('misc', 'Misc. Notes', 30),
    ('bugs', 'Software Bugs 🐛', 40)
;


CREATE TABLE IF NOT EXISTS checkout (
    time DATETIME(3) NOT NULL PRIMARY KEY,
    tagnumber VARCHAR(6) DEFAULT NULL,
    customer_name VARCHAR(48) DEFAULT NULL,
    customer_psid VARCHAR(24) DEFAULT NULL,
    checkout_bool BOOLEAN DEFAULT NULL,
    checkout_date DATE DEFAULT NULL,
    return_date DATE DEFAULT NULL,
    checkout_group VARCHAR(48) DEFAULT NULL,
    note VARCHAR(128) DEFAULT NULL
);

ALTER TABLE checkout
    DROP PRIMARY KEY,
    MODIFY COLUMN time DATETIME(3) NOT NULL PRIMARY KEY,
    MODIFY COLUMN tagnumber VARCHAR(6) DEFAULT NULL,
    MODIFY COLUMN customer_name VARCHAR(48) DEFAULT NULL,
    MODIFY COLUMN customer_psid VARCHAR(24) DEFAULT NULL,
    MODIFY COLUMN checkout_bool BOOLEAN DEFAULT NULL,
    MODIFY COLUMN checkout_date DATE DEFAULT NULL,
    MODIFY COLUMN return_date DATE DEFAULT NULL,
    MODIFY COLUMN checkout_group VARCHAR(48) DEFAULT NULL,
    MODIFY COLUMN note VARCHAR(128) DEFAULT NULL
    ;

DROP TABLE IF EXISTS static_emojis;
CREATE TABLE IF NOT EXISTS static_emojis (
    keyword VARCHAR(64) PRIMARY KEY,
    regex VARCHAR(64) DEFAULT NULL,
    replacement VARCHAR(64) DEFAULT NULL,
    text_bool BOOLEAN DEFAULT NULL,
    case_sensitive_bool BOOLEAN DEFAULT NULL
);

INSERT INTO static_emojis (keyword, regex, replacement, text_bool, case_sensitive_bool) VALUES 
    (':)', '\\:\\)', '😀', NULL, NULL),
    (':D', '\\:D\\)', '😁', NULL, 1),
    (';)', '\\;\\)', '😉', NULL, NULL),
    (':P', '\\:P', '😋', NULL, NULL),
    (':|', '\\:\\|', '😑', NULL, NULL),
    (':0', '\\:0', '😲', NULL, NULL),
    (':O', '\\:O', '😲', NULL, NULL),
    (':(', '\\:\\(', '😞', NULL, NULL),
    (':<', '\\:\\<', '😡', NULL, NULL),
    (':\\', '\\:\\\\', '😕', NULL, NULL),
    (';(', '\\;\\(', '😢', NULL, NULL),
    ('check', '\\:check', '✅', 1, 1),
    ('done', '\\:done', '✅', 1, 1),
    ('x', '\\:x', "❌", 1, NULL),
    ('cancel', '\\:cancel', "🚫", 1, 1),
    ('working', '\\:working', "⌛", 1, 1),
    ('waiting', '\\:waiting', "⌛", 1, 1),
    ('inprogress', '\\:inprogress', '⌛', 1, 1),
    ('shurg', '\\:shrug', "🤷", 1, 1),
    ('clock', '\\:clock', "🕓", 1, 1),
    ('warning', '\\:warning', "⚠️", 1, 1),
    ('arrow', '\\:arrow', "⏩", 1, 1),
    ('bug', '\\:bug', "🐛", 1, 1),
    ('poop', '\\:poop', "💩", 1, 1),
    ('star', '\\:star', "⭐", 1, 1),
    ('heart', '\\:heart', "❤️", 1, 1),
    ('love', '\\:love', "❤️", 1, 1),
    ('fire', '\\:fire', "🔥", 1, 1),
    ('like', '\\:like', "👍", 1, 1),
    ('dislike', '\\:dislike', "👎", 1, 1),
    ('info', '\\:info', "ℹ️", 1, 1),
    ('pin', '\\:pin', "📌", 1, 1),
    ('clap', '\\:clap', "👏", 1, 1),
    ('celebrate', '\\:celebrate', "🥳", 1, 1),
    ('hmm', '\\:hmm', "🤔", 1, 1),
    ('alert', '\\:alert', "🚨", 1, 1),
    ('wow', '\\:wow', '🤯', 1, 1),
    ('shock', '\\:shock', '⚡', 1, 1)
;