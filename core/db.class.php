<?php
/*
*数据库抽象类---定义数据库基本功能
*数据库基类应该实现为单例模式
*/
abstract class DB{
	
	public static $_instance=null;
	
	private $_link=null;
	
	protected $prefix='kt_';
	
	/*
	*私有构造函数---不允许NEW对象
	*/
	private function __construct(){
		
	}
	/**
	*	单例模式
	*/
	public static function Instance(){
		$c=get_called_class();
		if(isset(self::$_instance[$c])) return self::$_instance[$c];
		$instance=new $c();
		$instance->connect();
		self::$_instance[$c]=$instance;
		return $instance;
	}
	
	abstract protected function connect($connectstring=null);
	
	
	public function __destruct(){
		self::$_instance=null;
		$this->_link=null;
	}
}

class DBMysql extends DB{
	
	/**
	 * 执行sql语句
	 * @var string
	 */
	protected $sql;

	/*
	*连接
	*/

	protected  function connect($connectstring=null){
		$this->_link=mysqli_init();
		if(!mysqli_real_connect($this->_link,MYSQL_H,MYSQL_U,MYSQL_P,MYSQL_DB)){
			echo mysqli_connect_error();
			exit;
		}
		mysqli_set_charset($this->_link,MYSQL_CHARSET);
	}
	/**
	* 	查询
	*/
	public function find($sql){
		$result=$this->_query($sql);
		$rows=array();
		while($row=mysqli_fetch_assoc($result)){
			$rows[]=$row;
		}
		mysqli_free_result($result);
		return $rows;
	}
	/*
	*	获取单行
	*/
	public function get($sql){
		$result=$this->_query($sql);
		$row=mysqli_fetch_assoc($result);
		mysqli_free_result($result);
		return $row;
	}
	/*
	*	获取总的个数
	*/
	public function count($sql){
		$result=$this->_query($sql);
		$row=mysqli_fetch_array($result,MYSQLI_NUM);
		mysqli_free_result($result);
		return $row[0];
	}
	/**
	*	更新|插入|删除
	*/
	public function query($sql){
		return $this->_query($sql);
	}

	public function call($proc,$params=array()){
		foreach($params as &$param){
	    	substr($param,0,1)!='@' && $param="'".$this->escape($param)."'";
		}
		if($proc=='tt_proc_vendor_order'){
			
			// echo "CALL $proc(".implode(',', $params).")";exit;
		}

		$result=$this->query(sprintf("CALL %s(%s)",$proc,implode(',', $params)));
		mysqli_free_result($result);
	}

	/**
	 * 开启事务
	 * @return boolean true 开启成功 false 失败
	 */
	public function start_trans(){
		if(!mysqli_begin_transaction($this->_link)){
			throw new Exception(sprintf("启动事务失败: %s",mysqli_error($this->_link)), 10003);
		}
		return true;
	}

	/**
	 * 提交事务
	 * @return boolean true 提交成功
	 */
	public function commit(){
		if(!mysqli_commit($this->_link)){
			throw new Exception("提交事务失败", 10004);
		}
		return true;
	}

	/**
	 * 回滚事务
	 * @return boolean true 回滚成功
	 */
	public function rollback(){
		if(!mysqli_rollback($this->_link)){
			throw new Exception("回滚事务失败", 10005);
		}
		return true;
	}

	/**
	 * 过滤特殊字符
	 * @param  string $value 
	 * @return string
	 */
	public function escape($value){
		return mysqli_escape_string($this->_link,$value);
	}

	public function lastID(){
		return mysqli_insert_id($this->_link);
	}
	/**
	*	构造查询
	*/
	private function _query($sql){
		if(!$this->_link){
			$this->connect();
		}
		$query=mysqli_query($this->_link,$sql);
		if(!$query){
			throw new Exception(var_export(array('sql'=>$sql,'error'=>mysqli_error($this->_link))), 10001);
		}
		return $query;
	}
}

class DBMongo extends DB{
	
	CONST HOST='mongodb://localhost:27017';

	//CONST HOST='mongodb://fastty2013:fastty2013mongodb@localhost:27017/fastty';
	//CONST HOST='mongodb://fred:foobar@localhost';
	//CONST HOST='mongodb://fred:foobar@localhost/baz';	#Connect and login to the "baz" database as user "fred" with password "foobar":
	
	protected $_db='fastty';
	
	protected $_mongo;
	
	protected $_mongodb;

	protected $_talbe;		#集合
	
	protected $_set;		#更新值
	
	protected $_options=array();
	
	/**
	*	连接
	*/
	protected function connect($connectstring=self::HOST){
		$this->_mongo=new Mongo($connectstring);
		$this->_mongodb=$this->_mongo->selectDB($this->_db);
	}
	/*
	*	选择库和集合
	*/
	public function selectCollection($db,$collection){
		$this->_collection=$this->_mongo->selectCollection($db,$collection);
	}
	
	/**
	*	查询多条数据记录
	*/
	public function find($params=array()){
		return $this->_setParams($params);
		//return $this->_forCursor();
	}
	/**
	*	获取单个值
	*/
	public function get($params){
		return $this->_setParamsDirectReturn($params);
	}
	
	public function count($params){
		return $this->_setParams($params);
	}
	/**
	*	更新数据 | 插入数据 |  批量插入 |  删除数据
	*/
	public function query($params){
		return $this->_setParamsDirectReturn($params);
	}
	
	/**
	*	设置参数
	*/
	private function _setParams($params){
		$this->init();
		!isset($params['_table']) && exit('没有选定指定的集合！');
		$collection=$this->_mongodb->selectCollection($params['_table']);
		unset($params['_talbe']);
		isset($params['_options']) && $this->_options=array_merge($this->_options,$params['_options']);
		unset($parmas['_options']);
		$cursor=$collection->find($params['find'],$this->_options);
		
		foreach($params as $k=>$v){
			if(method_exists($cursor,$k)){
				if($k=='count'){
					return $cursor->count($params['count']);
				}
				/*else if($k=='fields'){   # 为0是排除，为1是包含
					$fields=array();
					foreach(explode(',',$v) as $field){
						$fields[$field]=true;
					}
					$cursor=$cursor->fields($fields);
				}*/
				else
					$cursor=$cursor->$k($v);
			}
		}
		unset($params);
		$result=array();
		while($doc=$cursor->getNext()){
			$result[]=$doc;
		}
		$cursor=null;
		$collection=null;
		return $result;
	}
	/**
	*	设置那些在集合类中直接返回值的
	*/
	private function _setParamsDirectReturn($params){
		$this->init();
		!isset($params['_table']) && exit('没有选定指定的集合！');
		$collection=$this->_mongodb->selectCollection($params['_table']);#new MongoCollection($this->_mongodb,$params['_talbe']);
		unset($params['_talbe']);
		isset($params['_options']) && $this->_options=array_merge($this->_options,$params['_options']);
		unset($params['_options']);
		//asort($params);
		foreach($params as $k=>$v){
			if(method_exists($collection,$k)){
				if($k=='findOne'){
					$fields=isset($params['fields'])?$params['fields']:array();
					$data=$collection->findOne($v,$fields);
				}
				else if($k!='update'){
					$data=$collection->$k($v,$this->_options);
				}
				else
					$data=$collection->$k($v,$params['_set'],$this->_options);
				
				unset($params);
				return $data;
			}
		}
	}
	
	private function init(){
		$this->_talbe='';
		$this->_set=array();
		$this->_options=array();
	}
}

class DBRedis extends DB{

	CONST HOST='localhost',PORT=6379,TIMEOUT=10;
		
	protected $_redis=null;

	public function getRedis(){
		$this->_redis===null && $this->connect();
		return $this->_redis;
	}
	
	protected function connect($host=self::HOST,$port=self::PORT,$timeout=self::TIMEOUT){
		$this->_redis=new Redis();
		$this->_redis->pconnect($host,$port,$timeout);
	}

}