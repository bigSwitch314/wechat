<?php
/**
 * Created by PhpStorm.
 * User: dj
 * Date: 2017/4/8 0026
 * Time: 上午 11:04
 */
namespace MsgCenter\Controller;

use MsgCenter\Service\MsgService;
use Common\Model\BusinessModel;
require_once __DIR__.'/../../../vendor/sms/SMSApi.php';

class ResqueController extends CommonController
{
//    public function __construct()
//    {
//        if(!IS_CLI){
//            $this->ajaxReturn([
//                'errorcode' => NOT_CLI_MODEL,
//                'errormsg' => '非CLI模式！',
//            ]);
//        }
//    }

    /**
     * 根据用户ID获取电话
     * lq 2017/11/13 add
     */
    public function getPhoneByUid()
    {
        try {
            //获取参数
            $uids   = I('uids');     // 账号ID, 必填

            //参数验证
            if(empty($uids) || !is_string($uids)) {
                throw new \Exception('参数错误@uids', PARAM_ERROR);
            }

            //查询数据
            $MsgService = new MsgService();
            $data = $MsgService->getPhoneByUid($uids);
            $this->ajaxReturn([
                'errorcode' => SUCCESS,
                'errormsg' => '获取成功！',
                'data'      => $data
            ]);

        } catch(\Exception $e) {
            $this->ajaxReturn([
                'errorcode' => $e->getCode(),
                'errormsg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 发送短信接口
     * lq 2017/11/13 add
     */
    public function sendSmsOld()
    {
        try {
            //获取参数
            $phone = I('phone'); // 电话, 必填
            $msg   = I('msg');   // 消息内容, 必填

            //参数验证
            if(empty($phone) || empty($msg)) {
                throw new \Exception('参数错误', PARAM_ERROR);
            }

            //查询数据
            $send_status = send_phone_dcount($phone, $msg);
            $this->ajaxReturn([
                'send_status' => $send_status
            ]);

        } catch(\Exception $e) {
            $this->ajaxReturn([
                'errorcode' => $e->getCode(),
                'errormsg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 发送短信接口
     * lq 2017/11/13 add
     */
    public function sendSms()
    {
        try {
            //获取参数
            $phone = I('phone'); // 电话, 必填
            $msg   = I('msg');   // 消息内容, 必填

            //参数验证
            if(empty($phone) || empty($msg)) {
                throw new \Exception('参数错误', PARAM_ERROR);
            }

            //查询数据
            $msg = urldecode($msg);
            $result = \SMSApi::sendSMS($phone, $msg, $size = 2);
            $this->ajaxReturn([
                'send_status' => $result
            ]);

        } catch(\Exception $e) {
            $this->ajaxReturn([
                'errorcode' => $e->getCode(),
                'errormsg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 更新场馆发消息通知详情
     * lq 2017/11/13 add
     */
    public function updateMsgInfo()
    {
        try {
            //获取参数
            $msg_id        = I('msg_id');        // 消息详情ID, 必填
            $send_fail_num = I('send_fail_num'); // 成功发送消息数量, 必填

            //参数验证
            if(empty($msg_id) || !is_numeric($msg_id) ||
               empty($send_fail_num) || !is_numeric($send_fail_num)
            ) {
                throw new \Exception('参数错误', PARAM_ERROR);
            }

            //更新数据
            $MsgService = new MsgService();
            $BusinessModel = new BusinessModel();
            $smginfo = $MsgService->getMsgInfo($msg_id);

            $bus_id = $smginfo['bus_id'];
            $BusinessModel->setInc_sms_number($bus_id, $send_fail_num);

            $where['id'] = $msg_id;
            $update['sms_num'] = $smginfo['sms_num'] - $send_fail_num;
            $update_status = $MsgService->updaeMsgInfo($where, $update);

            $this->ajaxReturn([
                'update_status' => $update_status
            ]);

        } catch(\Exception $e) {
            $this->ajaxReturn([
                'errorcode' => $e->getCode(),
                'errormsg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 将发送短信记录写入数据库
     * lq 2017/11/13 add
     */
    public function addSmsSendLog()
    {
        try {
            //获取参数
            $s_data =$_GET['s_data']; // 保存的数据, 必填

            //参数验证
            if(empty($s_data)) {
                throw new \Exception('参数错误@s_data', PARAM_ERROR);
            }

            //写入数据
            $s_data = json_decode($s_data, true);
            if(count($s_data) == count($s_data,1)){  // count(array,mode); array是数组，mode默认为0，1是递归的计数
                $s_data = [$s_data];
            }
            $MsgService = new MsgService();
            $add_status = $MsgService->addSmsSendLog($s_data);
            $this->ajaxReturn([
                'add_status' => $add_status
            ]);

        } catch(\Exception $e) {
            $this->ajaxReturn([
                'errorcode' => $e->getCode(),
                'errormsg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 获取阿里云可用短信数量
     * lq 2017/11/14 add
     * @return bool
     */
    public function getSmsSurplus()
    {
        $result = \SMSApi::getBalance();
        $result = json_decode($result, true);
        $balance = $result['data']['balance'];
        $this->ajaxReturn([
            'sms_surplus' => $balance
        ]);
    }

    /**
     * 短信不足5000条提醒
     * lq 2017/11/25 add
     * @return bool
     */
    public function getSmsNumber() {
        $result = \SMSApi::getBalance();
        $result = json_decode($result, true);
        $balance = $result['data']['balance'];
        if($balance < 5000) {
            $msg = '亿美软通平台剩余短信数量不足'.$balance.'条，请及时充值！';
            $phone = '18610963991,18983391264,18203004644';
            \SMSApi::sendSMS($phone, '【勤鸟运动】'.$msg);
            return true;
        }
        return false;
    }

    /**
     * 获取状态报告接口
     * lq 2017/11/25 add
     * @return bool
     */
    public function getReport() {
        $result = \SMSApi::getReport();
        echo  $result;
    }

}

