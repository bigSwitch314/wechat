<?php
/**
 * 常驻进程
 * User: LQ
 * Date: 2017/11/10
 */

date_default_timezone_set('Asia/Shanghai');

// 自动加载Job类
spl_autoload_register(function ($class_name) {
    require_once dirname(__FILE__).'/'.$class_name.'.php';
});

// Resque配置
$config = require_once(__DIR__.'/Config.php');
$redis = $config['REDIS_BACKEND'];

// 守护进程
require_once __DIR__.'/../../../vendor/chrisboulton/php-resque/resque.php';
