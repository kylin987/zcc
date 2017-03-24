<?php

/**
 * ECSHOP 支付响应页面
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo & church $
 * $Id: respond.php 17217 2011-01-19 06:29:08Z liubo & church $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_payment.php');
require(ROOT_PATH . 'includes/lib_order.php');

/* 支付方式代码 */
$pay_code = !empty($_REQUEST['code']) ? trim($_REQUEST['code']) : '';

//处理微信回调参数
$file_in = $HTTP_RAW_POST_DATA;
$xml = new DOMDocument();
$xml->loadXML($file_in);
$outTradeNos = $xml->getElementsByTagName('out_trade_no');
foreach ($outTradeNos as $outTradeNo) {
	$_POST['order_sn'] = $outTradeNo->nodeValue;	
}
$resultCodes = $xml->getElementsByTagName('result_code');
foreach ($resultCodes as $resultCode) {
	$_POST['result_code'] = $resultCode->nodeValue;		
}
$returnCodes = $xml->getElementsByTagName('return_code');
foreach ($returnCodes as $returnCode) {
	$_POST['return_code'] = $returnCode->nodeValue;	
}
$transactions = $xml->getElementsByTagName('transaction_id');
foreach ($transactions as $transaction) {
	$transaction_id = $transaction->nodeValue;	
}
//修改订单状态
if($_POST['result_code']=='SUCCESS' && isset($_POST['order_sn'])){
	$ls_order_sn=substr($_POST['order_sn'],0,13);
	$sql = "SELECT * FROM ". $GLOBALS['ecs']->table('order_info'). "WHERE order_sn='$ls_order_sn'";
	$order_info = $GLOBALS['db']->getRow($sql);
	if($order_info){	
		$order_sn=substr($_POST['order_sn'],0,13);
		$sql = "UPDATE ".$GLOBALS['ecs']->table('order_info'). " SET pay_status = 2,transaction_id ='$transaction_id' WHERE order_sn = '$order_sn'";
		$GLOBALS['db']->query($sql);
		$sql = "UPDATE ".$GLOBALS['ecs']->table('pay_log'). " SET is_paid = 1,transid ='$transaction_id' WHERE order_id = '$order_info[order_id]'";
		$GLOBALS['db']->query($sql);
		$sql = "UPDATE ".$GLOBALS['ecs']->table('wxpay_dy'). " SET serial_is_paid = 1 WHERE serial_trade_no = '$_POST[order_sn]'";
		$GLOBALS['db']->query($sql);
	}else{
		$order_sn=substr($_POST['order_sn'],0,-10);		
		
		$sql = "SELECT * FROM ". $GLOBALS['ecs']->table('user_account'). "WHERE id='$order_sn'";
		$account_info = $GLOBALS['db']->getRow($sql);
		if($account_info['is_paid']==0){
			log_account_change4($account_info['user_id'],$account_info['amount'],0,0,0,"充值",0);
		}	
		
		$time = time();
		$sql = "UPDATE ".$GLOBALS['ecs']->table('user_account'). " SET is_paid = 1,paid_time ='$time' WHERE id = '$order_sn'";
		$GLOBALS['db']->query($sql);
		
		$sql = "UPDATE ".$GLOBALS['ecs']->table('pay_log'). " SET is_paid = 1,transid ='$transaction_id' WHERE order_id = '$order_sn'";
		$GLOBALS['db']->query($sql);
		$sql = "UPDATE ".$GLOBALS['ecs']->table('wxpay_dy'). " SET serial_is_paid = 1 WHERE serial_trade_no = '$_POST[order_sn]'";
		$GLOBALS['db']->query($sql);
	}	
}


//获取首信支付方式
if (empty($pay_code) && !empty($_REQUEST['v_pmode']) && !empty($_REQUEST['v_pstring']))
{
    $pay_code = 'cappay';
}

if(isset($_POST['MerRemark'])  && $_POST['MerRemark']=='epay')
{
    $pay_code ='epay';
}

//获取快钱神州行支付方式
if (empty($pay_code) && ($_REQUEST['ext1'] == 'shenzhou') && ($_REQUEST['ext2'] == 'ecshop'))
{
    $pay_code = 'shenzhou';
}

//获取微信支付方式
if (isset($_POST['return_code']) && isset($_POST['result_code']))
{
	$pay_code = 'wxpay';
}

/* 参数是否为空 */
if (empty($pay_code))
{
    $msg = $_LANG['pay_not_exist'];
}
else
{
    /* 检查code里面有没有问号 */
    if (strpos($pay_code, '?') !== false)
    {
        $arr1 = explode('?', $pay_code);
        $arr2 = explode('=', $arr1[1]);

        $_REQUEST['code']   = $arr1[0];
        $_REQUEST[$arr2[0]] = $arr2[1];
        $_GET['code']       = $arr1[0];
        $_GET[$arr2[0]]     = $arr2[1];
        $pay_code           = $arr1[0];
    }

    /* 判断是否启用 */
    $sql = "SELECT COUNT(*) FROM " . $ecs->table('payment') . " WHERE pay_code = '$pay_code' AND enabled = 1";
    if ($db->getOne($sql) == 0)
    {
        $msg = $_LANG['pay_disabled'];
    }
    else
    {
        $plugin_file = 'includes/modules/payment/' . $pay_code . '.php';

        /* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
        if (file_exists($plugin_file))
        {
            /* 根据支付方式代码创建支付类的对象并调用其响应操作方法 */
            include_once($plugin_file);

            $payment = new $pay_code();
			file_put_contents(ROOT_PATH.'log.txt', var_export($_POST, true));
            $msg     = (@$payment->respond()) ? $_LANG['pay_success'] : $_LANG['pay_fail'];
        }
        else
        {
            $msg = $_LANG['pay_not_exist'];
        }
    }
}

assign_template();
$position = assign_ur_here();
$smarty->assign('page_title', $position['title']);   // 页面标题
$smarty->assign('ur_here',    $position['ur_here']); // 当前位置
$smarty->assign('page_title', $position['title']);   // 页面标题
$smarty->assign('ur_here',    $position['ur_here']); // 当前位置
$smarty->assign('helps',      get_shop_help());      // 网店帮助

$smarty->assign('message',    $msg);
$smarty->assign('shop_url',   $ecs->url());

$smarty->display('respond.dwt');

?>