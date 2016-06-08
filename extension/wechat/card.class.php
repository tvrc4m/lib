<?php

namespace WeChat;

class Card  extends Auth{

	public function __construct(){

		parent::__construct();

		$this->access_token=$this->get_access_token();

	    if(empty($this->access_token)) return false;
	}

    /**
     * 创建卡券
     * @param Array $data      卡券数据
     * @return array|boolean 返回数组中card_id为卡券ID
     */
    public function create_card($data) {
        
        return $this->http_post(self::API_BASE_URL_PREFIX.'/card/create?access_token='.$this->access_token, $data);
    }

    /**
     * 更改卡券信息
     * 调用该接口更新信息后会重新送审，卡券状态变更为待审核。已被用户领取的卡券会实时更新票面信息。
     * @param string $data
     * @return boolean
     */
    public function update_card($data) {
        
        return $this->http_post(self::API_BASE_URL_PREFIX.'/card/update?access_token='.$this->access_token, $data);
    }

    /**
     * 删除卡券
     * 允许商户删除任意一类卡券。删除卡券后，该卡券对应已生成的领取用二维码、添加到卡包 JS API 均会失效。
     * 注意：删除卡券不能删除已被用户领取，保存在微信客户端中的卡券，已领取的卡券依旧有效。
     * @param string $card_id 卡券ID
     * @return boolean
     */
    public function del_card($card_id) {

        $data = array('card_id' => $card_id);

        $result = $this->http_post(self::API_BASE_URL_PREFIX.'/card/delete?access_token='.$this->access_token, $data);
        
        return !empty($result);
    }

    /**
     * 查询卡券详情
     * @param string $card_id
     * @return boolean|array    返回数组信息比较复杂，请参看卡券接口文档
     */
    public function get_card_info($card_id) {

        $data = array('card_id' => $card_id);

        return $this->http_post(self::API_BASE_URL_PREFIX.'/card/get?access_token='.$this->access_token, $data);
    }

    /**
     * 获取颜色列表
	 * 获得卡券的最新颜色列表，用于创建卡券
	 * @return boolean|array   返回数组请参看 微信卡券接口文档 的json格式
     */
    public function get_card_colors() {
        
        return $this->http_get(self::API_BASE_URL_PREFIX.'/card/getcolors?access_token='.$this->access_token);
    }

    /**
     * 拉取门店列表
	 * 获取在公众平台上申请创建的门店列表
	 * @param int $offset  开始拉取的偏移，默认为0从头开始
	 * @param int $count   拉取的数量，默认为0拉取全部
	 * @return boolean|array   返回数组请参看 微信卡券接口文档 的json格式
     */
    public function get_card_locations($offset=0,$count=0) {

	    $data=array('offset'=>$offset,'count'=>$count);

        return $this->http_post(self::API_BASE_URL_PREFIX.'/card/location/batchget?access_token='.$this->access_token, $data);
    }

    /**
     * 生成卡券二维码
	 * 成功则直接返回ticket值，可以用 getQRUrl($ticket) 换取二维码url
	 *
	 * @param string $cardid 卡券ID 必须
	 * @param string $code 指定卡券 code 码，只能被领一次。use_custom_code 字段为 true 的卡券必须填写，非自定义 code 不必填写。
	 * @param string $openid 指定领取者的 openid，只有该用户能领取。bind_openid 字段为 true 的卡券必须填写，非自定义 openid 不必填写。
	 * @param int $expire_seconds 指定二维码的有效时间，范围是 60 ~ 1800 秒。不填默认为永久有效。
	 * @param boolean $is_unique_code 指定下发二维码，生成的二维码随机分配一个 code，领取后不可再次扫描。填写 true 或 false。默认 false。
	 * @param string $balance 红包余额，以分为单位。红包类型必填（LUCKY_MONEY），其他卡券类型不填。
     * @return boolean|string
     */
    public function create_card_qrcode($card_id,$code='',$openid='',$expire_seconds=0,$is_unique_code=false,$balance='') {

        $card = array('card_id' => $card_id);
        $data = array('action_name' => "QR_CARD");
        $code &&  $card['code'] = $code;
        $openid && $card['openid'] = $openid;
        $is_unique_code && $card['is_unique_code'] = $is_unique_code;
        $balance && $card['balance'] = $balance;
        $expire_seconds && $data['expire_seconds'] = $expire_seconds;

        $data['action_info'] = array('card' => $card);
        
        return $this->http_post(self::API_BASE_URL_PREFIX.'/card/qrcode/create?access_token='.$this->access_token, $data);
    }

    /**
     * 消耗 code
     * 自定义 code（use_custom_code 为 true）的优惠券，在 code 被核销时，必须调用此接口。
     *
     * @param string $code 要消耗的序列号
     * @param string $card_id 要消耗序列号所述的 card_id，创建卡券时use_custom_code 填写 true 时必填。
     * @return boolean|array
     * {
     *  "errcode":0,
     *  "errmsg":"ok",
     *  "card":{"card_id":"pFS7Fjg8kV1IdDz01r4SQwMkuCKc"},
     *  "openid":"oFS7Fjl0WsZ9AMZqrI80nbIq8xrA"
     * }
     */
    public function consume_card_code($code,$card_id='') {

        $data = array('code' => $code);

        $card_id && $data['card_id'] = $card_id;

        return $this->http_post(self::API_BASE_URL_PREFIX.'/card/code/consume?access_token='.$this->access_token, $data);
    }

    /**
     * code 解码
     * @param string $encrypt_code 通过 choose_card_info 获取的加密字符串
     * @return boolean|array
     * {
     *  "errcode":0,
     *  "errmsg":"ok",
     *  "code":"751234212312"
     *  }
     */
    public function decrypt_card_code($encrypt_code) {

        $data = array('encrypt_code' => $encrypt_code);

        return $this->http_post(self::API_BASE_URL_PREFIX.'/card/code/decrypt?access_token='.$this->access_token, $data);
    }

    /**
     * 查询 code 的有效性（非自定义 code）
     * @param string $code
     * @return boolean|array
     * {
     *  "errcode":0,
     *  "errmsg":"ok",
     *  "openid":"oFS7Fjl0WsZ9AMZqrI80nbIq8xrA",    //用户 openid
     *  "card":{
     *      "card_id":"pFS7Fjg8kV1IdDz01r4SQwMkuCKc",
     *      "begin_time": 1404205036,               //起始使用时间
     *      "end_time": 1404205036,                 //结束时间
     *  }
     * }
     */
    public function check_card_code($code) {

        $data = array('code' => $code);
        
        return $this->http_post(self::API_BASE_URL_PREFIX.'/card/code/get?access_token='.$this->access_token, $data);
    }

    /**
     * 批量查询卡列表
	 * @param $offset  开始拉取的偏移，默认为0从头开始
	 * @param $count   需要查询的卡片的数量（数量最大50,默认50）
     * @return boolean|array
     * {
     *  "errcode":0,
     *  "errmsg":"ok",
     *  "card_id_list":["ph_gmt7cUVrlRk8swPwx7aDyF-pg"],    //卡 id 列表
     *  "total_num":1                                       //该商户名下 card_id 总数
     * }
     */
    public function get_card_id_list($offset=0,$count=50) {

        if ($count>50) $count = 50;

        $data = array('offset' => $offset,'count'  => $count);

        return $this->http_post(self::API_BASE_URL_PREFIX.'/card/batchget?access_token='.$this->access_token, $data);
    }

    /**
     * 更改 code
     * 为确保转赠后的安全性，微信允许自定义code的商户对已下发的code进行更改。
     * 注：为避免用户疑惑，建议仅在发生转赠行为后（发生转赠后，微信会通过事件推送的方式告知商户被转赠的卡券code）对用户的code进行更改。
     * @param string $code      卡券的 code 编码
     * @param string $card_id   卡券 ID
     * @param string $new_code  新的卡券 code 编码
     * @return boolean
     */
    public function update_card_code($code,$card_id,$new_code) {

        $data = array('code' => $code,'card_id' => $card_id,'new_code' => $new_code);
        
        return $this->http_post(self::API_BASE_URL_PREFIX.'/card/code/update?access_token='.$this->access_token, $data);
    }

    /**
     * 设置卡券失效
     * 设置卡券失效的操作不可逆
     * @param string $code 需要设置为失效的 code
     * @param string $card_id 自定义 code 的卡券必填。非自定义 code 的卡券不填。
     * @return boolean
     */
    public function unavailable_card_code($code,$card_id='') {

        $data = array('code' => $code);

        if ($card_id) $data['card_id'] = $card_id;
        
        $result = $this->http_post(self::API_BASE_URL_PREFIX.'/card/code/unavailable?access_token='.$this->access_token, $data);
        
        return !empty($result);
    }

    /**
     * 库存修改
     * @param string $data
     * @return boolean
     */
    public function modify_card_stock($data) {

        $result = $this->http_post(self::API_BASE_URL_PREFIX.'/card/modifystock?access_token='.$this->access_token, $data);
        
        return !empty($result);
    }
}