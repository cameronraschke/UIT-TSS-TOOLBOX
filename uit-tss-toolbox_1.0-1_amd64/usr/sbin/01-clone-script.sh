#!/bin/bash

function intro {
	clear

	echo ""
	echo "Configurating Kernel..."
	/usr/sbin/sysctl -w "kernel.printk=2 4 1 2" &>/dev/null
	/usr/sbin/sysctl -w "kernel.kptr_restrict=1" &>/dev/null
	/usr/sbin/sysctl -w "vm.mmap_min_addr=65536" &>/dev/null
	/usr/sbin/sysctl -p &>/dev/null
	modprobe efivars &>/dev/null
	echo "Done."
	echo ""

	echo "Configuring audio..."
	/usr/bin/amixer sset Master 100% &>/dev/null
	/usr/bin/amixer set Master unmute &>/dev/null
	/usr/bin/amixer sset Speakers 100% &>/dev/null
	/usr/sbin/amixer set Speakers unmute &>/dev/null
	echo "Done."
	echo ""

	echo "Changing font..."
	/usr/bin/setfont /usr/share/consolefonts/Lat7-TerminusBold16.psf.gz
	echo "Done."
	echo ""

	sleep 1

	clear

	SERVER=''
	SERVERDNS=''
	USER='cameron'
	PASS='UHouston!'
	SMBPATH=''
	HOSTNAME='TSS-RENTAL-LAPTOP'
	CLIENTDISK=''
	IMAGENAME=''
	MODE=''


	clear
	echo ""
    echo ""
	echo "Welcome to UIT-TSS-CLONE by Cameron Raschke."
    echo ""
	echo "Press CTRL + C at any time to exit"
	echo "If you have exited and want to restart UIT-TSS-CLONE, press CTRL + D"
	echo ""
	echo ""
echo '
| |    | |  | |     | |
| |    | |  | |_____| |
| |    | |  | |_____| |
| |____| |  | |     | |
\________/  | |     | |
'
	echo ""
	echo "------------------------------"
	echo ""
echo 'Checklist:
	-Physical connections
	   * Make sure that both power and ethernet are plugged in to the client.
	-General best practices
	   * Sanitize laptops with cleaner before imaging them.
	   * Reset BIOS to default/factory settings before imaging.
	-Dells
	   * Make sure SATA mode is in AHCI mode and not RAID mode.
	      * This is usually under "System Configuration" or "Storage" in BIOS.
	      * Every Dell is in RAID mode by default. 
	      * If you reset BIOS, make sure you change SATA mode after the reset.
'
	read -p "Please remove the thumb drive and press Enter...."
	clear
}

function modeselect {
	echo ""
	echo "Do you want to clone a returned laptop/desktop [1] or save an image to the server [2]?"
	read -n1 -p "Choose [1,2] " MODE
	echo ""
	case $MODE in
	1)
	MODE='restoredisk'
	ACTION='clone'
	;;
	2)
	MODE='savedisk'
	ACTION='save'
	;;
	*)
	modeselect
	;;
	esac
}

function diskselect {
	DISKNAMES=$(lsblk --nodeps --noheadings -o NAME --exclude 1,2,7,11)
	DISKSIZES=$(lsblk --nodeps --noheadings -o NAME,SIZE --exclude 1,2,7,11)
	CLIENTDISK=""
	DISKCONF=""
	a="0"
	n="0"
	DISKARR=()
	echo ""
	echo ""
	echo "Which disk do you want to ${ACTION}?"
	while read -r line; do
		a=$(( $a + 1 ))
		echo "$a $line"
	done < <(echo "$DISKSIZES")
	echo ""
	for i in ${DISKNAMES}; do
		DISKARR+=( "$i" )
	done
	read -n1 -p "Select a disk: " CLIENTDISK
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
		echo "The selected disk is ${CLIENTDISK}"
		read -n1 -p "Press 1 to continue or 2 to reselect a disk: " DISKCONF
	    echo ""
	if [[ $DISKCONF != 1 ]]; then
		echo ""
		echo "Reselecting disk...."
		diskselect
	fi
	else
	    echo ""
	    echo "Invalid selection."
	    diskselect
	fi
}

function serverselect {
	SERVER='10.0.0.1'
	SERVERDNS='mickey.uit'
}

function clientselect {
	echo ""
	echo "Would you like to run this for HP laptops [1], Dell laptops [2], or Dell desktops [3]?"
	read -n1 -p "Choose [1,2,3] " CLIENTTYPE
	echo ""
	case $CLIENTTYPE in
	1)
	SMBPATH='hp'
	IMAGENAME='2022Fall-HP'
	;;
	2)
	SMBPATH='dell'
	IMAGENAME='2022Fall-Dell'
	;;
	3)
	SMBPATH='desktops'
	IMAGENAME='2022Spring-Win10Desktops'
	;;
	*)
	clientselect
	;;
	esac
}

function confirm {
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
	echo "Mode is: ${MODE}"
	echo "Server is: ${SERVER}/${SERVERDNS}"
	echo "Samba path is: //${SERVERDNS}/${SMBPATH}"
	echo "Image name is: ${IMAGENAME}"
	echo "Client disk: ${CLIENTDISK}"
	echo ""
		if [[ $MODE == "savedisk" ]]; then
		echo "Saving an image will overwrite the previous image stored on the server. \
Please make a backup if necessary."
		fi
		if [[ $MODE == "restoredisk" ]]; then
		echo "Restoring an image will overwrite the client's hard drive. \
Please make a backup if necessary."
		fi
	echo ""
	read -p "Press Enter to continue or CTRL + C to exit...."
	clear
}

function execute {
	SECONDS=0
	start_time=$SECONDS
	mkdir /home/partimag
	/usr/bin/umount /home/partimag &>/dev/null
	/usr/bin/mount -t cifs -o user=${USER} -o password=${PASS} //${SERVER}/${SMBPATH} /home/partimag
	if [[ $MODE == "restoredisk" ]]; then
	clear
	echo ""
	echo "Restoring disk ${CLIENTDISK}...."
	sleep 1
	/usr/sbin/ocs-sr --nogui --language en_US.UTF-8 --postaction command --user-mode beginner \
		--verbose -k1 --skip-check-restorable-r ${MODE} ${IMAGENAME} ${CLIENTDISK}
	fi
	if [[ $MODE == "savedisk" ]]; then
	clear
	echo ""
	echo "Saving disk ${CLIENTDISK}...."
	sleep 1
	/usr/sbin/ocs-sr --nogui --language en_US.UTF-8 --postaction command --user-mode beginner \
		--verbose --skip-enc-ocs-img --skip-fsck-src-part --use-partclone -z9 ${MODE} ${IMAGENAME} ${CLIENTDISK}
	fi
}

function terminate {
	elapsed=$(( SECONDS - start_time ))
	echo ""
	echo ""
	echo ""
	echo "Sending Email..."
	if [[ $MODE == "restoredisk" ]]; then
	ssh cameron@mickey.uit 'echo "UIT-TSS-CLONE" >> /home/cameron/laptop-reimage-count.today.txt' &>/dev/null
	scp cameron@mickey.uit:/home/cameron/laptop-reimage-count.today.txt /root/laptop-reimage-count.today.txt &>/dev/null
	scp cameron@mickey.uit:/home/cameron/laptop-image-update.txt /root/laptop-image-update.txt &>/dev/null
	TODAY=$(cat /root/laptop-reimage-count.today.txt | wc -l)
	TIME=$(eval "echo $(date -ud "@$elapsed" +'%M minutes')")
	UPDATE=$(cat /root/laptop-image-update.txt)
	echo ""
	echo "This computer has been reimaged from the server ${SERVERDNS} using the image \
${SMBPATH}, which was last updated on ${UPDATE}. Today, ${TODAY} computers have been \
reimaged, with this reimage taking ${TIME}."
	fi
	
	if [[ $MODE == "savedisk" ]]; then
	elapsed=$(( SECONDS - start_time ))
	scp cameron@mickey.uit:/home/cameron/laptop-reimage-count.today.txt \
		/root/laptop-reimage-count.today.txt &>/dev/null
	scp cameron@mickey.uit:/home/cameron/laptop-image-update.txt \
		/root/laptop-image-update.txt &>/dev/null
	TODAY=$(cat /root/laptop-reimage-count.today.txt | wc -l)
	TIME=$(eval "echo $(date -ud "@$elapsed" +'%M minutes')")
	UPDATE=$(cat /root/laptop-image-update.txt)
	echo ""
	echo "The image ${SMBPATH} has been successfully updated and saved to the server ${SERVERDNS}. \
The process took ${TIME} to complete. ${SMBPATH} was last updated on ${UPDATE}. \
Today, ${TODAY} computers have been reimaged."
	ssh cameron@mickey.uit 'echo "$(TZ='America/Chicago' date "+%A, %B %d at %I:%M%p")" > \
		/home/cameron/laptop-image-update.txt' &>/dev/null
	fi
	
	echo ""
	/usr/bin/play /root/oven.mp3 &> /dev/null
	read -p "Process has finished. Press Enter to reboot..."
	reboot
}

intro
modeselect
diskselect
clientselect
serverselect
confirm
execute
terminate