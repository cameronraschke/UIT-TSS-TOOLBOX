#!/usr/bin/php
<?php

#apt install mysql-server -y
#mysqld --initialize-insecure
#sed -i 's/^bind-address.*$/bind-address=0.0.0.0/' /etc/mysql/mysql.conf.d/mysqld.cnf
#systemctl restart mysql

#mysql --user="root" --execute="ALTER USER 'root'@'localhost' IDENTIFIED BY 'UHouston\!';"

#mysql --user="root" --password="UHouston!" --execute="CREATE USER 'laptops'@'%' IDENTIFIED BY 'UHouston\!';"
#mysql --user="root" --password="UHouston!" --execute="ALTER USER 'laptops'@'%' IDENTIFIED BY 'UHouston\!';"
#mysql --user="root" --password="UHouston!" --execute="GRANT ALL ON *.* TO 'laptops'@'%' WITH GRANT OPTION;"

$sql = "CREATE TABLE clientstats (tagnumber VARCHAR(150) NOT NULL DEFAULT '000000',device_type VARCHAR(150) NOT NULL DEFAULT 'N/A',last_job_date VARCHAR(150) NOT NULL DEFAULT 'N/A',all_lastuuid VARCHAR(150) NOT NULL DEFAULT 'N/A',all_time VARCHAR(150) NOT NULL DEFAULT 'N/A',all_avgtime VARCHAR(150) NOT NULL DEFAULT 'N/A',erase_time VARCHAR(150) NOT NULL DEFAULT 'N/A',erase_avgtime VARCHAR(150) NOT NULL DEFAULT 'N/A',clone_time VARCHAR(150) NOT NULL DEFAULT 'N/A',clone_avgtime VARCHAR(150) NOT NULL DEFAULT 'N/A',all_jobs VARCHAR(150) NOT NULL DEFAULT 'N/A',erase_jobs VARCHAR(150) NOT NULL DEFAULT 'N/A',clone_jobs VARCHAR(150) NOT NULL DEFAULT 'N/A',disk VARCHAR(150) NOT NULL DEFAULT 'N/A',PRIMARY KEY(tagnumber) )";

?>