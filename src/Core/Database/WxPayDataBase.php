<?php
/**
 * Created by singwa
 * User: singwa
 * motto: 现在的努力是为了小时候吹过的牛逼！
 * Time: 23:23
 */
/**
 *
 * 数据对象基础类，该类中定义数据类最基本的行为，包括：
 * 计算/设置/获取签名、输出xml格式的参数、从xml读取数据对象等
 * @author widyhu
 *
 */
namespace tingyu\WeixinPay\Core\Database;
use tingyu\WeixinPay\Core\WxPayConfig;

class WxPayDataBase
{
    protected $values = array();

    /**
     * 设置签名，详见签名生成算法类型
     * @param string $value
     **/
    public function setSignType($sign_type)
    {
        $this->values['sign_type'] = $sign_type;
        return $sign_type;
    }

    /**
     * 设置签名，详见签名生成算法
     * @param string $value
     **/
    public function setSign($config)
    {
        $sign = $this->makeSign($config);
        $this->values['sign'] = $sign;
        return $sign;
    }

    /**
     * 获取签名，详见签名生成算法的值
     * @return 值
     **/
    public function getSign()
    {
        return $this->values['sign'];
    }

    /**
     * 判断签名，详见签名生成算法是否存在
     * @return true 或 false
     **/
    public function isSignSet()
    {
        return array_key_exists('sign', $this->values);
    }

    /**
     * 输出xml字符
     * @throws \Exception
     **/
    public function toXml()
    {
        if(!is_array($this->values) || count($this->values) <= 0)
        {
            throw new \Exception("数组数据异常！");
        }

        $xml = "<xml>";
        foreach ($this->values as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @throws \Exception
     */
    public function fromXml($xml)
    {
        if(!$xml){
            throw new \Exception("xml数据异常！");
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $this->values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $this->values;
    }

    /**
     * 格式化参数格式化成url参数
     */
    public function toUrlParams()
    {
        $buff = "";
        foreach ($this->values as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 生成签名
     * @param WxPayConfig $config  配置对象
     * @param bool $needSignType  是否需要补signtype
     * @return string 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function makeSign(WxPayConfig $config, $needSignType = true)
    {
        if($needSignType) {
            $this->setSignType($config->getSignType());
        }
        //签名步骤一：按字典序排序参数
        ksort($this->values);
        $string = $this->toUrlParams();
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".$config->getKey();
        //签名步骤三：MD5加密或者HMAC-SHA256
        if($config->getSignType() == "MD5"){
            $string = md5($string);
        } else if($config->getSignType() == "HMAC-SHA256") {
            $string = hash_hmac("sha256",$string ,$config->getKey());
        } else {
            throw new \Exception("签名类型不支持！");
        }

        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 获取设置的值
     */
    public function getValues()
    {
        return $this->values;
    }
}
