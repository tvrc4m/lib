<?php

namespace WeChat;

class Auth  extends Base{

	protected $access_token;

	protected $cache_access_token;
	protected $cache_jsapi_ticket;
	protected $cache_jscard_ticket;

	public function __construct(){

		parent::__construct();
		$this->cache_access_token="access_token_".$this->appid;
		$this->cache_jsapi_ticket="jsapi_ticket_".$this->appid;
		$this->cache_jscard_ticket="jscard_ticket_".$this->appid;
	}

	/**
	 * 微信服务器验证token
	 */
	public function check_signature($data)
	{
        $signature = isset($data["signature"])?$data["signature"]:'';
	    $signature = isset($data["msg_signature"])?$data["msg_signature"]:$signature; //如果存在加密验证则用加密验证段
        $timestamp = isset($data["timestamp"])?$data["timestamp"]:'';
        $nonce = isset($data["nonce"])?$data["nonce"]:'';
        $echostr = isset($data["echostr"])?$data["echostr"]:'';

		$token = $this->token;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		if( $tmpStr == $signature ){
			return $echostr;
		}else{
			return false;
		}
	}


	/**
	 * oauth 授权跳转接口
	 * @param string $callback 回调URI
	 * @return string
	 */
	public function get_oauth_redirect($callback,$state='',$scope='snsapi_userinfo'){

		return self::OAUTH_PREFIX.'/authorize?appid='.$this->appid.'&redirect_uri='.urlencode($callback).'&response_type=code&scope='.$scope.'&state='.$state.'#wechat_redirect';
	}

	/**
	 * 通过code获取Access Token
	 * @return array {access_token,expires_in,refresh_token,openid,scope}
	 */
	public function get_oauth_access_token($code){

		if (!$code) return false;

		$json=$this->http_get(self::API_BASE_URL_PREFIX.'/sns/oauth2/access_token?appid='.$this->appid.'&secret='.$this->appsecret.'&code='.$code.'&grant_type=authorization_code');

		return $json['access_token'];
	}

	/**
	 * 刷新access token并续期
	 * @param string $refresh_token
	 * @return boolean|mixed
	 */
	public function get_oauth_refresh_token($refresh_token){

		$json=$this->http_get(self::API_BASE_URL_PREFIX.'/sns/oauth2/refresh_token?appid='.$this->appid.'&grant_type=refresh_token&refresh_token='.$refresh_token);

		return $json['access_token'];
	}

	/**
	 * 检验授权凭证是否有效
	 * @param string $access_token
	 * @param string $openid
	 * @return boolean 是否有效
	 */
	public function get_oauth_auth($access_token,$openid){

	    return $this->http_get(self::API_BASE_URL_PREFIX.'/sns/auth?access_token='.$access_token.'&openid='.$openid);
	}

	/**
	 * 获取access_token
	 * @param string $appid 如在类初始化时已提供，则可为空
	 * @param string $appsecret 如在类初始化时已提供，则可为空
	 * @param string $token 手动指定access_token，非必要情况不建议用
	 */
	public function get_access_token(){

		if ($access_token = $this->getCache($this->cache_access_token))  {
			return $access_token;
		}

		if($this->http_get(self::API_URL_PREFIX.'/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->appsecret)){

			$access_token = $json['access_token'];
			$expire = $json['expires_in'] ? intval($json['expires_in'])-100 : 3600;
			$this->setCache($this->cache_access_token,$access_token,$expire);
			return $access_token;
		}
		
		return false;
	}

	/**
	 * 获取授权后的用户资料
	 * @param string $openid
	 * @return array {openid,nickname,sex,province,city,country,headimgurl,privilege,[unionid]}
	 * 注意：unionid字段 只有在用户将公众号绑定到微信开放平台账号后，才会出现。建议调用前用isset()检测一下
	 */
	protected function get_oauth_user_info($access_token,$openid){

		return $this->http_get(self::API_BASE_URL_PREFIX.'/sns/userinfo?access_token='.$access_token.'&openid='.$openid);
	}

	/**
	 * 获取JSAPI授权TICKET
	 * @param string $appid 用于多个appid时使用,可空
	 * @param string $jsapi_ticket 手动指定jsapi_ticket，非必要情况不建议用
	 */
	public function get_js_ticket(){

		$access_token=$this->get_access_token();

		if(!$access_token) return false;
		
		if ($js_ticket = $this->getCache($this->cache_jsapi_ticket))  {

			return $js_ticket;
		}

		$json = $this->http_get(self::API_URL_PREFIX.'/ticket/getticket?access_token='.$access_token.'&type=jsapi');
		
		if ($json){

			$jsapi_ticket = $json['ticket'];
			$expire = $json['expires_in'] ? intval($json['expires_in'])-100 : 3600;
			$this->setCache($this->cache_jsapi_ticket,$jsapi_ticket,$expire);
			return $jsapi_ticket;
		}
		return false;
	}


	/**
	 * 获取JsApi使用签名
	 * @param string $url 网页的URL，自动处理#及其后面部分
	 * @param string $timestamp 当前时间戳 (为空则自动生成)
	 * @param string $noncestr 随机串 (为空则自动生成)
	 * @param string $appid 用于多个appid时使用,可空
	 * @return array|bool 返回签名字串
	 */
	public function get_js_sign($url){
		
		$jsapi_ticket=$this->get_js_ticket();

		if(!$jsapi_ticket || !$url) return false;

        $timestamp = time();
        $noncestr = $this->generateNonceStr();

	    $ret = strpos($url,'#');
	    if ($ret)
	        $url = substr($url,0,$ret);
	    $url = trim($url);
	    if (empty($url))
	        return false;
	    $arrdata = array("timestamp" => $timestamp, "noncestr" => $noncestr, "url" => $url, "jsapi_ticket" => $this->jsapi_ticket);
	    
	    $sign = $this->getSignature($arrdata);
	    // echo $sign;exit;
	    if (!$sign)
	        return false;
	    $signPackage = array(
	            "appId"     => $this->appid,
	            "nonceStr"  => $noncestr,
	            "timestamp" => $timestamp,
	            "url"       => $url,
	            "signature" => $sign
	    );
	    return $signPackage;
	}

	/**
	 * 获取微信卡券api_ticket
	 * @param string $appid 用于多个appid时使用,可空
	 * @param string $api_ticket 手动指定api_ticket，非必要情况不建议用
	 */
	public function get_js_card_ticket($api_ticket=''){

		$access_token=$this->get_access_token();

		if(!$access_token) return false;
		
		if ($api_ticket = $this->getCache($this->cache_jscard_ticket))  {
			
			return $api_ticket;
		}

		$result = $this->http_get(self::API_URL_PREFIX.'/ticket/getticket?access_token='.$access_token.'&type=wx_card');
		if ($result)
		{
			
			$api_ticket = $json['ticket'];
			$expire = $json['expires_in'] ? intval($json['expires_in'])-100 : 3600;
			$this->setCache($this->cache_jscard_ticket,$api_ticket,$expire);
			return $api_ticket;
		}
		return false;
	}

	/**
	 * 获取微信卡券签名
	 * @param array $arrdata 签名数组
	 * @param string $method 签名方法
	 * @return boolean|string 签名值
	 */
	public function get_ticket_signature($arrdata,$method="sha1") {

		if (!function_exists($method)) return false;

		$newArray = array();

		foreach($arrdata as $key => $value){

			array_push($newArray,(string)$value);
		}

		sort($newArray,SORT_STRING);

		return $method(implode($newArray));
	}

	/**
	 * 获取微信服务器IP地址列表
	 * @return array('127.0.0.1','127.0.0.1')
	 */
	public function getServerIp(){

		$access_token=$this->get_access_token();

		if(!$access_token) return false;
		
		$result = $this->http_get(self::API_URL_PREFIX.'/getcallbackip?access_token='.$this->access_token);
		
		return $result['ip_list'];
	}

	/**
	 * 设置缓存，按需重载
	 * @param mixed $value
	 * @param int $expired
	 * @return boolean
	 */
	protected function setCache($filename,$access_token,$expired){
		return @file_put_contents(WECHAT_CACHE.$filename,sprintf('%s|%s|%s',$access_token,time(),$expired));
	}

	/**
	 * 获取缓存，按需重载
	 * @param string $cachename
	 * @return mixed
	 */
	protected function getCache($filename){
		list($access_token,$record_time,$expired)=explode('|', @file_get_contents(WECHAT_CACHE.$filename));
		if (time()>$record_time+$expired) return false;
		return $access_token;
	}
}