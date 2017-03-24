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

admin_priv('user_dividend');


/*------------------------------------------------------ */
//-- 列表页
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $logdb = get_dividend_list();
	//var_dump(get_all_user_stock());

    $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', '分红');
    $smarty->assign('on', $separate_on);
    $smarty->assign('no_dis_profit',  get_shop_config_value('no_dis_profit'));
    $smarty->assign('logdb',        $logdb['logdb']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);
    if (!empty($_GET['auid']))
    {
        $smarty->assign('action_link',  array('text' => $_LANG['back_note'], 'href'=>"users.php?act=edit&id=$_GET[auid]"));
    }
    assign_query_info();
    $smarty->display('user_dividend_list.htm');
}



/*
    添加分红
*/
elseif ($_REQUEST['act'] == 'add')
{  
	$fh_value = intval($_POST['fh_value']);
	if(empty($fh_value) || (floatval($fh_value) <=0)){
		$cent = "参数错误";
		/* 提示信息 */
		$link[] = array('text' => $_LANG['go_back'], 'href'=>'user_dividend.php?act=list');
		sys_msg($cent, 0, $link);
	}
	$time = gmtime();
	$sql = "INSERT INTO " . $GLOBALS['ecs']->table('user_fenhong') . "( fh_value, add_time)".
															  " VALUES ( '$fh_value', '$time')";
	$id = $GLOBALS['db']->query($sql);
	if($id){
		$cent = "分红添加成功，请手动执行或延期执行";
		$getID=mysql_insert_id();
		/* 记录管理员操作 */
    	admin_log(addslashes($getID), 'add', 'dividend');
	}else{
		$cent = "分红添加失败";
	}
	/* 提示信息 */
	$link[] = array('text' => $_LANG['go_back'], 'href'=>'user_dividend.php?act=list');
	sys_msg($cent, 0, $link);
	
}
/*
    删除分红
*/
elseif ($_REQUEST['act'] == 'del')
{        
	
	$sql = "UPDATE " . $GLOBALS['ecs']->table('user_fenhong') .
               " SET is_del = 1" .
               " WHERE id = '" . $_GET['id'] . "'";
    $GLOBALS['db']->query($sql);

    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'user_dividend.php?act=list');
    sys_msg('删除成功', 0, $link);
}

elseif($_REQUEST['act'] == 'xy'){
	
}	
/*
    立即分红
*/
elseif ($_REQUEST['act'] == 'liji')
{
	//分红前更新
	change_user_stock('5');
	//开始分红
	$sql = "SELECT fh_value FROM " . $GLOBALS['ecs']->table('user_fenhong') . " a".
			" WHERE a.is_del = 0 AND a.is_do = 0 AND id = ".$_GET['id'];
    $fh_value = $GLOBALS['db']->getOne($sql);
	$result_value = user_dividend_begin($fh_value);
	
	
	
	//更新分红信息
	$sql = "UPDATE " . $GLOBALS['ecs']->table('user_fenhong') .
               " SET is_do = 1," .
               " do_time = '".gmtime() .
               "', do_result = '" . $result_value .
               "' WHERE id = '" . $_GET['id'] . "'";
    $GLOBALS['db']->query($sql);

    //更新未分配余额
    change_shop_value('no_dis_profit',$result_value,3);
    log_profit($result_value*-1,1,"平台分红");
	
	//分红后更新
	change_user_stock('6');


    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'user_dividend.php?act=list');
    sys_msg('分红成功', 0, $link);
}
/*
    凌晨分红
*/
elseif ($_REQUEST['act'] == 'lingchen')
{        
	
	$sql = "UPDATE " . $GLOBALS['ecs']->table('user_fenhong') .
               " SET is_do = 2" .
               " WHERE id = '" . $_GET['id'] . "'";
    $id=$GLOBALS['db']->query($sql);
    if($id){
    	/* 记录管理员操作 */
    	admin_log(addslashes($_GET['id']), 'setup', 'lc_dividend');
    }

    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'user_dividend.php?act=list');
    sys_msg('设置成功', 0, $link);
}


/*------------------------------------------------------ */
//-- ajax帐户信息列表
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $logdb = get_dividend_list();
    $smarty->assign('logdb',        $logdb['logdb']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

    $sort_flag  = sort_flag($logdb['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('user_dividend_list.htm'), '', array('filter' => $logdb['filter'], 'page_count' => $logdb['page_count']));
}



function get_dividend_list()
{

    $sqladd = '';	

    
	$sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('user_fenhong') . " a".
			" WHERE a.is_del = 0 $sqladd";
	


    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
    $logdb = array();
    /* 分页大小 */
    $filter = page_and_size($filter);

    
	
	$sql = "SELECT a.* FROM " . $GLOBALS['ecs']->table('user_fenhong') . " a".
			" WHERE a.is_del = 0 $sqladd".
			" ORDER BY id DESC" .
			" LIMIT " . $filter['start'] . ",$filter[page_size]";

    


    $logdb = $GLOBALS['db']->getAll($sql);

    $count = count($logdb);
    for ($i=0; $i<$count; $i++)
    {
        $logdb[$i]['do_time'] = $logdb[$i]['do_time'] == 0 ? '':local_date($GLOBALS['_CFG']['time_format'], $logdb[$i]['do_time']);	
		$logdb[$i]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $logdb[$i]['add_time']);
		$logdb[$i]['do_result'] = $logdb[$i]['do_result'] == '0.00'?'':$logdb[$i]['do_result'];
    }
    
    $arr = array('logdb' => $logdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
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