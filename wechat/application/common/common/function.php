<?php
/**
 * Created by PhpStorm.
 * User: bigSwitch
 * Date: 2017/9/11
 * Time: 22:57
 */

/**
 * 获取微信操作对象（单例模式）
 * @staticvar array $wechat 静态对象缓存对象
 * @param type $type 接口名称 ( Card|Custom|Device|Extend|Media|Oauth|Pay|Receive|Script|User )
 * @return \Wehcat\WechatReceive 返回接口对接
 */
function & load_wechat($type = '') {
    static $wechat = array();
    $index = md5(strtolower($type));
    if (!isset($wechat[$index])) {
        // 定义微信公众号配置参数（这里是可以从数据库读取的哦）
        $options = array(
            'token'           => '', // 填写你设定的key
            'appid'           => '', // 填写高级调用功能的app id, 请在微信开发模式后台查询
            'appsecret'       => '', // 填写高级调用功能的密钥
            'encodingaeskey'  => '', // 填写加密用的EncodingAESKey（可选，接口传输选择加密时必需）
            'mch_id'          => '', // 微信支付，商户ID（可选）
            'partnerkey'      => '', // 微信支付，密钥（可选）
            'ssl_cer'         => '', // 微信支付，双向证书（可选，操作退款或打款时必需）
            'ssl_key'         => '', // 微信支付，双向证书（可选，操作退款或打款时必需）
            'cachepath'       => '', // 设置SDK缓存目录（可选，默认位置在Wechat/Cache下，请保证写权限）
        );
        \Wechat\Loader::config($options);
        $wechat[$index] = \Wechat\Loader::get($type);
    }
    return $wechat[$index];
}