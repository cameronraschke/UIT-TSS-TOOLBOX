#!/bin/bash
DATE=$(date --iso)

for tagNum in $(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT tagnumber FROM jobstats WHERE NOT tagnumber = '000000';"); do

	exists=$(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" -s -N --execute="\
		SELECT tagnumber FROM clientstats WHERE tagnumber = '${tagNum}';")

		if [[ $exists == $tagNum ]]; then
			mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" -s -N --execute="\
    			UPDATE clientstats \
     			SET tagnumber = '${tagNum}' \
    			WHERE tagnumber = '${tagNum}';"
		else
			mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" -s -N --execute="\
				INSERT INTO clientstats(
				tagnumber, \
				device_type, \
				last_job_date, \
				all_lastuuid, \
				all_time, \
				all_avgtime, \
				all_avgtimetoday, \
				erase_avgtime, \
				erase_avgtimetoday, \
				clone_avgtime, \
				clone_avgtimetoday, \
				all_jobs, \
				all_jobstoday, \
				erase_jobs, \
				erase_jobstoday, \
				clone_jobs, \
				disk, \
				clone_jobstoday)
				VALUES (
				'${tagNum}', \
				DEFAULT, \
				DEFAULT, \
				DEFAULT, \
				DEFAULT, \
				DEFAULT, \
				DEFAULT, \
				DEFAULT, \
				DEFAULT, \
				DEFAULT, \
				DEFAULT, \
				DEFAULT, \
				DEFAULT, \
				DEFAULT, \
				DEFAULT, \
				DEFAULT, \
				DEFAULT, \
				DEFAULT);"
		fi

	# Update Disk in Clientstats
	for i in $(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" -s -N --execute="\
	SELECT disk FROM jobstats WHERE tagnumber = '${tagNum}';"); do
		mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" --execute="\
			UPDATE clientstats SET disk = '${i}' WHERE tagnumber = '${tagNum}';"
	done

	#Update linecount
	linecount=$(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT tagnumber FROM jobstats WHERE tagnumber = '${tagNum}';" | wc -l )
	linecounttoday=$(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT tagnumber FROM jobstats WHERE tagnumber = '${tagNum}' AND date = '${DATE}';" | wc -l )


	# Update total time
	erasetime=$(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT erase_time FROM jobstats WHERE tagnumber = '${tagNum}';")
	erasetime=$(z=0; for i in ${erasetime}; do z=$(( z + i )); echo $z; done | tail -n 1)
	imagetime=$(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT clone_time FROM jobstats WHERE tagnumber = '${tagNum}';")
	imagetime=$(z=0; for i in ${imagetime}; do z=$(( z + i )); echo $z; done | tail -n 1)
	
	totaltime=$(( erasetime + imagetime ))
    mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="UPDATE clientstats SET all_time = '${totaltime}' WHERE tagnumber = '${tagNum}';"


    # Average image and erase time overall
	sqlstatement=$(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT all_time FROM clientstats WHERE tagnumber = ${tagNum};")

	totaltime=$(echo "${sqlstatement}" | tail -n 1)

	TimesSeconds=$((totaltime / linecount))

	TimesMinutes=$(echo "$((TimesSeconds / 60)) minutes")

	mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" --execute="\
		UPDATE clientstats SET all_avgtime = '${TimesMinutes}' WHERE tagnumber = '${tagNum}';"


    # Average clone time overall
	sqlstatement=$(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT clone_time FROM jobstats WHERE tagnumber = ${tagNum};")

	totaltime=$(echo "${sqlstatement}" | tail -n 1)

	TimesSeconds=$((totaltime / linecount))

	TimesMinutes=$(echo "$((TimesSeconds / 60)) minutes")

	mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" --execute="\
		UPDATE clientstats SET clone_avgtime = '${TimesMinutes}' WHERE tagnumber = '${tagNum}';"

    # Average erase time overall
	sqlstatement=$(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT erase_time FROM jobstats WHERE tagnumber = ${tagNum};")

	totaltime=$(echo "${sqlstatement}" | tail -n 1)

	TimesSeconds=$((totaltime / linecount))

	TimesMinutes=$(echo "$((TimesSeconds / 60)) minutes")

	mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" --execute="\
		UPDATE clientstats SET erase_avgtime = '${TimesMinutes}' WHERE tagnumber = '${tagNum}';"


	# Update total time today
	erasetimetoday=$(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT erase_time FROM jobstats WHERE tagnumber = '${tagNum}' AND date = '${DATE}';")
	erasetimetoday=$(z=0; for i in ${erasetimetoday}; do z=$(( z + i )); echo $z; done | tail -n 1)
	imagetimetoday=$(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT clone_time FROM jobstats WHERE tagnumber = '${tagNum}' AND date = '${DATE}';")
	imagetimetoday=$(z=0; for i in ${imagetimetoday}; do z=$(( z + i )); echo $z; done | tail -n 1)
	
	totaltimetoday=$(( erasetimetoday + imagetimetoday ))


    # Average image and erase time today
	sqlstatement=$(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT all_time FROM clientstats WHERE tagnumber = ${tagNum};")

	if [[ $totaltimetoday -eq "0" ]]; then
		TimesSeconds='0'
	else
		TimesSeconds=$((totaltimetoday / linecounttoday))
	fi

	TimesMinutesToday=$(echo "$((TimesSeconds / 60)) minutes")

    mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="UPDATE clientstats SET all_avgtimetoday = '${TimesMinutesToday}' WHERE tagnumber = '${tagNum}';"


	# Update total jobs
	erasejobs=$(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT IF (erase_completed = 'Yes', 1, 0) FROM jobstats WHERE tagnumber = '${tagNum}';")
	erasejobs=$(z=0; for i in ${erasejobs}; do z=$(( z + i )); echo $z; done | tail -n 1)
	mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" --execute="\
		UPDATE clientstats SET erase_jobs = '${erasejobs}' WHERE tagnumber = ${tagNum};"
	clonejobs=$(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT IF (clone_completed = 'Yes', 1, 0) FROM jobstats WHERE tagnumber = '${tagNum}';")
	clonejobs=$(z=0; for i in ${clonejobs}; do z=$(( z + i )); echo $z; done | tail -n 1)
	mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" --execute="\
		UPDATE clientstats SET clone_jobs = '${clonejobs}' WHERE tagnumber = '${tagNum}';"
	
	totaljobs=$(( erasejobs + clonejobs ))
    mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="UPDATE clientstats SET all_jobs = '${totaljobs}' WHERE tagnumber = '${tagNum}';"

	# Update date
	sqldate=$(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT date FROM jobstats WHERE tagnumber = '${tagNum}' ORDER BY date DESC;" | head -n 1)

    mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="UPDATE clientstats SET last_job_date = '${sqldate}' WHERE tagnumber = '${tagNum}';"

	# Update device type
	for i in $(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" --execute="\
		SELECT tagnumber FROM jobstats WHERE clone_image LIKE '%HP' AND tagnumber = '${tagNum}';"); do 
			mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" --execute="\
				UPDATE clientstats SET device_type = 'HP' WHERE tagnumber = '${i}';"
	done
	
	for i in $(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" --execute="\
		SELECT tagnumber FROM jobstats WHERE clone_image LIKE '%Dell' AND tagnumber = '${tagNum}';"); do 
			mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" --execute="\
				UPDATE clientstats SET device_type = 'Dell' WHERE tagnumber = '${i}';"
	done

done

##### ----- serverstats ----- #####

	mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" --execute="\
		UPDATE serverstats SET date = '$(date --iso)';"

	# total laptops
	linecount=$(z=0; for i in $(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT tagnumber FROM clientstats WHERE NOT tagnumber = '000000';"); do \
		z=$(( z + 1 )); echo $z; done | tail -n 1)
	mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" --execute="\
		UPDATE serverstats SET laptop_count = '${linecount}';"

	# clone_avgtime
	lc_cloneavgtime=$(z=0; for i in $(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT tagnumber FROM clientstats WHERE NOT tagnumber = '000000' AND NOT clone_avgtime = '0 minutes';"); do \
		z=$(( z + 1 )); echo $z; done | tail -n 1)
	cloneAvgTime=$(z=0; for i in $(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT clone_avgtime FROM clientstats WHERE NOT clone_avgtime = '0 minutes';" | grep -P '[0-9]'); do \
		z=$(( z + i )); echo $z; done | tail -n 1)
	cloneAvgTime=$(( cloneAvgTime / lc_cloneavgtime ))
	mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" --execute="\
		UPDATE serverstats SET clone_avgtime = '${cloneAvgTime} minutes';"

	# nvme_erase_avgtime
	lc_eraseavgtime=$(z=0; for i in $(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT tagnumber FROM clientstats WHERE NOT tagnumber = '000000' AND NOT erase_avgtime = '0 minutes' AND disk LIKE 'nvme0%';"); do \
		z=$(( z + 1 )); echo $z; done | tail -n 1)
	eraseAvgTime=$(z=0; for i in $(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT erase_avgtime FROM clientstats WHERE NOT erase_avgtime = '0 minutes' AND disk LIKE 'nvme0%';" | grep -P '[0-9]'); do \
		z=$(( z + i )); echo $z; done | tail -n 1)
	nvmeeraseAvgTime=$(( eraseAvgTime / lc_eraseavgtime ))
	mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" --execute="\
		UPDATE serverstats SET nvme_erase_avgtime = '${nvmeeraseAvgTime} minutes';"

	# ssd_erase_avgtime
	lc_eraseavgtime=$(z=0; for i in $(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT tagnumber FROM clientstats WHERE NOT tagnumber = '000000' AND NOT erase_avgtime = '0 minutes' AND disk LIKE 'sd%';"); do \
		z=$(( z + 1 )); echo $z; done | tail -n 1)
	eraseAvgTime=$(z=0; for i in $(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT erase_avgtime FROM clientstats WHERE NOT erase_avgtime = '0 minutes' AND disk LIKE 'sd%';" | grep -P '[0-9]'); do \
		z=$(( z + i )); echo $z; done | tail -n 1)
	ssderaseAvgTime=$(( eraseAvgTime / lc_eraseavgtime ))
	mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" --execute="\
		UPDATE serverstats SET ssd_erase_avgtime = '${ssderaseAvgTime} minutes';"
	
	# clone_jobs
	clonejobs=$(z=0; for i in $(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT clone_jobs FROM clientstats WHERE NOT tagnumber = '000000';"); do \
		z=$(( z + i )); echo $z; done | tail -n 1)
	mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" --execute="\
		UPDATE serverstats SET clone_jobs = '${clonejobs}';"

	# erase_jobs
	erasejobs=$(z=0; for i in $(mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" \
		-s -N --execute="SELECT erase_jobs FROM clientstats WHERE NOT tagnumber = '000000';"); do \
		z=$(( z + i )); echo $z; done | tail -n 1)
	mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" --execute="\
		UPDATE serverstats SET erase_jobs = '${erasejobs}';"

	# all_jobs
	linecount=$(( clonejobs + erasejobs ))
	mysql --user="laptops" --password="UHouston!" --database="laptopDB" --host="10.0.0.1" --execute="\
		UPDATE serverstats SET all_jobs = '${linecount}';"