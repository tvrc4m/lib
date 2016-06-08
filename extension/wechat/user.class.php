<?php

namespace WeChat;

class User  extends Auth{

	public function __construct(){

		parent::__construct();

		$this->access_token=$this->get_access_token();

	    if(empty($this->access_token)) return false;
	}

	/**
	 * 批量获取关注用户列表
	 * @param unknown $next_openid
	 */
	public function get_user_list($next_openid=''){
		
		return $this->http_get(self::API_URL_PREFIX.'/user/get?access_token='.$this->access_token.'&next_openid='.$next_openid);
	}

	/**
	 * 获取关注者详细信息
	 * @param string $openid
	 * @return array {subscribe,openid,nickname,sex,city,province,country,language,headimgurl,subscribe_time,[unionid]}
	 * 注意：unionid字段 只有在用户将公众号绑定到微信开放平台账号后，才会出现。建议调用前用isset()检测一下
	 */
	public function get_user_info($openid){
		
		return $this->http_get(self::API_URL_PREFIX.'/user/info?access_token='.$this->access_token.'&openid='.$openid);
	}

	/**
	 * 设置用户备注名
	 * @param string $openid
	 * @param string $remark 备注名
	 * @return boolean|array
	 */
	public function update_user_remark($openid,$remark){
	    
	    $data = array('openid'=>$openid,'remark'=>$remark);

	    return $this->http_post(self::API_URL_PREFIX.'/user/info/updateremark?access_token='.$this->access_token,$data);
	}
}
