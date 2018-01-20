<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/8 0008
 * Time: 下午 5:54
 */
//namespace MsgCenter\Resque;

require_once __DIR__.'/../../../vendor/chrisboulton/php-resque/lib/Resque.php';

/**
 * 队列
 * User: LQ
 * Date: 2017/11/10
 */
class Queue
{
    //队列名称
    public static $queue_name;

    //任务名称
    public static $job_name;

    //参数
    public static $args;

    /**
     * Redis入队操作
     * lq 2017/11/14 add
     * @param string $queue_name
     * @param $job_name
     * @param $args
     * @return bool|string
     */
    public static function in($queue_name='default', $job_name, $args)
    {
        if(empty($job_name)) {
            return false;
        }

        self::$queue_name = $queue_name;
        self::$job_name = $job_name;
        self::$args = $args;

        date_default_timezone_set('Asia/Shanghai');

        //Redis配置
        $redis = self::getConfig('REDIS_BACKEND');
        if(empty($redis['host']) || empty($redis['port']) || empty($redis['auth'])) {
            return false;
        }

        \Resque::setBackend($redis['host'].':'.$redis['port']);
        \Resque::auth($redis['auth']);

        $jobId = \Resque::enqueue($queue_name, $job_name, $args, true);

        return $jobId;
    }

    /**
     * 获取配置
     * @return mixed
     */
    public static function getConfig($key) {
        $config = include  __DIR__.'/Config.php';
        return $config[$key];
    }

}

