<?php
/*
 * 控制层
 */
class Action extends View{

	protected $error=array();
	protected $settings=array();
	// protected $children=array('top'=>'common/top','bottom'=>'common/bottom','left'=>'common/left','right'=>'common/right');

	public function __construct(){

		parent::__construct();
		$this->initialize();
	}

	protected function initialize(){
		//检测登录cookie
		// P('checkLogin');
		$this->view->registerPlugin('block','lrtip','smarty_block_lrtip',false);
		$this->view->registerPlugin('block','top','smarty_block_top',false);
		$this->view->registerPlugin('block','toplr','smarty_block_toplr',false);
		// 加载setting配置
		$this->setting();
		$this->debug();
	}

	protected function setting(){

		$settings=M('setting/setting','all',array());
		$this->settings=$settings;
		
		foreach ($settings as $key => $value) {
			V(strtolower($key),$value);
		}

		$this->assign($settings);
	}

	protected function debug(){

		if(V('config_error_display')){
			ini_set('display_errors','On');
			error_reporting(E_ALL & ~E_NOTICE);	
		}
	}

	protected function filter($name){
		if (isset($_GET[$name])) {
			return  htmlspecialchars_decode($_GET[$name]);
		}
		return null;
	}

	protected function flushform($request,$result,$form=array()){

		foreach ($form as $field) {
			if(isset($request[$field])){
				$this->assign(array($field=>$request[$field]));
			}else if(isset($result[$field])){
                $this->assign(array($field=>$result[$field]));
			}else{
				$this->assign(array($field=>''));
			}
		}

		$this->assign(array('errors'=>$this->error));
	}

	public function check($sk){

		$app=strtolower($_GET['app']);

		$ignore=array('login','logout');

		foreach ($ignore as $v) {

			if($v==$app) return true;
		}

		return !!(S('LOGGED') && S($sk));
	}

	/**
	*	调用Action静态方法
	*	@param dir Medium下的第一层文件夹名--小写
	*	@param args array args[0]->文件名(无后缀) args[1]->run具体方法参数数组
	*   @return run方法的结果值
	*/

	public static function run($path,$args=array()){

		assert(is_string($path));
		assert(is_array($args));

		list($parent,$cls,$action)=explode('/', $path);

		empty($action) && $action='index';

		$classname=ucfirst($cls).'Action';

		$file=ACTION.$parent.'/'.$cls.'.action.php';
		// echo $file.PHP_EOL;
		if(!is_file($file)) exit('action file not found');
		
		include_once($file);

		$cls=new $classname;

		return call_user_func_array(array($cls,$action),array($args));
	}
}

class FAction extends Action{

	protected $children=array('column_left'=>'common/left','content_top'=>'common/top','content_bottom'=>'common/bottom','column_right'=>'common/right','content_middle'=>'common/middle');

	public function __construct(){
		parent::__construct();
	}
}

class AdminAction extends Action{

	public function __construct(){
		parent::__construct();
		if(!$this->check('ADMIN')){
			redirect(admin_url('login'));
		}
	}
}

class VAction extends Action{

	protected $children=array('column_left'=>'common/left','content_top'=>'common/top','content_bottom'=>'common/bottom','column_right'=>'common/right');

	public function __construct(){
		parent::__construct();
		if(!$this->check('VENDOR')){
			redirect(vendor_url('login'));
		}
	}
}

function smarty_block_lrtip($param, $content, &$smarty) {
	return $content;
}

function smarty_block_top($param, $content, &$smarty) {
	return $content;
}

function smarty_block_toplr($param, $content, &$smarty) {
	return $content;
}