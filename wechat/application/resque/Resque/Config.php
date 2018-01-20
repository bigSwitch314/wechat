<?php

//Resque配置

return [
    //执行的队列名字, * 表示执行所有队列中的任务
    'QUEUE' => '*',

    //worker检查queue的间隔, 默认为5秒
    'INTERVAL' => 5,

    //创建的Worker数量, 默认为创建1个Worker
    'COUNT' => 2,

    //基本调试模式, 1启用, 0关闭
    'VERBOSE' => 0,

    //详细调试模式, 1启用, 0关闭
    'VVERBOSE' => 0,

    //加载Job文件路径. APP_INCLUDE=require.php, 在require.php中引入所有Job的Class
    'APP_INCLUDE' => '',

    //在Redis数据库中为队列的KEY添加前缀，以方便多个Worker运行在同一个Redis数据库中方便区分。默认为空
    'PREFIX' => 'LQ',

    //消息队列redis配置
    'REDIS_BACKEND' => [
        'host' => '10.24.251.62',
        'port' => 6379,
        'auth' => 'gZJfGZq7iS'
    ],

];
