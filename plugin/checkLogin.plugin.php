<?php

class CheckLoginPlugin extends Plugin{

	protected $session_uid;
	protected $session_user;
	protected $cookie_logged;

	public function run($data){
		if(defined('IS_ADMIN')){
			$this->session_uid='AID';
			$this->session_user="ADMIN";
			$this->cookie_logged="ADMINLOGGED";
		}else{
			$this->session_uid="UID";
			$this->session_user="USER";
			$this->cookie_logged="LOGGED";
		}
		$uid=S($this->session_uid);
		$logged=C($this->cookie_logged);
		$logged=decrypt($logged);
		$array=explode(';', $logged);
		if($uid && !empty($array)){
			if($_SERVER['HTTP_HOST']==$array[0] && getip()==$array[1] && (int)$array[3]+COOKIE_TIMEOUT>time() && $uid==$array[2]){
				$this->_setCookie($array[2]);
				return 1;
			}
		}else if(!empty($array)){
			if($_SERVER['HTTP_HOST']==$array[0] && getip()==$array[1] && (int)$array[3]+COOKIE_TIMEOUT>time()){
				if(defined('IS_ADMIN'))
					$userinfo=M::admin('admin','get',array('uid'=>$array[2]));
				else
					$userinfo=SEA::account('user','get.by.uid',array('uid'=>$array[2]));
				if(!empty($userinfo)){
					$this->_setSession($userinfo);
					$this->_setCookie($userinfo['uid']);
					return 1;
				}
			}
		}
		$this->_clear();
		return 0;
	}

	private function _setSession($user){
		S($this->session_user,$user);
		S($this->session_uid,$user['uid']);
	}

	private function _setCookie($uid){
		$ip=getip();
		$time=time();
		$logged="{$_SERVER['HTTP_HOST']};{$ip};{$uid};{$time}";
		C($this->cookie_logged,encrypt($logged));
	}
	private function _clear(){
		C($this->cookie_logged,null);
		S($this->session_uid,null);
		if(file_exists(ROOT.'/session/sess_'.session_id()))
			session_destroy();
	}
}