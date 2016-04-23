<?php

define ( "SEARCHD_COMMAND_SEARCH",		0 );
define ( "SEARCHD_COMMAND_EXCERPT",		1 );
define ( "SEARCHD_COMMAND_UPDATE",		2 );
define ( "SEARCHD_COMMAND_KEYWORDS",	3 );
define ( "SEARCHD_COMMAND_PERSIST",		4 );
define ( "SEARCHD_COMMAND_STATUS",		5 );
define ( "SEARCHD_COMMAND_FLUSHATTRS",	7 );

/// current client-side command implementation versions
define ( "VER_COMMAND_SEARCH",		0x119 );
define ( "VER_COMMAND_EXCERPT",		0x103 );
define ( "VER_COMMAND_UPDATE",		0x102 );
define ( "VER_COMMAND_KEYWORDS",	0x100 );
define ( "VER_COMMAND_STATUS",		0x100 );
define ( "VER_COMMAND_QUERY",		0x100 );
define ( "VER_COMMAND_FLUSHATTRS",	0x100 );

/// known searchd status codes
define ( "SEARCHD_OK",				0 );
define ( "SEARCHD_ERROR",			1 );
define ( "SEARCHD_RETRY",			2 );
define ( "SEARCHD_WARNING",			3 );

/// known match modes
define ( "SPH_MATCH_ALL",			0 );
define ( "SPH_MATCH_ANY",			1 );
define ( "SPH_MATCH_PHRASE",		2 );
define ( "SPH_MATCH_BOOLEAN",		3 );
define ( "SPH_MATCH_EXTENDED",		4 );
define ( "SPH_MATCH_FULLSCAN",		5 );
define ( "SPH_MATCH_EXTENDED2",		6 );	// extended engine V2 (TEMPORARY, WILL BE REMOVED)

/// known ranking modes (ext2 only)
define ( "SPH_RANK_PROXIMITY_BM25",	0 );	///< default mode, phrase proximity major factor and BM25 minor one
define ( "SPH_RANK_BM25",			1 );	///< statistical mode, BM25 ranking only (faster but worse quality)
define ( "SPH_RANK_NONE",			2 );	///< no ranking, all matches get a weight of 1
define ( "SPH_RANK_WORDCOUNT",		3 );	///< simple word-count weighting, rank is a weighted sum of per-field keyword occurence counts
define ( "SPH_RANK_PROXIMITY",		4 );
define ( "SPH_RANK_MATCHANY",		5 );
define ( "SPH_RANK_FIELDMASK",		6 );
define ( "SPH_RANK_SPH04",			7 );
define ( "SPH_RANK_EXPR",			8 );
define ( "SPH_RANK_TOTAL",			9 );

/// known sort modes
define ( "SPH_SORT_RELEVANCE",		0 );
define ( "SPH_SORT_ATTR_DESC",		1 );
define ( "SPH_SORT_ATTR_ASC",		2 );
define ( "SPH_SORT_TIME_SEGMENTS", 	3 );
define ( "SPH_SORT_EXTENDED", 		4 );
define ( "SPH_SORT_EXPR", 			5 );

/// known filter types
define ( "SPH_FILTER_VALUES",		0 );
define ( "SPH_FILTER_RANGE",		1 );
define ( "SPH_FILTER_FLOATRANGE",	2 );

/// known attribute types
define ( "SPH_ATTR_INTEGER",		1 );
define ( "SPH_ATTR_TIMESTAMP",		2 );
define ( "SPH_ATTR_ORDINAL",		3 );
define ( "SPH_ATTR_BOOL",			4 );
define ( "SPH_ATTR_FLOAT",			5 );
define ( "SPH_ATTR_BIGINT",			6 );
define ( "SPH_ATTR_STRING",			7 );
define ( "SPH_ATTR_MULTI",			0x40000001 );
define ( "SPH_ATTR_MULTI64",			0x40000002 );

/// known grouping functions
define ( "SPH_GROUPBY_DAY",			0 );
define ( "SPH_GROUPBY_WEEK",		1 );
define ( "SPH_GROUPBY_MONTH",		2 );
define ( "SPH_GROUPBY_YEAR",		3 );
define ( "SPH_GROUPBY_ATTR",		4 );
define ( "SPH_GROUPBY_ATTRPAIR",	5 );


/// pack 64-bit signed
function sphPackI64 ( $v )
{
	//assert ( is_numeric($v) );
	
	// x64
	if ( PHP_INT_SIZE>=8 )
	{
		$v = (int)$v;
		return pack ( "NN", $v>>32, $v&0xFFFFFFFF );
	}

	// x32, int
	if ( is_int($v) )
		return pack ( "NN", $v < 0 ? -1 : 0, $v );

	// x32, bcmath	
	if ( function_exists("bcmul") )
	{
		if ( bccomp ( $v, 0 ) == -1 )
			$v = bcadd ( "18446744073709551616", $v );
		$h = bcdiv ( $v, "4294967296", 0 );
		$l = bcmod ( $v, "4294967296" );
		return pack ( "NN", (float)$h, (float)$l ); // conversion to float is intentional; int would lose 31st bit
	}

	// x32, no-bcmath
	$p = max(0, strlen($v) - 13);
	$lo = abs((float)substr($v, $p));
	$hi = abs((float)substr($v, 0, $p));

	$m = $lo + $hi*1316134912.0; // (10 ^ 13) % (1 << 32) = 1316134912
	$q = floor($m/4294967296.0);
	$l = $m - ($q*4294967296.0);
	$h = $hi*2328.0 + $q; // (10 ^ 13) / (1 << 32) = 2328

	if ( $v<0 )
	{
		if ( $l==0 )
			$h = 4294967296.0 - $h;
		else
		{
			$h = 4294967295.0 - $h;
			$l = 4294967296.0 - $l;
		}
	}
	return pack ( "NN", $h, $l );
}

/// pack 64-bit unsigned
function sphPackU64 ( $v )
{
	//assert ( is_numeric($v) );
	
	// x64
	if ( PHP_INT_SIZE>=8 )
	{
		assert ( $v>=0 );
		
		// x64, int
		if ( is_int($v) )
			return pack ( "NN", $v>>32, $v&0xFFFFFFFF );
						  
		// x64, bcmath
		if ( function_exists("bcmul") )
		{
			$h = bcdiv ( $v, 4294967296, 0 );
			$l = bcmod ( $v, 4294967296 );
			return pack ( "NN", $h, $l );
		}
		
		// x64, no-bcmath
		$p = max ( 0, strlen($v) - 13 );
		$lo = (int)substr ( $v, $p );
		$hi = (int)substr ( $v, 0, $p );
	
		$m = $lo + $hi*1316134912;
		$l = $m % 4294967296;
		$h = $hi*2328 + (int)($m/4294967296);

		return pack ( "NN", $h, $l );
	}

	// x32, int
	if ( is_int($v) )
		return pack ( "NN", 0, $v );
	
	// x32, bcmath
	if ( function_exists("bcmul") )
	{
		$h = bcdiv ( $v, "4294967296", 0 );
		$l = bcmod ( $v, "4294967296" );
		return pack ( "NN", (float)$h, (float)$l ); // conversion to float is intentional; int would lose 31st bit
	}

	// x32, no-bcmath
	$p = max(0, strlen($v) - 13);
	$lo = (float)substr($v, $p);
	$hi = (float)substr($v, 0, $p);
	
	$m = $lo + $hi*1316134912.0;
	$q = floor($m / 4294967296.0);
	$l = $m - ($q * 4294967296.0);
	$h = $hi*2328.0 + $q;

	return pack ( "NN", $h, $l );
}

// unpack 64-bit unsigned
function sphUnpackU64 ( $v )
{
	list ( $hi, $lo ) = array_values ( unpack ( "N*N*", $v ) );

	if ( PHP_INT_SIZE>=8 )
	{
		if ( $hi<0 ) $hi += (1<<32); // because php 5.2.2 to 5.2.5 is totally fucked up again
		if ( $lo<0 ) $lo += (1<<32);

		// x64, int
		if ( $hi<=2147483647 )
			return ($hi<<32) + $lo;

		// x64, bcmath
		if ( function_exists("bcmul") )
			return bcadd ( $lo, bcmul ( $hi, "4294967296" ) );

		// x64, no-bcmath
		$C = 100000;
		$h = ((int)($hi / $C) << 32) + (int)($lo / $C);
		$l = (($hi % $C) << 32) + ($lo % $C);
		if ( $l>$C )
		{
			$h += (int)($l / $C);
			$l  = $l % $C;
		}

		if ( $h==0 )
			return $l;
		return sprintf ( "%d%05d", $h, $l );
	}

	// x32, int
	if ( $hi==0 )
	{
		if ( $lo>0 )
			return $lo;
		return sprintf ( "%u", $lo );
	}

	$hi = sprintf ( "%u", $hi );
	$lo = sprintf ( "%u", $lo );

	// x32, bcmath
	if ( function_exists("bcmul") )
		return bcadd ( $lo, bcmul ( $hi, "4294967296" ) );
	
	// x32, no-bcmath
	$hi = (float)$hi;
	$lo = (float)$lo;
	
	$q = floor($hi/10000000.0);
	$r = $hi - $q*10000000.0;
	$m = $lo + $r*4967296.0;
	$mq = floor($m/10000000.0);
	$l = $m - $mq*10000000.0;
	$h = $q*4294967296.0 + $r*429.0 + $mq;

	$h = sprintf ( "%.0f", $h );
	$l = sprintf ( "%07.0f", $l );
	if ( $h=="0" )
		return sprintf( "%.0f", (float)$l );
	return $h . $l;
}

// unpack 64-bit signed
function sphUnpackI64 ( $v )
{
	list ( $hi, $lo ) = array_values ( unpack ( "N*N*", $v ) );

	// x64
	if ( PHP_INT_SIZE>=8 )
	{
		if ( $hi<0 ) $hi += (1<<32); // because php 5.2.2 to 5.2.5 is totally fucked up again
		if ( $lo<0 ) $lo += (1<<32);

		return ($hi<<32) + $lo;
	}

	// x32, int
	if ( $hi==0 )
	{
		if ( $lo>0 )
			return $lo;
		return sprintf ( "%u", $lo );
	}
	// x32, int
	elseif ( $hi==-1 )
	{
		if ( $lo<0 )
			return $lo;
		return sprintf ( "%.0f", $lo - 4294967296.0 );
	}
	
	$neg = "";
	$c = 0;
	if ( $hi<0 )
	{
		$hi = ~$hi;
		$lo = ~$lo;
		$c = 1;
		$neg = "-";
	}	

	$hi = sprintf ( "%u", $hi );
	$lo = sprintf ( "%u", $lo );

	// x32, bcmath
	if ( function_exists("bcmul") )
		return $neg . bcadd ( bcadd ( $lo, bcmul ( $hi, "4294967296" ) ), $c );

	// x32, no-bcmath
	$hi = (float)$hi;
	$lo = (float)$lo;
	
	$q = floor($hi/10000000.0);
	$r = $hi - $q*10000000.0;
	$m = $lo + $r*4967296.0;
	$mq = floor($m/10000000.0);
	$l = $m - $mq*10000000.0 + $c;
	$h = $q*4294967296.0 + $r*429.0 + $mq;
	if ( $l==10000000 )
	{
		$l = 0;
		$h += 1;
	}

	$h = sprintf ( "%.0f", $h );
	$l = sprintf ( "%07.0f", $l );
	if ( $h=="0" )
		return $neg . sprintf( "%.0f", (float)$l );
	return $neg . $h . $l;
}


function sphFixUint ( $value )
{
	if ( PHP_INT_SIZE>=8 )
	{
		// x64 route, workaround broken unpack() in 5.2.2+
		if ( $value<0 ) $value += (1<<32);
		return $value;
	}
	else
	{
		// x32 route, workaround php signed/unsigned braindamage
		return sprintf ( "%u", $value );
	}
}



class SphinxClient
{
	protected $_host;	
	protected $_port;	
	protected $_offset;
	protected $_limit;
	protected $_mode;	
	protected $_weights;
	protected $_sort;	
	protected $_sortby;
	protected $_min_id;
	protected $_max_id;
	protected $_filters;
	protected $_groupby;
	protected $_groupfunc;	
	protected $_groupsort;	
	protected $_groupdistinct;
	protected $_maxmatches;	
	protected $_cutoff;
	protected $_retrycount;	
	protected $_retrydelay;	
	protected $_anchor;
	protected $_indexweights;	
	protected $_ranker;
	protected $_rankexpr;
	protected $_maxquerytime;	
	protected $_fieldweights;	
	protected $_overrides;	
	protected $_select;

	protected $_error;
	protected $_warning;
	protected $_connerror;

	protected $_reqs;	
	protected $_mbenc;
	protected $_arrayresult;	
	protected $_timeout;
	
	//新增
	protected $_index;
	
	public static $_instance=null;

	public function __construct ()
	{
		$this->init();
		$this->Open();
	}
	
	/*
	* 单例模式----唯一实例
	*/
	public static function Instance(){
		if(self::$_instance==null){
			$c=get_called_class();
			self::$_instance=new $c();
			//self::$_instance->_index=$index;
		}
		return self::$_instance;
	}
	
	public function connect(){
		$this->init();
		$this->Open();
	}
	
	public function init(){
		// per-client-object settings
		$this->_host		= SE_HOST;
		$this->_port		= SE_PORT;
		$this->_path		= false;
		$this->_socket		= false;

		// per-query settings
		$this->_offset		= 0;
		$this->_limit		= 40;
		$this->_mode		= SPH_MATCH_EXTENDED2;	#扩展查询语法
		$this->_weights		= array ();
		$this->_sort		= SPH_SORT_RELEVANCE;
		$this->_sortby		= "";
		$this->_min_id		= 0;
		$this->_max_id		= 0;
		$this->_filters		= array ();
		$this->_groupby		= "";
		$this->_groupfunc	= SPH_GROUPBY_ATTR ;
		$this->_groupsort	= "@group desc";
		$this->_groupdistinct= "";
		$this->_maxmatches	= 1000;
		$this->_cutoff		= 0;
		$this->_retrycount	= 1; 	#重试一次
		$this->_retrydelay	= 500; 	#毫秒
		$this->_anchor		= array ();
		$this->_indexweights= array ();
		$this->_ranker		= SPH_RANK_PROXIMITY_BM25;
		$this->_rankexpr	= "";
		$this->_maxquerytime= 0;
		$this->_fieldweights= array();
		$this->_overrides 	= array();
		$this->_select		= "*";

		$this->_error		= ""; // per-reply fields (for single-query case)
		$this->_warning		= "";
		$this->_connerror	= false;

		$this->_reqs		= array ();	// requests storage (for multi-query case)
		$this->_mbenc		= "";
		$this->_arrayresult	= true;
		$this->_timeout		= 0;
		
		$this->_index='*';
	}

	public function setParmas($params){
		foreach($params as $key=>$value){
			!property_exists($this,$key) && exit('请检查此属性是否存在');
			$this->$key =$value;
		}
	}

	public function __destruct()
	{
		if ( $this->_socket !== false )
			fclose ( $this->_socket );
	}

	
	function GetLastError ()
	{
		return $this->_error;
	}

	
	function GetLastWarning ()
	{
		return $this->_warning;
	}

	function IsConnectError()
	{
		return $this->_connerror;
	}

	
	function SetServer ( $host, $port = 0 )
	{
		//assert ( is_string($host) );
		if ( $host[0] == '/')
		{
			$this->_path = 'unix://' . $host;
			return;
		}
		if ( substr ( $host, 0, 7 )=="unix://" )
		{
			$this->_path = $host;
			return;
		}
				
		//assert ( is_int($port) );
		$this->_host = $host;
		$this->_port = $port;
		$this->_path = '';

	}

	
	function SetConnectTimeout ( $timeout )
	{
		assert ( is_numeric($timeout) );
		$this->_timeout = $timeout;
	}


	function _Send ( $handle, $data, $length )
	{
		if ( feof($handle) || fwrite ( $handle, $data, $length ) !== $length )
		{
			$this->_error = 'connection unexpectedly closed (timed out?)';
			$this->_connerror = true;
			return false;
		}
		return true;
	}

	function _MBPush ()
	{
		$this->_mbenc = "";
		if ( ini_get ( "mbstring.func_overload" ) & 2 )
		{
			$this->_mbenc = mb_internal_encoding();
			mb_internal_encoding ( "latin1" );
		}
    }

	
	function _MBPop ()
	{
		if ( $this->_mbenc )
			mb_internal_encoding ( $this->_mbenc );
	}

	
	function _Connect ()
	{
		if ( $this->_socket!==false )
		{
			if ( !@feof ( $this->_socket ) )
				return $this->_socket;

			// force reopen
			$this->_socket = false;
		}

		$errno = 0;
		$errstr = "";
		$this->_connerror = false;

		if ( $this->_path )
		{
			$host = $this->_path;
			$port = 0;
		}
		else
		{
			$host = $this->_host;
			$port = $this->_port;
		}

		if ( $this->_timeout<=0 )
			$fp = @fsockopen ( $host, $port, $errno, $errstr );
		else
			$fp = @fsockopen ( $host, $port, $errno, $errstr, $this->_timeout );
		
		if ( !$fp )
		{
			if ( $this->_path )
				$location = $this->_path;
			else
				$location = "{$this->_host}:{$this->_port}";
			
			$errstr = trim ( $errstr );
			$this->_error = "connection to $location failed (errno=$errno, msg=$errstr)";
			$this->_connerror = true;
			return false;
		}
		if ( !$this->_Send ( $fp, pack ( "N", 1 ), 4 ) )
		{
			fclose ( $fp );
			$this->_error = "failed to send client protocol version";
			return false;
		}

		// check version
		list(,$v) = unpack ( "N*", fread ( $fp, 4 ) );
		$v = (int)$v;
		if ( $v<1 )
		{
			fclose ( $fp );
			$this->_error = "expected searchd protocol version 1+, got version '$v'";
			return false;
		}

		return $fp;
	}

	
	function _GetResponse ( $fp, $client_ver )
	{
		$response = "";
		$len = 0;

		$header = fread ( $fp, 8 );
		if ( strlen($header)==8 )
		{
			list ( $status, $ver, $len ) = array_values ( unpack ( "n2a/Nb", $header ) );
			$left = $len;
			while ( $left>0 && !feof($fp) )
			{
				$chunk = fread ( $fp, min ( 8192, $left ) );
				if ( $chunk )
				{
					$response .= $chunk;
					$left -= strlen($chunk);
				}
			}
		}
		if ( $this->_socket === false )
			fclose ( $fp );

		// check response
		$read = strlen ( $response );
		if ( !$response || $read!=$len )
		{
			$this->_error = $len
				? "failed to read searchd response (status=$status, ver=$ver, len=$len, read=$read)"
				: "received zero-sized searchd response";
			return false;
		}

		// check status
		if ( $status==SEARCHD_WARNING )
		{
			list(,$wlen) = unpack ( "N*", substr ( $response, 0, 4 ) );
			$this->_warning = substr ( $response, 4, $wlen );
			return substr ( $response, 4+$wlen );
		}
		if ( $status==SEARCHD_ERROR )
		{
			$this->_error = "searchd error: " . substr ( $response, 4 );
			return false;
		}
		if ( $status==SEARCHD_RETRY )
		{
			$this->_error = "temporary searchd error: " . substr ( $response, 4 );
			return false;
		}
		if ( $status!=SEARCHD_OK )
		{
			$this->_error = "unknown status code '$status'";
			return false;
		}

		// check version
		if ( $ver<$client_ver )
		{
			$this->_warning = sprintf ( "searchd command v.%d.%d older than client's v.%d.%d, some options might not work",
				$ver>>8, $ver&0xff, $client_ver>>8, $client_ver&0xff );
		}
		return $response;
	}

	
	
	
	function SetGroupDistinct ( $attribute )
	{
		assert ( is_string($attribute) );
		$this->_groupdistinct = $attribute;
	}

	function SetOverride ( $attrname, $attrtype, $values )
	{

		$this->_overrides[$attrname] = array ( "attr"=>$attrname, "type"=>$attrtype, "values"=>$values );
	}

	function Query ( $query, $comment="" )
	{
		//assert ( empty($this->_reqs) );

		$this->AddQuery ( $query, $comment );
		$results = $this->RunQueries ();
		$this->_reqs = array (); // just in case it failed too early

		if ( !is_array($results) )
			return false; // probably network error; error message should be already filled
		
		return $results[0];
	}

	
	function _PackFloat ( $f )
	{
		$t1 = pack ( "f", $f ); // machine order
		list(,$t2) = unpack ( "L*", $t1 ); // int in machine order
		return pack ( "N", $t2 );
	}

	function AddQuery ( $query, $comment="" )
	{
		// mbstring workaround
		$this->_MBPush ();

		// build request
		$req = pack ( "NNNN", $this->_offset, $this->_limit, $this->_mode, $this->_ranker );
		if ( $this->_ranker==SPH_RANK_EXPR )
			$req .= pack ( "N", strlen($this->_rankexpr) ) . $this->_rankexpr;
		$req .= pack ( "N", $this->_sort ); // (deprecated) sort mode
		$req .= pack ( "N", strlen($this->_sortby) ) . $this->_sortby;
		$req .= pack ( "N", strlen($query) ) . $query; // query itself
		$req .= pack ( "N", count($this->_weights) ); // weights
		foreach ( $this->_weights as $weight )
			$req .= pack ( "N", (int)$weight );
		$req .= pack ( "N", strlen($this->_index) ) . $this->_index; // indexes
		$req .= pack ( "N", 1 ); // id64 range marker
		$req .= sphPackU64 ( $this->_min_id ) . sphPackU64 ( $this->_max_id ); // id64 range

		// filters
		$req .= pack ( "N", count($this->_filters) );
		foreach ( $this->_filters as $filter )
		{
			$req .= pack ( "N", strlen($filter["attr"]) ) . $filter["attr"];
			$req .= pack ( "N", $filter["type"] );
			switch ( $filter["type"] )
			{
				case SPH_FILTER_VALUES:
					$req .= pack ( "N", count($filter["values"]) );
					foreach ( $filter["values"] as $value )
						$req .= sphPackI64 ( $value );
					break;

				case SPH_FILTER_RANGE:
					$req .= sphPackI64 ( $filter["min"] ) . sphPackI64 ( $filter["max"] );
					break;

				case SPH_FILTER_FLOATRANGE:
					$req .= $this->_PackFloat ( $filter["min"] ) . $this->_PackFloat ( $filter["max"] );
					break;

				default:
					break;//assert ( 0 && "internal error: unhandled filter type" );
			}
			$req .= pack ( "N", $filter["exclude"] );
		}

		// group-by clause, max-matches count, group-sort clause, cutoff count
		$req .= pack ( "NN", $this->_groupfunc, strlen($this->_groupby) ) . $this->_groupby;
		$req .= pack ( "N", $this->_maxmatches );
		$req .= pack ( "N", strlen($this->_groupsort) ) . $this->_groupsort;
		$req .= pack ( "NNN", $this->_cutoff, $this->_retrycount, $this->_retrydelay );
		$req .= pack ( "N", strlen($this->_groupdistinct) ) . $this->_groupdistinct;

		// anchor point
		if ( empty($this->_anchor) )
		{
			$req .= pack ( "N", 0 );
		} else
		{
			$a =& $this->_anchor;
			$req .= pack ( "N", 1 );
			$req .= pack ( "N", strlen($a["attrlat"]) ) . $a["attrlat"];
			$req .= pack ( "N", strlen($a["attrlong"]) ) . $a["attrlong"];
			$req .= $this->_PackFloat ( $a["lat"] ) . $this->_PackFloat ( $a["long"] );
		}

		// per-index weights
		$req .= pack ( "N", count($this->_indexweights) );
		foreach ( $this->_indexweights as $idx=>$weight )
			$req .= pack ( "N", strlen($idx) ) . $idx . pack ( "N", $weight );

		// max query time
		$req .= pack ( "N", $this->_maxquerytime );

		// per-field weights
		$req .= pack ( "N", count($this->_fieldweights) );
		foreach ( $this->_fieldweights as $field=>$weight )
			$req .= pack ( "N", strlen($field) ) . $field . pack ( "N", $weight );

		// comment
		$req .= pack ( "N", strlen($comment) ) . $comment;

		// attribute overrides
		$req .= pack ( "N", count($this->_overrides) );
		foreach ( $this->_overrides as $key => $entry )
		{
			$req .= pack ( "N", strlen($entry["attr"]) ) . $entry["attr"];
			$req .= pack ( "NN", $entry["type"], count($entry["values"]) );
			foreach ( $entry["values"] as $id=>$val )
			{

				$req .= sphPackU64 ( $id );
				switch ( $entry["type"] )
				{
					case SPH_ATTR_FLOAT:	$req .= $this->_PackFloat ( $val ); break;
					case SPH_ATTR_BIGINT:	$req .= sphPackI64 ( $val ); break;
					default:				$req .= pack ( "N", $val ); break;
				}
			}
		}

		// select-list
		$req .= pack ( "N", strlen($this->_select) ) . $this->_select;

		// mbstring workaround
		$this->_MBPop ();

		// store request to requests array
		$this->_reqs[] = $req;
		unset($query);
		return count($this->_reqs)-1;
	}

	
	function RunQueries ()
	{
		if ( empty($this->_reqs) )
		{
			$this->_error = "no queries defined, issue AddQuery() first";
			return false;
		}

		// mbstring workaround
		$this->_MBPush ();

		if (!( $fp = $this->_Connect() ))
		{
			$this->_MBPop ();
			return false;
		}

		// send query, get response
		$nreqs = count($this->_reqs);
		$req = join ( "", $this->_reqs );
		$len = 8+strlen($req);
		$req = pack ( "nnNNN", SEARCHD_COMMAND_SEARCH, VER_COMMAND_SEARCH, $len, 0, $nreqs ) . $req; // add header

		if ( !( $this->_Send ( $fp, $req, $len+8 ) ) ||
			 !( $response = $this->_GetResponse ( $fp, VER_COMMAND_SEARCH ) ) )
		{
			$this->_MBPop ();
			return false;
		}

		// query sent ok; we can reset reqs now
		$this->_reqs = array ();

		// parse and return response
		return $this->_ParseSearchResponse ( $response, $nreqs );
	}

	
	function _ParseSearchResponse ( $response, $nreqs )
	{
		$p = 0; // current position
		$max = strlen($response); // max position for checks, to protect against broken responses
		$results = array ();
		for ( $ires=0; $ires<$nreqs && $p<$max; $ires++ )
		{
			$results[] = array();
			$result =& $results[$ires];

			// extract status
			list(,$status) = unpack ( "N*", substr ( $response, $p, 4 ) ); $p += 4;
			if ( $status!=SEARCHD_OK )
			{
				list(,$len) = unpack ( "N*", substr ( $response, $p, 4 ) ); $p += 4;
				$message = substr ( $response, $p, $len ); $p += $len;

				if ( $status==SEARCHD_WARNING )
				{
					$result["warning"] = $message;
				} else
				{
					$result["error"] = $message;
					continue;
				}
			}
			$result["value"]=array();
			// read schema
			$attrs=array();

			list(,$nfields) = unpack ( "N*", substr ( $response, $p, 4 ) ); $p += 4;
			while ( $nfields-->0 && $p<$max )
			{
				list(,$len) = unpack ( "N*", substr ( $response, $p, 4 ) ); $p += 4;
				$p += $len;
			}

			list(,$nattrs) = unpack ( "N*", substr ( $response, $p, 4 ) ); $p += 4;
			while ( $nattrs-->0 && $p<$max  )
			{
				list(,$len) = unpack ( "N*", substr ( $response, $p, 4 ) ); $p += 4;
				$attr = substr ( $response, $p, $len ); $p += $len;
				list(,$type) = unpack ( "N*", substr ( $response, $p, 4 ) ); $p += 4;
				$attrs[$attr] = $type;
			}

			// read match count
			list(,$count) = unpack ( "N*", substr ( $response, $p, 4 ) ); $p += 4;
			list(,$id64) = unpack ( "N*", substr ( $response, $p, 4 ) ); $p += 4;

			// read matches
			$idx = -1;
			while ( $count-->0 && $p<$max )
			{
				// index into result array
				$idx++;

				// parse document id and weight
				if ( $id64 )
				{
					$doc = sphUnpackU64 ( substr ( $response, $p, 8 ) ); $p += 8;
					list(,$weight) = unpack ( "N*", substr ( $response, $p, 4 ) ); $p += 4;
				}
				else
				{
					list ( $doc, $weight ) = array_values ( unpack ( "N*N*",
						substr ( $response, $p, 8 ) ) );
					$p += 8;
					$doc = sphFixUint($doc);
				}
				$weight = sprintf ( "%u", $weight );

				// create match entry
				if ( $this->_arrayresult )
					$result["value"][$idx] = array ( "id"=>$doc, "weight"=>$weight );
				else
					$result["value"][$doc]["weight"] = $weight;

				// parse and create attributes
				$attrvals = array ();
				foreach ( $attrs as $attr=>$type )
				{
					// handle 64bit ints
					if ( $type==SPH_ATTR_BIGINT )
					{
						$attrvals[$attr] = sphUnpackI64 ( substr ( $response, $p, 8 ) ); $p += 8;
						continue;
					}

					// handle floats
					if ( $type==SPH_ATTR_FLOAT )
					{
						list(,$uval) = unpack ( "N*", substr ( $response, $p, 4 ) ); $p += 4;
						list(,$fval) = unpack ( "f*", pack ( "L", $uval ) ); 
						$attrvals[$attr] = $fval;
						continue;
					}

					// handle everything else as unsigned ints
					list(,$val) = unpack ( "N*", substr ( $response, $p, 4 ) ); $p += 4;
					if ( $type==SPH_ATTR_MULTI )
					{
						$attrvals[$attr] = array ();
						$nvalues = $val;
						while ( $nvalues-->0 && $p<$max )
						{
							list(,$val) = unpack ( "N*", substr ( $response, $p, 4 ) ); $p += 4;
							$attrvals[$attr][] = sphFixUint($val);
						}
					} else if ( $type==SPH_ATTR_MULTI64 )
					{
						$attrvals[$attr] = array ();
						$nvalues = $val;
						while ( $nvalues>0 && $p<$max )
						{
							$val = sphUnpackU64 ( substr ( $response, $p, 8 ) ); $p += 8;
							$attrvals[$attr][] = strval( $val ); // FIXME!!! sphFixUint returns MVA values as string so It to
							$nvalues -= 2;
						}
					} else if ( $type==SPH_ATTR_STRING )
					{
						$attrvals[$attr] = substr ( $response, $p, $val );
						$p += $val;						
					} else
					{
						$attrvals[$attr] = sphFixUint($val);
					}
				}
				
				if ( $this->_arrayresult )
					$result["value"][$idx]=array_merge($result["value"][$idx],$attrvals);
				else
					$result["value"][$doc]= array_merge($result["value"][$doc],$attrvals);
			}

			list ( $total, $total_found, $msecs, $words ) =
				array_values ( unpack ( "N*N*N*N*", substr ( $response, $p, 16 ) ) );
			$result["total"] = sprintf ( "%u", $total );
			$p += 16;
			while ( $words-->0 && $p<$max )
			{
				list(,$len) = unpack ( "N*", substr ( $response, $p, 4 ) ); $p += 4;
				$p += $len;
				$p += 8;
				
			}
		}

		$this->_MBPop ();
		return $results;
	}

	function BuildExcerpts ( $docs,  $words, $opts=array() )
	{
		assert ( is_array($docs) );
		assert ( is_string($words) );
		assert ( is_array($opts) );

		$this->_MBPush ();

		if (!( $fp = $this->_Connect() ))
		{
			$this->_MBPop();
			return false;
		}

		if ( !isset($opts["before_match"]) )		$opts["before_match"] = "<b>";
		if ( !isset($opts["after_match"]) )			$opts["after_match"] = "</b>";
		if ( !isset($opts["chunk_separator"]) )		$opts["chunk_separator"] = " ... ";
		if ( !isset($opts["limit"]) )				$opts["limit"] = 256;
		if ( !isset($opts["limit_passages"]) )		$opts["limit_passages"] = 0;
		if ( !isset($opts["limit_words"]) )			$opts["limit_words"] = 0;
		if ( !isset($opts["around"]) )				$opts["around"] = 5;
		if ( !isset($opts["exact_phrase"]) )		$opts["exact_phrase"] = false;
		if ( !isset($opts["single_passage"]) )		$opts["single_passage"] = false;
		if ( !isset($opts["use_boundaries"]) )		$opts["use_boundaries"] = false;
		if ( !isset($opts["weight_order"]) )		$opts["weight_order"] = false;
		if ( !isset($opts["query_mode"]) )			$opts["query_mode"] = false;
		if ( !isset($opts["force_all_words"]) )		$opts["force_all_words"] = false;
		if ( !isset($opts["start_passage_id"]) )	$opts["start_passage_id"] = 1;
		if ( !isset($opts["load_files"]) )			$opts["load_files"] = false;
		if ( !isset($opts["html_strip_mode"]) )		$opts["html_strip_mode"] = "index";
		if ( !isset($opts["allow_empty"]) )			$opts["allow_empty"] = false;
		if ( !isset($opts["passage_boundary"]) )	$opts["passage_boundary"] = "none";
		if ( !isset($opts["emit_zones"]) )			$opts["emit_zones"] = false;

		// v.1.2 req
		$flags = 1; // remove spaces
		if ( $opts["exact_phrase"] )	$flags |= 2;
		if ( $opts["single_passage"] )	$flags |= 4;
		if ( $opts["use_boundaries"] )	$flags |= 8;
		if ( $opts["weight_order"] )	$flags |= 16;
		if ( $opts["query_mode"] )		$flags |= 32;
		if ( $opts["force_all_words"] )	$flags |= 64;
		if ( $opts["load_files"] )		$flags |= 128;
		if ( $opts["allow_empty"] )		$flags |= 256;
		if ( $opts["emit_zones"] )		$flags |= 512;
		$req = pack ( "NN", 0, $flags ); // mode=0, flags=$flags
		$req .= pack ( "N", strlen($this->_index) ) . $this->_index; // req index
		$req .= pack ( "N", strlen($words) ) . $words; // req words

		// options
		$req .= pack ( "N", strlen($opts["before_match"]) ) . $opts["before_match"];
		$req .= pack ( "N", strlen($opts["after_match"]) ) . $opts["after_match"];
		$req .= pack ( "N", strlen($opts["chunk_separator"]) ) . $opts["chunk_separator"];
		$req .= pack ( "NN", (int)$opts["limit"], (int)$opts["around"] );
		$req .= pack ( "NNN", (int)$opts["limit_passages"], (int)$opts["limit_words"], (int)$opts["start_passage_id"] ); // v.1.2
		$req .= pack ( "N", strlen($opts["html_strip_mode"]) ) . $opts["html_strip_mode"];
		$req .= pack ( "N", strlen($opts["passage_boundary"]) ) . $opts["passage_boundary"];

		// documents
		$req .= pack ( "N", count($docs) );
		foreach ( $docs as $doc )
		{
			//assert ( is_string($doc) );
			$req .= pack ( "N", strlen($doc) ) . $doc;
		}

		$len = strlen($req);
		$req = pack ( "nnN", SEARCHD_COMMAND_EXCERPT, VER_COMMAND_EXCERPT, $len ) . $req; // add header
		if ( !( $this->_Send ( $fp, $req, $len+8 ) ) ||
			 !( $response = $this->_GetResponse ( $fp, VER_COMMAND_EXCERPT ) ) )
		{
			$this->_MBPop ();
			return false;
		}

		$pos = 0;
		$res = array ();
		$rlen = strlen($response);
		for ( $i=0; $i<count($docs); $i++ )
		{
			list(,$len) = unpack ( "N*", substr ( $response, $pos, 4 ) );
			$pos += 4;

			if ( $pos+$len > $rlen )
			{
				$this->_error = "incomplete reply";
				$this->_MBPop ();
				return false;
			}
			$res[] = $len ? substr ( $response, $pos, $len ) : "";
			$pos += $len;
		}

		$this->_MBPop ();
		return $res;
	}

	public function BuildKeywords ( $query,$index,$hits=true )
	{
		assert ( is_string($query) );
		assert ( is_bool($hits) );

		$this->_MBPush ();
		if (!( $fp = $this->_Connect() ))
		{
			$this->_MBPop();
			return false;
		}
		$req  = pack ( "N", strlen($query) ) . $query; // req query
		$req .= pack ( "N", strlen($index) ) . $index; // req index
		$req .= pack ( "N", (int)$hits );

		$len = strlen($req);
		$req = pack ( "nnN", SEARCHD_COMMAND_KEYWORDS, VER_COMMAND_KEYWORDS, $len ) . $req; // add header
		if ( !( $this->_Send ( $fp, $req, $len+8 ) ) ||
			 !( $response = $this->_GetResponse ( $fp, VER_COMMAND_KEYWORDS ) ) )
		{
			$this->_MBPop ();
			return false;
		}

		$pos = 0;
		$res = array ();
		$rlen = strlen($response);
		list(,$nwords) = unpack ( "N*", substr ( $response, $pos, 4 ) );
		$pos += 4;
		for ( $i=0; $i<$nwords; $i++ )
		{
			list(,$len) = unpack ( "N*", substr ( $response, $pos, 4 ) );	$pos += 4;
			$tokenized = $len ? substr ( $response, $pos, $len ) : "";
			$pos += $len;

			list(,$len) = unpack ( "N*", substr ( $response, $pos, 4 ) );	$pos += 4;
			$normalized = $len ? substr ( $response, $pos, $len ) : "";
			$pos += $len;

			$res[] = array ( "tokenized"=>$tokenized, "normalized"=>$normalized );

			if ( $hits )
			{
				list($ndocs,$nhits) = array_values ( unpack ( "N*N*", substr ( $response, $pos, 8 ) ) );
				$pos += 8;
				$res [$i]["docs"] = $ndocs;
				$res [$i]["hits"] = $nhits;
			}

			if ( $pos > $rlen )
			{
				echo "incomplete reply";
				$this->_MBPop ();
				return false;
			}
		}

		$this->_MBPop ();
		return $res;
	}

	function EscapeString ( $string )
	{
		$from = array ( '\\', '(',')','|','-','!','@','~','"','&', '/', '^', '$', '=' );
		$to   = array ( '\\\\', '\(','\)','\|','\-','\!','\@','\~','\"', '\&', '\/', '\^', '\$', '\=' );

		return str_replace ( $from, $to, $string );
	}
	function UpdateAttributes ($attrs, $values, $mva=false )
	{
		// verify everything
		assert ( is_bool($mva) );

		assert ( is_array($attrs) );
		foreach ( $attrs as $attr )
			assert ( is_string($attr) );

		assert ( is_array($values) );
		foreach ( $values as $id=>$entry )
		{
			assert ( is_numeric($id) );
			assert ( is_array($entry) );
			assert ( count($entry)==count($attrs) );
			foreach ( $entry as $v )
			{
				if ( $mva )
				{
					assert ( is_array($v) );
					foreach ( $v as $vv )
						assert ( is_int($vv) );
				} else
					assert ( is_int($v) );
			}
		}

		// build request
		$this->_MBPush ();
		$req = pack ( "N", strlen($this->_index) ) . $this->_index;

		$req .= pack ( "N", count($attrs) );
		foreach ( $attrs as $attr )
		{
			$req .= pack ( "N", strlen($attr) ) . $attr;
			$req .= pack ( "N", $mva ? 1 : 0 );
		}

		$req .= pack ( "N", count($values) );
		foreach ( $values as $id=>$entry )
		{
			$req .= sphPackU64 ( $id );
			foreach ( $entry as $v )
			{
				$req .= pack ( "N", $mva ? count($v) : $v );
				if ( $mva )
					foreach ( $v as $vv )
						$req .= pack ( "N", $vv );
			}
		}

		// connect, send query, get response
		if (!( $fp = $this->_Connect() ))
		{
			$this->_MBPop ();
			return -1;
		}

		$len = strlen($req);
		$req = pack ( "nnN", SEARCHD_COMMAND_UPDATE, VER_COMMAND_UPDATE, $len ) . $req; // add header
		if ( !$this->_Send ( $fp, $req, $len+8 ) )
		{
			$this->_MBPop ();
			return -1;
		}

		if (!( $response = $this->_GetResponse ( $fp, VER_COMMAND_UPDATE ) ))
		{
			$this->_MBPop ();
			return -1;
		}

		// parse response
		list(,$updated) = unpack ( "N*", substr ( $response, 0, 4 ) );
		$this->_MBPop ();
		return $updated;
	}

	function Open()
	{
		if ( $this->_socket !== false )
		{
			$this->_error = 'already connected';
			return false;
		}
		if ( !$fp = $this->_Connect() )
			return false;

		// command, command version = 0, body length = 4, body = 1
		$req = pack ( "nnNN", SEARCHD_COMMAND_PERSIST, 0, 4, 1 );
		if ( !$this->_Send ( $fp, $req, 12 ) )
			return false;

		$this->_socket = $fp;
		return true;
	}

	function Close()
	{
		if ( $this->_socket === false )
		{
			$this->_error = 'not connected';
			return false;
		}

		fclose ( $this->_socket );
		$this->_socket = false;
		
		return true;
	}
	function Status ()
	{
		$this->_MBPush ();
		if (!( $fp = $this->_Connect() ))
		{
			$this->_MBPop();
			return false;
		}

		$req = pack ( "nnNN", SEARCHD_COMMAND_STATUS, VER_COMMAND_STATUS, 4, 1 ); // len=4, body=1
		if ( !( $this->_Send ( $fp, $req, 12 ) ) ||
			 !( $response = $this->_GetResponse ( $fp, VER_COMMAND_STATUS ) ) )
		{
			$this->_MBPop ();
			return false;
		}

		$res = substr ( $response, 4 ); // just ignore length, error handling, etc
		$p = 0;
		list ( $rows, $cols ) = array_values ( unpack ( "N*N*", substr ( $response, $p, 8 ) ) ); $p += 8;

		$res = array();
		for ( $i=0; $i<$rows; $i++ )
			for ( $j=0; $j<$cols; $j++ )
		{
			list(,$len) = unpack ( "N*", substr ( $response, $p, 4 ) ); $p += 4;
			$res[$i][] = substr ( $response, $p, $len ); $p += $len;
		}

		$this->_MBPop ();
		return $res;
	}

	function FlushAttributes ()
	{
		$this->_MBPush ();
		if (!( $fp = $this->_Connect() ))
		{
			$this->_MBPop();
			return -1;
		}

		$req = pack ( "nnN", SEARCHD_COMMAND_FLUSHATTRS, VER_COMMAND_FLUSHATTRS, 0 ); // len=0
		if ( !( $this->_Send ( $fp, $req, 8 ) ) ||
			 !( $response = $this->_GetResponse ( $fp, VER_COMMAND_FLUSHATTRS ) ) )
		{
			$this->_MBPop ();
			return -1;
		}

		$tag = -1;
		if ( strlen($response)==4 )
			list(,$tag) = unpack ( "N*", $response );
		else
			$this->_error = "unexpected response length";

		$this->_MBPop ();
		return $tag;
	}
	
}
class Coreseek extends SphinxClient{
	
	public $queries=array();
	
	/*
	*批量搜索
	*/
	public function run(){
		//TODO:需要重写Response返回来的值，以使更符合要求的格式
		$data=$this->RunQueries();
		// print_r($data);
		self::$_instance=null;
		$results=array();
		foreach($data as $key=>$result) $results[$this->queries[$key]]=$result;
		return $results;
	}
	/*
	*添加到搜索队列中
	*@param sign string 搜索标识名
	*@param query string 搜索词
	*@param params array 选项设置
	*@return array 多维数组
	*/
	public function add($sign,$data,$params,$split=' '){
		$this->ResetParams();
		$this->queries[]=$sign;
		$this->_setParams($params);
		$keyword=$this->_setKeyword($data);
		//echo $keyword;
		$this->AddQuery($keyword);
	}
	/**
	*	更新单个文档的值
	*	
	*	@param data array 按字段名引用的数组，值必须为数字。
	*	@param params array 必传两个 参数  _index   _id
	*	@return 1 更新成功 0  更新失败
	*/
	public function update($data,$params){
		$this->_index=$params['_index'];
		$data=array_filter($data);
		$values[$params['_id']]=array_values($data);
		return $this->UpdateAttributes(array_keys($data),$values);
	}
	/**
	*	设置属性
	*	@params array 跟类字段一一对应
	*/
	private function _setParams($params){
		foreach($params as $key=>$value){
			!property_exists($this,$key) && exit('请检查此属性是否存在');
			$this->$key =$value;
		}
	}
	/*
	*	设置查询词
	*	@param data array 查询
	*	@return string 查询词
	*/
	private function _setKeyword($data){
		$keyword='';
		if(is_array($data))
			foreach($data as $k=>$v) !empty($v) && $keyword.=empty($k)?' '.$v.' ':' '.$k.' '.$v.' ';
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
	
	
	/*
	*重置参数
	*/
	private function ResetParams(){
		$this->_offset		= 0;
		$this->_limit		= 40;
		$this->_mode		= SPH_MATCH_EXTENDED2;
		$this->_weights		= array ();
		$this->_sort		= SPH_SORT_RELEVANCE;
		$this->_sortby		= "";
		$this->_min_id		= 0;
		$this->_max_id		= 0;
		$this->_filters		= array ();
		$this->_groupby		= "";
		$this->_groupfunc	= SPH_GROUPBY_ATTR;
		$this->_groupsort	= "@group desc";
		$this->_groupdistinct= "";
		$this->_maxmatches	= 1000;
		$this->_ranker		= SPH_RANK_PROXIMITY_BM25;
		$this->_rankexpr	= "";
		$this->_maxquerytime= 0;
		$this->_fieldweights= array();
		$this->_overrides 	= array();
		$this->_select		= "*";

		$this->_error		= ""; // per-reply fields (for single-query case)
		$this->_warning		= "";
		
		$this->_index='*';
	}
}