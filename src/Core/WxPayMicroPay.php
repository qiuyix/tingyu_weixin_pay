<?php
/**
*
* example目录下为简单的支付样例，仅能用于搭建快速体验微信支付使用
* 样例的作用仅限于指导如何使用sdk，在安全上面仅做了简单处理， 复制使用样例代码时请慎重
* 请勿直接直接使用样例对外提供服务
* 
**/
namespace tingyu\WeixinPay\Core;

use tingyu\WeixinPay\Core\Database\WxPayMicroPay;
use tingyu\WeixinPay\Core\Database\WxPayOrderQuery;
use tingyu\WeixinPay\Core\Database\WxPayReverse;

/**
 * 
 * 刷卡支付实现类
 * 该类实现了一个刷卡支付的流程，流程如下：
 * 1、提交刷卡支付
 * 2、根据返回结果决定是否需要查询订单，如果查询之后订单还未变则需要返回查询（一般反复查10次）
 * 3、如果反复查询10订单依然不变，则发起撤销订单
 * 4、撤销订单需要循环撤销，一直撤销成功为止（注意循环次数，建议10次）
 * 
 * 该类是微信支付提供的样例程序，商户可根据自己的需求修改，或者使用lib中的api自行开发，为了防止
 * 查询时hold住后台php进程，商户查询和撤销逻辑可在前端调用
 * 
 * @author widy
 *
 */
class MicroPay
{
	/**
	 * 提交刷卡支付， 由于刷卡支付的特殊性，用户获取到支付状态后，应立即多(10)次调用查询接口查询支付结果，注意时间间隔，如最终查询结果仍为失败，应立即取消订单
     * @license  https://pay.weixin.qq.com/wiki/doc/api/external/micropay.php?chapter=5_4 刷卡支付
     * @param WxPayConfig $config
     * @param WxPayMicroPay $microPayInput
     * @param array &$result 支付结果
	 * @throws \Exception
	 * @return bool 支付接口，false则肯定支付失败，true支付中或支付成功，用户需要调用查询方法进行多次从查询，如若失败必须撤销订单
	 */
	public function pay(WxPayConfig $config, WxPayMicroPay $microPayInput, &$result)
	{
		//①、提交被扫支付
		$result = WxPayApi::micropay($config, $microPayInput, 5);
		//如果返回成功
		if(!array_key_exists("return_code", $result)  || !array_key_exists("result_code", $result))
		{
			throw new \Exception("接口调用失败, 请确认输入是否有误！");
		}

		//②、接口调用成功，明确返回调用失败
		if($result["return_code"] == "SUCCESS" &&
		   $result["result_code"] == "FAIL" && 
		   $result["err_code"] != "USERPAYING" && 
		   $result["err_code"] != "SYSTEMERROR")
		{
			return false;
		}

	    return true;
	}
	
	/**
	 * 查询订单情况
     * @param WxPayConfig $config
	 * @param string $out_trade_no  商户订单号
	 * @param int $succCode         查询订单结果 0 订单不成功，1表示订单成功，2表示继续等待
	 * @return array|bool 查询结果和查询状态，应结合$succCode进行判断， 当为false时，可能需要继续等待或失败，但是当$succCode == 0, 表示失败
	 */
	public function query(WxPayConfig $config, $outTradeNo, &$succCode)
	{
		$queryOrderInput = new WxPayOrderQuery();
		$queryOrderInput->setOutTradeNo($outTradeNo);

        $result = WxPayApi::orderQuery($config, $queryOrderInput);

		if($result["return_code"] == "SUCCESS" 
			&& $result["result_code"] == "SUCCESS")
		{
			//支付成功
			if($result["trade_state"] == "SUCCESS"){
				$succCode = 1;
			   	return $result;
			}
			//用户支付中
			else if($result["trade_state"] == "USERPAYING"){
				$succCode = 2;
				return false;
			}
		}
		
		//如果返回错误码为“此交易订单号不存在”则直接认定失败
		if($result["err_code"] == "ORDERNOTEXIST") {
			$succCode = 0;
		} else {
			//如果是系统错误，则后续继续
			$succCode = 2;
		}
		return false;
	}
	
	/**
	 * 撤销订单，可能会有其它原因导致失败，强烈建议使用课程中如返回失败可尝试多调度几次
     * @param WxPayConfig $config 基础配置
	 * @param string $out_trade_no 订单编号
     * @return bool
	 */
	public function cancel(WxPayConfig $config, $outTradeNo)
	{
        $closetOrder = new WxPayReverse();
        $closetOrder->setOutTradeNo($outTradeNo);

        $result = WxPayApi::reverse($config, $closetOrder);

        //接口调用失败
        if($result["return_code"] != "SUCCESS"){
            return false;
        }

        //如果结果为success且不需要重新调用撤销，则表示撤销成功
        if($result["result_code"] != "SUCCESS" && $result["recall"] == "N") {
            return true;
        }

        return false;
	}
}