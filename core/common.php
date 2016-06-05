<?php

function C($key,$value=''){
	if(is_null($value)) setcookie($key,'',time()-3600,'/',COOKIE_DOMAIN);
	elseif(empty($value)) return $_COOKIE[$key];
	else setcookie($key,$value,time()+COOKIE_TIMEOUT,'/',COOKIE_DOMAIN);
}

/**
*	存储或提取会话数据----当value为空时，为提取数据。不为空，则进行保存。
*	@param key string 键
*	@param value 字符串或数字等简单类型
*/
function S($key,$value=''){
	if(is_null($value)) unset($_SESSION[$key]);
	elseif($value==='') return $_SESSION[$key];
	else $_SESSION[$key]=$value;
	// print_r($_SESSION);exit;
}

function N($link=''){
	$key='navigation';
	if (is_null($link))  unset($_SESSION[$key]);
	elseif($link===''){
		$navigation_bar=unserialize($_SESSION[$key]);
		// print_r($navigation_bar);exit;
		if (empty($navigation_bar))
			return '/';
		else{
			$current=array_pop($navigation_bar);
			$_SESSION[$key]=serialize($navigation_bar);
			return $current;
		}
	}else{
		$navigation_bar=unserialize($_SESSION[$key]);
		empty($navigation_bar) && $navigation_bar=array();
		if(end($navigation_bar)==$link) return;
		array_push($navigation_bar, $link);
		// print_r($navigation_bar);exit;
		$_SESSION[$key]=serialize($navigation_bar);
	}
}

/**
*	调用插件	
**/
function P($class,$data=array()){
	$classname=ucfirst($class).'Plugin';
	$file=PLUGIN.$class.'.plugin.php';
	!is_file($file) && exit('文件不存在.请检查路径');
	include_once($file);
	$instance=new $classname;
	return $instance->run($data);
}

function L($key,$value=null){
	global $langs;
	return $value?$langs[$key]=$value:$langs[$key];
}

function M($path,$action,$data=array()){

	static $medium_instances=array();

	list($dir,$name)=explode("/",$path);

	$key=strtolower($dir.$name);

	$name=strtolower($name);

	$instance=null;

	if(isset($medium_instances[$key])){

		$instance= $medium_instances[$key];
	}else{

		include_once MEDIUM.strtolower($path).'.php';

		if(strpos($name,'.')===FALSE){

			$cls=ucfirst($dir).ucfirst($name);
		}else{

			$split=explode('.',$name);
			$cls=ucfirst($dir).ucfirst($split[0]).ucfirst($split[1]);
		}

		$instance=new $cls();

		$medium_instances[$key]=$instance;
	}

	return $instance->run($action,$data);
}

function D($path){

	static $model_instances=array();

	list($dir,$name)=explode("/",$path);

	$key=strtolower($dir.$name);

	$name=strtolower($name);

	if(isset($model_instances[$key])) return $model_instances[$key];

	include_once MODEL.strtolower($path).'.model.php';

	if(strpos($name,'.')===FALSE){

		$cls=ucfirst($dir).ucfirst($name).'Model';
	}else{

		$split=explode('.',$name);
		$cls=ucfirst($dir).ucfirst($split[0]).ucfirst($split[1]).'Model';
	}

	$instance=new $cls();

	return $model_instances[$key]=$instance;
}

/*
	获取setting中定义的值
 */
function V($key,$value=NULL){
	static $settings=array();
	$key=strtolower($key);
	if($value==NULL){
		return $settings[$key];
	}else{
		$settings[$key]=$value;
	}
	return $settings;
}

function http($method,$url,$data){

	$ch=curl_init();

	curl_setopt($ch,CURLOPT_TIMEOUT,60);

	curl_setopt($ch,CURLOPT_HEADER,0);

	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)");

	if(strtolower($method)=='get'){

		if(!empty($data)) $url.='?'.http_build_query($data);
	}else if(strtolower($method)=='post'){

		curl_setopt($ch, CURLOPT_POST, true);

		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	}
	// echo $url.PHP_EOL;
	curl_setopt($ch, CURLOPT_URL, $url);

	if( ! $result = curl_exec($ch)) {
		$error=curl_error($ch);
	}
	// print_r($result);
	curl_close($ch);

	return $result;
}

function isLogged(){
	return !!S('LOGGED');
}

function update_user_session($user){

	$uid=$user['id'];

	S(SESSION_USER_ID,$uid);

	S(SESSION_USER,$user);

	$ip=getip();

	$time=time();

	$logged="{$_SERVER['HTTP_HOST']};{$ip};{$uid};{$time}";
	
	C('LOGGED',encrypt($logged));
}

