README

uit-tss-toolbox is a software suite that makes imaging and erasing computers easy. It also
does data collection on its various processes in the background. At least one server, client, 
switch, and USB thumbdrive is necessary to perform the operations. The server needs to run MySQL,
a web server (Nginx), a DNS/DHCP server (dnsmasq), an NTP server (chrony), and an SSH server (sshd).
The MySQL server is used for data collection, the web server transfers the live images to the clients, 
the DNS/DHCP server provides networking to the clients, and the NTP server makes sure the clients 
have the right time. All of these services are necessary. The clients need a functioning keyboard, 
mouse, and screen. The clients also need at least one free USB port for the thumbdrive and preferably 
an onboard RJ-45 NIC. The clients need to be plugged in to both power and ethernet.

Tagnumbers "000000" and "111111" are legacy tagnumbers that used to be reserved. 000000 used to be a
tagnumber that described a computer that would be in the process of imaging/erasing, but no 
tagnumber was entered by the user yet. Now, we associate the tagnumber with the serial number, so 
user input of the tagnumber is only required once. 111111 was reserved for a laptop without a tag.
Now, we use the tagnumber "NOTAG" or "NOTAG2" and so on to describe a laptop with no tag on it. 
It is important to record the serial number of all computers that you image in case the tag is lost. 