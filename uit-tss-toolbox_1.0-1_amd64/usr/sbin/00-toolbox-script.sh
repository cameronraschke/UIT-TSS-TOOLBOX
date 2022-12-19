#!/bin/bash

SSD_REGEX='sd.*'
NVME_REGEX='nvme.*'
SCSI_REGEX='hd.*'


function initialize {
	INTERFACES=$(cat /proc/net/dev | grep -oP '.*:\ ' | sed 's/://g' | sed 's/[[:space:]]//g')
    clear
    echo ""
    echo "Initializing UIT-TSS-TOOLBOX by Cameron Raschke"
    echo ""

    echo ""
    echo "Setting all interfaces down"
    for i in $INTERFACES; do
        ip link set down $i
    done

    echo ""
    echo "Flushing all IP addresses"
    for i in $INTERFACES; do
        ip addr flush dev $i
    done

    echo ""
    echo "Removing all interfaces from bonds and bridges"
    for i in $INTERFACES; do
        ip link set dev $i nomaster
    done

    echo ""
    echo "Flushing all routes"
    ip route flush table main

	echo ""
	echo "Configuring Kernel..."
	iptables -P INPUT DROP &>/dev/null
	iptables -P FORWARD DROP &>/dev/null
	iptables -P OUTPUT DROP &>/dev/null
	sysctl -w "net.ipv6.conf.all.disable_ipv6=1" &>/dev/null
	sysctl -w "net.ipv6.conf.default.disable_ipv6=1" &>/dev/null
	sysctl -w "net.ipv4.conf.default.rp_filter=2" &>/dev/null
	sysctl -w "net.ipv4.conf.all.rp_filter=2" &>/dev/null
	sysctl -w "net.ipv4.ip_forward=0" &>/dev/null
	sysctl -w "net.ipv6.conf.all.forwarding=1" &>/dev/null
	sysctl -w  "net.ipv6.conf.default.forwarding=1" &>/dev/null
	sysctl -w "kernel.printk=2 4 1 2" &>/dev/null
	sysctl -w "kernel.kptr_restrict=1" &>/dev/null
	sysctl -w "vm.mmap_min_addr=65536" &>/dev/null
	sysctl -p &>/dev/null
	echo "Done."
	echo ""
 
	echo "Configuring audio..."
	amixer sset Master 100% &>/dev/null
	amixer set Master unmute &>/dev/null
	amixer sset Speakers 100% &>/dev/null
	amixer set Speakers unmute &>/dev/null
	echo "Done."
	echo ""

	echo "Configuring font..."
	setfont /usr/share/consolefonts/Lat7-TerminusBold16.psf.gz
	echo "Done."
	echo ""

	sleep 1

	clear
}


function appselect {
    echo ""
}