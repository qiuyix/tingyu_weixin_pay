<?php


namespace tingyu\WeixinPay;


use tingyu\WeixinPay\Core\Database\WxPayCloseOrder;
use tingyu\WeixinPay\Core\Database\WxPayOrderQuery;
use tingyu\WeixinPay\Core\Database\WxPayRefund;
use tingyu\WeixinPay\Core\Database\WxPayRefundQuery;
use tingyu\WeixinPay\Core\Database\WxPayResults;
use tingyu\WeixinPay\Core\WxPayApi;
use tingyu\WeixinPay\Core\WxPayConfig;

abstract class IPay
{
    /**
     * 应用appid
     * @var mixed|string
     */
    protected $appId;

    /**
     * 公众账号 secert, 仅支付方式为jsapi是需要
     * @var
     */
    protected $appSecret;

    /**
     * 支付商户号
     * @var mixed|string
     */
    protected $mchId;

    /**
     * 支付异步通知url
     * @var mixed|string
     */
    protected $notifyUrl;

    /**
     * 签名密钥
     * @var mixed|string
     */
    protected $signKey;

    /**
     * ssl cert证书路径
     * @var mixed|string
     */
    protected $sslCertPath;

    /**
     * ssl key 证书路径
     * @var mixed|string
     */
    protected $sslKeyPath;

    /**
     * 签名类型方法
     */
    protected $signType;

    /**
     * 配置数组
     * @var
     */
    protected $config;

    protected $requestArr;

    protected $requestXml;

    protected $reponseArr;

    protected $reponseXml;

    /**
     * 实例化微信支付是需要传递数组参数，数组内容必须包含appid, mch_id, notify_url，['appid'=>'应用id','mch_id'=>'商户号','notify_url'=>'异步通知地址'， ‘key'='签名密钥', 'ssl_cert_path'=>'证书路径', 'ssl_ke_path'=>'证书密钥路径']
     * IPay constructor.
     * @param $config
     * @throws \Exception
     */
    public function __construct($config)
    {
        if (!is_array($config) || empty($config)) {
            throw new \Exception('缺少配置信息');
        }

        if (!array_key_exists('appid', $config) || !is_string($config['appid']) || $config['appid'] == '') {
            throw new \Exception("appid 必传");
        }

        if (!array_key_exists('mch_id', $config) || !is_string($config['mch_id']) || $config['mch_id'] == '') {
            throw new \Exception('mch_id 必传');
        }

        if (!array_key_exists('notify_url', $config) || !is_string($config['notify_url']) || $config['notify_url'] == '') {
            throw new \Exception('notify_url 必传');
        }

        if (!array_key_exists('key', $config) || !is_string($config['key']) || $config['key'] == '') {
            throw new \Exception('key 签名密钥必传');
        }

        if (!array_key_exists('ssl_cert_path', $config) || !is_string($config['ssl_cert_path']) || !file_exists($config['ssl_cert_path'])) {
            throw new \Exception('ssl cert 证书必传，且为绝对路径最佳');
        }

        if (!array_key_exists('ssl_key_path', $config) || !is_string($config['ssl_key_path']) || !file_exists($config['ssl_key_path'])) {
            throw new \Exception('ssl key 证书密钥必传，且为绝对路径');
        }

        if (!array_key_exists('sign_type', $config) || !is_string($config['sign_type']) || !in_array($config['sign_type'], ['md5', 'HMAC-SHA256'])) {
            throw new \Exception('签名类型值可为MD5或HMAC-SHA256');
        }


        // 这几个信息设置在哪里，写入配置中去
        $this->appId = $config['appid'];
        $this->mchId = $config['mch_id'];
        $this->notifyUrl = $config['notify_url'];
        $this->signKey = $config['key'];
        $this->sslCertPath = $config['ssl_cert_path'];
        $this->sslKeyPath = $config['ssl_key_path'];
        $this->signType = $config['sign_type'];
        $this->appSecret = isset($config['app_secret']) ? $config['app_secret'] : null;

        $this->config = $config;
    }

    /**
     * 统一下单
     */
    abstract function unifiedOrder($outTradeNo, $totalFee, $body);

    /**
     * 关闭订单
     * @param $outTradeNo
     * @return array
     * @throws \Exception
     */
    public function closeOrder($outTradeNo)
    {
        if (!is_string($outTradeNo) || $outTradeNo == '') {
            throw new \Exception('商户订单号字符串类型，必传');
        }

        $wxPayCloseOrder = new WxPayCloseOrder();
        $wxPayConfig = new WxPayConfig($this->config);
        $wxPayCloseOrder->setOutTradeNo($outTradeNo);

        return WxPayApi::closeOrder($wxPayConfig, $wxPayCloseOrder);
    }

    /**
     * 发起退款
     * @param $outRefundNo
     * @param $refundFee
     * @param $totalFee
     * @param string $outTradeNo
     * @param string $transactionId
     * @return array
     * @throws \Exception
     */
    public function refund($outRefundNo, $refundFee, $totalFee, $outTradeNo = '', $transactionId = '')
    {
        if (!is_string($outRefundNo) || $outRefundNo == '') {
            throw new \Exception('商户退款单号字符串类型，必传');
        }

        if (!preg_match('/^[^0]\d+$/', $totalFee)) {
            throw new \Exception('订单金额单位为分，非0及0开头，必传');
        }

        if (!preg_match('/^[^0]\d+$/', $refundFee)) {
            throw new \Exception('退款金额单位为分，非0及0开头，必传');
        }

        if (!is_string($outTradeNo) && !is_string($transactionId) && $outTradeNo == '' && $transactionId == '') {
            throw new \Exception('商户订单号和微信订单号二选一，且为字符串类型');
        }

        $wxPayRefund = new WxPayRefund();
        $wxPayConfig = new WxPayConfig($this->config);
        $wxPayRefund->setOutTradeNo($outTradeNo);
        $wxPayRefund->setOutRefundNo($refundFee);
        $wxPayRefund->setTransactionId($transactionId);
        $wxPayRefund->setOutRefundNo($outRefundNo);
        $wxPayRefund->setTotalFee($totalFee);

        return WxPayApi::refund($wxPayConfig, $wxPayRefund);
    }

    /**
     * 查询退款
     * @param string $outTradeNo
     * @param string $outRefundNo
     * @param string $transactionId
     * @param string $refundId
     * @return array
     * @throws \Exception
     */
    public function refundQuery($outTradeNo = '', $outRefundNo = '', $transactionId = '', $refundId = '')
    {
        if (!is_string($outTradeNo) && !is_string($outRefundNo) && !is_string($transactionId) && !is_string($refundId) &&
            $outTradeNo == '' && $outRefundNo == '' && $transactionId == '' && $refundId == ''
        ) {
            throw new \Exception('商户订单号、商户退款订单号、微信订单号、微信退款订单号四选一，且为字符串类型');
        }


        $wxPayRefundQuery = new WxPayRefundQuery();
        $wxPayConfig = new WxPayConfig($this->config);
        $wxPayRefundQuery->setOutTradeNo($outTradeNo);
        $wxPayRefundQuery->setOutRefundNo($outRefundNo);
        $wxPayRefundQuery->setTransactionId($transactionId);
        $wxPayRefundQuery->setRefundId($refundId);

        return WxPayApi::refundQuery($wxPayConfig, $wxPayRefundQuery);
    }

    /**
     * 查询订单
     * @param string $outTradeNo
     * @param string $transactionId
     * @return array
     * @throws \Exception
     */
    public function orderQuery($outTradeNo = '', $transactionId = '')
    {
        if (!is_string($outTradeNo) && !is_string($transactionId) && $outTradeNo == '' && $transactionId == '') {
            throw new \Exception('商户订单号和微信订单号二选一，且为字符串类型');
        }

        $wxPayOrderQuery = new WxPayOrderQuery();
        $wxPayConfig = new WxPayConfig($this->config);
        $wxPayOrderQuery->setOutTradeNo($outTradeNo);
        $wxPayOrderQuery->setTransactionId($transactionId);

        return WxPayApi::orderQuery($wxPayConfig, $wxPayOrderQuery);
    }

    /**
     * 验证签名，参数值是xml格式字符串
     * @param $xml
     * @return bool
     * @throws \Exception
     */
    public function checkSignXml($xml)
    {
        $wxpayResult = new WxPayResults();
        $wxPayConfig = new WxPayConfig($this->config);

        $wxpayResult->fromXml($xml);

        return $wxpayResult->checkSign($wxPayConfig);
    }

    /**
     * 验证签名, 参数值是数组
     * @param array $arr
     * @return bool
     * @throws \Exception
     */
    public function checkSignArr($arr)
    {
        $wxpayResult = new WxPayResults();
        $wxPayConfig = new WxPayConfig($this->config);

        $wxpayResult->fromArray($arr);

        return $wxpayResult->checkSign($wxPayConfig);
    }
}