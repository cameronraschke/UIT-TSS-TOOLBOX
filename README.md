# UIT-TSS-TOOLBOX
UIT-TSS-TOOLBOX includes two main programs: _UIT-TSS-CONFIGURE-CLONE_ and _UIT-TSS-CONFIGURE-SHRED_. UIT-TSS-TOOLBOX can only be ran on Debian/Ubuntu-based systems. Any program inside of UIT-TSS-TOOLBOX is not intended for use outside of the specific configuration specified in the source code and at the end of this document.

_UIT-TSS-CONFIGURE-CLONE_ and _UIT-TSS-CONFIGURE-SHRED_ create and configure a custom and minimal ISO of Debian Live that is tailored to do their respective functions. _UIT-TSS-CONFIGURE-CLONE_ and _UIT-TSS-CONFIGURE-SHRED_ also create two more programs: _UIT-TSS-FLASH-CLONE_ and _UIT-TSS-FLASH-SHRED_, which will flash either the _CLONE_ or the _SHRED_ ISO file to a drive of your choice.

Both packages are written in Bash (Unix Shell), so they can only be ran on Linux-based operating systems. More specifically, the CONFIGURE shells can only be ran on Debian/Ubuntu-based systems. The _FLASH_ programs can be ran on any Linux OS, as long as the ISO files are where they are supposed to be (check end of document).

UIT-TSS-CONFIGURE-CLONE is inteded to clone computers based on a specific client-server setup.

UIT-TSS-CONFIGURE-SHRED is intended to completely wipe hard drives of all data. UIT-TSS-SHRED is not intended to be connected to any network and doesn't have any networking outside of what is present in the Kernel.

Specific server configuration:
Server 1:
IP: 10.0.0.1
DNS address: mickey.uit
Samba shares: 2022Fall-HP, 2022Fall-Dell, 2022Fall-Win11Desktops
Username: cameron
Password: UHouston!
Hostname prefix of client: TSS-RENTAL-LAPTOP

Server 2:
IP: 10.0.0.2
DNS address: minnie.uit
Samba shares: 2022Fall-HP, 2022Fall-Dell, 2022Fall-Win11Desktops
Username: cameron
Password: UHouston!
Hostname prefix of client: TSS-RENTAL-LAPTOP

Important file locations:
