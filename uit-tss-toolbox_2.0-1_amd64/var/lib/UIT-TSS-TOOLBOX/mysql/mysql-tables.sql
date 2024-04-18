DROP TABLE IF EXISTS clientstats;
DELIMITER //
CREATE TABLE clientstats (
    tagnumber MEDIUMINT,
    system_manufacturer VARCHAR(64),
    last_job_time DATETIME,
    last_job_uuid VARCHAR(64),
    all_avgtime SMALLINT,
    erase_time SMALLINT,
    erase_avgtime SMALLINT,
    clone_time SMALLINT,
    clone_avgtime TINYINT,
    all_jobs SMALLINT,
    erase_jobs SMALLINT,
    clone_jobs SMALLINT,
    disk_type VARCHAR(4),
    tbw_pcnt TINYINT,
    system_model VARCHAR(24),
    bios_date VARCHAR(12),
    bios_version VARCHAR(24),
    system_serial VARCHAR(16),
    cpu_model VARCHAR(64),
    cpu_cores TINYINT,
    battery_health TINYINT,
    boot_time TINYINT,
    PRIMARY KEY (tagnumber)
);

-- First check if the table exists
CREATE TABLE IF NOT EXISTS jobstats (

    PRIMARY KEY (uuid)
);
    ALTER TABLE jobstats
        MODIFY COLUMN uuid DEFAULT NOT NULL VARCHAR(64),
        MODIFY COLUMN tagnumber MEDIUMINT DEFAULT NULL,
        MODIFY COLUMN etheraddress VARCHAR(24) DEFAULT NULL
        MODIFY COLUMN wifi_mac varchar(24)  YES      NULL          |
        MODIFY COLUMN date date         YES      NULL          |
        MODIFY COLUMN time datetime(3)  YES      NULL          |
        MODIFY COLUMN department varchar(12)  YES      NULL          |
        MODIFY COLUMN bios_vendor varchar(16)  YES      NULL          |
        MODIFY COLUMN bios_version varchar(24)  YES      NULL          |
        MODIFY COLUMN bios_date varchar(12)  YES      NULL          |
        MODIFY COLUMN bios_revision varchar(8)   YES      NULL          |
        MODIFY COLUMN bios_firmware varchar(8)   YES      NULL          |
        MODIFY COLUMN system_manufacturer varchar(12)  YES      NULL          |
        MODIFY COLUMN system_model varchar(24)  YES      NULL          |
        MODIFY COLUMN system_serial varchar(16)  YES      NULL          |
        MODIFY COLUMN system_uuid varchar(36)  YES      NULL          |
        MODIFY COLUMN system_sku varchar(16)  YES      NULL          |
        MODIFY COLUMN motherboard_manufacturer varchar(16)  YES      NULL          |
        MODIFY COLUMN motherboard_serial varchar(32)  YES      NULL          |
        MODIFY COLUMN chassis_manufacturer varchar(12)  YES      NULL          |
        MODIFY COLUMN chassis_type varchar(12)  YES      NULL          |
        MODIFY COLUMN chassis_serial varchar(16)  YES      NULL          |
        MODIFY COLUMN chassis_tag varchar(16)  YES      NULL          |
        MODIFY COLUMN cpu_manufacturer varchar(32)  YES      NULL          |
        MODIFY COLUMN cpu_model varchar(64)  YES      NULL          |
        MODIFY COLUMN cpu_maxspeed smallint     YES      NULL          |
        MODIFY COLUMN cpu_cores tinyint      YES      NULL          |
        MODIFY COLUMN cpu_threads tinyint      YES      NULL          |
        MODIFY COLUMN cpu_temp decimal(6,2) YES      NULL          |
        MODIFY COLUMN ram_serial varchar(24)  YES      NULL          |
        MODIFY COLUMN battery_manufacturer varchar(16)  YES      NULL          |
        MODIFY COLUMN battery_name varchar(16)  YES      NULL          |
        MODIFY COLUMN battery_capacity mediumint    YES      NULL          |
        MODIFY COLUMN battery_serial varchar(8)   YES      NULL          |
        MODIFY COLUMN battery_manufacturedate date         YES      NULL          |
        MODIFY COLUMN battery_health tinyint      YES      NULL          |
        MODIFY COLUMN battery_charge_cycles smallint     YES      NULL          |
        MODIFY COLUMN boot_time decimal(5,2) YES      NULL          |
        MODIFY COLUMN action varchar(16)  YES      NULL          |
        MODIFY COLUMN hibernate                varchar(3)   YES      NULL          |
        MODIFY COLUMN disk                     varchar(8)   YES      NULL          |
        MODIFY COLUMN disk_type                varchar(4)   YES      NULL          |
        MODIFY COLUMN disksizegb               smallint     YES      NULL          |
        MODIFY COLUMN disk_model               varchar(32)  YES      NULL          |
        MODIFY COLUMN disk_serial              varchar(32)  YES      NULL          |
        MODIFY COLUMN disk_firmware            varchar(10)  YES      NULL          |
        MODIFY COLUMN disk_power_on_hours      mediumint    YES      NULL          |
        MODIFY COLUMN disk_temp                varchar(32)  YES      NULL          |
        MODIFY COLUMN disk_reads               decimal(7,2) YES      NULL          |
        MODIFY COLUMN disk_writes              decimal(7,2) YES      NULL          |
        MODIFY COLUMN all_time                 smallint     YES      NULL          |
        MODIFY COLUMN erase_completed          varchar(3)   YES      NULL          |
        MODIFY COLUMN erase_mode               varchar(24)  YES      NULL          |
        MODIFY COLUMN erase_time               smallint     YES      NULL          |
        MODIFY COLUMN erase_diskpercent        tinyint      YES      NULL          |
        MODIFY COLUMN clone_completed          varchar(3)   YES      NULL          |
        MODIFY COLUMN clone_mode               varchar(16)  YES      NULL          |
        MODIFY COLUMN clone_time               smallint     YES      NULL          |
        MODIFY COLUMN clone_master             varchar(8)   YES      NULL          |
        MODIFY COLUMN clone_server             varchar(24)  YES      NULL          |
        MODIFY COLUMN clone_image              varchar(32)  YES      NULL          |
        MODIFY COLUMN clone_imageupdate
        ADD PRIMARY KEY (uuid);