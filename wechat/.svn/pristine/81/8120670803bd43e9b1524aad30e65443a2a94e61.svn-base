<?php
namespace app\wechat\controller;
use think\Session;

class Index
{
    public function index()
    {
        //1.获取参数
        $nonce = $_GET['nonce'];
        $token = 'weixin';
        $timestamp = $_GET['timestamp'];
        $signature = $_GET['signature'];
        $echostr = !empty($_GET['echostr'])? $_GET['echostr']: '';
        //2.形成数组并按字典序排序
        $arr = [$nonce, $timestamp, $token];
        sort($arr,SORT_STRING);
        //3.形成字符串并加密
        $str = sha1(implode($arr));
        //4.验证
        if($str == $signature && $echostr){
            echo $echostr;
            exit;
        }else{
            $this->reponseMsg();
            exit;
        }
    }

    //接收事件推送并回复
    public function reponseMsg(){
        //1.获取微信推送过来的post数据（xml格式）
        $postArr = $GLOBALS['HTTP_RAW_POST_DATA'];
        //$rs =  file_get_contents("php://input");
        //2.处理消息类型，并设置回复类型和内容
//        <xml>
//            <ToUserName><![CDATA[toUser]]></ToUserName>
//            <FromUserName><![CDATA[FromUser]]></FromUserName>
//            <CreateTime>123456789</CreateTime>
//            <MsgType><![CDATA[event]]></MsgType>
//            <Event><![CDATA[subscribe]]></Event>
//        </xml>
        $postObj = simplexml_load_string($postArr);
        //判读该数据包师傅是订阅的事件推送
        if(strtolower($postObj->MsgType) == 'event'){
            //如果是关注事件
            if(strtolower($postObj->Event) == 'subscribe'){
                //回复用户消息
                $toUser   = $postObj->FromUserName;
                $fromUser = $postObj->ToUserName;
                $time     = time();
                $msgType  = 'text';
                $content  = '欢迎关注我们的微信公众号!'.'@toUser:'.$toUser.'@fromUser:'.$fromUser;
                $template = "<xml>
                     <ToUserName><![CDATA[%s]]></ToUserName>
                     <FromUserName><![CDATA[%s]]></FromUserName>
                     <CreateTime>%s</CreateTime>
                     <MsgType><![CDATA[%s]]></MsgType>
                     <Content><![CDATA[%s]]></Content>
                     </xml>";
                $info = sprintf($template, $toUser,$fromUser,$time,$msgType,$content);
                echo $info;
            }
            //如果是重扫二维码
            if(strtolower($postObj->Event) == 'scan'){
                if($postObj->EventKey==2000){//扫的临时二维码
                    $title = '临时二维码欢迎您！';
                }
                if($postObj->EventKey==3000){//扫的永久二维码
                    $title = '永久二维码欢迎您！';
                }
                //回复用户消息
                $toUser   = $postObj->FromUserName;
                $fromUser = $postObj->ToUserName;
                $time     = time();
                $msgType  = 'text';
                $content  = $title.'@'.$postObj->EventKey;
                $template = "<xml>
                     <ToUserName><![CDATA[%s]]></ToUserName>
                     <FromUserName><![CDATA[%s]]></FromUserName>
                     <CreateTime>%s</CreateTime>
                     <MsgType><![CDATA[%s]]></MsgType>
                     <Content><![CDATA[%s]]></Content>
                     </xml>";
                $info = sprintf($template, $toUser,$fromUser,$time,$msgType,$content);
                echo $info;
            }
        }

        //回复文本消息
        if(strtolower($postObj->MsgType) == 'text' && $postObj->Content != 'tuwen1'){
            //如果是关注事件
            switch(strtolower($postObj->Content)){
                case 1:
                    $content = 'Imooc is very good!';
                    break;
                case 2:
                    $content = 'Luo is very good!';
                    break;
                case 3:
                    $content = 'Qiang is very good!';
                    break;
                case 4:
                    $content = "<a href='http://www.imooc.com/'>慕课</a>";
                    break;
                default:
                    $content = 'Default is very good!';
            }
            //回复用户消息
            $toUser   = $postObj->FromUserName;
            $fromUser = $postObj->ToUserName;
            $time     = time();
            $msgType  = 'text';
            //$content  = 'Imooc is very good!';
            $template = "<xml>
                 <ToUserName><![CDATA[%s]]></ToUserName>
                 <FromUserName><![CDATA[%s]]></FromUserName>
                 <CreateTime>%s</CreateTime>
                 <MsgType><![CDATA[%s]]></MsgType>
                 <Content><![CDATA[%s]]></Content>
                 </xml>";
            $info = sprintf($template, $toUser,$fromUser,$time,$msgType,$content);
            echo $info;
        }

        //回复图文消息
        if(strtolower($postObj->MsgType) == 'text' && $postObj->Content == 'tuwen1'){
            //回复用户消息
            $toUser   = $postObj->FromUserName;
            $fromUser = $postObj->ToUserName;
            $time     = time();
            $msgType  = 'news';
            $arr = [
               [   'title' => 'imooc',
                   'description' => 'Imooc is very cool!',
                   'picUrl' => 'http://www.imooc.com/static/img/index/logo_new.png',
                   'url' => 'http://www.imooc.com/',
               ],
                [   'title' => 'imooc22222',
                    'description' => 'Imooc is very cool22222!',
                    'picUrl' => 'http://www.imooc.com/static/img/index/logo_new.png',
                    'url' => 'http://www.imooc.com/',
                ],
            ];
            $template = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <ArticleCount>".count($arr)."</ArticleCount>
                            <Articles>";
            foreach ( $arr as $k=>$v) {
                $template .="<item>
                            <Title><![CDATA[".$v['title']."]]></Title>
                            <Description><![CDATA[".$v['description']."]]></Description>
                            <PicUrl><![CDATA[".$v['picUrl']."]]></PicUrl>
                            <Url><![CDATA[".$v['url']."]]></Url>
                            </item>";
            }
            $template .=   "</Articles>
                        </xml>";
            $info = sprintf($template, $toUser,$fromUser,$time,$msgType);
            echo $info;
        }
    }

    public function http_curl_test(){
        // 获取imooc
        // 1.初始化curl
        $ch = curl_init();
        $url = 'http://www.baidu.com';
        // 2.设置curl参数
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 3.采集
        $output = curl_exec($ch);
        // 4.关闭
        curl_close($ch);
        dump($output);
    }

    public function getWxAccessToken(){
        session_start();
        if(isset($_SESSION['acessToken']) && isset($_SESSION['expire_time']) && $_SESSION['expire_time']>time()){
            return $_SESSION['acessToken'];
        }else{
            // 1.请求URL地址
            $appid = 'wxf23eb5d763ce3ea6';
            $secret = '0e10d2f4b048924bb7de6d1589987608';
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$secret;
            // 2.初始化curl
            $ch = curl_init();
            // 3.设置curl参数
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // 4.采集
            $res = curl_exec($ch);
            if(curl_errno($ch)){
                return curl_error($ch);
            }
            // 5.关闭
            curl_close($ch);
            // 6.返回结果
            $acessToken = json_decode($res,true)['access_token'];
            $_SESSION['expire_time'] = time()+7000;
            $_SESSION['acessToken'] = $acessToken;
            return  $_SESSION['acessToken'];
        }
    }

    public function getWxServerIp(){
        $accessToken = '77nYZVni-k38vFUP5EINGCeUS9Ku-cdrCEifA86lyq2DYx5U2jKyGyGh5hZOTz3DB6NsKBOT2dtVEp25EBpWNhBzAQWkZ2r8BMAg4Eqsm13P9QRIpcLl09F9HUzW6U3TDhPQDeAEADER';
        $url = 'https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token='.$accessToken;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        if(curl_errno($ch)){
            dump(curl_error($ch));
        }
        curl_close($ch);
        dump(json_decode($res,true));
    }

    public  function http_curl($url,$type="get",$res="json",$arr=""){
        //1.初始化curl
        $ch = curl_init();
        //2.设置url的参数
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch , CURLOPT_RETURNTRANSFER, 1);
        if($type == "post"){
            curl_setopt($ch , CURLOPT_POST, 1);
            curl_setopt($ch , CURLOPT_POSTFIELDS, $arr);
        }
        curl_setopt($ch , CURLOPT_SSL_VERIFYPEER, false);
        //3.采集
        $output = curl_exec($ch);

        //4.关闭curl

        if ($res == "json") {
            if( curl_errno($ch) ){
                return curl_error($ch);
            }else{
                return  json_decode($output,true);
            }
        } else {
            return  $output;
        }
        curl_close($ch);
    }

    public function getTemporaryQrCode(){
        // 1.获取全局票据
        $access_token = 'xA70UUrB63Y_cEUSRi_lagpCLDx3fPJAFx-IBKJ6qKjIzbVTMi3K6BnqEL6JOTGFA_wmJEcMZERM71DhctaQk32_fLE-9tW9EHUV8B8kJZF1tNMLpTGU1zVFG3pEs_EoIDMiAHATOX';
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token;
        //{"expire_seconds": 604800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": 123}}}
        $postArr = [
            'expire_seconds'=>604800,
            'action_name'=>'QR_SCENE',
            'action_info'=>[
                'scene'=>['scene_id'=>2000],
            ],
        ];
        $postJson = json_encode($postArr);
        $res = $this->http_curl($url,'post','json',$postJson);
        $ticket = $res['ticket'];
        $url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($ticket);
        echo '临时二维码';
        echo "<img src='".$url."'>";
    }

    public function getPermanentQrCode(){
        // 1.获取全局票据
        $access_token = 'xA70UUrB63Y_cEUSRi_lagpCLDx3fPJAFx-IBKJ6qKjIzbVTMi3K6BnqEL6JOTGFA_wmJEcMZERM71DhctaQk32_fLE-9tW9EHUV8B8kJZF1tNMLpTGU1zVFG3pEs_EoIDMiAHATOX';
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token;
        //{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": 123}}}
        $postArr = [
            'action_name'=>'QR_LIMIT_SCENE',
            'action_info'=>[
                'scene'=>['scene_id'=>3000],
            ],
        ];
        $postJson = json_encode($postArr);
        $res = $this->http_curl($url,'post','json',$postJson);
        $ticket = $res['ticket'];
        $url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($ticket);
        echo '永久二维码';
        echo "<img src='".$url."'>";
    }

    //网页授权
    //获取用户的openId
    public function getBaseInfo(){
        // 1.获取到code
        $appid = 'wxf23eb5d763ce3ea6';
        $rediect_uri =urlencode('http://www.bigswitch314.cn/wechat/index/getUserOpenId');
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appid&redirect_uri=$rediect_uri&response_type=code&scope=snsapi_base&state=123#wechat_redirect";
        header('location:'.$url);
    }

    public function getUserOpenId(){
        // 2.获取到用户授权的AccessToken
        $appid = 'wxf23eb5d763ce3ea6';
        $secret = '0e10d2f4b048924bb7de6d1589987608';
        $code = $_GET['code'];
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$secret&code=$code&grant_type=authorization_code ";
        // 3.拉取用户的openId
        $res = $this->http_curl($url, 'get');
        echo 'openid: '.$res['openid'];
    }

    //获取用户的详细信息
    public function getDetailInfo(){
        // 1.获取到code
        $appid = 'wxf23eb5d763ce3ea6';
        $rediect_uri =urlencode('http://www.bigswitch314.cn/wechat/index/getUserInfo');
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appid&redirect_uri=$rediect_uri&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect";
        header('location:'.$url);
    }

    public function getUserInfo(){
        // 2.获取到用户授权的AccessToken
        $appid = 'wxf23eb5d763ce3ea6';
        $secret = '0e10d2f4b048924bb7de6d1589987608';
        $code = $_GET['code'];
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$secret&code=$code&grant_type=authorization_code ";
        // 3.拉取用户的详细信息
        $res = $this->http_curl($url, 'get');
        $access_token = $res['access_token'];
        $openid = $res['openid'];
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN ";
        $r = $this->http_curl($url, 'get');
        echo json_encode($r);
    }

    //创建微信菜单
    public function defineItem(){
        $accessToken = $this->getWxAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$accessToken;
        $postArr = [
            'button' => [
                [
                    'name' => urlencode('菜单一'),
                    'type' => 'click',
                    'key'  => 'item1',
                ],//第一个一级菜单
                [
                    'name' => urlencode('菜单二'),
                    'sub_button' => [
                        [
                            'name' => urlencode('歌曲'),
                            'type' => 'click',
                            'key'  => 'songs',
                        ],//第一个二级菜单
                        [
                            'name' => urlencode('电影'),
                            'type' => 'view',
                            'url'  => 'http://www.zbj.com',
                        ],//第二个二级菜单
                    ],
                ],//第一二个一级菜单
                [
                    'name' => urlencode('菜单三'),
                    'type' => 'view',
                    'url'  => 'http://www.qq.com',
                ],//第三个一级菜单
            ],

        ];
        $postJson = urldecode(json_encode($postArr));
        $res = $this->http_curl($url,'post','json',$postJson);
        return json_encode($res);
    }

    function uu(){
        echo 'ii';
        echo 'jj';
        echo 'ee';
    }
}
