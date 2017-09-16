<?php

class Lang {

	public static $lang='zh';

	public function __construct($lang='zh'){

		self::$lang=$lang;

		global $langs;

		include_once(LANG.$lang.'/'.$lang.'.php');
	}

	/**
	*	调用Lang静态方法
	*	@param dir Lang下的第一层文件夹名--小写
	*	@param args array args[0]->文件名(无后缀) args[1] 访问属性
	*   @return 返回属性对应的值
	*/

	public static function load($path){

		assert(is_string($path));

		$file=LANG.self::$lang.'/'.$path.'.lang.php';

		list($parent,$cls)=explode('/', $path);
		// echo $file.PHP_EOL;
		if(!is_file($file)) exit('language file not found');
		
		global $langs;
		
		include_once($file);
	}
}