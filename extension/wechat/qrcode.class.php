<?php

namespace WeChat;

class Qrcode  extends Auth{

	const QRCODE_IMG_URL='https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=';


	/**
	 * 创建二维码ticket
	 * @param int|string $scene_id 自定义追踪id,临时二维码只能用数值型
	 * @param int $type 0:临时二维码；1:数值型永久二维码(此时expire参数无效)；2:字符串型永久二维码(此时expire参数无效)
	 * @param int $expire 临时二维码有效期，最大为604800秒
	 * @return array('ticket'=>'qrcode字串','expire_seconds'=>604800,'url'=>'二维码图片解析后的地址')
	 */
	public function get_qrcode($scene_id,$type=0,$expire=604800){

		if (!isset($scene_id) || !is_numeric($scene_id)) return false;

		$access_token=$this->get_access_token();

	    if(empty($access_token)) return false;

		switch ($type) {
			case '0':
				$action_name = 'QR_SCENE';
				$action_info = array('scene'=>(array('scene_id'=>$scene_id)));
				break;

			case '1':
				$action_name = 'QR_LIMIT_SCENE';
				$action_info = array('scene'=>(array('scene_id'=>$scene_id)));
				break;

			case '2':
				$action_name = 'QR_LIMIT_STR_SCENE';
				$action_info = array('scene'=>(array('scene_str'=>$scene_id)));
				break;

			default:
				return false;
		}

		$data = array(
			'action_name'    => $action_name,
			'expire_seconds' => $expire,
			'action_info'    => $action_info
		);

		if($type)  unset($data['expire_seconds']);

		$result = $this->http_post(self::API_URL_PREFIX.'/qrcode/create?access_token='.$access_token,$data);
		
		return $result;
	}

	/**
	 * 获取二维码图片
	 * @param string $ticket 传入由getQRCode方法生成的ticket参数
	 * @return string url 返回http地址
	 */
	public function get_qr_url($ticket) {

		return self::QRCODE_IMG_URL.urlencode($ticket);
	}

	/**
	 * 长链接转短链接接口
	 * @param string $long_url 传入要转换的长url
	 * @return boolean|string url 成功则返回转换后的短url
	 */
	public function get_short_url($long_url){

	    $access_token=$this->get_access_token();

	    if(empty($access_token)) return false;

	    $data = array(
            'action'=>'long2short',
            'long_url'=>$long_url
	    );

	    $result = $this->http_post(self::API_URL_PREFIX.'/shorturl?access_token='.$access_token,$data);

		return $json['short_url'];
	}
}
