<?php

function admin_url($url='',$params=array()){

	$query=empty($params)?'':'?'.http_build_query($params);
	
	return sprintf("http://%s/admin/%s%s",$_SERVER['HTTP_HOST'],$url,$query);
}

function vendor_url($url='',$params=array()){

	$query=empty($params)?'':'?'.http_build_query($params);

	return sprintf("http://%s/vendor/%s%s",$_SERVER['HTTP_HOST'],$url,$query);
}

function shop_url($url='',$params=array()){

	$query=empty($params)?'':'?'.http_build_query($params);

	return sprintf("http://%s/shop/%s%s",$_SERVER['HTTP_HOST'],$url,$query);
}