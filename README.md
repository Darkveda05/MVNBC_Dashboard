# MVNBC (Multivendor Network Backup Config) - Dashboard

MVNBC Dashboard is a GUI dashboard for MVNBC (Multivendor Network Backup Config) that manages automated cron-based configuration backups for multi-vendor network devices.

Current working products:
1) cisco
2) cisco ASA
3) H3C
4) Arista
5) Aruba CX
6) Mikrotik


Requirements: 
1) Linux (Ubuntu/debian/casaos)
2) git
3) zsh
4) web server (nginx/apache/xampp)
   
Installation step:

    sudo apt install git
    sudo apt install zsh
    sudo apt install sshpass -y
    sudo apt install dos2unix -y

    git clone https://github.com/Darkveda05/MVNBC_Dashboard.git
    cd MVNBC_Dashboard
    chmod +x MVNBC.sh
    ./MVNBC.sh

Example schedule autobackup (cron) for 5 minutes

    /*5 * * * *
    
<img width="500" height="843" alt="Image" src="https://github.com/Darkveda05/MVNBC_Dashboard/blob/main/output/1.Installation.png" />

Check if the cron service is running (casaos / debian / ubuntu)

    whoami
    sudo crontab -u <user> -l
    sudo crontab -u <user> -e
    crontab -e   (for ubuntu)


Change network device username/password to auto backup config

    sudo nano config/devices.conf

    Format:
    vendor,ip address,username,password,enable password
    cisco,172.16.30.29,admin,cisco,cisco123
    mikrotik,172.16.30.1,admin,admin123   <------- if no enable password, just let it blank


<h3>Dashboard</h3>
http://server ip/MVNBC_Dashboard/web/login.php<p></p>

Username: admin ; Password: admin

<img width="500" height="843" alt="Image" src="https://github.com/Darkveda05/MVNBC_Dashboard/blob/main/output/3.Login.png" />
<img width="500" height="843" alt="Image" src="https://github.com/Darkveda05/MVNBC_Dashboard/blob/main/output/4.Dashboard.png" />
