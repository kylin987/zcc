<?php

/**
 * ECSHOP 程序说明
 * ===========================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: liubo $
 * $Id: affiliate_ck.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');

admin_priv('spree_trade');


/*------------------------------------------------------ */
//-- 列表页
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $logdb = get_spree_sell_list();
	//var_dump($logdb);
    $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', '出售大礼包');
    $smarty->assign('on', $separate_on);
    $smarty->assign('logdb',        $logdb['logdb']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);
    if (!empty($_GET['auid']))
    {
        $smarty->assign('action_link',  array('text' => $_LANG['back_note'], 'href'=>"users.php?act=edit&id=$_GET[auid]"));
    }
    assign_query_info();
    $smarty->display('user_spree_sell.htm');
}
/*
    购买礼包审核
*/
elseif ($_REQUEST['act'] == 'check')
{        
	$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_spree') .
				" WHERE is_check = 1 AND id = '" . $_GET['id'] . "'";
	$result = 	$GLOBALS['db']->getRow($sql);
	if($result)	{
		if($result['is_sell'] !=1){
			$cent="该订单已经审核或被取消，不要重复审核！";
		}else{			
			//开始审核
			//加钱			
			$change_desc = "出售大礼包".$result['spree_sn'];
        	log_account_change($result['user_id'], $result['sell_price'], 0, 0, 0, $change_desc, $change_type);
			
			
			//更新礼包信息并删除礼包
			$sql = "UPDATE " . $GLOBALS['ecs']->table('user_spree') .
					   " SET is_sell = 2 ,is_del =1, sell_time = '" . time() ."'".
					   " WHERE id = '" . $_GET['id'] . "'";
			$GLOBALS['db']->query($sql);
			
			//插入礼包流水
			$time = time();
			$year = (int)date('Y',$result['check_time']);
			$month = (int)date('m',$result['check_time']);
			$sql = "INSERT INTO " . $GLOBALS['ecs']->table('spree_log') . "( user_id, trade_type, spree_sn,year,month, trade_price ,trade_beizhu,send_time,status, check_time)".
                                                              " VALUES ( '$result[user_id]', 1,'$result[spree_sn]', '$year','$month','$result[sell_price]', '$result[sell_beizhu]', '$result[send_time]', 1,$time)";
			$GLOBALS['db']->query($sql);
						
			
			/* 记录管理员操作 */
			admin_log(addslashes($result['spree_sn']), 'check', 'sell_spree');
			
			$cent="审核成功";
		}
	}else{
		$cent="未查到该订单";
	}

    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'user_spree_sell.php?act=list');
    sys_msg($cent, 0, $link);
}

/*
    查看购买申请
*/
elseif ($_REQUEST['act'] == 'edit')
{        
	$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_spree') .
				" WHERE is_sell <> 0 AND id = '" . $_GET['id'] . "'";
	$result = 	$GLOBALS['db']->getRow($sql);
	$result['username'] = getChenghu($result['user_id']);
	$result['send_time'] = local_date($GLOBALS['_CFG']['date_format'], $result['send_time']);
	if($result['is_sell']==1 || $result['is_sell']==3){
		$result['lang_status'] = "驳回";
	}else{
		$result['lang_status'] = "已完成";
	}
	$result['year'] = (int)date('Y',$result['check_time']);	
	$result['month'] = (int)date('m',$result['check_time']);
	$smarty->assign('log_info', $result);
	$smarty->assign('ur_here', '查看购买申请');
	$smarty->display('user_spree_sell_info.htm');			
}

/*
    驳回申请
*/
elseif ($_REQUEST['act'] == 'action')
{        
	if($_POST['status'] == 1){
		$sql = "UPDATE " . $GLOBALS['ecs']->table('user_spree') .
				   " SET is_sell = 3,admin_note = '".$_POST['admin_note']."'" .
				   " WHERE id = '" . $_POST['id'] . "'";
		$GLOBALS['db']->query($sql);
	
		/* 提示信息 */
		$link[] = array('text' => $_LANG['go_back'], 'href'=>'user_spree_sell.php?act=list');
		sys_msg('已驳回', 0, $link);
	}else{
		$sql = "UPDATE " . $GLOBALS['ecs']->table('spree_log') .
				   " SET admin_note = '".$_POST['user_spree']."'" .
				   " WHERE id = '" . $_POST['id'] . "'";
		$GLOBALS['db']->query($sql);
	
		/* 提示信息 */
		$link[] = array('text' => $_LANG['go_back'], 'href'=>'user_spree_sell.php?act=list');
		sys_msg('已修改', 0, $link);
	}	
		
}








/*------------------------------------------------------ */
//-- ajax帐户信息列表
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $logdb = get_spree_sell_list();
    $smarty->assign('logdb',        $logdb['logdb']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

    $sort_flag  = sort_flag($logdb['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('user_spree_sell.htm'), '', array('filter' => $logdb['filter'], 'page_count' => $logdb['page_count']));
}



function get_spree_sell_list()
{

    $sqladd = '';
    if (isset($_REQUEST['status']))
    {		
		$sqladd = ' AND a.is_sell = ' . (int)$_REQUEST['status'];
		$filter['is_sell'] = (int)$_REQUEST['status'];
    }
	
    
    if (isset($_GET['user_id']))
    {
        $sqladd = ' AND a.user_id=' . $_GET['user_id'];
    }
	
	

    
	$sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('user_spree') . " a".
			" WHERE a.is_check = 1 AND is_sell <> 0 $sqladd";
	


    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
    $logdb = array();
    /* 分页大小 */
    $filter = page_and_size($filter);

    
	
	$sql = "SELECT a.* FROM " . $GLOBALS['ecs']->table('user_spree') . " a".
			" WHERE a.is_check = 1 AND is_sell <> 0 $sqladd".
			" ORDER BY is_sell,send_time DESC" .
			" LIMIT " . $filter['start'] . ",$filter[page_size]";

    


    $logdb = $GLOBALS['db']->getAll($sql);

    $count = count($logdb);
    for ($i=0; $i<$count; $i++)
    {
		$logdb[$i]['year'] = (int)date('Y',$logdb[$i]['check_time']);	
		$logdb[$i]['month'] = (int)date('m',$logdb[$i]['check_time']);
		$logdb[$i]['zd_price'] = getZdprice((int)date('Y',$logdb[$i]['check_time']),(int)date('m',$logdb[$i]['check_time']));
		
        $logdb[$i]['sell_time'] = local_date($GLOBALS['_CFG']['date_format'], $logdb[$i]['sell_time']);	
		$logdb[$i]['check_time'] = local_date($GLOBALS['_CFG']['date_format'], $logdb[$i]['check_time']);		
		$logdb[$i]['username'] = getChenghu($logdb[$i]['user_id']);	
		$logdb[$i]['tname'] = getTname($logdb[$i]['user_id']);	
			
    }
    
    $arr = array('logdb' => $logdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}
function write_affiliate_log($oid, $uid, $username, $money, $point, $separate_by)
{
    $time = gmtime();
    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('affiliate_log') . "( order_id, user_id, user_name, time, money, point, separate_type)".
                                                              " VALUES ( '$oid', '$uid', '$username', '$time', '$money', '$point', $separate_by)";
    if ($oid)
    {
        $GLOBALS['db']->query($sql);
    }
}
//获取或用户名
function getChenghu($u_id){
	
		$sql2 = "SELECT user_name from ". $GLOBALS['ecs']->table('users') ." where user_id = ".$u_id;
		$username = $GLOBALS['db']->getOne($sql2);
		return 	$username;
	 
}
//获取真实姓名
function getTname($u_id){	
		$sql2 = "SELECT tname from ". $GLOBALS['ecs']->table('users') ." where user_id = ".$u_id;
		$tname = $GLOBALS['db']->getOne($sql2);
		return 	$tname;	 
}

//获取指导价
function getZdprice($year,$month){
	$sql = "SELECT price from ". $GLOBALS['ecs']->table('spree_price') ." where is_del = 0 AND year = '".$year."' AND month = '".$month."'";
	$price = $GLOBALS['db']->getOne($sql);
	return 	$price;	 
}

/**
 * 查询会员余额的数量
 * @access  public
 * @param   int     $user_id        会员ID
 * @return  int
 */
function get_user_surplus($user_id)
{
    $sql = "SELECT user_money FROM " .$GLOBALS['ecs']->table('users').
           " WHERE user_id = '$user_id'";

    return $GLOBALS['db']->getOne($sql);
}

/**
 * 得到新订单号
 * @return  string
 */
function get_order_sn()
{
    /* 选择一个随机的方案 */
    mt_srand((double) microtime() * 1000000);

    return date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

/**
 * 获取指定月份的第一天开始和最后一天结束的时间戳
 *
 * @param int $y 年份 $m 月份
 * @return array(本月开始时间，本月结束时间)
 */
function mFristAndLast($y = "", $m = ""){
    if ($y == "") $y = date("Y");
    if ($m == "") $m = date("m");
    $m = sprintf("%02d", intval($m));
    $y = str_pad(intval($y), 4, "0", STR_PAD_RIGHT);
 
    $m>12 || $m<1 ? $m=1 : $m=$m;
    $firstday = strtotime($y . $m . "01000000");
    $firstdaystr = date("Y-m-01", $firstday);
    $lastday = strtotime(date('Y-m-d 23:59:59', strtotime("$firstdaystr +1 month -1 day")));
 
    return array(
        "firstday" => $firstday,
        "lastday" => $lastday
    );
}

function autowrap($fontsize, $angle, $fontface, $string, $width) {
	// 参数分别是 字体大小, 角度, 字体名称, 字符串, 预设宽度
	$content = "";
	// 将字符串拆分成一个个单字 保存到数组 letter 中
	preg_match_all("/./u", $string, $arr); 
	$letter = $arr[0];
	foreach($letter as $l) {
		$teststr = $content.$l;
		$testbox = imagettfbbox($fontsize, $angle, $fontface, $teststr);
		if (($testbox[2] > $width) && ($content !== "")) {
			$content .= PHP_EOL;
		}
		$content .= $l;
	}
	return $content;
}	

/**
 * curl 获取
 */
function curlGet($url, $timeout = 5, $header = "") {
    $defaultHeader = '$header = "User-Agent:Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12\r\n";
        $header.="Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n";
        $header.="Accept-language: zh-cn,zh;q=0.5\r\n";
        $header.="Accept-Charset: GB2312,utf-8;q=0.7,*;q=0.7\r\n";';
    $header = empty($header) ? $defaultHeader : $header;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);    // https请求 不验证证书和hosts
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array($header)); //模拟的header头
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
?>