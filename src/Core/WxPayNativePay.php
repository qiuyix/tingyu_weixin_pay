<?php
/**
*
* example目录下为简单的支付样例，仅能用于搭建快速体验微信支付使用
* 样例的作用仅限于指导如何使用sdk，在安全上面仅做了简单处理， 复制使用样例代码时请慎重
* 请勿直接直接使用样例对外提供服务
* 
**/
namespace tingyu\WeixinPay\Core;

use tingyu\WeixinPay\Core\Database\WxPayBizPayUrl;
use tingyu\WeixinPay\Core\Database\WxPayUnifiedOrder;

/**
 * 注意 名称得更改 NativePay => WxPayNativePay
 * 扫码支付实现类
 * @author widyhu
 *
 */
class WxPayNativePay
{
	/**
	 * 生成扫描支付URL,模式一,适用于价格固定的，售货柜的场景使用；如果价格经常变化的，推荐使用模式二
     * @param WxPayConfig $config
	 * @param $productId
	 */
	public function getPrePayUrl(WxPayConfig $config, $productId)
	{
		$biz = new WxPayBizPayUrl();
		$biz->setProductId($productId);
        $values = WxpayApi::bizpayurl($config, $biz);
		$url = "weixin://wxpay/bizpayurl?" . $this->toUrlParams($values);
		return $url;
	}

    /**
	 *
	 * 参数数组转换为url参数
	 * @param array $urlObj
	 */
	private function toUrlParams($urlObj)
	{
		$buff = "";
		foreach ($urlObj as $k => $v)
		{
			$buff .= $k . "=" . $v . "&";
		}

		$buff = trim($buff, "&");
		return $buff;
	}

    /**
     * 生成直接支付url，支付url有效期为2小时,模式二
     * @param WxPayConfig $config
     * @param WxPayUnifiedOrder $input
     * @return
     * @throws \Exception 抛出异常
     */
    public function getPayUrl(WxPayConfig $config, WxPayUnifiedOrder $input)
    {
        if($input->getTradeType() != "NATIVE") {
            throw new \Exception('支付类型不合法,合法值为NATIVE');
        }

        try{
            $result = WxPayApi::unifiedOrder($config, $input);
        } catch(\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $result;
    }
}