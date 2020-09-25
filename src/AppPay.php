<?php


namespace tingyu\WeixinPay;

use tingyu\WeixinPay\Core\Database\WxPayUnifiedOrder;
use tingyu\WeixinPay\Core\WxPayApi;
use tingyu\WeixinPay\Core\WxPayConfig;

/**
 * app 支付
 * Class AppPay
 * @package tingyu\WeixinPay
 */
class AppPay extends IPay
{
    private $tradeType = 'APP';

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


        $wxpayConfig = new WxPayConfig($this->config);

        $wxpayUnifiedOrder = new WxPayUnifiedOrder();
        $wxpayUnifiedOrder->setOutTradeNo($outTradeNo);
        $wxpayUnifiedOrder->setTotalFee($totalFee);
        $wxpayUnifiedOrder->setBody($body);
        $wxpayUnifiedOrder->setTradeType($this->tradeType);

        $result = WxPayApi::unifiedOrder($wxpayConfig, $wxpayUnifiedOrder);

        if ($result && isset($result['result_code'])
            && isset($result['return_code'])
            && $result['result_code'] == 'SUCCESS'
            && $result['return_code'] == 'SUCCESS'
        ) {

        } else {
            throw new \Exception("App支付统一下单失败");
        }

    }
}