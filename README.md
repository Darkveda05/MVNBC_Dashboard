# MVNBC_Dashboard

MVNBC_Dashboard is a GUI dashboard for MVNBC that manages automated cron-based configuration backups for multi-vendor network devices.

Requirements: 
1) Linux (Ubuntu/debian/casaos)
2) Web server (nginx/apache/xampp)

Installtion step:

    cd /DATA/AppData/nginx/config/www
    git https://github.com/Darkveda05/MVNBC_Dashboard.git
    cd MVNBC_Dashboard
    sudo apt install sshpass -y
    sudo apt install dos2unix -y
    chmod +x MVNBC.sh
    ./MVNBC.sh

<img width="500" height="843" alt="Image" src="https://github.com/Darkveda05/MVNBC_Dashboard/blob/main/output/1.Cron.png" />

Verify cron is running (casaos / debian)

    whoami
    sudo crontab -u <user> -l
    sudo crontab -u <user> -e  
    
Verify cron is running (ubuntu)

    crontab -e

<img width="500" height="843" alt="Image" src="https://github.com/Darkveda05/MVNBC_Dashboard/blob/main/output/1.Cron.png" />


Dashboard:

Login to http://server ip/MVNBC_Dashboard/web/login.php

Username: admin<p></p>
Password: admin

<img width="500" height="843" alt="Image" src="https://github.com/Darkveda05/MVNBC_Dashboard/blob/main/output/3.Dashboard.png" />
