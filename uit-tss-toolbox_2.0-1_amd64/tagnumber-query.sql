SELECT DATE_FORMAT(t10.time, '%b %D %Y, %r') AS 'time_formatted',
  t9.time AS 'jobstatsTime', jobstats.tagnumber, jobstats.system_serial, t1.department, 
  locations.location, locations.status, t2.department_readable, 
  t3.note, DATE_FORMAT(t3.time, '%b %D %Y, %r') AS 'note_time_formatted', 
  locations.disk_removed, 
  jobstats.etheraddress, system_data.wifi_mac, 
  system_data.chassis_type, 
  system_data.system_manufacturer, system_data.system_model, 
  system_data.cpu_model, system_data.cpu_cores, system_data.cpu_threads, 
  jobstats.ram_capacity, jobstats.ram_speed, 
  t4.disk_model, t4.disk_size, t4.disk_type,
  t5.identifier, t5.recovery_key, 
  clientstats.battery_health, clientstats.disk_health, 
  DATE_FORMAT(remote.present, '%b %D %Y, %r') AS 'time_formatted', remote.status, remote.present_bool, 
  remote.kernel_updated, remote.bios_updated, SEC_TO_TIME(remote.uptime) AS 'uptime_formatted'
FROM jobstats
LEFT JOIN remote ON jobstats.tagnumber = remote.tagnumber
LEFT JOIN clientstats ON jobstats.tagnumber = clientstats.tagnumber
LEFT JOIN locations ON jobstats.tagnumber = locations.tagnumber
LEFT JOIN system_data ON jobstats.tagnumber = system_data.tagnumber
LEFT JOIN (SELECT tagnumber, department FROM departments WHERE time IN (SELECT MAX(time) FROM departments WHERE tagnumber IS NOT NULL GROUP BY tagnumber)) t1 
  ON jobstats.tagnumber = t1.tagnumber
LEFT JOIN (SELECT department, department_readable FROM static_departments) t2
  ON t1.department = t2.department
LEFT JOIN (SELECT tagnumber, time, note FROM locations WHERE time IN (SELECT MAX(time) FROM locations WHERE note IS NOT NULL GROUP BY tagnumber)) t3
  ON jobstats.tagnumber = t3.tagnumber
LEFT JOIN (SELECT tagnumber, disk_model, disk_size, disk_type FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE disk_type IS NOT NULL AND tagnumber IS NOT NULL GROUP BY tagnumber)) t4 
  ON jobstats.tagnumber = t4.tagnumber
LEFT JOIN (SELECT tagnumber, identifier, recovery_key FROM bitlocker) t5 
  ON jobstats.tagnumber = t5.tagnumber
LEFT JOIN (SELECT tagnumber, ram_capacity, ram_speed FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE ram_capacity IS NOT NULL AND ram_speed IS NOT NULL AND tagnumber IS NOT NULL GROUP BY tagnumber)) t8
  ON jobstats.tagnumber = t8.tagnumber
INNER JOIN (SELECT MAX(time) AS 'time' FROM jobstats WHERE tagnumber IS NOT NULL AND system_serial IS NOT NULL GROUP BY tagnumber) t9
  ON jobstats.time = t9.time
INNER JOIN (SELECT MAX(time) AS 'time' FROM locations WHERE tagnumber IS NOT NULL AND system_serial IS NOT NULL GROUP BY tagnumber) t10
  ON locations.time = t10.time
WHERE jobstats.tagnumber IS NOT NULL and jobstats.system_serial IS NOT NULL


GROUP BY jobstats.tagnumber


  t6.erase_time AS 'erase_avgtime', t7.clone_time AS 'clone_avgtime', 


LEFT JOIN (SELECT tagnumber, erase_time, ROW_NUMBER() OVER(PARTITION BY tagnumber ORDER BY time DESC) AS 'row_count' FROM jobstats WHERE erase_time IS NOT NULL) t6
  ON jobstats.tagnumber = t6.tagnumber
LEFT JOIN (SELECT tagnumber, clone_time, ROW_NUMBER() OVER(PARTITION BY tagnumber ORDER BY time DESC) AS 'row_count' FROM jobstats WHERE clone_time IS NOT NULL) t7
  ON jobstats.tagnumber = t7.tagnumber
WHERE t6.row_count <= 1 AND t7.row_count <= 1
SELECT
FROM jobstats
WHERE

GROUP BY jobstats.tagnumber
----------------------------------------


SELECT 
  locations.time, jobstats.tagnumber, jobstats.system_serial, 
  system_data.system_model, static_departments.department_readable, 
  locations.location, CONCAT(t1.ram_capacity, 'GB'),
  clientstats.disk_health, locations.note
FROM jobstats 
LEFT JOIN locations ON jobstats.tagnumber = locations.tagnumber 
LEFT JOIN departments ON jobstats.tagnumber = departments.tagnumber
LEFT JOIN clientstats ON jobstats.tagnumber = clientstats.tagnumber
LEFT JOIN static_departments ON departments.department = static_departments.department_readable
LEFT JOIN system_data ON jobstats.tagnumber = system_data.tagnumber
LEFT JOIN (SELECT tagnumber, ram_capacity FROM jobstats WHERE time IN (SELECT MAX(time) FROM jobstats WHERE host_connected = 1 AND tagnumber IS NOT NULL GROUP BY tagnumber)) t1
  ON jobstats.tagnumber = t1.tagnumber
INNER JOIN (SELECT MAX(time) AS 'time' FROM jobstats WHERE tagnumber IS NOT NULL AND system_serial IS NOT NULL GROUP BY tagnumber) t2
  ON jobstats.time = t2.time
INNER JOIN (SELECT MAX(time) AS 'time' FROM locations WHERE tagnumber IS NOT NULL AND system_serial IS NOT NULL GROUP BY tagnumber) t3
  ON locations.time = t3.time
INNER JOIN (SELECT MAX(time) AS 'time' FROM departments WHERE tagnumber IS NOT NULL AND system_serial IS NOT NULL GROUP BY tagnumber) t4
  ON departments.time = t4.time
WHERE departments.department = 'shrl'
ORDER BY locations.time ASC;


--Issue uuid = 'techComm-73bfd5e4-adf7-46ab-a420-199e99399f16'

--| uuid                                          | tagnumber | etheraddress      | date       | time                    | department | system_serial | disk    | disk_model              | disk_type | disk_size | disk_serial    | disk_writes | disk_reads | disk_power_on_hours | disk_temp | disk_firmware | battery_model | battery_serial | battery_health | battery_charge_cycles | battery_capacity | battery_manufacturedate | cpu_temp | bios_version      | bios_date  | bios_firmware | ram_serial | ram_capacity | ram_speed | cpu_usage | network_usage | boot_time | erase_completed | erase_mode | erase_diskpercent | erase_time | clone_completed | clone_master | clone_time | host_connected |
--+-----------------------------------------------+-----------+-------------------+------------+-------------------------+------------+---------------+---------+-------------------------+-----------+-----------+----------------+-------------+------------+---------------------+-----------+---------------+---------------+----------------+----------------+-----------------------+------------------+-------------------------+----------+-------------------+------------+---------------+------------+--------------+-----------+-----------+---------------+-----------+-----------------+------------+-------------------+------------+-----------------+--------------+------------+----------------+
--| techComm-73bfd5e4-adf7-46ab-a420-199e99399f16 | 625885    | 38:22:e2:2e:68:22 | 2024-09-18 | 2024-09-18 18:06:01.662 | techComm   | 5CD014DJGZ    | nvme0n1 | MTFDHBA256TCK-1AS1AABHA | nvme      |       256 | UHPVN0172D49HR |        5.02 |       5.89 |
--    324 |        38 | HPS0V23       | RE03045XL     | 06BF           |             84 |                   269 |            45040 | 2020-01-21              |       43 | R71 Ver. 01.28.00 | 04/12/2024 | 81.49         | 3626C768   |            8 |      2667 |     13.40 |          0.06 |     23.87 |            NULL | NULL       |              NULL |       NULL |            NULL |         NULL |       NULL |              1 |
--+-----------------------------------------------+-----------+-------------------+------------+-------------------------+------------+---------------+---------+-------------------------+-----------+-----------+----------------+-------------+------------+---------------------+-----------+---------------+---------------+----------------+----------------+-----------------------+------------------+-------------------------+----------+-------------------+------------+---------------+------------+--------------+-----------+-----------+---------------+-----------+-----------------+------------+-------------------+------------+-----------------+--------------+------------+----------------+