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

$sql = "CREATE TABLE clientstats (tagnumber VARCHAR(150) NOT NULL DEFAULT '000000',device_type VARCHAR(150) NOT NULL DEFAULT 'N/A',last_job_date VARCHAR(150) NOT NULL DEFAULT 'N/A',all_lastuuid VARCHAR(150) NOT NULL DEFAULT 'N/A',all_time VARCHAR(150) NOT NULL DEFAULT 'N/A',all_avgtime VARCHAR(150) NOT NULL DEFAULT 'N/A',erase_time VARCHAR(150) NOT NULL DEFAULT 'N/A',erase_avgtime VARCHAR(150) NOT NULL DEFAULT 'N/A',clone_time VARCHAR(150) NOT NULL DEFAULT 'N/A',clone_avgtime VARCHAR(150) NOT NULL DEFAULT 'N/A',all_jobs VARCHAR(150) NOT NULL DEFAULT 'N/A',erase_jobs VARCHAR(150) NOT NULL DEFAULT 'N/A',clone_jobs VARCHAR(150) NOT NULL DEFAULT 'N/A',disk VARCHAR(150) NOT NULL DEFAULT 'N/A',time VARCHAR(150) NOT NULL DEFAULT 'N/A',PRIMARY KEY(tagnumber) )";
$conn->query($sql);

$sql = "CREATE TABLE serverstats (date VARCHAR(150) NOT NULL DEFAULT 'N/A',laptop_count VARCHAR(150) NOT NULL DEFAULT 'N/A',last_image_update VARCHAR(150) NOT NULL DEFAULT 'N/A',all_jobs VARCHAR(150) NOT NULL DEFAULT 'N/A',clone_jobs VARCHAR(150) NOT NULL DEFAULT 'N/A',erase_jobs VARCHAR(150) NOT NULL DEFAULT 'N/A',all_avgtime VARCHAR(150) NOT NULL DEFAULT 'N/A',clone_avgtime VARCHAR(150) NOT NULL DEFAULT 'N/A',nvme_erase_avgtime VARCHAR(150) NOT NULL DEFAULT 'N/A',ssd_erase_avgtime VARCHAR(150) NOT NULL DEFAULT 'N/A', PRIMARY KEY(date) )";
$conn->query($sql);

$conn->close();
?>