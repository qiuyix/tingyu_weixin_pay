<?php
/**
*
* example目录下为简单的支付样例，仅能用于搭建快速体验微信支付使用
* 样例的作用仅限于指导如何使用sdk，在安全上面仅做了简单处理， 复制使用样例代码时请慎重
* 请勿直接直接使用样例对外提供服务
* 
**/
namespace tingyu\WeixinPay\Core;

use tingyu\WeixinPay\Core\Database\WxPayJsApiPay as WxpyJsApiPayDb;


/**
 * 
 * JSAPI支付实现类
 * 该类实现了从微信公众平台获取code、通过code获取openid和access_token、
 * 生成jsapi支付js接口所需的参数、生成获取共享收货地址所需的参数
 * 
 * 该类是微信支付提供的样例程序，商户可根据自己的需求修改，或者使用lib中的api自行开发
 * 
 * @author widy
 *
 */
class WxPayJsApiPay
{
	/**
	 * 
	 * 网页授权接口微信服务器返回的数据，返回样例如下
	 * {
	 *  "access_token":"ACCESS_TOKEN",
	 *  "expires_in":7200,
	 *  "refresh_token":"REFRESH_TOKEN",
	 *  "openid":"OPENID",
	 *  "scope":"SCOPE",
	 *  "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
	 * }
	 * 其中access_token可用于获取共享收货地址
	 * openid是微信支付jsapi支付接口必须的参数
	 * @var array
	 */
	public $data = null;

	/**
	 * 获取jsapi支付的参数
	 * @param array $UnifiedOrderResult 统一支付接口返回的数据
	 * @throws \Exception
	 * 
	 * @return array
	 */
	public function GetJsApiParameters(WxPayConfig $config, $UnifiedOrderResult)
	{
		if(!array_key_exists("appid", $UnifiedOrderResult)
		    || !array_key_exists("prepay_id", $UnifiedOrderResult)
		    || $UnifiedOrderResult['prepay_id'] == "") {
			throw new \Exception("参数错误");
		}

		$jsapi = new WxpyJsApiPayDb();
		$jsapi->setAppid($UnifiedOrderResult["appid"]);
		$jsapi->setTimeStamp(time());
		$jsapi->setNonceStr(WxPayApi::getNonceStr());
		$jsapi->setPackage("prepay_id=" . $UnifiedOrderResult['prepay_id']);

		$jsapi->setPaySign($jsapi->makeSign($config));
		return $jsapi->getValues();
	}
}
