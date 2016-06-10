<?php

include_once(EXTENSION . 'phpmailer/class.phpmailer.php');

class SmsPlugin extends Plugin{

	public function run($options){


	}

	private function sms_code($phone,$code){

		include_once EXTENSION."alidayu/TopClient.php";
		include_once EXTENSION."alidayu/request/AlibabaAliqinFcSmsNumSendRequest.php";

		$c = new TopClient;
		$c ->appkey = ALIDAYU_APPID ;
		$c ->secretKey = ALIDAYU_APPSECRET ;
		$req = new AlibabaAliqinFcSmsNumSendRequest;
		$req ->setExtend( "" );
		$req ->setSmsType( "normal" );
		$req ->setSmsFreeSignName( "" );
		$req ->setSmsParam(array('code'=>$code));
		$req ->setRecNum($phone);
		$req ->setSmsTemplateCode("SMS_10650290");
		$resp = $c ->execute( $req );
	}
}