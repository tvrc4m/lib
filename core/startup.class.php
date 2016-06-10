<?php

header('Content-type:text/html;charset=utf-8');
// 错误级别
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

if (!defined("TIME_ZONE")) {
	date_default_timezone_set('Asia/Shanghai');
}


include_once(CORE.'function.php');
include_once(CORE.'common.php');
include_once(CORE.'code.inc.php');
include_once(CORE.'admin.func.php');
include_once(CORE.'db.class.php');
include_once(CORE.'model.class.php');
include_once(CORE.'view.class.php');
include_once(CORE.'action.class.php');
include_once(CORE.'medium.class.php');
include_once(CORE.'lang.class.php');
include_once(CORE.'plugin.class.php');
include_once(CORE.'alias.class.php');
include_once(CORE.'exception.class.php');