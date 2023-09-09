README

uit-tss-toolbox is a software suite that makes imaging and erasing computers easy. It also
does data collection on its various processes in the background. At least one server, client, 
switch, and USB thumbdrive is necessary to perform the operations. The server needs to run MySQL,
a web server (Nginx), a DNS/DHCP server (dnsmasq), an NTP server (chrony), and an SSH server (sshd).
The MySQL server is used for data collection, the web server transfers the live images to the clients, 
the DNS/DHCP server provides networking to the clients, and the NTP server makes sure the clients 
have the right time. The clients need a functioning keyboard, mouse, and screen. The clients 
also need at least one free USB port for the thumbdrive and preferably an onboard RJ-45 NIC.
The clients need to be plugged in to both power and ethernet.