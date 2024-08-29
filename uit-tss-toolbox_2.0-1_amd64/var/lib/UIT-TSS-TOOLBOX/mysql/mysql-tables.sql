DROP TABLE IF EXISTS serverstats;
CREATE TABLE serverstats (
    date DATE NOT NULL PRIMARY KEY,
    client_count SMALLINT DEFAULT NULL,
    battery_health DECIMAL(5,2) DEFAULT NULL,
    disk_health DECIMAL(5,2) DEFAULT NULL,
    all_jobs MEDIUMINT DEFAULT NULL,
    clone_jobs MEDIUMINT DEFAULT NULL,
    erase_jobs MEDIUMINT DEFAULT NULL,
    clone_avgtime SMALLINT DEFAULT NULL,
    nvme_erase_avgtime SMALLINT DEFAULT NULL,
    hdd_erase_avgtime SMALLINT DEFAULT NULL,
    last_image_update DATE DEFAULT NULL
);


DROP TABLE IF EXISTS clientstats;
CREATE TABLE clientstats (
    tagnumber VARCHAR(8) NOT NULL PRIMARY KEY,
    system_serial VARCHAR(24) DEFAULT NULL,
    system_model VARCHAR(20) DEFAULT NULL,
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
    uuid VARCHAR(45) NOT NULL,
    tagnumber VARCHAR(8) DEFAULT NULL,
    etheraddress VARCHAR(17) DEFAULT NULL,
    date DATE DEFAULT NULL,
    time DATETIME(3) DEFAULT NULL,
    department VARCHAR(8) DEFAULT NULL,
    system_serial VARCHAR(24) DEFAULT NULL,
    disk VARCHAR(8) DEFAULT NULL,
    disk_model VARCHAR(36) DEFAULT NULL,
    disk_type VARCHAR(4) DEFAULT NULL,
    disk_size SMALLINT DEFAULT NULL,
    disk_serial VARCHAR(32) DEFAULT NULL,
    disk_writes DECIMAL(5,2) DEFAULT NULL,
    disk_reads DECIMAL(5,2) DEFAULT NULL,
    disk_power_on_hours MEDIUMINT DEFAULT NULL,
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
    clone_master BOOLEAN DEFAULT NULL,
    clone_time SMALLINT DEFAULT NULL,
    host_connected BOOLEAN DEFAULT NULL
);

ALTER TABLE jobstats
    DROP PRIMARY KEY,
    MODIFY COLUMN uuid VARCHAR(45) NOT NULL PRIMARY KEY FIRST,
    MODIFY COLUMN tagnumber VARCHAR(8) DEFAULT NULL AFTER uuid,
    MODIFY COLUMN etheraddress VARCHAR(17) DEFAULT NULL AFTER tagnumber,
    MODIFY COLUMN date DATE DEFAULT NULL AFTER etheraddress,
    MODIFY COLUMN time DATETIME(3) DEFAULT NULL AFTER date,
    MODIFY COLUMN department VARCHAR(8) DEFAULT NULL AFTER time,
    MODIFY COLUMN system_serial VARCHAR(24) DEFAULT NULL AFTER department,
    MODIFY COLUMN disk VARCHAR(8) DEFAULT NULL AFTER system_serial,
    MODIFY COLUMN disk_model VARCHAR(36) DEFAULT NULL AFTER disk,
    MODIFY COLUMN disk_type VARCHAR(4) DEFAULT NULL AFTER disk_model,
    MODIFY COLUMN disk_size SMALLINT DEFAULT NULL AFTER disk_type,
    MODIFY COLUMN disk_serial VARCHAR(32) DEFAULT NULL AFTER disk_size,
    MODIFY COLUMN disk_writes DECIMAL(5,2) DEFAULT NULL AFTER disk_serial,
    MODIFY COLUMN disk_reads DECIMAL(5,2) DEFAULT NULL AFTER disk_writes,
    MODIFY COLUMN disk_power_on_hours MEDIUMINT DEFAULT NULL AFTER disk_reads,
    MODIFY COLUMN disk_temp TINYINT DEFAULT NULL AFTER disk_power_on_hours,
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
    MODIFY COLUMN clone_master BOOLEAN DEFAULT NULL AFTER clone_completed,
    MODIFY COLUMN clone_time SMALLINT DEFAULT NULL AFTER clone_master,
    MODIFY COLUMN host_connected BOOLEAN DEFAULT NULL AFTER clone_time;


CREATE TABLE IF NOT EXISTS locations (
    time DATETIME(3) NOT NULL,
    tagnumber VARCHAR(8) DEFAULT NULL,
    system_serial VARCHAR(24) DEFAULT NULL,
    location VARCHAR(128) DEFAULT NULL,
    disk_removed BOOLEAN DEFAULT NULL,
    os_installed BOOLEAN DEFAULT NULL,
    bios_updated BOOLEAN DEFAULT NULL,
    note VARCHAR(256) DEFAULT NULL
);

ALTER TABLE locations
    DROP PRIMARY KEY,
    MODIFY COLUMN time DATETIME(3) NOT NULL PRIMARY KEY FIRST,
    MODIFY COLUMN tagnumber VARCHAR(8) DEFAULT NULL AFTER time,
    MODIFY COLUMN system_serial VARCHAR(24) DEFAULT NULL AFTER tagnumber,
    MODIFY COLUMN location VARCHAR(128) DEFAULT NULL AFTER system_serial,
    MODIFY COLUMN status BOOLEAN DEFAULT NULL AFTER location,
    MODIFY COLUMN os_installed BOOLEAN DEFAULT NULL AFTER status,
    MODIFY COLUMN disk_removed BOOLEAN DEFAULT NULL AFTER os_installed,
    MODIFY COLUMN note VARCHAR(256) DEFAULT NULL AFTER disk_removed;


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
    ('TOSHIBA MQ01ACF050',NULL,NULL,'600000',NULL,NULL,'5','55','sata','hdd','1','7200',NULL),
    ('WDC PC SN520 SDAPNUW-256G-1006','1300','1700','1752000','200',NULL,'0','70','m.2','nvme','0',NULL,NULL),
    ('LITEON CV8-8E120-11 SATA 128GB','380','550','1500000','146',NULL,'0','70','m.2','ssd','0',NULL,'50000'),
    ('LITEON CV3-8D512-11 SATA 512GB','490','540','1500000','250',NULL,NULL,NULL,'m.2','ssd','0',NULL,NULL),
    ('TOSHIBA KSG60ZMV256G M.2 2280 256GB','340','550','1500000',NULL,NULL,'0','80','m.2','ssd','0',NULL,NULL),
    ('TOSHIBA THNSNK256GVN8 M.2 2280 256GB','388','545','1500000',NULL,NULL,NULL,NULL,'m.2','ssd','0',NULL,NULL)
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
    ('RE03045XL','300'),
    ('DELL VN3N047','300'),
    ('DELL N2K6205','300'),
    ('DELL 1VX1H93','300'),
    ('DELL W7NKD85','300');


DROP TABLE IF EXISTS static_bios_stats;
CREATE TABLE IF NOT EXISTS static_bios_stats (
    system_model VARCHAR(20) NOT NULL PRIMARY KEY,
    bios_version VARCHAR(24) DEFAULT NULL
);

INSERT INTO static_bios_stats
    (
        system_model,
        bios_version
    )
    VALUES
    ('HP ProBook 450 G6', 'R71 Ver. 01.28.00'),
    ('Latitude 7400', '1.31.0'),
    ('Latitude 3500', '1.32.0'),
    ('Latitude 3560', 'A19'),
    ('Latitude 3590', '1.26.0'),
    ('Latitude 7430', '1.23.0'),
    ('Latitude 7490', '1.38.0'),
    ('Latitude 7480', '1.37.0'),
    ('Latitude E7470', '1.36.3'),
    ('OptiPlex 9010 AIO', 'A25'),
    ('Latitude E6430', 'A24'),
    ('OptiPlex 790', 'A22'),
    ('OptiPlex 780', 'A15'),
    ('OptiPlex 7460 AIO', '1.35.0'),
    ('Latitude 5590', '1.35.0'),
    ('XPS 15 9560', '1.24.0'),
    ('Latitude 5480', '1.36.0'),
    ('Latitude 5289', '1.35.0'),
    ('Surface Book', '92.3748.768'),
    ('Aspire T3-710', 'R01-B1'),
    ('Surface Pro', NULL),
    ('Surface Pro 4', '109.3748.768'),
    ('OptiPlex 7000', '1.24.0');


CREATE TABLE IF NOT EXISTS remote (
    tagnumber VARCHAR(8) NOT NULL PRIMARY KEY,
    task VARCHAR(24) DEFAULT NULL,
    last_job_time DATETIME(3) DEFAULT NULL,
    present DATETIME DEFAULT NULL,
    present_bool BOOLEAN DEFAULT NULL,
    status VARCHAR(30) DEFAULT NULL,
    os_installed BOOLEAN DEFAULT NULL,
    bios_updated BOOLEAN DEFAULT NULL,
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
    MODIFY COLUMN task VARCHAR(24) DEFAULT NULL AFTER tagnumber,
    MODIFY COLUMN last_job_time DATETIME(3) DEFAULT NULL AFTER task,
    MODIFY COLUMN present DATETIME DEFAULT NULL AFTER date,
    MODIFY COLUMN present_bool BOOLEAN DEFAULT NULL AFTER present,
    MODIFY COLUMN status VARCHAR(30) DEFAULT NULL AFTER present_bool,
    MODIFY COLUMN os_installed BOOLEAN DEFAULT NULL AFTER status,
    MODIFY COLUMN battery_charge TINYINT DEFAULT NULL AFTER os_installed,
    MODIFY COLUMN bios_updated BOOLEAN DEFAULT NULL AFTER battery_charge,
    modify COLUMN kernel_updated BOOLEAN DEFAULT NULL AFTER bios_updated,
    MODIFY COLUMN battery_status VARCHAR(20) DEFAULT NULL AFTER bios_updated,
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
    system_model VARCHAR(20) DEFAULT NULL,
    system_uuid VARCHAR(36) DEFAULT NULL,
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