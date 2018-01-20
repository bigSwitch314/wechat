<?php
/* =============================================================================
 * Author: qinyongbo - 408964446@qq.com
 * QQ : 408964446
 * Last modified: 2016-07-06 16:03
 * Filename: Booking.class.php
 * Description: 
 * @copyright    Copyright (c) 2016, rocketbird.cn
=============================================================================*/
namespace MsgCenter\Model;

use Think\Model;

class MsgcenterMsginfoModel extends Model
{
    protected $tableName = 'msgcenter_msginfo'; // 定义表名

    /**
     * 添加发送消息
     * lq 2017/11/08 add
     * @param $s_data
     * @return mixed
     */
    public function addMsg($s_data)
    {
        return $this->add($s_data);
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
        $map['mi.bus_id'] = $bus_id;
        //$map['mi.send_status'] = 1;
        if(!empty($keyword)) {
            $map['mi.title'] = ['like','%'.$keyword.'%'];
        }
        if(!empty($begin_date) && !empty($end_date)) {
            $begin_time = strtotime($begin_date);
            $end_time = strtotime($end_date.' 23:59:59');
            $map['mi.create_time'] = ['between', [$begin_time, $end_time]];
        }

        $count = $this->table('rb_msgcenter_msginfo')
                      ->alias('mi')
                      ->where($map)
                      ->count();
        $list  = $this->table('rb_msgcenter_msginfo')
                      ->alias('mi')
                      ->field('mi.id as msg_id, mi.create_time, mi.notice_way, mi.title, mi.content, mi.send_object, mi.sender_name as sender')
                      ->where($map)
                      ->page($page_no, $page_size)
                      ->order('mi.create_time desc')
                      ->select();
        return ['count'=>$count, 'list'=>$list];

    }

    /**
     * 获取场馆发消息通知详情
     * lq 2017/11/09 add
     * @param $msg_id
     * @return mixed
     */
    public function getMsgInfo($msg_id)
    {
        return $this->table('rb_msgcenter_msginfo')
                    ->field('id, bus_id, sms_num, create_time, notice_way, title, content, send_object')
                    ->where(['id'=>$msg_id])
                    ->find();
    }

    /**
     * 查询场馆会员
     * lq 2017/11/09 add
     * @param $bus_id
     * @param $keyword
     * @return mixed
     */
    public function searchMember($bus_id, $keyword, $user_id)
    {
        $map['bus_id'] = $bus_id;
        $map['is_disable'] = 1;
        if(!empty($keyword)) {
            $map['username|phone|card_sn'] = ['like',"%$keyword%"];
        }
        if(!empty($user_id)) {
            $map['user_id'] = $user_id;
        }
        return $this->table('rb_user_bus')
            ->field('user_id, username, phone')
            ->where($map)
            ->select();
    }

    /**
     * PC首页账号详细
     * lq 2017/11/09 add
     * @param $bus_id
     * @return mixed
     */
    public function getAccountDetails($bus_id)
    {
        $map['bu.id'] = $bus_id;
        return M('Business')
            ->alias('bu')
            ->field("bu.thumb as logo, r.name as version, bu.edition as version_id, bu.version_expire_time as expire_date, bu.sms_number")
            ->join("LEFT JOIN rb_role r on r.id=bu.edition")
            ->where($map)
            ->find();
    }

    /**
     * 根据用户ID获取电话
     * lq 2017/11/13 add
     * @param $uids
     * @return mixed
     */
    public function getPhoneByUid($uids)
    {
        $map['user_id'] = ['in', $uids];
        return $this->table('rb_user_bus')
            ->field('user_id, phone')
            ->where($map)
            ->select();
    }

    /**
     * 更新场馆发消息通知详情
     * lq 2017/11/13 add
     * @param $where
     * @param $update
     * @return mixed
     */
    public function updateMsgInfo($where, $update)
    {
        return $this->table('rb_msgcenter_msginfo')->where($where)->save($update);
    }

    /**
     * 将发送短信记录写入数据库
     * lq 2017/11/13 add
     * @param $s_data
     * @return mixed
     */
    public function addSmsSendLog($s_data)
    {
        return M('msgcenter_sendlog')->addall($s_data);
    }

    /**
     * 判断用户类型
     * LQ 2017/11/15 add
     * @param $bus_id
     * @return array
     */
    public function getUids($bus_id)
    {
        // 客户会员卡
        $map['ub.bus_id'] = $bus_id;
        $map['ub.is_disable'] = 1;
        $user_cardinfo = M('UserBus')
            ->alias('ub')
            ->field('ub.user_id,c.card_type_id,cu.card_id,cu.balance,cu.id,cu.marketers_id,ub.marketers_id as ub_ms_id,uc.status,
            cu.all_num,cu.suspend_time,cu.all_days,cu.last_num,cu.active_time,cu.end_time,cu.charge_type,cu.deleted')
            ->join("LEFT JOIN rb_user_card uc on uc.user_id=ub.user_id AND uc.bus_id=ub.bus_id")
            ->join("LEFT JOIN rb_card_user cu on cu.id=uc.card_user_id")
            ->join("LEFT JOIN rb_card c on c.id=cu.card_id")
            ->order('cu.marketers_id')
            ->where($map)
            ->select();
        //会籍个人设置(卡相关)
        $where['bus_id'] = $bus_id;
        $marketer_setting = M('marketers_personal_setting')
            ->field('marketers_id,card_day_remind,card_num_remind,card_value_remind')
            ->where($where)
            ->select();
        //返回查询结果
        return $data = [
            'user_cardinfo' => $user_cardinfo,
            'marketer_setting' => $marketer_setting,
        ];
    }

    /**
     * 获取场馆消费的短信条数
     * @param $bus_id
     * @return mixed
     */
    public function getBusConsumeSms($bus_id)
    {
        $map['bus_id'] = $bus_id;
        $map['notice_way'] = 2; //通知方式（1.模板消息，2.短信）
        return M('MsgcenterMsginfo')
               ->field("sms_num")
               ->where($map)
               ->select();
    }

    /**
     * 获取场馆购买的短信条数
     * @param $bus_id
     * @return mixed
     */
    public function getBusBuySms($bus_id)
    {
        $map['id'] = $bus_id;
        return M('Business')
            ->field("sms_number")
            ->where($map)
            ->find();
    }

}

