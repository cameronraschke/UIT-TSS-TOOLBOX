#!/usr/bin/php
<?php
include('/var/lib/UIT-TSS-TOOLBOX/DB-connect-local.php');

#apt install mysql-server -y
#mysqld --initialize-insecure
#sed -i 's/^bind-address.*$/bind-address=0.0.0.0/' /etc/mysql/mysql.conf.d/mysqld.cnf
#systemctl restart mysql

#mysql --user="root" --execute="ALTER USER 'root'@'localhost' IDENTIFIED BY 'UHouston\!';"

#mysql --user="root" --password="UHouston!" --execute="CREATE USER 'laptops'@'%' IDENTIFIED BY 'UHouston\!';"
#mysql --user="root" --password="UHouston!" --execute="ALTER USER 'laptops'@'%' IDENTIFIED BY 'UHouston\!';"
#mysql --user="root" --password="UHouston!" --execute="GRANT ALL ON *.* TO 'laptops'@'%' WITH GRANT OPTION;"

$conn->query("CREATE TABLE clientstats (tagnumber VARCHAR(150) NOT NULL DEFAULT '000000',device_type VARCHAR(150) NOT NULL DEFAULT 'N/A',last_job_date VARCHAR(150) NOT NULL DEFAULT 'N/A',all_lastuuid VARCHAR(150) NOT NULL DEFAULT 'N/A',all_time VARCHAR(150) NOT NULL DEFAULT 'N/A',all_avgtime VARCHAR(150) NOT NULL DEFAULT 'N/A',erase_time VARCHAR(150) NOT NULL DEFAULT 'N/A',erase_avgtime VARCHAR(150) NOT NULL DEFAULT 'N/A',clone_time VARCHAR(150) NOT NULL DEFAULT 'N/A',clone_avgtime VARCHAR(150) NOT NULL DEFAULT 'N/A',all_jobs VARCHAR(150) NOT NULL DEFAULT 'N/A',erase_jobs VARCHAR(150) NOT NULL DEFAULT 'N/A',clone_jobs VARCHAR(150) NOT NULL DEFAULT 'N/A',disk VARCHAR(150) NOT NULL DEFAULT 'N/A',time VARCHAR(150) NOT NULL DEFAULT 'N/A',PRIMARY KEY(tagnumber) )");

$conn->query("CREATE TABLE serverstats (date VARCHAR(150) NOT NULL DEFAULT 'N/A',laptop_count VARCHAR(150) NOT NULL DEFAULT 'N/A',last_image_update VARCHAR(150) NOT NULL DEFAULT 'N/A',all_jobs VARCHAR(150) NOT NULL DEFAULT 'N/A',clone_jobs VARCHAR(150) NOT NULL DEFAULT 'N/A',erase_jobs VARCHAR(150) NOT NULL DEFAULT 'N/A',all_avgtime VARCHAR(150) NOT NULL DEFAULT 'N/A',clone_avgtime VARCHAR(150) NOT NULL DEFAULT 'N/A',nvme_erase_avgtime VARCHAR(150) NOT NULL DEFAULT 'N/A',ssd_erase_avgtime VARCHAR(150) NOT NULL DEFAULT 'N/A', PRIMARY KEY(date) )");

$conn->query("ALTER TABLE jobstats DROP COLUMN 'alldisks'");

$conn->query("ALTER TABLE jobstats DROP COLUMN 'erase_pattern'");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN uuid VARCHAR(150) NOT NULL DEFAULT 'N/A' FIRST");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN tagnumber VARCHAR(150) NOT NULL DEFAULT '000000' AFTER uuid");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN etheraddress VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER tagnumber");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN date VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER etheraddress");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN time VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER date");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN action VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER time");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN reboot VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER action");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN disk VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER reboot");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN disksizegb VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER disk");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN all_time VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER disksizegb");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN erase_completed VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER all_time");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN erase_mode VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER erase_completed");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN erase_time VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER erase_mode");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN erase_diskpercent VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER erase_time");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN clone_completed VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER erase_diskpercent");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN clone_mode VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER clone_completed");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN clone_time VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER clone_mode");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN clone_master VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER clone_time");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN clone_server VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER clone_master");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN clone_image VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER clone_server");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN clone_imageupdate VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER clone_image");

$conn->query("ALTER TABLE jobstats MODIFY COLUMN clone_sambauser VARCHAR(150) NOT NULL DEFAULT 'N/A' AFTER clone_imageupdate");

$conn->close();
?>