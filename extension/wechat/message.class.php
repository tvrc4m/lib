<?php

namespace WeChat;

class Message  extends Base{

    /**
     * 获取微信服务器发来的信息
     */
	public function getRev(){

		$postStr = !empty($this->postxml)?$this->postxml:file_get_contents("php://input");

		return (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
	}

	/**
	 *
	 * 回复微信服务器, 此函数支持链式操作
	 * Example: $this->text('msg tips')->reply();
	 * @param string $msg 要发送的信息, 默认取$this->_msg
	 * @param bool $return 是否返回信息而不抛出到浏览器 默认:否
	 */
	public function reply($msg=array(),$return = false){

		if (empty($msg)) {
		    if (empty($this->_msg))   //防止不先设置回复内容，直接调用reply方法导致异常
		        return false;
			$msg = $this->_msg;
		}
		$xmldata=  $this->xml_encode($msg);

		if ($this->encrypt_type == 'aes') { //如果来源消息为加密方式

			include_once "crypt.class.php";

		    $pc = new WeChat\Prpcrypt($this->encodingAesKey);

		    $array = $pc->encrypt($xmldata, $this->appid);
		    $ret = $array[0];
		    if ($ret != 0) {
		        return false;
		    }
		    $timestamp = time();
		    $nonce = rand(77,999)*rand(605,888)*rand(11,99);
		    $encrypt = $array[1];
		    $tmpArr = array($this->token, $timestamp, $nonce,$encrypt);//比普通公众平台多了一个加密的密文
		    sort($tmpArr, SORT_STRING);
		    $signature = implode($tmpArr);
		    $signature = sha1($signature);
		    $xmldata = $this->generate($encrypt, $signature, $timestamp, $nonce);
		}
		if ($return)

			return $xmldata;
		else
			echo $xmldata;
	}

	private function xmlSafeStr($str){

		return '<![CDATA['.preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/",'',$str).']]>';
	}

	/**
	 * 数据XML编码
	 * @param mixed $data 数据
	 * @return string
	 */
	private function data_to_xml($data) {
	    $xml = '';
	    foreach ($data as $key => $val) {
	        is_numeric($key) && $key = "item id=\"$key\"";
	        $xml    .=  "<$key>";
	        $xml    .=  ( is_array($val) || is_object($val)) ? $this->data_to_xml($val)  : $this->xmlSafeStr($val);
	        list($key, ) = explode(' ', $key);
	        $xml    .=  "</$key>";
	    }
	    return $xml;
	}

	/**
     * xml格式加密，仅请求为加密方式时再用
     */
	private function generate($encrypt, $signature, $timestamp, $nonce){
	    //格式化加密信息
	    $format = "<xml>
				<Encrypt><![CDATA[%s]]></Encrypt>
				<MsgSignature><![CDATA[%s]]></MsgSignature>
				<TimeStamp>%s</TimeStamp>
				<Nonce><![CDATA[%s]]></Nonce>
				</xml>";
	    return sprintf($format, $encrypt, $signature, $timestamp, $nonce);
	}

	/**
	 * XML编码
	 * @param mixed $data 数据
	 * @param string $root 根节点名
	 * @param string $item 数字索引的子节点名
	 * @param string $attr 根节点属性
	 * @param string $id   数字索引子节点key转换的属性名
	 * @param string $encoding 数据编码
	 * @return string
	*/
	private function xml_encode($data, $root='xml', $item='item', $attr='', $id='id', $encoding='utf-8') {
	    if(is_array($attr)){
	        $_attr = array();
	        foreach ($attr as $key => $value) {
	            $_attr[] = "{$key}=\"{$value}\"";
	        }
	        $attr = implode(' ', $_attr);
	    }
	    $attr   = trim($attr);
	    $attr   = empty($attr) ? '' : " {$attr}";
	    $xml   = "<{$root}{$attr}>";
	    $xml   .= $this->data_to_xml($data, $item, $id);
	    $xml   .= "</{$root}>";
	    return $xml;
	}
}
