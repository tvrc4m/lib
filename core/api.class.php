<?php

/**
* 响应格式 {status:response_code,errmsg:response_message,}
*/
class Api {

    /**
     * appid
     * @var string
     */
    protected $appid;

    /**
     * 加密字符串
     * @var string
     */
    protected $secret;

    /**
     * 时间戳
     * @var int
     */
    protected $timestamp;

    /**
     * 时间有效期（单位为秒）
     * @var int
     */
    protected $alive    =   60;

    /**
     * 是否ip限制
     * @var string
     */
    protected $ip_limited   =    false;

    /**
     * 访问者ip
     * @var string
     */
    protected $ip;

    /**
     * 加密后的字符串
     * @var string
     */
    protected $signature;

    /**
     * 响应码
     * @var int
     */
    protected $response_code;

    /**
     * 响应错误文本
     * @var string
     */
    protected $response_message;

    /**
     * 响应成功后的数据 
     * @var [type]
     */
    protected $response_data;

    /**
     * app数据信息
     * @var array
     */
    public $app = array();

    public function __construct($app){

        parent::__construct();
        $this->ip = getip();
        if (!$this->checkIp($user_ip)) {
            // 如果不允许此ip访问
            $this->response();
        }
        $this->app=$app;
        if (!$this->checkApp()) {
            // 如果应用不可用
            $this->response();
        }
        $this->appid =$_POST['appid'];
        $this->secret=$this->app['secret'];
        $this->timestamp= $_POST['timestamp'];
        $this->signature= $_POST['signature'];
    }

    private function response(){
        // 响应json
        exit(json_encode(array('status'=>$this->response_code,'errmsg'=>$this->response_message)));
    }

    private function checkApp(){

        if (empty($this->app)) {
            
            $this->response_code=1101;
            $this->response_message='不存在该应用';
            return false;
        }else if ($this->app['status']!=1) {

            $this->response_code=1101;
            $this->response_message='此应用已被禁用';
            return false;
        }
        return true;
    }

    /**
     * 检测ip
     * @param  string $ip ip地址
     * @return boolean     是否在允许的列表中
     */
    private function checkIp($ip){

        if (!$this->ip_limited) return true;

        $allow_ips=$this->apps[$this->appid]['limits'];

        if(!in_array($ip, $allow_ips)){

            $this->response_code=1104;
            $this->response_message='ip受限';
        }

        return true;
    }

    /**
     * 检测签名是否正确和时间有效期
     * @param  array $data 需要签名的数据
     * @return boolean  true: 验证成功  false: 失败
     */
    private function check($data){

        if ($data['timestamp']+$this->alive>time()) {
            // 在有效期内
            if($this->signature===$this->signature($data)){

                return true;
            }else{

                $this->response_code=1103;
                $this->response_message='签名验证失败';
                return false;
            }
        }else{
            // 已失效
            $this->response_code=1102;
            $this->response_message='签名已失效';
            return false;
        }
    }

    /**
     * 生成签名字符串
     * @param  array $data 需要签名的数据 
     * @return string      签名字符串
     */
    private function signature($data){

        $data['signature']=$this->secret;
        $data['timestamp']=$this->timestamp;
        $data['appid']=$this->appid;

        sort($data);

        $str=http_build_query($data);

        return sha1($str);
    }
}
