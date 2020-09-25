<?php


namespace tingyu\WeixinPay;

use tingyu\WeixinPay\Core\WxPayJsApiPay;
use tingyu\WeixinPay\Core\Database\WxPayUnifiedOrder;
use tingyu\WeixinPay\Core\WxPayApi;
use tingyu\WeixinPay\Core\WxPayConfig;

/**
 * 小程序支付
 * Class MicroProgramPay
 * @package tingyu\WeixinPay
 */
class MicroProgramPay extends IPay
{
    private $tradeType = 'JSAPI';

    /**
     * 用户唯一标识
     * @var
     */
    protected $openid = '';

    /**
     * 终端ip
     * @var
     */
    protected $spbillCreateIp = '';

    /**
     * 场景信息
     * @var
     */
    protected $sceneInfo;

    /**
     * 设置用户openid标识
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
     * 设置终端ip
     * @param $spbillCreateIp
     */
    public function setSpbillCreateIp($spbillCreateIp)
    {
        $this->spbillCreateIp = $spbillCreateIp;
    }

    /**
     * 设置场景信息
     * @param string $appName 应用名
     * @param string $packageName 报名
     */
    /**
     * 设置场景信息，常用于线下活动时的场景信息上报，支持上报实际门店信息，商户也可以按需求自己上报相关信息
     * @param string $id
     * @param string $name
     * @param string $areaCode
     * @param string $address
     */
    public function setSceneInfo($id = '', $name = '', $areaCode = '', $address = '') {
        $arr = [
            'store_info' => [
                'id'=> $id,
                'name' => $name,
                'area_code' => $areaCode,
                'address' => $address
            ]
        ];

        $this->sceneInfo = json_encode($arr);
    }

    /**
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

        if ($this->spbillCreateIp == '') {
            throw new \Exception("终端ip不能为空, 通过setSpbillCreateIp()设置");
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
        $wxpayUnifiedOrder->setSpbillCreateIp($this->spbillCreateIp);

        if ($this->sceneInfo != '') {
            $wxpayUnifiedOrder->setSceneInfo($this->sceneInfo);
        }

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