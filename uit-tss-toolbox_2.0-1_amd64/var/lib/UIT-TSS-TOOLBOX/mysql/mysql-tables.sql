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
    system_serial VARCHAR(16) DEFAULT NULL,
    system_model VARCHAR(24) DEFAULT NULL,
    last_job_time DATETIME DEFAULT NULL,
    battery_health DECIMAL(5,2) DEFAULT NULL,
    disk_health DECIMAL(5,2) DEFAULT NULL,
    erase_avgtime SMALLINT DEFAULT NULL,
    clone_avgtime TINYINT DEFAULT NULL,
    all_jobs SMALLINT DEFAULT NULL,
    erase_jobs SMALLINT DEFAULT NULL,
    clone_jobs SMALLINT DEFAULT NULL,
    disk_type VARCHAR(8) DEFAULT NULL,
    bios_version VARCHAR(24) DEFAULT NULL
);


CREATE TABLE IF NOT EXISTS jobstats (
    uuid VARCHAR(64) NOT NULL PRIMARY KEY,
    tagnumber MEDIUMINT DEFAULT NULL,
    etheraddress VARCHAR(24) DEFAULT NULL,
    wifi_mac VARCHAR(24) DEFAULT NULL,
    date DATE DEFAULT NULL,
    time DATETIME(3) DEFAULT NULL,
    department VARCHAR(12) DEFAULT NULL,
    bios_vendor VARCHAR(16) DEFAULT NULL,
    bios_version VARCHAR(24) DEFAULT NULL,
    bios_date VARCHAR(12) DEFAULT NULL,
    bios_revision VARCHAR(8) DEFAULT NULL,
    bios_firmware VARCHAR(8) DEFAULT NULL,
    system_manufacturer VARCHAR(12) DEFAULT NULL,
    system_model VARCHAR(24) DEFAULT NULL,
    system_serial VARCHAR(16) DEFAULT NULL,
    system_uuid VARCHAR(36) DEFAULT NULL,
    system_sku VARCHAR(16) DEFAULT NULL,
    motherboard_manufacturer VARCHAR(16) DEFAULT NULL,
    motherboard_serial VARCHAR(32) DEFAULT NULL,
    chassis_manufacturer VARCHAR(12) DEFAULT NULL,
    chassis_type VARCHAR(12) DEFAULT NULL,
    chassis_serial VARCHAR(16) DEFAULT NULL,
    chassis_tag VARCHAR(16) DEFAULT NULL,
    network_usage DECIMAL(5,2) DEFAULT NULL,
    cpu_usage DECIMAL(6,2) DEFAULT NULL,
    cpu_manufacturer VARCHAR(32) DEFAULT NULL,
    cpu_model VARCHAR(64) DEFAULT NULL,
    cpu_maxspeed SMALLINT DEFAULT NULL,
    cpu_cores TINYINT DEFAULT NULL,
    cpu_threads TINYINT DEFAULT NULL,
    cpu_temp DECIMAL(5,2) DEFAULT NULL,
    ram_serial VARCHAR(24) DEFAULT NULL,
    ram_capacity DECIMAL(4,2) DEFAULT NULL,
    ram_speed SMALLINT DEFAULT NULL,
    battery_manufacturer VARCHAR(16) DEFAULT NULL,
    battery_name VARCHAR(16) DEFAULT NULL,
    battery_capacity MEDIUMINT DEFAULT NULL,
    battery_serial VARCHAR(8) DEFAULT NULL,
    battery_manufacturedate DATE DEFAULT NULL,
    battery_health TINYINT DEFAULT NULL,
    battery_charge_cycles SMALLINT DEFAULT NULL,
    boot_time DECIMAL(5,2) DEFAULT NULL,
    hibernate VARCHAR(3) DEFAULT NULL,
    disk VARCHAR(8) DEFAULT NULL,
    disk_type VARCHAR(8) DEFAULT NULL,
    disksizegb SMALLINT DEFAULT NULL,
    disk_model VARCHAR(32) DEFAULT NULL,
    disk_serial VARCHAR(32) DEFAULT NULL,
    disk_firmware VARCHAR(10) DEFAULT NULL,
    disk_power_on_hours MEDIUMINT DEFAULT NULL,
    disk_temp VARCHAR(32) DEFAULT NULL,
    disk_reads DECIMAL(5,2) DEFAULT NULL,
    disk_writes DECIMAL(5,2) DEFAULT NULL,
    erase_completed VARCHAR(3) DEFAULT NULL,
    erase_mode VARCHAR(24) DEFAULT NULL,
    erase_time SMALLINT DEFAULT NULL,
    erase_diskpercent TINYINT DEFAULT NULL,
    clone_completed VARCHAR(3) DEFAULT NULL,
    clone_time SMALLINT DEFAULT NULL,
    clone_master VARCHAR(8) DEFAULT NULL
);

ALTER TABLE jobstats
    DROP PRIMARY KEY,
    MODIFY COLUMN uuid VARCHAR(64) NOT NULL PRIMARY KEY,
    MODIFY COLUMN tagnumber MEDIUMINT DEFAULT NULL,
    MODIFY COLUMN etheraddress VARCHAR(24) DEFAULT NULL,
    MODIFY COLUMN wifi_mac VARCHAR(24) DEFAULT NULL,
    MODIFY COLUMN date DATE DEFAULT NULL,
    MODIFY COLUMN time DATETIME(3) DEFAULT NULL,
    MODIFY COLUMN department VARCHAR(12) DEFAULT NULL,
    MODIFY COLUMN bios_vendor VARCHAR(16) DEFAULT NULL,
    MODIFY COLUMN bios_version VARCHAR(24) DEFAULT NULL,
    MODIFY COLUMN bios_date VARCHAR(12) DEFAULT NULL,
    MODIFY COLUMN bios_revision VARCHAR(8) DEFAULT NULL,
    MODIFY COLUMN bios_firmware VARCHAR(8) DEFAULT NULL,
    MODIFY COLUMN system_manufacturer VARCHAR(12) DEFAULT NULL,
    MODIFY COLUMN system_model VARCHAR(24) DEFAULT NULL,
    MODIFY COLUMN system_serial VARCHAR(16) DEFAULT NULL,
    MODIFY COLUMN system_uuid VARCHAR(36) DEFAULT NULL,
    MODIFY COLUMN system_sku VARCHAR(16) DEFAULT NULL,
    MODIFY COLUMN motherboard_manufacturer VARCHAR(16) DEFAULT NULL,
    MODIFY COLUMN motherboard_serial VARCHAR(32) DEFAULT NULL,
    MODIFY COLUMN chassis_manufacturer VARCHAR(12) DEFAULT NULL,
    MODIFY COLUMN chassis_type VARCHAR(12) DEFAULT NULL,
    MODIFY COLUMN chassis_serial VARCHAR(16) DEFAULT NULL,
    MODIFY COLUMN chassis_tag VARCHAR(16) DEFAULT NULL,
    MODIFY COLUMN network_usage DECIMAL(5,2) DEFAULT NULL,
    MODIFY COLUMN cpu_usage DECIMAL(6,2) DEFAULT NULL,
    MODIFY COLUMN cpu_manufacturer VARCHAR(32) DEFAULT NULL,
    MODIFY COLUMN cpu_model VARCHAR(64) DEFAULT NULL,
    MODIFY COLUMN cpu_maxspeed SMALLINT DEFAULT NULL,
    MODIFY COLUMN cpu_cores TINYINT DEFAULT NULL,
    MODIFY COLUMN cpu_threads TINYINT DEFAULT NULL,
    MODIFY COLUMN cpu_temp DECIMAL(5,2) DEFAULT NULL,
    MODIFY COLUMN ram_serial VARCHAR(24) DEFAULT NULL,
    MODIFY COLUMN ram_capacity DECIMAL(4,2) DEFAULT NULL,
    MODIFY COLUMN ram_speed SMALLINT DEFAULT NULL,
    MODIFY COLUMN battery_manufacturer VARCHAR(16) DEFAULT NULL,
    MODIFY COLUMN battery_name VARCHAR(16) DEFAULT NULL,
    MODIFY COLUMN battery_capacity MEDIUMINT DEFAULT NULL,
    MODIFY COLUMN battery_serial VARCHAR(8) DEFAULT NULL,
    MODIFY COLUMN battery_manufacturedate DATE DEFAULT NULL,
    MODIFY COLUMN battery_health TINYINT DEFAULT NULL,
    MODIFY COLUMN battery_charge_cycles SMALLINT DEFAULT NULL,
    MODIFY COLUMN boot_time DECIMAL(5,2) DEFAULT NULL,
    MODIFY COLUMN hibernate VARCHAR(3) DEFAULT NULL,
    MODIFY COLUMN disk VARCHAR(8) DEFAULT NULL,
    MODIFY COLUMN disk_type VARCHAR(8) DEFAULT NULL,
    MODIFY COLUMN disksizegb SMALLINT DEFAULT NULL,
    MODIFY COLUMN disk_model VARCHAR(32) DEFAULT NULL,
    MODIFY COLUMN disk_serial VARCHAR(32) DEFAULT NULL,
    MODIFY COLUMN disk_firmware VARCHAR(10) DEFAULT NULL,
    MODIFY COLUMN disk_power_on_hours MEDIUMINT DEFAULT NULL,
    MODIFY COLUMN disk_temp VARCHAR(32) DEFAULT NULL,
    MODIFY COLUMN disk_reads DECIMAL(5,2) DEFAULT NULL,
    MODIFY COLUMN disk_writes DECIMAL(5,2) DEFAULT NULL,
    MODIFY COLUMN erase_completed VARCHAR(3) DEFAULT NULL,
    MODIFY COLUMN erase_mode VARCHAR(24) DEFAULT NULL,
    MODIFY COLUMN erase_time SMALLINT DEFAULT NULL,
    MODIFY COLUMN erase_diskpercent TINYINT DEFAULT NULL,
    MODIFY COLUMN clone_completed VARCHAR(3) DEFAULT NULL,
    MODIFY COLUMN clone_time SMALLINT DEFAULT NULL,
    MODIFY COLUMN clone_master VARCHAR(8) DEFAULT NULL;


CREATE TABLE IF NOT EXISTS locations (
    time DATETIME(3) NOT NULL PRIMARY KEY,
    tagnumber MEDIUMINT DEFAULT NULL,
    system_serial VARCHAR(16) DEFAULT NULL,
    location VARCHAR(256) DEFAULT NULL,
    status VARCHAR(12) DEFAULT NULL,
    problem VARCHAR(512) DEFAULT NULL
);

ALTER TABLE locations
    DROP PRIMARY KEY,
    MODIFY COLUMN time DATETIME(3) NOT NULL PRIMARY KEY,
    MODIFY COLUMN tagnumber MEDIUMINT DEFAULT NULL AFTER time,
    MODIFY COLUMN system_serial VARCHAR(16) DEFAULT NULL AFTER tagnumber,
    MODIFY COLUMN location VARCHAR(256) DEFAULT NULL AFTER system_serial,
    MODIFY COLUMN status VARCHAR(12) DEFAULT NULL AFTER location,
    MODIFY COLUMN problem VARCHAR(512) DEFAULT NULL AFTER status;


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
