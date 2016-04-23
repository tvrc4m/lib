<?php

class QrcodePlugin extends Plugin{

	public function run($options){

		$data=$options['data'];

		$size=$options['size'];

		empty($size) && $size=6;

		$image=sprintf('%sqrcode/%s.png',IMG,uniqid('TT'));

		include_once(EXTENSION.'phpqrcode/qrlib.php');

		QRcode::png($data,$image,QR_ECLEVEL_L,$size);
	}
}