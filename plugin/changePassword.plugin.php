<?php

class ChangePasswordPlugin extends Plugin{

	public function run($data){

		$uid=S('LOGGED');

		$vendor=S('VENDOR');

		$route=$_GET['app'];

		$filter=array('change','logout','login');

		foreach ($filter as $f) {

			if($f==$route) return true;
		}

		if(!empty($uid) && $vendor){

			if(empty($vendor['changed'])){

				redirect(vendor_url('change/password'));
			}
		}

		return true;
	}
}