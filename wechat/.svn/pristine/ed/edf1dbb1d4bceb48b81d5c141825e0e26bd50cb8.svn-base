<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/29 0029
 * Time: 下午 5:11
 */
namespace MsgCenter\Controller;
use Common\Service\WePlayService;
use Think\Controller;

class WxPayController extends Controller
{
    public function refund(){
        $order_sn = I('post.transaction_id');
        $amount = I('post.amount');
        $total_fee = $amount * 100;
        $refund_fee = $amount * 100;
        $wechat_play_service = new WePlayService();
        $refund = $wechat_play_service -> refund($order_sn, $total_fee, $refund_fee);
        //退钱成功就改订单信息
        logw("refund=" . json_encode($refund), 'pay-return');
        if ($refund['return_code'] == "SUCCESS" and $refund['result_code'] == "SUCCESS") {    //判断是不是退款成功
            $this->ajaxReturn([
                'errorcode' => SUCCESS,
                'errormsg' => '成功'
            ]);
        }else{
            $this->ajaxReturn([
                'errorcode' => SUCCESS,
                'errormsg' => '失败'
            ]);
        }
    }

}
