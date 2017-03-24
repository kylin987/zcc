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

admin_priv('user_zhuanzhang');


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
	
    $logdb = get_zhuanzhang_list();
	//var_dump($logdb);
	//exit;
    $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', '转账列表');
    $smarty->assign('on', $separate_on);
    $smarty->assign('logdb',        $logdb['logdb']);
	$smarty->assign('start_date',   $_REQUEST['start_date']);
    $smarty->assign('end_date',     $_REQUEST['end_date']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);
    $smarty->assign('action_link',  array('text' => '下载报表','href'=>'#download'));
    assign_query_info();
    $smarty->display('user_zhuanzhang_list.htm');
}



/*------------------------------------------------------ */
//-- ajax帐户信息列表
/*------------------------------------------------------ */
elseif (isset($_REQUEST['act']) && ($_REQUEST['act'] == 'query' ||  $_REQUEST['act'] == 'download'))
{
    
    if (strstr($_REQUEST['start_date'], '-') === false)
    {
        $_REQUEST['start_date'] = local_date('Y-m-d', $_REQUEST['start_date']);
        $_REQUEST['end_date'] = local_date('Y-m-d', $_REQUEST['end_date']);
    }
    /*------------------------------------------------------ */
    //--Excel文件下载
    /*------------------------------------------------------ */
    if ($_REQUEST['act'] == 'download')
    {
        //$file_name = $_REQUEST['start_date'].'_'.$_REQUEST['end_date'] . '_op';
        $logdb = get_zhuanzhang_list(false);
		//var_dump($logdb);
		//exit;
        /*------------------------------------------------------ */
		//--Excel文件下载
		/*------------------------------------------------------ */
	   
		
		header("Content-type: application/vnd.ms-excel; charset=utf-8");
		header("Content-Disposition: attachment; filename=$file_name.xls");

		
		
		error_reporting(E_ALL);  
	  
		date_default_timezone_set('Europe/London');  
	  
		require_once(ROOT_PATH . 'Classes/PHPExcel.php');  
		
		//设定缓存模式为经gzip压缩后存入cache（还有多种方式请百度）  
		$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip;  
		$cacheSettings = array();  
		PHPExcel_Settings::setCacheStorageMethod($cacheMethod,$cacheSettings);  
	  
		echo date('H:i:s') . " Create new PHPExcel object\n";  
		$objPHPExcel = new PHPExcel(); 
		
		echo date('H:i:s') . " Set properties\n"; 	
		
		
		/*设置标题属性*/  
		//字体大小  
		$objPHPExcel->getActiveSheet()->getStyle('A1:L1')->getFont()->setSize(12);  
		//加粗  
		$objPHPExcel->getActiveSheet()->getStyle('A1:L1')->getFont()->setBold(true);  
		
		
		
		
		//表格宽度  
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(18); 
		
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);  
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(14);  
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(18);  
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(16);
		
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(12);  
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(12);  
		$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(14);
		
		$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(10);  
		$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(14);  
		$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(18);  
		$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(16);  

		//合并单元格
		$objPHPExcel->getActiveSheet()->mergeCells('B1:E1');
		$objPHPExcel->getActiveSheet()->mergeCells('F1:H1');
		$objPHPExcel->getActiveSheet()->mergeCells('I1:L1');

		
		
		//设置背景色
		$objPHPExcel->getActiveSheet()->getStyle( 'A1:L2')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$objPHPExcel->getActiveSheet()->getStyle( 'A1:L1')->getFill()->getStartColor()->setARGB('00989998');
		$objPHPExcel->getActiveSheet()->getStyle( 'A2:L2')->getFill()->getStartColor()->setARGB('00dde0df');
		
		
		
	  
		echo date('H:i:s') . " Add some data\n";  
		$objPHPExcel->setActiveSheetIndex(0);  
		
		$objPHPExcel->getActiveSheet()->setCellValue('A1', '转账时间');  
		$objPHPExcel->getActiveSheet()->setCellValue('B1', '转入人信息'); 
		$objPHPExcel->getActiveSheet()->setCellValue('F1', '转账金额');
		$objPHPExcel->getActiveSheet()->setCellValue('I1', '转出人信息'); 
		
		$objPHPExcel->getActiveSheet()->setCellValue('A2', '时间');  
		
		$objPHPExcel->getActiveSheet()->setCellValue('B2', '用户ID');  
		$objPHPExcel->getActiveSheet()->setCellValue('C2', '真实姓名');  
		$objPHPExcel->getActiveSheet()->setCellValue('D2', '电话');  
		$objPHPExcel->getActiveSheet()->setCellValue('E2', '用户等级');  
		
		$objPHPExcel->getActiveSheet()->setCellValue('F2', '自有余额');  
		$objPHPExcel->getActiveSheet()->setCellValue('G2', '分红余额');  
		$objPHPExcel->getActiveSheet()->setCellValue('H2', '总额'); 
		
		$objPHPExcel->getActiveSheet()->setCellValue('I2', '用户ID');  
		$objPHPExcel->getActiveSheet()->setCellValue('J2', '真实姓名');  
		$objPHPExcel->getActiveSheet()->setCellValue('K2', '电话');  
		$objPHPExcel->getActiveSheet()->setCellValue('L2', '用户等级'); 

		
		/////////////////////// 
		$n = 2;
		foreach($logdb['logdb'] as $k=>$v){
			$objPHPExcel->getActiveSheet()->setCellValue('A' . ($k+3), $v['change_time']);
			$objPHPExcel->getActiveSheet()->setCellValue('B' . ($k+3), $v['user_id']);  
			$objPHPExcel->getActiveSheet()->setCellValue('C' . ($k+3), $v['tname']);  
			$objPHPExcel->getActiveSheet()->setCellValue('D' . ($k+3), $v['mobile_phone']);
			$objPHPExcel->getActiveSheet()->setCellValue('E' . ($k+3), $v['rankname']);  
			$objPHPExcel->getActiveSheet()->setCellValue('F' . ($k+3), $v['own_money']); 
			$objPHPExcel->getActiveSheet()->setCellValue('G' . ($k+3), $v['divi_money']); 
			$objPHPExcel->getActiveSheet()->setCellValue('H' . ($k+3), $v['user_money']);  
			$objPHPExcel->getActiveSheet()->setCellValue('I' . ($k+3), $v['other_userid']); 
			$objPHPExcel->getActiveSheet()->setCellValue('J' . ($k+3), $v['o_tname']);  
			$objPHPExcel->getActiveSheet()->setCellValue('K' . ($k+3), $v['o_mobile_phone']);  
			$objPHPExcel->getActiveSheet()->setCellValue('L' . ($k+3), $v['o_rankname']);			 

			$n++;
		}
		
		//绘制边框样式
		//边框样式
		$styleArray = array(  
			'borders' => array(  
				'allborders' => array(  
					//'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的  
					'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框  
					'color' => array('argb' => $color),  
				),  
			),  
		);  
		$objPHPExcel->getActiveSheet()->getStyle('A1:L'.$n)->applyFromArray($styleArray);
		//水平居中  
		$objPHPExcel->getActiveSheet()->getStyle('A1:L'.$n)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::VERTICAL_CENTER);  
		//垂直居中  
		$objPHPExcel->getActiveSheet()->getStyle('A1:L'.$n)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER); 
		
		
		
		//$objPHPExcel->getActiveSheet()->setTitle(("我的订单"));  
		$objPHPExcel->setActiveSheetIndex(0);  
	  
		require_once(ROOT_PATH . 'Classes/PHPExcel/IOFactory.php');  
	  
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5'); 
		
		$_REQUEST['start_time']= $_REQUEST['start_date'];
		$_REQUEST['end_time']= $_REQUEST['end_date'];
		$beginday = mktime(0,0,0,date('m'),date('d')-7,date('Y'))-28800;		
		if(empty($_REQUEST['start_time'])){
			$_REQUEST['start_time'] = date("Y-m-d H:i:s", $beginday);
		}
		if(empty($_REQUEST['end_time'])){
			$_REQUEST['end_time'] = date("Y-m-d H:i:s", time());
		}
		$file_name = "zhuanzhang_".date("Y-m-d-H-i",strtotime($_REQUEST['start_time']))."_".date("Y-m-d-H-i",strtotime($_REQUEST['end_time'])).".xls";
		//$url = getcwd();
		$objWriter->save('down_xls/'.$file_name);  
	  
		//$url = "order.xls";  
	  
		ecs_header("Location: down_xls/$file_name\n");  
		exit;  
    }
    $logdb = get_zhuanzhang_list();
    $smarty->assign('logdb',        $logdb['logdb']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

    make_json_result($smarty->fetch('user_zhuanzhang_list.htm'), '', array('filter' => $logdb['filter'], 'page_count' => $logdb['page_count']));
}



function get_zhuanzhang_list($is_pagination = true)
{

    $sqladd = '';
	
	/* 时间参数 */	
	$beginday = mktime(0,0,0,date('m'),date('d')-7,date('Y'))-28800;		//减去8小时，和标准时间对应
	$endday = mktime(0,0,0,date('m'),date('d'),date('Y'))-28800;
    $filter['start_date'] = empty($_REQUEST['start_date']) ? $beginday : local_strtotime($_REQUEST['start_date']);
    $filter['end_date'] = empty($_REQUEST['end_date']) ? $endday : local_strtotime($_REQUEST['end_date']);
	
	$filter['sort_by']    = empty($_REQUEST['sort_by'])    ? 'change_time' : trim($_REQUEST['sort_by']);
	$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC'     : trim($_REQUEST['sort_order']);
	
	$sqladd = " AND a.change_time >= '".$filter['start_date']."' AND a.change_time < '" . ($filter['end_date'] + 86399) . "'";
	
    
    
    if (isset($_REQUEST['user_id']))
    {
        $sqladd .= ' AND a.user_id=' . $_REQUEST['user_id'];
    }
	
	

    
	$sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('account_log') . " a".
			" WHERE a.other_userid <> 0 AND a.user_money >= 0 $sqladd";
	


    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
    $logdb = array();
    /* 分页大小 */
    $filter = page_and_size($filter);

    
	
	$sql = "SELECT a.*,u.tname,u.user_rank,u.mobile_phone,o.tname as o_tname,o.user_rank as o_user_rank,o.mobile_phone as o_mobile_phone FROM " . $GLOBALS['ecs']->table('account_log') . " a".
			" left join " . $GLOBALS['ecs']->table('users')." as u on a.user_id=u.user_id".
			" left join " . $GLOBALS['ecs']->table('users')." as o on a.other_userid=o.user_id".
			" WHERE a.other_userid <> 0 AND a.user_money >= 0 $sqladd".
			" ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'];
	if ($is_pagination)
    {
        $sql .= " LIMIT " . $filter['start'] . ', ' . $filter['page_size'];
    }
    


    $logdb = $GLOBALS['db']->getAll($sql);

    $count = count($logdb);
    for ($i=0; $i<$count; $i++)
    {
        $logdb[$i]['change_time'] = local_date($GLOBALS['_CFG']['time_format'], $logdb[$i]['change_time']);		
		$logdb[$i]['rankname'] = get_user_rank_info($logdb[$i]['user_id'],2);
		$logdb[$i]['o_rankname'] = get_user_rank_info($logdb[$i]['other_userid'],2);
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