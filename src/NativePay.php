<?php


namespace tingyu\WeixinPay;

use tingyu\WeixinPay\Core\Database\WxPayUnifiedOrder;
use tingyu\WeixinPay\Core\WxPayConfig;
use tingyu\WeixinPay\Core\WxPayNativePay;

/**
 * 扫码支付
 * Class NativePay
 * @package tingyu\WeixinPay
 */
class NativePay extends IPay
{
    private $tradeType = 'NATIVE';

    // 支付超时时间单位秒
    private $payExpire = 60;

    private $productId;

    /**
     * 设置productId
     * @param $productId
     * @throws \Exception
     */
    public function setProductId($productId)
    {
        if (!is_string($productId) || !is_int($productId)) {
            throw new \Exception("扫码支付ProductId内容不合法");
        }
        $this->productId = $productId;
    }

    /**
     * 统一下单
     * @param string $outTradeNo 订单号
     * @param int $totalPrice 支付金额，单位分
     * @param string $body 支付描述
     * @return mixed 返回二维码之地，使用者需要将其转换成二维码图片输出
     * @throws \Exception
     */
    public function unifiedOrder($outTradeNo, $totalFee, $body)
    {
        if ($this->productId == null || $this->productId == '') {
            throw new \Exception("扫码支付ProductId未设值");
        }

        if ($outTradeNo == '') {
            throw new \Exception("商户订单号不能为空");
        }

        if (!preg_match('/^[^0]\d+$/', $totalFee)) {
            throw new \Exception("支付金额整数类型，单位为分");
        }

        if ($body == "") {
            throw new \Exception("支付描述不能为空");
        }

        $wxpayNativePay = new WxPayNativePay();
        $wxPayConfig = new WxPayConfig($this->config);
        $wxPayUnifiedOrder = new WxPayUnifiedOrder();

        $wxPayUnifiedOrder->setBody($body);
        $wxPayUnifiedOrder->setOutTradeNo($outTradeNo);
        $wxPayUnifiedOrder->setTotalFee($totalFee);
        $wxPayUnifiedOrder->setProductId($this->productId);

        $wxPayUnifiedOrder->setTimeStart(date('YmdHis'));
        $wxPayUnifiedOrder->setTimeExpire(date('YmdHis', time() + $this->payExpire));

        // 签名密钥，证书， 证书密钥
        $result = $wxpayNativePay->getPayUrl($wxPayConfig, $wxPayUnifiedOrder);

        if ($result && isset($result['result_code'])
            && isset($result['return_code'])
            && $result['result_code'] == 'SUCCESS'
            && $result['return_code'] == 'SUCCESS'
        ) {
            $url = $result['code_url'];

            // 二维码地址
            return $url;
        } else {
            // 是否需要配置日志，提示信息
            throw new \Exception("统一下单失败");
        }
    }
}