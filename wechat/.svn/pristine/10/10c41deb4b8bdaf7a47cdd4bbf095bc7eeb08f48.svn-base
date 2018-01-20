<?php
namespace app\socket\controller;

class SocketClient
{
    /*
    +-------------------------------
    *    @socket连接整个过程
    +-------------------------------
    *    @socket_create
    *    @socket_connect
    *    @socket_write
    *    @socket_read
    *    @socket_close
    +--------------------------------
    */
    public function Request()
    {
        set_time_limit(0);
        echo "<h2>tcp/Ip Connection </h2>".PHP_EOL;
        $port = 10005;
        $ip ='192.168.33.12';

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) .PHP_EOL;
        } else {
            echo "OK".PHP_EOL;
        }

        echo "Attempting to connect to '$ip' on port '$port'...".PHP_EOL;
        $result = socket_connect($socket, $ip, $port);
        if($result === false) {
            echo "socket_connect() failed: Reason: ($result) " . socket_strerror(socket_last_error($socket)).PHP_EOL;
        } else {
            echo "OK".PHP_EOL;
        }
        $in = "HEAD / http/1.1".PHP_EOL;
        $in .= "HOST: localhost".PHP_EOL;
        $in .= "Connection: close".PHP_EOL;
        if(false == socket_write($socket, $in, strlen($in))) {
            echo "socket_write() failed: reason: " . socket_strerror(socket_last_error($socket)).PHP_EOL;
        } else {
            echo "发送到服务器信息成功！".PHP_EOL;
            echo "发送的内容为:<font color='red'>$in</font> <br>";
        }

        $out = '';
        while ($out = socket_read($socket, 8192)) {
            echo "接收服务器回传信息成功！".PHP_EOL;
            echo "接受的内容为:".$out.PHP_EOL;
        }
        echo "closeing socket..".PHP_EOL;
        socket_close($socket);
        echo "ok".PHP_EOL;
    }
}
