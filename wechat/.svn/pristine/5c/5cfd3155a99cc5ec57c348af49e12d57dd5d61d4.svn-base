<?php
namespace app\wechat\controller;
use think\Controller;
use think\Session;
use \Wechat\WechatService;


class Message extends Controller
{
   public function sendTemmplateMsg(){
       //Vendor('wechat.wechat-php-sdk.Wechat.WechatReceive');
       //$wechat = new WechatReceive;
       $wechat = new WechatService();
       $wechat->access_token = 'Y5SeVQB0Wg5C-zJzOHcdBWKPfy2_LXc2BygbZIWHL0YHXSweupxszAmCqqdxZY9xDR-g-aEldoByhlExJWGykLm5F-7aPQh-I-e4Jofus9uGFmIPCF1_WB3xUzmpjsORBONeACAXKJ';
       $data = ' {
           "touser":"ohJTK1Er5L7aDL3vFbHGQr8_7xDc",
           "template_id":"nS3F0xb_ODDgXNX4ZLPYsgrFXEuWmuZeR-Z8Es8Xpzo",
           "url":"",
           "data":{
                   "first": {
                       "value":"恭喜你购买成功！",
                       "color":"#173177"
                   },
                   "keynote1":{
                       "value":"巧克力",
                       "color":"#173177"
                   },
                   "keynote2": {
                       "value":"39.8元",
                       "color":"#173177"
                   },
                   "keynote3": {
                       "value":"2014年9月22日",
                       "color":"#173177"
                   },
                   "remark":{
                       "value":"欢迎再次购买！",
                       "color":"#173177"
                   }
           }
       }';
       $res = $wechat->sendTemplateMessage($data);
       dump('ii');
       dump($res);
       die;
   }



}
