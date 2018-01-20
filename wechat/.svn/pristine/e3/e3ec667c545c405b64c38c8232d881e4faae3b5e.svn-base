<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/16 0016
 * Time: 上午 10:34
 */
namespace MsgCenter\Model;

use Think\Model;

class MsgcenterSmspackageModel extends Model
{
    protected $tableName = 'msgcenter_smspackage'; // 定义表名

    public function get_smspackage($id){
        $map['delete'] = 0;
        if(!empty($id)){
            $map['id'] = $id;
        }
        return $this->where($map)
                    ->field('id,number,amount')
                    ->order('number asc')
                    ->select();
    }

}