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

admin_priv('user_stock');


/*------------------------------------------------------ */
//-- 列表页
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
	
	/* 时间参数 */
	$beginday = mktime(0,0,0,date('m'),date('d')-7,date('Y'))-28800;		//减去8小时，和标准时间对应
	$endday = mktime(0,0,0,date('m'),date('d'),date('Y'))-28800;
    if (!isset($_REQUEST['start_date']))
    {
        $_REQUEST['start_date'] = local_date('Y-m-d', $beginday);
    }
    if (!isset($_REQUEST['end_date']))
    {
        $_REQUEST['end_date'] = local_date('Y-m-d', $endday);
    }
	if (empty($_REQUEST['user_id']))
    {
        $_REQUEST['user_id'] = null;
    }
	
    $logdb = get_stock_list();
	//var_dump($logdb);
    $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', '股权列表');
    $smarty->assign('on', $separate_on);
    $smarty->assign('logdb',        $logdb['logdb']);
	$smarty->assign('start_date',   $_REQUEST['start_date']);
    $smarty->assign('end_date',     $_REQUEST['end_date']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);
    if (!empty($_GET['auid']))
    {
        $smarty->assign('action_link',  array('text' => $_LANG['back_note'], 'href'=>"users.php?act=edit&id=$_GET[auid]"));
    }
    assign_query_info();
    $smarty->display('user_stock_list.htm');
}



/*------------------------------------------------------ */
//-- ajax帐户信息列表
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
	if (strstr($_REQUEST['start_date'], '-') === false)
    {
        $_REQUEST['start_date'] = local_date('Y-m-d', $_REQUEST['start_date']);
        $_REQUEST['end_date'] = local_date('Y-m-d', $_REQUEST['end_date']);
    }
	
    $logdb = get_stock_list();
    $smarty->assign('logdb',        $logdb['logdb']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

    $sort_flag  = sort_flag($logdb['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('user_stock_list.htm'), '', array('filter' => $logdb['filter'], 'page_count' => $logdb['page_count']));
}



function get_stock_list()
{

    $sqladd = '';
	
	/* 时间参数 */	
	$beginday = mktime(0,0,0,date('m'),date('d')-7,date('Y'))-28800;		//减去8小时，和标准时间对应
	$endday = mktime(0,0,0,date('m'),date('d'),date('Y'))-28800;
    $filter['start_date'] = empty($_REQUEST['start_date']) ? $beginday : local_strtotime($_REQUEST['start_date']);
    $filter['end_date'] = empty($_REQUEST['end_date']) ? $endday : local_strtotime($_REQUEST['end_date']);
	
	$filter['sort_by']    = empty($_REQUEST['sort_by'])    ? 'user_id' : trim($_REQUEST['sort_by']);
	$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC'     : trim($_REQUEST['sort_order']);
	
	$sqladd = " AND a.premium_time >= '".$filter['start_date']."' AND a.premium_time < '" . ($filter['end_date'] + 86399) . "'";
	
    if (isset($_REQUEST['status']))
    {		
		$sqladd .= ' AND a.status = ' . (int)$_REQUEST['status'];
		$filter['status'] = (int)$_REQUEST['status'];
    }
	if (isset($_REQUEST['up_bonus'])) 
	{
		$sqladd .= ' AND a.up_bonus = ' . (int)$_REQUEST['up_bonus'];
        $filter['up_bonus'] = (int)$_REQUEST['up_bonus'];
	}
	if (isset($_REQUEST['ktimes'])) 
	{
		if($_REQUEST['ktimes'] == '1'){
			$sqladd .= ' AND a.ktimes = ' . (int)$_REQUEST['ktimes'];
		}else{
			$sqladd .= ' AND a.ktimes <> "1" ';
		}
        $filter['ktimes'] = (int)$_REQUEST['ktimes']; 
	}
	if (isset($_REQUEST['no_done']))
    {
        $sqladd .= ' AND a.now_assess_value < 5000';
		$filter['no_done'] = (int)$_REQUEST['no_done'];
    }
    
    if (isset($_REQUEST['user_id']))
    {
        $sqladd .= ' AND a.user_id=' . $_REQUEST['user_id'];
    }
	
	

    
	$sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('user_stock') . " a".
			" WHERE a.user_id <> 0 $sqladd";
	


    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
    $logdb = array();
    /* 分页大小 */
    $filter = page_and_size($filter);

    
	
	$sql = "SELECT a.* FROM " . $GLOBALS['ecs']->table('user_stock') . " a".
			" WHERE a.user_id <> 0 $sqladd".
			" ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
			" LIMIT " . $filter['start'] . ",$filter[page_size]";

    


    $logdb = $GLOBALS['db']->getAll($sql);

    $count = count($logdb);
    for ($i=0; $i<$count; $i++)
    {
        $logdb[$i]['premium_time'] = local_date($GLOBALS['_CFG']['date_format'], $logdb[$i]['premium_time']);		
		$logdb[$i]['nickname'] = getChenghu($logdb[$i]['user_id']);
		$logdb[$i]['user_name'] = get_username2($logdb[$i]['user_id'],1);
		$logdb[$i]['tname'] = get_username2($logdb[$i]['user_id'],2);
		$logdb[$i]['rankname'] = get_user_rank_info($logdb[$i]['user_id'],2);
		$logdb[$i]['ktimes'] = $logdb[$i]['ktimes'] == '1' || $logdb[$i]['ktimes'] == '0'?"初次申请":"<font color=red>".$logdb[$i]['ktimes']."</font>次追加";
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
//获取昵称或用户名
function getChenghu($u_id){
	$sql = "SELECT nickname from ". $GLOBALS['ecs']->table('wechat_user') ." where ect_uid = ".$u_id;
	$nickname = $GLOBALS['db']->getOne($sql);
	if (empty($nickname)) {
		$sql2 = "SELECT user_name from ". $GLOBALS['ecs']->table('users') ." where user_id = ".$u_id;
		$username = $GLOBALS['db']->getOne($sql2);
		return 	$username;
	}
	return $nickname; 
}
//获取用户名
function get_username2($u_id,$type = ''){	
	$sql2 = "SELECT user_name,tname from ". $GLOBALS['ecs']->table('users') ." where user_id = ".$u_id;
	$username = $GLOBALS['db']->getRow($sql2);
	if($type = '2'){
		return 	$username['tname'];
	}else{
		return 	$username['user_name'];	
	}
		
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