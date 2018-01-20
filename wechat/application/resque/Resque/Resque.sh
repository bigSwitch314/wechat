#!/bin/bash

#常驻进程

#lq本地文件路径
#path="/home/wwwroot/default/new_qn/App/MsgCenter/Resque"
#php_path="/usr/bin/php"
#log_path="/home/wwwroot/default/new_qn/App/MsgCenter/Resque"

#qn_beta文件路径
#path="/home/www/beta/new_qn/App/MsgCenter/Resque"
#php_path="/usr/bin/php"
#log_path="/mnt/test/log"

#qn_test文件路径
#path="/home/www/beta/new_qn/App/MsgCenter/Resque"
#php_path="/usr/bin/php"
#log_path="/mnt/test/log"

#qn_sim文件路径
#path="/home/www/qn/predeploy/online/App/MsgCenter/Resque"
#php_path="/usr/local/bin/php"
#log_path="/mnt/sim/log"

#qn_online文件路径
path="/home/www/qn/deploy/online/App/MsgCenter/Resque"
php_path="/usr/local/bin/php"
log_path="/mnt/online/log"


count=`ps -ef | grep "${php_path} ${path}/Resque.php" | grep -v "grep" | wc -l`

start_time=`date +%Y-%m-%d_%X`

if [ $count -eq 0 ]
then
    echo $start_time" Resque Statrt Successful!"
    nohup ${php_path} ${path}/Resque.php >> ${log_path}/Resque.log 2>&1 &
fi