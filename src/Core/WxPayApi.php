<?php
namespace tingyu\WeixinPay\Core;
use tingyu\WeixinPay\Core\Database\WxPayBizPayUrl;
use tingyu\WeixinPay\Core\Database\WxPayCloseOrder;
use tingyu\WeixinPay\Core\Database\WxPayDownloadBill;
use tingyu\WeixinPay\Core\Database\WxPayMicroPay;
use tingyu\WeixinPay\Core\Database\WxPayNotifyResults;
use tingyu\WeixinPay\Core\Database\WxPayOrderQuery;
use tingyu\WeixinPay\Core\Database\WxPayRefund;
use tingyu\WeixinPay\Core\Database\WxPayRefundQuery;
use tingyu\WeixinPay\Core\Database\WxPayResults;
use tingyu\WeixinPay\Core\Database\WxPayReport;
use tingyu\WeixinPay\Core\Database\WxPayReverse;
use tingyu\WeixinPay\Core\Database\WxPayShortUrl;
use tingyu\WeixinPay\Core\Database\WxPayUnifiedOrder;

/**
 * 
 * 接口访问类，包含所有微信支付API列表的封装，类中方法为static方法，
 * 每个接口有默认超时时间（除提交被扫支付为10s，上报超时时间为1s外，其他均为6s）
 * @author widyhu
 *
 */
class WxPayApi
{
	/**
	 * SDK版本号
	 * @var string
	 */
	public static $VERSION = "3.0.10";

	/**
	 * 
	 * 统一下单，WxPayUnifiedOrder中out_trade_no、body、total_fee、trade_type必填
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayConfig $config  配置对象
	 * @param WxPayUnifiedOrder $inputObj
	 * @param int $timeOut
	 * @throws \Exception
	 * @return array  成功时返回，其他抛异常
	 */
	public static function unifiedOrder(WxPayConfig $config, WxPayUnifiedOrder $inputObj, $timeOut = 6)
	{
		$url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
		//检测必填参数
		if(!$inputObj->isOutTradeNoSet()) {
			throw new \Exception("缺少统一支付接口必填参数out_trade_no！");
		}else if(!$inputObj->isBodySet()){
			throw new \Exception("缺少统一支付接口必填参数body！");
		}else if(!$inputObj->isTotalFeeSet()) {
			throw new \Exception("缺少统一支付接口必填参数total_fee！");
		}else if(!$inputObj->isTradeTypeSet()) {
			throw new \Exception("缺少统一支付接口必填参数trade_type！");
		}
		
		//关联参数
		if($inputObj->getTradeType() == "JSAPI" && !$inputObj->isOpenidSet()){
			throw new \Exception("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！");
		}
		if($inputObj->getTradeType() == "NATIVE" && !$inputObj->isProductIdSet()){
			throw new \Exception("统一支付接口中，缺少必填参数product_id！trade_type为JSAPI时，product_id为必填参数！");
		}
		
		//异步通知url未设置，则使用配置文件中的url
		if(!$inputObj->isNotifyUrlSet() && $config->getNotifyUrl() != ""){
			$inputObj->setNotifyUrl($config->getNotifyUrl());//异步通知url
		}
		
		$inputObj->setAppid($config->getAppId());//公众账号ID
		$inputObj->setMchId($config->getMerchantId());//商户号
		$inputObj->SetSpbillCreateip($_SERVER['REMOTE_ADDR']);//终端ip
		$inputObj->setNonceStr(self::getNonceStr());//随机字符串
		
		//签名
		$inputObj->setSign($config);
		$xml = $inputObj->ToXml();
		
		$startTimeStamp = self::getMillisecond();//请求开始时间
		$response = self::postXmlCurl($config, $xml, $url, false, $timeOut);
		$result = WxPayResults::init($config, $response);
//		todo self::reportCostTime($config, $url, $startTimeStamp, $result); //暂时取消 上报请求花费时间
		
		return $result;
	}
	
	/**
	 * 
	 * 查询订单，WxPayOrderQuery中out_trade_no、transaction_id至少填一个
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayConfig $config  配置对象
	 * @param WxPayOrderQuery $inputObj
	 * @param int $timeOut
	 * @throws \Exception
	 * @return array 成功时返回，其他抛异常
	 */
	public static function orderQuery(WxPayConfig $config, WxPayOrderQuery $inputObj, $timeOut = 6)
	{
		$url = "https://api.mch.weixin.qq.com/pay/orderquery";
		//检测必填参数
		if(!$inputObj->isOutTradeNoSet() && !$inputObj->isTransactionIdSet()) {
			throw new \Exception("订单查询接口中，out_trade_no、transaction_id至少填一个！");
		}
		$inputObj->setAppid($config->getAppId());//公众账号ID
		$inputObj->setMchId($config->getMerchantId());//商户号
		$inputObj->setNonceStr(self::getNonceStr());//随机字符串
		
		$inputObj->setSign($config);//签名
		$xml = $inputObj->toXml();
		
		$startTimeStamp = self::getMillisecond();//请求开始时间
		$response = self::postXmlCurl($config, $xml, $url, false, $timeOut);
		$result = WxPayResults::init($config, $response);
//		self::reportCostTime($config, $url, $startTimeStamp, $result);//上报请求花费时间
		
		return $result;
	}
	
	/**
	 * 
	 * 关闭订单，WxPayCloseOrder中out_trade_no必填
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayConfig $config  配置对象
	 * @param WxPayCloseOrder $inputObj
	 * @param int $timeOut
	 * @throws \Exception
	 * @return array 成功时返回，其他抛异常
	 */
	public static function closeOrder(WxPayConfig $config, WxPayCloseOrder $inputObj, $timeOut = 6)
	{
		$url = "https://api.mch.weixin.qq.com/pay/closeorder";
		//检测必填参数
		if(!$inputObj->isOutTradeNoSet()) {
			throw new \Exception("订单查询接口中，out_trade_no必填！");
		}
		$inputObj->setAppid($config->GetAppId());//公众账号ID
		$inputObj->setMchId($config->GetMerchantId());//商户号
		$inputObj->setNonceStr(self::getNonceStr());//随机字符串
		
		$inputObj->setSign($config);//签名
		$xml = $inputObj->toXml();
		
		$startTimeStamp = self::getMillisecond();//请求开始时间
		$response = self::postXmlCurl($config, $xml, $url, false, $timeOut);
		$result = WxPayResults::init($config, $response);
//		self::reportCostTime($config, $url, $startTimeStamp, $result);//上报请求花费时间
		
		return $result;
	}

	/**
	 * 
	 * 申请退款，WxPayRefund中out_trade_no、transaction_id至少填一个且
	 * out_refund_no、total_fee、refund_fee、op_user_id为必填参数
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayConfig $config  配置对象
	 * @param WxPayRefund $inputObj
	 * @param int $timeOut
	 * @throws \Exception
	 * @return array 成功时返回，其他抛异常
	 */
	public static function refund(WxPayConfig $config, WxPayRefund $inputObj, $timeOut = 6)
	{
		$url = "https://api.mch.weixin.qq.com/secapi/pay/refund";
		//检测必填参数
		if(!$inputObj->isOutTradeNoSet() && !$inputObj->isTransactionIdSet()) {
			throw new \Exception("退款申请接口中，out_trade_no、transaction_id至少填一个！");
		}else if(!$inputObj->isOutRefundNoSet()){
			throw new \Exception("退款申请接口中，缺少必填参数out_refund_no！");
		}else if(!$inputObj->isTotalFeeSet()){
			throw new \Exception("退款申请接口中，缺少必填参数total_fee！");
		}else if(!$inputObj->isRefundFeeSet()){
			throw new \Exception("退款申请接口中，缺少必填参数refund_fee！");
		}else if(!$inputObj->isOpUserIdSet()){
			throw new \Exception("退款申请接口中，缺少必填参数op_user_id！");
		}
		$inputObj->setAppid($config->getAppId());//公众账号ID
		$inputObj->setMchId($config->getMerchantId());//商户号
		$inputObj->setNonceStr(self::getNonceStr());//随机字符串
		
		$inputObj->setSign($config);//签名
		$xml = $inputObj->toXml();
		$startTimeStamp = self::getMillisecond();//请求开始时间
		$response = self::postXmlCurl($config, $xml, $url, true, $timeOut);
		$result = WxPayResults::init($config, $response);
//		self::reportCostTime($config, $url, $startTimeStamp, $result);//上报请求花费时间
		
		return $result;
	}
	
	/**
	 * 
	 * 查询退款
	 * 提交退款申请后，通过调用该接口查询退款状态。退款有一定延时，
	 * 用零钱支付的退款20分钟内到账，银行卡支付的退款3个工作日后重新查询退款状态。
	 * WxPayRefundQuery中out_refund_no、out_trade_no、transaction_id、refund_id四个参数必填一个
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayConfig $config  配置对象
	 * @param WxPayRefundQuery $inputObj
	 * @param int $timeOut
	 * @throws \Exception
	 * @return array 成功时返回，其他抛异常
	 */
	public static function refundQuery(WxPayConfig $config, WxPayRefundQuery $inputObj, $timeOut = 6)
	{
		$url = "https://api.mch.weixin.qq.com/pay/refundquery";
		//检测必填参数
		if(!$inputObj->isOutRefundNoSet() &&
			!$inputObj->isOutTradeNoSet() &&
			!$inputObj->isTransactionIdSet() &&
			!$inputObj->isRefundidSet()) {
			throw new \Exception("退款查询接口中，out_refund_no、out_trade_no、transaction_id、refund_id四个参数必填一个！");
		}
		$inputObj->setAppid($config->getAppId());//公众账号ID
		$inputObj->setMchId($config->getMerchantId());//商户号
		$inputObj->setNonceStr(self::getNonceStr());//随机字符串
		
		$inputObj->setSign($config);//签名
		$xml = $inputObj->ToXml();
		
		$startTimeStamp = self::getMillisecond();//请求开始时间
		$response = self::postXmlCurl($config, $xml, $url, false, $timeOut);
		$result = WxPayResults::init($config, $response);
//		self::reportCostTime($config, $url, $startTimeStamp, $result);//上报请求花费时间
		
		return $result;
	}
	
	/**
	 * 下载对账单，WxPayDownloadBill中bill_date为必填参数
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayConfig $config  配置对象
	 * @param WxPayDownloadBill $inputObj
	 * @param int $timeOut
	 * @throws \Exception
	 * @return string 成功时返回，其他抛异常
	 */
	public static function downloadBill(WxPayConfig $config, WxPayDownloadBill $inputObj, $timeOut = 6)
	{
		$url = "https://api.mch.weixin.qq.com/pay/downloadbill";
		//检测必填参数
		if(!$inputObj->isBillDateSet()) {
			throw new \Exception("对账单接口中，缺少必填参数bill_date！");
		}
		$inputObj->setAppid($config->getAppId());//公众账号ID
		$inputObj->setMchId($config->getMerchantId());//商户号
		$inputObj->setNonceStr(self::getNonceStr());//随机字符串
		
		$inputObj->setSign($config);//签名
		$xml = $inputObj->toXml();
		
		$response = self::postXmlCurl($config, $xml, $url, false, $timeOut);
		if(substr($response, 0 , 5) == "<xml>"){
			return "";
		}
		return $response;
	}
	
	/**
	 * 提交被扫支付API
	 * 收银员使用扫码设备读取微信用户刷卡授权码以后，二维码或条码信息传送至商户收银台，
	 * 由商户收银台或者商户后台调用该接口发起支付。
	 * WxPayWxPayMicroPay中body、out_trade_no、total_fee、auth_code参数必填
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayConfig $config  配置对象
	 * @param WxPayMicroPay $inputObj
	 * @param int $timeOut
	 */
	public static function micropay(WxPayConfig $config, WxPayMicroPay $inputObj, $timeOut = 10)
	{
		$url = "https://api.mch.weixin.qq.com/pay/micropay";
		//检测必填参数
		if(!$inputObj->isBodySet()) {
			throw new \Exception("提交被扫支付API接口中，缺少必填参数body！");
		} else if(!$inputObj->isOutTradeNoSet()) {
			throw new \Exception("提交被扫支付API接口中，缺少必填参数out_trade_no！");
		} else if(!$inputObj->isTotalFeeSet()) {
			throw new \Exception("提交被扫支付API接口中，缺少必填参数total_fee！");
		} else if(!$inputObj->isAuthCodeSet()) {
			throw new \Exception("提交被扫支付API接口中，缺少必填参数auth_code！");
		}
		
		$inputObj->setSpbillCreateIp($_SERVER['REMOTE_ADDR']);//终端ip
		$inputObj->setAppid($config->getAppId());//公众账号ID
		$inputObj->setMchId($config->getMerchantId());//商户号
		$inputObj->setNonceStr(self::getNonceStr());//随机字符串
		
		$inputObj->setSign($config);//签名
		$xml = $inputObj->ToXml();
		
		$startTimeStamp = self::getMillisecond();//请求开始时间
		$response = self::postXmlCurl($config, $xml, $url, false, $timeOut);
		$result = WxPayResults::init($config, $response);
//		self::reportCostTime($config, $url, $startTimeStamp, $result);//上报请求花费时间
		
		return $result;
	}
	
	/**
	 * 
	 * 撤销订单API接口，WxPayReverse中参数out_trade_no和transaction_id必须填写一个
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayConfig $config  配置对象
	 * @param WxPayReverse $inputObj
	 * @param int $timeOut
	 * @throws \Exception
	 */
	public static function reverse(WxPayConfig $config, WxPayReverse $inputObj, $timeOut = 6)
	{
		$url = "https://api.mch.weixin.qq.com/secapi/pay/reverse";
		//检测必填参数
		if(!$inputObj->isOutTradeNoSet() && !$inputObj->isTransactionIdSet()) {
			throw new \Exception("撤销订单API接口中，参数out_trade_no和transaction_id必须填写一个！");
		}
		
		$inputObj->setAppid($config->getAppId());//公众账号ID
		$inputObj->setMchId($config->getMerchantId());//商户号
		$inputObj->setNonceStr(self::getNonceStr());//随机字符串
		
		$inputObj->setSign($config);//签名
		$xml = $inputObj->toXml();
		
		$startTimeStamp = self::getMillisecond();//请求开始时间
		$response = self::postXmlCurl($config, $xml, $url, true, $timeOut);
		$result = WxPayResults::init($config, $response);
//		self::reportCostTime($config, $url, $startTimeStamp, $result);//上报请求花费时间
		
		return $result;
	}
	
	/**
	 * 
	 * 测速上报，该方法内部封装在report中，使用时请注意异常流程
	 * WxPayReport中interface_url、return_code、result_code、user_ip、execute_time_必填
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayConfig $config  配置对象
	 * @param WxPayReport $inputObj
	 * @param int $timeOut
	 * @throws \Exception
	 * @return string 成功时返回，其他抛异常
	 */
	public static function report(WxPayConfig $config, WxPayReport $inputObj, $timeOut = 1)
	{
		$url = "https://api.mch.weixin.qq.com/payitil/report";
		//检测必填参数
		if(!$inputObj->isInterfaceUrlSet()) {
			throw new \Exception("接口URL，缺少必填参数interface_url！");
		} if(!$inputObj->isReturnCodeSet()) {
			throw new \Exception("返回状态码，缺少必填参数return_code！");
		} if(!$inputObj->isResultCodeSet()) {
			throw new \Exception("业务结果，缺少必填参数result_code！");
		} if(!$inputObj->isUserIpSet()) {
			throw new \Exception("访问接口IP，缺少必填参数user_ip！");
		} if(!$inputObj->isExecuteTimeSet()) {
			throw new \Exception("接口耗时，缺少必填参数execute_time_！");
		}
		$inputObj->setAppid($config->getAppId());//公众账号ID
		$inputObj->setMchId($config->getMerchantId());//商户号
		$inputObj->setUserIp($_SERVER['REMOTE_ADDR']);//终端ip
		$inputObj->setTime(date("YmdHis"));//商户上报时间
		$inputObj->setNonceStr(self::getNonceStr());//随机字符串
		
		$inputObj->setSign($config);//签名
		$xml = $inputObj->ToXml();
		
		$startTimeStamp = self::getMillisecond();//请求开始时间
		$response = self::postXmlCurl($config, $xml, $url, false, $timeOut);
		return $response;
	}
	
	/**
	 * 
	 * 生成二维码规则,模式一生成支付二维码
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayConfig $config  配置对象
	 * @param WxPayBizPayUrl $inputObj
	 * @param int $timeOut
	 * @throws \Exception
	 * @return array 成功时返回，其他抛异常
	 */
	public static function bizpayurl(WxPayConfig $config, WxPayBizPayUrl $inputObj, $timeOut = 6)
	{
		if(!$inputObj->isProductIdSet()){
			throw new \Exception("生成二维码，缺少必填参数product_id！");
		}

		$inputObj->setAppid($config->getAppId());//公众账号ID
		$inputObj->setMchId($config->getMerchantId());//商户号
		$inputObj->setTimeStamp(time());//时间戳
		$inputObj->setNonceStr(self::getNonceStr());//随机字符串
		
		$inputObj->SetSign($config);//签名
		
		return $inputObj->getValues();
	}
	
	/**
	 * 
	 * 转换短链接
	 * 该接口主要用于扫码原生支付模式一中的二维码链接转成短链接(weixin://wxpay/s/XXXXXX)，
	 * 减小二维码数据量，提升扫描速度和精确度。
	 * appid、mchid、spbill_create_ip、nonce_str不需要填入
	 * @param WxPayConfig $config  配置对象
	 * @param WxPayShortUrl $inputObj
	 * @param int $timeOut
	 * @throws \Exception
	 * @return array 成功时返回，其他抛异常
	 */
	public static function shorturl(WxPayConfig $config, WxPayShortUrl $inputObj, $timeOut = 6)
	{
		$url = "https://api.mch.weixin.qq.com/tools/shorturl";
		//检测必填参数
		if(!$inputObj->isLongUrlSet()) {
			throw new \Exception("需要转换的URL，签名用原串，传输需URL encode！");
		}
		$inputObj->setAppid($config->getAppId());//公众账号ID
		$inputObj->setMchId($config->getMerchantId());//商户号
		$inputObj->setNonceStr(self::getNonceStr());//随机字符串
		
		$inputObj->setSign($config);//签名
		$xml = $inputObj->toXml();
		
		$startTimeStamp = self::getMillisecond();//请求开始时间
		$response = self::postXmlCurl($config, $xml, $url, false, $timeOut);
		$result = WxPayResults::init($config, $response);
//		self::reportCostTime($config, $url, $startTimeStamp, $result);//上报请求花费时间
		
		return $result;
	}
	
 	/**
 	 * 支付结果通用通知, 获取微信异步回调的值
     * @param WxPayConfig $config 支付配置
 	 * @param mixed $callback function, [obj, function]异步回调函数
     * @param string $msg 引用变量，可获取到错误提示值
     * @return bool|string false：回调内容空，烟钱失败
 	 */
	public static function notify($config, $callback, &$msg)
	{
		//获取通知的数据
		$xml = file_get_contents("php://input");
		if (empty($xml)) {
			# 如果没有数据，直接返回失败
			return false;
		}

		//如果返回成功则验证签名
		try {
			$result = WxPayNotifyResults::init($config, $xml);   // return WxpayNotifyResults obj, 其属性values保存解析后的array值
		} catch (\Exception $e){
			$msg = $e->getMessage();
			return false;
		}

		// 回调用户定义逻辑处理方法
		return call_user_func($callback, $result->getValues(), $xml, 'ok');
	}
	
	/**
	 * 产生随机字符串，不长于32位
	 * @param int $length
	 * @return string 产生的随机字符串
	 */
	public static function getNonceStr($length = 32) 
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {  
			$str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
		} 
		return $str;
	}
	
	/**
	 * 直接输出xml
	 * @param string $xml
	 */
	public static function replyNotify($xml)
	{
		echo $xml;
	}
	
	/**
	 * 
	 * 上报数据， 上报的时候将屏蔽所有异常流程
	 * @param WxPayConfig $config  配置对象
	 * @param string $usrl
	 * @param int $startTimeStamp
	 * @param array $data
	 */
	private static function reportCostTime(WxPayConfig $config, $url, $startTimeStamp, $data)
	{
		//如果不需要上报数据
		$reportLevenl = $config->getReportLevenl();
		if($reportLevenl == 0){
			return;
		} 
		//如果仅失败上报
		if($reportLevenl == 1 &&
			 array_key_exists("return_code", $data) &&
			 $data["return_code"] == "SUCCESS" &&
			 array_key_exists("result_code", $data) &&
			 $data["result_code"] == "SUCCESS")
		 {
		 	return;
		 }
		 
		//上报逻辑
		$endTimeStamp = self::getMillisecond();
		$objInput = new WxPayReport();
		$objInput->setInterfaceUrl($url);
		$objInput->setExecuteTime($endTimeStamp - $startTimeStamp);
		//返回状态码
		if(array_key_exists("return_code", $data)){
			$objInput->setReturnCode($data["return_code"]);
		}
		//返回信息
		if(array_key_exists("return_msg", $data)){
			$objInput->setReturnMsg($data["return_msg"]);
		}
		//业务结果
		if(array_key_exists("result_code", $data)){
			$objInput->setResultCode($data["result_code"]);
		}
		//错误代码
		if(array_key_exists("err_code", $data)){
			$objInput->setErrCode($data["err_code"]);
		}
		//错误代码描述
		if(array_key_exists("err_code_des", $data)){
			$objInput->setErrCodeDes($data["err_code_des"]);
		}
		//商户订单号
		if(array_key_exists("out_trade_no", $data)){
			$objInput->setOutTradeNo($data["out_trade_no"]);
		}
		//设备号
		if(array_key_exists("device_info", $data)){
			$objInput->setDeviceInfo($data["device_info"]);
		}
		
		try{
			self::report($config, $objInput);
		} catch (\Exception $e){
			//不做任何处理
		}
	}

	/**
	 * 以post方式提交xml到对应的接口url
	 * 
	 * @param WxPayConfig $config  配置对象
	 * @param string $xml  需要post的xml数据
	 * @param string $url  url
	 * @param bool $useCert 是否需要证书，默认不需要
	 * @param int $second   url执行超时时间，默认30s
	 * @throws \Exception
	 */
	private static function postXmlCurl(WxPayConfig $wxPayConfig, $xml, $url, $useCert = false, $second = 30)
	{		
		$ch = curl_init();
		$curlVersion = curl_version();
		$ua = "WXPaySDK/".self::$VERSION." (".PHP_OS.") PHP/".PHP_VERSION." CURL/".$curlVersion['version']." "
		.$wxPayConfig->getMerchantId();

		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, $second);

		$proxyHost = "0.0.0.0";
		$proxyPort = 0;
        $wxPayConfig->getProxy($proxyHost, $proxyPort);
		//如果有配置代理这里就设置代理
		if($proxyHost != "0.0.0.0" && $proxyPort != 0){
			curl_setopt($ch,CURLOPT_PROXY, $proxyHost);
			curl_setopt($ch,CURLOPT_PROXYPORT, $proxyPort);
		}
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
		curl_setopt($ch,CURLOPT_USERAGENT, $ua);
        // 设置请求header
        $headers = [];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		//设置输出header
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		//要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	
		if($useCert == true){
			//设置证书
			//使用证书：cert 与 key 分别属于两个.pem文件
			//证书文件请放入服务器的非web目录下
			$sslCertPath = "";
			$sslKeyPath = "";
            $wxPayConfig->getSSLCertPath($sslCertPath, $sslKeyPath);
			curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
			curl_setopt($ch,CURLOPT_SSLCERT, $sslCertPath);
			curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
			curl_setopt($ch,CURLOPT_SSLKEY, $sslKeyPath);
		}
		//post提交方式
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		//运行curl
		$data = curl_exec($ch);
		//返回结果
		if($data){
			curl_close($ch);
			return $data;
		} else { 
			$error = curl_errno($ch);
			curl_close($ch);
			throw new \Exception("curl出错，错误码:$error");
		}
	}
	
	/**
	 * 获取毫秒级别的时间戳
	 */
	private static function getMillisecond()
	{
		//获取毫秒的时间戳
		$time = explode ( " ", microtime () );
		$time = $time[1] . ($time[0] * 1000);
		$time2 = explode( ".", $time );
		$time = $time2[0];
		return $time;
	}
}

