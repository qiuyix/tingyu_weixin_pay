<?php
/**
 * Created by tingyu
 * User: tingyu
 * motto: 努力让自己变得更好
 * Time: 23:25
 */
/**
 *
 * 回调回包数据基类
 *
 **/
namespace tingyu\WeixinPay\Core\Database;
use tingyu\WeixinPay\Core\WxPayConfig;

class WxPayNotifyResults extends WxPayResults
{
    /**
     * 将xml转为array
     * @param WxPayConfig $config
     * @param string $xml
     * @return WxPayNotifyResults
     * @throws \Exception
     */
    public static function init(WxPayConfig $config, $xml)
    {
        $obj = new self();
        $obj->fromXml($xml);
        //失败则直接返回失败
        $obj->checkSign($config);
        return $obj;
    }
}
