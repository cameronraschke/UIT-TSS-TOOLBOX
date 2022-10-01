# UIT-TSS-TOOLBOX
UIT-TSS-TOOLBOX includes two main programs: _UIT-TSS-CONFIGURE-CLONE_ and _UIT-TSS-CONFIGURE-SHRED_. UIT-TSS-TOOLBOX can only be ran on Debian/Ubuntu-based systems (preferably stock Debian). Any program inside of UIT-TSS-TOOLBOX is not intended for use outside of the specific configuration specified in the source code and at the end of this document.
<br />
<br />
_UIT-TSS-CONFIGURE-CLONE_ and _UIT-TSS-CONFIGURE-SHRED_ create and configure a custom and minimal ISO of Debian Live that is tailored to do its respective function. _UIT-TSS-CONFIGURE-CLONE_ and _UIT-TSS-CONFIGURE-SHRED_ also create two more programs: _UIT-TSS-FLASH-CLONE_ and _UIT-TSS-FLASH-SHRED_, which will flash either the _CLONE_ or the _SHRED_ ISO file to a drive of your choice.
<br />
<br />
Both packages are written in Bash (Unix Shell), so they can only be ran on Linux-based operating systems. More specifically, the _CONFIGURE_ shells can only be ran on Debian/Ubuntu-based systems. The _FLASH_ programs can be ran on any Linux OS, as long as the ISO files are where they are supposed to be (check end of document).
<br />
<br />
_UIT-TSS-CONFIGURE-CLONE_ is inteded to clone computers based on a specific client-server setup.
<br />
<br />
_UIT-TSS-CONFIGURE-SHRED_ is intended to completely wipe hard drives of all data. _UIT-TSS-CONFIGURE-SHRED_ is not intended to be connected to any network and doesn't have any networking outside of what is present in the Kernel.
<br />
<br />
<br />
## Installing UIT-TSS-TOOLBOX
Make sure you are root
<br />
<br />
<code>sudo -i</code>
<br />
<br />
Download my repo from GitHub
<br />
<br />
<code>git clone https://github.com/cameronraschke/UIT-TSS-TOOLBOX</code>
<br />
<br />
Change directory to the newly downloaded Git repo
<br />
<br />
<code>cd ./UIT-TSS-TOOLBOX</code>
<br />
<br />
Change file permissions
<br />
<br />
<code>chmod 755 ./uit-tss-toolbox_1.0-1_amd64/DEBIAN/postinst</code>
<br />
<br />
Make the folder into a .deb package
<br />
<br />
<code>dpkg-deb --build --root-owner-group ./uit-tss-toolbox_1.0-1_amd64</code>
<br />
<br />
Install the new .deb file
<br />
<br />
<code>dpkg -i ./uit-tss-toolbox_1.0-1_amd64.deb</code>
<br />
<br />
<br />
## Removing uit-tss-toolbox
Type in this command to completely purge uit-tss-toolbox
<br />
<br />
<code>dpkg --purge uit-tss-toolbox</code>
<br />
<br />
<br />
### Specific server configuration:
Server 1:\
IP: 10.0.0.1\
DNS address: mickey.uit\
Samba shares: 2022Fall-HP, 2022Fall-Dell, 2022Fall-Win11Desktops\
Username: cameron\
Password: UHouston!\
Hostname prefix of client: TSS-RENTAL-LAPTOP\
<br />
Server 2:\
IP: 10.0.0.2\
DNS address: minnie.uit\
Samba shares: 2022Fall-HP, 2022Fall-Dell, 2022Fall-Win11Desktops\
Samba username: cameron\
Samba password: UHouston!\
Hostname prefix of client: TSS-RENTAL-LAPTOP\
<br />
### Important file locations:
Main directory: /opt/UIT-TSS-CLONE/ or /opt/UIT-TSS-SHRED/\
ISO file location: /opt/UIT-TSS-CLONE/UIT-TSS-CLONE-amd64.hybrid.iso or /opt/UIT-TSS-CLONE/UIT-TSS-SHRED-amd64.hybrid.iso
