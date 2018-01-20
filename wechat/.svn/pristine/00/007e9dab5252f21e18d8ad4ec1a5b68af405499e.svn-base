#!/bin/bash

#常驻进程

#lq本地文件路径
path="/home/wwwroot/default/wechat/application/socket/server"
php_path="/usr/bin/php"
log_path="/home/wwwroot/default/wechat/application/socket/server"




count=`ps -ef | grep "${php_path} ${path}/SocketServer.php" | grep -v "grep" | wc -l`

start_time=`date +%Y-%m-%d_%X`

if [ $count -eq 0 ]
then
    echo $start_time" SocketServer Statrt Successful!"
    nohup ${php_path} ${path}/SocketServer.php >> ${log_path}/SocketServer.log 2>&1 &
else
    echo "SocketServer Already Running!"
fi