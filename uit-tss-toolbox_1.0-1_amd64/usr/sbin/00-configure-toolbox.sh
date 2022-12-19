#!/bin/bash

function confirmDelete {
    read -n1 -p "Are you sure you want to delete the old directory? [y/n]: " CONFIRM
    if [[ $CONFIRM == "y" ]]; then
        return
    elif [[ $CONFIRM == "n" ]]; then
        exit 1
    else
        confirmDelete
    fi
}

apt purge live-build -y
wget http://ftp.us.debian.org/debian/pool/main/l/live-build/live-build_20220505_all.deb \
	--output-document=/opt/debian-live.deb
apt install /opt/debian-live.deb -y

apt install dosfstools -y

rm -r /opt/UIT-TSS-TOOLBOX/
mkdir /opt/UIT-TSS-TOOLBOX/

( cd /opt/UIT-TSS-TOOLBOX/ && \

	lb clean && \

	lb config \
		--apt apt \
		--apt-recommends true \
		--apt-secure true \
		--apt-source-archives true \
		--architecture amd64 \
		--archive-areas 'main' \
		--binary-filesystem fat32 \
		--binary-images iso-hybrid \
		--bootappend-live "boot=live live-media=removable toram username=root hostname=UIT-TSS-TOOLBOX \
			timezone=America/Chicago locales=en_US.UTF-8" \
		--bootloaders "grub-efi syslinux" \
		--chroot-filesystem squashfs \
		--compression gzip \
		--clean \
		--debian-installer live \
		--debian-installer-distribution bullseye \
		--distribution bullseye \
		--debootstrap-options "--variant=minbase --arch=amd64" \
		--hdd-label UIT-TSS-TOOLBOX \
		--image-name UIT-TSS-TOOLBOX \
		--initramfs live-boot \
		--initsystem systemd \
		--iso-application UIT-TSS-TOOLBOX \
		--iso-preparer "Cameron Raschke caraschke@uh.edu" \
		--iso-publisher "Cameron Raschke caraschke@uh.edu" \
		--iso-volume UIT-TSS-TOOLBOX \
		--mode debian \
		--system live \
		--uefi-secure-boot enable \
		--updates true)

cat <<'EOF' > /opt/UIT-TSS-TOOLBOX/config/bootloaders/isolinux/isolinux.cfg
UI vesamenu.c32

MENU TITLE Boot Menu
DEFAULT linux
        TIMEOUT 1
        MENU RESOLUTION 640 480
        SAY Now booting into UIT-TSS-TOOLBOX by Cameron Raschke
label linux
        menu label UIT-TSS-TOOLBOX by Cameron Raschke
        menu default
        linux /live/vmlinuz
        initrd /live/initrd.img
        append @APPEND_LIVE@
EOF

rm -r /opt/UIT-TSS-TOOLBOX/config/bootloaders/grub-pc/*
cat <<'EOF' > /opt/UIT-TSS-TOOLBOX/config/bootloaders/grub-pc/grub.cfg
insmod part_gpt
insmod part_msdos
insmod fat
insmod iso9660

set default="0"
set timeout=0
set timeout_style=hidden

menuentry "UIT-TSS-TOOLBOX by Cameron Raschke" {
        linux @KERNEL_LIVE@ @APPEND_LIVE@
        initrd @INITRD_LIVE@
}
EOF

cat <<'EOF' > /opt/UIT-TSS-TOOLBOX/config/package-lists/live.list.chroot
live-boot
live-config
live-config-systemd
systemd-sysv
openssl
less
iproute2
curl
wget
openssh-client
nano
vim
clonezilla
cifs-utils
passwd
locales
firmware-linux-free
apt-utils
parted
partclone
partimage
gparted
ethtool
isc-dhcp-client
net-tools
gzip
lzip
zstd
sudo
chntpw
libasound2
libasound2-plugins
alsa-utils
sox
libsox-fmt-all
ca-certificates
sshpass
kbd
procps
dosfstools
efibootmgr
efivar
ntfs-3g
iptables
hdparm
util-linux
nvme-cli
coreutils
pv
EOF

mkdir -p /opt/UIT-TSS-TOOLBOX/config/includes.chroot/root/
cat <<'EOF' > /opt/UIT-TSS-TOOLBOX/config/hooks/live/0100-uit-tss-toolbox-setup.hook.chroot

touch /root/.ssh_passwd
echo "UHouston!" > /root/.ssh_passwd
chown root:root /root/.ssh_passwd
chmod 600 /root/.ssh_passwd

sysctl --quiet --write "net.ipv6.conf.all.disable_ipv6=1"
sysctl --quiet --write "net.ipv6.conf.default.disable_ipv6=1"
sysctl --quiet --write "net.ipv4.conf.default.rp_filter=2"
sysctl --quiet --write "net.ipv4.conf.all.rp_filter=2"
sysctl --quiet --write "net.ipv4.ip_forward=0"
sysctl --quiet --write "net.ipv6.conf.all.forwarding=1"
sysctl --quiet --write "net.ipv6.conf.default.forwarding=1"
sysctl --load

sysctl --quiet --write "kernel.printk=2 4 1 2"
sysctl --quiet --write "kernel.kptr_restrict=1"
sysctl --quiet --write "vm.mmap_min_addr=65536"
sysctl --load

echo "uit-tss-toolbox.cameronraschke.com" > /etc/hostname
echo -e "\nWelcome to UIT-TSS-TOOLBOX by Cameron Raschke.\n" > /etc/motd
echo -e "\nWelcome to UIT-TSS-TOOLBOX by Cameron Raschke.\n" > /etc/issue.net
echo -e "Banner /etc/issue.net" >> /etc/ssh/sshd_config
EOF

chmod 777 /opt/UIT-TSS-TOOLBOX/config/hooks/live/0100-uit-tss-toolbox-setup.hook.chroot

mkdir -p /opt/UIT-TSS-TOOLBOX/config/includes.chroot/root/
wget https://soundboardguy.com/wp-content/uploads/2022/01/Oven-Timer-Ding.mp3 \
	--output-document=/opt/UIT-TSS-TOOLBOX/config/includes.chroot/root/oven.mp3

mkdir -p /opt/UIT-TSS-TOOLBOX/config/includes.chroot/root
touch /opt/UIT-TSS-TOOLBOX/config/includes.chroot/root/.bash_profile

cat <<'EOF' > /opt/UIT-TSS-TOOLBOX/config/includes.chroot/root/.bash_profile
#!/bin/bash

clear
echo ""
echo -e "Welcome to UIT-TSS-TOOLBOX by Cameron Raschke."
echo ""

echo ""
echo "Configurating Kernel..."
sysctl --quiet --write "kernel.printk=2 4 1 2"
sysctl --quiet --write "kernel.kptr_restrict=1"
sysctl --quiet --write "vm.mmap_min_addr=65536"
sysctl --load
echo "Done."

echo ""
echo "Configuring networking..."
sysctl --quiet --write "net.ipv6.conf.all.disable_ipv6=1"
sysctl --quiet --write "net.ipv6.conf.default.disable_ipv6=1"
sysctl --quiet --write "net.ipv4.conf.default.rp_filter=2"
sysctl --quiet --write "net.ipv4.conf.all.rp_filter=2"
sysctl --quiet --write "net.ipv4.ip_forward=0"
sysctl --quiet --write "net.ipv6.conf.all.forwarding=1"
sysctl --quiet --write "net.ipv6.conf.default.forwarding=1"
sysctl --load

INTERFACES=$(cat /proc/net/dev | grep -oP '.*:\ ' | sed 's/://g' | sed 's/lo//g' | sed 's/w.*//g' | sed 's/[[:space:]]//g')
for iface in $INTERFACES; do
	ip link set "${iface}" up
	dhclient -r "${iface}"
	dhclient -4 "${iface}"
done

echo "Done."

echo ""
echo "Configuring audio..."
/usr/bin/amixer sset Master 100%
/usr/bin/amixer set Master unmute
/usr/bin/amixer sset Speakers 100%
/usr/sbin/amixer set Speakers unmute
echo "Done."

echo ""
echo "Changing font..."
/usr/bin/setfont /usr/share/consolefonts/Lat7-TerminusBold16.psf.gz
echo "Done."

echo ""
echo "Configuring SSH..."
if [ ! -f /root/.ssh/id_rsa ]; then
ssh-keygen -t rsa -b 4096 -f /root/.ssh/id_rsa -N ""
fi
sshpass -f /root/.ssh_passwd ssh-copy-id \
	-o "StrictHostKeyChecking=no" cameron@mickey.uit
scp cameron@mickey.uit:/home/cameron/toolbox-script.sh /home
echo "Done."

sleep 1
chmod 755 /home/toolbox-script.sh
/home/toolbox-script.sh
EOF