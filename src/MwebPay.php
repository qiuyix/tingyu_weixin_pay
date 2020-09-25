<?php


namespace tingyu\WeixinPay;

use tingyu\WeixinPay\Core\WxPayJsApiPay;
use tingyu\WeixinPay\Core\Database\WxPayUnifiedOrder;
use tingyu\WeixinPay\Core\WxPayApi;
use tingyu\WeixinPay\Core\WxPayConfig;

/**
 * h5网页支付
 * Class JsapiPay
 * @package tingyu\WeixinPay
 */
class MwebPay extends IPay
{
    private $tradeType = 'MWEB';

    /**
     * 用户身份标识openid
     * @var
     */
    protected $openid = '';

    /**
     * 发起支付客户端ip
     * @var
     */
    protected $spbillCreateIp = '';

    /**
     * 场景信息
     * @var
     */
    protected $sceneInfo;

    /**
     * 设置支付用户的openid，可选参数
     * @param string $openid
     * @throws \Exception
     */
    public function setOpenid($openid)
    {
        $this->openid = $openid;
    }

    /**
     * 设置ios的场景信息
     * @param string $appName 应用名
     * @param string $bundleId bundle_id
     */
    public function setIosSceneInfo($appName, $bundleId) {
        $arr = [
            'h5_info' => [
                'type'=> 'IOS',
                'app_name' => $appName,
                'bundle_id' => $bundleId
            ]
        ];

        $this->sceneInfo = json_encode($arr);
    }

    /**
     * 设置Android的场景信息
     * @param string $appName 应用名
     * @param string $packageName 报名
     */
    public function setAndroidSceneInfo($appName, $packageName) {
        $arr = [
            'h5_info' => [
                'type'=> 'Android',
                'app_name' => $appName,
                'bundle_id' => $packageName
            ]
        ];

        $this->sceneInfo = json_encode($arr);
    }

    /**
     * 设置H5网页的场景信息
     * @param string $wapUrl Wap网站url地址
     * @param string $wapName Wap网站名
     */
    public function setWapSceneInfo($wapUrl, $wapName) {
        $arr = [
            'h5_info' => [
                'type'=> 'Wap',
                'wap_url' => $wapUrl,
                'wap_name' => $wapName
            ]
        ];

        $this->sceneInfo = json_encode($arr);
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

        if ($this->spbillCreateIp == '') {
            throw new \Exception('终端ip必须设置');
        }

        if (is_null($this->sceneInfo) || $this->sceneInfo == '') {
            throw new \Exception("支付场景信息必须设置，可调setXxxSceneInfo方法设置");
        }

        $wxpayConfig = new WxPayConfig($this->config);

        $wxpayUnifiedOrder = new WxPayUnifiedOrder();
        $wxpayUnifiedOrder->setOutTradeNo($outTradeNo);
        $wxpayUnifiedOrder->setTotalFee($totalFee);
        $wxpayUnifiedOrder->setBody($body);
        $wxpayUnifiedOrder->setTradeType($this->tradeType);
        $wxpayUnifiedOrder->setSpbillCreateIp($this->spbillCreateIp);
        $wxpayUnifiedOrder->setSceneInfo($this->sceneInfo);

        if (!is_null($this->openid)) {
            $wxpayUnifiedOrder->setOpenid($this->openid);
        }

        $result = WxPayApi::unifiedOrder($wxpayConfig, $wxpayUnifiedOrder);

        if ($result && isset($result['result_code'])
            && isset($result['return_code'])
            && $result['result_code'] == 'SUCCESS'
            && $result['return_code'] == 'SUCCESS'
        ) {
            return $result;
        } else {
            throw new \Exception("App支付统一下单失败");
        }
    }

}