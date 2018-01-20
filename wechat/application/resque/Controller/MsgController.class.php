<?php
/**
 * Created by PhpStorm.
 * User: dj
 * Date: 2017/4/8 0026
 * Time: 上午 11:04
 */
namespace MsgCenter\Controller;

use Common\Tools\RedisClient;
use MsgCenter\Service\MsgService;
use Common\Model\BusinessModel;
require_once __DIR__.'/../Resque/Queue.php';

class MsgController extends CommonController
{
    /**
     * 场馆发送消息前判断
     * lq 2017/11/08 add
     */
    public function getInfoBeforeSend()
    {
        try {
            //获取参数
            $bus_id      = session('bus_id')? session('bus_id'): I('bus_id');  // 场馆ID, 必填
            $send_object = $_POST['send_object']; // 发送对象(ordinary, private, expire, 223, 179), 必填
            $title       = htmlspecialchars_decode(I('title'));       // 标题(主题), 非必填
            $content     = htmlspecialchars_decode(I('content'));     // 内容, 必填
            $sign        = htmlspecialchars_decode(I('sign'));        // 签名, 必填

            //参数验证
            if(empty($bus_id) || !is_numeric($bus_id)) {
                throw new \Exception('参数错误@bus_id', PARAM_ERROR);
            }
            array_map(function ($key, $value) {
                if(empty($value) && $value !=0) {
                    throw new \Exception('参数错误@'.$key, PARAM_ERROR);
                }
            }, ['send_object', 'content', 'sign'], [$send_object, $content, $sign]);

            //计算短信实际条数
            if(empty($title)) {
                $length = mb_strlen($sign.$content,"UTF8");
            } else {
                $length = mb_strlen($sign.$title.$content,"UTF8");
            }
            logw('BeforeSendLength = '.json_encode($length));
            $weight = ceil($length/63);

            //计算需发送的用户ID
            $MsgService = new MsgService();
            $uids = $MsgService->getSendUids($bus_id, $send_object);

            //判断场馆剩余短信数量是否够用
            $consume_sms = count($uids)*$weight;
            $surplus_sms = $MsgService->getBusSurplusSms($bus_id);
            if($surplus_sms < $consume_sms) {
                $this->ajaxReturn([
                    'errorcode' => SMS_INSUFFICIENT,
                    'errormsg'  => '短信数量不足'.$surplus_sms.'条，请充值！',
                    'data'      => [
                        'consume_sms' => $consume_sms,
                        'surplus_sms' => $surplus_sms,
                    ]
                ]);
            }
            $this->ajaxReturn([
                'errorcode' => SMS_CONSUME,
                'errormsg'  => '本次发送将消耗'.$consume_sms.'条短信！',
                'data'      => [
                    'consume_sms' => $consume_sms,
                    'surplus_sms' => $surplus_sms,
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
     * 场馆发送消息
     * lq 2017/11/08 add
     */
    public function sendMsg()
    {
        try {
            //获取参数
            $bus_id = session('bus_id');  // 场馆ID, 必填
            $sender_id = session('authId'); //发送账号ID, 必填
            $sender_name = session('username');  //发送账号, 必填

            //$sender_id   = I('admin_id');    // 发送账号ID, 必填
            //$sender_name = I('admin_name');  // 发送账号, 必填
            //$bus_id      = I('bus_id');      // 场馆ID, 必填

            //开始执行添加
            //开始启动redis锁
            //加锁机制。代码进入操作前检查操作是否上锁，如果锁上，中断操作。
            //否则进行下一操作，第一步将操作上锁，然后执行代码，最后执行完代码将操作锁打开。
            //声明redis
            $redis = new RedisClient();
            //定义锁的时间秒数
            $lockSecond = 300;
            //锁的值
            $lockKey="send_".$bus_id;
            $value = 1;
            //获取锁的状态
            $lock_status = $redis->get($lockKey);
            if (empty($lock_status)) {
                //Redis实现分布式锁,不能同时创建
                $is_set= $redis->setLockKey($lockKey, $value, array('nx', 'ex' => $lockSecond));
                //判断锁的状态
                if ($is_set) {
                        $send_object = $_POST['send_object']; // 发送对象(ordinary, private, expire, 223, 179), 必填
                        $title       = htmlspecialchars_decode(I('title'));       // 标题(主题), 非必填
                        $content     = htmlspecialchars_decode(I('content'));     // 内容, 必填
                        $sign        = htmlspecialchars_decode(I('sign'));        // 签名, 必填

                        //过滤空格换行
                        $title = str_replace([" ","　","\t","\n","\r"], '', $title);
                        $content = str_replace([" ","　","\t","\n","\r"], '', $content);

                        //参数验证
                        if(empty($bus_id) || !is_numeric($bus_id)) {
                            throw new \Exception('参数错误@bus_id', PARAM_ERROR);
                        }
                        if(empty($sender_id) || !is_numeric($sender_id)) {
                            throw new \Exception('参数错误@sender_id', PARAM_ERROR);
                        }
                        array_map(function ($key, $value) {
                            if(empty($value) && $value !=0 ) {
                                throw new \Exception('参数错误@'.$key, PARAM_ERROR);
                            }
                        }, ['send_object', 'content', 'sign'], [$send_object, $content, $sign]);

                        //计算短信实际条数
                        if(empty($title)) {
                            $length = mb_strlen($sign.$content,"UTF8");
                        } else {
                            $length = mb_strlen($sign.$title.$content,"UTF8");
                        }
                        $weight = ceil($length/63);

                        //计算需发送的用户ID
                        $MsgService = new MsgService();
                        $uids = $MsgService->getSendUids($bus_id, $send_object);
                        if(count($uids) < 1) {
                            throw new \Exception('发送失败！', SEND_MSG_FAIL);
                        }

                        //判断场馆剩余短信数量是否够用
                        $consume_sms = count($uids)*$weight;
                        $surplus_sms = $MsgService->getBusSurplusSms($bus_id);
                        if($surplus_sms < $consume_sms) {
                            $this->ajaxReturn([
                                'errorcode' => SMS_INSUFFICIENT,
                                'errormsg'  => '短信数量不足，请充值！',
                                'data'      => [
                                    'consume_sms' => $consume_sms,
                                    'surplus_sms' => $surplus_sms,
                                ]
                            ]);
                        }

                        //写入数据库
                        $s_data = [
                            'bus_id'      => $bus_id,
                            'sender_id'   => $sender_id,
                            'sender_name' => $sender_name,
                            'send_object' => json_encode($send_object, JSON_UNESCAPED_UNICODE),
                            'notice_way'  => 2,
                            'title'       => $title? $title: '',
                            'content'     => $content,
                            'sms_num'     => count($uids)*$weight,
                            'send_status' => 1,
                            'send_time'   => time(),
                            'create_time' => time()
                        ];
                        $insert_id = $MsgService->addMsg($s_data);

                        $model = M();
                        $model->startTrans();
                        $BusinessModel = new BusinessModel();
                        $update_status = $BusinessModel->setDes_sms_number($bus_id, count($uids)*$weight);
                        if(empty($insert_id) || empty($update_status)) {
                            $model->rollback();
                            throw new \Exception('发送失败！', SEND_MSG_FAIL);
                        }
                        $model->commit();

                        //发送信息
                        if($insert_id) {
                            //调用发送信息接口
                            $queue = 'default';
                            $job   = 'SendSmsJob';
                            $args  = [
                                'msg_id'  => $insert_id,
                                'user_id' => $uids,
                                'msg'     => $title? '【'.$sign.'】'.$title.'：'.$content: '【'.$sign.'】'.$content,
                                'title'   => $title? $title: ''
                            ];
                            $job_id = \Queue::in($queue, $job, $args, true);
                            $redis->delKey($lockKey);//操作解锁
                            $this->ajaxReturn([
                                'errorcode' => SUCCESS,
                                'errormsg' => '发送成功！',
                                'data' => [
                                    'job_id' => $job_id,
                                    'push_time' => time(),
                                ]
                            ]);
                        } else {
                            $this->ajaxReturn([
                                'errorcode' => SEND_MSG_FAIL,
                                'errormsg' => '发送失败！'
                            ]);
                        }
                }else{
                    throw new \Exception('操作频繁', FAIL);
                }
            }else{
                throw new \Exception('操作频繁', FAIL);
            }
        } catch(\Exception $e) {
            $this->ajaxReturn([
                'errorcode' => $e->getCode(),
                'errormsg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 获取场馆发消息通知列表
     * lq 2017/11/08 add
     */
    public function getMsgList()
    {
        try {
            //获取参数
            $bus_id   = session('bus_id')? session('bus_id'): I('bus_id');   // 场馆ID, 必填
            $keyword    = I('keyword');    // 主题名称, 非必填
            $begin_date = I('begin_date'); // 发送时间, 费必填
            $end_date   = I('end_date');   // 发送时间, 非必填
            $page_no = I('page_no', 1);
            $page_size = I('page_size', 10);

            //参数验证
            if(empty($bus_id) || !is_numeric($bus_id)) {
                throw new \Exception('参数错误@bus_id', PARAM_ERROR);
            }
            array_map(function ($key, $value) {
                if(!empty($value) && (!preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $value) || !strtotime($value))) {
                    throw new \Exception('参数错误@'.$key, PARAM_ERROR);
                }
            }, ['begin_date', 'end_date'], [$begin_date, $end_date]);

            //查询数据
            $MsgService = new MsgService();
            $data = $MsgService->getMsgList($bus_id, $keyword, $begin_date, $end_date, $page_no, $page_size);
            $this->ajaxReturn([
                'errorcode' => SUCCESS,
                'errormsg'  => '获取成功！',
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
     * 获取场馆发消息通知详情
     * lq 2017/11/09 add
     */
    public function getMsgInfo()
    {
        try {
            //获取参数
            $msg_id     = I('msg_id');     // 消息ID, 必填

            //参数验证
            if(empty($msg_id) || !is_numeric($msg_id)) {
                throw new \Exception('参数错误@msg_id', PARAM_ERROR);
            }

            //查询数据
            $MsgService = new MsgService();
            $data = $MsgService->getMsgInfo($msg_id);
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
     * 查询场馆会员
     * 2017/11/09 add
     */
    public function searchMember()
    {
        try {
            //获取参数
            $bus_id  = session('bus_id'); // 场馆ID, 必填
            $keyword = I('keyword');      // 查询关键字（姓名，或电话号码，或卡号）
            $user_id = I('user_id');      // 查询关键字（用户ID）

            //参数验证
            if(empty($bus_id) || !is_numeric($bus_id)) {
                throw new \Exception('参数错误@bus_id', PARAM_ERROR);
            }
            if(empty($keyword) && empty($user_id)) {
                throw new \Exception('参数错误', PARAM_ERROR);
            }

            //查询数据
            $MsgService = new MsgService();
            $data = $MsgService->searchMember($bus_id, $keyword, $user_id);
            $this->ajaxReturn([
                'errorcode' => SUCCESS,
                'errormsg'  => '获取成功！',
                'data'      => $data
            ]);

        } catch(\Exception $e) {
            $this->ajaxReturn([
                'errorcode' => $e->getCode(),
                'errormsg'  => $e->getMessage()
            ]);
        }
    }
}