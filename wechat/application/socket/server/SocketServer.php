<?php
//SocketServer启动时间
fwrite(STDOUT, '['.date('Y-m-d H:i:s').'] '.'SocketServer Statrt Successful!'.PHP_EOL);

//在命令行模式下运行
if(PHP_SAPI != 'cli') {
    exit('请在CLI模式下运行！');
}

//确保在连接客户端时不会超时
set_time_limit(0);
//报错级别
error_reporting(E_ALL);

$ip = '192.168.33.12';
$port = 10005;

/*
 +-------------------------------
 *    @socket通信整个过程
 +-------------------------------
 *    @socket_create
 *    @socket_bind
 *    @socket_listen
 *    @socket_accept
 *    @socket_read
 *    @socket_write
 *    @socket_close
 +--------------------------------
*/

if( ($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) { // 创建一个socket连接
    echo "socket_create() failed: reason:" . socket_strerror(socket_last_error($sock)) . "\n";
}

if (socket_bind($sock, $ip, $port) === false) { // 绑socket到端口
    echo "socket_bind() failed: reason:" . socket_strerror(socket_last_error($sock)) . "\n";
}

if (socket_listen($sock, 5) === false) { // 开始监听连接
    echo "socket_listen() failed :reason:" . socket_strerror(socket_last_error($sock)) . "\n";
}

$count = 0;
do {
    if (($msgsock = socket_accept($sock)) === false) { // 堵塞等待另一个Socket来处理通信
        echo "socket_accept() failed :reason:".socket_strerror(socket_last_error($sock)) . "\n";
        break;
    }
    //发送消息到客户端
    $msg = "<font color='red'>server send:welcome</font><br/>";
    socket_write($msgsock, $msg, strlen($msg));

    //接收客户端消息
    $buf = socket_read($msgsock, 8192);
    $talkback = "received message:$buf\n";
    echo $talkback;
    if(++$count >=5) {
        break;
    }
    socket_close($msgsock);
} while(true);

socket_close($sock);

