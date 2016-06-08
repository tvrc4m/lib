<?php

namespace WeChat;

class Group  extends Auth{

	public function __construct(){

		parent::__construct();

		$this->access_token=$this->get_access_token();

	    if(empty($this->access_token)) return false;
	}

	/**
	 * 获取用户分组列表
	 * @return boolean|array
	 */
	public function get_group(){
		
		return $this->http_get(self::API_URL_PREFIX.'/groups/get?access_token='.$this->access_token);
	}

	/**
	 * 获取用户所在分组
	 * @param string $openid
	 * @return boolean|int 成功则返回用户分组id
	 */
	public function get_user_group($openid){
	    
	    $data = array('openid'=>$openid);

	    $result = $this->http_post(self::API_URL_PREFIX.'/groups/getid?access_token='.$this->access_token,$data);
	    
		return $result['groupid'];
	}

	/**
	 * 新增自定分组
	 * @param string $name 分组名称
	 * @return boolean|array
	 */
	public function create_group($name){
		
		$data = array('group'=>array('name'=>$name));

		return $this->http_post(self::API_URL_PREFIX.'/groups/create?access_token='.$this->access_token,$data);
	}

	/**
	 * 更改分组名称
	 * @param int $groupid 分组id
	 * @param string $name 分组名称
	 * @return boolean|array
	 */
	public function update_group($groupid,$name){
		
		$data = array('group'=>array('id'=>$groupid,'name'=>$name));

		return $this->http_post(self::API_URL_PREFIX.'/groups/update?access_token='.$this->access_token,$data);
	}

	/**
	 * 移动用户分组
	 * @param int $groupid 分组id
	 * @param string $openid 用户openid
	 * @return boolean|array
	 */
	public function update_group_members($groupid,$openid){
		
		$data = array('openid'=>$openid,'to_groupid'=>$groupid);

		return $this->http_post(self::API_URL_PREFIX.'/groups/members/update?access_token='.$this->access_token,$data);
	}

	/**
	 * 批量移动用户分组
	 * @param int $groupid 分组id
	 * @param string $openid_list 用户openid数组,一次不能超过50个
	 * @return boolean|array
	 */
	public function batch_update_group_members($groupid,$openid_list){
		
		$data = array('openid_list'=>$openid_list,'to_groupid'=>$groupid);

		return $this->http_post(self::API_URL_PREFIX.'/groups/members/batchupdate?access_token='.$this->access_token,$data);
	}
}