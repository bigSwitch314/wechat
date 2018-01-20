
<?php
/**
 * Created by PhpStorm.
 * User: bigSwitch
 * Date: 2017/11/10
 * Time: 16:24
 */
require_once __DIR__.'/Queue.php';

class SendSmsJob {
    /**
     * worker（守护进程）所执行的方法
     * lq 2017/11/13
     * @return bool
     */
    public function perform()
    {
        $msg_id   = $this->args['msg_id'];
        $msg      = $this->args['msg'];
        $title    = $this->args['title'];
        $uid_arr  = $this->args['user_id'];
        $uid_str  =  implode(',', $uid_arr);
        $path     = __DIR__.'/../../../';
        $php_path = '/usr/local/bin/php';

        //判断可用短信数量
        exec("cd $path && $php_path index.php MsgCenter/Resque/getSmsSurplus", $output);
        $result = json_decode($output[0], true);
        $number = count($uid_arr);
        if($result['sms_surplus'] < $number) {
            $notice = '['.date('Y-m-d H:i:s').'] 消息中心-Resque-亿美软通平台剩余短信数量不足'.$result['sms_surplus'].'条，请及时充值！';
            fwrite(STDOUT, $notice.PHP_EOL);
            //发送短信提醒
            unset($output);
            exec("cd $path && $php_path index.php MsgCenter/Resque/sendSms/phone/18610963991/msg/$notice ", $output);
            exec("cd $path && $php_path index.php MsgCenter/Resque/sendSms/phone/18983391264/msg/$notice ", $output);
            //将该任务写入Redis, 沉睡10分钟
            $this->args['push_again_time'] = date('Y-m-d H:i:s');
            $job = \Queue::in('default', 'SendSmsJob', $this->args);
            fwrite(STDOUT, '['.date('Y-m-d H:i:s').']  .push_job_again: '.json_encode($this->args) .'job_id: '.$job.PHP_EOL.PHP_EOL);
            sleep(600);
            return false;
        }

        //发送短信
        unset($output);
        exec("cd $path && $php_path index.php MsgCenter/Resque/getPhoneByUid/uids/$uid_str", $output);
        $result = json_decode($output[0], true);
        $user_phone = $result['data'];
        $send_fail_num = 0;
        $s_data = [];
        $msg = urlencode($msg);
        foreach($user_phone as $value) {
            unset($output);
            $phone = $value['phone'];
            exec("cd $path && $php_path index.php MsgCenter/Resque/sendSms/msg/$msg/phone/$phone", $output);
            $r = json_decode($output[0], true);
            $result = $r['send_status'][0]['code'];
            if($result != 'SUCCESS') {
                $send_fail_num++;
            }
            $s_data = [
                'receiver' => $value['user_id'],
                'receiver_type' => 3,
                'msginfo_id' => $msg_id,
                'receive_time' => time(),
                'send_status' => $result,
                'create_time' => time(),
            ];
            //将发送短信记录写入数据库
            unset($output);
            $json_data = json_encode($s_data, JSON_UNESCAPED_UNICODE);
            exec("cd $path && $php_path index.php MsgCenter/Resque/addSmsSendLog/s_data/'{$json_data}'", $output);
            $result = json_decode($output[0], true);
            if(empty($result['add_status'])) {
                $log = '['.date('Y-m-d H:i:s').'] 消息中心-RESQUE-发送短信记录写入失败！  add: '.json_encode(['s_data'=>$s_data], JSON_UNESCAPED_UNICODE).PHP_EOL;
                fwrite(STDOUT, $log);
            }
            $log_data[] = $s_data;
        }

        //若有发送失败的短信，则更新消费短信条数
        $length = mb_strlen($this->args['msg'],"UTF8");
        if($title) {
            $length = $length - 3;
        } else {
            $length = $length - 2;
        }
        $weight = ceil($length/63);
        if(!empty($send_fail_num)) {
            unset($output);
            $send_fail_num = $send_fail_num*$weight;
            exec("cd $path && $php_path index.php MsgCenter/Resque/updateMsgInfo/msg_id/$msg_id/send_fail_num/$send_fail_num", $output);
            $result = json_decode($output[0], true);
            if(empty($result['update_status'])) {
                $log = '['.date('Y-m-d H:i:s').'] 消息中心-RESQUE-消费短信条数更新失败！  update: '.json_encode([$msg_id, $send_fail_num]).PHP_EOL;
                fwrite(STDOUT, $log);
            }
        }

        $fail_info = [
            'total_num' => $number,
            'fail_num' => $send_fail_num,
            'weight' => $weight
        ];
        fwrite(STDOUT, '['.date('Y-m-d H:i:s').']  .fail_info: '.json_encode($fail_info, JSON_UNESCAPED_UNICODE).PHP_EOL.PHP_EOL);
        fwrite(STDOUT, '['.date('Y-m-d H:i:s').']  .log_data: '.json_encode($log_data, JSON_UNESCAPED_UNICODE).PHP_EOL.PHP_EOL);
        return true;
    }
}
