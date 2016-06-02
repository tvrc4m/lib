<?php
/*
 * 控制层
 */
class Action extends View{

	protected $error=array();
	protected $settings=array();
	protected $post=array();
	protected $get=array();
	protected $request=array();
	protected $header=array();
	protected $footer=array();

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
		// $this->setting();
		// $this->debug();
		$this->post=&$_POST;
		$this->get=&$_GET;
		$this->request=&$_REQUEST;
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
	 * 获取基础class类的组合子类
	 * @return array 子类的相对路径+传递的参数数组
	 */
	protected function get_children()
	{
		return array(
			'header'=>array('common/header',array('pTitle'=>$this->title,'pKeyword'=>$this->keyword,'pDesc'=>$this->description,'pCss'=>$this->css,'pJs'=>$this->js,'header'=>$this->header)),
			'footer'=>array('common/footer',array('footer'=>$this->footer)),
		);
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

		if(empty($cls)){
			
			$classname=ucfirst($parent).'Action';
			$file=ACTION.$parent.'.action.php';
		}else{
			$classname=ucfirst($cls).'Action';
			$file=ACTION.$parent.'/'.$cls.'.action.php';
		}
		
		if(!is_file($file)){
			echo $file;
			exit('action file not found');
		} 
		
		include_once($file);

		$cls=new $classname;

		return call_user_func_array(array($cls,$action),array($args));
	}

	public function __get($medium){
		
		static $medium_instances=array();

		list($dir,$name)=explode("_",strtolower($medium),2);

		$name=str_replace('_', '.', $name);

		if(isset($medium_instances[$medium])) return $medium_instances[$medium];

		$file=MEDIUM.$dir.'/'.$name.'.php';

		if (!is_file($file)) {
			
			return $this->$medium;
		}

		include_once $file;

		if(strpos($name,'.')===FALSE){

			$cls=ucfirst($dir).ucfirst($name);
		}else{

			$split=explode('.',$name);
			$cls=ucfirst($dir).ucfirst($split[0]).ucfirst($split[1]);
		}

		$instance=new $cls();

		return $medium_instances[$medium]=$instance;
	}
}

class FAction extends Action{

	protected $children=array('column_left'=>'common/left','content_top'=>'common/top','content_bottom'=>'common/bottom','column_right'=>'common/right','content_middle'=>'common/middle');

	public function __construct(){
		parent::__construct();
	}
}
// 后台需要登录的基础action类
class AdminAction extends Action{

	public function __construct(){
		parent::__construct();
		if(!$this->check('ADMIN')){
			redirect(admin_url('login'));
		}
	}

	public function display($tpl,$cache_id=null,$compile_id=null,$suffix='.tpl'){
		$this->assign(array('pTitle'=>$this->title));
		parent::display($tpl,$cache_id,$compile_id,$suffix);
	}
}
// 前台需要登录的基础action类
class VAction extends Action{

	protected $children=array('column_left'=>'common/left','content_top'=>'common/top','content_bottom'=>'common/bottom','column_right'=>'common/right');

	public function __construct(){
		parent::__construct();
		if(!$this->check('VENDOR')){
			redirect(vendor_url('login'));
		}
	}
}

/**
* 只面向action只有一级目录结构的项目基础action类
*/
class SingleAction extends Action
{
	
	function __construct(){
		parent::__construct();
	}

	protected function get_children(){
		return array(
			'header'=>array('header',array('pTitle'=>$this->title,'pKeyword'=>$this->keyword,'pDesc'=>$this->description,'pCss'=>$this->css,'pJs'=>$this->js)),
			'footer'=>'footer',
		);
	}
}

/**
* App页面
*/
class AppAction extends Action
{

	/**
	 * 用来判断浏览器的实例对象
	 * @var object
	 */
	protected $browser;

	/**
	 * 记录回退按钮需要回退的步数,默认为-1
	 * @var integer
	 */
	protected $history_back=-1;
	/**
	 * 标识用户是否已登录
	 * @var boolean
	 */
	protected $is_login;

	function __construct(){
		parent::__construct();
		$this->header['show_navbar']=1;
		$this->footer['hide_tabbar']=1;
		$this->browser_history();
		$this->browser_detected();
		$this->is_login=(S(SESSION_USER_ID) && S(SESSION_USER));
	}

	/**
	 * 记录每次post请求，后退的步数递减
	 * 默认后退步数为-1
	 * !import 表单提交中需要传递history_back回来
	 * !TODO 如何更好的在表单中写入history_back
	 * @return
	 */
	protected function browser_history(){
		if($_SERVER['REQUEST_METHOD']=='POST'){
			$this->history_back=(int)$this->post['history_back'];
			$this->history_back--;
		}
		$this->assign(array('history_back'=>$this->history_back));
	}

	protected function browser_detected(){
		include_once CORE."browser.class.php";
		$this->browser=new Browser();
	}

	protected function get_children(){
		return array(
			'header'=>array('common/header',array(
				'pTitle'=>$this->title,
				'pKeyword'=>$this->keyword,
				'pDesc'=>$this->description,
				'pCss'=>$this->css,
				'pJs'=>$this->js,
				'header'=>array_merge(array(
					'title'=>$this->title,
					'left_navbar'=>array(
						'href'=>sprintf("javascript:goBack(%d);",(int)$this->history_back),
						'icon'=>'fa-chevron-left'
					),
					'is_weixin'=>$this->browser->weixin,
					'is_ios'=>$this->browser->ios,
					'is_andriod'=>$this->browser->andriod,
					),
				$this->header))),
			'footer'=>array('common/footer',array('footer'=>$this->footer)),
		);
	}
}

class AppAuthAction extends AppAction{

	/**
	 * 用户对象实例
	 * @var object
	 */	
	protected $user;

	/**
	 * 当前登录用户id
	 * @var int
	 */
	protected $user_id;

	/**
	 * 当前用户切换的wedding_id
	 * @var int
	 */
	protected $wedding_id;


	public function __construct(){

		parent::__construct();

		if(!$this->is_login){

			$back= $_SERVER['HTTPS']?'https://':'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

			redirect('/login?back='.urlencode($back));
		}

		$this->user_id=S(SESSION_USER_ID);

		$this->user=S(SESSION_USER);

		$this->wedding_id=$this->user['wedding_id'];
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