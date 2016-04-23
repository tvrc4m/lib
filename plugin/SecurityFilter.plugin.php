<?php

class SecurityFilterPlugin extends Plugin{
	
	public function run($data){

       	$referer=empty($_SERVER['HTTP_REFERER']) ? array() : array($_SERVER['HTTP_REFERER']);

       	//set_error_handler("customError",E_ERROR);
		
		$getfilter="'|\\b(and|or)\\b.+?(>|<|=|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)|onerror|onclick|onload|onmove|onresize|onscroll|onstop|onabort|onunload|.*?script.*?|alert";
		$postfilter="\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)|onerror|onclick|onload|onmove|onresize|onscroll|onstop|onabort|onunload|.*?script.*?|alert";
		$cookiefilter="\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)|onerror|onclick|onload|onmove|onresize|onscroll|onstop|onabort|onunload|.*?script.*?|alert";

       	foreach($_GET as $key=>$value){ 
			$this->StopAttack($key,$value,$getfilter);
		}
		foreach($_POST as $key=>$value){ 
			$this->StopAttack($key,$value,$postfilter);
		}
		foreach($_COOKIE as $key=>$value){ 
			$this->StopAttack($key,$value,$cookiefilter);
		}
		foreach($referer as $key=>$value){ 
		  	$this->StopAttack($key,$value,$getfilter);
		}
    }

   private function customError($errno, $errstr, $errfile, $errline)
	{ 
		echo " <b>Error number:</b>[$errno],error on line $errline in $errfile<br />   ";
	 	die();
	}
	private function StopAttack($StrFiltKey,$StrFiltValue,$ArrFiltReq){  
		if(empty($StrFiltValue)) return;
		$StrFiltValue=$this->arr_foreach($StrFiltValue);
		if (preg_match("/".$ArrFiltReq."/is",$StrFiltValue)==1){   
			
        	print "<div style=\"position:fixed;top:0px;width:100%;height:100%;background-color:white;color:green;font-weight:bold;border-bottom:5px solid #999;\"><br>您的提交带有不合法参数,谢谢合作!<br></div>";
        	
        	exit();
		}
		if (preg_match("/".$ArrFiltReq."/is",$StrFiltKey)==1){   
        	print "<div style=\"position:fixed;top:0px;width:100%;height:100%;background-color:white;color:green;font-weight:bold;border-bottom:5px solid #999;\"><br>您的提交带有不合法参数,谢谢合作!<br></div>";
        	exit();
		}  
	}

	private function arr_foreach($arr) {
		static $str;
		if (!is_array($arr)) {
	  		return $arr;
	  	}
	  	foreach ($arr as $key => $val ){
	    	if (is_array($val)) {
	        	$this->arr_foreach($val);
	    	} else {
	      		$str[] = $val;
	    	}
	  	}
	  return implode($str);
	}

}