<?php
/**
 * Created by tingyu
 * User: tingyu
 * motto: 努力让自己变得更好
 * Time: 23:24
 */
/**
 *
 * 接口调用结果类
 * @author widyhu
 *
 */
namespace tingyu\WeixinPay\Core\Database;
use tingyu\WeixinPay\Core\WxPayConfig;

class WxPayResults extends WxPayDataBase
{
    /**
     * 生成签名 - 重写该方法
     * @param WxPayConfig $config  配置对象
     * @param bool $needSignType  是否需要补signtype
     * @return string 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function makeSign(WxPayConfig $config, $needSignType = false)
    {
        //签名步骤一：按字典序排序参数
        ksort($this->values);
        $string = $this->toUrlParams();
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".$config->getKey();
        //签名步骤三：MD5加密或者HMAC-SHA256
        if(strlen($this->getSign()) <= 32){
            //如果签名小于等于32个,则使用md5验证
            $string = md5($string);
        } else {
            //是用sha256校验
            $string = hash_hmac("sha256",$string ,$config->getKey());
        }
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 验证签名是否正确
     * @param WxPayConfig $config  配置对象
     * 检测签名
     */
    public function checkSign(WxPayConfig $config)
    {
        if(!$this->isSignSet()){
            throw new \Exception("签名值未设置！", 2001);
        }

        $sign = $this->makeSign($config, false);
        if($this->getSign() == $sign){
            return true;
        }
        throw new \Exception("签名值验证错误！", 2002);
    }

    /**
     *
     * 使用数组初始化
     * @param array $array
     */
    public function fromArray($array)
    {
        $this->values = $array;
    }

    /**
     *
     * 使用数组初始化对象
     * @param WxPayConfig $config
     * @param array $array
     * @param bool $noCheckSign 是否检测签名
     */
    public static function initFromArray(WxPayConfig $config, $array, $noCheckSign = false)
    {
        $obj = new self();
        $obj->fromArray($array);
        if($noCheckSign == false){
            $obj->checkSign($config);
        }
        return $obj;
    }

    /**
     *
     * 设置参数
     * @param string $key
     * @param string $value
     */
    public function setData($key, $value)
    {
        $this->values[$key] = $value;
    }

    /**
     * 将xml转为array
     * @param WxPayConfig $config  配置对象
     * @param string $xml
     * @throws \Exception
     * @return array
     */
    public static function init(WxPayConfig $config, $xml)
    {
        $obj = new self();
        $obj->fromXml($xml);
        //失败则直接返回失败
        if($obj->values['return_code'] != 'SUCCESS') {
            foreach ($obj->values as $key => $value) {
                #除了return_code和return_msg之外其他的参数存在，则报错
                if($key != "return_code" && $key != "return_msg"){
                    throw new \Exception("输入数据存在异常！");
                }
            }
            return $obj->getValues();
        }
        $obj->checkSign($config);

        return $obj->getValues();
    }
}
