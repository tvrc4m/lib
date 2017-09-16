<?php

// base path
define('STATIC', ROOT.'/static/');
define('IMG', ROOT.'/image/');
define('LIB', ROOT.'/library/');

define('CORE', LIB.'core/');
define('PLUGIN', LIB.'plugin/');
define('EXTENSION', LIB.'extension/');
define('CACHE', LIB.'cache/');
define('CONFIG', LIB.'config/');
define('LIB_MEDIUM',LIB.'medium/');
define('LIB_MODEL',LIB.'model/');


define('SESSION', CACHE.'session');
define('LOG', CACHE.'log/');

define('ACTION', HOME.'/action/');
define('MEDIUM', LIB.'medium/');
define('MODEL', LIB.'model/');
define('LANG', HOME.'/lang/');



define('COOKIE_TIMEOUT',1800); // 30 min

define('ADMIN_IMAGE',sprintf('http://%s/static/admin/image/',$_SERVER['HTTP_HOST']));

// extension library 
define('SMARTY', EXTENSION.'smarty/');

// cache
define('COMPILE_DIR', CACHE.'compile/');
define('CACHE_DIR', CACHE.'html/');
define('HTML_CACHE',FALSE);

// cookie  && session

define('COOKIE_DOMAIN',$_SERVER['HTTP_HOST']);
// define('COOKIE_DOMAIN','ticket');
define('COOKIE_ENCRYPT_KEY','tvrc4m@cookie');


include_once(CONFIG.'db.config.php');

