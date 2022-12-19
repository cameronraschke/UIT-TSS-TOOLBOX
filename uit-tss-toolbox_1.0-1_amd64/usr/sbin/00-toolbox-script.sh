#!/bin/bash

SSD_REGEX='sd.*'
NVME_REGEX='nvme.*'
SCSI_REGEX='hd.*'

	echo "Configuring audio..."
	/usr/bin/amixer sset Master 100% &>/dev/null
	/usr/bin/amixer set Master unmute &>/dev/null
	/usr/bin/amixer sset Speakers 100% &>/dev/null
	/usr/sbin/amixer set Speakers unmute &>/dev/null
	echo "Done."
	echo ""

	echo "Configuring font..."
	/usr/bin/setfont /usr/share/consolefonts/Lat7-TerminusBold16.psf.gz
	echo "Done."
	echo ""

	sleep 1

	clear
}

function intro {
    echo ""
	echo "Welcome to UIT-TSS-TOOLBOX by Cameron Raschke (caraschke@uh.edu)"
	echo ""
	echo "Press CTRL + C at any time to exit UIT-TSS-TOOLBOX"
	echo "If you have exited UIT-TSS-TOOLBOX and you want to restart it, press CTRL + D"
	echo ""
	echo ""
	echo ""
	echo "| |    | |  | |     | |"
	echo "| |    | |  | |_____| |"
	echo "| |    | |  | |_____| |"
	echo "| |____| |  | |     | |"
	echo "\________/  | |     | |"
	echo ""
	echo "------------------------------"
	echo ""
	echo 'Checklist:
	-General best practices
	   * Sanitize laptops with cleaner before imaging them.
	   * Reset BIOS to default/factory settings before imaging.
	-Physical connections
	   * Make sure that power and ethernet are plugged in to the client.
	   
	-Dells
	   * Make sure SATA mode is in AHCI mode and not RAID mode.
	      * This is usually under "System Configuration" or "Storage" in BIOS.
	      * Every Dell is in RAID mode by default. 
	      * If you reset BIOS, make sure you change SATA mode to AHCI after the reset."
	-If Erasing
	    * Do not use Secure Erase on USB drives or drives connected over USB.
	      * Autodetect mode and NIST 800-88r1 mode can both do Secure Erase.'
	echo ""
	read -p "Please remove the thumb drive and press Enter...."
	clear
}

}