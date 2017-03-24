<?php

/**
 * ECSHOP 微信扫码支付插件
 * 可以自由发布
 *
 * $Author: church $
 * $Id: wxpay.php 17217 2015-08-17 06:29:08Z church $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/wxpay.php';

if (file_exists($payment_lang))
{
    global $_LANG;

    include_once($payment_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = isset($modules) ? count($modules) : 0;

    /* 代码 */
    $modules[$i]['code']    = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'wxpay_desc';

    /* 是否支持货到付款 */
    $modules[$i]['is_cod']  = '0';

    /* 是否支持在线支付 */
    $modules[$i]['is_online']  = '1';

    /* 作者 */
    $modules[$i]['author']  = 'church';

    /* 网址 */
    $modules[$i]['website'] = 'http://weixin.qq.com';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';

    /* 配置信息 */
    $modules[$i]['config']  = array(
        array('name' => 'appid',           'type' => 'text',   'value' => ''),
        array('name' => 'mch_id',            'type' => 'text',   'value' => ''),
		array('name' => 'app_secret',            'type' => 'text',   'value' => ''),
		array('name' => 'wxpay_key',            'type' => 'text',   'value' => ''),
    );

    return;
}

/**
 * 类
 */
class wxpay
{

    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
	 function __construct()
    {
        $this->wxpay();
    }
    function wxpay()
    {
    }

    

    /**
     * 生成支付二维码
     * @param   array   $order      订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order, $payment)
    {
        if (!defined('EC_CHARSET'))
        {
            $charset = 'utf-8';
        }
        else
        {
            $charset = EC_CHARSET;
        }
		


        $parameter = array(
			'appid' 			=> $payment['appid'],
			'mch_id'			=> $payment['mch_id'],
			'nonce_str'			=> strtolower(md5(mt_rand())),
			'product_id'		=> $order['order_sn'],
			'time_stamp' 		=> time(),
        );


        $param = '';

        foreach ($parameter AS $key => $val)
        {
            $param .= "$key={$val}&";
        }
		
        $stringSignTemp  = $param. 'key='. $payment['wxpay_key'];
		
		// echo $stringSignTemp; die;
        //$sign  = substr($sign, 0, -1). ALIPAY_AUTH;
		
        // $url = 'weixin://wxpay/bizpayurl?'.$param. 'sign='. strtoupper(md5($stringSignTemp));
        $url = base64_encode(urlencode('weixin://wxpay/bizpayurl?'.$param. 'sign='.strtoupper(md5($stringSignTemp))));
		
        $button = '<div style="text-align:center"><input type="button" class="pay_a" onclick="window.open(\'user.php?act=show_qrcode&data='.$url.'&order_sn='.$order[order_sn].'\')" value="' .$GLOBALS['_LANG']['pay_button']. '" /></div>';

        return $button;
    }

    /**
     * 响应操作
     */
    function respond()
    {
        if (isset($_POST['result_code']) && $_POST['result_code'] == 'SUCCESS') {
		
			$pay_status = 2;
			 /* 取得订单信息 */
			 logg($_POST);
			$sql = 'SELECT order_id, user_id, order_sn, consignee, address, tel, shipping_id, extension_code, extension_id, goods_amount ' .
					'FROM ' . $GLOBALS['ecs']->table('order_info') .
				   " WHERE order_sn = '{$_POST['order_sn']}'";
			
			$order    = $GLOBALS['db']->getRow($sql);
			$order_id = $order['order_id'];
			$order_sn = $order['order_sn'];

			/* 修改订单状态为已付款 */
			$sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') .
						" SET order_status = '" . OS_CONFIRMED . "', " .
							" confirm_time = '" . gmtime() . "', " .
							" pay_status = '$pay_status', " .
							" pay_time = '".gmtime()."', " .
							" money_paid = order_amount," .
							" order_amount = 0 ".
				   "WHERE order_id = '$order_id'";
			$GLOBALS['db']->query($sql);

			/* 记录订单操作记录 */
			order_action($order_sn, OS_CONFIRMED, SS_UNSHIPPED, $pay_status, $note, $GLOBALS['_LANG']['buyer']);
			
			file_put_contents(ROOT_PATH.'log.txt', '终于成功了');
			return true;
			
        } else {
            return false;
        }
		
    }
}

