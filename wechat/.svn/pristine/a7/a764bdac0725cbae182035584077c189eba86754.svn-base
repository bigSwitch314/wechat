<?php
/**
 * 微信支付回调
 * @date 2016-7-27
 */

namespace MsgCenter\Controller;

use MsgCenter\Model\MsgcenterSmsorderInfoModel;
use MsgCenter\Model\MsgcenterSmsorderModel;
use Think\Controller;
use Common\Tools\RedisClient;
use Common\Model\BusinessModel;

class WxPayNotifyController extends Controller
{

    /**
     * Config
     */
    private $WxConfig;
    /**
     * 微信支付回调信息
     * @param $http_ram array
     */
    private $http_raw;

    public function __construct()
    {
        if (empty($this->http_raw)) {
            $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
            $this->http_raw = $this->xmlToArray($xml);
        }
        //获取当前商家的支付授权信息
        $this->WxConfig = C('RECEIVABLES');
    }

    /**
     * 向微信返回信息
     * @param unknown_type $bool
     */
    public function to_message($bool,$order_sn="")
    {
        if ($bool) {
            if($order_sn != ""){
                $redisClient = new RedisClient();
                $redisClient->delKey($order_sn);
            }
            $message['return_code'] = 'SUCCESS';
            $messageXml = $this->arrayToXml($message);
        } else {
            $message['return_code'] = 'FAIL';
            $message['return_msg'] = '失败';
            $messageXml = $this->arrayToXml($message);
        }
        return $messageXml;
    }

    /**
     * 生成认证签名
     * @param $data
     * @return string
     */
    public  function create_sign($data)
    {
        $str = '';
        $key = $this->WxConfig['partnerkey'];
        ksort($data);//数组排序
        foreach ($data as $k => $v) {//拼接成相关规则的字符串
            if (!empty($v)) {
                if ($str == '') {
                    $str = $k . "=" . $v;
                } else {
                    $str .= '&' . $k . "=" . $v;
                }
            }
        }
        $str .= "&key=" . $key;
        //加密
        $str = MD5($str);
        $res = strtoupper($str);
        return $res;
    }

    /**
     * 验证参数的合法性
     * @return bool
     */
    public function checkSign($arr)
    {
        $tmpData = $arr;
        unset($tmpData['sign']);
        $sign = $this->create_sign($tmpData);//本地签名
        if ($arr['sign'] == $sign) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * 将xml转为array
     * @return array
     */
    public function xmlToArray($xml)
    {
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

    /**
     * array转xml
     * @return xml
     */
    public function arrayToXml($arr){
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (1 == 1) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";

            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * dj 2017-11-15 add
     * 短信购买后支付后回传地址
     *
     */
    public function sms_native_notify()
    {
        logw("http_raw=" . json_encode($this->http_raw), 'pay-return');
        if ($this->http_raw['return_code'] == 'SUCCESS' and $this->http_raw['result_code'] == 'SUCCESS') { //支付成功
            $r = $this->checkSign($this->http_raw);//验证参数的合法性
            if ($r) {
                $openid = $this->http_raw['openid'];
                $transaction_id = $this->http_raw['transaction_id'];//支付id
                $out_trade_no = $this->http_raw['out_trade_no'];//商家订单id order_sn
                $total_fee = $this->http_raw['total_fee'];//支付 金额
                $MsgcenterSmsorderModel = new MsgcenterSmsorderModel();
                $smsorder_info = $MsgcenterSmsorderModel->get_smsorder_info($out_trade_no);
                if (empty($smsorder_info)) {
                    echo $this->to_message(false);
                    exit;
                }
                if (number_format($smsorder_info['amount'], 2, '.', '') != number_format($total_fee / 100, 2, '.', '')) {
                    //支付金额和订单金额不一致
                    echo $this->to_message(false);
                    exit;
                }

                try {
                    //数据插入
                    $time = time();
                    $allorder['order_sn'] = $smsorder_info['order_sn']? $smsorder_info['order_sn']: '';
                    $allorder['create_time'] = $smsorder_info['create_time'];
                    $allorder['pay_time'] = $time;
                    $allorder['amount'] = number_format($total_fee / 100, 2, '.', '');
                    $allorder['pay_type'] = 1;
                    $allorder['wx_id'] = $transaction_id;
                    $allorder['bus_id'] = $smsorder_info['bus_id'] ? $smsorder_info['bus_id'] : 0;
                    $allorder['merchants_id'] = 1;
                    $allorder['from_type'] = 2;
                    $allorder['name'] = "购买短信包";
                    $allorder['description'] = $smsorder_info['remark'];
                    $allorder['openid'] = $openid;
                    $allorder['is_subscribe'] = $this->http_raw['is_subscribe'];;//是否关注
                    $allorder['trade_type'] = $this->http_raw['trade_type'];//交易类型
                    $allorder['trade_state'] = $this->http_raw['result_code'];//交易状态
                    $allorder['bank_type'] = $this->http_raw['bank_type'];//付款银行
                    $allorder['total_fee'] = $total_fee;//总金额
                    $allorder['fee_type'] = $this->http_raw['fee_type'];//货币钟类
                    $allorder['cash_fee'] = $this->http_raw['cash_fee'];//现金支付金额
                    $allorder['cash_fee_type'] = 0;//现金支付货币类型 主动拉去需要更新
                    $allorder['transaction_id'] = $transaction_id;//微信支付交易号
                    $allorder['out_trade_no'] = $out_trade_no;//微信返回商家订单号
                    $allorder['attach'] = 0;//商家数据包 主动拉去需要更新
                    $allorder['end_time'] = $this->http_raw['time_end'];//支付完成时间
                    $allorder['trade_state_desc'] = '';//交易状态描述，如:支付失败，请重新下单支付
                    $allorder['insert_time'] = $time;
                    $allorder['pay_state'] = 2;
                    $MsgcenterSmsorderInfoModel = new MsgcenterSmsorderInfoModel();
                    $result = $MsgcenterSmsorderInfoModel->where(['wx_id'=>$transaction_id])->find();
                    if($result) {
                        unset($allorder['wx_id']);
                        $allorder_rst = $MsgcenterSmsorderInfoModel->where(['wx_id'=>$transaction_id])->save($allorder);
                    } else {
                        $allorder_rst = $MsgcenterSmsorderInfoModel->add($allorder);
                    }
                    //判断支付状态
                    if ($smsorder_info['pay_status'] == 1 && $smsorder_info['deleted'] == 0) {//已经支付成功
                        echo $this->to_message(true,$smsorder_info['order_sn']);
                        exit;
                    }
                    $model = M();
                    $model->startTrans();
                    //如果没有添加
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
                        //再判断支付状态
                        $smsorder_info = $MsgcenterSmsorderModel->get_smsorder_info($out_trade_no);
                        if($smsorder_info['pay_status'] == 0 && $smsorder_info['status'] == 0){
                            //redis唯一key
                            $is_set = $redis->setLockKey($lockKey, $value, array('nx', 'ex' => $lockSecond));
                        }
                        //判断锁的状态
                        if ($is_set) {
                            //修改场馆短信条数
                            $BusinessModel = new BusinessModel();
                            $update_status = $BusinessModel->setInc_sms_number($smsorder_info['bus_id'], $smsorder_info['sms_number']);
                            logw("allorder_rst=" . json_encode($allorder_rst), 'pay-return');
                            if (!$update_status) {
                                $model->rollback();
                                throw new \Exception('写入总订单失败');
                            }
                            $pay_status = 1;
                            $status = 1;
                            unset($smsorder_info);
                            $smsorder_info = $MsgcenterSmsorderModel->update_smsorder_pay_status($out_trade_no,$pay_status, $status);//修改订单信息
                            logw("smsorder_info=" . json_encode($smsorder_info), 'pay-return');
                            if ($smsorder_info['pay_status'] != $pay_status) {
                                throw new \Exception('修改订单信息失败');
                            }
                            //删除锁
                            $redis->delKey($lockKey);//操作解锁
                            $model->commit();
                            logw("数据全部成功写入数据库！" , 'pay-return');
                            echo $this->to_message(true);
                            exit;
                        }
                    }else{
                        $model->rollback();
                        logw("锁定状态！" , 'pay-return');
                        throw new \Exception('锁定状态！');
                    }
                    $model->commit();
                } catch (\Exception $e) {
                    $model->rollback();
                    logw("pay_error=" . $e->getMessage(), 'pay-return');
                    echo $this->to_message(false);
                    exit;
                }
            } else {
                logw("pay=支付验证失败", 'pay-return');
                echo $this->to_message(false);
                exit;
            }
        } else {
            logw("pay=支付结果失败", 'pay-return');
            echo $this->to_message(false);
            exit;
        }
    }
}
