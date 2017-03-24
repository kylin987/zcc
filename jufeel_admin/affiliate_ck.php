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

admin_priv('affiliate_ck');
$timestamp = time();

$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
empty($affiliate) && $affiliate = array();
$separate_on = $affiliate['on'];

/*------------------------------------------------------ */
//-- 分成页
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
	
    $logdb = get_affiliate_ck();
    $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', $_LANG['affiliate_ck']);
    $smarty->assign('on', $separate_on);
    $smarty->assign('logdb',        $logdb['logdb']);
    $smarty->assign('filter',       $logdb['filter']);
	$smarty->assign('start_date',   $_REQUEST['start_date']);
    $smarty->assign('end_date',     $_REQUEST['end_date']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);
    if (!empty($_GET['auid']))
    {
        $smarty->assign('action_link',  array('text' => $_LANG['back_note'], 'href'=>"users.php?act=edit&id=$_GET[auid]"));
    }
    assign_query_info();
    $smarty->display('affiliate_ck_list.htm');
}
/*------------------------------------------------------ */
//-- 分页
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
	if (strstr($_REQUEST['start_date'], '-') === false)
    {
        $_REQUEST['start_date'] = local_date('Y-m-d', $_REQUEST['start_date']);
        $_REQUEST['end_date'] = local_date('Y-m-d', $_REQUEST['end_date']);
    }
	
    $logdb = get_affiliate_ck();
    $smarty->assign('logdb',        $logdb['logdb']);
    $smarty->assign('on', $separate_on);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

    $sort_flag  = sort_flag($logdb['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('affiliate_ck_list.htm'), '', array('filter' => $logdb['filter'], 'page_count' => $logdb['page_count']));
}
/*
    取消分成，不再能对该订单进行分成
*/
elseif ($_REQUEST['act'] == 'del')
{
    $oid = (int)$_REQUEST['oid'];
    $stat = $db->getOne("SELECT is_separate FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$oid'");
    if (empty($stat))
    {
        $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
               " SET is_separate = 2" .
               " WHERE order_id = '$oid'";
        $db->query($sql);
    }
    $links[] = array('text' => $_LANG['affiliate_ck'], 'href' => 'affiliate_ck.php?act=list');
    sys_msg($_LANG['edit_ok'], 0 ,$links);
}
/*
    撤销某次分成，将已分成的收回来
*/
elseif ($_REQUEST['act'] == 'rollback')
{
    $logid = (int)$_REQUEST['logid'];
    $stat = $db->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('affiliate_log') . " WHERE log_id = '$logid'");
    if (!empty($stat))
    {
        if($stat['separate_type'] == 1)
        {
            //推荐订单分成
            $flag = -2;
        }
        else
        {
            //推荐注册分成
            $flag = -1;
        }
        log_account_change($stat['user_id'], -$stat['money'], 0, -$stat['point'], 0, $_LANG['loginfo']['cancel']);
        $sql = "UPDATE " . $GLOBALS['ecs']->table('affiliate_log') .
               " SET separate_type = '$flag'" .
               " WHERE log_id = '$logid'";
        $db->query($sql);
    }
    $links[] = array('text' => $_LANG['affiliate_ck'], 'href' => 'affiliate_ck.php?act=list');
    sys_msg($_LANG['edit_ok'], 0 ,$links);
}
/*
    分成
*/
elseif ($_REQUEST['act'] == 'separate')
{
    include_once(ROOT_PATH . 'includes/lib_order.php');
    $affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
	
    empty($affiliate) && $affiliate = array();

    $separate_by = $affiliate['config']['separate_by'];

    $oid = (int)$_REQUEST['oid'];

    $row = $db->getRow("SELECT o.order_sn, o.is_separate,o.city,o.district, (o.goods_amount - o.discount - integral_money - bonus) AS goods_amount, o.user_id FROM " . $GLOBALS['ecs']->table('order_info') . " o".
                    " LEFT JOIN " . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id".
            " WHERE order_id = '$oid'");

    $order_sn = $row['order_sn'];
	

    if (empty($row['is_separate']))
    {
        $affiliate['config']['level_point_all'] = (float)$affiliate['config']['level_point_all'];
        $affiliate['config']['level_money_all'] = (float)$affiliate['config']['level_money_all'];
        if ($affiliate['config']['level_point_all'])
        {
            $affiliate['config']['level_point_all'] /= 100;
        }
        if ($affiliate['config']['level_money_all'])
        {
            $affiliate['config']['level_money_all'] /= 100;
        }
        $money = round($affiliate['config']['level_money_all'] * $row['goods_amount'],2);
        $integral = integral_to_give(array('order_id' => $oid, 'extension_code' => ''));
        $point = round($affiliate['config']['level_point_all'] * intval($integral['custom_points']), 0);
		
		//各阶段分成情况
		$fc['teyue'] = (float)$affiliate['config']['cash_lev_teyue'] / 100;
		$fc['d1'] = (float)$affiliate['config']['cash_lev_1'] / 100;
		$fc['d2'] = (float)$affiliate['config']['cash_lev_2'] / 100;		
		
        if(empty($separate_by))
        {
			/*   先进行现金分成 start  */
		
			//获取本订单的现金分成
			$all_give_cash = get_order_give_cash($oid);
			//初始化份额		
			$set_teyue = array('cash'=>0,'user_id'=>'','user_name'=>'');
			$set_d1 = array('cash'=>0,'user_id'=>'','user_name'=>'');
			$set_d2 = array('cash'=>0,'user_id'=>'','user_name'=>'');
			
			//县级代理商包括（县和小城市的区）
			//市/区代理商包括(小城市和大城市的区)
			
			if($all_give_cash != 0){
				$up_userinfo = get_up_userinfo($row['user_id']);
				$jxs_district_info = get_jxs_info($row['district']);		//获取收货区县的经销商信息
				$jxs_city_info = get_jxs_info($row['city']);		//获取收货市的经销商信息	
				if($up_userinfo['up_rank_id'] == 138){		//如果上级用户是特约经销商				
					$set_teyue['cash'] = $fc['teyue'];		//分给特约经销商它的份额并下面列出它的资料
					$set_teyue['user_id'] = $up_userinfo['up_id'];			
					$set_teyue['user_name'] = $up_userinfo['up_username'];
					if($jxs_district_info){		//如果有该区有代理商
						if($jxs_district_info['proxy_lev'] == 1){		//如果该代理商是县级代理商
							$set_d1['cash'] = $fc['d1'];		//分给县级经销商它的份额并在下面列出它的信息
							$set_d1['user_id'] = $jxs_district_info['user_id'];
							$set_d1['user_name'] = $jxs_district_info['user_name'];
							if($jxs_city_info){		//如果有市/区级代理
								$set_d2['cash'] = $fc['d2'];	//分给市/区级经销商它的份额并列出它的信息
								$set_d2['user_id'] = $jxs_city_info['user_id'];
								$set_d2['user_name'] = $jxs_city_info['user_name'];
							}
						}elseif($jxs_district_info['proxy_lev'] == 2){		//如果该代理商是市/区级代理
							$set_d2['cash'] = $fc['d1']+$fc['d2'];		//直接拿县+市的分成额总和并列出它的信息
							$set_d2['user_id'] = $jxs_district_info['user_id'];
							$set_d2['user_name'] = $jxs_district_info['user_name'];
						}else{
							print("上级等级有错误");
							exit;
						}	
					}else{		//如果有该区没有代理商
						if($jxs_city_info){		//如果市级有代理商，说明是小城市的市代
							$set_d2['cash'] = $fc['d1']+$fc['d2'];		//直接拿县+市的分成额总和并列出它的信息
							$set_d2['user_id'] = $jxs_city_info['user_id'];
							$set_d2['user_name'] = $jxs_city_info['user_name'];
						}
					}
				}else{
					//如果上级用户不是特约经销商				
					if($jxs_district_info){		//如果有该区有代理商
						if($jxs_district_info['proxy_lev'] == 1){		//如果该代理商是县级代理商
							$set_d1['cash'] = $fc['teyue']+$fc['d1'];		//拿特约+县级经销商的份额
							$set_d1['user_id'] = $jxs_district_info['user_id'];
							$set_d1['user_name'] = $jxs_district_info['user_name'];
							if($jxs_city_info){		//如果有市/区级代理
								$set_d2['cash'] = $fc['d2'];	//分给市/区级经销商它的份额
								$set_d2['user_id'] = $jxs_city_info['user_id'];
								$set_d2['user_name'] = $jxs_city_info['user_name'];
							}
						}elseif($jxs_district_info['proxy_lev'] == 2){		//如果该代理商是市/区级代理
							$set_d2['cash'] = $fc['teyue']+$fc['d1']+$fc['d2'];		//直接拿特约+县+市的分成额总和
							$set_d2['user_id'] = $jxs_district_info['user_id'];
							$set_d2['user_name'] = $jxs_district_info['user_name'];
						}else{
							print("上级等级有错误");
							exit;
						}	
					}else{		//如果有该区没有代理商
						if($jxs_city_info){		//如果市级有代理商，说明是小城市的市代
							$set_d2['cash'] = $fc['teyue']+$fc['d1']+$fc['d2'];		//直接拿特约+县+市的分成额总和
							$set_d2['user_id'] = $jxs_city_info['user_id'];
							$set_d2['user_name'] = $jxs_city_info['user_name'];
						}
					}
				}
				$chenghu=getChenghu($row['user_id']);
				if($set_teyue['cash'] != 0){							
					$info = sprintf($_LANG['separate_info_cash'], addslashes($chenghu),$order_sn,'');
					log_account_change($set_teyue['user_id'],$set_teyue['cash']*$all_give_cash , 0, 0, 0, $info);
					write_affiliate_log($oid, $set_teyue['user_id'], $set_teyue['user_name'], $set_teyue['cash']*$all_give_cash, 0, $separate_by);
				}
				if($set_d1['cash'] != 0){				
					$info = sprintf($_LANG['separate_info_cash'], addslashes($chenghu),$order_sn,'区域分成');
					log_account_change($set_d1['user_id'],$set_d1['cash']*$all_give_cash , 0, 0, 0, $info);
					write_affiliate_log($oid, $set_d1['user_id'], $set_d1['user_name'], $set_d1['cash']*$all_give_cash, 0, $separate_by);
				}
				if($set_d2['cash'] != 0){				
					$info = sprintf($_LANG['separate_info_cash'], addslashes($chenghu),$order_sn,'区域分成');
					log_account_change($set_d2['user_id'],$set_d2['cash']*$all_give_cash, 0, 0, 0, $info);
					write_affiliate_log($oid, $set_d2['user_id'], $set_d2['user_name'], $set_d2['cash']*$all_give_cash, 0, $separate_by);
				}
				//附加功能，如果没有分完，分给ID=9的用户
				$ts_id =9;
				$ts_username = "总裁基金";
				$yifen_cash = $set_teyue['cash']+$set_d1['cash']+$set_d2['cash'];
				$all_config_cash = $fc['teyue']+$fc['d1']+$fc['d2'];
				$shengyu_cash = $all_config_cash - $yifen_cash;
				if($shengyu_cash > 0){
					$info = sprintf($_LANG['separate_info_cash'], addslashes($chenghu),$order_sn,'区域分成');
					log_account_change($ts_id,$shengyu_cash*$all_give_cash, 0, 0, 0, $info);
					write_affiliate_log($oid, $ts_id, $ts_username, $shengyu_cash*$all_give_cash, 0, $separate_by);
				}
			}
			/*  现金分成 end  */
            //推荐注册分成
            $num = count($affiliate['item']);
			
            for ($i=0; $i < $num; $i++)
            {
                $affiliate['item'][$i]['level_point'] = (float)$affiliate['item'][$i]['level_point'];
                $affiliate['item'][$i]['level_money'] = (float)$affiliate['item'][$i]['level_money'];
                if ($affiliate['item'][$i]['level_point'])
                {
                    $affiliate['item'][$i]['level_point'] /= 100;
                }
                if ($affiliate['item'][$i]['level_money'])
                {
                    $affiliate['item'][$i]['level_money'] /= 100;
                }
                $setmoney = round($money * $affiliate['item'][$i]['level_money'], 2);
                $setpoint = round($point * $affiliate['item'][$i]['level_point'], 0);
				
                $row = $db->getRow("SELECT o.parent_id as user_id,o.user_rank as o_rank,o.user_id as o_uid,u.user_rank,u.user_name FROM " . $GLOBALS['ecs']->table('users') . " o" .
                        " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.parent_id = u.user_id".
                        " WHERE o.user_id = '$row[user_id]'"
                    );
                $up_uid = $row['user_id'];	//上级id
				$up_uid_rankid = get_user_rankid($up_uid);
				$o_uid = $row['o_uid'];		//下级ID
                if (empty($up_uid) || empty($row['user_name']))
                {
                    break;
                }
                else
                {
					
					
					
					if($up_uid_rankid == '135'){
						//$setmoney = 0;
						//$setpoint = 0;
						$up_uid = 9;
					}
					
					$chenghu=getChenghu($o_uid);
                    $info = sprintf($_LANG['separate_info_k2'], addslashes($chenghu),$order_sn, $setmoney);
                    log_account_change($up_uid,$setmoney , 0, 0, $setpoint, $info);
                    write_affiliate_log($oid, $up_uid, $row['user_name'], $setmoney, $setpoint, $separate_by);
					/* 微信通 分成提醒微信上级用户 start  by kylin */
					/*
					$file = '../mobile/include/apps/default/controller/WechatController.class.php';
					if(file_exists($file) && $up_uid > 0){
						$sql = 'SELECT name, config FROM '.$GLOBALS['ecs']->table('wechat_extend').' where enable = 1 and command = "ck_remind" limit 1';
						$remind = $GLOBALS['db']->getRow($sql);
						$remind_title = $remind['name'] ? $remind['name'] : '您获得了金币分成';
						$chenghu=getChenghu($o_uid);											
						$msg="您获得了来自".$chenghu."的佣金分成";
						$content = '';
						if($remind['config']){
							$config = unserialize($remind['config']);
							$content = str_replace('[$chenghu]', $chenghu, $config['template']);
						}
						$sql1 = 'SELECT openid FROM '.$GLOBALS['ecs']->table('wechat_user').' where ect_uid = '.$up_uid;
						$openid = $GLOBALS['db']->getOne($sql1);
						if(!empty($remind_title) && !empty($openid)){
							$order_url = $GLOBALS['ecs']->url() . 'mobile/?c=user&a=account_detail2';
							$order_url = urlencode(base64_encode($order_url));
							$url = $GLOBALS['ecs']->url() . 'mobile/?c=api&openid='.$openid.'&title='.urlencode($remind_title).'&msg='.urlencode($content).'&url='.$order_url;
							curlGet($url);
						}
					}
					*/
					/* 微信通 分成提醒微信上级用户 end  kylin */
                }
            }			
			
        }
        else
        {
            //推荐订单分成
            $row = $db->getRow("SELECT o.parent_id, u.user_name FROM " . $GLOBALS['ecs']->table('order_info') . " o" .
                    " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.parent_id = u.user_id".
                    " WHERE o.order_id = '$oid'"
                );
            $up_uid = $row['parent_id'];
            if(!empty($up_uid) && $up_uid > 0)
            {
                $info = sprintf($_LANG['separate_info'], $order_sn, $money, $point);
                log_account_change($up_uid, $money, 0, $point, 0, $info);
                write_affiliate_log($oid, $up_uid, $row['user_name'], $money, $point, $separate_by);
            }
            else
            {
                $links[] = array('text' => $_LANG['affiliate_ck'], 'href' => 'affiliate_ck.php?act=list');
                sys_msg($_LANG['edit_fail'], 1 ,$links);
            }
        }
        $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
               " SET is_separate = 1" .
               " WHERE order_id = '$oid'";
        $db->query($sql);
    }
    $links[] = array('text' => $_LANG['affiliate_ck'], 'href' => 'affiliate_ck.php?act=list');
    sys_msg($_LANG['edit_ok'], 0 ,$links);
}
function get_affiliate_ck()
{

    $affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
    empty($affiliate) && $affiliate = array();
    $separate_by = $affiliate['config']['separate_by'];

    $sqladd = '';
	
	/* 时间参数 */	
	$beginday = mktime(0,0,0,date('m'),date('d')-7,date('Y'))-28800;		//减去8小时，和标准时间对应
	$endday = mktime(0,0,0,date('m'),date('d'),date('Y'))-28800;
    $filter['start_date'] = empty($_REQUEST['start_date']) ? $beginday : local_strtotime($_REQUEST['start_date']);
    $filter['end_date'] = empty($_REQUEST['end_date']) ? $endday : local_strtotime($_REQUEST['end_date']);
	
	$sqladd = " AND o.add_time >= '".$filter['start_date']."' AND o.add_time < '" . ($filter['end_date'] + 86399) . "'";
	
    if (isset($_REQUEST['status']))
    {
		if(isset($_REQUEST['cd']))
		{
			$sqladd .= ' AND (o.order_status = 1 OR o.order_status = 5) AND o.is_separate = ' . (int)$_REQUEST['status'];
			$filter['status'] = (int)$_REQUEST['status'];
			$filter['cd'] = (int)$_REQUEST['cd'];
		}else{
			$sqladd .= ' AND o.is_separate = ' . (int)$_REQUEST['status'];
			$filter['status'] = (int)$_REQUEST['status'];
		}	
    }
	if (isset($_REQUEST['zn'])) 
	{
		$sqladd .= ' AND (o.order_status = 1 OR o.order_status = 5)';
        $filter['zn'] = (int)$_REQUEST['zn'];
	}
    if (isset($_REQUEST['order_sn']))
    {
        $sqladd .= ' AND o.order_sn LIKE \'%' . trim($_REQUEST['order_sn']) . '%\'';
        $filter['order_sn'] = $_REQUEST['order_sn'];
    }
    if (isset($_GET['auid']))
    {
        $sqladd .= ' AND a.user_id=' . $_GET['auid'];
    }

    if(!empty($affiliate['on']))
    {
        if(empty($separate_by))
        {
            //推荐注册分成
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_info') . " o".
                    " LEFT JOIN".$GLOBALS['ecs']->table('users')." u ON o.user_id = u.user_id".
                    " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                    " WHERE o.user_id > 0 AND (u.parent_id > 0 AND o.is_separate = 0 OR o.is_separate > 0) $sqladd";
        }
        else
        {
            //推荐订单分成
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_info') . " o".
                    " LEFT JOIN".$GLOBALS['ecs']->table('users')." u ON o.user_id = u.user_id".
                    " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                    " WHERE o.user_id > 0 AND (o.parent_id > 0 AND o.is_separate = 0 OR o.is_separate > 0) $sqladd";
        }
    }
    else
    {
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_info') . " o".
                " LEFT JOIN".$GLOBALS['ecs']->table('users')." u ON o.user_id = u.user_id".
                " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                " WHERE o.user_id > 0 AND o.is_separate > 0 $sqladd";
    }


    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
    $logdb = array();
    /* 分页大小 */
    $filter = page_and_size($filter);

    if(!empty($affiliate['on']))
    {
        if(empty($separate_by))
        {
            //推荐注册分成
            $sql = "SELECT o.*, a.log_id, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $GLOBALS['ecs']->table('order_info') . " o".
                    " LEFT JOIN".$GLOBALS['ecs']->table('users')." u ON o.user_id = u.user_id".
                    " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                    " WHERE o.user_id > 0 AND (u.parent_id > 0 AND o.is_separate = 0 OR o.is_separate > 0) $sqladd".
                    " ORDER BY order_id DESC" .
                    " LIMIT " . $filter['start'] . ",$filter[page_size]";

            /*
                SQL解释：

                列出同时满足以下条件的订单分成情况：
                1、有效订单o.user_id > 0
                2、满足以下情况之一：
                    a.有用户注册上线的未分成订单 u.parent_id > 0 AND o.is_separate = 0
                    b.已分成订单 o.is_separate > 0

            */
        }
        else
        {
            //推荐订单分成
            $sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $GLOBALS['ecs']->table('order_info') . " o".
                    " LEFT JOIN".$GLOBALS['ecs']->table('users')." u ON o.user_id = u.user_id".
                    " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                    " WHERE o.user_id > 0 AND (o.parent_id > 0 AND o.is_separate = 0 OR o.is_separate > 0) $sqladd" .
                    " ORDER BY order_id DESC" .
                    " LIMIT " . $filter['start'] . ",$filter[page_size]";

            /*
                SQL解释：

                列出同时满足以下条件的订单分成情况：
                1、有效订单o.user_id > 0
                2、满足以下情况之一：
                    a.有订单推荐上线的未分成订单 o.parent_id > 0 AND o.is_separate = 0
                    b.已分成订单 o.is_separate > 0

            */
        }
    }
    else
    {
        //关闭
        $sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $GLOBALS['ecs']->table('order_info') . " o".
                " LEFT JOIN".$GLOBALS['ecs']->table('users')." u ON o.user_id = u.user_id".
                " LEFT JOIN " . $GLOBALS['ecs']->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                " WHERE o.user_id > 0 AND o.is_separate > 0 $sqladd" .
                " ORDER BY order_id DESC" .
                " LIMIT " . $filter['start'] . ",$filter[page_size]";
    }


    $query = $GLOBALS['db']->query($sql);
    while ($rt = $GLOBALS['db']->fetch_array($query))
    {
        if(empty($separate_by) && $rt['up'] > 0)
        {
            //按推荐注册分成
            $rt['separate_able'] = 1;
        }
        elseif(!empty($separate_by) && $rt['parent_id'] > 0)
        {
            //按推荐订单分成
            $rt['separate_able'] = 1;
        }
        if(!empty($rt['suid']))
        {
            //在affiliate_log有记录
            $rt['info'] = sprintf($GLOBALS['_LANG']['separate_info2'], $rt['suid'], $rt['auser'], $rt['money'], $rt['point']);
            if($rt['separate_type'] == -1 || $rt['separate_type'] == -2)
            {
                //已被撤销
                $rt['is_separate'] = 3;
                $rt['info'] = "<s>" . $rt['info'] . "</s>";
            }
        }
        $logdb[] = $rt;
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
//获取订单给上级分配的现金额
function get_order_give_cash($oid){
	$sql = "SELECT goods_id,goods_number from ". $GLOBALS['ecs']->table('order_goods') ." where order_id = ".$oid;
	$goods_arr = $GLOBALS['db']->getAll($sql);
	$all_cash = 0;
	foreach($goods_arr as $k=>$v){
		$sql = "SELECT give_cash from ". $GLOBALS['ecs']->table('goods') ." where goods_id = ".$v['goods_id'];
		$give_cash = $GLOBALS['db']->getOne($sql);
		$all_cash+=floatval($give_cash) * floatval($v['goods_number']);
	}
	return $all_cash;
}
//获得上级用户部分资料
function get_up_userinfo($user_id){	
	$sql = "SELECT u.user_id as up_id,u.user_name as up_username,u.user_rank as up_rank_id FROM " . $GLOBALS['ecs']->table('users') . " o" .
                        " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.parent_id = u.user_id".
                        " WHERE o.user_id = '$user_id'";
	$row = $GLOBALS['db']->	getRow($sql);	
	return $row;				
}

//获取指定区域ID的经销商信息
function get_jxs_info($district){
	$sql = "SELECT user_id,user_name,proxy_lev,proxy_area_id from ". $GLOBALS['ecs']->table('users') ." where proxy_area_id = ".$district;
	return $GLOBALS['db']->getRow($sql);
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