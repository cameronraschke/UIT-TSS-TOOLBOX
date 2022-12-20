#!/bin/bash

SSD_REGEX='sd.*'
NVME_REGEX='nvme.*'
SCSI_REGEX='hd.*'
RED=$(tput setaf 1)
CLEAR=$(tput sgr0)
BLUE=$(tput setaf 4)
BOLD=$(tput bold)
RESET=$(tput sgr0)
cloneElapsed="0"
shredElapsed="0"



function intro {
	clear
	echo "${RESET}"
    echo ""
	echo "${BOLD}Welcome to UIT-TSS-TOOLBOX by Cameron Raschke (caraschke@uh.edu)${RESET}"
	echo ""
	echo "${BOLD}Press ${BLUE}CTRL + C${RESET}${BOLD} at any time to exit UIT-TSS-TOOLBOX${RESET}"
	echo "${BOLD}If you have exited UIT-TSS-TOOLBOX and you want to restart it, press ${BLUE}CTRL + D${RESET}"
	echo ""
	echo ""
	echo ""
	tput setaf 1
	tput bold
	echo "| |    | |  | |     | |"
	echo "| |    | |  | |_____| |"
	echo "| |    | |  | |_____| |"
	echo "| |____| |  | |     | |"
	echo "\________/  | |     | |"
	tput sgr0
	echo ""
	echo "------------------------------"
	echo ""
	echo "Checklist:"
	echo "${BOLD}-General best practices${RESET} "
	echo "   * Sanitize laptops with cleaner before imaging them."
	echo "   * Reset BIOS to default/factory settings before imaging."
	echo "${BOLD}-Physical connections${RESET} "
	echo "   * Make sure that power and ethernet are plugged in to the client."
	echo "   * Do not use Secure Erase on USB drives or drives connected over USB."
	echo "      * Autodetect mode and NIST 800-88r1 mode can both do Secure Erase."
	echo "${BOLD}-Dells${RESET} "
	echo "   * Make sure SATA mode is in AHCI mode and not RAID mode."
	echo "      * This is usually under \"System Configuration\" or \"Storage\" in BIOS."
	echo "      * Every Dell is in RAID mode by default."
	echo "      * If you reset BIOS, make sure you change SATA mode to AHCI after the reset."
	echo ""
	read -p "${BOLD}Please remove the thumb drive and press Enter....${RESET}"
	clear
}



function powerWarning {
	tput reset
	COLS=$(tput cols)
	ROWS=$(tput lines)
	tput cup $(( $ROWS / 2 )) $(( $COLS / 2 ))
	echo "${BOLD}${RED}*** WARNING ***${RESET} After pressing Enter, the system will enter hibernate mode."
	echo "This is normal. Please wake up the system after it hibernates. ${BOLD}${RED}*** WARNING ***${RESET}"
	echo ""
	read -p "Please press Enter...."
	tput reset
	echo -n mem > /sys/power/state
	tput reset
}



function appSelect {
	clear
	echo ""
	echo -n "Would you like to ${BOLD}erase and clone [1]${RESET}, ${BOLD}only erase (advanced) [2]${RESET}"
	echo ", or ${BOLD}only clone [3]${RESET}?"
	read -n 1 -p "${BOLD}Please enter [1-3]: ${RESET}" APPSELECT
	if [[ $APPSELECT == "1" ]]; then
		APPSELECT="EC"
		ACTION="erase and clone"
		powerWarning
	elif [[ $APPSELECT == "2" ]]; then
		APPSELECT="E"
		ACTION="erase"
		powerWarning
	elif [[ $APPSELECT == "3" ]]; then
		APPSELECT="C"
		ACTION="clone"
	else
		echo "${BOLD}${RED}Please enter a valid number [1-3].${CLEAR}"
		sleep 0.5
		appSelect
	fi
}



function basicEraseMode_Shred {
	shredMode='autodetect'
	RMODE='Autodetect'
	if [[ $shredMode == 'autodetect' ]]; then
		if [[ $CLIENTDISK =~ $SSD_REGEX ]]; then
			shredMode='zero'
			RMODE='Zero Mode'
		elif [[ $CLIENTDISK =~ $NVME_REGEX ]]; then
			shredMode='nist'
			RMODE='NIST 800-88r1 Mode'
		else
			shredMode='zero'
			RMODE='Zero Mode'
		fi
	fi
}



function advEraseMode_Shred {
	echo ""
	echo "Please choose an erase mode: "
	echo ""

	echo ""
	echo "${BOLD}${BLUE}[1] Autodetect${RESET} (Default)
	-NIST 800-88r1 or Zero Mode depending on drive
	-Best trade off between security and speed"

	echo ""
	echo "${BOLD}${BLUE}[2] NIST 800-88r1${RESET}
	-Fastest for NVME
	-Secure Erase
	-Verification"

	echo ""
	echo "${BOLD}${BLUE}[3] Zero Mode + Quick Verify${RESET}
	-One pass of zeroes
	-Quick verification step"
	
	echo ""
	echo "${BOLD}${BLUE}[4] DOD 5220.22-M/NCSC-TG-025/AFSSI-5020/HMG IS5${RESET}
	-Writes a pass of zeroes, then ones, then a random bit
	-3 passes, 3 verifications"

	echo ""
	echo "${BOLD}${BLUE}[5] RCMP TSSIT OPS-II/VSITR${RESET}
	-Alternates passes between 0's and 1's 6 times
	-Writes random bit, verifies random bit"

	echo ""
	echo "${BOLD}${BLUE}[6] Schneier${RESET}
	-A pass of 1's then a pass of 0's
	-Five passes of a random stream of characters"

	echo ""
	echo "${BOLD}${BLUE}[7] Gutmann${RESET}
	-Four random character passes
	-27 predefined pattern passes
	-Four random character passes"
	
	echo ""
	echo "${BOLD}${BLUE}[8] Verify Only${RESET}
	-Does not write data
	-Different levels of verification
	-Chooses a character to verify"

	echo ""
	echo "${BOLD}${BLUE}[9] Unlock${RESET}
	-Unlocks disk previously locked by this program"

	echo ""
	read -n1 -p "Choose [0-9]: " MODESELECT
	echo ""

	case $MODESELECT in
	1)
	shredMode='autodetect'
	RMODE='Autodetect'
	;;
	2)
	shredMode='nist'
	RMODE='NIST 800-88r1 Mode'
	;;
	3)
	shredMode='zero'
	RMODE='Zero Mode'
	;;
	4)
	shredMode='dod'
	RMODE='DOD 5220.22-M/NCSC-TG-025/AFSSI-5020/HMG IS5 Mode'
	;;
	5)
	shredMode='rcmp'
	RMODE='RCMP TSSIT OPS-II/VSITR Mode'
	;;
	6)
	shredMode='schneier'
	RMODE='Schneier Mode'
	;;
	7)
	shredMode='gutmann'
	RMODE='Gutmann Mode'
	;;
	8)
	shredMode='verify'
	RMODE='Verify Mode'
	;;
	9)
	shredMode='unlock'
	RMODE='Unlock Mode'
	;;
	*)
	modeselect
	;;
	esac
}



function diskSelect {
	DISKNAMES=$(lsblk --nodeps --noheadings -o NAME --exclude 1,2,7,11)
	DISKSIZES=$(lsblk --nodeps --noheadings -o NAME,SIZE --exclude 1,2,7,11)
	CLIENTDISK=""
	local DISKCONF=""
	local a="0"
	local n="0"
	local DISKARR=()
	echo ""
	echo ""
	echo "Which disk do you want to ${BOLD}${ACTION}${RESET}?"
	while read -r line; do
		a=$(( $a + 1 ))
		echo "$a $line"
	done < <(echo "${BOLD}$DISKSIZES${RESET}")
	echo ""
	for i in ${DISKNAMES}; do
		DISKARR+=( "$i" )
	done
	read -n 1 -p "Select a disk: " CLIENTDISK
	for i in ${!DISKARR[@]}; do
		n=$(( $n + 1 ))
		if [[ $n == $CLIENTDISK ]]; then
			CLIENTDISK=${DISKARR[$i]}
		fi
	done
	echo ""
	echo ""
	if [[ $CLIENTDISK =~ nvme* || $CLIENTDISK =~ sd* ]]; then
		echo ""
		echo "The selected disk is ${BOLD}${BLUE}${CLIENTDISK}${RESET}"
		read -n 1 -p "Press 1 to continue or 2 to reselect a disk: " DISKCONF
	    echo ""
	if [[ $DISKCONF != 1 ]]; then
		echo ""
		echo "Reselecting disk...."
		diskSelect
	fi
	else
	    echo ""
	    echo "${BOLD}${RED}Invalid selection.${CLEAR}"
		sleep 0.5
	    diskSelect
	fi
}



function randBit_Shred {
	RANDBIT=$(cat /dev/urandom | xxd -plain | head -1 | cut -c 1)
}



function randPattern_Shred {
	RANDPATTERN=$(cat /dev/urandom | xxd -plain | head -1 | cut -c 8)
}



function writeDisk_Shred {
	SECTIONS='1000'
	DIVPERSEC='2'
	BS='1M'
	DISKSIZEMB=$(( $(blockdev --getsize64 /dev/${CLIENTDISK}) / 1000000 ))
	DISKSIZEGB=$(( $(blockdev --getsize64 /dev/${CLIENTDISK}) / 1000000 / 1000 ))
	SECTIONSIZEMB=$(( ${DISKSIZEMB} / ${SECTIONS} ))
	COUNT=$(( ${SECTIONSIZEMB} / 100 * ${PCNTOFSECTOR} / 2 ))
	PROCFAIL='0'
	local a='0'
	local i='0'

	if [[ -z $CHAR ]]; then
		CHAR='0'
	fi

	if [[ -z $WMODE ]]; then
		WMODE='zero'
	fi

	if [[ -z $PCNTOFSECTOR ]]; then
		CHAR='0'
	fi
	
	if [[ "$WMODE" == 'zero' ]]; then
		SOURCE='cat /dev/zero'
		BITS='null bits'
	fi

	if [[ "$WMODE" == 'random' ]]; then
		SOURCE='cat /dev/urandom'
		BITS='random bits'
	fi

	if [[ "$WMODE" == 'randBit_Shred' ]]; then
		randBit_Shred
		SOURCE="yes \"${RANDBIT}\""
		BITS="a random bit (${RANDBIT})"
	fi

	if [[ "$WMODE" == 'randPattern_Shred' ]]; then
		randPattern_Shred
		SOURCE="yes \"${RANDPATTERN}\""
		BITS="a random bit (${RANDPATTERN})"
	fi

	if [[ "$WMODE" == 'char' ]]; then
		SOURCE="yes \"${CHAR}\""
		BITS="\"${CHAR}\""
	fi

	echo "Filling ${PCNTOFSECTOR}% of ${CLIENTDISK} with a stream of ${BITS}...."
	echo ""

	if [[ $PCNTOFSECTOR == '100' ]]; then
		${SOURCE} | (pv > /dev/${CLIENTDISK})
		return 0
	fi
	
	while [[ $i -le $SECTIONS ]]; do

		echo -e "Writing to section ${i}/${SECTIONS}"
		tput cuu1

		COUNT1=$(shuf -i 1-${COUNT} -n 1)
		SKIP1=$(( $(shuf -i 1-$(( ${SECTIONSIZEMB} / ${DIVPERSEC} )) -n 1) + ${a} / 2 ))
    	${SOURCE} | dd bs=${BS} count=${COUNT1} seek=${SKIP1} of=/dev/${CLIENTDISK} iflag=fullblock status=none 2>/dev/null

		COUNT2=$(shuf -i 1-${COUNT} -n 1)
   		SKIP2=$(( $(shuf -i 1-$(( ${SECTIONSIZEMB} + ${SECTIONSIZEMB} - ${SECTIONSIZEMB} / ${DIVPERSEC} )) -n 1) + ${a} ))
    	${SOURCE} | dd bs=${BS} count=${COUNT2} seek=${SKIP2} of=/dev/${CLIENTDISK} iflag=fullblock status=none 2>/dev/null

		i=$(( ${i} + 1 ))
		a=$(( ${a} + ${SECTIONSIZEMB} ))

	done

	echo "Completely filling the first sector...."
    ${SOURCE} | dd bs=${BS} count=${SECTIONSIZEMB} seek=0 of=/dev/${CLIENTDISK} iflag=fullblock status=none 2>/dev/null
	echo "Completely filling the last sector...."
	${SOURCE} | dd bs=${BS} count=${SECTIONSIZEMB} seek=$(( ${DISKSIZEMB} - ${SECTIONSIZEMB} )) of=/dev/${CLIENTDISK} \
		iflag=fullblock status=none 2>/dev/null

	echo ""
	if [[ $PROCFAIL == '0' ]]; then
		echo "${BOLD}Success! ${PCNTOFSECTOR}% of ${CLIENTDISK} has been overwritten.${RESET}"
		return 0
		else
		echo "${BOLD}${RED}Write failed.${RESET}"
		return 1
	fi
}



function vrfyDisk_Shred {
	
	if [[ -z $SECTIONS ]]; then
		SECTIONS='1000'
	fi

	if [[ -z $PCNTOFSECTOR ]]; then
		PCNTOFSECTOR='100'
	fi

	DIVPERSEC='2'
	BS='1M'
	DISKSIZEMB=$(( $(blockdev --getsize64 /dev/${CLIENTDISK}) / 1000000 ))
	DISKSIZEGB=$(( $(blockdev --getsize64 /dev/${CLIENTDISK}) / 1000000 / 1000 ))
	SECTIONSIZEMB=$(( ${DISKSIZEMB} / ${SECTIONS} ))
	a='0'
	i='0'
	COUNT=$(( ${SECTIONSIZEMB} / 100 * ${PCNTOFSECTOR} / 2 ))
	PROCFAIL='0'

	if [[ -z $CHAR ]]; then
		CHAR='0'
	fi

	echo "Looking for non-${CHAR}'s on ${CLIENTDISK}...."
	echo ""
	echo ""

	if [[ $PCNTOFSECTOR == '100' ]]; then
		FULLVRFY=$(pv /dev/${CLIENTDISK} | grep -oP -m 1 "[^${CHAR}]" | head -1)
		if [[ -z $FULLVRFY ]]; then
        	echo "${BOLD}The drive ${CLIENTDISK} is completely and securely wiped.${RESET}"
			PROCFAIL='0'
		else
			echo "${BOLD}${RED}Bad bits found on device ${CLIENTDISK}. Test has failed.${RESET}"
			PROCFAIL='1'
			return 1
		fi
	fi
	
	while [[ $i -le $SECTIONS && $PROCFAIL == '0' ]]; do

		echo "Verifying section ${i}/${SECTIONS}"
		tput cuu1

		COUNT1=$(shuf -i 1-${COUNT} -n 1)
		SKIP1=$(( $(shuf -i 1-$(( ${SECTIONSIZEMB} / ${DIVPERSEC} )) -n 1) + ${a} / 2 ))
    	if [[ $(dd if=/dev/${CLIENTDISK} bs=${BS} count=${COUNT1} skip=${SKIP1} iflag=fullblock status=none 2>/dev/null \
        	| grep --quiet -oP -m 1 [^${CHAR}]; echo $?) == '0' ]]; then
            	PROCFAIL='1'
            	echo "Bad bits found on device ${CLIENTDISK}."
        		return 1
    	fi

		COUNT2=$(shuf -i 1-${COUNT} -n 1)
    	SKIP2=$(( $(shuf -i 1-$(( ${SECTIONSIZEMB} + ${SECTIONSIZEMB} - ${SECTIONSIZEMB} / ${DIVPERSEC} )) -n 1) + ${a} ))
    	if [[ $(dd if=/dev/${CLIENTDISK} bs=${BS} count=${COUNT2} skip=${SKIP2} iflag=fullblock status=none 2>/dev/null \
        	| grep --quiet -oP -m 1 [^${CHAR}]; echo $?) == '0' ]]; then
            	PROCFAIL='1'
            	echo "Bad bits found on device ${CLIENTDISK}."
        		return 1
    	fi

		i=$(( ${i} + 1 ))
		a=$(( ${a} + ${SECTIONSIZEMB} ))

	done

	echo ""
	echo "Verifing the first sector of the disk...."
	if [[ $(dd if=/dev/${CLIENTDISK} count=${SECTIONSIZEMB} skip=0 iflag=fullblock status=none 2>/dev/null \
        | grep --quiet -oP -m 1 [^${CHAR}]; echo $?) == '0' && $PROCFAIL == '0' ]]; then
            PROCFAIL='1'
            echo "Bad bits found on device ${CLIENTDISK}."
        	return 1
    fi
	echo "Verifing the last sector of the disk...."
    if [[ $(dd if=/dev/${CLIENTDISK} count=${SECTIONSIZEMB} skip=$(( ${DISKSIZEMB} - ${SECTIONSIZEMB} )) \
		iflag=fullblock status=none 2>/dev/null \
        | grep --quiet -oP -m 1 [^${CHAR}]; echo $?) == '0' && $PROCFAIL == '0' ]]; then
            PROCFAIL='1'
            echo "Bad bits found on device ${CLIENTDISK}."
        	return 1
    fi

	echo ""
    echo "${PCNTOFSECTOR}% of ${CLIENTDISK} has been verified."
	if [[ $PROCFAIL == '0' ]]; then
		echo "Test passed successfully!"
		return 0
		else
		echo "Test failed."
		return 1
	fi
}



function secErase_Shred {
	if [[ $CLIENTDISK =~ $SSD_REGEX ]]; then 
		echo "Using Secure Erase on ${CLIENTDISK}. This can take a while, please keep the device powered on...."
		hdparm --user-master u --security-set-pass UHouston /dev/${CLIENTDISK} &>/dev/null
		hdparm --user-master u --security-erase UHouston /dev/${CLIENTDISK} &>/dev/null
	elif [[ $CLIENTDISK =~ $NVME_REGEX ]]; then
		echo "Using Secure Erase on ${CLIENTDISK:0:-2}. This can take a while, please keep the device powered on...."
		nvme format /dev/${CLIENTDISK:0:-2} --ses=1 --namespace-id=1 &>/dev/null
		nvme format /dev/${CLIENTDISK:0:-2} --ses=2 --namespace-id=1 &>/dev/null
	elif [[ $CLIENTDISK =~ $SCSI_REGEX ]]; then
		echo "No compatible SATA or NVME drive is selected. Can't use Secure Erase on ${CLIENTDISK}. Continuing...."
	else
		echo "No compatible SATA or NVME drive is selected. Can't use Secure Erase on ${CLIENTDISK}. Continuing...."
	fi
}



function secUnlock_Shred {
	if [[ $CLIENTDISK =~ $SSD_REGEX ]]; then 
		echo "Unlocking ${CLIENTDISK}, please keep the device powered on...."
		hdparm --user-master u --security-unlock UHouston /dev/${CLIENTDISK} &>/dev/null
		hdparm --user-master u --security-disable UHouston /dev/${CLIENTDISK} &>/dev/null
		echo "${CLIENTDISK} is successfully unlocked."
	elif [[ $CLIENTDISK =~ $NVME_REGEX ]]; then
		echo "Only SATA drives can be unlocked. Failed to unlock ${CLIENTDISK:0:-2}. Continuing...."
	elif [[ $CLIENTDISK =~ $SCSI_REGEX ]]; then
		echo "No compatible SATA or NVME drive is selected. Can't unlock ${CLIENTDISK}. Continuing...."
	else
		echo "No compatible SATA or NVME drive is selected. Can't unlock ${CLIENTDISK}. Continuing...."
	fi
}



function nistMode_Shred {
	clear
	echo ""
	echo "${BOLD}UIT-TSS-TOOLBOX running in ${BLUE}${RMODE}.${RESET}"
	echo ""

	echo ""
	echo "${BOLD}Step [1/3]: ${RESET}"
	PCNTOFSECTOR='25'
	WMODE='random'
	writeDisk_Shred
	
	echo ""
	echo "${BOLD}Step [2/3]: ${RESET}"
	secErase_Shred
	secUnlock_Shred
	
	echo ""
	echo "${BOLD}Step [3/3]: ${RESET}"
	PCNTOFSECTOR='50'
	CHAR='0'
	vrfyDisk_Shred

	if [[ $PROCFAIL == '1' ]]; then
		echo ""
		echo ""
		echo "Step [1/2]: "
		PCNTOFSECTOR='100'
		WMODE='zero'
		writeDisk_Shred

		echo ""
		echo "Step [2/2]: "
		PCNTOFSECTOR='100'
		CHAR='0'
		vrfyDisk_Shred
	fi
}



function zeroMode_Shred {
	clear
	echo ""
	echo "UIT-TSS-TOOLBOX running in ${RMODE}."
	echo ""

	echo ""
	echo "Step [1/2]: "
	echo ""
	WMODE='zero'
	PCNTOFSECTOR='100'
	writeDisk_Shred
	
	echo ""
	echo "Step: [2/2]: "
	PCNTOFSECTOR='10'
	CHAR='0'
	vrfyDisk_Shred
}



function dodMode_Shred {
	
	clear
	echo ""
	echo "UIT-TSS-TOOLBOX running in ${RMODE}."
	echo ""

	echo ""
	echo "Step [1/6]: "
	echo ""
	WMODE='zero'
	PCNTOFSECTOR='100'
	writeDisk_Shred
	
	echo ""
	echo "Step [2/6]: "
	echo ""
	PCNTOFSECTOR='100'
	CHAR='0'
	vrfyDisk_Shred

	echo ""
	echo "Step [3/6]: "
	echo ""
	WMODE='char'
	PCNTOFSECTOR='100'
	CHAR='1'
	writeDisk_Shred
	
	echo ""
	echo "Step [4/6]: "
	echo ""
	PCNTOFSECTOR='100'
	CHAR='1'
	vrfyDisk_Shred

	echo ""
	echo "Step [5/6]: "
	echo ""
	WMODE='randBit_Shred'
	PCNTOFSECTOR='100'
	writeDisk_Shred
	
	echo ""
	echo "Step [6/6]: "
	echo ""
	PCNTOFSECTOR='100'
	CHAR=${RANDBIT}
	vrfyDisk_Shred
}



function rcmpMode_Shred {

	clear
	echo ""
	echo "UIT-TSS-TOOLBOX running in ${RMODE}."
	echo ""

	echo ""
	echo "Step [1/8]: "
	WMODE='zero'
	PCNTOFSECTOR='100'
	writeDisk_Shred

	echo ""
	echo "Step [2/8]: "
	WMODE='char'
	PCNTOFSECTOR='100'
	CHAR='1'
	writeDisk_Shred

	echo ""
	echo "Step [3/8]: "
	WMODE='zero'
	PCNTOFSECTOR='100'
	writeDisk_Shred
	
	echo ""
	echo "Step [4/8]: "
	WMODE='char'
	PCNTOFSECTOR='100'
	CHAR='1'
	writeDisk_Shred

	echo ""
	echo "Step [5/8]: "
	WMODE='zero'
	PCNTOFSECTOR='100'
	writeDisk_Shred

	echo ""
	echo "Step [6/8]: "
	WMODE='char'
	PCNTOFSECTOR='100'
	CHAR='1'
	writeDisk_Shred

	echo ""
	echo "Step [7/8]: "
	echo ""
	WMODE='randBit_Shred'
	PCNTOFSECTOR='100'
	writeDisk_Shred
	
	echo ""
	echo "Step [8/8]: "
	echo ""
	PCNTOFSECTOR='100'
	CHAR="${RANDBIT}"
	vrfyDisk_Shred

}



function schneierMode_Shred {

	clear
	echo ""
	echo "UIT-TSS-TOOLBOX running in ${RMODE}."
	echo ""

	echo ""
	echo "Step [1/7]: "
	WMODE='char'
	PCNTOFSECTOR='100'
	CHAR='1'
	writeDisk_Shred

	echo ""
	echo "Step [2/7]: "
	WMODE='zero'
	PCNTOFSECTOR='100'
	writeDisk_Shred

	echo ""
	echo "Step [3/7]: "
	WMODE='rand'
	PCNTOFSECTOR='100'
	writeDisk_Shred

	echo ""
	echo "Step [4/7]: "
	WMODE='rand'
	PCNTOFSECTOR='100'
	writeDisk_Shred

	echo ""
	echo "Step [5/7]: "
	WMODE='rand'
	PCNTOFSECTOR='100'
	writeDisk_Shred

	echo ""
	echo "Step [6/7]: "
	WMODE='rand'
	PCNTOFSECTOR='100'
	writeDisk_Shred

	echo ""
	echo "Step [7/7]: "
	WMODE='rand'
	PCNTOFSECTOR='100'
	writeDisk_Shred

}



function gutmann {
	COUNT='0'
	GUTMANNARRAY=(01010101 10101010 10010010 01001001 00100100 00000000 00010001 00100010)
	GUTMANNARRAY+=(00110011 01000100 01010101 01100110 01110111 10001000 10011001 10101010)
	GUTMANNARRAY+=(10111011 11001100 11011101 11101110 11111111 10010010 01001001 00100100)
	GUTMANNARRAY+=(01101101 10110110 11011011)

	clear
	echo ""
	echo "UIT-TSS-TOOLBOX running in ${RMODE}."
	echo ""

	
	while [[ $COUNT -le 35 ]]; do 

		if [[ $COUNT -le 4 ]]; then
			echo ""
			echo "[${COUNT}/35] Writing \"${RANDOMPATTERN}\" to ${CLIENTDISK}"
			WMODE='randPattern_Shred'
			PCNTOFSECTOR='100'
			writeDisk_Shred
		fi

		if [[ $COUNT -le 31 && $COUNT -gt 4 ]]; then
			WMODE='char'
			PCNTOFSECTOR='100'
			for i in ${GUTMANNARRAY[@]}; do
				RANDNUM=$(shuf -i 1-26 -n 1)
				CHAR=${GUTMANARRAY[$RANDNUM]}
			done
			echo "[${COUNT}/35] Writing pattern ${CHAR} to ${CLIENTDISK}"
			writeDisk_Shred
		fi

		if [[ $COUNT -gt 31 ]]; then
			echo ""
			echo "[${COUNT}/35] Writing \"${RANDOMPATTERN}\" to ${CLIENTDISK}"
			WMODE='randPattern_Shred'
			PCNTOFSECTOR='100'
			writeDisk_Shred
		fi

	done

	echo ""
	echo ""
	echo "The drive ${CLIENTDISK} is erased using Gutmann's method."
}



function verifyMode_Shred {
	clear
	echo ""
	echo "UIT-TSS-TOOLBOX running in ${RMODE}."
	echo ""
	
	echo ""
	echo "Select the desired level of verification."
	echo ""

	echo ""
	echo "1 Full verification (Default)
	-Longest, verifies every bit on the disk"

	echo ""
	echo "2 Moderate verification
	-1000 divisions, 75% verified"

	echo ""
	echo "3 Medium verification
	-1000 divisions, 50% verified"

	echo ""
	echo "4 Fast verification
	-1000 divisions, 25% verified"

	echo ""
	echo "5 Very fast verification
	-1000 divisions, 10% verified"

	echo ""
	read -n 1 -p "Please select [1-3]: " VRFYSELECT
	echo ""

	case $VRFYSELECT in
	1)
	VRFYMODE='full'
	;;
	2)
	VRFYMODE='moderate'
	;;
	3)
	VRFYMODE='medium'
	;;
	4)
	VRFYMODE='fast'
	;;
	5)
	VRFYMODE='vfast'
	;;
	*)
	echo ""
	echo ""
	echo "Incorrect input selected, please try again"
	sleep 1
	verifyMode_Shred
	;;
	esac

	echo ""
	echo "Mode is ${VRFYMODE}"
	echo ""
	read -p "Which character/pattern would you like to verify?: " CHAR
	echo ""

	if [[ $VRFYMODE == 'full' ]]; then
		PCNTOFSECTOR='100'
		CHAR=${CHAR}
		vrfyDisk_Shred
	fi

	if [[ $VRFYMODE == 'moderate' ]]; then
		SECTIONS='10000'
		PCNTOFSECTOR='75'
		CHAR=${CHAR}
		vrfyDisk_Shred
	fi

	if [[ $VRFYMODE == 'medium' ]]; then
		SECTIONS='5000'
		PCNTOFSECTOR='50'
		CHAR=${CHAR}
		vrfyDisk_Shred
	fi

	if [[ $VRFYMODE == 'fast' ]]; then
		SECTIONS='2500'
		PCNTOFSECTOR='25'
		CHAR=${CHAR}
		vrfyDisk_Shred
	fi

	if [[ $VRFYMODE == 'vfast' ]]; then
		PCNTOFSECTOR='10'
		CHAR=${CHAR}
		vrfyDisk_Shred
	fi
}

function unlockMode_Shred {
	clear
	echo ""
	echo "UIT-TSS-TOOLBOX running in ${RMODE}"
	echo ""
	
	secUnlock_Shred
}

function clientselect_Clone {
	echo ""
	echo "Would you like to run this for HP laptops [1], Dell laptops [2], or Dell desktops [3]?"
	read -n1 -p "Choose [1,2,3] " CLIENTTYPE
	echo ""
	case $CLIENTTYPE in
	1)
	sambaPath='hp'
	cloneImgName='2022Fall-HP'
	;;
	2)
	sambaPath='dell'
	cloneImgName='2022Fall-Dell'
	;;
	3)
	sambaPath='desktops'
	cloneImgName='2022Spring-Win10Desktops'
	;;
	*)
	clientselect
	;;
	esac
}

function confirm_Clone {
	echo ""
	echo ""
	echo ""
	echo "------------------------------"
	echo ""
	echo "Default settings:"
	echo "Server type: Samba"
	echo "User: ${USER}"
	echo "Password: ${PASS}"
	echo "Hostname prefix: ${HOSTNAME}"
	echo ""
	echo ""
	echo "Custom settings:"
	echo "Mode is: ${cloneMode}"
	echo "Server is: ${SERVER}/${SERVERDNS}"
	echo "Samba path is: //${SERVERDNS}/${SMBPATH}"
	echo "Image name is: ${IMAGENAME}"
	echo "Client disk: ${CLIENTDISK}"
	echo ""
		if [[ $cloneMode == "savedisk" ]]; then
		echo "Saving an image will overwrite the previous image stored on the server. \
Please make a backup if necessary."
		fi
		if [[ $cloneMode == "restoredisk" ]]; then
		echo "Restoring an image will overwrite the client's hard drive. \
Please make a backup if necessary."
		fi
	echo ""
	read -p "Press Enter to continue or CTRL + C to exit...."
	clear
}


function execute_Clone {
	SECONDS=0
	start_time=$SECONDS
	sambaUser="cameron"
	sambaPassword="UHouston!"
	sambaServer="10.0.0.1"
	sambaDNS="mickey.uit"
	mkdir /home/partimag
	/usr/bin/umount /home/partimag &>/dev/null
	/usr/bin/mount -t cifs -o user=${sambaUser} -o password=${sambaPassword} //${sambaServer}/${sambaPath} /home/partimag
	if [[ $MODE == "restoredisk" ]]; then
		clear
		echo ""
		echo "Restoring disk ${CLIENTDISK}...."
		sleep 1
		/usr/sbin/ocs-sr --nogui --language en_US.UTF-8 --postaction command --user-mode beginner \
			--verbose -k1 --skip-check-restorable-r ${cloneMode} ${cloneImgName} ${CLIENTDISK}
	fi
	if [[ $MODE == "savedisk" ]]; then
		clear
		echo ""
		echo "Saving disk ${CLIENTDISK}...."
		sleep 1
		/usr/sbin/ocs-sr --nogui --language en_US.UTF-8 --postaction command --user-mode beginner \
			--verbose --skip-enc-ocs-img --skip-fsck-src-part --use-partclone -z9 ${cloneMode} ${cloneImgName} ${CLIENTDISK}
	fi
	cloneElapsed=$(( SECONDS - start_time))
}

function execute_Shred {
	if [[ $APPSELECT == "EC" || $APPSELECT == "E" ]]; then
		SECONDS=0
		start_time=$SECONDS
		if [[ $shredMode == 'nist' ]]; then
			nistMode_Shred
		fi

		if [[ $shredMode == 'zero' ]]; then
			zeroMode_Shred
		fi

		if [[ $shredMode == 'dod' ]]; then
			dodMode_Shred
		fi

		if [[ $shredMode == 'rcmp' ]]; then
			rcmpMode_Shred
		fi
	
		if [[ $shredMode == 'gutmann' ]]; then
			gutmann
		fi
	
		if [[ $shredMode == 'schneier' ]]; then
			schneierMode_Shred
		fi
	
		if [[ $shredMode == 'verify' ]]; then
			verifyMode_Shred
		fi

		if [[ $shredMode == 'unlock' ]]; then
			unlockMode_Shred
		fi
		shredElapsed=$(( SECONDS - start_time ))
	fi
}


function execute {
	if [[ $APPSELECT == "EC" ]]; then
		clientselect_Clone
		basicEraseMode_Shred
		execute_Shred
		execute_Clone
	elif [[ $APPSELECT == "E" ]]; then
		advEraseMode_Shred
		execute_Shred
	elif [[ $APPSELECT == "C" ]]; then
		clientselect_Clone
		execute_Clone
	else
		echo "${RED}Error - Invalid application selected.${CLEAR}"
	fi
}



function terminate {
	elapsed=$(( cloneElapsed + shredElapsed ))
	echo ""
	echo ""
	echo ""
	if [[ $cloneMode == "restoredisk" ]]; then
		ssh cameron@mickey.uit 'echo "UIT-TSS-CLONE" >> /home/cameron/laptop-reimage-count.today.txt' &>/dev/null
		scp cameron@mickey.uit:/home/cameron/laptop-reimage-count.today.txt /root/laptop-reimage-count.today.txt &>/dev/null
		scp cameron@mickey.uit:/home/cameron/laptop-image-update.txt /root/laptop-image-update.txt &>/dev/null
		TODAY=$(cat /root/laptop-reimage-count.today.txt | wc -l)
		TIME=$(eval "echo $(date -ud "@$elapsed" +'%M minutes')")
		UPDATE=$(cat /root/laptop-image-update.txt)
		echo ""
		echo -ne "This computer has been erased and reimaged from the server \"${sambaDNS}\" using the image"
		echo -ne "\"${sambaPath}\", which was last updated on ${UPDATE}. Today, ${TODAY} computers have been"
		echo "reimaged, with this reimage taking ${TIME}."
	fi
	
	if [[ $cloneMode == "savedisk" ]]; then
		elapsed=$(( SECONDS - start_time ))
		scp cameron@mickey.uit:/home/cameron/laptop-reimage-count.today.txt \
			/root/laptop-reimage-count.today.txt &>/dev/null
		scp cameron@mickey.uit:/home/cameron/laptop-image-update.txt \
			/root/laptop-image-update.txt &>/dev/null
		TODAY=$(cat /root/laptop-reimage-count.today.txt | wc -l)
		TIME=$(eval "echo $(date -ud "@$elapsed" +'%M minutes')")
		UPDATE=$(cat /root/laptop-image-update.txt)
		echo ""
		echo -ne "The image \"${sambaPath}\" has been successfully updated and saved to the server \"${sambaDNS}\"."
		echo -ne "The process took ${TIME} to complete. \"${sambaPath}\" was last updated on ${UPDATE}."
		echo -e "Today, ${TODAY} computers have been reimaged and/or erased."
		ssh cameron@mickey.uit 'echo "$(TZ='America/Chicago' date "+%A, %B %d at %I:%M%p")" > \
			/home/cameron/laptop-image-update.txt' &>/dev/null
	fi
	
	echo ""
	/usr/bin/play /root/oven.mp3 &> /dev/null
	read -p "Process has finished. Press Enter to reboot..."
	reboot
}



intro
appSelect
diskSelect
execute
terminate