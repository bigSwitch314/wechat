<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/16 0016
 * Time: 上午 10:56
 */

namespace MsgCenter\Model;

use Think\Model;

class MsgcenterSmsorderModel extends Model
{
    protected $tableName = 'msgcenter_smsorder'; // 定义表名

    /**
     * dj 创建短信包 订单
     * @param $smspackage_id 短信包ID
     * @param $bus_id 场馆ID
     * @param $amount 金额
     * @param $order_sn 订单号
     * @param $remark 备注
     * @return mixed
     */
    public function add_smsorder($sms_package_id,$bus_id,$amount,$order_sn,$remark,$sms_number){
        //判断订单是否存在
        $info = $this->exist_smsorder($sms_package_id, $bus_id);
        if(!empty($info)){
            return $info;
        }
        $data['sms_package_id'] = $sms_package_id;
        $data['bus_id'] = $bus_id;
        $data['amount'] = $amount;
        $data['sms_number'] = $sms_number;
        $data['order_sn'] = $order_sn;
        $data['remark'] = $remark;
        $data['create_time'] = time();
        $id = $this->add($data);
        $map['id'] = $id;
        $info = $this->where($map)->find();
        return $info;

    }

    /**
     * dj 2017-11-15 add
     * 获取订单详细
     * @param $out_trade_no 订单号
     * @return mixed
     */
    public function get_smsorder_info($out_trade_no){
        $map['order_sn'] = $out_trade_no;
        $info = $this->where($map)->find();
        return $info;
    }

    /**
     * dj 2017-11-16 add
     * 跟新订单状态
     * @param $out_trade_no 订单号
     * @param $pay_status 支付状态
     * @return mixed
     * @throws \Exception
     */
    public function update_smsorder_paystatus($out_trade_no, $pay_status){
        //先修改定金订单信息
        $data['pay_status'] = $pay_status;
        $data['edit_time'] = time();
        $map['order_sn'] = $out_trade_no;
        $save = $this->where($map)->save($data);
        if($save===false){
            throw new \Exception('修改支付状态失败！',SAVE_FAIL);
        }
        $info = $this->where($map)->find();
        return $info;

    }

    /**
     * dj 2017-11-16 add
     * 跟新订单状态
     * @param $out_trade_no 订单号
     * @param $pay_status 支付状态
     * @return mixed
     * @throws \Exception
     */
    public function update_smsorder_status($out_trade_no,$status){
        //先修改定金订单信息
        $data['status'] = $status;
        $data['edit_time'] = time();
        $map['order_sn'] = $out_trade_no;
        $save = $this->where($map)->save($data);
        if($save===false){
            throw new \Exception('修改支付状态失败！',SAVE_FAIL);
        }
        $info = $this->where($map)->find();
        return $info;

    }

    /**
     * dj 2017-11-16 add
     * 跟新订单状态
     * @param $out_trade_no 订单号
     * @param $pay_status 支付状态
     * @return mixed
     * @throws \Exception
     */
    public function update_smsorder_pay_status($out_trade_no,$pay_status,$status){
        //先修改定金订单信息
        $data['pay_status'] = $pay_status;
        $data['status'] = $status;
        $data['edit_time'] = time();
        $data['deleted'] = 0;
        $map['order_sn'] = $out_trade_no;
        $save = $this->where($map)->save($data);
        if($save===false){
            throw new \Exception('修改支付状态失败！',SAVE_FAIL);
        }
        $info = $this->where($map)->find();
        return $info;

    }

    /**
     * dj 订单详细
     * @param $smspackage_id
     * @param $bus_id
     */
    public function get_smsorder_info_spid($order_sn){
        $map['order_sn'] = $order_sn;
        $info = $this->where($map)->field('*')->find();
        return $info;
    }

    /**
     * 判断订单是否存在
     * @param $smspackage_id
     * @param $bus_id
     */
    public function exist_smsorder($sms_package_id,$bus_id){
        $map['sms_package_id'] = $sms_package_id;
        $map['bus_id'] = $bus_id;
        $map['pay_status'] = 0;
        $map['deleted'] = 0;
        //10分
        $expiry = time()-60*60*2;
        $map['create_time'] = ['lt',$expiry];
        //删除
       $info = $this->where($map)->field('*')->find();
       if(!empty($info)){
           $save_map['id'] = $info['id'];
           $save = $this->where($save_map)->setField('deleted',1);
           if($save === false){
               throw new \Exception('订单创建失败！',ORDER_ERROR);
           }
       }
       //没有订单
        $map2['sms_package_id'] = $sms_package_id;
        $map2['bus_id'] = $bus_id;
        $map2['pay_status'] = 0;
        $map2['deleted'] = 0;
        //10分
        $expiry = time()-600;
        $map2['create_time'] = ['gt',$expiry];
        //删除
        $info = $this->where($map2)->field('*')->find();
        return $info;
    }
}
