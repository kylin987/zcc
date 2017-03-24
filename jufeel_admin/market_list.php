<?php

/**
 * ECSHOP 运营明细列表程序
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: sale_list.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/statistic.php');
$smarty->assign('lang', $_LANG);

if (isset($_REQUEST['act']) && ($_REQUEST['act'] == 'query' ||  $_REQUEST['act'] == 'download'))
{
    /* 检查权限 */
    check_authz_json('sale_order_stats');
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
        $file_name = $_REQUEST['start_date'].'_'.$_REQUEST['end_date'] . '_market';
        $market_list_data = get_market_list(false);
        header("Content-type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=$file_name.xls");

        /* 文件标题 */
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_REQUEST['start_date']. $_LANG['to'] .$_REQUEST['end_date']. $_LANG['op_list']) . "\t\n";

        /* 商品名称,订单号,商品数量,销售价格,销售日期 */
        echo ecs_iconv(EC_CHARSET, 'GB2312', '统计时间') . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', '订单数量') . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['order_sn']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['cost']) . "\t";
		echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['pay_money']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['profit']) . "\t";
        echo ecs_iconv(EC_CHARSET, 'GB2312', $_LANG['sell_date']) . "\t\n";

        foreach ($op_sales_list['op_list_data'] AS $key => $value)
        {
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['goods_name']) . "\t";
			echo ecs_iconv(EC_CHARSET, 'GB2312', $value['goods_number']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', '[ ' . $value['order_sn'] . ' ]') . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['good_cost'] . '+' . $value['op_cost'] . '+' . $value['other_cost'] . '=' . $value['all_cost']) . "\t";
			if($value['shipping_fee'] <> 0){
				echo ecs_iconv(EC_CHARSET, 'GB2312', $value['pay_goods_money'] . '+' . $value['shipping_fee']) . "\t";
			}else{
				echo ecs_iconv(EC_CHARSET, 'GB2312', $value['pay_goods_money']) . "\t";
			}
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['down_profit']) . "\t";
            echo ecs_iconv(EC_CHARSET, 'GB2312', $value['change_time']) . "\t";
            echo "\n";
        }
        exit;
    }
    $market_list_data = get_market_list();
    $smarty->assign('market_sales_list', $market_list_data['market_list_data']);
    $smarty->assign('filter',       $market_list_data['filter']);
    $smarty->assign('record_count', $market_list_data['record_count']);
    $smarty->assign('page_count',   $market_list_data['page_count']);

    make_json_result($smarty->fetch('market_list.htm'), '', array('filter' => $market_list_data['filter'], 'page_count' => $market_list_data['page_count']));
}
/*------------------------------------------------------ */
//--商品明细列表
/*------------------------------------------------------ */
else
{
    /* 权限判断 */
    admin_priv('sale_order_stats');
    /* 时间参数 */
    if (!isset($_REQUEST['start_date']))
    {
        $start_date = local_strtotime('-7 days');
    }
    if (!isset($_REQUEST['end_date']))
    {
        $end_date = local_strtotime('today');
    }
    
    $market_list_data = get_market_list();
	//var_dump($market_list_data);
    /* 赋值到模板 */
    $smarty->assign('filter',       $market_list_data['filter']);
    $smarty->assign('record_count', $market_list_data['record_count']);
    $smarty->assign('page_count',   $market_list_data['page_count']);
    $smarty->assign('market_sales_list', $market_list_data['market_list_data']);
    $smarty->assign('full_page',        1);
    $smarty->assign('start_date',       local_date('Y-m-d', $start_date));
    $smarty->assign('end_date',         local_date('Y-m-d', $end_date));
    $smarty->assign('ur_here',      $_LANG['market_list']);
    $smarty->assign('cfg_lang',     $_CFG['lang']);
    $smarty->assign('action_link',  array('text' => $_LANG['down_sales'],'href'=>'#download'));

    /* 显示页面 */
    assign_query_info();
    $smarty->display('market_list.htm');
}
/*------------------------------------------------------ */
//--获取运营明细需要的函数
/*------------------------------------------------------ */
/**
 * 取得运营明细数据信息
 * @param   bool  $is_pagination  是否分页
 * @return  array   运营明细数据
 */
function get_market_list($is_pagination = true){

    /* 时间参数 */
    $filter['start_date'] = empty($_REQUEST['start_date']) ? local_strtotime('-7 days') : local_strtotime($_REQUEST['start_date']);
    $filter['end_date'] = empty($_REQUEST['end_date']) ? local_strtotime('today') : local_strtotime($_REQUEST['end_date']);
  
    /* 查询数据的条件 */
    $where = " WHERE tj_time >= '".$filter['start_date']."' AND tj_time < '" . ($filter['end_date'] + 86400) . "'";
    
    $sql = "SELECT COUNT(m_id) FROM " .
           $GLOBALS['ecs']->table('ac_log') . 
           $where;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    /* 分页大小 */
    $filter = page_and_size($filter);

    $sql = 'SELECT * '.
           "FROM " . $GLOBALS['ecs']->table('ac_log'). 
           $where. " ORDER BY tj_time DESC, m_id DESC";
    if ($is_pagination)
    {
        $sql .= " LIMIT " . $filter['start'] . ', ' . $filter['page_size'];
    }

    $market_list_data = $GLOBALS['db']->getAll($sql);
    foreach ($market_list_data as $key => $item)
    {        
		$market_list_data[$key]['format_add_profit'] = price_format($market_list_data[$key]['add_profit']);		
		$market_list_data[$key]['format_add_market_value'] = price_format($market_list_data[$key]['add_market_value']);
		$market_list_data[$key]['format_now_no_dis_profit'] = price_format($market_list_data[$key]['now_no_dis_profit']);
		$market_list_data[$key]['format_now_market_value'] = price_format($market_list_data[$key]['now_market_value']);
        $market_list_data[$key]['tj_time']  = local_date($GLOBALS['_CFG']['time_format'], $market_list_data[$key]['tj_time']);		
    }
    $arr = array('market_list_data' => $market_list_data, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    return $arr;
}
	//获取订单的发货时间
	function get_shipping_time($order_id){		
		$sql = 'SELECT shipping_time FROM' .
          	 $GLOBALS['ecs']->table('order_info') .
         	  " WHERE order_id = $order_id";
    	return $GLOBALS['db']->getOne($sql);
		
		
	}
?>