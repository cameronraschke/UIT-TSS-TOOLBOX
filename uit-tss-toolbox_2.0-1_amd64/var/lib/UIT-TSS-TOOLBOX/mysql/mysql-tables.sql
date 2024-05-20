DROP TABLE IF EXISTS serverstats;
CREATE TABLE serverstats (
    date DATE NOT NULL PRIMARY KEY,
    laptop_count SMALLINT DEFAULT NULL,
    disk_health DECIMAL(5,2) DEFAULT NULL,
    battery_health DECIMAL(5,2) DEFAULT NULL,
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
    tagnumber MEDIUMINT NOT NULL PRIMARY KEY,
    system_serial VARCHAR(12) DEFAULT NULL,
    system_model VARCHAR(20) DEFAULT NULL,
    last_job_time DATETIME DEFAULT NULL,
    battery_health SMALLINT DEFAULT NULL,
    disk_health DECIMAL(5,2) DEFAULT NULL,
    disk_type VARCHAR(8) DEFAULT NULL,
    bios_version VARCHAR(24) DEFAULT NULL,
    erase_avgtime SMALLINT DEFAULT NULL,
    clone_avgtime TINYINT DEFAULT NULL,
    all_jobs SMALLINT DEFAULT NULL
);


CREATE TABLE IF NOT EXISTS jobstats (
    uuid VARCHAR(45) NOT NULL PRIMARY KEY,
    tagnumber MEDIUMINT DEFAULT NULL AFTER uuid,
    etheraddress VARCHAR(17) DEFAULT NULL AFTER tagnumber,
    wifi_mac VARCHAR(17) DEFAULT NULL AFTER etheraddress,
    date DATE DEFAULT NULL AFTER wifi_mac,
    time DATETIME(3) DEFAULT NULL AFTER date,
    department VARCHAR(8) DEFAULT NULL AFTER time,
    system_manufacturer VARCHAR(12) DEFAULT NULL AFTER department,
    system_model VARCHAR(20) DEFAULT NULL AFTER system_manufacturer,
    system_serial VARCHAR(12) DEFAULT NULL AFTER system_model,
    system_uuid VARCHAR(36) DEFAULT NULL AFTER system_serial,
    system_sku VARCHAR(16) DEFAULT NULL AFTER system_uuid,
    chassis_type VARCHAR(8) DEFAULT NULL AFTER system_sku,
    disk VARCHAR(8) DEFAULT NULL AFTER chassis_type,
    disk_model VARCHAR(32) DEFAULT NULL AFTER disk,
    disk_type VARCHAR(4) DEFAULT NULL AFTER disk_model,
    disk_size SMALLINT DEFAULT NULL AFTER disk_type,
    disk_serial VARCHAR(32) DEFAULT NULL AFTER disk_size,
    disk_writes DECIMAL(5,2) DEFAULT NULL AFTER disk_serial,
    disk_reads DECIMAL(5,2) DEFAULT NULL AFTER disk_writes,
    disk_power_on_hours MEDIUMINT DEFAULT NULL AFTER disk_reads,
    disk_temp TINYINT DEFAULT NULL AFTER disk_power_on_hours,
    disk_firmware VARCHAR(10) DEFAULT NULL AFTER disk_temp,
    battery_name VARCHAR(16) DEFAULT NULL AFTER disk_firmware,
    battery_serial VARCHAR(8) DEFAULT NULL AFTER battery_name,
    battery_health TINYINT DEFAULT NULL AFTER battery_serial,
    battery_charge_cycles SMALLINT DEFAULT NULL AFTER battery_health,
    battery_capacity MEDIUMINT DEFAULT NULL AFTER battery_charge_cycles,
    battery_manufacturedate DATE DEFAULT NULL AFTER battery_capacity,
    cpu_manufacturer VARCHAR(20) DEFAULT NULL AFTER battery_manufacturedate,
    cpu_model VARCHAR(46) DEFAULT NULL AFTER cpu_manufacturer,
    cpu_maxspeed SMALLINT DEFAULT NULL AFTER cpu_model,
    cpu_cores TINYINT DEFAULT NULL AFTER cpu_maxspeed,
    cpu_threads TINYINT DEFAULT NULL AFTER cpu_cores,
    cpu_temp DECIMAL(5,2) DEFAULT NULL AFTER cpu_threads,
    motherboard_manufacturer VARCHAR(16) DEFAULT NULL AFTER cpu_temp,
    motherboard_serial VARCHAR(24) DEFAULT NULL AFTER motherboard_manufacturer,
    bios_version VARCHAR(24) DEFAULT NULL AFTER motherboard_serial,
    bios_date VARCHAR(12) DEFAULT NULL AFTER bios_version,
    bios_firmware VARCHAR(8) DEFAULT NULL AFTER bios_date,
    ram_serial VARCHAR(24) DEFAULT NULL AFTER bios_firmware,
    ram_capacity DECIMAL(4,2) DEFAULT NULL AFTER ram_serial,
    ram_speed SMALLINT DEFAULT NULL AFTER ram_capacity,
    cpu_usage DECIMAL(6,2) DEFAULT NULL AFTER ram_speed,
    network_usage DECIMAL(5,2) DEFAULT NULL AFTER cpu_usage,
    boot_time DECIMAL(5,2) DEFAULT NULL AFTER network_usage,
    erase_completed VARCHAR(3) DEFAULT NULL AFTER boot_time,
    erase_mode VARCHAR(24) DEFAULT NULL AFTER erase_completed,
    erase_diskpercent TINYINT DEFAULT NULL AFTER erase_mode,
    erase_time SMALLINT DEFAULT NULL AFTER erase_diskpercent,
    clone_completed VARCHAR(3) DEFAULT NULL AFTER erase_time,
    clone_master VARCHAR(3) DEFAULT NULL AFTER clone_completed,
    clone_time SMALLINT DEFAULT NULL AFTER clone_master
);

ALTER TABLE jobstats
    DROP PRIMARY KEY,
    MODIFY COLUMN uuid VARCHAR(45) NOT NULL PRIMARY KEY,
    MODIFY COLUMN tagnumber MEDIUMINT DEFAULT NULL AFTER uuid,
    MODIFY COLUMN etheraddress VARCHAR(17) DEFAULT NULL AFTER tagnumber,
    MODIFY COLUMN wifi_mac VARCHAR(17) DEFAULT NULL AFTER etheraddress,
    MODIFY COLUMN date DATE DEFAULT NULL AFTER wifi_mac,
    MODIFY COLUMN time DATETIME(3) DEFAULT NULL AFTER date,
    MODIFY COLUMN department VARCHAR(8) DEFAULT NULL AFTER time,
    MODIFY COLUMN system_manufacturer VARCHAR(12) DEFAULT NULL AFTER department,
    MODIFY COLUMN system_model VARCHAR(20) DEFAULT NULL AFTER system_manufacturer,
    MODIFY COLUMN system_serial VARCHAR(12) DEFAULT NULL AFTER system_model,
    MODIFY COLUMN system_uuid VARCHAR(36) DEFAULT NULL AFTER system_serial,
    MODIFY COLUMN system_sku VARCHAR(16) DEFAULT NULL AFTER system_uuid,
    MODIFY COLUMN chassis_type VARCHAR(8) DEFAULT NULL AFTER system_sku,
    MODIFY COLUMN disk VARCHAR(8) DEFAULT NULL AFTER chassis_type,
    MODIFY COLUMN disk_model VARCHAR(32) DEFAULT NULL AFTER disk,
    MODIFY COLUMN disk_type VARCHAR(4) DEFAULT NULL AFTER disk_model,
    MODIFY COLUMN disk_size SMALLINT DEFAULT NULL AFTER disk_type,
    MODIFY COLUMN disk_serial VARCHAR(32) DEFAULT NULL AFTER disk_size,
    MODIFY COLUMN disk_writes DECIMAL(5,2) DEFAULT NULL AFTER disk_serial,
    MODIFY COLUMN disk_reads DECIMAL(5,2) DEFAULT NULL AFTER disk_writes,
    MODIFY COLUMN disk_power_on_hours MEDIUMINT DEFAULT NULL AFTER disk_reads,
    MODIFY COLUMN disk_temp TINYINT DEFAULT NULL AFTER disk_power_on_hours,
    MODIFY COLUMN disk_firmware VARCHAR(10) DEFAULT NULL AFTER disk_temp,
    MODIFY COLUMN battery_name VARCHAR(16) DEFAULT NULL AFTER disk_firmware,
    MODIFY COLUMN battery_serial VARCHAR(8) DEFAULT NULL AFTER battery_name,
    MODIFY COLUMN battery_health TINYINT DEFAULT NULL AFTER battery_serial,
    MODIFY COLUMN battery_charge_cycles SMALLINT DEFAULT NULL AFTER battery_health,
    MODIFY COLUMN battery_capacity MEDIUMINT DEFAULT NULL AFTER battery_charge_cycles,
    MODIFY COLUMN battery_manufacturedate DATE DEFAULT NULL AFTER battery_capacity,
    MODIFY COLUMN cpu_manufacturer VARCHAR(20) DEFAULT NULL AFTER battery_manufacturedate,
    MODIFY COLUMN cpu_model VARCHAR(46) DEFAULT NULL AFTER cpu_manufacturer,
    MODIFY COLUMN cpu_maxspeed SMALLINT DEFAULT NULL AFTER cpu_model,
    MODIFY COLUMN cpu_cores TINYINT DEFAULT NULL AFTER cpu_maxspeed,
    MODIFY COLUMN cpu_threads TINYINT DEFAULT NULL AFTER cpu_cores,
    MODIFY COLUMN cpu_temp DECIMAL(5,2) DEFAULT NULL AFTER cpu_threads,
    MODIFY COLUMN motherboard_manufacturer VARCHAR(16) DEFAULT NULL AFTER cpu_temp,
    MODIFY COLUMN motherboard_serial VARCHAR(24) DEFAULT NULL AFTER motherboard_manufacturer,
    MODIFY COLUMN bios_version VARCHAR(24) DEFAULT NULL AFTER motherboard_serial,
    MODIFY COLUMN bios_date VARCHAR(12) DEFAULT NULL AFTER bios_version,
    MODIFY COLUMN bios_firmware VARCHAR(8) DEFAULT NULL AFTER bios_date,
    MODIFY COLUMN ram_serial VARCHAR(24) DEFAULT NULL AFTER bios_firmware,
    MODIFY COLUMN ram_capacity DECIMAL(4,2) DEFAULT NULL AFTER ram_serial,
    MODIFY COLUMN ram_speed SMALLINT DEFAULT NULL AFTER ram_capacity,
    MODIFY COLUMN cpu_usage DECIMAL(6,2) DEFAULT NULL AFTER ram_speed,
    MODIFY COLUMN network_usage DECIMAL(5,2) DEFAULT NULL AFTER cpu_usage,
    MODIFY COLUMN boot_time DECIMAL(5,2) DEFAULT NULL AFTER network_usage,
    MODIFY COLUMN erase_completed VARCHAR(3) DEFAULT NULL AFTER boot_time,
    MODIFY COLUMN erase_mode VARCHAR(24) DEFAULT NULL AFTER erase_completed,
    MODIFY COLUMN erase_diskpercent TINYINT DEFAULT NULL AFTER erase_mode,
    MODIFY COLUMN erase_time SMALLINT DEFAULT NULL AFTER erase_diskpercent,
    MODIFY COLUMN clone_completed VARCHAR(3) DEFAULT NULL AFTER erase_time,
    MODIFY COLUMN clone_master VARCHAR(3) DEFAULT NULL AFTER clone_completed,
    MODIFY COLUMN clone_time SMALLINT DEFAULT NULL AFTER clone_master;


CREATE TABLE IF NOT EXISTS locations (
    time DATETIME(3) NOT NULL PRIMARY KEY,
    tagnumber MEDIUMINT DEFAULT NULL,
    system_serial VARCHAR(16) DEFAULT NULL,
    location VARCHAR(128) DEFAULT NULL,
    status BOOLEAN DEFAULT NULL,
    note VARCHAR(256) DEFAULT NULL
);

ALTER TABLE locations
    DROP PRIMARY KEY,
    MODIFY COLUMN time DATETIME(3) NOT NULL PRIMARY KEY,
    MODIFY COLUMN tagnumber MEDIUMINT DEFAULT NULL AFTER time,
    MODIFY COLUMN system_serial VARCHAR(16) DEFAULT NULL AFTER tagnumber,
    MODIFY COLUMN location VARCHAR(128) DEFAULT NULL AFTER system_serial,
    MODIFY COLUMN status BOOLEAN DEFAULT NULL AFTER location,
    MODIFY COLUMN note VARCHAR(256) DEFAULT NULL AFTER status;


DROP TABLE IF EXISTS static_disk_stats;
CREATE TABLE IF NOT EXISTS static_disk_stats (
    disk_model VARCHAR(128) NOT NULL PRIMARY KEY,
    disk_write_speed SMALLINT DEFAULT NULL,
    disk_read_speed SMALLINT DEFAULT NULL,
    disk_mtbf MEDIUMINT DEFAULT NULL,
    disk_tbw SMALLINT DEFAULT NULL,
    disk_tbr SMALLINT DEFAULT NULL,
    min_temp SMALLINT DEFAULT NULL,
    max_temp SMALLINT DEFAULT NULL,
    disk_interface VARCHAR(8) DEFAULT NULL,
    disk_type VARCHAR(8) DEFAULT NULL,
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
    ('MTFDHBA256TCK-1AS1AABHA','3000','1600','2000000','75',NULL,NULL,NULL,'m.2','nvme','0', NULL, NULL),
    ('SSDPEMKF256G8 NVMe INTEL 256GB','3210','1315','1600000','144',NULL,'0','70','m.2','nvme','0',NULL,NULL),
    ('ST500LM034-2GH17A','160','160',NULL,'55','55','0','60','sata','hdd','1','7200','600000'),
    ('TOSHIBA MQ01ACF050',NULL,NULL,'600000',NULL,NULL,'5','55','sata','hdd','1','7200',NULL),
    ('WDC PC SN520 SDAPNUW-256G-1006','1300','1700','1752000','200',NULL,'0','70','m.2','nvme','0',NULL,NULL);


DROP TABLE IF EXISTS static_battery_stats;
CREATE TABLE IF NOT EXISTS static_battery_stats (
    battery_name VARCHAR(24) NOT NULL PRIMARY KEY,
    battery_charge_cycles SMALLINT DEFAULT NULL
);

INSERT INTO static_battery_stats
    (battery_name,
    battery_charge_cycles
    )
VALUES 
    ('RE03045XL','300'),
    ('DELL VN3N047','300'),
    ('DELL N2K6205','300'),
    ('DELL 1VX1H93','300'),
    ('DELL W7NKD85','300');
