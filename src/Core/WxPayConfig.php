<?php
/**
*
* example目录下为简单的支付样例，仅能用于搭建快速体验微信支付使用
* 样例的作用仅限于指导如何使用sdk，在安全上面仅做了简单处理， 复制使用样例代码时请慎重
* 请勿直接直接使用样例对外提供服务
* 
**/
namespace tingyu\WeixinPay\Core;

/**
 * 微信支付基础配置类
 * Class WxPayConfig
 * @package tingyu\WeixinPay\Core
 */
class WxPayConfig
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * 绑定支付的APPID,(开户邮件中可查看)
     * @return mixed
     */
	public function getAppId()
	{
	    return $this->config['app_id'];
	}

    /**
     * 微信商户号，（开户邮件中可查看）
     * @return mixed
     */
	public function getMerchantId()
	{
	    return $this->config['mch_id'];
	}
	
	//=======【支付相关配置：支付成功回调地址/签名方式】===================================
	/**
     * 支付回调url
     * @return string
	*/
	public function getNotifyUrl()
	{
		return $this->config['notify_url'];
	}

    /**
     * 获取验证签名方式
     * @return mixed
     */
	public function getSignType()
	{
		return $this->config['sign_type'];
	}

	//=======【curl代理设置】===================================
	/**
	 * 这里设置代理机器，只有需要代理的时候才设置，不需要代理，请设置为0.0.0.0和0
	 * 本例程通过curl使用HTTP POST方法，此处可修改代理服务器，
	 * 默认CURL_PROXY_HOST=0.0.0.0和CURL_PROXY_PORT=0，此时不开启代理（如有需要才设置）
	 * @param string $proxyHost 代理ip
     * @param string $proxyPort 代理端口
	 */
	public function getProxy(&$proxyHost, &$proxyPort)
	{
		$proxyHost = "0.0.0.0";
		$proxyPort = 0;
	}
	

	//=======【上报信息配置】===================================
	/**
	 * 接口调用上报等级，默认紧错误上报（注意：上报超时间为【1s】，上报无论成败【永不抛出异常】，
	 * 不会影响接口调用流程），开启上报之后，方便微信监控请求调用的质量，建议至少
	 * 开启错误上报。
	 * 上报等级，0.关闭上报; 1.仅错误出错上报; 2.全量上报
	 * @return int
	 */
	public function getReportLevenl()
	{
		return 1;
	}


    /**
     * 商户支付密钥，参考开户邮件设置（必须设置，登陆商户平台自行设置）
     * 设置地址 ：https://pay.weixin.qq.com/index.php/account/api_cert
     * @return mixed
     */
	public function getKey()
	{
	    return $this->config['key'];
	}

    /**
     * 公众账号secert （仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置）
     * 获取地址：https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token=2005451881&lang=zh_CN
     * @return mixed
     */
	public function getAppSecret()
	{
	    return $this->config['app_secret'];
	}


	//=======【证书路径设置-需要业务方继承】=====================================
	/**
	 * 证书路径,注意应该填写绝对路径（仅退款、撤销订单时需要，可登录商户平台下载，
	 * API证书下载地址：https://pay.weixin.qq.com/index.php/account/api_cert，下载之前需要安装商户操作证书）
	 * 注意:
	 * 1.证书文件不能放在web服务器虚拟目录，应放在有访问权限控制的目录中，防止被他人下载；
	 * 2.建议将证书文件名改为复杂且不容易猜测的文件名；
	 * 3.商户服务器要做好病毒和木马防护工作，不被非法侵入者窃取证书文件。
     * @param string $sslCertPath 证书路径
	 * @param string $sslKeyPath 证书密钥路径
	 */
	public function getSSLCertPath(&$sslCertPath, &$sslKeyPath)
	{
		$sslCertPath = $this->config['ssl_cert_path'];
		$sslKeyPath = $this->config['ssl_key_path'];
	}
}
