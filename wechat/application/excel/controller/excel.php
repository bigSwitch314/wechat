<?php
// +----------------------------------------------------------------------
// | Description: 调试测试
// +----------------------------------------------------------------------
// | Author: linchuangbin <linchuangbin@honraytech.com>
// +----------------------------------------------------------------------

namespace app\web\controller;
use think\Db;
use think\Cache;
use think\Request;
use app\web\controller\ApiCommon;

class Excel extends ApiCommon{
    /**
     * 场馆数据excel导入
     * @return \think\response\Json
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \think\Exception
     */
    public function bus_import()
    {
        //获取上传文件
        $file = request()->file('file');
        if(empty($file)){
            return json([
                'errorcode' => NOT_CHOOSE_EXCEL_FAIL,
                'errormsg'  => '请选择excel文件！'
            ]);
        }
        //文件格式验证
        $info = $file->validate(['ext' => 'xls,xlsx'])->move(ROOT_PATH . 'public' . DS . 'uploads');//上传验证后缀名,以及上传之后移动的地址
        if ($info) {
            $exclePath = $info->getSaveName();  //获取文件名
            $file_name = ROOT_PATH . 'public' . DS . 'uploads' . DS . $exclePath;   //上传文件的地址
            $ext = end(explode('.', $file_name));
            if($ext=='xls'){
                $reader = \PHPExcel_IOFactory::createReader('Excel5');
            }elseif($ext=='xlsx'){
                $reader = \PHPExcel_IOFactory::createReader('Excel2007');
            }
            // 载入excel文件
            $PHPExcel = $reader->load($file_name);
            $excel_array=$PHPExcel->getsheet(0)->toArray();   //转换为数组格式
            array_shift($excel_array);  //删除第一个数组(标题);
            $total_count = count($excel_array);//总记录条数

            //数据字段
            $data = [];
            foreach($excel_array as $k=>$v) {
                $data[$k]['bus_id'] = $v[0];
                $data[$k]['name'] = $v[1];
                $data[$k]['mer_id'] = $v[2];
                $data[$k]['mer_name'] = $v[3];
                $data[$k]['social_credit_code'] = $v[4];
                $data[$k]['charge_version'] = $v[5] ;
                $data[$k]['brand'] = $v[6];
                $data[$k]['use_status'] = $v[7];
                $data[$k]['sign_status'] = $v[8];
                $data[$k]['sign_time'] = $v[9];
            }

            //数据验证
            $error_notice = '';
            $error_data = [];
            $correct_data = [];
            foreach($data as $k=>$v){
                //场馆ID验证
                if(empty($v['bus_id']) || !is_numeric($v['bus_id'])){
                    $error_notice .= '场馆ID错误；';
                }
                //验证统一社会信用码
//                if(!empty($v['social_credit_code']) && !is_string($v['social_credit_code'])){
//                    $error_notice .= '注册码格式错误；';
//                }
                //验证收费版本
                if(!empty($v['charge_version']) && (!is_numeric($v['charge_version']) || !in_array($v['charge_version'], [1,2]))) {  //不为空
                    $error_notice .= '收费版本数据错误；';
                }
                //验证品牌
                if(empty($v['brand']) && $v['brand']===(float)0) {  //为空且不恒等于0
                    $error_notice .= '品牌数据错误；';
                }
                if(!empty($v['brand']) && (!is_numeric($v['brand']) || !in_array($v['brand'], [1,2]))) {  //不为空
                    $error_notice .= '品牌数据错误；';
                }
                //验证使用状态
                if(!empty($v['use_status']) && (!is_numeric($v['use_status']) || $v['use_status'] !=1)) {  //不为空
                    $error_notice .= '使用状态错误；';
                }
                //验证签约状态
                if(!empty($v['sign_status']) && (!is_numeric($v['sign_status']) || $v['sign_status'] !=1)) {  //不为空
                    $error_notice .= '签约状态数据错误；';
                }
                //验证签约时间
                if(!empty($v['sign_time']) && !preg_match("#^\d{4}/\d{1,2}/\d{1,2}$#",$v['sign_time'])){
                    $error_notice .= '签约时间格式错误；';
                }

                //筛选数据
                if(!empty($error_notice)){
                    $error_data[$k] = $data[$k];
                    $error_data[$k]['error_notice'] = trim($error_notice,'；');
                    unset($error_notice);
                }else{
                    if(!empty($v['sign_time'])) {
                        $data[$k]['sign_time'] = strtotime($v['sign_time']);
                    }
                    foreach ($data[$k] as $key=>$val) {
                        if($val === null) {
                            unset($data[$k][$key]);
                        }
                    }
                    $correct_data[$k] = $data[$k];
                }
            }
            //数据插入表
            foreach($correct_data as $k=>$v){
                $where['bus_id'] = $v['bus_id'];
                $res = Db::table('rbbo_business')->where($where)->find();
                if(!empty($res)){
                    unset($v['bus_id']);
                    unset($v['name']);
                    unset($v['mer_id']);
                    unset($v['mer_name']);
                    $r = Db::table('rbbo_business')->where($where)->update($v);
                    if($r === false){
                        $correct_data[$k]['sign_time'] = date('Y-m-d', $correct_data[$k]['sign_time']);
                        $correct_data[$k]['error_notice'] = '场馆更新失败';
                        array_push($error_data, $correct_data[$k]);
                    }
                }else{
                    $correct_data[$k]['sign_time'] = $correct_data[$k]['sign_time']? date('Y-m-d', $correct_data[$k]['sign_time']): '';
                    $correct_data[$k]['error_notice'] = '场馆id不存在';
                    array_push($error_data, $correct_data[$k]);
                }
            }
            // 缓存错误的记录
            if(!empty($error_data)){
                $file_name = randStr(5);
                Cache::store('file')->set($file_name,json_encode($error_data),300);
                // 下载失败列表url
                $request = Request::instance();
                $domain = $request->domain();
                $url = $domain.'/web/excel/bus_import_fail_list?file_name='.$file_name;
            }

            //返回结果
            $r_data =[
                'count'   => $total_count,
                'success' => $total_count - count($error_data),
                'fail'    => count($error_data),
                'url'     => !empty($url)? $url: '',
            ];
            return json([
                'errorcode' => SUCCESS,
                'data'      => $r_data,
            ]);
        } else {
            return json([
                'errorcode' => FILE_FORMAT_ERROR,
                'errormsg'  => $file->getError(),
            ]);
        }
    }

    /**
     * 场馆excel模板下载
     */
    public function bus_templet_download(){
        $file_url = APP_PATH.'../public/downloads/场馆数据导入模板.xlsx';
        $new_name ='场馆数据导入模板.xlsx';
        $file_name=basename($file_url);
        $file_name=trim($new_name=='')? $file_name: urlencode($new_name);
        $file=fopen($file_url,'r'); //打开文件
        //输入文件标签
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: ".filesize($file_url));
        header("Content-Disposition: attachment; filename=".$file_name);
        //输出文件内容
        echo fread($file,filesize($file_url));
        fclose($file);
    }

    /**
     * 场馆导入失败列表excel下载
     */
    public function bus_import_fail_list(){
        $name = $this->param['file_name'];
        $list = json_decode(Cache::store('file')->get($name),true);
        $file_name = '场馆错误数据导出';
        $title =['场馆id','场馆名称','商家id','商家名称','统一社会信用码','收费版本','品牌','使用状态','签约状态','签约时间','错误提示'];
        $this->excel($list,$title,$file_name);
    }




    function excel($data=array(),$title=array(),$filename='report'){
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Content-type:application/vnd.ms-<excel></excel>");
        header("Content-Disposition:attachment;filename=".$filename.".xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        //导出xls 开始
        if (!empty($title)){
            foreach ($title as $k => $v) {
                $title[$k]=iconv("UTF-8", "GBK",$v);
            }
            $title= implode("\t", $title);
            echo "$title\n";
        }
        if (!empty($data)){
            foreach($data as $key=>$val){
                foreach ($val as $ck => $cv) {
                    $data[$key][$ck]=iconv("UTF-8", "GBK", "\"$cv\"");
                }
                $data[$key]=implode("\t", $data[$key]);
            }
            echo implode("\n",$data);
        }
    }
}
