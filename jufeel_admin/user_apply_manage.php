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

admin_priv('apply_manage');


/*------------------------------------------------------ */
//-- 列表页
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
	
	/* 时间参数 */
	$beginday = mktime(0,0,0,date('m'),date('d')-7,date('Y'))-28800;		//减去8小时，和标准时间对应
	$endday = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-28800;
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
	
    $logdb = get_apply_list();
	//var_dump($logdb);
    $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', '代理申请');
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
    $smarty->display('user_apply_list.htm');
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
    编辑用户的申请信息
*/
elseif ($_REQUEST['act'] == 'edit')
{     
	if($_POST){
		$admin_note = $_POST['admin_note'];
		$pay_status = $_POST['pay_status'];
		if($pay_status == '1'){
			$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('users') .
					" WHERE user_id = '" . $_POST['user_id'] . "'";
			$res = $GLOBALS['db']->getRow($sql);
			if(floatval($res['user_money']) < floatval($_POST['daili_amount'])){
				$link[] = array('text' => $_LANG['go_back'], 'href'=>'user_apply_manage.php?act=list');
				sys_msg('用户余额不足，请充值后再来修改', 0, $link);
			}else{
				$change_desc = "入股金(".$_POST['id'].")支付";
				log_account_change($_POST['user_id'],$_POST['daili_amount']*-1,0,0,0,$change_desc,20,2);
				$sql = "UPDATE " . $GLOBALS['ecs']->table('apply_list') .
						   " SET admin_note = '" . $admin_note . "' ,total_fee = '500000' , pay_status = '" . $pay_status . "'".
						   " WHERE id = '" . $_POST['id'] . "'";
				$GLOBALS['db']->query($sql);
				/* 记录管理员操作 */
				admin_log(addslashes($_POST['id']), 'edit', 'apply_pay_status');
				
				/* 提示信息 */
				$link[] = array('text' => $_LANG['go_back'], 'href'=>'user_apply_manage.php?act=list');
				sys_msg('修改成功', 0, $link);
			}
		}else{
			/* 提示信息 */
			$link[] = array('text' => $_LANG['go_back'], 'href'=>'user_apply_manage.php?act=list');
			sys_msg('无法修改', 0, $link);
		}		
	}else{
		$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('apply_list') .
					" WHERE id = '" . $_GET['id'] . "'";
		$result = 	$GLOBALS['db']->getRow($sql);
		$result['username'] = getChenghu($result['user_id']);
		$result['time'] = local_date($GLOBALS['_CFG']['date_format'], $result['send_time']);		
		$smarty->assign('apply_info', $result);
		$smarty->assign('ur_here', '查看合伙人申请');
		$smarty->display('user_apply_info.htm');
	}				
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
	$sql = "SELECT id,user_id,daili_lev,daili_amount,pay_status,is_apply,ktimes FROM " . $GLOBALS['ecs']->table('apply_list') .
				" WHERE is_del = 0 AND id = '" . $_GET['id'] . "'";
	$result = 	$GLOBALS['db']->getRow($sql);
	if($result)	{
		if($result['is_apply']==1){
			$cent="该订单已经审核，不要重复审核！";
		}else{
			if($result['pay_status'] !=1){
				$cent="该订单未付款，不能审核！";
			}else{				
				//开始审核 
				$sql = "UPDATE " . $GLOBALS['ecs']->table('apply_list') .
						   " SET is_apply = 1 , shenhe_time = '" . gmtime() ."'".
						   " WHERE id = '" . $_GET['id'] . "'";
				$GLOBALS['db']->query($sql);
				//修改用户等级和入股金以及考核时间和任务量
				//$assess_time = get_shop_config_value('assess_time');
				$assess_value = get_shop_config_value('assess_value');
				$rank_id=138;
				$user_premium =  $result['daili_amount'];
				$premium_time = gmtime();
				$month = (int)date("m",gmtime()+28800);
				$year = (int)date("Y",gmtime()+28800);
				$next_assess_time = mktime(0,0,0,$month+1,1,$year);
				
				$year_assess_time = explode('-',get_shop_config_value('year_assess_time'));
				$year_assess_value = ((intval($year_assess_time[0])-$year)*12+intval($year_assess_time[1])-$month)*floatval($assess_value);
				
				//$year_assess_value = (13 - $month)*floatval($assess_value);
				
				if($result['ktimes'] =='1'){
					//提升用户等级
					$sql = "UPDATE " . $GLOBALS['ecs']->table('users') .
							   " SET user_rank = '" . $rank_id ."',done_stock = 0 " .					   
							   " WHERE user_id = '" . $result['user_id'] . "'";
					$GLOBALS['db']->query($sql);					
				}else{
					$sql = "UPDATE " . $GLOBALS['ecs']->table('users') .
							   " SET done_stock = 0 " .					   
							   " WHERE user_id = '" . $result['user_id'] . "'";
					$GLOBALS['db']->query($sql);
				}
				
				//计入入股金考核表中
				$sql = 'INSERT INTO '. $ecs->table('user_stock') . " (`user_id`, `user_premium`, `premium_time`, `next_assess_time`, `next_assess_value`, `year_assess_value`, `ktimes`) VALUES ('$result[user_id]', '$user_premium', '$premium_time', '$next_assess_time', '$assess_value', '$year_assess_value', '$result[ktimes]')";
       			$db->query($sql);

				$s_id = mysql_insert_id();
				//进入入股金流水				
				$sql = 'INSERT INTO '. $ecs->table('ad_log') . " (`s_id`, `user_id`, `user_premium`, `premium_time`) VALUES ('$s_id', '$result[user_id]', '$user_premium', '$premium_time')";
       			$db->query($sql);
				
				//更新未分配入股金
				change_shop_value("no_compute_premium",$user_premium,1);
				
				/* 记录管理员操作 */
    			admin_log(addslashes($result['user_id']), 'shenhe', 'daili');
				
				$cent="审核成功";
			}
		}
	}else{
		$cent="未查到该订单";
	}

    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'user_apply_manage.php?act=list');
    sys_msg($cent, 0, $link);
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
	
    $logdb = get_apply_list();
    $smarty->assign('logdb',        $logdb['logdb']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

    $sort_flag  = sort_flag($logdb['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('user_apply_list.htm'), '', array('filter' => $logdb['filter'], 'page_count' => $logdb['page_count']));
}

/*
    生成代理要约
*/
elseif ($_REQUEST['act'] == 'make')
{  
	$aid = $_GET['id'];
	$sql = "SELECT heyue_url FROM " . $GLOBALS['ecs']->table('apply_list') .
				" WHERE is_del = 0 AND id = '" . $aid . "'";
	$heyue_url = 	$GLOBALS['db']->getOne($sql);
	if(empty($heyue_url)){	
		if($aid){
			//画个黑色背景图
			$im = imagecreatetruecolor(640, 1800);
			$bgcolor = imagecolorallocate($im, 218, 155, 3);
			imagefill($im, 0, 0, $bgcolor);
			//画个白色底
			$im2 = imagecreatetruecolor(630, 1790);
			$bgcolor = imagecolorallocate($im2, 254, 255, 255);
			imagefill($im2, 0, 0, $bgcolor);
			imagecopymerge($im, $im2, 5, 5, 0, 0, 630, 1790, 100);
			
			include_once(ROOT_PATH . 'includes/Image.class.php');
			$image = new Image();
			
//写标头
			$str = '合伙人协议';
			$font=ROOT_PATH . 'mobile/data/sale/ttf/msyh.ttf';						
			$image->water4($str, $font, 26, 0,0,0, 225, 70,$im);
			
			$dl_info = get_daili_info($aid);
			
			//var_dump($dl_info);
			//写第一段
			//$tname = model('ClipsBase')->get_daili_info($this->user_id,1);		
			$str = '    本协议是由   开封多千盈电子商务有限公司   （以下简称甲方）依据中华人民共和国相关法律及法规与   '.$dl_info['tname'].'   （以下简称乙方）之间本着平等互利、诚实信任、共同发展的原则，经友好协商，就乙方成为甲方所创办的   千盈资本花园   合伙人之一事宜，达成如下约定：';
			$font =ROOT_PATH .  'mobile/data/sale/ttf/fimfang.ttf';
			$box = autowrap(14, 0, $font, $str, 590);					
			$image->water4($box, $font, 14, 0,0,0, 185, 120,$im);
			//写第二段
			$str='1．甲方的权利和义务';
			$font2 =ROOT_PATH . 'mobile/data/sale/ttf/fzdh.ttf';
			$image->water5($str, $font2, 14, 0,0,0, 62, 245,$im);
			//写第二段的文字
			$str = '    1.1甲方为乙方业务拓展提供相应的工具和指导及相应售出产品的售后支持。                                                           1.2甲方应及时修复平台的BUG以方便乙方开展业务。                 1.3甲方应为平台提供升级服务。                                   1.4甲方有权利在乙方未达成  千盈资本花园  任务指标的情况下强制对其作出降级处理。';
			$box = autowrap(14, 0, $font, $str, 590);
			$image->water4($box, $font, 14, 0,0,0, 185, 270,$im);

			//写第二1段
			$str='2．乙方的权利和义务';
			$font2 =ROOT_PATH . 'mobile/data/sale/ttf/fzdh.ttf';
			$image->water5($str, $font2, 14, 0,0,0, 62, 420,$im);
			//写第二1段的文字
			$str = '    2．1．申请成为甲方合伙人，并缴纳股金；                         2．2．合伙人义务宣传平台产品和拓展平台用户群体，但不得以夸大产品效果和欺诈性质诱导客户；                                      2．3．合伙人有义务及权利提出合理性平台升级建议；               2．4．合伙人权利可向甲方申请查看各项经营报表；                 2．5．合伙人有义务完成平台所有赋予的任务指标。';
			$box = autowrap(14, 0, $font, $str, 590);
			$image->water4($box, $font, 14, 0,0,0, 185, 445,$im);

			//写第三段
			$str='3．合伙人退出';
			$image->water5($str, $font2, 14, 0,0,0, 62, 600,$im);
			//写第三段的文字
			$str = '    3．1．自愿退出：合伙人自愿提出的退伙申请，平台将以合伙人起始入股金加银行结算定期利率15个工作日内退换合伙人；                 3．2．强制退出：合伙人出现以下情况，平台将以合伙人起始入股金加银行结算定期利率15个工作日内退换合伙人；                       3．2．1．未完成平台给予合伙人任务指标；                        3．2．2．因故意或重大过失给平台造成损失；                      3．2．3．被依法宣告为无民事行为能力。';
			$box = autowrap(14, 0, $font, $str, 590);
			$image->water4($box, $font, 14, 0,0,0, 185, 625,$im);
			
			//写第三段
			$str='4．分红';
			$image->water5($str, $font2, 14, 0,0,0, 62, 805,$im);
			//写第三段的文字
			$str = '    根据平台运营情况，平台承诺一年内至少分红一次，每年分红金额不低于年度纯利润10%。';
			$box = autowrap(14, 0, $font, $str, 590);
			$image->water4($box, $font, 14, 0,0,0, 185, 830,$im);
			
			//写第四段
			$str='5．保密';
			$image->water5($str, $font2, 14, 0,0,0, 62, 875,$im);
			//写第四段的文字
			$str = '    5．1．甲、乙双方对彼此之间相互提供的信息、资料以及本协议的具体内容负有保密义务；                                              5．2．甲方保留产品名称变更的权利；                             5．3．乙方明确知悉甲方为第三方开发公司，与腾讯公司无关。';
			$box = autowrap(14, 0, $font, $str, 590);
			$image->water4($box, $font, 14, 0,0,0, 185, 900,$im);
			
			//写第五段
			$str='6．免责条件';
			$image->water5($str, $font2, 14, 0,0,0, 62, 995,$im);
			//写第五段的文字
			$str = '    6．1．因国家政策法规调整、自然灾害等不可抗力或意外事件而影响乙方正常的服务和技术支持时，双方互不承担责任；                   6．2．因腾讯公司调整产品导致服务不可继续执行，属不可抗力，双方互不承担责任。';
			$box = autowrap(14, 0, $font, $str, 590);
			$image->water4($box, $font, 14, 0,0,0, 185, 1020,$im);
			
			//写第六段
			$str='7．附则';
			$image->water5($str, $font2, 14, 0,0,0, 62, 1115,$im);
			//写第六段的文字
			$str = '    7．1．此协议一经在线确定同意即具有法律效力，双方必须严格遵守，任何一方不得无故终止。如一方违约，对方均有权追究其法律责任，要求赔偿损失。                                                    7．2．合同的延续性：自本协议签订之日，一年为一周期，于当期协议到期前并甲乙双方没有提出异议，本协议自动存续下一周期。        7．3．乙方地址，联系方式，如有变动，乙方应立即告知甲方，否则造成的损失甲方不承担任何责任。                                   7．4．以上条款如有未尽事宜，在协议期间双方应平等协商解决。';
			$box = autowrap(14, 0, $font, $str, 590);
			$image->water4($box, $font, 14, 0,0,0, 185, 1140,$im);
			
			//写第七段的文字
			$str = '    本协议在甲方千盈资本花园、乙方个人中心一图片形式保存。经用户在线确认同意后生效！！';
			$box = autowrap(14, 0, $font, $str, 590);
			$image->water4($box, $font, 14, 0,0,0, 185, 1355,$im);
			
			//写签约日期
			$str = '签订日期：'.date('Y-m-d',$dl_info['shenhe_time']);
			$image->water5($str, $font, 14, 0,0,0, 57, 1415,$im);	
			
			//写解释权
			$str = '协议条款的解释权归属   开封多千盈电子商务有限公司';
			$image->water5($str, $font, 14, 0,0,0, 165, 1480,$im);			
			//盖章
			$im2 = ROOT_PATH . 'mobile/data/qky/qky_gz.png';
			$source = ImageCreateFrompng($im2);
			$degrees = rand(0,360);
			$water_im = imagerotate($source, $degrees, -1);
			//imagealphablending($im, true);
			$x=rand(200,300);
			$y=rand(1240,1390);
			imagecopy($im, $water_im, $x, $y, 0, 0, 250, 250);
			
			//再写点字
			$str = '';
			$image->water5($str, $font, 14, 0,0,0, 23, 1520,$im);
			
			//身份证
			//var_dump(explode('/',$dl_info['sf_code_1']));
			$sf1_name = explode('.',basename($dl_info['sf_code_1']));
			$sf1_name2 = explode('.',basename($dl_info['sf_code_2']));
			$sf1 = ROOT_PATH . 'mobile/data/attached/sf_code/'.$sf1_name[0].".jpg";
			$sf2 = ROOT_PATH . 'mobile/data/attached/sf_code/'.$sf1_name2[0].".jpg";
			$source = ImageCreateFromjpeg($sf1);
			imagecopy($im, $source, 20, 1550, 0, 0, 290, 185);
			$source = ImageCreateFromjpeg($sf2);
			imagecopy($im, $source, 330, 1550, 0, 0, 290, 185);
			
			$hy_name = time().'_'.$aid.'_'.$dl_info['user_id'].'.png';
			$heyue = ROOT_PATH . 'mobile/data/heyue/'.$hy_name;
			//var_dump($box);
			//header('Content-type: image/png');
			imagepng($im,$heyue);
			imagedestroy($im);
			
			$heyue_url = './data/heyue/'.$hy_name;
			if(file_exists($heyue)){
				$sql = "UPDATE " . $GLOBALS['ecs']->table('apply_list') .
						   " SET heyue_url = '" . $heyue_url ."'".
						   " WHERE id = '" . $aid . "'";
				$GLOBALS['db']->query($sql);
				/* 微信通 分成提醒微信上级用户 start  by kylin */					
				$file = '../mobile/include/apps/default/controller/WechatController.class.php';
				if(file_exists($file) && $dl_info['user_id'] > 0){					
					$remind_title = '您的合伙人申请已经通过审核';											
					$content="恭喜您，".$dl_info['tname']."。您的合伙人申请已经通过了审核，您可以到会员中心查看您的合伙人协议，同时您开始享受合伙人政策！";		
					
					if(!empty($remind_title) && !empty($dl_info['openid'])){
						$order_url = $GLOBALS['ecs']->url() . 'mobile/?c=user&a=apply_index';
						$order_url = urlencode(base64_encode($order_url));
						$url = $GLOBALS['ecs']->url() . 'mobile/?c=api&openid='.$dl_info['openid'].'&title='.urlencode($remind_title).'&msg='.urlencode($content).'&url='.$order_url;
						curlGet($url);
					}
				}
				
				/* 微信通 分成提醒微信上级用户 end  kylin */
				//$money = $dl_info['total_fee']/100;
				//$info = $_LANG['dl_lev'][$dl_info[daili_lev]].'代理申请('.$dl_info['id'].')审核通过，发放余额'.$money.'元';
				//log_account_change($dl_info['user_id'],$money , 0, 0, 0, $info);		//给用户的余额进行充值
				$cent="生成合约成功";
			}else{
				$cent="生成合约失败";
			}
			
		}else{
			$cent="参数错误";
		}
	}else{
		$cent="已生成，不要重复生成！";
	}
	/* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'user_apply_manage.php?act=list');
    sys_msg($cent, 0, $link);
}

function get_apply_list()
{

    $sqladd = '';
	
	/* 时间参数 */	
	$beginday = mktime(0,0,0,date('m'),date('d')-7,date('Y'))-28800;		//减去8小时，和标准时间对应
	$endday = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-28800;
	
    $filter['start_date'] = empty($_REQUEST['start_date']) ? $beginday : local_strtotime($_REQUEST['start_date']);
    $filter['end_date'] = empty($_REQUEST['end_date']) ? $endday : local_strtotime($_REQUEST['end_date']);
	
	$sqladd = " AND a.time >= '".$filter['start_date']."' AND a.time < '" . ($filter['end_date'] + 28799) . "'";
	
    if (isset($_REQUEST['pay_status']))
    {		
		$sqladd .= ' AND a.pay_status = ' . (int)$_REQUEST['pay_status'];
		$filter['pay_status'] = (int)$_REQUEST['pay_status'];
    }
	if (isset($_REQUEST['is_apply'])) 
	{
		$sqladd .= ' AND a.is_apply = ' . (int)$_REQUEST['is_apply'];
        $filter['is_apply'] = (int)$_REQUEST['is_apply'];
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
    
    if (isset($_REQUEST['user_id']))
    {
        $sqladd .= ' AND a.user_id=' . $_REQUEST['user_id'];
    }
	
	

    
	$sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('apply_list') . " a".
			" WHERE a.is_del = 0 $sqladd";
	


    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
    $logdb = array();
    /* 分页大小 */
    $filter = page_and_size($filter);

    
	
	$sql = "SELECT a.* FROM " . $GLOBALS['ecs']->table('apply_list') . " a".
			" WHERE a.is_del = 0 $sqladd".
			" ORDER BY id DESC" .
			" LIMIT " . $filter['start'] . ",$filter[page_size]";

    


    $logdb = $GLOBALS['db']->getAll($sql);

    $count = count($logdb);
    for ($i=0; $i<$count; $i++)
    {
        $logdb[$i]['time'] = local_date($GLOBALS['_CFG']['date_format'], $logdb[$i]['time']);		
		$logdb[$i]['nickname'] = getChenghu($logdb[$i]['user_id']);
		$logdb[$i]['user_name'] = get_username($logdb[$i]['user_id']);
		$logdb[$i]['total_fee2'] = $logdb[$i]['total_fee']/100;
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
function get_username($u_id){	
	$sql2 = "SELECT user_name from ". $GLOBALS['ecs']->table('users') ." where user_id = ".$u_id;
	$username = $GLOBALS['db']->getOne($sql2);
	return 	$username;	
}


//获取用户申请代理订单的信息
function get_daili_info($aid){
	$sql = "SELECT * from ". $GLOBALS['ecs']->table('apply_list') ." where is_del = 0 AND id = ".$aid;
	$arr = $GLOBALS['db']->getRow($sql);
	if($type ==1){
		return $arr['tname'];
	}elseif($type==2){
		return date('Y-m-d',$arr['time']);
	}else{		
		return $arr;
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