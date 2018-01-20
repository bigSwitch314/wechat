<?php
/**
 * Created by PhpStorm.
 * User: dj
 * Date: 2017/4/8 0026
 * Time: 上午 11:04
 */
namespace MsgCenter\Controller;
use Common\Model\BusinessModel;

class TestController extends CommonController{

    /*function index() {
        echo 'Hello world!';
        require_once 'ThinkPHP/Library/Org/Util/phpqrcode/phpqrcode.php';
        // 拼装场馆H5详情url
        $Level = "Q";
        $Size = 10;
        $Padding = 1;
        \QRcode::png('https://www.2345.com',false,$Level,$Size,$Padding);
    }*/

    /*function index() {
        $path = __DIR__.'/../../../';
        $php_path = '/usr/bin/php';
        $phone = '17623812833';
        //$phone = '18983391264';
        //$msg = str_replace(['/', '.'], ['*@*', '*#*'], '今天周五啦！');
        $msg = '3.14';
        $msg = str_replace(['/', '.'], ['*@*', '*#*'], $msg);
        //dump($msg);
        //die;
        exec("cd $path && $php_path index.php MsgCenter/Resque/sendSms/phone/$phone/msg/$msg", $output);
        $r = json_decode($output[0], true);
        $result = $r['send_status'][0]['code'];
        dump($result);
        dump($output);
        die;





    }*/
    function index() {
        $bus_name = '大猫健身';
        $beg_time = time();
        $class_name = '瑜伽课程';
        $content = "【勤鸟运动】您已成功预约健身地图的01月11日23:00的单次购课用届时请提前到前台使用微信扫一扫签到二维码进场。";
        //$content = "【勤鸟健身】您已成功预约".$bus_name." ".date('Y-m-d:H:i:s',$beg_time)." ".$class_name."课程，届时请提前到前台使用微信扫一扫签到二维码进场。";
        $result = func_sms_v3('18203004644', $content);
        dump($result);die;
    }




}