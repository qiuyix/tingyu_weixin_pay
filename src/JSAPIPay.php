<?php


namespace tingyu\WeixinPay;

use tingyu\WeixinPay\Core\WxPayJsApiPay;
use tingyu\WeixinPay\Core\Database\WxPayUnifiedOrder;
use tingyu\WeixinPay\Core\WxPayApi;
use tingyu\WeixinPay\Core\WxPayConfig;

/**
 * 公众号支付
 * Class JsapiPay
 * @package tingyu\WeixinPay
 */
class JSAPIPay extends IPay
{
    private $tradeType = 'JSAPI';

    protected $openid = '';

    /**
     * 设置用户的唯一标识 openid
     * @param $openid
     * @throws \Exception
     */
    public function setOpenid($openid)
    {
        if (!is_string($openid)) {
            throw new \Exception("openid 值不合法");
        }

        $this->openid = $openid;
    }

    /**
     * 统一下单
     * @param string $outTradeNo 支付订单号
     * @param int $totalFee 支付金额（分）
     * @param string $body 支付标题
     * @return array 支付参数
     * @throws \Exception
     */
    function unifiedOrder($outTradeNo, $totalFee, $body)
    {
        if ($outTradeNo == '') {
            throw new \Exception("商户订单号不能为空");
        }

        if (!preg_match('/^[^0]\d+$/', $totalFee)) {
            throw new \Exception("支付金额整数类型，单位为分");
        }

        if ($body == "") {
            throw new \Exception("支付描述不能为空");
        }

        if ($this->openid == '') {
            throw new \Exception("用户openid不能为空，通过setOpenid()设置");
        }

        $wxpayConfig = new WxPayConfig($this->config);

        $wxpayUnifiedOrder = new WxPayUnifiedOrder();
        $wxpayUnifiedOrder->setOutTradeNo($outTradeNo);
        $wxpayUnifiedOrder->setTotalFee($totalFee);
        $wxpayUnifiedOrder->setBody($body);
        $wxpayUnifiedOrder->setTradeType($this->tradeType);
        $wxpayUnifiedOrder->setOpenid($this->openid);

        $result = WxPayApi::unifiedOrder($wxpayConfig, $wxpayUnifiedOrder);

        if ($result && isset($result['result_code'])
            && isset($result['return_code'])
            && $result['result_code'] == 'SUCCESS'
            && $result['return_code'] == 'SUCCESS'
        ) {
            return (new WxPayJsApiPay())->GetJsApiParameters($wxpayConfig, $result);
        } else {
            throw new \Exception("App支付统一下单失败");
        }
    }

}