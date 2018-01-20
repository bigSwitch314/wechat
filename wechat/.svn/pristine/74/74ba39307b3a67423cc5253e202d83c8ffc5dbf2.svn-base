<?php
namespace app\index\controller;

use app\index\model\Merchants;

class Index
{
    public function index()
    {
        $res = file_put_contents(LOG_PATH.'weixin.log', date('Y-m-d H:i:s').'---@@@---'.PHP_EOL.PHP_EOL, FILE_APPEND);
        echo 'Hello world3333!';
    }

    public function dbTest() {
       $model = new Merchants();
        $res = $model->getList();
        dump($res);
    }

    public function redisTest(){
        $redisClient = new \RedisClient;

        $redisClient->set('dj', 'dengjing');
        echo $redisClient->get('dj');

    }
}
