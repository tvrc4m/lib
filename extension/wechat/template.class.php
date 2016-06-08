<?php

namespace WeChat;

class Template  extends Auth{

	public function __construct(){

		parent::__construct();

		$this->access_token=$this->get_access_token();

	    if(empty($this->access_token)) return false;
	}

	/**
	 * 模板消息 添加消息模板
	 * 成功返回消息模板的调用id
	 * @param string $tpl_id 模板库中模板的编号，有“TM**”和“OPENTMTM**”等形式
	 * @return boolean|string
	 */
	public function add_template($tpl_id){

	    $data = array ('template_id_short' =>$tpl_id);

	    $json = $this->http_post(self::API_URL_PREFIX.'/message/template/api_add_template?access_token='.$this->access_token,$data);
	    
		return $json['template_id'];
	}

	/**
	 * 发送模板消息
	 * @param array $data 消息结构
	 * ｛
			"touser":"OPENID",
			"template_id":"ngqIpbwh8bUfcSsECmogfXcV14J0tQlEpBO27izEYtY",
			"url":"http://weixin.qq.com/download",
			"topcolor":"#FF0000",
			"data":{
				"参数名1": {
					"value":"参数",
					"color":"#173177"	 //参数颜色
					},
				"Date":{
					"value":"06月07日 19时24分",
					"color":"#173177"
					},
				"CardNumber":{
					"value":"0426",
					"color":"#173177"
					},
				"Type":{
					"value":"消费",
					"color":"#173177"
					}
			}
		}
	 * @return boolean|array
	 */
	public function send_template($data){

		return $this->http_post(self::API_URL_PREFIX.'/message/template/send?access_token='.$this->access_token,$data);
	}
}