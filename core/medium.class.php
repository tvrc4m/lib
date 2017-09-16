<?php
/****	中间件类	***/

abstract class Medium{

	public function __construct(){

	}

	/**
	*	调用Medium静态方法
	*	@param dir Medium下的第一层文件夹名--小写
	*	@param args array args[0]->文件名(无后缀) args[1]->run具体方法参数数组
	*   @return run方法的结果值
	*/

	public static function __callStatic($dir,$args){

		assert(is_string($args[0]));
		assert(is_string($args[1]));
		assert(is_array($args[2]));
		
		$classname=ucfirst($args[0]);

		$file=MEDIUM.$dir.'/'.$args[0].'.php';

		// echo $classname.$file.PHP_EOL;
		
		if(!is_file($file)) exit('medium file not found');
		
		include_once($file);

		$cls=new $classname;

		return $cls->run($args[1],$args[2]);
	}

	/**
	*	Medium子类的标准入口方法
	*	@param data 参数数组 
	*/
	abstract public function run($action,$data);
}

/**
*	Sphinx Medium
*/
class SphinxMedium extends Medium{

	// sphinx 索引结果标识
	protected $_sign;

	public function __construct(){

		parent::__construct();

	}

	public function run($action,$data){

		$this->_sign=$data['sign'];

		empty($this->_sign) && exit('sign值为空');
	}
}