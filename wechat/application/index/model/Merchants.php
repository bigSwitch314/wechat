<?php
namespace app\index\model;

use think\Model;


class Merchants extends Model
{
    protected $table = 'rb_merchants';

    public function getList(){
       return $this->alias('ub')->limit(10)->select();
    }
}
