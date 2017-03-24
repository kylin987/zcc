<?php

/**
 * ECSHOP 会员管理程序
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: users.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*------------------------------------------------------ */
//-- 用户帐号列表
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'list')
{
    /* 检查权限 */
    admin_priv('users_manage');
    $sql = "SELECT rank_id, rank_name, min_points FROM ".$ecs->table('user_rank')." ORDER BY min_points ASC ";
    $rs = $db->query($sql);
	
    $ranks = array();
    while ($row = $db->FetchRow($rs))
    {
        $ranks[$row['rank_id']] = $row['rank_name'];
    }
	
    $smarty->assign('user_ranks',   $ranks);
    $smarty->assign('ur_here',      $_LANG['03_users_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['04_users_add'], 'href'=>'users.php?act=add'));
	//log_account_change(17,-5 , 0, 0, 0, 0, $info);
    $user_list = user_list();	
		
	//var_dump($user_list);
    $smarty->assign('user_list',    $user_list['user_list']);
    $smarty->assign('filter',       $user_list['filter']);
    $smarty->assign('record_count', $user_list['record_count']);
    $smarty->assign('page_count',   $user_list['page_count']);
    $smarty->assign('full_page',    1);
    $smarty->assign('sort_user_id', '<img src="images/sort_desc.gif">');

    assign_query_info();
    $smarty->display('users_list.htm');
}
/*------------------------------------------------------ */
//-- ajax返回用户列表
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'we_list'){
	$keyword=$_POST['keyword'];
	
	/* 检查权限 */
    admin_priv('users_manage');
    $sql = "SELECT rank_id, rank_name, min_points FROM ".$ecs->table('user_rank')." ORDER BY min_points ASC ";
    $rs = $db->query($sql);

    $ranks = array();
    while ($row = $db->FetchRow($rs))
    {
        $ranks[$row['rank_id']] = $row['rank_name'];
    }

    $smarty->assign('user_ranks',   $ranks);
    $smarty->assign('ur_here',      $_LANG['03_users_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['04_users_add'], 'href'=>'users.php?act=add'));

    //$user_list = user_list();
	$sql = "SELECT u.user_id, u.user_name, u.email, u.is_validated, u.user_money, u.frozen_money, u.gold_coin, u.rank_points, u.pay_points, u.reg_time,u.apply_time,u.user_rank,w.nickname ".//UUECS
                " FROM " . $GLOBALS['ecs']->table('users') . " as u left join " . $GLOBALS['ecs']->table('wechat_user') . " as w on u.user_id = w.ect_uid where w.nickname like '%" . mysql_like_quote($keyword) ."%'";	
       
    $user_list = $GLOBALS['db']->getAll($sql);
	foreach ($user_list as $k=>$v){		
		$user_list[$k]['rank_name']=get_user_rank2($v['user_rank'],$v['rank_points']);	
	}
	
    $smarty->assign('user_list',    $user_list);    
    $smarty->assign('full_page',    1);
    $smarty->assign('sort_user_id', '<img src="images/sort_desc.gif">');

    assign_query_info();
    $smarty->display('users_list.htm');

}
/*------------------------------------------------------ */
//-- ajax返回用户列表
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $user_list = user_list();

    $smarty->assign('user_list',    $user_list['user_list']);
    $smarty->assign('filter',       $user_list['filter']);
    $smarty->assign('record_count', $user_list['record_count']);
    $smarty->assign('page_count',   $user_list['page_count']);

    $sort_flag  = sort_flag($user_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('users_list.htm'), '', array('filter' => $user_list['filter'], 'page_count' => $user_list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加会员帐号
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{
    /* 检查权限 */
    admin_priv('users_manage');

    $user = array(  'rank_points'   => $_CFG['register_points'],
                    'pay_points'    => $_CFG['register_points'],
                    'sex'           => 0,
                    'credit_line'   => 0
                    );
    /* 取出注册扩展字段 */
    $sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 AND id != 6 ORDER BY dis_order, id';
    $extend_info_list = $db->getAll($sql);
    $smarty->assign('extend_info_list', $extend_info_list);

    $smarty->assign('ur_here',          $_LANG['04_users_add']);
    $smarty->assign('action_link',      array('text' => $_LANG['03_users_list'], 'href'=>'users.php?act=list'));
    $smarty->assign('form_action',      'insert');
    $smarty->assign('user',             $user);
    $smarty->assign('special_ranks',    get_rank_list(true));

    assign_query_info();
    $smarty->display('user_info.htm');
}

/*------------------------------------------------------ */
//-- 添加会员帐号
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert')
{
    /* 检查权限 */
    admin_priv('users_manage');
    $username = empty($_POST['username']) ? '' : trim($_POST['username']);
    $password = empty($_POST['password']) ? '' : trim($_POST['password']);
    $email = empty($_POST['email']) ? '' : trim($_POST['email']);
	$tname = empty($_POST['tname']) ? '' : trim($_POST['tname']);
    $sex = empty($_POST['sex']) ? 0 : intval($_POST['sex']);
    $sex = in_array($sex, array(0, 1, 2)) ? $sex : 0;
    $birthday = $_POST['birthdayYear'] . '-' .  $_POST['birthdayMonth'] . '-' . $_POST['birthdayDay'];
    $rank = empty($_POST['user_rank']) ? 0 : intval($_POST['user_rank']);
    $credit_line = empty($_POST['credit_line']) ? 0 : floatval($_POST['credit_line']);

    $users =& init_users();

    if (!$users->add_user($username, $password, $email,$tname))
    {
        /* 插入会员数据失败 */
        if ($users->error == ERR_INVALID_USERNAME)
        {
            $msg = $_LANG['username_invalid'];
        }
        elseif ($users->error == ERR_USERNAME_NOT_ALLOW)
        {
            $msg = $_LANG['username_not_allow'];
        }
        elseif ($users->error == ERR_USERNAME_EXISTS)
        {
            $msg = $_LANG['username_exists'];
        }
        elseif ($users->error == ERR_INVALID_EMAIL)
        {
            $msg = $_LANG['email_invalid'];
        }
        elseif ($users->error == ERR_EMAIL_NOT_ALLOW)
        {
            $msg = $_LANG['email_not_allow'];
        }
        elseif ($users->error == ERR_EMAIL_EXISTS)
        {
            $msg = $_LANG['email_exists'];
        }
        else
        {
            //die('Error:'.$users->error_msg());
        }
        sys_msg($msg, 1);
    }

    /* 注册送积分 */
    if (!empty($GLOBALS['_CFG']['register_points']))
    {
        log_account_change($_SESSION['user_id'], 0, 0, $GLOBALS['_CFG']['register_points'], $GLOBALS['_CFG']['register_points'], $_LANG['register_points']);
    }

    /*把新注册用户的扩展信息插入数据库*/
    $sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有扩展字段的id
    $fields_arr = $db->getAll($sql);

    $extend_field_str = '';    //生成扩展字段的内容字符串
    $user_id_arr = $users->get_profile_by_name($username);
    foreach ($fields_arr AS $val)
    {
        $extend_field_index = 'extend_field' . $val['id'];
        if(!empty($_POST[$extend_field_index]))
        {
            $temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];
            $extend_field_str .= " ('" . $user_id_arr['user_id'] . "', '" . $val['id'] . "', '" . $temp_field_content . "'),";
        }
    }
    $extend_field_str = substr($extend_field_str, 0, -1);

    if ($extend_field_str)      //插入注册扩展数据
    {
        $sql = 'INSERT INTO '. $ecs->table('reg_extend_info') . ' (`user_id`, `reg_field_id`, `content`) VALUES' . $extend_field_str;
        $db->query($sql);
    }

    /* 更新会员的其它信息 */
    $other =  array();
    $other['credit_line'] = $credit_line;
    $other['user_rank']  = $rank;
    $other['sex']        = $sex;
    $other['birthday']   = $birthday;
    $other['reg_time'] = local_strtotime(local_date('Y-m-d H:i:s'));

    $other['msn'] = isset($_POST['extend_field1']) ? htmlspecialchars(trim($_POST['extend_field1'])) : '';
    $other['qq'] = isset($_POST['extend_field2']) ? htmlspecialchars(trim($_POST['extend_field2'])) : '';
    $other['office_phone'] = isset($_POST['extend_field3']) ? htmlspecialchars(trim($_POST['extend_field3'])) : '';
    $other['home_phone'] = isset($_POST['extend_field4']) ? htmlspecialchars(trim($_POST['extend_field4'])) : '';
    $other['mobile_phone'] = isset($_POST['extend_field5']) ? htmlspecialchars(trim($_POST['extend_field5'])) : '';

    $db->autoExecute($ecs->table('users'), $other, 'UPDATE', "user_name = '$username'");

    /* 记录管理员操作 */
    admin_log($_POST['username'], 'add', 'users');

    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
    sys_msg(sprintf($_LANG['add_success'], htmlspecialchars(stripslashes($_POST['username']))), 0, $link);

}

/*------------------------------------------------------ */
//-- 编辑用户帐号
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit')
{
    /* 检查权限 */
    admin_priv('users_manage');

    $sql = "SELECT u.user_name, u.sex,u.proxy_lev,u.proxy_area_id, u.birthday, u.pay_points, u.rank_points, u.user_rank , u.user_money, u.frozen_money, u.gold_coin, u.credit_line, u.parent_id, u2.user_name as parent_username,w.nickname, u.qq, u.msn,u.tname, u.office_phone, u.home_phone, u.mobile_phone,u.raply_bank,u.raply_kaihu,u.raply_username,u.raply_number,u.raply_beizhu".
        " FROM " .$ecs->table('users'). " u LEFT JOIN " . $ecs->table('users') . " u2 ON u.parent_id = u2.user_id LEFT JOIN " . $ecs->table('wechat_user') . " w ON u.parent_id = w.ect_uid WHERE u.user_id='$_GET[id]'";

    $row = $db->GetRow($sql);
	
    $row['user_name'] = addslashes($row['user_name']);
    $users  =& init_users();
    $user   = $users->get_user_info($row['user_name']);

    $sql = "SELECT u.user_id, u.sex,u.proxy_lev,u.proxy_area_id, u.birthday, u.pay_points, u.rank_points, u.user_rank , u.user_money, u.frozen_money, u.gold_coin, u.credit_line, u.parent_id, u2.user_name as parent_username,w.nickname, u.qq, u.msn,u.tname,
    u.office_phone, u.home_phone, u.mobile_phone,u.raply_bank,u.raply_kaihu,u.raply_username,u.raply_number,u.raply_beizhu".
        " FROM " .$ecs->table('users'). " u LEFT JOIN " . $ecs->table('users') . " u2 ON u.parent_id = u2.user_id LEFT JOIN " . $ecs->table('wechat_user') . " w ON u.parent_id = w.ect_uid WHERE u.user_id='$_GET[id]'";

    $row = $db->GetRow($sql);	
	
    if ($row)
    {
        $user['user_id']        = $row['user_id'];
        $user['sex']            = $row['sex'];
		$user['proxy_lev']      = $row['proxy_lev'];
		$user['proxy_area_id']  = $row['proxy_area_id'];
		$user['proxy_area_name']= get_area_name($row['proxy_area_id']);
		$user['proxy_area_upname']= get_area_name($row['proxy_area_id'],2);			
        $user['birthday']       = date($row['birthday']);
        $user['pay_points']     = $row['pay_points'];
        $user['rank_points']    = $row['rank_points'];
        $user['user_rank']      = $row['user_rank'];
        $user['user_money']     = $row['user_money'];
        $user['frozen_money']   = $row['frozen_money'];
		$user['gold_coin']   = $row['gold_coin'];
        $user['credit_line']    = $row['credit_line'];
        $user['formated_user_money'] = price_format($row['user_money']);
        $user['formated_frozen_money'] = price_format($row['frozen_money']);
		$user['formated_gold_coin'] = price_format($row['gold_coin']);
        $user['parent_id']      = $row['parent_id'];
        $user['parent_username']= $row['parent_username'];
        $user['qq']             = $row['qq'];
        $user['msn']            = $row['msn'];
		$user['tname']            = $row['tname'];
        $user['office_phone']   = $row['office_phone'];
        $user['home_phone']     = $row['home_phone'];
        $user['mobile_phone']   = $row['mobile_phone'];
		$user['nickname']   = $row['nickname'];
		$user['raply_bank']            = $row['raply_bank'];
        $user['raply_kaihu']   = $row['raply_kaihu'];
        $user['raply_username']     = $row['raply_username'];
        $user['raply_number']   = $row['raply_number'];
		$user['raply_beizhu']   = $row['raply_beizhu'];
		$user['stock'] = get_user_stock($row['user_id']);
		if(!empty($user['stock'])){
			foreach($user['stock'] as $k=>$v){
				$user['stock'][$k][stock_value] = get_last_stock($user['stock'][$k][stock]);
			}
		}
		
    }
    else
    {
          $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
          sys_msg($_LANG['username_invalid'], 0, $links);
//        $user['sex']            = 0;
//        $user['pay_points']     = 0;
//        $user['rank_points']    = 0;
//        $user['user_money']     = 0;
//        $user['frozen_money']   = 0;
//        $user['credit_line']    = 0;
//        $user['formated_user_money'] = price_format(0);
//        $user['formated_frozen_money'] = price_format(0);
     }

    /* 取出注册扩展字段 */
    $sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 AND id != 6 ORDER BY dis_order, id';
    $extend_info_list = $db->getAll($sql);

    $sql = 'SELECT reg_field_id, content ' .
           'FROM ' . $ecs->table('reg_extend_info') .
           " WHERE user_id = $user[user_id]";
    $extend_info_arr = $db->getAll($sql);

    $temp_arr = array();
    foreach ($extend_info_arr AS $val)
    {
        $temp_arr[$val['reg_field_id']] = $val['content'];
    }

    foreach ($extend_info_list AS $key => $val)
    {
        switch ($val['id'])
        {
            case 1:     $extend_info_list[$key]['content'] = $user['msn']; break;
            case 2:     $extend_info_list[$key]['content'] = $user['qq']; break;
            case 3:     $extend_info_list[$key]['content'] = $user['office_phone']; break;
            case 4:     $extend_info_list[$key]['content'] = $user['home_phone']; break;
            case 5:     $extend_info_list[$key]['content'] = $user['mobile_phone']; break;
            default:    $extend_info_list[$key]['content'] = empty($temp_arr[$val['id']]) ? '' : $temp_arr[$val['id']] ;
        }
    }

    $smarty->assign('extend_info_list', $extend_info_list);

    /* 当前会员推荐信息 */
    $affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
    $smarty->assign('affiliate', $affiliate);

    empty($affiliate) && $affiliate = array();

    if(empty($affiliate['config']['separate_by']))
    {
        //推荐注册分成
        $affdb = array();
        $num = count($affiliate['item']);
        $up_uid = "'$_GET[id]'";
        for ($i = 1 ; $i <=$num ;$i++)
        {
            $count = 0;
            if ($up_uid)
            {
                $sql = "SELECT user_id FROM " . $ecs->table('users') . " WHERE parent_id IN($up_uid)";
                $query = $db->query($sql);
                $up_uid = '';
                while ($rt = $db->fetch_array($query))
                {
                    $up_uid .= $up_uid ? ",'$rt[user_id]'" : "'$rt[user_id]'";
                    $count++;
                }
            }
            $affdb[$i]['num'] = $count;
        }
        if ($affdb[1]['num'] > 0)
        {
            $smarty->assign('affdb', $affdb);
        }
    }
	//判断是否为超级管理员
	$admin=0;
	if($_SESSION['action_list']=="all" || strpos($_SESSION['action_list'],'upid_manage') !== false){
		$admin=1;
	}
	//判断是否有提现银行管理权限
	$raply=0;
	if($_SESSION['action_list']=="all" || strpos($_SESSION['action_list'],'raply_manage') !== false){
		$raply=1;
	}
	
	//var_dump($user);
    assign_query_info();
    $smarty->assign('ur_here',          $_LANG['users_edit']);
    $smarty->assign('action_link',      array('text' => $_LANG['03_users_list'], 'href'=>'users.php?act=list&' . list_link_postfix()));
    $smarty->assign('user',             $user);
	$smarty->assign('admin',             $admin);
	$smarty->assign('raply',             $raply);
    $smarty->assign('form_action',      'update');
    $smarty->assign('special_ranks',    get_rank_list(true));
    $smarty->display('user_info.htm');
}

/*------------------------------------------------------ */
//-- 更新用户帐号
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'update')
{
	
    /* 检查权限 */
    admin_priv('users_manage');
    $username = empty($_POST['username']) ? '' : trim($_POST['username']);
    $password = empty($_POST['password']) ? '' : trim($_POST['password']);
    $email = empty($_POST['email']) ? '' : trim($_POST['email']);
	$tname = empty($_POST['tname']) ? '' : trim($_POST['tname']);
    $sex = empty($_POST['sex']) ? 0 : intval($_POST['sex']);
    $sex = in_array($sex, array(0, 1, 2)) ? $sex : 0;
	$proxy_lev = empty($_POST['proxy_lev']) ? 0 : intval($_POST['proxy_lev']);
	$proxy_area_id = empty($_POST['proxy_area_id']) ? 0 : intval($_POST['proxy_area_id']);	
    $birthday = $_POST['birthdayYear'] . '-' .  $_POST['birthdayMonth'] . '-' . $_POST['birthdayDay'];
    $rank = empty($_POST['user_rank']) ? 0 : intval($_POST['user_rank']);
    $credit_line = empty($_POST['credit_line']) ? 0 : floatval($_POST['credit_line']);
	$k_parent_id = empty($_POST['k_parent_id']) ? 0 : intval($_POST['k_parent_id']);
	
	if($k_parent_id){
		$sql = 'SELECT user_id ' .
          	 'FROM ' . $ecs->table('users') .
         	  " WHERE user_id = $k_parent_id";
    	$k_user_id = $db->getAll($sql);
		if($k_user_id){
			$par_id=$k_parent_id;	
		}else{
			$msg = "没有该用户，请查证后再填写";
			sys_msg($msg, 1);
		}
	}else{
		$par_id=0;
	}
	
	//检查代理区域
	if($proxy_area_id != 0){
		$sql = 'SELECT user_id ' .
          	 'FROM ' . $ecs->table('users') .
         	  " WHERE proxy_area_id = $proxy_area_id";
    	$rs = $db->getRow($sql);
		
		if(!empty($rs) && $rs['user_id'] != $_POST['id']){			
			$msg = "该区域已有代理商";
			sys_msg($msg, 1);
		}
	}
	
    $users  =& init_users();
	
    if (!$users->edit_user(array('username'=>$username, 'password'=>$password, 'email'=>$email, 'tname'=>$tname, 'gender'=>$sex, 'bday'=>$birthday ), 1))
	
    {
        if ($users->error == ERR_EMAIL_EXISTS)
        {
            $msg = $_LANG['email_exists'];
        }
        else
        {
            $msg = $_LANG['edit_user_failed'];
        }
        sys_msg($msg, 1);
    }
    if(!empty($password))
    {
			$sql="UPDATE ".$ecs->table('users'). "SET `ec_salt`='0' WHERE user_name= '".$username."'";
			$db->query($sql);
	}
    /* 更新用户扩展字段的数据 */
    $sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有扩展字段的id
    $fields_arr = $db->getAll($sql);
    $user_id_arr = $users->get_profile_by_name($username);
    $user_id = $user_id_arr['user_id'];

    foreach ($fields_arr AS $val)       //循环更新扩展用户信息
    {
        $extend_field_index = 'extend_field' . $val['id'];
        if(isset($_POST[$extend_field_index]))
        {
            $temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];

            $sql = 'SELECT * FROM ' . $ecs->table('reg_extend_info') . "  WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
            if ($db->getOne($sql))      //如果之前没有记录，则插入
            {
                $sql = 'UPDATE ' . $ecs->table('reg_extend_info') . " SET content = '$temp_field_content' WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
            }
            else
            {
                $sql = 'INSERT INTO '. $ecs->table('reg_extend_info') . " (`user_id`, `reg_field_id`, `content`) VALUES ('$user_id', '$val[id]', '$temp_field_content')";
            }
            $db->query($sql);
        }
    }


    /* 更新会员的其它信息 */
    $other =  array();
    $other['credit_line'] = $credit_line;
    $other['user_rank'] = $rank;

    $other['msn'] = isset($_POST['extend_field1']) ? htmlspecialchars(trim($_POST['extend_field1'])) : '';
    $other['qq'] = isset($_POST['extend_field2']) ? htmlspecialchars(trim($_POST['extend_field2'])) : '';
    $other['office_phone'] = isset($_POST['extend_field3']) ? htmlspecialchars(trim($_POST['extend_field3'])) : '';
    $other['home_phone'] = isset($_POST['extend_field4']) ? htmlspecialchars(trim($_POST['extend_field4'])) : '';
    $other['mobile_phone'] = isset($_POST['extend_field5']) ? htmlspecialchars(trim($_POST['extend_field5'])) : '';	
	
	$other['proxy_lev'] = $proxy_lev;
	$other['proxy_area_id'] = $proxy_area_id;
	
	$other['raply_bank'] = isset($_POST['raply_bank']) ? htmlspecialchars(trim($_POST['raply_bank'])) : '';
	$other['raply_kaihu'] = isset($_POST['raply_kaihu']) ? htmlspecialchars(trim($_POST['raply_kaihu'])) : '';
	$other['raply_username'] = isset($_POST['raply_username']) ? htmlspecialchars(trim($_POST['raply_username'])) : '';
	$other['raply_number'] = isset($_POST['raply_number']) ? htmlspecialchars(trim($_POST['raply_number'])) : '';
	$other['raply_beizhu'] = isset($_POST['raply_beizhu']) ? htmlspecialchars(trim($_POST['raply_beizhu'])) : '';
	
	
	$other['parent_id'] = $par_id;

    $db->autoExecute($ecs->table('users'), $other, 'UPDATE', "user_name = '$username'");

    /* 记录管理员操作 */
    admin_log($username, 'edit', 'users');

    /* 提示信息 */
    $links[0]['text']    = $_LANG['goto_list'];
    $links[0]['href']    = 'users.php?act=list&' . list_link_postfix();
    $links[1]['text']    = $_LANG['go_back'];
    $links[1]['href']    = 'javascript:history.back()';

    sys_msg($_LANG['update_success'], 0, $links);

}

/*------------------------------------------------------ */
//-- 批量发放红包
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'send_hb')
{
	/* 检查权限 */
    admin_priv('users_drop');
	
	if (isset($_POST['checkboxes']))
    {
		$sql = "SELECT user_id,gold_coin FROM " . $ecs->table('users') . " WHERE user_id " . db_create_in($_POST['checkboxes']);
        $usersinfo = $db->getAll($sql);
		foreach($usersinfo as $k=>$v){
			$usersinfo[$k]['openid']=get_openid($v['user_id']);
			//$usersinfo[$k]['nickname']=get_nickname($v['user_id']);
		}
		
		
		//发放红包
		$count=0;
		$count2=0;
		foreach($usersinfo as $k=>$v){
			if($v['gold_coin']>200){
				$gold_coin=200;
				$res=wx_pay($v['openid'],$gold_coin,$v['user_id']);
			}elseif($v['gold_coin']<1){
				//$gold_coin=0;
				$res='FAIL';
			}else{
				$gold_coin=$v['gold_coin'];
				$res=wx_pay($v['openid'],$gold_coin,$v['user_id']);
			}
			
			if($res=='SUCCESS'){
				$count++;
				$info = sprintf($_LANG['send_hb_log'], $gold_coin);
                log_account_change2($v['user_id'],0 , 0, -$gold_coin, 0, 0, $info);
			}else{
				$count2++;
			}
		}	
		
		$lnk[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
        sys_msg(sprintf($_LANG['batch_send_hb'], $count,$count2), 0, $lnk);
	}
	else
    {
        $lnk[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
        sys_msg($_LANG['no_select_user'], 0, $lnk);
    }
}
/*------------------------------------------------------ */
//-- 批量删除会员帐号
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'batch_remove')
{
    /* 检查权限 */
    admin_priv('users_drop');

    if (isset($_POST['checkboxes']))
    {
        $sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id " . db_create_in($_POST['checkboxes']);
        $col = $db->getCol($sql);
        $usernames = implode(',',addslashes_deep($col));
        $count = count($col);
        /* 通过插件来删除用户 */
        $users =& init_users();
        $users->remove_user($col);

        admin_log($usernames, 'batch_remove', 'users');

        $lnk[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
        sys_msg(sprintf($_LANG['batch_remove_success'], $count), 0, $lnk);
    }
    else
    {
        $lnk[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
        sys_msg($_LANG['no_select_user'], 0, $lnk);
    }
}

/* 编辑用户名 */
elseif ($_REQUEST['act'] == 'edit_username')
{
    /* 检查权限 */
    check_authz_json('users_manage');

    $username = empty($_REQUEST['val']) ? '' : json_str_iconv(trim($_REQUEST['val']));
    $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

    if ($id == 0)
    {
        make_json_error('NO USER ID');
        return;
    }

    if ($username == '')
    {
        make_json_error($GLOBALS['_LANG']['username_empty']);
        return;
    }

    $users =& init_users();

    if ($users->edit_user($id, $username))
    {
        if ($_CFG['integrate_code'] != 'ecshop')
        {
            /* 更新商城会员表 */
            $db->query('UPDATE ' .$ecs->table('users'). " SET user_name = '$username' WHERE user_id = '$id'");
        }

        admin_log(addslashes($username), 'edit', 'users');
        make_json_result(stripcslashes($username));
    }
    else
    {
        $msg = ($users->error == ERR_USERNAME_EXISTS) ? $GLOBALS['_LANG']['username_exists'] : $GLOBALS['_LANG']['edit_user_failed'];
        make_json_error($msg);
    }
}

/*------------------------------------------------------ */
//-- 编辑email
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_email')
{
    /* 检查权限 */
    check_authz_json('users_manage');

    $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
    $email = empty($_REQUEST['val']) ? '' : json_str_iconv(trim($_REQUEST['val']));

    $users =& init_users();

    $sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id = '$id'";
    $username = $db->getOne($sql);


    if (is_email($email))
    {
        if ($users->edit_user(array('username'=>$username, 'email'=>$email)))
        {
            admin_log(addslashes($username), 'edit', 'users');

            make_json_result(stripcslashes($email));
        }
        else
        {
            $msg = ($users->error == ERR_EMAIL_EXISTS) ? $GLOBALS['_LANG']['email_exists'] : $GLOBALS['_LANG']['edit_user_failed'];
            make_json_error($msg);
        }
    }
    else
    {
        make_json_error($GLOBALS['_LANG']['invalid_email']);
    }
}

/*------------------------------------------------------ */
//-- 删除会员帐号
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'remove')
{
    /* 检查权限 */
    admin_priv('users_drop');

    $sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id = '" . $_GET['id'] . "'";
    $username = $db->getOne($sql);
    /* 通过插件来删除用户 */
    $users =& init_users();
    $users->remove_user($username); //已经删除用户所有数据

    /* 记录管理员操作 */
    admin_log(addslashes($username), 'remove', 'users');

    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
    sys_msg(sprintf($_LANG['remove_success'], $username), 0, $link);
}

/*------------------------------------------------------ */
//--  收货地址查看
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'address_list')
{
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $sql = "SELECT a.*, c.region_name AS country_name, p.region_name AS province, ct.region_name AS city_name, d.region_name AS district_name ".
           " FROM " .$ecs->table('user_address'). " as a ".
           " LEFT JOIN " . $ecs->table('region') . " AS c ON c.region_id = a.country " .
           " LEFT JOIN " . $ecs->table('region') . " AS p ON p.region_id = a.province " .
           " LEFT JOIN " . $ecs->table('region') . " AS ct ON ct.region_id = a.city " .
           " LEFT JOIN " . $ecs->table('region') . " AS d ON d.region_id = a.district " .
           " WHERE user_id='$id'";
    $address = $db->getAll($sql);
    $smarty->assign('address',          $address);
    assign_query_info();
    $smarty->assign('ur_here',          $_LANG['address_list']);
    $smarty->assign('action_link',      array('text' => $_LANG['03_users_list'], 'href'=>'users.php?act=list&' . list_link_postfix()));
    $smarty->display('user_address_list.htm');
}

/*------------------------------------------------------ */
//-- 脱离推荐关系
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'remove_parent')
{
    /* 检查权限 */
    admin_priv('users_manage');

    $sql = "UPDATE " . $ecs->table('users') . " SET parent_id = 0 WHERE user_id = '" . $_GET['id'] . "'";
    $db->query($sql);

    /* 记录管理员操作 */
    $sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id = '" . $_GET['id'] . "'";
    $username = $db->getOne($sql);
    admin_log(addslashes($username), 'edit', 'users');

    /* 提示信息 */
    $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
    sys_msg(sprintf($_LANG['update_success'], $username), 0, $link);
}

elseif ($_REQUEST['act'] == 'set_user_fenxiao')
{
    /* 检查权限 */
    admin_priv('users_manage');
	$uid = $_GET['uid'];
	if(!$uid){
		//sys_msg(sprintf(, $username), 0, $link);
		die("ERROR");
	}

    $sql = "UPDATE " . $ecs->table('users') . " SET user_rank=100 WHERE user_id = '" . $uid . "'";
    $db->query($sql);

	$sql = "SELECT user_name FROM " . $ecs->table('users') . " WHERE user_id = '" . $uid . "'";
    $username = $db->getOne($sql);
    admin_log(addslashes($username), 'edit', 'users');

	$link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
	sys_msg(sprintf($_LANG['update_success'], $username), 0, $link);
}
//获取区域ID和其上级ID
elseif ($_REQUEST['act'] == 'get_area_id')
{
	include_once(ROOT_PATH . 'includes/cls_json.php');    
    $json = new JSON();
	$area_name = $_GET['area_name'];
	$sql = "SELECT * FROM " . $ecs->table('region') . " WHERE region_name LIKE '%" . $area_name . "%'";	
	$query = $db->getAll($sql);	
	foreach($query as $k=>$v){
		$result[$k]['area_id'] = $v['region_id'];
		$result[$k]['area_name'] = $v['region_name'];
		$result[$k]['area_upid'] = $v['parent_id'];
		$result[$k]['area_upname'] = get_area_name($v['parent_id']);
	}
	
	die($json->encode($result));
}
/*------------------------------------------------------ */
//-- 查看用户推荐会员列表
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'aff_list')
{
    /* 检查权限 */
    admin_priv('users_manage');
    $smarty->assign('ur_here',      $_LANG['03_users_list']);

    $auid = $_GET['auid'];
    $user_list['user_list'] = array();

    $affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
    $smarty->assign('affiliate', $affiliate);

    empty($affiliate) && $affiliate = array();

    $num = count($affiliate['item']);
    $up_uid = "'$auid'";
    $all_count = 0;
    for ($i = 1; $i<=$num; $i++)
    {
        $count = 0;
        if ($up_uid)
        {
            $sql = "SELECT user_id FROM " . $ecs->table('users') . " WHERE parent_id IN($up_uid)";
            $query = $db->query($sql);
            $up_uid = '';
            while ($rt = $db->fetch_array($query))
            {
                $up_uid .= $up_uid ? ",'$rt[user_id]'" : "'$rt[user_id]'";
                $count++;
            }
        }
        $all_count += $count;

        if ($count)
        {
            $sql = "SELECT user_id, user_name, '$i' AS level, email, is_validated, user_money, frozen_money, rank_points, pay_points, reg_time,tname,mobile_phone,apply_time ".
                    " FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id IN($up_uid)" .
                    " ORDER by level, user_id";
            $user_list['user_list'] = array_merge($user_list['user_list'], $db->getAll($sql));
        }
    }

    $temp_count = count($user_list['user_list']);
    for ($i=0; $i<$temp_count; $i++)
    {
        $user_list['user_list'][$i]['reg_time'] = local_date($_CFG['date_format'], $user_list['user_list'][$i]['reg_time']);
		$user_list['user_list'][$i]['apply_time'] = local_date($_CFG['date_format'], $user_list['user_list'][$i]['apply_time']);
		$user_list['user_list'][$i]['nickname'] = get_nickname($user_list['user_list'][$i]['user_id']);
    }

    $user_list['record_count'] = $all_count;

    $smarty->assign('user_list',    $user_list['user_list']);
    $smarty->assign('record_count', $user_list['record_count']);
    $smarty->assign('full_page',    1);
    $smarty->assign('action_link',  array('text' => $_LANG['back_note'], 'href'=>"users.php?act=edit&id=$auid"));

    assign_query_info();
    $smarty->display('affiliate_list.htm');
}

/**
 *  返回用户列表数据
 *
 * @access  public
 * @param
 *
 * @return void
 */
function user_list()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 过滤条件 */
		$filter['s_type'] = empty($_REQUEST['s_type']) ? '' : trim($_REQUEST['s_type']);
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }
        $filter['rank'] = empty($_REQUEST['rank']) ? 0 : intval($_REQUEST['rank']);
        $filter['pay_points_gt'] = empty($_REQUEST['pay_points_gt']) ? 0 : intval($_REQUEST['pay_points_gt']);
        $filter['pay_points_lt'] = empty($_REQUEST['pay_points_lt']) ? 0 : intval($_REQUEST['pay_points_lt']);

        $filter['sort_by']    = empty($_REQUEST['sort_by'])    ? 'user_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC'     : trim($_REQUEST['sort_order']);

		

        $ex_where = ' WHERE 1 ';
		
		// UUECS
		if($_GET['is_fenxiao']){
			$ex_where .= " AND user_rank !='100' AND apply_time !='' ";
		}
		
        if ($filter['keywords'])
        {
			if ($filter['s_type']==0){
            	$ex_where .= " AND user_name LIKE '%" . mysql_like_quote($filter['keywords']) ."%'";
			}elseif($filter['s_type']==1)	{
				$ex_where .= " AND tname LIKE '%" . mysql_like_quote($filter['keywords']) ."%'";
			}elseif($filter['s_type']==2)	{
				$ex_where .= " AND mobile_phone LIKE '%" . mysql_like_quote($filter['keywords']) ."%'";
			}elseif($filter['s_type']==4)	{
				$ex_where .= " AND nicheng LIKE '%" . mysql_like_quote($filter['keywords']) ."%'";
			}else{
				$ex_where .= " AND user_id = ".$filter['keywords'];
			}
        }
        if ($filter['rank'])
        {
            $sql = "SELECT min_points, max_points, special_rank FROM ".$GLOBALS['ecs']->table('user_rank')." WHERE rank_id = '$filter[rank]'";
            $row = $GLOBALS['db']->getRow($sql);
            if ($row['special_rank'] > 0)
            {
                /* 特殊等级 */
                $ex_where .= " AND user_rank = '$filter[rank]' ";
            }
            else
            {
                $ex_where .= " AND rank_points >= " . intval($row['min_points']) . " AND rank_points < " . intval($row['max_points']);
            }
        }
        if ($filter['pay_points_gt'])
        {
             $ex_where .=" AND pay_points >= '$filter[pay_points_gt]' ";
        }
        if ($filter['pay_points_lt'])
        {
            $ex_where .=" AND pay_points < '$filter[pay_points_lt]' ";
        }

        $filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('users') . $ex_where);

        /* 分页大小 */
        $filter = page_and_size($filter);
        $sql = "SELECT user_id, user_name,nicheng, email, is_validated,double_time,next_double_time, user_money, frozen_money, gold_coin, rank_points, pay_points, reg_time,apply_time,user_rank,tname,mobile_phone ".//UUECS
                " FROM " . $GLOBALS['ecs']->table('users') . $ex_where .
                " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ',' . $filter['page_size'];
	//die($sql);
        $filter['keywords'] = stripslashes($filter['keywords']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $user_list = $GLOBALS['db']->getAll($sql);	
	foreach ($user_list as $k=>$v){		
		$user_list[$k]['rank_name']=get_user_rank2($v['user_rank'],$v['rank_points']);	
	}

    $count = count($user_list);
    for ($i=0; $i<$count; $i++)
    {
        $user_list[$i]['reg_time'] = local_date($GLOBALS['_CFG']['date_format'], $user_list[$i]['reg_time']);
		$user_list[$i]['apply_time'] = local_date($GLOBALS['_CFG']['date_format'], $user_list[$i]['apply_time']);//UUECS
		$user_list[$i]['nickname'] = get_nickname($user_list[$i]['user_id']);
		if($user_list[$i]['next_double_time']==0){
			$user_list[$i]['next_time'] = 0;
		}else{						
			$user_list[$i]['next_time'] = local_date($GLOBALS['_CFG']['time_format'], $user_list[$i]['next_double_time']);
		}
        
        
    }
	
    $arr = array('user_list' => $user_list, 'filter' => $filter,
        'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}
function get_user_stock($user_id){
	$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_stock') . " WHERE user_id = '$user_id'";
	return $GLOBALS['db']->getAll($sql);
}
function get_last_stock($stock){
	if($stock == 0){
		return "0";
	}else{
		$market_value = get_shop_config_value('market_value');
		$pure_profit = get_shop_config_value('pure_profit');
		$all_premium = get_shop_config_value('all_premium'); 
		$total_assets = floatval($pure_profit) + floatval($all_premium);
		$stock2 = intval(intval($stock)/$total_assets*floatval($market_value));     //占市值的资产部分
		$last_stock = $stock2 > intval($stock) ? $stock2:$stock;
		return $last_stock;
	}        
}
function get_stock_value($stock){
    $no_dis_profit = get_shop_config_value('no_dis_profit');
    $all_premium = get_shop_config_value('all_premium');
    $market_value = get_shop_config_value('market_value');

    $total_assets = floatval($no_dis_profit) + floatval($all_premium);
    $stock2 = floor(intval($stock)/$total_assets*floatval($market_value));     //占市值的资产部分
    $last_stock = $stock2 > intval($stock) ? $stock2:$stock;
    return $last_stock;
}
function get_nickname($uid){
	$sql = "SELECT nickname FROM " . $GLOBALS['ecs']->table('wechat_user') . " WHERE ect_uid = '$uid'";
    $nickname = $GLOBALS['db']->getOne($sql);
	return $nickname;
}
function get_openid($uid){
	$sql = "SELECT openid FROM " . $GLOBALS['ecs']->table('wechat_user') . " WHERE ect_uid = '$uid'";
    $openid = $GLOBALS['db']->getOne($sql);
	return $openid;
}
function get_user_rank2($user_rank,$rank_points){
	if($rank_points==0){
		return "普通用户";
	}
	$sql2 = "SELECT `rank_id` , `rank_name` , `min_points` , `max_points` , `special_rank`".
           " FROM " .$GLOBALS['ecs']->table('user_rank');
    $rank_info = $GLOBALS['db']->getAll($sql2);
	
	//判断是否为特殊等级
	foreach($rank_info as $k=>$v){
		if($user_rank== $v['rank_id'] && $v['special_rank']==1){
			return $v['rank_name'];
		}
	}
	
	foreach($rank_info as $k=>$v){
		if($rank_points <= $v['max_points'] && $rank_points >= $v['min_points']){			
			return $v['rank_name'];
		}
	}
}
function get_area_name($region_id,$type = ''){
	if($type == 2){
		$sql = "SELECT parent_id FROM " . $GLOBALS['ecs']->table('region') . " WHERE region_id = '$region_id'";
		$parent_id = $GLOBALS['db']->getOne($sql);
		$sql = "SELECT region_name FROM " . $GLOBALS['ecs']->table('region') . " WHERE region_id = '$parent_id'";
		$region_name = $GLOBALS['db']->getOne($sql);
		return $region_name;
	}else{
		$sql = "SELECT region_name FROM " . $GLOBALS['ecs']->table('region') . " WHERE region_id = '$region_id'";
		$region_name = $GLOBALS['db']->getOne($sql);
		return $region_name;
	}	
}


//获取微信支付信息
function get_wxpay_info(){
	$sql = "SELECT pay_config FROM " . $GLOBALS['ecs']->table('touch_payment') . " WHERE pay_code = 'wxpay' and enabled = 1";
    $wxpay_info = $GLOBALS['db']->getOne($sql);
	$wxpay_info=unserialize($wxpay_info);	
		$wxpay_info_arr['wxpay_appid']=$wxpay_info[0]['value'];
		$wxpay_info_arr['wxpay_appsecret']=$wxpay_info[1]['value'];
		$wxpay_info_arr['wxpay_mchid']=$wxpay_info[2]['value'];
		$wxpay_info_arr['wxpay_key']=$wxpay_info[3]['value'];
	return $wxpay_info_arr;
}


	/**
     * 微信支付
     * 
     * @param string $openid 用户openid
     */
    function wx_pay($re_openid,$gold_coin,$user_id)
    {
        include_once(ROOT_PATH . 'includes/hongbao/WxHongBaoHelper.php');
        $commonUtil = new CommonUtil();
        $wxHongBaoHelper = new WxHongBaoHelper();
		
		$wxpay_info=get_wxpay_info();

        $wxHongBaoHelper->setParameter("nonce_str", great_rand());//随机字符串，丌长于 32 位
        $wxHongBaoHelper->setParameter("mch_billno", $wxpay_info['wxpay_mchid'].date('YmdHis').rand(1000, 9999));//订单号
        $wxHongBaoHelper->setParameter("mch_id", $wxpay_info['wxpay_mchid']);//商户号
        $wxHongBaoHelper->setParameter("wxappid", $wxpay_info['wxpay_appid']);
        $wxHongBaoHelper->setParameter("nick_name", '全国爱牙活动');//提供方名称
        $wxHongBaoHelper->setParameter("send_name", '全国爱牙活动');//红包发送者名称
        $wxHongBaoHelper->setParameter("re_openid", $re_openid);//相对于医脉互通的openid
        $wxHongBaoHelper->setParameter("total_amount", $gold_coin*100);//付款金额，单位分
        $wxHongBaoHelper->setParameter("min_value", 100);//最小红包金额，单位分
        $wxHongBaoHelper->setParameter("max_value", 20000);//最大红包金额，单位分 
        $wxHongBaoHelper->setParameter("total_num", 1);//红包収放总人数
        $wxHongBaoHelper->setParameter("wishing", '恭喜发财');//红包祝福诧
        $wxHongBaoHelper->setParameter("client_ip", '127.0.0.1');//调用接口的机器 Ip 地址
        $wxHongBaoHelper->setParameter("act_name", '全国爱牙活动');//活劢名称
        $wxHongBaoHelper->setParameter("remark", '赶快领取！');//备注信息
        $postXml = $wxHongBaoHelper->create_hongbao_xml();
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';		
		$responseXml = $wxHongBaoHelper->curl_post_ssl($url, $postXml);
		$responseObj = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
		
		$wp_result=objectToArray($responseObj);
		//写入数据库
		log_hongbao($wp_result,$user_id);
		
		return $wp_result['result_code'];

		return;
       
    }
	
	/**
     * 生成随机数
     * 
     */     
    function great_rand(){
        $str = '1234567890abcdefghijklmnopqrstuvwxyz';
        for($i=0;$i<30;$i++){
            $j=rand(0,35);
            $t1 .= $str[$j];
        }
        return $t1;    
    }
	/**
	 * Convert a SimpleXML object into an array (last resort).
	 * @param object $xml
	 * @param bool   $root    Should we append the root node into the array
	 * @return array|string
	 */
	function xmlToArr($xml, $root = true)
	{

		if(!$xml->children())
		{
			return (string)$xml;
		}
		$array = array();
		foreach($xml->children() as $element => $node)
		{
			$totalElement = count($xml->{$element});
			if(!isset($array[$element]))
			{
				$array[$element] = "";
			}
			// Has attributes
			if($attributes = $node->attributes())
			{
				$data = array('attributes' => array(), 'value' => (count($node) > 0) ? $this->xmlToArr($node, false) : (string)$node);
				foreach($attributes as $attr => $value)
				{
					$data['attributes'][$attr] = (string)$value;
				}
				if($totalElement > 1)
				{
					$array[$element][] = $data;
				}
				else
				{
					$array[$element] = $data;
				}
				// Just a value
			}
			else
			{
				if($totalElement > 1)
				{
					$array[$element][] = $this->xmlToArr($node, false);
				}
				else
				{
					$array[$element] = $this->xmlToArr($node, false);
				}
			}
		}
		if($root)
		{
			return array($xml->getName() => $array);
		}
		else
		{
			return $array;
		}

	}
	//对象转数组,使用get_object_vars返回对象属性组成的数组
	function objectToArray($obj){
		$arr = is_object($obj) ? get_object_vars($obj) : $obj;
		if(is_array($arr)){
			return array_map(__FUNCTION__, $arr);
		}else{
			return $arr;
		}
	}
	

?>