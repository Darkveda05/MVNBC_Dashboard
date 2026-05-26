# MVNBC (Multivendor Network Backup Config) - Dashboard

MVNBC Dashboard is a GUI dashboard for MVNBC (Multivendor Network Backup Config) that manages automated cron-based configuration backups for multi-vendor network devices.

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

    git https://github.com/Darkveda05/MVNBC_Dashboard.git
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

<h3>Dashboard</h3>
http://server ip/MVNBC_Dashboard/web/login.php<p></p>

Username: admin ; Password: admin

<img width="500" height="843" alt="Image" src="https://github.com/Darkveda05/MVNBC_Dashboard/blob/main/output/3.Login.png" />
<img width="500" height="843" alt="Image" src="https://github.com/Darkveda05/MVNBC_Dashboard/blob/main/output/4.Dashboard.png" />
