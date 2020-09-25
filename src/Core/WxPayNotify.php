<?php
/**
 * 
 * 回调基础类
 * @author widyhu
 *
 */
namespace tingyu\WeixinPay\Core;
use tingyu\WeixinPay\Core\Database\WxPayNotifyReply;

class WxPayNotify extends WxPayNotifyReply
{
	private $config = null;

	/**
	 * 回调入口
     * 业务处理的回调方法，
     * class :: function notifyProcess($responseArr, $responseXml, &$msg)
     * {
     *    $responseArr 验签后的数组
     *    $responseXml 验签后的xml
     *    $msg 提示信息
     *    todo 逆行参数校验，状态码认证，支付失败也会回调, 可进行订单验证，调用微信的查询接口， 处理业务逻辑
     *    todo 必须有返回值，且值必须时布尔类型;
     * }
     * @param WxPayConfig $config
     * @param array $callback 业务处理回调方法,必须采用对象形式，[$object, $function]， 如 class:: function($arr, $xml, &$msg, $arr -> 验签成功后的数组，$xml -> 原始xml，$msg -> 处理过程的提示内容),
	 * @param bool $needSign  是否需要对返回值进行签名后返回
	 */
	final public function handle(WxPayConfig $config, $callback, $needSign = true)
	{
	    // todo 检验 $callback 的属性，是否是函数，是否具有相应必备的属性，用于替代 self::NotifyProcess 的方法，执行用户自定义的逻辑

		$this->config = $config;
		$msg = "OK";
		//当返回false的时候，表示notify中调用notifyCallBack回调失败获取签名校验失败，此时直接回复失败
		$result = WxpayApi::notify($config, $callback, $msg);
		if($result == false){
			$this->setReturnCode("FAIL");
			$this->setReturnMsg($msg);
			$this->replyNotify(false);
			return;
		} else {
			//该分支在成功回调到notifyCallBack方法，处理完成之后流程
			$this->setReturnCode("SUCCESS");
			$this->setReturnMsg("OK");
		}
		$this->replyNotify($needSign);
	}
	
	/**
     * 回复通知
	 * @param bool $needSign 是否需要签名输出
     * @throws \Exception
	 */
	final private function replyNotify($needSign = true)
	{
		//如果需要签名
		if($needSign == true && 
			$this->getReturnCode() == "SUCCESS")
		{
			$this->setSign($this->config);
		}

		// 此处可能会抛出异常，但改异常时必须处理的，所以留给业务处理
        $xml = $this->toXml();
        WxpayApi::replyNotify($xml);
	}
}