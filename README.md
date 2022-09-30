# UIT-TSS-TOOLBOX
UIT-TSS-TOOLBOX includes two main programs: UIT-TSS-CONFIGURE-CLONE and UIT-TSS-CONFIGURE-SHRED. UIT-TSS-TOOLBOX can only be ran on Debian/Ubuntu-based systems. Any program inside of UIT-TSS-TOOLBOX is not intended for use outside of the specific configuration specified in the source code and at the end of this document.

UIT-TSS-CONFIGURE-CLONE and UIT-TSS-CONFIGURE-SHRED create a custom and minimal ISO of Debian Live that is tailored to their specific function. They also create two more programs: UIT-TSS-FLASH-CLONE and UIT-TSS-FLASH-SHRED, which flash either the CLONE or the SHRED ISO to a drive of your choice.

Both packages are written in Bash (Unix Shell), so they can only be ran on Linux-based operating systems.

UIT-TSS-CLONE is inteded to clone computers based on a specific client-server setup.

UIT-TSS-SHRED is intended to completely wipe hard drives of all data. UIT-TSS-SHRED is not intended to be connected to any network.


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
