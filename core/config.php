<?php

// base path
define('LIB', ROOT.'/library/');

define('CORE', LIB.'core/');
define('PLUGIN', LIB.'plugin/');
define('EXTENSION', LIB.'extension/');
define('CACHE', LIB.'cache/');

define('SESSION', CACHE.'session');
define('LOG', CACHE.'log/');
// cache
define('COMPILE_DIR', CACHE.'compile/');
define('CACHE_DIR', CACHE.'html/');
define('HTML_CACHE',FALSE);

define('COOKIE_TIMEOUT',1800); // 30 min

// extension library 
define('SMARTY', EXTENSION.'smarty/');

// cookie  && session

define('COOKIE_DOMAIN',$_SERVER['HTTP_HOST']);
// define('COOKIE_DOMAIN','ticket');
define('COOKIE_ENCRYPT_KEY','tvrc4m@cookie');

// 是否启用pjax无刷新页面请求
define('PAJX_ENABLE', true);

