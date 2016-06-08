<?php

namespace WeChat;

class CustomerService extends Auth{

	public function __construct(){

		parent::__construct();

		$this->access_token=$this->get_access_token();

	    if(empty($this->access_token)) return false;
	}

	/**
	 * 获取多客服会话记录
	 * @param array $data 数据结构{"starttime":123456789,"endtime":987654321,"openid":"OPENID","pagesize":10,"pageindex":1,}
	 * @return boolean|array
	 */
	public function get_custom_service_message($data){
		
		$result = $this->http_post(self::API_URL_PREFIX.'/customservice/getrecord?access_token='.$this->access_token,$data);
		
		return $result;
	}

	/**
	 * 转发多客服消息
	 * Example: $obj->transfer_customer_service($customer_account)->reply();
	 * @param string $customer_account 转发到指定客服帐号：test1@test
	 */
	public function transfer_customer_service($customer_account = ''){

		$msg = array(
			'ToUserName' => $this->getRevFrom(),
			'FromUserName'=>$this->getRevTo(),
			'CreateTime'=>time(),
			'MsgType'=>'transfer_customer_service',
		);
		if ($customer_account) {
			$msg['TransInfo'] = array('KfAccount'=>$customer_account);
		}
		$this->Message($msg);
		return $this;
	}

	/**
	 * 获取多客服客服基本信息
	 *
	 * @return boolean|array
	 */
	public function list_custom_service(){

		$result = $this->http_get(self::API_URL_PREFIX.'/customservice/getkflist?access_token='.$this->access_token);
		
		return $result;
	}


	/**
	 * 发送客服消息
	 * @param array $data 消息结构{"touser":"OPENID","msgtype":"news","news":{...}}
	 * @return boolean|array
	 */
	public function send_custom_message($data){
		
		return $this->http_post(self::API_URL_PREFIX.'/message/custom/send?access_token='.$this->access_token,$data);
	}

	/**
	 * 获取多客服在线客服接待信息
	 *
	 * @return boolean|array {
	 "kf_online_list": [
	 {
	 "kf_account": "test1@test",	//客服账号@微信别名
	 "status": 1,			//客服在线状态 1：pc在线，2：手机在线,若pc和手机同时在线则为 1+2=3
	 "kf_id": "1001",		//客服工号
	 "auto_accept": 0,		//客服设置的最大自动接入数
	 "accepted_case": 1		//客服当前正在接待的会话数
	 }
	 ]
	 }
	 */
	public function getCustomServiceOnlineKFlist(){

	    $result = $this->http_get(self::API_URL_PREFIX.'/customservice/getonlinekflist?access_token='.$this->access_token);
	    
	    return $result;
	}

	/**
	 * 创建指定多客服会话
	 * @tutorial 当用户已被其他客服接待或指定客服不在线则会失败
	 * @param string $openid           //用户openid
	 * @param string $kf_account     //客服账号
	 * @param string $text                 //附加信息，文本会展示在客服人员的多客服客户端，可为空
	 * @return boolean | array            //成功返回json数组
	 * {
	 *   "errcode": 0,
	 *   "errmsg": "ok",
	 * }
	 */
	public function create_session($openid,$kf_account,$text=''){

	    $data=array(
	    	"openid" =>$openid,
	        "kf_account" => $kf_account
	    );

	    if ($text) $data["text"] = $text;
	    	
	    $result = $this->http_post(self::API_BASE_URL_PREFIX.'/customservice/kfsession/create?access_token='.$this->access_token,$data);
	    
	    return $result;
	}

	/**
	 * 关闭指定多客服会话
	 * @tutorial 当用户被其他客服接待时则会失败
	 * @param string $openid           //用户openid
	 * @param string $kf_account     //客服账号
	 * @param string $text                 //附加信息，文本会展示在客服人员的多客服客户端，可为空
	 * @return boolean | array            //成功返回json数组
	 * {
	 *   "errcode": 0,
	 *   "errmsg": "ok",
	 * }
	 */
	public function close_session($openid,$kf_account,$text=''){

	    $data=array(
	    	"openid" =>$openid,
	        "kf_account" => $kf_account
	    );

	    if ($text) $data["text"] = $text;

	    $result = $this->http_post(self::API_BASE_URL_PREFIX.'/customservice/kfsession/close?access_token='.$this->access_token,$data);
	    
	    return $result;
	}

	/**
	 * 获取用户会话状态
	 * @param string $openid           //用户openid
	 * @return boolean | array            //成功返回json数组
	 * {
	 *     "errcode" : 0,
	 *     "errmsg" : "ok",
	 *     "kf_account" : "test1@test",    //正在接待的客服
	 *     "createtime": 123456789,        //会话接入时间
	 *  }
	 */
	public function get_session($openid){
	    
	    $result = $this->http_get(self::API_BASE_URL_PREFIX.'/customservice/kfsession/getsession?access_token='.$this->access_token.'&openid='.$openid);
	    
	    return $result;
	}

	/**
	 * 获取指定客服的会话列表
	 * @param string $openid           //用户openid
	 * @return boolean | array            //成功返回json数组
	 *  array(
	 *     'sessionlist' => array (
	 *         array (
	 *             'openid'=>'OPENID',             //客户 openid
	 *             'createtime'=>123456789,  //会话创建时间，UNIX 时间戳
	 *         ),
	 *         array (
	 *             'openid'=>'OPENID',             //客户 openid
	 *             'createtime'=>123456789,  //会话创建时间，UNIX 时间戳
	 *         ),
	 *     )
	 *  )
	 */
	public function get_session_list($kf_account){
	    
	    $result = $this->http_get(self::API_BASE_URL_PREFIX.'/customservice/kfsession/getsessionlist?access_token='.$this->access_token.'&kf_account='.$kf_account);
	    
	    return $result;
	}

	/**
	 * 获取未接入会话列表
	 * @param string $openid           //用户openid
	 * @return boolean | array            //成功返回json数组
	 *  array (
	 *     'count' => 150 ,                            //未接入会话数量
	 *     'waitcaselist' => array (
	 *         array (
	 *             'openid'=>'OPENID',             //客户 openid
	 *             'kf_account ' =>'',                   //指定接待的客服，为空则未指定
	 *             'createtime'=>123456789,  //会话创建时间，UNIX 时间戳
	 *         ),
	 *         array (
	 *             'openid'=>'OPENID',             //客户 openid
	 *             'kf_account ' =>'',                   //指定接待的客服，为空则未指定
	 *             'createtime'=>123456789,  //会话创建时间，UNIX 时间戳
	 *         )
	 *     )
	 *  )
	 */
	public function get_session_wait(){
	    
	    $result = $this->http_get(self::API_BASE_URL_PREFIX.'/customservice/kfsession/getwaitcase?access_token='.$this->access_token);
	    
	    return $result;
	}

	/**
	 * 添加客服账号
	 *
	 * @param string $account      //完整客服账号，格式为：账号前缀@公众号微信号，账号前缀最多10个字符，必须是英文或者数字字符
	 * @param string $nickname     //客服昵称，最长6个汉字或12个英文字符
	 * @param string $password     //客服账号明文登录密码，会自动加密
	 * @return boolean|array
	 * 成功返回结果
	 * {
	 *   "errcode": 0,
	 *   "errmsg": "ok",
	 * }
	 */
	public function add_account($account,$nickname,$password){

	    $data=array(
	    	"kf_account" =>$account,
	        "nickname" => $nickname,
	        "password" => md5($password)
	    );
		
		$result = $this->http_post(self::API_BASE_URL_PREFIX.'/customservice/kfaccount/add?access_token='.$this->access_token,$data);
		
		return $result;
	}

	/**
	 * 修改客服账号信息
	 *
	 * @param string $account      //完整客服账号，格式为：账号前缀@公众号微信号，账号前缀最多10个字符，必须是英文或者数字字符
	 * @param string $nickname     //客服昵称，最长6个汉字或12个英文字符
	 * @param string $password     //客服账号明文登录密码，会自动加密
	 * @return boolean|array
	 * 成功返回结果
	 * {
	 *   "errcode": 0,
	 *   "errmsg": "ok",
	 * }
	 */
	public function update_account($account,$nickname,$password){

	    $data=array(
	            "kf_account" =>$account,
	            "nickname" => $nickname,
	            "password" => md5($password)
	    );

	    $result = $this->http_post(self::API_BASE_URL_PREFIX.'/customservice/kfaccount/update?access_token='.$this->access_token,$data);
	    
	    return $result;
	}

	/**
	 * 删除客服账号
	 *
	 * @param string $account      //完整客服账号，格式为：账号前缀@公众号微信号，账号前缀最多10个字符，必须是英文或者数字字符
	 * @return boolean|array
	 * 成功返回结果
	 * {
	 *   "errcode": 0,
	 *   "errmsg": "ok",
	 * }
	 */
	public function delete_account($account){
	    
	    $result = $this->http_get(self::API_BASE_URL_PREFIX.'/customservice/kfaccount/del?access_token='.$this->access_token.'&kf_account='.$account);
	    
	    return $result;
	}

	/**
	 * 上传客服头像
	 *
	 * @param string $account //完整客服账号，格式为：账号前缀@公众号微信号，账号前缀最多10个字符，必须是英文或者数字字符
	 * @param string $imgfile //头像文件完整路径,如：'D:\user.jpg'。头像文件必须JPG格式，像素建议640*640
	 * @return boolean|array
	 * 成功返回结果
	 * {
	 *   "errcode": 0,
	 *   "errmsg": "ok",
	 * }
	 */
	public function set_account_head_img($account,$imgfile){
	    
	    $result = $this->http_post(self::API_BASE_URL_PREFIX.'/customservice/kfaccount/uploadheadimg?access_token='.$this->access_token.'&kf_account='.$account,array('media'=>'@'.$imgfile),true);
	    
	    return $result;
	}

	/**
	 * 语义理解接口
	 * @param String $uid      用户唯一id（非开发者id），用户区分公众号下的不同用户（建议填入用户openid）
	 * @param String $query    输入文本串
	 * @param String $category 需要使用的服务类型，多个用“，”隔开，不能为空
	 * @param Float $latitude  纬度坐标，与经度同时传入；与城市二选一传入
	 * @param Float $longitude 经度坐标，与纬度同时传入；与城市二选一传入
	 * @param String $city     城市名称，与经纬度二选一传入
	 * @param String $region   区域名称，在城市存在的情况下可省略；与经纬度二选一传入
	 * @return boolean|array
	 */
	public function querySemantic($uid,$query,$category,$latitude=0,$longitude=0,$city="",$region=""){
	    if (!$this->access_token && !$this->checkAuth()) return false;
	    $data=array(
	            'query' => $query,
	            'category' => $category,
	            'appid' => $this->appid,
	            'uid' => ''
	    );
	    //地理坐标或城市名称二选一
	    if ($latitude) {
	        $data['latitude'] = $latitude;
	        $data['longitude'] = $longitude;
	    } elseif ($city) {
	        $data['city'] = $city;
	    } elseif ($region) {
	        $data['region'] = $region;
	    }
	    $result = $this->http_post(self::API_BASE_URL_PREFIX.self::SEMANTIC_API_URL.'access_token='.$this->access_token,self::json_encode($data));
	    if ($result)
	    {
	        $json = json_decode($result,true);
	        if (!$json || !empty($json['errcode'])) {
	            $this->errCode = $json['errcode'];
	            $this->errMsg = $json['errmsg'];
	            return false;
	        }
	        return $json;
	    }
	    return false;
	}
}
