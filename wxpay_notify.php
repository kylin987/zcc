<?php

/**
 * ECSHOP 微信回调页面
 * 
 * 可自由修改发布
 * $Author: church $
 * $Id: wxpay_notify.php 17217 2015-08-17 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_payment.php');
require(ROOT_PATH . 'includes/lib_order.php');

//开启调试
define('WXDEBUG', true);

//接收微信xml参数, 并解析
$file_in = $HTTP_RAW_POST_DATA; //接收post数据
$product_id = '';
$xml = new DOMDocument();
$xml->loadXML($file_in);
$productIds = $xml->getElementsByTagName('product_id');
foreach ($productIds as $productId) {
	$product_id = $productId->nodeValue;
}


//获取微信支付APPID等信息
$sql = "SELECT pay_fee, pay_config FROM ". $ecs->table('payment'). " WHERE pay_id=5";		//这里的pay_id不一定为4, 根据实际情况更改
$wxpayInfo = $db->getRow($sql);
$payConfig = unserialize($wxpayInfo['pay_config']);
foreach($payConfig as $value) {
	${$value['name']} = $value['value'];
}


//根据微信返回的productId获取商品信息
$sql = "SELECT * FROM ". $ecs->table('order_info'). "WHERE order_sn='$product_id'";
$orderInfo = $db->getRow($sql);
if(empty($orderInfo)){
	$sql = "SELECT * FROM ". $ecs->table('pay_log'). "WHERE order_id='$product_id'";
	$payInfo = $db->getRow($sql);
	
	$total_fee = $payInfo['order_amount'];
}else{
	$total_fee = $orderInfo['order_amount'];
}

//获取本地IP
if ($_SERVER['REMOTE_ADDR']) {
	$cip = $_SERVER['REMOTE_ADDR'];
} elseif (getenv("REMOTE_ADDR")) {
	$cip = getenv("REMOTE_ADDR");
} elseif (getenv("HTTP_CLIENT_IP")) {
	$cip = getenv("HTTP_CLIENT_IP");
}

//检测目前订单是否支付成功
if(empty($orderInfo)){		//充值订单
	if($payInfo['is_paid']=='1'){	//如果支付成功
		$sql = "SELECT * FROM ". $ecs->table('wxpay_dy'). "WHERE order_sn='$payInfo[order_id]' AND serial_is_paid=1";
		$zfinfo = $db->getRow($sql);
		if($zfinfo){
			$out_trade_no=$zfinfo['serial_trade_no'];
		}else{
			echo "error";
		}
	}else{	//如果没有支付成功
		//生成新的out_trade_no
		$t=time();
		$out_trade_no=$payInfo['order_id']."_".$t;
		$sql = "INSERT INTO " . $GLOBALS['ecs']->table('wxpay_dy') .
					" (order_sn, serial_trade_no,serial_pay_code,serial_time)".
					" VALUES ('$payInfo[order_id]','$out_trade_no','wx','$t')";
		$GLOBALS['db']->query($sql);
	}
}else{
	if($orderInfo['pay_status']=='2'){	//如果支付成功
		$sql = "SELECT * FROM ". $ecs->table('wxpay_dy'). "WHERE order_sn='$orderInfo[order_sn]' AND serial_is_paid=1";
		$zfinfo = $db->getRow($sql);
		if($zfinfo){
			$out_trade_no=$zfinfo['serial_trade_no'];
		}else{
			echo "error";
		}
	}else{	//如果没有支付成功
		//生成新的out_trade_no
		$t=time();
		$out_trade_no=$orderInfo['order_sn']."_".$t;
		$sql = "INSERT INTO " . $GLOBALS['ecs']->table('wxpay_dy') .
					" (order_sn, serial_trade_no,serial_pay_code,serial_time)".
					" VALUES ('$orderInfo[order_sn]','$out_trade_no','wx','$t')";
		$GLOBALS['db']->query($sql);
	}
}	



//初始化数据
$params = array(
	'appid' 			=> 	$appid,										
	'mch_id'			=> 	$mch_id,
	'device_info' 		=> 	'WEB',
	'nonce_str' 		=> 	strtolower(md5(mt_rand())),
	'body'				=> 	$orderInfo['order_sn'].'订单支付',									//这里填你的商户信息， 比如说百度
	'out_trade_no'		=> 	$out_trade_no,
	'fee_type'			=>	'CNY',
	'total_fee'			=>	intval($total_fee * 100),
	'spbill_create_ip'	=>	$cip,
	'notify_url'		=>	'http://www.qianyingziben.com/respond.php',				//记得改这里
	'trade_type'		=>	'NATIVE',
	'product_id'		=>	$product_id,
);	

ksort($params);

//对数据进行签名
$signString = '';
$signArrTemp = array_merge($params, array('key'=>$wxpay_key));
$paramString = '';
foreach ($signArrTemp as $key=>$value) {
	$paramString .= "$key=$value".'&';
}
$paramString = rtrim($paramString, '&');
$sign = strtoupper(md5($paramString));

//组装xmlData
$params = array_merge($params, array('sign'=>$sign));
$xmlData = '<xml>';
foreach ($params as $key=>$value) {
	$xmlData .= "<$key>".$value."</$key>";
}
$xmlData .= '</xml>';



//提交数据到微信
$ch = curl_init();
$header =  array("Content-type: text/xml");
$apiUrl = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
curl_setopt_array($ch, array(
	 CURLOPT_URL 				=> 	$apiUrl,
	 CURLOPT_HTTPHEADER			=> 	$header,
	 CURLOPT_POST				=>	true,
	 CURLOPT_SSL_VERIFYPEER		=>	false,
	 CURLOPT_POSTFIELDS			=>	$xmlData
));

$returnString = curl_exec($ch);

if (!curl_error($ch)) {
	file_put_contents(ROOT_PATH.'log.txt', var_export($returnString, true));
	die;
}

exit;