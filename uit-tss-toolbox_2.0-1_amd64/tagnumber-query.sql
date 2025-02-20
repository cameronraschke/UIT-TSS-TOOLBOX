SELECT jobstats.tagnumber, jobstats.system_serial, t1.department, 
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
  
  clientstats.battery_health, clientstats.disk_health
FROM jobstats
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


GROUP BY tagnumber


SELECT AVG(t6.erase_time) AS 'erase_avgtime', AVG(t7.clone_time) AS 'clone_avgtime'
FROM jobstats
LEFT JOIN (SELECT tagnumber, erase_time, ROW_NUMBER() OVER(PARTITION BY tagnumber ORDER BY time DESC) AS 'row_count' FROM jobstats WHERE erase_time IS NOT NULL) t6
  ON jobstats.tagnumber = t6.tagnumber
LEFT JOIN (SELECT tagnumber, clone_time, ROW_NUMBER() OVER(PARTITION BY tagnumber ORDER BY time DESC) AS 'row_count' FROM jobstats WHERE clone_time IS NOT NULL) t7
  ON jobstats.tagnumber = t7.tagnumber
WHERE
t6.row_count <= 3 AND t7.tagnumber <= 3