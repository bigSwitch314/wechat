<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/15 0015
 * Time: 下午 5:47
 */
namespace MsgCenter\Controller;

use Common\Model\BusinessModel;
use Common\Service\WxConfig;
use Common\Tools\RedisClient;
use MsgCenter\Model\MsgcenterSmsorderInfoModel;
use MsgCenter\Model\MsgcenterSmsorderModel;
use MsgCenter\Model\MsgcenterSmspackageModel;
use Org\Util\Wechat;
use Org\Util\Wxpay;
use Think\Exception;

class AdminController extends CommonController
{
    /**
     * 获取购买短信包列表
     * dj 2017-11-15 add
     */
    public function getSmsPacketList(){
        try{
            $MsgcenterSmspackageModel = new MsgcenterSmspackageModel();
            $list = $MsgcenterSmspackageModel->get_smspackage();
            $this->ajaxReturn([
                'errorcode' => SUCCESS,
                'errormsg' => '发送成功！',
                'data' => $list? $list: []
            ]);

        } catch(\Exception $e) {
            $this->ajaxReturn([
                'errorcode' => $e->getCode(),
                'errormsg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 获取购买短信二维码
     * dj 2017-11-15 add
     */
    public function getBuySmsQrCode(){
        try{
            $MsgcenterSmspackageModel = new MsgcenterSmspackageModel();
            $list = $MsgcenterSmspackageModel->get_smspackage();
            if(empty($list)){
                throw new \Exception('二维码生成失败！',PARAM_ERROR);
            }

            $sms_package_id = I('sms_package_id');
            if(empty($sms_package_id)){
                $sms_package_info = $list[0];
            } else {
                $id_arr = array_column($list, 'id');
                if(!in_array($sms_package_id, $id_arr)) {
                    throw new \Exception('参数错误@sms_package_id', PARAM_ERROR);
                }
                $list = array_column($list, null, 'id');
                $sms_package_info = $list[$sms_package_id];
            }

            $pay_code = $this->wxPayCode($sms_package_info);
            //生成支付二维码
            require_once 'ThinkPHP/Library/Org/Util/phpqrcode/phpqrcode.php';
            $Level = "Q";
            $Size = 10;
            $Padding = 1;
            ob_start();
            \QRcode::png($pay_code['code_url'],false,$Level,$Size,$Padding);
            $image_string = base64_encode(ob_get_contents());
            ob_end_clean();

            $this->ajaxReturn([
                'errorcode' => SUCCESS,
                'errormsg' => '获取成功！',
                'data' => [
                    'order_sn' => $pay_code['order_sn'],
                    'image_string' => 'data:image/png;base64,'.$image_string
                ]
            ]);

        } catch(\Exception $e) {
            $this->ajaxReturn([
                'errorcode' => $e->getCode(),
                'errormsg' => $e->getMessage()
            ]);
        }
    }


    /**
     * dj 2017-11-15 add
     * 获取微信支付二维码
     * @param $item 短信包数据
     * @return array
     */
    public function wxPayCode($sms_package_info){
        //1.创建订单
        $bus_id = session('bus_id');
        $amount = $sms_package_info['amount'];
        $remark = '购买'.$sms_package_info['number'].'条短信包';
        $sms_number = $sms_package_info['number'];
        //$order_sn = create_order_sn();
        $order_sn = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);

        $MsgcenterSmsorderModel = new MsgcenterSmsorderModel();
        $smsorder_info = $MsgcenterSmsorderModel->add_smsorder($sms_package_info['id'],$bus_id,$amount,$order_sn,$remark,$sms_number);
        if(!$smsorder_info){
            throw new \Exception('订单创建失败！',ORDER_ERROR);
        }
        //获取支付二维码
        $native_res = $this->nativeRes($smsorder_info);
        if(empty($native_res['code_url'])){
            throw new \Exception('支付二维码生成失败！',CODE_URL_ERROR);
        }

        return [
            'order_sn' => $native_res['order_sn'],
            'code_url' => $native_res['code_url'],
        ];
    }


    /**
     * 短信包支付
     * @param $scene_id 场景值ID
     * @return array
     */
    public function nativeRes($smsorder_info) {
        //询订单信息
        $order_sn = $smsorder_info['order_sn'];
        if (empty($order_sn)) {
            throw new  \Exception('订单不存在!',ORDER_IS_NULL);
        }
        $amount =  $smsorder_info['amount'];

        //获取公众号配置信息
        $config = C('RECEIVABLES');

        //支付类型
        $appid = $config['appid'];
        $appsecret = $config['appsecret'];
        $partnerid = $config['partnerid'];
        $partnerkey = $config['partnerkey'];

        $notify_url = C('WEB_SITE').'/MsgCenter/WxPayNotify/sms_native_notify';
        $trade_type = 'NATIVE';
        $wx_class = new Wxpay($appid,$appsecret,$partnerid,$partnerkey,$notify_url,$trade_type);
        $param['body'] = $smsorder_info['remark'];
        $param['out_trade_no'] = $order_sn;
        $param['total_fee'] = $amount * 100;
        $param['spbill_create_ip'] = get_client_ip();
        $param['product_id'] = $smsorder_info['id'];
        /*if (strstr($host, "sim") || strstr($host,"kaifa")) {
            $result = array('sim、kaifa不支持微信支付');
        } else {
            //支付参数签名校验
            $result = $wx_class->unified_pay($param);
            if ($result['status'] == 0) {
                throw new \Exception('微信签名数据异常!', WECHAT_SIGN_ERROR);
            }
        }*/
        $result = $wx_class->unified_pay($param);
        if ($result['status'] == 0) {
            throw new \Exception('微信签名数据异常!', WECHAT_SIGN_ERROR);
        }
        $result['order_sn'] = $order_sn;
        return $result;
    }


    public function getSmsPayStatus(){
        try{
            $bus_id = session('bus_id')? session('bus_id'): I('bus_id');
            $order_sn = I('order_sn');

            if(empty($bus_id) || !is_numeric($bus_id)) {
                throw new \Exception('参数错误@bus_id', PARAM_ERROR);
            }
            if(empty($order_sn) || !is_numeric($order_sn)) {
                throw new \Exception('参数错误@order_sn', PARAM_ERROR);
            }

            $MsgcenterSmsorderModel = new MsgcenterSmsorderModel();
            $sms_order = $MsgcenterSmsorderModel->get_smsorder_info_spid($order_sn);
            if(empty($sms_order)) {
                $this->ajaxReturn([
                    'errorcode' => ORDER_DEAL_ERROR_1,
                    'errormsg' => '订单不存在或已删除！',
                ]);
            }
            if($sms_order['status'] == 1 && $sms_order['pay_status'] == 1) {
                $this->ajaxReturn([
                    'errorcode' => SUCCESS,
                    'errormsg' => '购买成功！',
                ]);
            }

            // 2.若支付状态为失败，直接返回支付失败
            if($sms_order['pay_status'] == 2) { //支付失败
                $this->ajaxReturn([
                    'errorcode' => PAY_FAIL,
                    'errormsg' => '支付失败！',
                ]);
            }

            // 3.若支付状态为未支付，需从微信后台系统判断支付状态
            if($sms_order['status'] == 0 && $sms_order['pay_status'] == 0) { //未支付
                $check_status = $this->check_sms_paystatus($sms_order);
                if($check_status == 'NOTPAY') {
                    $this->ajaxReturn([
                        'errorcode' => PAY_ING,
                        'errormsg' => '未支付！',
                    ]);
                } elseif($check_status == 'USERPAYING') {
                    $this->ajaxReturn([
                        'errorcode' => PAY_ING,
                        'errormsg' => '正在支付！',
                    ]);
                } elseif($check_status == 'SUCCESS') {
                    $Model = M();
                    $Model->startTrans();
                    $out_trade_no = $sms_order['order_sn'];
                    //开始执行添加
                    //开始启动redis锁
                    //加锁机制。代码进入操作前检查操作是否上锁，如果锁上，中断操作。
                    //否则进行下一操作，第一步将操作上锁，然后执行代码，最后执行完代码将操作锁打开。
                    //声明redis
                    $redis=new RedisClient();
                    //定义锁的时间秒数
                    $lockSecond = 300;
                    //锁的值
                    $lockKey="msg_".$out_trade_no;
                    $value = 1;
                    //获取锁的状态
                    $lock_status = $redis->get($lockKey);
                    if (empty($lock_status)) {
                        //再去查询订单状态,为了避开解锁后状态
                        $sms_order = $MsgcenterSmsorderModel->get_smsorder_info_spid($order_sn);
                        if($sms_order['pay_status'] == 0 && $sms_order['status'] == 0){
                            //Redis实现分布式锁,不能同时创建
                            $is_set= $redis->setLockKey($lockKey, $value, array('nx', 'ex' => $lockSecond));
                        }
                        //判断锁的状态
                        if ($is_set) {
                            //修改场馆短信条数
                            $BusinessModel = new BusinessModel();
                            $sms_number = $sms_order['sms_number'];
                            $save_bus = $BusinessModel->setInc_sms_number($bus_id,$sms_number);
                            //修改短信到账结果
                            $pay_status = 1;
                            $status = 1;
                            $save_sms_order = $MsgcenterSmsorderModel->update_smsorder_pay_status($out_trade_no,$pay_status, $status);

                            if($save_bus === false || $save_sms_order === false){
                                $Model->rollback();
                                logw("sms_pay_error= 支付成功，短信未到账，请联系管理员！(order_sn: $out_trade_no)", 'pay-return');
                                throw new Exception('支付成功，短信未到账，请联系管理员！',SMS_ERROR);
                            }
                            //删除锁定
                            $redis->delKey($lockKey);//操作解锁
                            $Model->commit();
                            $this->ajaxReturn([
                                'errorcode' => SUCCESS,
                                'errormsg' => '购买成功！',
                            ]);
                        }else{
                            $Model->rollback();
                            throw new \Exception('支付中',PAY_ING);
                        }
                    }else{
                        logw('lock_out_trade_no'.json_encode($lock_status));
                        throw new \Exception('支付中',PAY_ING);
                    }
                } elseif($check_status == 'PAYERROR') {
                    //修改短信到账结果
                    $out_trade_no = $sms_order['order_sn'];
                    $pay_status = 2;
                    $status = 0;
                    $MsgcenterSmsorderModel->update_smsorder_pay_status($out_trade_no,$pay_status, $status);
                    $this->ajaxReturn([
                        'errorcode' => PAY_FAIL,
                        'errormsg' => '支付失败！',
                    ]);
                }
            }

        } catch(\Exception $e) {
            $this->ajaxReturn([
                'errorcode' => $e->getCode(),
                'errormsg' => $e->getMessage()
            ]);
        }
    }

    public function check_sms_paystatus($sms_order){
        //获取公众号配置信息
        $config = C('RECEIVABLES');
        $wechat_class = new Wechat($config);
        $out_trade_no = $sms_order['order_sn'];
        $wx_order_info = $wechat_class->getPayOrderStatus($out_trade_no);
        logw("function=sms_wx_order_info\twx_id=".$out_trade_no."\twx_order_info=".json_encode($wx_order_info));
        $pay_status = 'PAYERROR';
        if (!empty($wx_order_info)) {
            //订单查询成功
            if ($wx_order_info['return_code'] == 'SUCCESS' && $wx_order_info['result_code'] == 'SUCCESS') {
                $pay_status = $wx_order_info['trade_state'];
            } elseif ($wx_order_info['return_code'] == 'SUCCESS') {
                $pay_status = 'PAYERROR'; //支付失败
            }
        }
        return $pay_status;
    }
}