<?php


namespace tingyu\WeixinPay;


use tingyu\WeixinPay\Core\Database\WxPayNotifyReply;
use tingyu\WeixinPay\Core\Database\WxPayNotifyResults;
use tingyu\WeixinPay\Core\WxPayApi;
use tingyu\WeixinPay\Core\WxPayConfig;

class Notify extends IPay
{

    /**
     * 获取异步回调数据并进行验签，验签成功返回解析后的array，失败则抛出异常
     * @param string $xml 引用变量，定义为空字符串最好，可通过此参数获取到原始的回调xml格式数据
     * @return array
     * @throws \Exception
     */
    public function callback(&$xml)
    {
        $xml = file_get_contents("php://input");
        if (empty($xml)) {
            throw new \Exception("未获取到回调数据");
        }

        // 验签, 并解析数据，验签失败货解析失败都将以异常形式抛出
        $result = WxPayNotifyResults::init(new WxPayConfig($this->config), $xml);

        return $result->getValues();
    }

    /**
     * 业务逻辑处理后通知微信支付回调信息
     * @param bool $bool true 业务处理成功，false 业务处理失败
     * @param string $msg 业务处理失败返回的通知信息
     * @throws \Exception
     */
    public function reply($bool, $msg = '')
    {
        $WxPayNotifyReply = new WxPayNotifyReply();
        if ($bool) {
            $WxPayNotifyReply->setReturnCode("SUCCESS");
            $WxPayNotifyReply->setReturnMsg("OK");
        } else {
            $WxPayNotifyReply->setReturnCode("FAIL");
            $WxPayNotifyReply->setReturnMsg($msg);
        }

        $xml = $WxPayNotifyReply->toXml();

        WxPayApi::replyNotify($xml);
        return;
    }




    /**
     * 无效方法，禁止使用
     * @deprecated
     * @param $outTradeNo
     * @param $totalFee
     * @param $body
     * @throws \Exception
     */
    public function unifiedOrder($outTradeNo, $totalFee, $body){
        throw new \Exception('无效方法，禁止使用');
    }

    /**
     * 无效方法，禁止使用
     * @deprecated
     * @param $outTradeNo
     * @return array|void
     * @throws \Exception
     */
    public function closeOrder($outTradeNo) {
        throw new \Exception('无效方法，禁止使用');
    }

    /**
     * 无效方法，禁止使用
     * @deprecated
     * @param $outRefundNo
     * @param $refundFee
     * @param $totalFee
     * @param string $outTradeNo
     * @param string $transactionId
     * @return array|void
     * @throws \Exception
     */
    public function refund($outRefundNo, $refundFee, $totalFee, $outTradeNo, $transactionId){
        throw new \Exception('无效方法，禁止使用');
    }

    /**
     * 无效方法，禁止使用
     * @deprecated
     * @param string $outTradeNo
     * @param string $outRefundNo
     * @param string $transactionId
     * @param string $refundId
     * @return array|void
     * @throws \Exception
     */
    public function refundQuery($outTradeNo, $outRefundNo, $transactionId, $refundId){
        throw new \Exception('无效方法，禁止使用');
    }

    /**
     * 无效方法，禁止调用
     * @deprecated
     * @param string $outTradeNo
     * @param string $transactionId
     * @return array|void
     * @throws \Exception
     */
    public function orderQuery($outTradeNo = '', $transactionId = ''){
        throw new \Exception('无效方法，禁止使用');
    }
}