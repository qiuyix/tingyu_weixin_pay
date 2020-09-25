<?php
/**
 * Created by tingyu
 * User: tingyu
 * motto: 努力让自己变得更好
 * Time: 23:24
 */
/**
 *
 * 只使用md5算法进行签名， 不管配置的是什么签名方式，都只支持md5签名方式
 *
 **/
namespace tingyu\WeixinPay\Core\Database;
use tingyu\WeixinPay\Core\WxPayConfig;

class WxPayDataBaseSignMd5 extends WxPayDataBase
{
    /**
     * 生成签名 - 重写该方法
     * @param WxPayConfig $config  配置对象
     * @param bool $needSignType  是否需要补signtype
     * @return string 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function makeSign(WxPayConfig $config, $needSignType = false)
    {
        if($needSignType) {
            $this->setSignType($config->getSignType());
        }
        //签名步骤一：按字典序排序参数
        ksort($this->values);
        $string = $this->toUrlParams();
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".$config->getKey();
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
}