<?php
namespace app\mobile\controller;

use \app\common\tools\RedisClient;
use \app\common\service\CourseMark as CourseMarkService;
use \app\common\model\ClassMark as ClassMarkModel;
use think\Config;
use think\Db;

require_once __DIR__.'/../../../extend/Wxpay/lib/WxPay.Api.php';
require_once __DIR__.'/../../../extend/Wxpay/lib/WxPay.Data.php';

class Wxpaynotify extends \WxPayNotifyReply
{
    /**
     * 单次购卡回调处理逻辑
     * @return bool
     * @throws \Exception
     * @throws \WxPayException
     */
    public function SinglePayCourse()
    {
        //小程序对应的商家支付秘钥
        $partnerkey = Config::get('key');

        //获取通知的数据
        $xml = file_get_contents('php://input');
        $array = $this->FromXml($xml);
        log_w('会员端2.0单次购课-微信回调数据:' . json_encode($array), 'pay-return');

        //签名验证
        $data = \WxPayResults::Init($xml, $partnerkey);
        if ($data['return_code'] == 'SUCCESS' and $data['result_code'] == 'SUCCESS') { //支付成功
            $transaction_id = $data['transaction_id'];//支付id
            $out_trade_no   = $data['out_trade_no'];//商家订单id
            $total_fee      = $data['total_fee'];//支付金额
            $map['order_sn'] = $out_trade_no;
            $order_info = Db::table('rb_cardorder_info')->where($map)->find();
            if (empty($order_info)) {
                //打日志，未查询该订单
                $this->SetReturn_code("FAIL");
                $this->SetReturn_msg('订单异常！');
                $this->ReplyNotify(false);
                log_w('会员端2.0单次购课-微信回调: 订单异常！', 'pay-return');
                exit;
            }
            if (2 == $order_info['pay_status']) { // 回调处理已完成
                $this->SetReturn_code("SUCCESS");
                $this->SetReturn_msg("OK");
                $this->ReplyNotify(false);
                exit;
            }

            //回调处理逻辑
            Db::startTrans();

            //添加预约
            $redisClient = new RedisClient();
            $class_mark = $redisClient->get($out_trade_no);
            $class_mark = json_decode($class_mark, true);
            $course_schedule_id = $class_mark['course_schedule_id'];
            $CourseMarkService = new CourseMarkService();
            $class_mark_id = $CourseMarkService->add_mark_by_transaction(0, $course_schedule_id, $order_info['bus_id'], $order_info['user_id'], 0, $class_mark['num'], "balance_pay_card");

            if(false == $class_mark_id) {
                $this->SetReturn_code("FAIL");
                $this->SetReturn_msg('添加预约失败！');
                $this->ReplyNotify(false);
                exit;
            }

            //修改订单
            unset($map);
            $map['order_sn'] = $out_trade_no;
            $order_data['order_status'] = 2;
            $order_data['pay_status'] = 2;
            $order_data['pay_time'] = time();
            $order_data['pay_type'] = 1;
            $order_data['class_mark_id'] = $class_mark_id;
            $order_data['edit_time'] = time();
            $order_data['deal_time'] = time();
            $order_data['wx_id'] = $transaction_id;
            $order_data['edit_time'] = time();
            $order_r = Db::table('rb_cardorder_info')->where($map)->update($order_data);//修改订单信息

            //数据插入allorder
            $merchant = Db::table('rb_business')->where(['id' => $order_info['bus_id']])->field("merchants_id")->find();
            $merchants_id = $merchant['merchants_id'];
            $time = time();
            $allorder['order_sn']          = isset($order_info['order_sn']) ? $order_info['order_sn'] : '';
            $allorder['user_id']           = isset($order_info['user_id']) ? $order_info['user_id'] : 0;
            $allorder['phone']             = isset($order_info['phone']) ? $order_info['phone'] : '';
            $allorder['create_time']       = isset($order_info['create_time']) ? $order_info['create_time'] : 0;
            $allorder['pay_time']          = $time;
            $allorder['amount']            = number_format($total_fee / 100, 2, '.', '');
            $allorder['pay_type']          = isset($order_info['pay_type']) ? $order_info['pay_type'] : 0;
            $allorder['wx_id']             = $transaction_id;
            $allorder['bus_id']            = isset($order_info['bus_id']) ? $order_info['bus_id'] : 0;
            $allorder['merchants_id']      = $merchants_id;
            $allorder['from_type']         = isset($order_info['from_type']) ? $order_info['from_type'] : 0;
            $allorder['name']              = isset($order_info['name']) ? $order_info['name'] : '';
            $allorder['description']       = isset($order_info['description']) ? $order_info['description'] : '';
            $allorder['device_info']       = isset($order_info['device_info']) ? $order_info['device_info'] : '';//设备号
            $allorder['openid']            = $data['openid'];
            $allorder['is_subscribe']      = $data['is_subscribe'];;//是否关注
            $allorder['trade_type']        = $data['trade_type'];//交易类型
            $allorder['trade_state']       = $data['result_code'];//交易状态
            $allorder['bank_type']         = $data['bank_type'];//付款银行
            $allorder['total_fee']         = $total_fee;//总金额
            $allorder['fee_type']          = $data['fee_type'];//货币钟类
            $allorder['cash_fee']          = $data['cash_fee'];//现金支付金额
            $allorder['cash_fee_type']     = 0;//现金支付货币类型 主动拉去需要更新
            $allorder['transaction_id']    = $transaction_id;//微信支付交易号
            $allorder['out_trade_no']      = $out_trade_no;//微信返回商家订单号
            $allorder['attach']            = 0;//商家数据包 主动拉去需要更新
            $allorder['end_time']          = $data['time_end'];//支付完成时间
            $allorder['trade_state_desc']  = isset($order_info['trade_state_desc']) ? $order_info['trade_state_desc'] : '';//交易状态描述，如:支付失败，请重新下单支付
            $allorder['insert_time']       = $time;
            $allorder['withdrawal_status'] = 4;
            $allorder['pay_state']         = 2;
            $allorder['card_id']           = 0;
            $allorder['order_type']        = 2;
            $allorder['withdrawal_id']     = 0;
            $allorder['auth_code']         = '';
            $allorder['transaction_number'] = 0;
            $allorder['class_mark_id']     = $class_mark_id;
            $allorder_rst = Db::table("rb_allorder_info")->insert($allorder);

            $result = true;
            if (!$allorder_rst || !$order_r) {
                $result = false;
                Db::rollback();
                log_w('会员端2.0单次购课-微信回调: 订单更新失败！', 'pay-return');
            }
            Db::commit();//

            //发送消息
            unset($map);
            $map['id'] = $class_mark_id;
            $classMarkModel = new ClassMarkModel();
            $res2 = $classMarkModel->get_map_class_mark($map);
            $content_array = array(
                "bus_name"=>$res2['bus_name'],
                "date"=>date('m月d', $res2['date_time']),
                "time"=>$res2['beg_time'],
                "class_name"=>$res2['class_name']
            );
           func_sms_v5($order_info['phone'], $content_array, "SMS_78610178");

        } else {
            $result = false;
        }

        //返回结果
        if($result == false) {
            $this->SetReturn_code("FAIL");
            $this->SetReturn_msg('FAIL');
            $this->ReplyNotify(false);
            exit;
        } else {
            $this->SetReturn_code("SUCCESS");
            $this->SetReturn_msg("OK");
            $this->ReplyNotify(false);
        }
        exit;
    }

    /**
     * 回复通知
     * @param bool $needSign 是否需要签名输出
     */
    private function ReplyNotify($needSign = true)
    {
        //如果需要签名
        if($needSign == true &&
            $this->GetReturn_code() == "SUCCESS")
        {
            $this->SetSign();
        }
        \WxpayApi::replyNotify($this->ToXml());
    }
}
