<?php

class VKException extends Exception{

	public function __toString(){

		if (DEBUG) {
			
			parent::__toString();
		}else{

			echo "";
		}
	}
}