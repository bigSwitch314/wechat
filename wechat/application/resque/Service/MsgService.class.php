<?php
/* =============================================================================
 * Author: luoqiang - 408964446@qq.com
 * QQ : 408964446
 * Last modified: 2016-07-06 16:03
 * Filename: Booking.class.php
 * Description: 
 * @copyright    Copyright (c) 2016, rocketbird.cn
=============================================================================*/
namespace MsgCenter\Service;

use MsgCenter\Model\MsgcenterMsginfoModel;
use Common\Model\UserBusModel;

class MsgService
{
    /**
     * 消息中心详情模型
     * @var $MsgcenterMsginfoModel
     */
    private $MsgcenterMsginfoModel;

    /**
     * 实例化 MsgcenterMsginfoModel
     * @return MsgcenterMsginfoModel
     */
    private function getMsgcenterMsginfoModel()
    {
        if (empty($this->MsgcenterMsginfoModel)) {
            $this->MsgcenterMsginfoModel = new MsgcenterMsginfoModel();
        }
        return $this->MsgcenterMsginfoModel;
    }

    /**
     * 添加发送消息
     * lq 2017/11/08 add
     * @param $s_data
     * @return mixed
     */
    public function addMsg($s_data)
    {
        return $this->getMsgcenterMsginfoModel()->addMsg($s_data);
}

    /**
     * 获取场馆发送消息通知列表
     * lq 2017/11/08 add
     * @param $bus_id
     * @param $keyword
     * @param $begin_date
     * @param $end_date
     * @return mixed
     */
    public function getMsgList($bus_id, $keyword, $begin_date, $end_date, $page_no, $page_size)
    {
        $result = $this->getMsgcenterMsginfoModel()->getMsgList($bus_id, $keyword, $begin_date, $end_date, $page_no, $page_size);
        foreach($result['list'] as &$v) {
            $v['receiver'] = array_reverse(json_decode($v['send_object'], true));
            $v['receiver'] = implode('、', $v['receiver']);
            $v['send_time'] = date('Y-m-d H:i', $v['create_time']);
            unset($v['create_time'], $v['send_object']);
        }
        return [
            'count'  => $result['count']? $result['count']: 0,
            'list' => $result['list']? $result['list']: []
        ];
    }

    /**
     * 获取场馆发消息通知详情
     * lq 2017/11/09 add
     * @param $msg_id
     * @return mixed
     */
    public function getMsgInfo($msg_id)
    {
        $info = $this->getMsgcenterMsginfoModel()->getMsgInfo($msg_id);
        $info['receiver'] = array_values(json_decode($info['send_object'], true));
        $info['send_time'] = $info['create_time'];
        unset($info['send_object'], $info['create_time']);
        return $info? $info: [];
    }

    /**
     * 查询场馆会员
     * lq 2017/11/09 add
     * @param $keyword
     * @return mixed
     */
    public function searchMember($bus_id, $keyword, $user_id)
    {
        $member = $this->getMsgcenterMsginfoModel()->searchMember($bus_id, $keyword, $user_id);
        return $member? $member: [];
    }

    /**
     *  PC首页账号详细
     * lq 2017/11/09 add
     * @param $bus_id
     * @return array|mixed
     */
    public function getAccountDetails($bus_id)
    {
        $account_details = $this->getMsgcenterMsginfoModel()->getAccountDetails($bus_id);
        $account_details['version'] = $account_details['version']? $account_details['version']: '';
        if((int)$account_details['sms_number'] < 0) {
            $account_details['sms_number'] = 0;
        }
        if(empty($account_details['expire_date'])) {
            $account_details['expire_date'] = '未知';
        } else {
            $account_details['expire_date'] = date('Y-m-d', $account_details['expire_date']);
        }
        $version = [
            '0' => '体验版',
            '1' => '工作室版',
            '2' => '俱乐部版',
            '3' => '智能版',
            '4' => '基础版',
        ];
        $account_details['version'] = $version[$account_details['version_id']]? $version[$account_details['version_id']]: $account_details['version'];
        unset($account_details['version_id']);
        return $account_details? $account_details: [];
    }

    /**
     * 根据用户ID获取电话
     * lq 2017/11/13 add
     * @param $uids
     * @return array
     */
    public function getPhoneByUid($uids)
    {
        $phones = $this->getMsgcenterMsginfoModel()->getPhoneByUid($uids);
        return $phones? $phones: [];
    }

    /**
     * 更新场馆发消息通知详情
     * lq 2017/11/13 add
     * @param $where
     * @param $update
     * @return mixed
     */
    public function updaeMsgInfo($where, $update)
    {
        return $this->getMsgcenterMsginfoModel()->updateMsgInfo($where, $update);
    }

    /**
     * 将发送短信记录写入数据库
     * lq 2017/11/13 add
     * @param $s_data
     * @return mixed
     */
    public function addSmsSendLog($s_data)
    {
        return $this->getMsgcenterMsginfoModel()->addSmsSendLog($s_data);
    }

    /**
     * 判断用户类型
     * LQ 2017/11/15 add
     * @param $bus_id
     */
    public function getUids($bus_id)
    {
        $where['ub.bus_id'] = array('eq',$bus_id);
        $where['ub.is_disable'] = array('eq',1);
        $UserBusModel = new UserBusModel();
        $list1 = $UserBusModel->get_all_user_id($where);
        foreach($list1 as $k=>$v){
            if($v['card_type_id'] == 3){
                $list1[$k]['all_num'] = $v['total'];
                $list1[$k]['last_num'] = $v['balance'];
            }
        }
        //此处与会籍端的判断方式不同,此处只需要判断存在某中状态就判定为某种状态
        foreach($list1 as $k=>$v){
            //未指派会籍
            if (empty($v['marketers_id'])) {
                $array4[$v['user_id']] = $v['user_id'];
            }
            //有效用户
            if(($v['deleted']==0 or $v['deleted']==4) and $v['status']==1 and $v['card_user_id']!=0){
                if(($v['end_time']>time()
                        and ($v['card_type_id']==1 or ($v['card_type_id']==2 and $v['last_num']>0)
                            or ($v['card_type_id']==3 and $v['balance']>0)
                            or ($v['card_type_id']==4 and $v['last_num']>0)))
                    or ($v['end_time']==0 and $v['active_time']==0)
                ){
                    $array1[$v['user_id']] = $v['user_id'];
                    if($v['card_type_id'] == 4) {
                        $array11[$v['user_id']] = $v['user_id']; //私教会员
                    }
                    continue ;
                }else{
                    $array2[$v['user_id']] = $v['user_id'];
                    continue ;
                }
            }
            //无卡用户
            if(($v['status']==1 and ($v['deleted']==1 or $v['deleted']==2 or $v['deleted']==3))
                or is_null($v['status'])
                or $v['status']==0
                or ($v['status']==0 and ($v['deleted']==0 or $v['deleted']==4))
            ){
                $array3[$v['user_id']] = $v['user_id'];
                continue ;
            }
        }
        //存在正常的
        if($array1){
            //过期的
            $array2 = array_diff($array2,$array1);
            if($array2){
                $array3 = array_diff($array3,$array1,$array2);
            }else{
                $array3 = array_diff($array3,$array1);
            }
        }else{
            if($array2){
                $array3 = array_diff($array3,$array2);
            }
        }

        $uids['private'] = $array11;
        $uids['ordinary'] = array_diff((array)$array1, (array)$array11);
        $uids['expire'] = $array2;

        return $uids;
    }

    /**
     * lq 2017-8-7 add
     * 获取会员类型（会员、即将过期会员、过期会员、非会员）
     */
    public function get_user_type($user_type){
        //$user_type数据结构
//		$user_type = [
//			'13' => [
//				'marketer_setting' => [
//					'marketers_id'    => 13,
//					'card_day_remind' => 7,
//					'card_num_remind' => 1,
//					'card_val_remind' => 1,
//				],
//				'users' => [
//					'0' =>[
//
//						'user_id' => 9880,
//                        'card_type_id' => 3,
//                        'marketers_id' => 13,
//                        'status' => 0,
//                        'all_num' => 100,
//                        'suspend_time' => 0,
//                        'all_days' => 89,
//                        'last_num' => 100,
//                        'active_time' => 1499356800,
//                        'end_time' => 1507132799,
//                        'charge_type' => 1,
//                        'deleted' => 2,
//					],
//					'1' =>[
//
//							'user_id' => 9880,
//							'card_type_id' => 3,
//							'marketers_id' => 13,
//							'status' => 0,
//							'all_num' => 100,
//							'suspend_time' => 0,
//							'all_days' => 89,
//							'last_num' => 100,
//							'active_time' => 1499356800,
//							'end_time' => 1507132799,
//							'charge_type' => 1,
//							'deleted' => 2,
//					],
//				],
//			],
//		];
        foreach($user_type as $key=>$val){
            $card_day_remind   = $val['marketer_setting']['card_day_remind'];
            $card_num_remind   = $val['marketer_setting']['card_num_remind'];
            $card_value_remind = $val['marketer_setting']['card_value_remind'];
            $time = time();
            foreach($val['users'] as $k=>$v){
                //采集用户及用户当前会籍id
                $users[] = $v['user_id'];
                $ub_ms_id[$v['user_id']] = $v['ub_ms_id'];
                //有效用户
                if(in_array($v['deleted'],[0,4]) and $v['status']=="1"){
                    if(($v['end_time']>$time and ($v['charge_type']==2 or ($v['charge_type']=="1" and ($v['last_num']>0 or $v['balance'] >0) )))
                        or ($v['end_time']==0 and $v['active_time']==0)){
                        $array1[$v['user_id']] = $v['user_id'];
                        if($v['card_type_id']==4) {
                            $array11[$v['user_id']] = $v['user_id']; //私教会员
                        }
                        //判断有无即将过期的会员
                        if($v['card_type_id']==1 and ($v['end_time']-($card_day_remind*24*3600))<=$time and $v['end_time']>$time){
                            $array5[$v['user_id']] = $v['user_id'];
                            continue;
                        }
                        if($v['card_type_id']==2 and $v['last_num']>0 and $v['last_num']<=$card_num_remind and $v['end_time']>$time){
                            $array5[$v['user_id']] = $v['user_id'];
                            continue;
                        }
                        if($v['card_type_id']==3 and $v['last_num']>0 and $v['last_num']<=$card_value_remind and $v['end_time']>$time){
                            $array5[$v['user_id']] = $v['user_id'];
                            continue;
                        }
                        if($v['card_type_id']==4 and $v['charge_type']==1 and $v['last_num']<=$card_num_remind and $v['end_time']>$time){
                            $array5[$v['user_id']] = $v['user_id'];
                            $array55[$v['user_id']] = $v['user_id']; //即将过期私教会员
                            continue;
                        }
                        if($v['card_type_id']==4 and $v['charge_type']==2 and ($v['end_time']-($card_day_remind*24*3600))<=$time and $v['end_time']>$time){
                            $array5[$v['user_id']] = $v['user_id'];
                            $array55[$v['user_id']] = $v['user_id']; //即将过期私教会员
                            continue;
                        }

                    }else{
                        $array2[$v['user_id']] = $v['user_id'];
                        if($v['card_type_id']==4) {
                            $array22[$v['user_id']] = $v['user_id']; // 过期私教会员
                        }
                        continue ;
                    }
                }
                //无卡用户
                if(($v['status']=="1" and ($v['deleted']=="1" or $v['deleted']==2 or $v['deleted']==3))
                    or is_null($v['status'])
                    or $v['status']==0
                    or ($v['status']==0 and ($v['deleted']==0 or $v['deleted']==4))
                ){
                    $array3[$v['user_id']] = $v['user_id'];
                    continue;
                }
            }
            //$array1:会员，包含即将过期会员；$array5:只包含即将过期会员
            //$array2:过期会员；$array3：非会员
            //$array11:私教会员，$array55: 私教即将过期会员
            $array4 = array_diff((array)$array1, (array)$array5);//非即将过期会员
            $array111 = array_diff((array)$array1, (array)$array11);//非私教会员
            $users = array_values(array_unique($users));
            foreach($users as $k=>$v ){
                if(in_array($v,$array11)){
                    $users_list[$k]['user_id'] = $v;
                    $users_list[$k]['user_type'] = '私教会员';
                } elseif(in_array($v,$array111)) {
                    $users_list[$k]['user_id'] = $v;
                    $users_list[$k]['user_type'] = '普通会员';
                } elseif(in_array($v,$array2)) {
                    $users_list[$k]['user_id'] = $v;
                    $users_list[$k]['user_type'] = '过期会员';
                } elseif(in_array($v,$array3)) {
                    $users_list[$k]['user_id'] = $v;
                    $users_list[$k]['user_type'] = '非会员';
                }
            }
            $user_list_new[$key] = $users_list;
            unset($users);
            unset($users_list);
        }
        return $user_list_new;
    }

    /**
     * 获取场馆剩余短信数量
     * lq 2017/11/20 add
     * @param $bus_id
     * @return mixed|string
     */
    public function  getBusSurplusSms($bus_id)
    {
        $result = $this->getMsgcenterMsginfoModel()->getBusBuySms($bus_id);
        $SurplusSms = (int)$result['sms_number'];
        if($SurplusSms < 0) {
            $SurplusSms = 0;
        }
        return $SurplusSms;
    }

    /**
     * 计算需要发送的用户ID
     * lq 2017/11/20 add
     * @param $bus_id
     * @param $send_object
     * @return array
     */
    public function getSendUids($bus_id, $send_object) {
        $uids = $this->getUids($bus_id);
        $send_object = implode(',', array_keys($send_object));
        $ordinary_uids = $private_uids = $expire_uids = [];
        if(strpos($send_object, 'ordinary') !== false) {
            $ordinary_uids = $uids['ordinary'];
            $send_object = str_replace('ordinary', '', $send_object);
        }
        if(strpos($send_object, 'private') !== false) {
            $private_uids = $uids['private'];
            $send_object = str_replace('private', '', $send_object);

        }
        if(strpos($send_object, 'expire') !== false) {
            $expire_uids = $uids['expire'];
            $send_object = str_replace('expire', '', $send_object);
        }
        $uids = array_merge((array)$ordinary_uids, (array)$private_uids, (array)$expire_uids);

        $send_object = array_map(function ($value) {
            return (int)$value;
        }, explode(',', $send_object));
        $send_object = array_filter($send_object);
        if(!empty($send_object)) {
            foreach($send_object as $v) {
                if(!in_array($v, $uids)) {
                    array_push($uids, $v);
                }
            }
        }
        return $uids;
    }


}

