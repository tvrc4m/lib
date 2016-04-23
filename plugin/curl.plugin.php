<?php

class CurlPlugin extends Plugin{

	public function run($options){

		assert(is_array($options));
		
		$curl=curl_init();

		curl_setopt($curl,CURLOPT_TIMEOUT,10);

		//curl_setopt($curl,CURLOPT_HEADER,0);

		curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);

		curl_setopt($curl,CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)");

		curl_setopt_array($curl,$options);

		if( ! $result = curl_exec($curl)) {
			$error=curl_error($curl);
		}

		curl_close($curl);

		return $result;
	}
}