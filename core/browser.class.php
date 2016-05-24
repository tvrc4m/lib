<?php

/**
 * 判断浏览器设备
 */
class Browser {

    protected $agent;

    protected $platforms=array('iphone'=>'iPhone','ipad'=>'iPad','ipod'=>'iPod','android'=>'Android','wechat'=>'MicroMessenger');

    public function __construct(){
            
        $this->agent=isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";
        
        $this->detected();
    }

    protected function detected(){

        foreach ($this->platforms as $field => $platform) {
            
            stripos($this->agent,$platform) !== false && $this->$field=true;
        }
    }
}