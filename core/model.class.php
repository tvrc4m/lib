<?php
/*
 * 模块类
 */
abstract class Model{
    
	protected $_db=null;
	
	protected $_cache=null;

	protected static $instance=null;
	
	protected static $_instances=array();

	public function __construct(){
		//$this->_cache=MemoryCache::Instance();	
	}
	/**
	*	单例模式
	*/
	public static function Instance(){
		$c=get_called_class();
		if(isset(self::$_instances[$c])) return self::$_instances[$c];
		self::$instance=new $c();
		self::$instance->init();
		self::$_instances[$c]=self::$instance;
		return self::$instance;
	}
	/**
	*	Model子类
	*/
	public static function getStorges(){
		return array('MysqlModel'=>'mysql','MongoModel'=>'mongo','SphinxModel'=>'sphinx','RedisModel'=>'redis');
	}

	/**
	* Model静态方法调用
	* @param dir Model目录下的目录
	* @param args[0]->文件名(不带type.php),args[1]->要调用的方法,args[2]->调用方法的参数
	* @return 原方法返回值
	*/

	public static function __callStatic($dir,$args){

		assert(is_string($args[1]));
		assert(is_array($args[2]));

		$storges=self::getStorges();

		$storge=$storges[get_called_class()];

		$classname=ucfirst($args[0]).ucfirst($storge);
		// echo $classname.PHP_EOL;
		
		$file=(isset($GLOBALS['USE_CATALOG']) && $GLOBALS['USE_CATALOG']==1?CATALOG.'model/':MODEL).$dir.'/'.$args[0].'.model.php';
		
		$GLOBALS['USE_CATALOG']=0;

		// echo $file.PHP_EOL;exit();

		if(!is_file($file)) exit('model file not found');
		
		include_once($file);
		// echo $classname;exit();
		// print_r($classname::Instance());
		return call_user_func_array(array($classname::Instance(),$args[1]),$args[2]);
	}
	
	abstract protected function init();
	
	/**
	*	设置缓存
	*/
	public function setcache($k,$v,$expiration=3600){
		$this->_cache->set($k,$v,$expiratio);
	}
	
	/**
	*	读取缓存
	*/
	public function getcache($k){
		return $this->_cache->get($k);
	}
	
}
/**
*	MYSQL模块类
*/
class MysqlModel extends Model{

	protected $prefix='tt_';
	
	protected $table='';

	public function __construct(){
		parent::__construct();
		$this->init();
		$this->prefix=DB_PREFIX;
	}
	
	protected function init(){
		$this->_db=DBMysql::Instance();
	}

	// 拼接values部分
	protected function set($sets){
		foreach($sets as $field=>&$value){
			$value="`{$field}`='".$this->escape($value)."'";	
		}
		return ' SET '.implode(',',$sets);
	}

	// 拼接where部分
	protected function where($where,$split=' AND '){
		if(empty($where)) return '';
		if(!is_array($where)) return ' WHERE '.$where;
		array_walk($where,function(&$value,$field){
			if(is_array($value))
				$value="`{$field}` IN (".implode(',',$value).")";
			else
				$value="`{$field}`='{$value}'";
		});

		return ' WHERE '.implode($split,$where);
	}

	protected function field($fields){
		if(empty($fields)) return '*';
		if(!is_array($fields)) return $fields;
		return implode(',', $fields);
	}

	protected function sort($sort){
		if(!is_array($sort) || empty($sort)) return '';
		return ' ORDER BY '.implode(',', $sort);
	}

	protected function limit($limit){
		if(empty($limit)) return '';
		if(is_array($limit)) $limit=implode(',', $limit);
		return ' LIMIT '.$limit;
	}

	protected function group($group){
		if(empty($group)) return '';
		if(is_array($group)) $group=implode(',', $group);
		return ' GROUP BY '.$group;
	}

	/*
	*	插入新数据
	*/
	protected function insert($data){
		if(empty($data)) return 0;
		$values=$this->set($data);
		$this->query("INSERT INTO ".$this->prefix.$this->table." {$values}");
		return $this->lastID();
	}
	/**
	*	适合where是并集的情况下
	*/
	protected function update($data,$where){
		if(empty($data)) return 0;
		$values=$this->set($data);
		if(empty($values)) return;
		$where=$this->where($where);
		return $this->query("UPDATE ".$this->prefix.$this->table." {$values} {$where}");
	}

	protected function select($params=array()){
		$field=$this->field($params['field']);
		$where=$this->where($params['where']);
		$sort=$this->sort($params['sort']);
		$group=$this->group($params['group']);
		$limit=$this->limit($params['limit']);
		// echo "SELECT {$field} FROM ".$this->prefix.$this->table." {$where} {$group} {$sort} {$limit}";
		return $this->find("SELECT {$field} FROM ".$this->prefix.$this->table." {$where} {$group} {$sort} {$limit}");
	}

	protected function delete($where){
		$where=$this->where($where);
		return $this->query("DELETE FROM ".$this->prefix.$this->table." {$where}");	
	}

	protected function replace($data,$where){
		if(empty($data)) return 0;
		$values=$this->set($data);
		$where=$this->where($where);
		return $this->query("REPLACE INTO ".$this->prefix.$this->table." {$values} {$where}");
	}

	protected function one($params=array()){
		$result=$this->select(array('where'=>$params));
		return $result[0];
	}

	public function find($sql){
		return $this->_db->find($sql);
	}
	
	public function query($sql){
		return $this->_db->query($sql);
	}
	
	public function get($sql){
		return $this->_db->get($sql);
	}
	
	public function count($sql){
		return $this->_db->count($sql);
	}

	public function call($proc,$params){

		return $this->_db->call($proc,$params);
	}

	public function escape($value){
		return $this->_db->escape($value);
	}

	public function lastID(){
		return $this->_db->lastID();
	}
}
/**
*	MONGO模块类
*/
class MongoModel extends Model{
	
	protected $table='';
	
	protected function init(){
		$this->_db=DBMongo::Instance();
	}
	
	public function find($params){
		$this->_setTable($params);
		return $this->_db->find($params);
	}
	
	public function get($params){
		$this->_setTable($params);
		return $this->_db->get($params);
	}
	
	public function query($params){
		$this->_setTable($params);
		return $this->_db->query($params);
	}

	public function count($params){
		$this->_setTable($params);
		return $this->_db->count($params);
	}

	public function removeNull($where,$field){

		$params=array('update'=>$where,'_set'=>array('$pullAll'=>array($field=>array(null))));
		
		return $this->query($params);
	}

	public function extradata($id,$field,$data,$count,$tongji,$otherSetDate=array()){

		$ret=$this->get(array('findOne'=>array('_id'=>$id),'fields'=>array("{$field}"=>1)));
		
		$total=count($ret[$field]);

		$setData=array_merge(array('$push'=>array("{$field}"=>$data),'$inc'=>array("{$tongji}"=>1)),$otherSetDate);

		$addparams=array('update'=>array('_id'=>$id),'_set'=>$setData,'_options'=>array('upsert'=>1));	
		
		if($total<$count){

			return $this->query($addparams);	#直接添加

		}else{

			$setData=array_merge(array('$pop'=>array("{$field}"=>-1)),$otherSetDate);#删除头部的数据

			$params=array('update'=>array('_id'=>$id),'_set'=>$setData);

			$this->query($params);

			return $this->query($addparams);
		}
	}
	/**
	*	设置table名
	*/
	protected function _setTable(&$params){	
		if($this->table && !isset($params['_table'])){
			$params['_table']=$this->table;
		}
	}
	
}

class SphinxModel extends Model{

	protected $index;

	protected static $queries=array();

	protected $filters=array();

	// protected 

	protected function init(){

		$this->_db=new Coreseek();

	}
	public static function run(){
		return Coreseek::Instance()->run();
		// return $this->_db->run();
	}
	/**
	*	单条搜索---立即执行返回数据
	*	@param data array|string 查询词
	*	@param params array 属性设置数组
	*	@param split string 查询词分割符
	*	@return array 返回数组
	*/
	public function find($data=array(),$params=array(),$split=' '){
		$this->_db->init();
		$this->_setParams($params);
		$keyword=$this->_setKeyword($data);
		return $this->_db->Query($keyword);
	}

	/**
	*	返回一条数据信息---立即执行返回数据----单一数组
	*	@param data array|string 查询词
	*	@param params array 属性设置数组
	*	@param split string 查询词分割符
	*	@return array 返回数组
	*/
	public function get($data=array(),$param=array(),$split=' '){
		$result=$this->find($data,$param,$split);
		if(isset($result['value']) && !empty($result['value']))
			return $result['value'][0];
		return array();
	}

	
	public function one($id){
		$this->_db->init();
		$this->_db->setParmas(array('_max_id'=>$id,'_min_id'=>$id,'_index'=>$this->index));
		$result = $this->_db->Query('');
		if(isset($result['value']) && !empty($result['value']))
			return $result['value'][0];
		return array();
	}

	/**
	*	添加到批量查询数组中
	*	@param sign 此查询语句结果标识
	*	@param data 查询关键词或数组
	*	@param params array 过滤数组
	*	@param split 
	*	@return null
	*/
	public function add($sign,$data=array(),$params=array(),$split=' '){
		$params['_filters']=$this->filters;
		Coreseek::Instance()->add($sign,$data,$params,$split);
	}

	public function summary($keywords,$index='*'){

		return $this->_db->BuildKeywords($keywords,$index);
	}
	/**
	*	设置属性
	*	@params array 跟类字段一一对应
	*/
	private function _setParams($params){
		!isset($params['_index']) && $params['_index']=$this->index;
		$params['_filters']=$this->filters;
		$this->_db->setParmas($params);
	}
	/*
	*	设置查询词
	*	@param data array 查询
	*	@return string 查询词
	*	@return string 查询词
	*/
	private function _setKeyword($data){
		$keyword='';
		if(is_array($data) && !empty($data))
			foreach($data as $k=>$v) !empty($v) && $keyword.=empty($kv)?' '.$v.' ':' '.$k.' '.$v.' ';
		else if(is_string($data)) $keyword=$data;
		return $keyword;
	}
	/**
	*	设置过虑值或范围
	*/
	private function _setFilters($filters){
		foreach($filters as $k=>$v)
			!empty($v) && $this->_filters[]['type']=$k=='values'?SPH_FILTER_VALUES:($k=='range'?SPH_FILTER_RANGE:SPH_FILTER_FLOATRANGE);
	}

	public function addFilter($type,$attr,$values,$exclude=false){
		$this->filters[]=array("type"=>$type, "attr"=>$attr, "exclude"=>$exclude, "values"=>$values);
	}
}

class RedisModel extends Model{

	protected function init(){
		$this->_db=DBRedis::Instance();
	}

	/**
	*	调用redis内置方法
	*	@param method string redis内置方法
	*	@param args array 方法对应的参数
	*/
	public function __call($method,$args){
		if(method_exists($this->_db->getRedis(),$method)) 
			return call_user_func_array(array($this->_db->getRedis(),$method),$args);
		else
			exit('no this '.$method);
	}
}