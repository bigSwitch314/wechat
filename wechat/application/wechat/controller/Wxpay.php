<?php
namespace app\mobile\controller;

use app\common\model\CourseSchedule as CourseScheduleModel;
use app\common\service\CourseMark as CourseMarkService;
use app\common\controller\Common as CommonController;
use app\common\service\PtSchedule as PtScheduleService;
use app\common\service\Course as CourseService;
use app\common\service\UserBus as UserBusService;
use app\common\model\Business as BusinessModel;
use app\common\model\ClassMark as ClassMarkModel;
use app\common\model\CardOrderInfo as CardOrderInfoModel;
use app\common\model\Merchants as MerchantsModel;
use app\common\tools\RedisClient;
use think\Request;
use think\Config;
use think\Db;

require_once __DIR__.'/../../../extend/Wxpay/lib/WxPay.Api.php';
require_once __DIR__.'/../../../extend/Wxpay/lib/WxPay.Data.php';


class Wxpay extends CommonController
{
	/**
	 * 添加预约团课
	 */
	public function addMark()
	{
		try {
			$param              = $this->param;
			$bus_id             = auth_decode($param['bus_id']); //场馆id
			$user_id            = auth_decode($param['user_id']); //用户id
			$course_schedule_id = $param['course_schedule_id']; //排课表id
			$num                = $param['num']; //预约人数
			$openid             = $param['openid'];
			//$openid             = 'oYvDFwPAI1wQGeaW8LpaQQTfV-5I';
			$single_purchase    = $param['single_purchase']; //是否按照新流程走
			$phone              = $param['phone'];
			$amount             = $param['amount']; //总金额
			$verification_code  = $param['verification_code']; //验证码
			$card_user_id       = $param['card_user_id'];
			$todeduct           = $param['todeduct'];

			if(empty($bus_id) || !is_numeric($bus_id) ||
			   empty($user_id) || !is_numeric($user_id) ||
			   empty($course_schedule_id) || !is_numeric($course_schedule_id) ||
			   empty($num) || !is_numeric($num))
			{
				throw new \Exception('参数错误！', PARAM_ERROR);
			}

			$CourseMarkService = new CourseMarkService();

			$ret = array("errorcode" => 0, "errormsg" => "添加预约成功");

			if ($single_purchase == 1 or $single_purchase == 2) {
				if ($single_purchase == 2 and !preg_match("/^1\d{10}$/", $phone)) {
					throw new \Exception('手机号码错误', PHONE_ERROR);
				}
				if ($single_purchase == 2 and $verification_code != get_sms_code($phone)) {
					throw new \Exception('验证码错误', AUTH_CODE_ERROR);
				}
				if (!is_numeric($amount) or 0 > $amount) {
					throw new \Exception('金额不正确', PARAM_ERROR);
				}

				$result = $CourseMarkService->single_purchase_lesson_add_mark($bus_id, $openid, $phone, $num, $amount, $course_schedule_id);

                $ret = $this->payCard($result['order_sn'], $result['bus_id'], $result['user_id']);

			} else {
				//老流程
				$CourseMarkService->deal_add_mark($card_user_id, $bus_id, $user_id, $course_schedule_id, $todeduct, $num, 1);
			}
		} catch (\Exception $e) {

			$ret = array("errorcode" => $e->getCode(), "errormsg" => $e->getMessage());
		}
		$this->ajaxReturn($ret);
	}

    /**
     *课程取消预约
     * @param Request $request
     */
    public function userCancelClassMark(Request $request)
    {
        try{
            $bus_id = auth_decode( $request->param( 'bus_id' ) );
            $class_mark_id =  $request->param( 'class_mark_id' );
            if ( empty( $bus_id ) || ! is_numeric( $bus_id ) ) {
                throw new \Exception( '参数错误', PARAM_ERROR );
            }
            if ( empty( $class_mark_id ) || ! is_numeric( $class_mark_id ) ) {
                throw new \Exception( '参数错误', PARAM_ERROR );
            }
            $CourseMarkService = new CourseMarkService();
            $is_pay_class = $CourseMarkService->isPayClass($class_mark_id, $bus_id);
            if ($is_pay_class) {
                //单次购课
                try {
                    $CourseMarkService->singlePurchaseLessonCancel($class_mark_id , $bus_id , true);
                    $r_data = [
                        'errorcode' => SUCCESS,
                        'errormsg' => "取消预约成功",
                    ];
                } catch (\Exception $e) {
                    $r_data = [
                        'errorcode' => $e->getCode(),
                        'errormsg' => $e->getMessage(),
                    ];
                }
                $this->ajaxReturn($r_data);
            } else {
                //非单次购课
                $CourseMarkService->userCancelClassMark($class_mark_id ,$bus_id);
            }
            $this->ajaxReturn([
                'errorcode' => SUCCESS,
                'errormsg'  => '取消成功!',
            ]);

        } catch(\Exception $e) {
            $this->ajaxReturn([
                'errorcode' => $e->getCode(),
                'errormsg'  => $e->getMessage()
            ]);
        }
    }

	/**
	 * 会员卡支付和单次购买生成支付参数
	 * @param $order_sn
	 * @param $bus_id
	 * @param $user_id
	 * @return array
	 * @throws \Exception
	 * @throws \WxPayException
	 */
	public function payCard($order_sn, $bus_id, $user_id)
	{
		$UserBusService = new UserBusService();
		$user = $UserBusService->get_userbus($user_id,$bus_id);
		if (empty($user)) {
			throw new \Exception('用户数据异常', ABNORMAL_ERR);
		}

		$BusinessModel =new BusinessModel();
		$bus_info = $BusinessModel->get_default_bus($bus_id);
		if (empty($bus_info)) {
			throw new \Exception('场馆信息验证失败', DB_ERROR);
		}
		$CardorderInfoModel = new CardOrderInfoModel();
		$card_order_info = $CardorderInfoModel->get_card_order_info($order_sn, $bus_id, $user_id);
		if (empty($card_order_info)){
			throw new \Exception('订单不存在', ORDER_IS_NULL);
		}
		if ($card_order_info['pay_status'] == 2) {
			throw new \Exception('该订单已支付', ORDER_PAY_OVER);
		}
		if ($card_order_info['order_status'] != 1) {
			throw new \Exception('该订单异常，不能完成支付', ORDER_EXCEPTION);
		}

		//获取小程序配置信息
		$MerchantsModel = new MerchantsModel();
		$config     = $MerchantsModel->getMerchantsAppletInfo($bus_info['m_id']);
		$appid      = $config['appid'];    //小程序appid
		$partnerid  = Config::get('mchid'); //小程序对应的商家ID
		$partnerkey = Config::get('key');   //小程序对应的商家秘钥

		//调用统一下单接口
		$input = new \WxPayUnifiedOrder();
		$input->SetAppid($appid);      //小程序appid
		$input->SetMch_id($partnerid); //商户号
		$input->SetKey($partnerkey);   //商户号
		$input->SetBody('单次购课支付！');
		$input->SetNotify_url( Config::get('notify_url').'/Mobile/Wxpaynotify/SinglePayCourse');
		$input->SetOut_trade_no($order_sn);
		$input->SetTotal_fee($card_order_info['amount'] * 100);
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + 600));
		$input->SetTrade_type("JSAPI");
		$input->SetOpenid($user['m_openid']);
		$order = \WxPayApi::unifiedOrder($input);
		if($order['return_code'] == 'FAIL') {
			throw new \Exception($order['return_msg']);
		}

		$jsApiParameters = $this->GetJsApiParameters((array)$order);
		$jsApiParameters =json_decode($jsApiParameters, true);
		$jsApiParameters['order_sn']= $order_sn;

		return[
			'errorcode' => SUCCESS,
			'errormsg'  => '获取成功!',
			'data'      => $jsApiParameters
		];
	}

	/**
	 * 获取jsapi支付的参数
	 * @param array $UnifiedOrderResult 统一支付接口返回的数据
	 * @param $UnifiedOrderResult
	 * @return string
	 * @throws \WxPayException
	 */
	public function GetJsApiParameters($UnifiedOrderResult)
	{
		if(!array_key_exists("appid", $UnifiedOrderResult)
				|| !array_key_exists("prepay_id", $UnifiedOrderResult)
				|| $UnifiedOrderResult['prepay_id'] == "")
		{
			throw new \WxPayException("参数错误");
		}

		$jsapi = new \WxPayJsApiPay();
		$jsapi->SetAppid($UnifiedOrderResult["appid"]);
		$timeStamp = time();
		$jsapi->SetTimeStamp("$timeStamp");
		$jsapi->SetNonceStr(\WxPayApi::getNonceStr());
		$jsapi->SetPackage("prepay_id=" . $UnifiedOrderResult['prepay_id']);
		$jsapi->SetKey($UnifiedOrderResult["key"]);
		$jsapi->SetSignType("MD5");
		$jsapi->SetPaySign($jsapi->MakeSign());
		$parameters = json_encode($jsapi->GetValues());

		return $parameters;
	}

	/**
	 * 获取单次购课支付状态
	 * @return string
	 */
	public function getPayCourseStatus()
	{
		try{
			$param    = $this->param;
			$bus_id   = auth_decode($param['bus_id']);
			$user_id  = auth_decode($param['user_id']);
			$order_sn = $param['order_sn'];

			if (empty($bus_id)   || !is_numeric($bus_id)  ||
				empty($user_id)  || !is_numeric($user_id) ||
				empty($order_sn))
			{
				throw  new \Exception('参数错误', PARAM_ERROR);
			}

			//查询订单状态
			$CardOrderInfoModel = new CardOrderInfoModel();
			$result = $CardOrderInfoModel->get_card_order_info($order_sn, $bus_id, $user_id);
			if(2 == $result['pay_status']) {
				$this->ajaxReturn([
						'errorcode' => SUCCESS,
						'errormsg'  => '支付成功!'
				]);
			}
			if(1 == $result['pay_status']) {
				$this->ajaxReturn([
						'errorcode' => SIGNLE_BUY_CARD_FAIL,
						'errormsg'  => '支付失败!'
				]);
			}

			$status = $this->checkPayCourseStatus($order_sn, $bus_id);
			if('SUCCESS' == $status) { //支付成功
				//查询支付回调是否添加预约成功
				$redisClient = new RedisClient();
				$data = $redisClient->get($order_sn);
				$data = json_decode($data, true);
				$course_schedule_id = $data['course_schedule_id'];

				$ClassMarkModel = new ClassMarkModel();
				$map = [
					'bus_id'  => $bus_id,
					'user_id' => $user_id,
					'status'  => ['neq', 3],
					'course_schedule_id' => $course_schedule_id
				];
				$result = $ClassMarkModel->get_map_class_mark($map);
				if(!empty($result)) {
					$this->ajaxReturn([
							'errorcode' => SUCCESS,
							'errormsg'  => '支付成功!'
					]);
				} else {
					$this->ajaxReturn([
							'errorcode' => NOTPAY,
							'errormsg'  => '未支付!'
					]);
				}

			} elseif('NOTPAY' == $status) {

				$this->ajaxReturn([
						'errorcode' => NOTPAY,
						'errormsg'  => '未支付!'
				]);

			} elseif('FAIL' == $status) {
				//更新订单状态
				$this->ajaxReturn([
						'errorcode' => SIGNLE_BUY_CARD_FAIL,
						'errormsg'  => '支付失败!'
				]);

			}

		} catch(\Exception $e) {
			Db::rollback();
			$this->ajaxReturn([
					'errorcode' => $e->getCode(),
					'errormsg'  => $e->getMessage()
			]);
		}

	}

	/**
	 * 单次购课支付状态
	 * @param $order_sn
	 * @param $bus_id
	 * @return string
	 * @throws \Exception
	 */
	public function checkPayCourseStatus($order_sn, $bus_id)
	{
		if(empty($order_sn)) {
			throw new \Exception('参数错误', PARAM_ERROR);
		}

		//获取会员端小程序配置信息
		$BusinessModel =new BusinessModel();
		$MerchantsModel = new MerchantsModel();
		$bus_info = $BusinessModel->get_default_bus($bus_id);
		$config     = $MerchantsModel->getMerchantsAppletInfo($bus_info['m_id']);
		$appid      = $config['appid'];      //小程序appid
		$partnerid  = Config::get('mchid'); //小程序对应的商家ID
		$partnerkey = Config::get('key');   //小程序对应的商家秘钥

		$out_trade_no = $order_sn;
		$input = new \WxPayOrderQuery();
		$input->SetAppid($appid);//公众账号ID
		$input->SetMch_id($partnerid);//商户号
		$input->setKey($partnerkey);
		$input->SetOut_trade_no($out_trade_no);
		$result = \WxPayApi::orderQuery($input);

		$pay_status = 'PAYERROR';
		if ( $result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS') {
			$pay_status = $result['trade_state'];
		} elseif ($result['return_code'] == 'SUCCESS') {
			$pay_status = 'FAIL';
		}
		return $pay_status;
	}

}
