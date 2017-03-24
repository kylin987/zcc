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

admin_priv('spree_manage');


/*------------------------------------------------------ */
//-- 列表页
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $logdb = get_spree_list();
	//var_dump($logdb);
    $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', '大礼包审核');
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
    $smarty->display('user_spree_list.htm');
}
/*
    删除申请
*/
elseif ($_REQUEST['act'] == 'del')
{        
	
	$sql = "UPDATE " . $GLOBALS['ecs']->table('apply_list') .
               " SET is_del = 1" .
               " WHERE id = '" . $_GET['id'] . "'";
    $GLOBALS['db']->query($sql);

    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'user_apply_manage.php?act=list');
    sys_msg('删除成功', 0, $link);
}
/*
    驳回申请
*/
elseif ($_REQUEST['act'] == 'bohui')
{        
	
	$sql = "UPDATE " . $GLOBALS['ecs']->table('apply_list') .
               " SET is_apply = 2" .
               " WHERE id = '" . $_GET['id'] . "'";
    $GLOBALS['db']->query($sql);

    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'user_apply_manage.php?act=list');
    sys_msg('已驳回', 0, $link);
}
/*
    审核代理
*/
elseif ($_REQUEST['act'] == 'shenhe')
{        
	$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_spree') .
				" WHERE is_del = 0 AND id = '" . $_GET['id'] . "'";
	$result = $GLOBALS['db']->getRow($sql);
	if($result)	{
		if($result['is_check']==1){
			$cent="该礼包已经审核，不要重复审核！";
		}else{			
			//开始审核
			$time = gmtime();
			$sql = "UPDATE " . $GLOBALS['ecs']->table('user_spree') .
					   " SET is_check = 1 , check_time = '" . $time ."'".
					   " WHERE id = '" . $_GET['id'] . "'";
			$GLOBALS['db']->query($sql);			
			
			/* 记录管理员操作 */
			admin_log(addslashes($result['spree_sn']), 'jihuo', 'spree');
			
			$cent="审核成功";
			
		}
	}else{
		$cent="未查到该订单";
	}

    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'user_spree_manage.php?act=list');
    sys_msg($cent, 0, $link);
}
/*
    大礼包定价页面
*/
elseif ($_REQUEST['act'] == 'price')
{		
    $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', '大礼包定价');
    
    $smarty->assign('price_list', get_all_spree_price());
    $smarty->display('user_spree_price.htm');
}

/*
    大礼包添加定价
*/
elseif ($_REQUEST['act'] == 'add_price')
{  
	$year = intval($_POST['year']);
    $month = intval($_POST['month']);
	$price = floatval($_POST['price']);	
	if(empty($year) || empty($month) || empty($month) || ($month >12)){
		$cent = "年、月、价格必须填写完整正确";
		/* 提示信息 */
		$link[] = array('text' => $_LANG['go_back'], 'href'=>'user_spree_manage.php?act=price');
		sys_msg($cent, 0, $link);
	}
	$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('spree_price') .
			" WHERE is_del = 0 AND year = " . $year . " AND month = " . $month;
    $rs = $GLOBALS['db']->getOne($sql);
	if($rs){
		$cent = "该价格已存在";
		/* 提示信息 */
		$link[] = array('text' => $_LANG['go_back'], 'href'=>'user_spree_manage.php?act=price');
		sys_msg($cent, 0, $link);
	}else{
		$time = gmtime();
		$sql = "INSERT INTO " . $GLOBALS['ecs']->table('spree_price') . "( year, month, price, admin_name, add_time)".
																  " VALUES ( '$year', '$month', '$price', '$_SESSION[admin_name]', '$time')";
		$sp_id = $GLOBALS['db']->query($sql);
		if($sp_id){
			$cent = "价格添加成功";
		}else{
			$cent = "价格添加失败";
		}
		/* 提示信息 */
		$link[] = array('text' => $_LANG['go_back'], 'href'=>'user_spree_manage.php?act=price');
		sys_msg($cent, 0, $link);		
	}
	
}
/*
    删除定价
*/
elseif ($_REQUEST['act'] == 'price_del')
{        
	
	$sql = "UPDATE " . $GLOBALS['ecs']->table('spree_price') .
               " SET is_del = 1" .
               " WHERE sp_id = '" . $_GET['id'] . "'";
    $GLOBALS['db']->query($sql);

    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'user_spree_manage.php?act=price');
    sys_msg('删除成功', 0, $link);
}
/*
    生成代理要约
*/
elseif ($_REQUEST['act'] == 'make')
{  
	
}

/*------------------------------------------------------ */
//-- ajax帐户信息列表
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $logdb = get_spree_list();
    $smarty->assign('logdb',        $logdb['logdb']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

    $sort_flag  = sort_flag($logdb['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('user_spree_list.htm'), '', array('filter' => $logdb['filter'], 'page_count' => $logdb['page_count']));
}



function get_spree_list()
{

    $sqladd = '';
    if (isset($_REQUEST['is_check']))
    {		
		$sqladd = ' AND a.is_check = ' . (int)$_REQUEST['is_check'];
		$filter['is_check'] = (int)$_REQUEST['is_check'];
    }
	
    
    if (isset($_GET['user_id']))
    {
        $sqladd = ' AND a.user_id=' . $_GET['user_id'];
    }
	
	

    
	$sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('user_spree') . " a".
			" WHERE a.is_del = 0 $sqladd";
	


    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
    $logdb = array();
    /* 分页大小 */
    $filter = page_and_size($filter);

    
	
	$sql = "SELECT a.* FROM " . $GLOBALS['ecs']->table('user_spree') . " a".
			" WHERE a.is_del = 0 $sqladd".
			" ORDER BY id DESC" .
			" LIMIT " . $filter['start'] . ",$filter[page_size]";

    


    $logdb = $GLOBALS['db']->getAll($sql);

    $count = count($logdb);
    for ($i=0; $i<$count; $i++)
    {
        $logdb[$i]['ex_time'] = local_date($GLOBALS['_CFG']['date_format'], $logdb[$i]['ex_time']);	
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

//获取用户申请代理订单的信息
function get_daili_info($aid){
	$sql = "SELECT * from ". $GLOBALS['ecs']->table('apply_list') ." where id = ".$aid;
	$arr = $GLOBALS['db']->getRow($sql);
	if($type ==1){
		return $arr['tname'];
	}elseif($type==2){
		return date('Y-m-d',$arr['time']);
	}else{		
		return $arr;
	}			
}

//获取所有的礼包价格
function get_all_spree_price(){
	$sql = "SELECT a.* FROM " . $GLOBALS['ecs']->table('spree_price') . " a".
			" WHERE a.is_del = 0 ".
			" ORDER BY sp_id DESC";
    return $GLOBALS['db']->getAll($sql);	
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