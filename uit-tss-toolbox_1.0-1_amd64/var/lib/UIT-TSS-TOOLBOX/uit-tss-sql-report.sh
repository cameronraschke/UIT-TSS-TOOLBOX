#!/bin/bash
DATE=$(date --iso)
mysql --user="laptops" --password="UHouston!" --database="laptopDB" --html --execute="\
    SELECT tagnumber,date,etheraddress,disk,action,all_totaltime,clone_time,clone_image,clone_master,clone_imageupdate,\
    clone_mode,clone_completed,erase_time,erase_mode,erase_completed \
    FROM jobstats ORDER BY date DESC;" > /mnt/uit-tss-laptop-job-report-$(date --iso).html

mysql --user="laptops" --password="UHouston!" --database="laptopDB" --html --execute="\
    SELECT tagnumber,device_type,last_job_date,all_avgtime,erase_avgtime,clone_avgtime,all_jobs,\
    clone_jobs,erase_jobs \
    FROM clientstats ORDER BY last_job_date DESC;" > /mnt/uit-tss-laptop-report-$(date --iso).html

mysql --user="laptops" --password="UHouston!" --database="laptopDB" --html --execute="\
    SELECT laptop_count,last_image_update,all_jobs,clone_jobs,erase_jobs,clone_avgtime,nvme_erase_avgtime,ssd_erase_avgtime \
    FROM serverstats;" > /mnt/uit-tss-server-report-$(date --iso).html