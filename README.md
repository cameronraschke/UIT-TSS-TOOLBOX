# UIT-TSS-TOOLBOX
UIT-TSS-TOOLBOX includes two main programs: _UIT-TSS-CONFIGURE-CLONE_ and _UIT-TSS-CONFIGURE-SHRED_. UIT-TSS-TOOLBOX can only be ran on Debian/Ubuntu-based systems. Any program inside of UIT-TSS-TOOLBOX is not intended for use outside of the specific configuration specified in the source code and at the end of this document.


_UIT-TSS-CONFIGURE-CLONE_ and _UIT-TSS-CONFIGURE-SHRED_ create and configure a custom and minimal ISO of Debian Live that is tailored to do their respective functions. _UIT-TSS-CONFIGURE-CLONE_ and _UIT-TSS-CONFIGURE-SHRED_ also create two more programs: _UIT-TSS-FLASH-CLONE_ and _UIT-TSS-FLASH-SHRED_, which will flash either the _CLONE_ or the _SHRED_ ISO file to a drive of your choice.


Both packages are written in Bash (Unix Shell), so they can only be ran on Linux-based operating systems. More specifically, the _CONFIGURE_ shells can only be ran on Debian/Ubuntu-based systems. The _FLASH_ programs can be ran on any Linux OS, as long as the ISO files are where they are supposed to be (check end of document).


_UIT-TSS-CONFIGURE-CLONE_ is inteded to clone computers based on a specific client-server setup.


_UIT-TSS-CONFIGURE-SHRED_ is intended to completely wipe hard drives of all data. _UIT-TSS-CONFIGURE-SHRED_ is not intended to be connected to any network and doesn't have any networking outside of what is present in the Kernel.
<br />
<br />
<br />
**Specific server configuration:**__
Server 1:__
IP: 10.0.0.1__
DNS address: mickey.uit__
Samba shares: 2022Fall-HP, 2022Fall-Dell, 2022Fall-Win11Desktops__
Username: cameron__
Password: UHouston!__
Hostname prefix of client: TSS-RENTAL-LAPTOP__
<br />
Server 2:__
IP: 10.0.0.2__
DNS address: minnie.uit__
Samba shares: 2022Fall-HP, 2022Fall-Dell, 2022Fall-Win11Desktops__
Username: cameron__
Password: UHouston!__
Hostname prefix of client: TSS-RENTAL-LAPTOP__
<br />
<br />
**Important file locations:**__
Main directory: /opt/UIT-TSS-CLONE/ or /opt/UIT-TSS-SHRED/__
ISO file location: /opt/UIT-TSS-CLONE/UIT-TSS-CLONE-amd64.hybrid.iso or /opt/UIT-TSS-CLONE/UIT-TSS-SHRED-amd64.hybrid.iso__
