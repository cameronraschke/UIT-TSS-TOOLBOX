DROP TABLE IF EXISTS serverstats;
CREATE TABLE serverstats (
    date DATE DEFAULT NULL,
    laptop_count SMALLINT DEFAULT NULL;
    last_image_update DATE DEFAULT NULL,
    all_jobs MEDIUMINT DEFAULT NULL,
    clone_jobs MEDIUMINT DEFAULT NULL,
    erase_jobs MEDIUMINT DEFAULT NULL,
    clone_avgtime SMALLINT DEFAULT NULL,
    nvme_erase_avgtime SMALLINT DEFAULT NULL,
    sata_erase_avgtime SMALLINT DEFAULT NULL,
    tbw_pcnt TINYINT DEFAULT NULL,
    disk_mtbf DECIMAL(3,2) DEFAULT NULL,
    battery_health TINYINT DEFAULT NULL,
    boot_time SMALLINT DEFAULT NULL,
    PRIMARY KEY (date)
);

DROP TABLE IF EXISTS clientstats;
CREATE TABLE clientstats (
    tagnumber MEDIUMINT DEFAULT NOT NULL,
    system_manufacturer VARCHAR(12) DEFAULT NULL,
    last_job_time DATETIME DEFAULT NULL,
    last_job_uuid VARCHAR(64) DEFAULT NULL,
    all_avgtime SMALLINT DEFAULT NULL,
    erase_time SMALLINT DEFAULT NULL,
    erase_avgtime SMALLINT DEFAULT NULL,
    clone_time SMALLINT DEFAULT NULL,
    clone_avgtime TINYINT DEFAULT NULL,
    all_jobs SMALLINT DEFAULT NULL,
    erase_jobs SMALLINT DEFAULT NULL,
    clone_jobs SMALLINT DEFAULT NULL,
    disk_type VARCHAR(4) DEFAULT NULL,
    tbw_pcnt TINYINT DEFAULT NULL,
    system_model VARCHAR(24) DEFAULT NULL,
    bios_date VARCHAR(12) DEFAULT NULL,
    bios_version VARCHAR(24) DEFAULT NULL,
    system_serial VARCHAR(16) DEFAULT NULL,
    cpu_model VARCHAR(64) DEFAULT NULL,
    cpu_cores TINYINT DEFAULT NULL,
    battery_health TINYINT DEFAULT NULL,
    boot_time TINYINT DEFAULT NULL,
    PRIMARY KEY (tagnumber)
);

CREATE TABLE IF NOT EXISTS jobstats (
    uuid VARCHAR(64) DEFAULT NOT NULL,
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
    cpu_manufacturer VARCHAR(32) DEFAULT NULL,
    cpu_model VARCHAR(64) DEFAULT NULL,
    cpu_maxspeed SMALLINT DEFAULT NULL,
    cpu_cores TINYINT DEFAULT NULL,
    cpu_threads TINYINT DEFAULT NULL,
    cpu_temp DECIMAL(6,2) DEFAULT NULL,
    ram_serial VARCHAR(24) DEFAULT NULL,
    battery_manufacturer VARCHAR(16) DEFAULT NULL,
    battery_name VARCHAR(16) DEFAULT NULL,
    battery_capacity MEDIUMINT DEFAULT NULL,
    battery_serial VARCHAR(8) DEFAULT NULL,
    battery_manufacturedate DATE DEFAULT NULL,
    battery_health TINYINT DEFAULT NULL,
    battery_charge_cycles SMALLINT DEFAULT NULL,
    boot_time DECIMAL(5,2) DEFAULT NULL,
    action VARCHAR(16) DEFAULT NULL,
    hibernate VARCHAR(3) DEFAULT NULL,
    disk VARCHAR(8)  DEFAULT NULL,
    disk_type VARCHAR(4) DEFAULT NULL,
    disksizegb SMALLINT DEFAULT NULL,
    disk_model VARCHAR(32) DEFAULT NULL,
    disk_serial VARCHAR(32) DEFAULT NULL,
    disk_firmware VARCHAR(10) DEFAULT NULL,
    disk_power_on_hours MEDIUMINT DEFAULT NULL,
    disk_temp VARCHAR(32) DEFAULT NULL,
    disk_reads DECIMAL(7,2) DEFAULT NULL,
    disk_writes DECIMAL(7,2) DEFAULT NULL,
    all_time SMALLINT    DEFAULT NULL,
    erase_completed VARCHAR(3) DEFAULT NULL,
    erase_mode VARCHAR(24) DEFAULT NULL,
    erase_time SMALLINT DEFAULT NULL,
    erase_diskpercent TINYINT DEFAULT NULL,
    clone_completed VARCHAR(3) DEFAULT NULL,
    clone_mode VARCHAR(16) DEFAULT NULL,
    clone_time SMALLINT DEFAULT NULL,
    clone_master VARCHAR(8) DEFAULT NULL,
    clone_server VARCHAR(24) DEFAULT NULL,
    clone_image VARCHAR(32) DEFAULT NULL,
    clone_imageupdate DATE DEFAULT NULL,
    PRIMARY KEY (uuid)
);

ALTER TABLE jobstats
    MODIFY COLUMN uuid VARCHAR(64) DEFAULT NOT NULL,
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
    MODIFY COLUMN cpu_manufacturer VARCHAR(32) DEFAULT NULL,
    MODIFY COLUMN cpu_model VARCHAR(64) DEFAULT NULL,
    MODIFY COLUMN cpu_maxspeed SMALLINT DEFAULT NULL,
    MODIFY COLUMN cpu_cores TINYINT DEFAULT NULL,
    MODIFY COLUMN cpu_threads TINYINT DEFAULT NULL,
    MODIFY COLUMN cpu_temp DECIMAL(6,2) DEFAULT NULL,
    MODIFY COLUMN ram_serial VARCHAR(24) DEFAULT NULL,
    MODIFY COLUMN battery_manufacturer VARCHAR(16) DEFAULT NULL,
    MODIFY COLUMN battery_name VARCHAR(16) DEFAULT NULL,
    MODIFY COLUMN battery_capacity MEDIUMINT DEFAULT NULL,
    MODIFY COLUMN battery_serial VARCHAR(8) DEFAULT NULL,
    MODIFY COLUMN battery_manufacturedate DATE DEFAULT NULL,
    MODIFY COLUMN battery_health TINYINT DEFAULT NULL,
    MODIFY COLUMN battery_charge_cycles SMALLINT DEFAULT NULL,
    MODIFY COLUMN boot_time DECIMAL(5,2) DEFAULT NULL,
    MODIFY COLUMN action VARCHAR(16) DEFAULT NULL,
    MODIFY COLUMN hibernate VARCHAR(3) DEFAULT NULL,
    MODIFY COLUMN disk VARCHAR(8)  DEFAULT NULL,
    MODIFY COLUMN disk_type VARCHAR(4) DEFAULT NULL,
    MODIFY COLUMN disksizegb SMALLINT DEFAULT NULL,
    MODIFY COLUMN disk_model VARCHAR(32) DEFAULT NULL,
    MODIFY COLUMN disk_serial VARCHAR(32) DEFAULT NULL,
    MODIFY COLUMN disk_firmware VARCHAR(10) DEFAULT NULL,
    MODIFY COLUMN disk_power_on_hours MEDIUMINT DEFAULT NULL,
    MODIFY COLUMN disk_temp VARCHAR(32) DEFAULT NULL,
    MODIFY COLUMN disk_reads DECIMAL(7,2) DEFAULT NULL,
    MODIFY COLUMN disk_writes DECIMAL(7,2) DEFAULT NULL,
    MODIFY COLUMN all_time SMALLINT    DEFAULT NULL,
    MODIFY COLUMN erase_completed VARCHAR(3) DEFAULT NULL,
    MODIFY COLUMN erase_mode VARCHAR(24) DEFAULT NULL,
    MODIFY COLUMN erase_time SMALLINT DEFAULT NULL,
    MODIFY COLUMN erase_diskpercent TINYINT DEFAULT NULL,
    MODIFY COLUMN clone_completed VARCHAR(3) DEFAULT NULL,
    MODIFY COLUMN clone_mode VARCHAR(16) DEFAULT NULL,
    MODIFY COLUMN clone_time SMALLINT DEFAULT NULL,
    MODIFY COLUMN clone_master VARCHAR(8) DEFAULT NULL,
    MODIFY COLUMN clone_server VARCHAR(24) DEFAULT NULL,
    MODIFY COLUMN clone_image VARCHAR(32) DEFAULT NULL,
    MODIFY COLUMN clone_imageupdate DATE DEFAULT NULL,
    ADD PRIMARY KEY (uuid);