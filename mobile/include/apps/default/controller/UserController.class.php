<?php
/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：UserController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch用户中心
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');
class UserController extends CommonController {
	protected $user_id;
	protected $action;
	protected $back_act = '';
	/**
	 * 构造函数
	 */
	public function __construct() {
		parent::__construct();
		// 属性赋值
		$this->user_id = $_SESSION['user_id'];
		$this->action = ACTION_NAME;
		// 验证登录
		$this->check_login();
		// 用户信息
		$info = model('ClipsBase')->get_user_default($this->user_id);
		// 如果是显示页面，对页面进行相应赋值
		assign_template();
		$this->assign('action', $this->action);
		$this->assign('info', $info);
	}
	/**
	 * 会员中心欢迎页
	 */
	public function index() {
		// 是否成为分销商
		$user_info = model('ClipsBase')->get_user_default($this->user_id);
		$this->assign('user_rank', $user_info['user_rank']);
		// 用户等级
		$user_rank_id = model('ClipsBase')->get_user_rank($this->user_id);
		//dump($user_rank_id);
		if ($rank = model('ClipsBase')->get_rank_info2($user_rank_id)) {
			$this->assign('rank_name', $rank['rank_name']);
		}
		if ($user_rank_id == 136 || $user_rank_id == 137) {
			$proxy_area = model('ClipsBase')->get_proxy_area($this->user_id);
			if ($proxy_area) {
				$this->assign('proxy_area', $proxy_area);
			}
		}
		//检测用户的余额是否为负数
		$user_surplus = model('ClipsBase')->get_user_surplus2($this->user_id);
		if (floatval($user_surplus) < 0) {
			model('ClipsBase')->send_wechat_msg(4, $this->user_id, "有人余额为负数，ID为" . $this->user_id . ",请去后台查看", 0);
		}
		//dump($user_info);
		if ($user_info['frozen_premium'] <> '0.00') {
			$this->assign('frozen_premium', $user_info['frozen_premium']);
		}
		if ($user_info['done_stock'] <> '0') {
			$this->assign('done_stock', $user_info['done_stock']);
		}
		//消费用户的推荐码显示
		//$user_rank_id = model('ClipsBase')->get_user_rank($this->user_id);
		//if($user_rank_id !='117'){
		$re_code = encodeId($this->user_id);
		$t_re_code = model('ClipsBase')->add_re_code($this->user_id, $re_code);
		$this->assign('t_re_code', $t_re_code);
		//}
		$market_value = model('ClipsBase')->get_shop_config_value('market_value');
		$this->assign('market_value', p_easy($market_value));
		$this->assign('pe_ratios', model('ClipsBase')->get_shop_config_value('pe_ratios'));
		if ($user_rank_id == '138' || $user_rank_id == '144') {
			$user_stock = model('ClipsBase')->get_user_stock($this->user_id);
			foreach ($user_stock as $k => $v) {
				if ($v['ktimes'] == '1') {
					$user_stock[$k]['is_first'] = 1;
				}
				$user_stock[$k]['user_premium'] = p_easy($user_stock[$k]['user_premium']);
				$user_stock[$k]['premium_time'] = local_date(C('date_format'), $user_stock[$k]['premium_time']);
				$user_stock[$k]['last_stock'] = p_easy(model('ClipsBase')->get_last_stock($user_stock[$k]['stock']));
				if (floatval($user_stock[$k]['now_assess_value']) >= floatval($user_stock[$k]['year_assess_value'])) {
					$user_stock[$k]['year_assess_value'] = "已完成";
				} else {
					$user_stock[$k]['year_assess_value'] = p_easy(floatval($user_stock[$k]['year_assess_value']) - floatval($user_stock[$k]['now_assess_value']));
				}
				$user_stock[$k]['now_assess_value'] = p_easy($user_stock[$k]['now_assess_value']);
			}
			$this->assign('pure_profit', p_easy(model('ClipsBase')->get_shop_config_value('pure_profit')));
			$this->assign('assess_value', p_easy(model('ClipsBase')->get_shop_config_value('assess_value')));
			$this->assign('all_premium', p_easy(model('ClipsBase')->get_shop_config_value('all_premium')));
			$this->assign('no_dis_profit', p_easy(model('ClipsBase')->get_shop_config_value('no_dis_profit')));
			$this->assign('user_stock', $user_stock);
			//检测用户当前月的任务是否完成			
			if(intval(date("d")) >19){
				if(model('ClipsBase')->check_month_task($this->user_id)){						
					$last_time = model('ClipsBase')->get_last_notice($this->user_id);
					if(!empty($last_time) && date('m-d',(intval($last_time)+28800)) == date('m-d')){
						//不再提示							
					}else{
						//提示
						model('ClipsBase')->notice_user($this->user_id);
						$this->assign('notice', 1);
					}
				}
			}				
			
			
		}
		//获取用户的礼包数量
		$this->assign('user_spree_count', model('ClipsBase')->get_user_spree_count($this->user_id));
		//获取用户下次翻倍时间
		$this->assign('user_next_double_time', $user_info['next_double_time']);
		//获取用户是否关注公众号
		$user_wechat_info = model('ClipsBase')->get_user_wechatinfo($this->user_id);
		if ($user_wechat_info) {
			$this->assign('is_subscribe', $user_wechat_info['subscribe']);
		} else {
			$this->assign('is_subscribe', 0);
		}
		// 收藏
		$goods_list = model('ClipsBase')->get_collection_goods($this->user_id, 5, 0);
		// 评论
		$comment_list = model('ClipsBase')->get_comment_list($this->user_id, 5, 0);
		// 浏览记录
		$history = insert_history();
		// 信息中心是否有新回复
		$sql = 'SELECT msg_id FROM ' . $this->model->pre . 'feedback WHERE parent_id IN (SELECT f.msg_id FROM ' . $this->model->pre . 'feedback f LEFT JOIN ' . $this->model->pre . 'touch_feedback t ON f.msg_id = t.msg_id WHERE f.parent_id = 0 and f.user_id = ' . $this->user_id . ' and t.msg_read = 0 ORDER BY msg_time DESC) ORDER BY msg_time DESC';
		$rs = $this->model->query($sql);
		if ($rs) {
			$this->assign('new_msg', 1);
		}
		//赚取的总红包
		$hb_count = model('ClipsBase')->get_hb_count($this->user_id);
		//dump($hb_count);
		$this->assign('hb_count', $hb_count['hb']);
		//我的粉丝数量
		$line_count = model('Sale')->get_line_count();
		$this->assign('line_count', $line_count);
		//分享产品
		$goods_count = model('Sale')->get_sale_goods_count();
		$this->assign('goods_count', $goods_count);
		$this->assign('user_rank_id', $user_rank_id);
		$this->assign('user_id', $this->user_id);
		$this->assign('user_notice', C('user_notice'));
		$this->assign('goods_list', $goods_list);
		$this->assign('comment_list', $comment_list);
		$this->assign('history', $history);
		$this->assign('title', L('user_center'));
		//$this->assign('time', time());
		$this->display('user.dwt');
	}
	/**
	 * 账户中心
	 */
	public function profile() {
		// 修改个人资料的处理
		if (IS_POST) {
			$email = I('post.email');
			$other['qq'] = $qq = I('post.extend_field2');
			$other['office_phone'] = $office_phone = I('post.extend_field3');
			$other['mobile_phone'] = $mobile_phone = I('post.extend_field5');
			$other['nicheng'] = $nicheng = I('post.nicheng');
			$other['tname'] = $tname = I('post.tname');
			$sel_question = I('post.sel_question');
			$passwd_answer = I('post.passwd_answer');
			$other['raply_bank'] = I('post.raply_bank');
			$other['raply_kaihu'] = I('post.raply_kaihu');
			$other['raply_username'] = I('post.raply_username');
			$other['raply_number'] = I('post.raply_number');
			$other['raply_beizhu'] = I('post.raply_beizhu');
			
			$sf_code = I('post.extend_field7');
			if (empty($other['tname']) || empty($other['mobile_phone']) || empty($sf_code)) {
				show_message("真实姓名、手机号、身份证号必须填写");
			}
			
			// 读出所有扩展字段的id
			$where['type'] = 0;
			$where['display'] = 1;
			$fields_arr = $this->model->table('reg_fields')->field('id')->where($where)->order('dis_order, id')->select();
			if (empty($fields_arr)) {
				$fields_arr = array();
			}
			// 循环更新扩展用户信息
			foreach ($fields_arr as $val) {
				$extend_field_index = 'extend_field' . $val['id'];
				if (isset($_POST[$extend_field_index])) {
					$temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr(htmlspecialchars($_POST[$extend_field_index]), 0, 99) : htmlspecialchars($_POST[$extend_field_index]);
					$where_s['reg_field_id'] = $val['id'];
					$where_s['user_id'] = $this->user_id;
					$rs_s = $this->model->table('reg_extend_info')->where($where_s)->find();
					if ($rs_s) {
						// 如果之前没有记录，则插入
						$where_u['reg_field_id'] = $val['id'];
						$where_u['user_id'] = $this->user_id;
						$data_u['content'] = $temp_field_content;
						$this->model->table('reg_extend_info')->data($data_u)->where($where_u)->update();
					} else {
						$data_i['user_id'] = $this->user_id;
						$data_i['reg_field_id'] = $val['id'];
						$data_i['content'] = $temp_field_content;
						$this->model->table('reg_extend_info')->data($data_i)->insert();
					}
				}
			}
			if (!empty($office_phone) && !preg_match('/^[\d|\_|\-|\s]+$/', $office_phone)) {
				show_message(L('passport_js.office_phone_invalid'));
			}
			if (!empty($qq) && !preg_match('/^\d+$/', $qq)) {
				show_message(L('passport_js.qq_invalid'));
			}
			if (!empty($mobile_phone) && !preg_match('/^[\d-\s]+$/', $mobile_phone)) {
				show_message(L('passport_js.mobile_phone_invalid'));
			}
			// 写入密码提示问题和答案
			if (!empty($passwd_answer) && !empty($sel_question)) {
				$where_up['user_id'] = $this->user_id;
				$data_up['passwd_question'] = $sel_question;
				$data_up['passwd_answer'] = $passwd_answer;
				$this->model->table('users')->data($data_up)->where($where_up)->update();
			}
			$profile = array('user_id' => $this->user_id, 'email' => I('post.email'), 'sex' => I('post.sex', 0), 'other' => isset($other) ? $other : array());
			if (model('Users')->edit_profile($profile)) {
				show_message(L('edit_profile_success'), L('profile_lnk'), url('profile'), 'info');
			} else {
				if (self::$user->error == ERR_EMAIL_EXISTS) {
					$msg = sprintf(L('email_exist'), $profile['email']);
				} else {
					$msg = L('edit_profile_failed');
				}
				show_message($msg, '', '', 'info');
			}
			exit();
		}
		// 用户资料
		$user_info = model('Users')->get_profile($this->user_id);
		// 取出注册扩展字段
		$where = 'type < 2 and display = 1';
		$extend_info_list = $this->model->table('reg_fields')->where($where)->order('dis_order, id')->select();
		$condition['user_id'] = $this->user_id;
		$extend_info_arr = $this->model->table('reg_extend_info')->field('reg_field_id, content')->where($condition)->select();
		if (empty($extend_info_arr)) {
			$extend_info_arr = array();
		}
		//dump($extend_info_arr);
		$temp_arr = array();
		foreach ($extend_info_arr as $val) {
			$temp_arr[$val['reg_field_id']] = $val['content'];
		}
		foreach ($extend_info_list as $key => $val) {
			switch ($val['id']) {
				case 1:
					$extend_info_list[$key]['content'] = $user_info['nicheng'];
				break;
				case 2:
					$extend_info_list[$key]['content'] = $user_info['qq'];
				break;
				case 3:
					$extend_info_list[$key]['content'] = $user_info['office_phone'];
				break;
				case 4:
					unset($extend_info_list[$key]);
				break;
				case 5:
					$extend_info_list[$key]['content'] = $user_info['mobile_phone'];
				break;
					/*
					
					case 7:
					
					               $extend_info_list[$key]['content'] = $user_info['tname'];
					
					               break;
					
					*/
				default:
					$extend_info_list[$key]['content'] = empty($temp_arr[$val['id']]) ? '' : $temp_arr[$val['id']];
			}
		}
		$this->assign('title', L('profile'));
		$this->assign('extend_info_list', $extend_info_list);
		// 密码提示问题
		$this->assign('passwd_questions', L('passwd_questions'));
		$this->assign('profile', $user_info);
		//获取微信头像和昵称
		$user_wechat_info = model('ClipsBase')->get_touxiang($this->user_id);
		$this->assign('wechat_info', $user_wechat_info[0]);
		$this->display('user_profile.dwt');
	}
	//自行修改头像
	public function update_headimg() {
		$time = time();
		$img_content = $_POST['avatar_base64']; // 图片内容
		$u_id = $this->user_id;
		if ($u_id) {
			if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $img_content, $result)) {
				$type = $result[2];
				$new_file = "./data/a_headimg/{$time}_tx_{$u_id}.{$type}";
				if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $img_content)))) {
					$res = model('ClipsBase')->add_u_headimg($new_file, $u_id);
					if ($res) {
						$result = array("code" => 1, "msg" => "修改头像成功", "img_m" => "");
						$result['img_m'] = $new_file;
						echo json_encode($result);
					} else {
						$result = array("code" => 0, "msg" => "修改头像失败", "img_m" => "");
						echo json_encode($result);
					}
				}
			}
		}
	}
	/**
	 * 资金管理
	 */
	public function account_detail() {
		// 获取剩余余额
		$surplus_amount = model('ClipsBase')->get_user_surplus($this->user_id);
		if (empty($surplus_amount)) {
			$surplus_amount = 0;
		}
		$size = I(C('page_size'), 5);
		$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
		$where = 'user_id = ' . $this->user_id . ' AND user_money <> 0';
		$count = $this->model->table('account_log')->field('COUNT(*)')->where($where)->getOne();
		$this->pageLimit(url('user/account_detail'), $size);
		$this->assign('pager', $this->pageShow($count));
		$account_detail = model('Users')->get_account_detail($this->user_id, $size, ($page - 1) * $size);
		$this->assign('title', L('label_user_yue'));
		$this->assign('surplus_amount', price_format($surplus_amount, false));
		$this->assign('account_log', $account_detail);
		$this->display('user_account_detail.dwt');
	}
	/**
	 * 金币管理
	 */
	public function account_detail2() {
		// 获取剩余余额
		$gold_amount = model('ClipsBase')->get_user_gold2($this->user_id);
		if (empty($gold_amount)) {
			$gold_amount = 0;
		}
		$size = I(C('page_size'), 5);
		$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
		$where = 'user_id = ' . $this->user_id . ' AND gold_coin <> 0';
		$count = $this->model->table('account_log')->field('COUNT(*)')->where($where)->getOne();
		$this->pageLimit(url('user/account_detail2'), $size);
		$this->assign('pager', $this->pageShow($count));
		$account_detail = model('Users')->get_account_detail2($this->user_id, $size, ($page - 1) * $size);
		$this->assign('title', L('label_user_gold'));
		$this->assign('surplus_amount', price_format($surplus_amount, false));
		$this->assign('gold_amount', $gold_amount);
		$this->assign('account_log', $account_detail);
		$this->display('user_account_detail2.dwt');
	}
	/**
	 * 银币管理
	 */
	public function account_detail3() {
		// 获取剩余银币
		$sliv_amount = model('ClipsBase')->get_user_sliv2($this->user_id);
		if (empty($sliv_amount)) {
			$sliv_amount = 0;
		}
		$size = I(C('page_size'), 5);
		$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
		$where = 'user_id = ' . $this->user_id . ' AND  pay_points <> 0';
		$count = $this->model->table('account_log')->field('COUNT(*)')->where($where)->getOne();
		$this->pageLimit(url('user/account_detail3'), $size);
		$this->assign('pager', $this->pageShow($count));
		$account_detail = model('Users')->get_account_detail3($this->user_id, $size, ($page - 1) * $size);
		$this->assign('title', L('label_user_silver'));
		$this->assign('surplus_amount', price_format($surplus_amount, false));
		$this->assign('sliv_amount', $sliv_amount);
		$this->assign('account_log', $account_detail);
		$this->display('user_account_detail3.dwt');
	}
	/**
	 * 积分管理
	 */
	public function account_detail4() {
		// 获取剩余银币
		$jifen_amount = model('ClipsBase')->get_user_jifen2($this->user_id);
		if (empty($jifen_amount)) {
			$jifen_amount = 0;
		}
		$size = I(C('page_size'), 5);
		$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
		$where = 'user_id = ' . $this->user_id . ' AND  rank_points <> 0';
		$count = $this->model->table('account_log')->field('COUNT(*)')->where($where)->getOne();
		$this->pageLimit(url('user/account_detail4'), $size);
		$this->assign('pager', $this->pageShow($count));
		$account_detail = model('Users')->get_account_detail4($this->user_id, $size, ($page - 1) * $size);
		$this->assign('title', L('label_user_jifen'));
		$this->assign('surplus_amount', price_format($surplus_amount, false));
		$this->assign('jifen_amount', $jifen_amount);
		$this->assign('account_log', $account_detail);
		$this->display('user_account_detail4.dwt');
	}
	/**
	 *  会员充值和提现申请记录
	 */
	public function account_log() {
		$size = I(C('page_size'), 5);
		$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
		$count = $this->model->table('user_account')->field('COUNT(*)')->where("user_id = $this->user_id AND process_type " . db_create_in(array(SURPLUS_SAVE, SURPLUS_RETURN)))->getOne();
		$this->pageLimit(url('user/account_log'), $size);
		$this->assign('pager', $this->pageShow($count));
		//获取剩余余额
		$surplus_amount = model('ClipsBase')->get_user_surplus2($this->user_id);
		if (empty($surplus_amount)) {
			$surplus_amount = 0;
		}
		//获取余额记录
		$account_log = model('ClipsBase')->get_account_log($this->user_id, $size, ($page - 1) * $size);
		//模板赋值
		$this->assign('surplus_amount', $surplus_amount);
		$this->assign('account_log', $account_log);
		$this->assign('title', L('label_user_gold'));
		$this->display('user_account_log.dwt');
	}
	/**
	 *  会员股份记录
	 */
	public function stock_log() {
		$size = I(C('page_size'), 5);
		$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
		$count = $this->model->table('stock_log')->field('COUNT(*)')->where("user_id = $this->user_id AND change_type " . db_create_in(array('6', '9')))->getOne();
		$this->pageLimit(url('user/stock_log'), $size);
		$this->assign('pager', $this->pageShow($count));
		//获取剩余余额
		$all_stock = model('ClipsBase')->get_user_stock($this->user_id);
		$all_stock_value = 0;
		if (!empty($all_stock)) {
			foreach ($all_stock as $k => $v) {
				$stock_value = model('ClipsBase')->get_last_stock($v['stock']);
				$all_stock_value+= $stock_value;
			}
		}
		//获取余额记录
		$account_log = model('ClipsBase')->get_stock_log($this->user_id, $size, ($page - 1) * $size);
		//模板赋值
		$this->assign('all_stock_value', $all_stock_value);
		$this->assign('account_log', $account_log);
		$this->assign('title', L('label_user_gold'));
		$this->display('user_stock_log.dwt');
	}
	/**
	 *  删除会员余额
	 */
	public function cancel() {
		$id = I('get.id', 0);
		if ($id == 0 || $this->user_id == 0) {
			ecs_header("Location: " . url('User/account_log'));
			exit;
		}
		$result = model('ClipsBase')->del_user_account($id, $this->user_id);
		ecs_header("Location: " . url('User/account_log'));
	}
	/**
	 *  会员退款申请界面
	 */
	public function account_raply() {
		//检测用户的提现银行信息
		$raply_info = model('ClipsBase')->get_user_raply_info($this->user_id);
		if (empty($raply_info['raply_bank']) || empty($raply_info['raply_kaihu']) || empty($raply_info['raply_username']) || empty($raply_info['raply_number'])) {
			$this->assign('raply_info', '您的提现信息不完整，请补充后再提现！<a href=index.php?m=default&c=user&a=profile>点此补充</a>');
		}
		// 获取剩余余额
		$surplus_amount = model('ClipsBase')->get_user_surplus2($this->user_id);
		if (empty($surplus_amount)) {
			$surplus_amount = 0;
		}
		$frozen_premium = model('ClipsBase')->get_user_frozen_premium($this->user_id);
		if ($frozen_premium <> '0.00') {
			$this->assign('frozen_premium', $frozen_premium);
		}
		$this->assign('surplus_amount', $surplus_amount);
		$this->assign('title', L('label_user_gold'));
		$this->display('user_account_raply.dwt');
	}
	/**
	 *  会员预付款界面
	 */
	public function account_deposit() {
		$this->assign('title', L('label_user_surplus'));
		$surplus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		$account = model('ClipsBase')->get_surplus_info($surplus_id);
		$this->assign('payment', model('ClipsBase')->get_online_payment_list(false));
		$this->assign('order', $account);
		$this->display('user_account_deposit.dwt');
	}
	/**
	 *  对会员余额申请的处理
	 */
	public function act_account() {
		$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
		if ($amount <= 0) {
			show_message($_LANG['amount_gt_zero']);
		}
		/* 变量初始化 */
		$surplus = array('user_id' => $this->user_id, 'rec_id' => !empty($_POST['rec_id']) ? intval($_POST['rec_id']) : 0, 'process_type' => isset($_POST['surplus_type']) ? intval($_POST['surplus_type']) : 0, 'payment_id' => isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0, 'user_note' => isset($_POST['user_note']) ? trim($_POST['user_note']) : '', 'amount' => $amount);
		/* 退款申请的处理 */
		if ($surplus['process_type'] == 1) {
			//判断提现信息是否完整
			if (!empty($_POST['is_wanzheng'])) {
				show_message("提现信息不完整，无法提现", L('back_page_up'), '', 'info');
			}
			$raply_type = isset($_POST['raply_type']) ? intval($_POST['raply_type']) : 0;
			if ($raply_type == 0) {
				//判断是否大于1000元
				if ($amount < 1000) {
					show_message("单次提现最小1000元", L('back_page_up'), '', 'info');
				}
				/* 判断是否有足够的余额的进行退款的操作 */
				$sur_amount = model('ClipsBase')->get_user_surplus2($this->user_id);
				if ($amount > $sur_amount) {
					$content = L('surplus_amount_error');
					show_message($content, L('back_page_up'), '', 'info');
				}
				$surplus['is_premium'] = 0;
			} else {
				/* 判断用户是否有足够的入股金要提现  */
				$frozen_premium = model('ClipsBase')->get_user_frozen_premium($this->user_id);
				if ($amount > $frozen_premium) {
					$content = L('surplus_amount_error');
					show_message($content, L('back_page_up'), '', 'info');
				}				
				$surplus['is_premium'] = $raply_type;
				$s_id = model('ClipsBase')->get_user_back_stock($this->user_id);
				$surplus['s_id'] = $s_id;
			}
			//插入会员账目明细
			$amount = '-' . $amount;
			$surplus['payment'] = '';
			$surplus['rec_id'] = model('ClipsBase')->insert_user_account($surplus, $amount);
			/* 如果成功提交 */
			if ($surplus['rec_id'] > 0) {
				$content = L('surplus_appl_submit');
				show_message($content, L('back_account_log'), url('User/account_log'), 'info');
			} else {
				$content = L('process_false');
				show_message($content, L('back_page_up'), '', 'info');
			}
		}
		/* 如果是会员预付款，跳转到下一步，进行线上支付的操作 */
		else {
			if ($surplus['payment_id'] <= 0) {
				show_message(L('select_payment_pls'));
			}
			//获取支付方式名称
			$payment_info = array();
			$payment_info = model('Order')->payment_info($surplus['payment_id']);
			$surplus['payment'] = $payment_info['pay_name'];
			if ($surplus['rec_id'] > 0) {
				//更新会员账目明细
				$surplus['rec_id'] = model('ClipsBase')->update_user_account($surplus);
			} else {
				//插入会员账目明细
				$surplus['rec_id'] = model('ClipsBase')->insert_user_account($surplus, $amount);
			}
			//取得支付信息，生成支付代码
			$payment = unserialize_config($payment_info['pay_config']);
			//生成伪订单号, 不足的时候补0
			$order = array();
			$order['order_sn'] = $surplus['rec_id'];
			$order['user_name'] = $_SESSION['user_name'];
			$order['surplus_amount'] = $amount;
			$order['cz'] = 1;
			//计算支付手续费用
			$payment_info['pay_fee'] = pay_fee($surplus['payment_id'], $order['surplus_amount'], 0);
			//计算此次预付款需要支付的总金额
			$order['order_amount'] = $amount + $payment_info['pay_fee'];
			//记录支付log
			$order['log_id'] = model('ClipsBase')->insert_pay_log($surplus['rec_id'], $order['order_amount'], $type = PAY_SURPLUS, 0);
			/* 调用相应的支付方式文件 */
			include_once (ROOT_PATH . 'plugins/payment/' . $payment_info['pay_code'] . '.php');
			/* 取得在线支付方式的支付按钮 */
			$pay_obj = new $payment_info['pay_code']();
			$payment_info['pay_button'] = $pay_obj->get_code($order, $payment);
			/* 模板赋值 */
			$this->assign('payment', $payment_info);
			$this->assign('pay_fee', price_format($payment_info['pay_fee'], false));
			$this->assign('amount', price_format($amount, false));
			$this->assign('order', $order);
			$this->display('user_act_account.dwt');
		}
	}
	/**
	 * 会员通过帐目明细列表进行再付款的操作
	 */
	public function pay() {
		//变量初始化
		$surplus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		$payment_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
		if ($surplus_id == 0) {
			ecs_header("Location: " . url('User/account_log'));
			exit;
		}
		//如果原来的支付方式已禁用或者已删除, 重新选择支付方式
		if ($payment_id == 0) {
			ecs_header("Location: " . url('User/account_deposit', array('id' => $surplus_id)));
			exit;
		}
		//获取单条会员帐目信息
		$order = array();
		$order = model('ClipsBase')->get_surplus_info($surplus_id);
		//支付方式的信息
		$payment_info = array();
		$payment_info = model('Order')->payment_info($payment_id);
		/* 如果当前支付方式没有被禁用，进行支付的操作 */
		if (!empty($payment_info)) {
			//取得支付信息，生成支付代码
			$payment = unserialize_config($payment_info['pay_config']);
			//生成伪订单号
			$order['order_sn'] = $surplus_id;
			//获取需要支付的log_id
			$order['log_id'] = model('ClipsBase')->get_paylog_id($surplus_id, $pay_type = PAY_SURPLUS);
			$order['user_name'] = $_SESSION['user_name'];
			$order['surplus_amount'] = $order['amount'];
			//计算支付手续费用
			$payment_info['pay_fee'] = pay_fee($payment_id, $order['surplus_amount'], 0);
			//计算此次预付款需要支付的总金额
			$order['order_amount'] = $order['surplus_amount'] + $payment_info['pay_fee'];
			//如果支付费用改变了，也要相应的更改pay_log表的order_amount
			$order_amount = M()->getOne("SELECT order_amount FROM " . M()->pre . 'pay_log' . " WHERE log_id = '$order[log_id]'");
			$this->model->table('order_goods')->field('COUNT(*)')->where("order_id = '$order[order_id]' " . " AND is_real = 1")->getOne();
			if ($order_amount <> $order['order_amount']) {
				M()->query("UPDATE " . M()->pre . "pay_log SET order_amount = '$order[order_amount]' WHERE log_id = '$order[log_id]'");
			}
			/* 调用相应的支付方式文件 */
			include_once (ROOT_PATH . 'plugins/payment/' . $payment_info['pay_code'] . '.php');
			/* 取得在线支付方式的支付按钮 */
			$pay_obj = new $payment_info['pay_code']();
			$payment_info['pay_button'] = $pay_obj->get_code($order, $payment);
			/* 模板赋值 */
			$this->assign('payment', $payment_info);
			$this->assign('order', $order);
			$this->assign('pay_fee', price_format($payment_info['pay_fee'], false));
			$this->assign('amount', price_format($order['surplus_amount'], false));
			$this->display('user_act_account.dwt');
		}
		/* 重新选择支付方式 */
		else {
			$this->assign('payment', model('ClipsBase')->get_online_payment_list());
			$this->assign('order', $order);
			$this->display('user_account_deposit.dwt');
		}
	}
	/**
	 * 获取未付款订单
	 */
	public function not_pay_order_list() {
		$pay = 0;
		$size = I(C('page_size'), 10);
		$this->assign('show_asynclist', C('show_asynclist'));
		$count = $this->model->table('order_info')->where('user_id = ' . $this->user_id)->count();
		$filter['page'] = '{page}';
		$offset = $this->pageLimit(url('not_pay_order_list', $filter), $size);
		$offset_page = explode(',', $offset);
		$orders = model('Users')->get_user_orders($this->user_id, $pay, $offset_page[1], $offset_page[0]);
		$this->assign('pay', $pay);
		$this->assign('title', L('not_pay_list'));
		$this->assign('pager', $this->pageShow($count));
		$this->assign('orders_list', $orders);
		$this->display('user_order_list.dwt');
	}
	/**
	 * 获取全部订单
	 */
	public function order_list() {
		$pay = 1;
		$size = I(C('page_size'), 10);
		$count = $this->model->table('order_info')->where('user_id = ' . $this->user_id)->count();
		$filter['page'] = '{page}';
		$offset = $this->pageLimit(url('order_list', $filter), $size);
		$offset_page = explode(',', $offset);
		$orders = model('Users')->get_user_orders($this->user_id, $pay, $offset_page[1], $offset_page[0]);
		$this->assign('pay', $pay);
		$this->assign('title', L('order_list_lnk'));
		$this->assign('pager', $this->pageShow($count));
		$this->assign('orders_list', $orders);
		$this->display('user_order_list.dwt');
	}
	/**
	 * ajax获取订单
	 */
	public function async_order_list() {
		if (IS_AJAX) {
			$start = $_POST['last'];
			$limit = $_POST['amount'];
			$pay = isset($_GET['pay']) ? intval($_GET['pay']) : 0;
			$order_list = model('Users')->get_user_orders($this->user_id, $pay, $limit, $start);
			foreach ($order_list as $key => $order) {
				$this->assign('orders', $order);
				$sayList[] = array('single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi'));
			}
			die(json_encode($sayList));
		} else {
			$this->redirect(url('index'));
		}
	}
	/**
	 * 订单跟踪
	 */
	public function order_tracking() {
		$order_id = I('get.order_id', 0);
		$ajax = I('get.ajax', 0);
		$where['user_id'] = $this->user_id;
		$where['order_id'] = $order_id;
		$orders = $this->model->table('order_info')->field('order_id, order_sn, invoice_no, shipping_name, shipping_id')->where($where)->find();
		// 生成快递100查询接口链接
		$shipping = get_shipping_object($orders['shipping_id']);
		$query_link = $shipping->kuaidi100($orders['invoice_no']);
		$get_content = Http::doGet($query_link);
		$this->assign('title', L('order_tracking'));
		$this->assign('trackinfo', $get_content);
		$this->display('user_order_tracking.dwt');
	}
	//显示未发货的页面
	public function order_tracking_no() {
		$this->display('user_order_tracking_no.dwt');
	}
	/**
	 * 订单详情
	 */
	public function order_detail() {
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
		// 订单详情
		$order = model('Users')->get_order_detail($order_id, $this->user_id);
		//logg($order);
		if ($order['order_status'] == OS_UNCONFIRMED) {
			$order['handler'] = "<a class=\"btn btn-info ect-colorf\" href=\"" . url('user/cancel_order', array('order_id' => $order['order_id'])) . "\" onclick=\"if (!confirm('" . L('confirm_cancel') . "')) return false;\">" . L('cancel') . "</a>";
		} else if ($order['order_status'] == OS_SPLITED) {
			/* 对配送状态的处理 */
			if ($order['shipping_status'] == SS_SHIPPED) {
				@$order['handler'] = "<a class=\"btn btn-info ect-colorf\" href=\"" . url('user/affirm_received', array('order_id' => $order['order_id'])) . "\" onclick=\"if (!confirm('" . L('confirm_received') . "')) return false;\">" . L('received') . "</a>";
			} elseif ($order['shipping_status'] == SS_RECEIVED) {
				@$order['handler'] = '<a class="btn btn-info ect-colorf" type="button" href="javascript:void(0);">' . L('ss_received') . '</a>';
			} else {
				if ($order['pay_status'] == PS_UNPAYED) {
					@$order['handler'] = "<a class=\"btn btn-infoect-colorf\" href=\"" . url('user/cancel_order', array('order_id' => $order['order_id'])) . "\">" . L('pay_money') . "</a>";
				} else {
					// @$order['handler'] = "<a class=\"btn btn-info ect-colorf\" href=\"javascript:void(0);\">" . L('view_order') . "</a>";
					
				}
			}
		} else {
			$order['handler'] = '<a class="btn btn-info ect-colorf" type="button" href="javascript:void(0);">' . L('os.' . $order['order_status']) . '</a>';
		}
		if ($order === false) {
			ECTouch::err()->show(L('back_home_lnk'), './');
			exit();
		}
		// 订单商品
		$goods_list = model('Order')->order_goods($order_id);
		foreach ($goods_list as $key => $value) {
			$goods_list[$key]['market_price'] = price_format($value['market_price'], false);
			$goods_list[$key]['goods_price'] = price_format($value['goods_price'], false);
			$goods_list[$key]['subtotal'] = price_format($value['subtotal'], false);
			$goods_list[$key]['tags'] = model('ClipsBase')->get_tags($value['goods_id']);
			$goods_list[$key]['goods_thumb'] = get_image_path($order_id, $value['goods_thumb']);
		}
		// 设置能否修改使用余额数
		if ($order['order_amount'] > 0) {
			if ($order['order_status'] == OS_UNCONFIRMED || $order['order_status'] == OS_CONFIRMED) {
				$user = model('Order')->user_info($order['user_id']);
				if ($user['user_money'] + $user['credit_line'] > 0) {
					$this->assign('allow_edit_surplus', 1);
					$this->assign('max_surplus', sprintf(L('max_surplus'), $user['user_money']));
				}
			}
		}
		// 未发货，未付款时允许更换支付方式
		if ($order['order_amount'] > 0 && $order['pay_status'] == PS_UNPAYED && $order['shipping_status'] == SS_UNSHIPPED) {
			$payment_list = model('Order')->available_payment_list(false, 0, true);
			// 过滤掉当前支付方式和余额支付方式
			if (is_array($payment_list)) {
				foreach ($payment_list as $key => $payment) {
					if ($payment['pay_id'] == $order['pay_id'] || $payment['pay_code'] == 'balance') {
						unset($payment_list[$key]);
					}
				}
			}
			$this->assign('payment_list', $payment_list);
		}
		$order['pay_desc'] = html_out($order['pay_desc']);
		// 订单 支付 配送 状态语言项
		$order['order_status'] = L('os.' . $order['order_status']);
		$order['pay_status'] = L('ps.' . $order['pay_status']);
		$order['shipping_status'] = L('ss.' . $order['shipping_status']);
		// 如果是银行汇款或货到付款 则显示支付描述
		$payment = model('Order')->payment_info($order['pay_id']);
		if ($payment['pay_code'] == 'bank' || $payment['pay_code'] == 'cod') {
			$this->assign('pay_desc', $payment['pay_desc']);
		}
		//生成快递查询链接
		if ($order['invoice_no']) {
			$shipping = get_shipping_object($order['shipping_id']);
			$query_link = $shipping->kuaidi100($order['invoice_no']);
			$root_url = str_replace('/mobile', '', __URL__);
			$query_link2 = $query_link . '&callbackurl=' . urlencode($root_url . $_SERVER['REQUEST_URI']);
		}
		$this->assign('query_link', $query_link2);
		$this->assign('title', L('order_detail'));
		$this->assign('order', $order);
		$this->assign('goods_list', $goods_list);
		$this->display('user_order_detail.dwt');
	}
	/**
	 * 确认收货
	 */
	public function affirm_received() {
		$order_id = I('get.order_id', 0, 'intval');
		if (model('Users')->affirm_received($order_id, $this->user_id)) {
			ecs_header("Location: " . url('order_list') . "\n");
			exit();
		} else {
			ECTouch::err()->show(L('order_list_lnk'), url('order_list'));
		}
	}
	/**
	 * 编辑使用余额支付的处理
	 */
	public function edit_surplus() {
		// 检查订单号
		$order_id = intval($_POST['order_id']);
		if ($order_id <= 0) {
			ecs_header("Location: " . url('index/index') . "\n");
			exit();
		}
		// 检查余额
		$surplus = floatval($_POST['surplus']);
		if ($surplus <= 0) {
			ECTouch::err()->add(L('error_surplus_invalid'));
			ECTouch::err()->show(L('order_detail'), url('order_detail', array('order_id' => $order_id)));
		}
		// 取得订单order_id
		$order = model('Order')->order_info($order_id);
		if (empty($order)) {
			ecs_header("Location: " . url('index/index') . "\n");
			exit();
		}
		// 检查订单用户跟当前用户是否一致
		if ($_SESSION['user_id'] != $order['user_id']) {
			ecs_header("Location: " . url('index/index') . "\n");
			exit();
		}
		// 检查订单是否未付款，检查应付款金额是否大于0
		if ($order['pay_status'] != PS_UNPAYED || $order['order_amount'] <= 0) {
			ECTouch::err()->add(L('error_order_is_paid'));
			ECTouch::err()->show(L('order_detail'), url('order_detail', array('order_id' => $order_id)));
		}
		// 计算应付款金额（减去支付费用）
		$order['order_amount']-= $order['pay_fee'];
		// 余额是否超过了应付款金额，改为应付款金额
		if ($surplus > $order['order_amount']) {
			$surplus = $order['order_amount'];
		}
		// 取得用户信息
		$user = model('Order')->user_info($_SESSION['user_id']);
		// 用户帐户余额是否足够
		if ($surplus > $user['user_money'] + $user['credit_line']) {
			ECTouch::err()->add(L('error_surplus_not_enough'));
			ECTouch::err()->show(L('order_detail'), url('order_detail', array('order_id' => $order_id)));
		}
		// 修改订单，重新计算支付费用
		$order['surplus']+= $surplus;
		$order['order_amount']-= $surplus;
		if ($order['order_amount'] > 0) {
			$cod_fee = 0;
			if ($order['shipping_id'] > 0) {
				$regions = array($order['country'], $order['province'], $order['city'], $order['district']);
				$shipping = model('Shipping')->shipping_area_info($order['shipping_id'], $regions);
				if ($shipping['support_cod'] == '1') {
					$cod_fee = $shipping['pay_fee'];
				}
			}
			$pay_fee = 0;
			if ($order['pay_id'] > 0) {
				$pay_fee = pay_fee($order['pay_id'], $order['order_amount'], $cod_fee);
			}
			$order['pay_fee'] = $pay_fee;
			$order['order_amount']+= $pay_fee;
		}
		// 如果全部支付，设为已确认、已付款
		if ($order['order_amount'] == 0) {
			if ($order['order_status'] == OS_UNCONFIRMED) {
				$order['order_status'] = OS_CONFIRMED;
				$order['confirm_time'] = gmtime();
			}
			$order['pay_status'] = PS_PAYED;
			$order['pay_time'] = gmtime();
			//标记为全余额付款
			$all_surplus = 1;
		}
		$order = addslashes_deep($order);
		model('Users')->update_order($order_id, $order);
		// 更新用户余额
		$change_desc = sprintf(L('pay_order_by_surplus'), $order['order_sn']);
		$account_log = model('ClipsBase')->log_account_change3($user['user_id'], (-1) * $surplus, 0, 0, 0, $change_desc,99,2);
		//更新到订单详情里
		$k_own_money = floatval($account_log[own_money])*-1;
		$k_divi_money = floatval($account_log[divi_money])*-1;
		model('ClipsBase')->update_order_money($order ['order_sn'],$k_own_money,$k_divi_money);
		
		//全额付款的话，直接送积分，如果未付完，送积分的事情交给支付插件
		if ($all_surplus == 1) {
			//修改上级ID
			if ($order['up_id'] != 0) {
				$user_rankpoints = model('ClipsBase')->get_user_jifen2($order['user_id']);
				if ($user_rankpoints == 0) {
					model('ClipsBase')->update_upid($order['user_id'], $order['up_id']);
				}
			}
			//logg($order);
			$integral = model('ClipsBase')->integral_to_give($order['order_id']);
			$info = "订单 " . $order['order_sn'] . " 奖励";
			model('ClipsBase')->log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), $info);
			//在这里添加到ab_log里
			model('ClipsBase')->log_ab_change($order['order_id']);
			//在这里添加到销售任务流水里
			model('ClipsBase')->log_task($order['order_id']);
		}
		// 销量
		$this->update_touch_goods($order_id);
		// 跳转
		$url = url('order_detail', array('order_id' => $order_id));
		ecs_header("Location: $url\n");
		exit();
	}
	/**
	 * 更改支付方式的处理
	 */
	public function edit_payment() {
		// 检查支付方式
		$pay_id = intval($_POST['pay_id']);
		if ($pay_id <= 0) {
			ecs_header("Location: " . url('index/index') . "\n");
			exit();
		}
		$payment_info = model('Order')->payment_info($pay_id);
		if (empty($payment_info)) {
			ecs_header("Location: " . url('index/index') . "\n");
			exit();
		}
		// 检查订单号
		$order_id = intval($_POST['order_id']);
		if ($order_id <= 0) {
			ecs_header("Location: " . url('index/index') . "\n");
			exit();
		}
		// 取得订单
		$order = model('Order')->order_info($order_id);
		if (empty($order)) {
			ecs_header("Location: " . url('index/index') . "\n");
			exit();
		}
		// 检查订单用户跟当前用户是否一致
		if ($_SESSION['user_id'] != $order['user_id']) {
			ecs_header("Location: " . url('index/index') . "\n");
			exit();
		}
		// 检查订单是否未付款和未发货 以及订单金额是否为0 和支付id是否为改变
		if ($order['pay_status'] != PS_UNPAYED || $order['shipping_status'] != SS_UNSHIPPED || $order['goods_amount'] <= 0 || $order['pay_id'] == $pay_id) {
			$url = url('order_detail', array('order_id' => $order_id));
			ecs_header("Location: $url\n");
			exit();
		}
		$order_amount = $order['order_amount'] - $order['pay_fee'];
		$pay_fee = pay_fee($pay_id, $order_amount);
		$order_amount+= $pay_fee;
		$data['pay_id'] = $pay_id;
		$data['pay_name'] = $payment_info['pay_name'];
		$data['pay_fee'] = $pay_fee;
		$data['order_amount'] = $order_amount;
		$where['order_id'] = $order_id;
		$this->model->table('order_info')->data($data)->where($where)->update();
		// 跳转
		$url = url('order_detail', array('order_id' => $order_id));
		ecs_header("Location: $url\n");
		exit();
	}
	/**
	 * 取消订单
	 */
	public function cancel_order() {
		$order_id = I('get.order_id', 0, 'intval');
		if (model('Users')->cancel_order($order_id, $this->user_id)) {
			$url = url('order_list');
			ecs_header("Location: $url\n");
			exit();
		} else {
			ECTouch::err()->show(L('order_list_lnk'), url('order_list'));
		}
	}
	/**
	 * 收货地址列表界面
	 */
	public function address_list() {
		if (IS_AJAX) {
			$start = $_POST['last'];
			$limit = $_POST['amount'];
			// 获得用户所有的收货人信息
			$consignee_list = model('Users')->get_consignee_list($this->user_id, 0, $limit, $start);
			if ($consignee_list) {
				foreach ($consignee_list as $k => $v) {
					$address = '';
					if ($v['province']) {
						$address.= model('RegionBase')->get_region_name($v['province']);
					}
					if ($v['city']) {
						$address.= model('RegionBase')->get_region_name($v['city']);
					}
					if ($v['district']) {
						$address.= model('RegionBase')->get_region_name($v['district']);
					}
					$v['address'] = $address . ' ' . $v['address'];
					$v['url'] = url('user/edit_address', array('id' => $v['address_id']));
					$this->assign('consignee', $v);
					$sayList[] = array('single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi'));
				}
			}
			die(json_encode($sayList));
			exit();
		}
		// 赋值于模板
		$this->assign('title', L('consignee_info'));
		$this->display('user_address_list.dwt');
	}
	// 添加收货地址
	public function add_address() {
		if (IS_POST) {
			$address = array('user_id' => $this->user_id, 'address_id' => intval($_POST['address_id']), 'country' => I('post.country', 0, 'intval'), 'province' => I('post.province', 0, 'intval'), 'city' => I('post.city', 0, 'intval'), 'district' => I('post.district', 0, 'intval'), 'address' => I('post.address'), 'consignee' => I('post.consignee'), 'mobile' => I('post.mobile'));
			if (model('Users')->update_address($address)) {
				show_message(L('edit_address_success'), L('address_list_lnk'), url('address_list'));
			}
			exit();
		}
		$province_list = model('RegionBase')->get_regions(1, 1);
		$city_list = model('RegionBase')->get_regions(2);
		$district_list = model('RegionBase')->get_regions(3);
		$this->assign('title', L('add_address'));
		// 取得国家列表、商店所在国家、商店所在国家的省列表
		$this->assign('country_list', model('RegionBase')->get_regions());
		$this->assign('shop_province_list', model('RegionBase')->get_regions(1, C('shop_country')));
		$this->assign('province_list', $province_list);
		$this->assign('city_list', $city_list);
		$this->assign('district_list', $district_list);
		$this->display('user_add_address.dwt');
	}
	/**
	 * 编辑收货地址的处理
	 */
	public function edit_address() {
		// 编辑收货地址
		if (IS_POST) {
			$address = array('user_id' => $this->user_id, 'address_id' => intval($_POST['address_id']), 'country' => I('post.country', 0, 'intval'), 'province' => I('post.province', 0, 'intval'), 'city' => I('post.city', 0, 'intval'), 'district' => I('post.district', 0, 'intval'), 'address' => I('post.address'), 'consignee' => I('post.consignee'), 'mobile' => I('post.mobile'));
			if (model('Users')->update_address($address)) {
				show_message(L('edit_address_success'), L('address_list_lnk'), url('address_list'));
			}
			exit();
		}
		$id = isset($_GET['id']) ? intval($_GET['id']) : '';
		// 获得用户对应收货人信息
		$consignee = model('Users')->get_consignee_list($_SESSION['user_id'], $id);
		$province_list = model('RegionBase')->get_regions(1, 1);
		$city_list = model('RegionBase')->get_regions(2, $consignee['province']);
		$district_list = model('RegionBase')->get_regions(3, $consignee['city']);
		$this->assign('title', L('edit_address'));
		$this->assign('consignee', $consignee);
		// 取得国家列表、商店所在国家、商店所在国家的省列表
		$this->assign('country_list', model('RegionBase')->get_regions());
		$this->assign('shop_province_list', model('RegionBase')->get_regions(1, C('shop_country')));
		$this->assign('province_list', $province_list);
		$this->assign('city_list', $city_list);
		$this->assign('district_list', $district_list);
		$this->display('user_edit_address.dwt');
	}
	/**
	 * 删除收货地址
	 */
	public function del_address_list() {
		$id = intval($_GET['id']);
		if (model('Users')->drop_consignee($id)) {
			$url = url('address_list');
			ecs_header("Location: $url\n");
			exit();
		} else {
			show_message(L('del_address_false'));
		}
	}
	/**
	 * 信息中心
	 */
	public function msg_list() {
		if (IS_AJAX) {
			$order_id = I('get.order_id', 0);
			$start = $_POST['last'];
			$limit = $_POST['amount'];
			// 获取信息
			$message_list = model('ClipsBase')->get_message_list($this->user_id, $_SESSION['user_name'], $limit, $start, $order_id);
			if (is_array($message_list)) {
				// 修改信息状态
				$sql = 'SELECT parent_id FROM ' . $this->model->pre . 'feedback WHERE parent_id in (SELECT f.msg_id FROM ' . $this->model->pre . 'feedback f LEFT JOIN ' . $this->model->pre . 'touch_feedback t ON f.msg_id = t.msg_id WHERE f.parent_id = 0 AND f.user_id = ' . $this->user_id . ' AND t.msg_read = 0 ORDER BY msg_time DESC) ORDER BY msg_time DESC';
				$rs = $this->model->query($sql);
				if ($rs) {
					foreach ($rs as $v) {
						$where['msg_id'] = $v['parent_id'];
						$data['msg_read'] = 1;
						$this->model->table('touch_feedback')->data($data)->where($where)->update();
					}
				}
				foreach ($message_list as $key => $vo) {
					$this->assign('msg', $vo);
					$sayList[] = array('single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi'));
				}
			}
			echo json_encode($sayList);
			exit();
		} else {
			$order_id = I('request.order_id') ? intval(I('request.order_id')) : 0;
			/* 获取用户留言的数量 */
			if ($order_id) {
				$sql = "SELECT COUNT(*) as count FROM " . $this->model->pre . "feedback WHERE parent_id = 0 AND order_id = '$order_id' AND user_id = '$this->user_id'";
				$order_info = $this->row("SELECT * FROM " . $this->model->pre . "order_info WHERE order_id = '$order_id' AND user_id = '$this->user_id'");
				$order_info['url'] = url('user/order_detail', array('order_id' => $order_id));
			} else {
				$sql = "SELECT COUNT(*) as count FROM " . $this->model->pre . "feedback WHERE parent_id = 0 AND user_id = '$this->user_id' AND user_name = '" . $_SESSION['user_name'] . "' AND order_id=0";
			}
			$count = $this->model->getRow($sql);
			$filter['page'] = '{page}';
			$offset = $this->pageLimit(url('msg_list', $filter), 5);
			$offset_page = explode(',', $offset);
			// 获取信息
			$message_list = model('ClipsBase')->get_message_list($this->user_id, $_SESSION['user_name'], $offset_page[1], $offset_page[0], $order_id);
			$this->assign('pager', $this->pageShow($count['count']));
			$this->assign('message_list', $message_list);
		}
		$this->assign('title', L('user_service_list'));
		$this->display('user_msg_list.dwt');
	}
	/**
	 * 删除信息
	 */
	public function del_msg() {
		$id = I('get.id', 0);
		$order_id = I('get.order_id', 0);
		if ($id > 0) {
			$where_s['msg_id'] = $id;
			$row = $this->model->table('feedback')->field('user_id, message_img')->where($where_s)->find();
			if ($row && $row['user_id'] == $this->user_id) {
				// 验证通过，删除留言，回复，及相应文件
				if ($row['message_img']) {
					@unlink(ROOT_PATH . DATA_DIR . '/feedbackimg/' . $row['message_img']);
				}
				$where_d = 'msg_id = ' . $id . ' OR parent_id = ' . $id;
				$this->model->table('feedback')->where($where_d)->delete();
			}
		}
		$url = url('msg_list', array('order_id' => $order_id));
		ecs_header("Location: $url\n");
		exit();
	}
	/**
	 * 客户服务
	 */
	public function service() {
		if (IS_POST) {
			$message = array('user_id' => $this->user_id, 'user_name' => $_SESSION['user_name'], 'user_email' => $_SESSION['email'], 'msg_type' => I('post.msg_type', 0), 'msg_title' => I('post.msg_title'), 'msg_content' => I('post.msg_content'), 'order_id' => I('post.order_id', 0), 'upload' => (isset($_FILES['message_img']['error']) && $_FILES['message_img']['error'] == 0) || (!isset($_FILES['message_img']['error']) && isset($_FILES['message_img']['tmp_name']) && $_FILES['message_img']['tmp_name'] != 'none') ? $_FILES['message_img'] : array());
			if (model('ClipsBase')->add_message($message)) {
				$data['msg_id'] = M()->insert_id();
				$this->model->table('touch_feedback')->data($data)->insert();
				show_message(L('add_message_success'), L('user_service'), url('msg_list'), 'info');
			} else {
				self::err()->show(L('user_service'), url('service'));
			}
			exit();
		}
		// 页面显示
		$this->assign('title', L('user_service'));
		$this->display('user_service.dwt');
	}
	/**
	 * 分享推荐
	 */
	public function share() {
		$share = unserialize(C('affiliate'));
		$goodsid = I('request.goodsid', 0);
		if (empty($goodsid)) {
			$page = I('request.page', 1);
			$size = I(C('page_size'), 10);
			empty($share) && $share = array();
			if (empty($share['config']['separate_by'])) {
				// 推荐注册分成
				$affdb = array();
				$num = count($share['item']);
				$up_uid = "'$this->user_id'";
				$all_uid = "'$this->user_id'";
				for ($i = 1;$i <= $num;$i++) {
					$count = 0;
					if ($up_uid) {
						$where = 'parent_id IN(' . $up_uid . ')';
						$rs = $this->model->table('users')->field('user_id')->where($where)->select();
						if (empty($rs)) {
							$rs = array();
						}
						$up_uid = '';
						foreach ($rs as $k => $v) {
							$up_uid.= $up_uid ? ",'$v[user_id]'" : "'$v[user_id]'";
							if ($i < $num) {
								$all_uid.= ", '$v[user_id]'";
							}
							$count++;
						}
					}
					$affdb[$i]['num'] = $count;
					$affdb[$i]['point'] = $share['item'][$i - 1]['level_point'];
					$affdb[$i]['money'] = $share['item'][$i - 1]['level_money'];
					$this->assign('affdb', $affdb);
					dump($all_uid);
					$sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";
					$sql = "SELECT o.*, a.log_id, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
				}
			} else {
				// 推荐订单分成
				$sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";
				$sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
			}
			$res = $this->model->query($sqlcount);
			$count = $res[0]['count'];
			$url_format = url('share', array('page' => '{page}'));
			$limit = $this->pageLimit($url_format, 10);
			$sql = $sql . ' LIMIT ' . $limit;
			$rt = $this->model->query($sql);
			//dump($rt);
			if ($rt) {
				foreach ($rt as $k => $v) {
					if (!empty($v['suid'])) {
						// 在affiliate_log有记录
						if ($v['separate_type'] == - 1 || $v['separate_type'] == - 2) {
							// 已被撤销
							$v['is_separate'] = 3;
						}
					}
					$rt[$k]['order_sn'] = substr($v['order_sn'], 0, strlen($v['order_sn']) - 5) . "***" . substr($v['order_sn'], -2, 2);
				}
			} else {
				$rt = array();
			}
			$pager = $this->pageShow($count);
			$this->assign('pager', $pager);
			$this->assign('affiliate_type', $share['config']['separate_by']);
			$this->assign('logdb', $rt);
		} else {
			// 单个商品推荐
			$this->assign('userid', $this->user_id);
			$this->assign('goodsid', $goodsid);
			$types = array(1, 2, 3, 4, 5);
			$this->assign('types', $types);
			$goods = model('Goods')->get_goods_info($goodsid);
			$goods['goods_img'] = get_image_path(0, $goods['goods_img']);
			$goods['goods_thumb'] = get_image_path(0, $goods['goods_thumb']);
			$goods['shop_price'] = price_format($goods['shop_price']);
			$this->assign('goods', $goods);
		}
		$shopurl = __URL__ . '/?u=' . $this->user_id;
		$this->assign('shopurl', $shopurl);
		$this->assign('domain', __HOST__);
		$this->assign('shopdesc', C('shop_desc'));
		$this->assign('title', L('label_share'));
		$this->assign('share', $share);
		$this->display('user_share.dwt');
	}
	/**
	 * 生成二维码
	 */
	public function create_qrcode() {
		$value = I('get.value');
		if ($value) {
			// 二维码
			// 纠错级别：L、M、Q、H
			$errorCorrectionLevel = 'L';
			// 点的大小：1到10
			$matrixPointSize = 4;
			QRcode::png($value, false, $errorCorrectionLevel, $matrixPointSize, 2);
		}
	}
	/**
	 * 我的标签
	 */
	public function tag_list() {
		$tags = get_user_tags($this->user_id);
		$this->assign('title', L('label_tag'));
		$this->assign('tags', $tags);
		$this->display('user_tag_list.dwt');
	}
	/**
	 * 删除标签
	 */
	public function del_tag() {
		if (IS_AJAX) {
			$tag_words = I('get.tag_wrods');
			$rs = model('ClipsBase')->delete_tag($tag_words, $this->user_id);
			if (empty($rs)) {
				exit(json_encode(array('status' => 0, 'msg' => '删除失败')));
			}
		}
	}
	/**
	 * 我的红包
	 */
	public function bonus() {
		if (IS_POST) {
			$bonus_sn = I('post.bonus_sn', '', 'intval');
			if (model('Users')->add_bonus($this->user_id, $bonus_sn)) {
				show_message(L('add_bonus_sucess'), L('back_up_page'), url('bonus'), 'info');
			} else {
				ECTouch::err()->show(L('back_up_page'), url('bonus'));
			}
		}
		// 分页
		$filter['page'] = '{page}';
		$offset = $this->pageLimit(url('bonus', $filter), 5);
		$offset_page = explode(',', $offset);
		$count = $this->model->table('user_bonus')->where('user_id = ' . $this->user_id)->count();
		$bonus = model('Users')->get_user_bouns_list($this->user_id, $offset_page[1], $offset_page[0]);
		$this->assign('title', L('label_bonus'));
		$this->assign('pager', $this->pageShow($count));
		$this->assign('bonus', $bonus);
		$this->display('user_bonus.dwt');
	}
	/**
	 * 缺货登记列表
	 */
	public function booking_list() {
		/* 获取缺货登记的数量 */
		$sql = "SELECT COUNT(*) as num " . "FROM " . $this->model->pre . "booking_goods AS bg, " . $this->model->pre . "goods AS g " . "WHERE bg.goods_id = g.goods_id AND user_id = '$this->user_id'";
		$count = $this->model->query($sql);
		// 分页
		$filter['page'] = '{page}';
		$offset = $this->pageLimit(url('booking_list', $filter), 5);
		$offset_page = explode(',', $offset);
		$booking_list = model('ClipsBase')->get_booking_list($this->user_id, $offset_page[1], $offset_page[0]);
		$this->assign('title', L('label_booking'));
		$this->assign('pager', $this->pageShow($count[0]['num']));
		$this->assign('booking_list', $booking_list);
		$this->display('user_booking_list.dwt');
	}
	/**
	 * 添加缺货登记
	 */
	public function add_booking() {
		if (IS_POST) {
			$booking = array('goods_id' => I('post.id', 0), 'goods_amount' => I('post.number', 0), 'desc' => I('post.desc'), 'linkman' => I('post.linkman'), 'email' => I('post.email'), 'tel' => I('post.tel'), 'booking_id' => I('post.rec_id', 0),);
			// 查看此商品是否已经登记过
			$rec_id = model('ClipsBase')->get_booking_rec($this->user_id, $booking['goods_id']);
			if ($rec_id > 0) {
				show_message(L('booking_rec_exist'), L('back_page_up'), '', 'error');
			}
			if (model('ClipsBase')->add_booking($booking)) {
				show_message(L('booking_success'), L('back_booking_list'), url('booking_list'), 'info');
			} else {
				ECTouch::err()->show(L('booking_list_lnk'), url('booking_list'));
			}
		}
		$goods_id = I('get.id', 0);
		if ($goods_id == 0) {
			show_message(L('no_goods_id'), L('back_page_up'), '', 'error');
		}
		/* 根据规格属性获取货品规格信息 */
		$goods_attr = '';
		if ($_GET['spec'] != '') {
			$goods_attr_id = $_GET['spec'];
			$attr_list = array();
			$sql = "SELECT a.attr_name, g.attr_value " . "FROM " . $this->model->pre . "goods_attr AS g, " . $this->model->pre . "attribute AS a " . "WHERE g.attr_id = a.attr_id " . "AND g.goods_attr_id " . db_create_in($goods_attr_id);
			$row = $this->model->query($sql);
			if (empty($row)) {
				$row = array();
			}
			foreach ($row as $v) {
				$attr_list[] = $v['attr_name'] . ': ' . $v['attr_value'];
			}
			$goods_attr = join(chr(13) . chr(10), $attr_list);
		}
		$this->assign('goods_attr', $goods_attr);
		$this->assign('goods', model('ClipsBase')->get_goodsinfo($goods_id));
		$this->assign('title', L('label_booking'));
		$this->display('user_add_booking.dwt');
	}
	/**
	 * 删除缺货登记
	 */
	public function del_booking() {
		$id = I('get.rec_id', 0);
		if ($id == 0 || $this->user_id == 0) {
			$this->redirect(url('booking_list'));
		}
		$result = model('ClipsBase')->delete_booking($id, $this->user_id);
		if ($result) {
			$this->redirect(url('booking_list'));
		}
	}
	/**
	 * 收藏商品列表
	 */
	public function collection_list() {
		// 分页
		$count = $this->model->table('collect_goods')->where('user_id = ' . $this->user_id)->order('add_time desc')->count();
		$filter['page'] = '{page}';
		$offset = $this->pageLimit(url('collection_list', $filter), 5);
		$offset_page = explode(',', $offset);
		$collection_list = model('ClipsBase')->get_collection_goods($this->user_id, $offset_page[1], $offset_page[0]);
		$this->assign('title', L('label_collection'));
		$this->assign('pager', $this->pageShow($count));
		$this->assign('collection_list', $collection_list);
		$this->display('user_collection_list.dwt');
	}
	/**
	 * 添加收藏商品
	 */
	public function add_collection() {
		$result = array('error' => 0, 'message' => '');
		$goods_id = intval($_GET['id']);
		if (!isset($this->user_id) || $this->user_id == 0) {
			$result['error'] = 2;
			$result['message'] = L('login_please');
			die(json_encode($result));
		} else {
			// 检查是否已经存在于用户的收藏夹
			$where['user_id'] = $this->user_id;
			$where['goods_id'] = $goods_id;
			$rs = $this->model->table('collect_goods')->where($where)->count();
			if ($rs > 0) {
				$rs = $this->model->table('collect_goods')->where($where)->delete();
				if (!$rs) {
					$result['error'] = 1;
					$result['message'] = M()->errorMsg();
					die(json_encode($result));
				} else {
					$result['error'] = 0;
					$result['message'] = L('collect_success');
					die(json_encode($result));
				}
			} else {
				$data['user_id'] = $this->user_id;
				$data['goods_id'] = $goods_id;
				$data['add_time'] = gmtime();
				if ($this->model->table('collect_goods')->data($data)->insert() === false) {
					$result['error'] = 1;
					$result['message'] = M()->errorMsg();
					die(json_encode($result));
				} else {
					$result['error'] = 0;
					$result['message'] = L('collect_success');
					die(json_encode($result));
				}
			}
		}
	}
	/**
	 * 删除收藏商品
	 */
	public function delete_collection() {
		// ajax请求
		if (IS_AJAX && IS_GET) {
			$rs = 0;
			$rec_id = I('get.rec_id', 0);
			if ($rec_id > 0) {
				$where['user_id'] = $this->user_id;
				$where['rec_id'] = $rec_id;
				$this->model->table('collect_goods')->where($where)->delete();
				$rs = 1;
			}
			echo json_encode(array('status' => $rs));
		} elseif (!IS_AJAX && IS_GET) {
			$rec_id = I('get.rec_id', 0);
			if ($rec_id > 0) {
				$where['user_id'] = $this->user_id;
				$where['rec_id'] = $rec_id;
				$this->model->table('collect_goods')->where($where)->delete();
			}
			$this->redirect(url('collection_list'));
		} else {
			echo json_encode(array('status' => 0));
		}
	}
	/**
	 * 添加关注
	 */
	public function add_attention() {
		$rec_id = I('get.rec_id', 0);
		if ($rec_id) {
			$this->model->table('collect_goods')->data('is_attention = 1')->where('rec_id = ' . $rec_id . ' and user_id = ' . $this->user_id)->update();
		}
		$this->redirect(url('collection_list'));
	}
	/**
	 * 取消关注
	 */
	public function del_attention() {
		$rec_id = I('get.rec_id', 0);
		if ($rec_id) {
			$this->model->table('collect_goods')->data('is_attention = 0')->where('rec_id = ' . $rec_id . ' and user_id = ' . $this->user_id)->update();
		}
		$this->redirect(url('collection_list'));
	}
	/**
	 * 评论列表
	 */
	public function comment_list() {
		// 分页
		$count = $this->model->table('comment')->where('parent_id = 0 and user_id = ' . $this->user_id)->count();
		$filter['page'] = '{page}';
		$offset = $this->pageLimit(url('comment_list', $filter), 5);
		$offset_page = explode(',', $offset);
		$comment_list = model('ClipsBase')->get_comment_list($this->user_id, $offset_page[1], $offset_page[0]);
		$this->assign('title', L('label_comment'));
		$this->assign('pager', $this->pageShow($count));
		$this->assign('comment_list', $comment_list);
		$this->display('user_comment_list.dwt');
	}
	/**
	 * 删除评论
	 */
	public function delete_comment() {
		// ajax请求
		if (IS_AJAX && IS_GET) {
			$rs = 0;
			$id = I('get.id', 0);
			if ($id > 0) {
				$where['user_id'] = $this->user_id;
				$where['comment_id'] = $id;
				$this->model->table('comment')->where($where)->delete();
				$rs = 1;
			}
			echo json_encode(array('status' => $rs));
		} elseif (IS_GET && !IS_AJAX) {
			$id = I('get.id', 0);
			if ($id > 0) {
				$where['user_id'] = $this->user_id;
				$where['comment_id'] = $id;
				$this->model->table('comment')->where($where)->delete();
			}
			$this->redirect(url('comment_list'));
		} else {
			echo json_encode(array('status' => 0));
		}
	}
	/**
	 * 登录
	 */
	public function login() {
		// 登录处理
		if (IS_POST) {
			$username = I('post.username');
			$password = I('post.password');
			$this->back_act = urldecode(I('post.back_act'));
			$captcha = intval(C('captcha'));
			if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
				if (empty($_POST['captcha'])) {
					show_message(L('invalid_captcha'), L('relogin_lnk'), url('login', array('referer' => urlencode($this->back_act))), 'error');
				}
				// 检查验证码
				if ($_SESSION['ectouch_verify'] !== strtoupper($_POST['captcha'])) {
					show_message(L('invalid_captcha'), L('relogin_lnk'), url('login', array('referer' => urlencode($this->back_act))), 'error');
				}
			}
			// 用户名是邮箱格式
			if (is_email($username)) {
				$where['email'] = $username;
				$username_try = $this->model->table('users')->field('user_name')->where($where)->getOne();
				$username = $username_try ? $username_try : $username;
			}
			// 用户名是手机格式
			if (is_mobile($username)) {
				$where['mobile_phone'] = $username;
				$username_try = $this->model->table('users')->field('user_name')->where($where)->getOne();
				$username = $username_try ? $username_try : $username;
			}
			if (self::$user->login($username, $password, isset($_POST['remember']))) {
				model('Users')->update_user_info();
				model('Users')->recalculate_price();
				$jump_url = empty($this->back_act) ? url('index') : $this->back_act;
				$this->redirect($jump_url);
			} else {
				$_SESSION['login_fail']++;
				show_message(L('login_failure'), L('relogin_lnk'), url('login', array('referer' => urlencode($this->back_act))), 'error');
			}
			exit();
		}
		// 登录页面显示
		if (isset($_GET['referer']) && !empty($_GET['referer'])) {
			$this->back_act = $_GET['referer'];
		}
		if (empty($this->back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
			$this->back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'c=user') ? url('index/index') : $GLOBALS['_SERVER']['HTTP_REFERER'];
			$this->back_act = urlencode($this->back_act);
		}
		// 验证码相关设置
		$captcha = intval(C('captcha'));
		if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
			$this->assign('enabled_captcha', 1);
			$this->assign('rand', mt_rand());
		}
		$this->assign('step', I('get.step'));
		$this->assign('anonymous_buy', C('anonymous_buy'));
		$this->assign('title', L('login'));
		$this->assign('back_act', $this->back_act);
		$this->display('user_login.dwt');
	}
	/**
	 * 注册
	 */
	public function register() {
		// 注册处理
		if (IS_POST) {
			$enabled_sms = isset($_POST['enabled_sms']) ? intval($_POST['enabled_sms']) : 0;
			$this->back_act = isset($_POST['back_act']) ? in($_POST['back_act']) : '';
			// 邮箱注册处理
			if (0 == $enabled_sms) {
				// 数据处理
				$username = isset($_POST['username']) ? in($_POST['username']) : '';
				$email = isset($_POST['email']) ? in($_POST['email']) : '';
				$password = isset($_POST['password']) ? in($_POST['password']) : '';
				$recode = isset($_POST['recode']) ? in($_POST['recode']) : '';
				$other = array();
				$other['tname'] = isset($_POST['tname']) ? $_POST['tname'] : '';
				if (model('ClipsBase')->check_is_re_code($recode)) {
					$other['parent_id'] = decodeId($recode);
				} else {
					show_message("邀请口令无效", L('sign_up'), url('register'), 'error');
				}
				if (preg_match("/^1[34578]{1}\d{9}$/", $username)) {
					$other['mobile_phone'] = $username;
				}
				$sel_question = isset($_POST['sel_question']) ? in($_POST['sel_question']) : '';
				$passwd_answer = isset($_POST['passwd_answer']) ? in($_POST['passwd_answer']) : '';
				// 验证码检查
				if (intval(C('captcha')) & CAPTCHA_REGISTER) {
					if (empty($_POST['captcha'])) {
						show_message(L('invalid_captcha'), L('sign_up'), url('register'), 'error');
					}
					// 检查验证码
					if ($_SESSION['ectouch_verify'] !== strtoupper($_POST['captcha'])) {
						show_message(L('invalid_captcha'), L('sign_up'), url('register'), 'error');
					}
				}
				if (empty($_POST['agreement'])) {
					show_message(L('passport_js.agreement'));
				}
				if (strlen($username) < 3) {
					show_message(L('passport_js.username_shorter'));
				}
				if (strlen($username) > 15) {
					show_message(L('passport_js.username_longer'));
				}
				if (strlen($password) < 6) {
					show_message(L('passport_js.password_shorter'));
				}
				if (strpos($password, ' ') > 0) {
					show_message(L('passwd_balnk'));
				}
			} // 手机号注册处理
			elseif (1 == $enabled_sms) {
				$username = isset($_POST['mobile']) ? in($_POST['mobile']) : '';
				$password = isset($_POST['mobile_code']) ? in($_POST['mobile_code']) : '';
				$sms_code = isset($_POST['sms_code']) ? in($_POST['sms_code']) : '';
				$other['mobile_phone'] = $username;
				if (empty($username)) {
					show_message(L('msg_mobile_blank'), L('register_back'), url('register'), 'error');
				}
				if ($sms_code != $_SESSION['sms_code']) {
					show_message(L('sms_code_error'), L('register_back'), url('register'), 'error');
				}
				if ($password != $_SESSION['sms_mobile_code']) {
					show_message(L('mobile_code_error'), L('register_back'), url('register'), 'error');
				}
				// 验证手机号重复
				$where['mobile_phone'] = $username;
				$user_id = $this->model->table('users')->field('user_id')->where($where)->getOne();
				if ($user_id) {
					show_message(L('msg_mobile_exists'), L('register_back'), url('register'), 'error');
				}
				// 设置一个默认的邮箱
				$email = $username . '@qq.com';
			} else {
				ECTouch::err()->show(L('sign_up'), url('register'));
			}
			// 判断是否为分销商推荐用户
			/*
			
			         if (session('sale_id')){
			
			             $other['parent_id'] = session('sale_id');
			
			         }
			
			*/
			if (model('Users')->register($username, $password, $email, $other) !== false) {
				// 判断是否为分销商推荐用户
				if (session('sale_id')) {
					session('sale_id', null);
				}
				/*把新注册用户的扩展信息插入数据库*/
				$sql = 'SELECT id,is_need,reg_field_name FROM ' . M()->pre . 'reg_fields' . ' WHERE  display = 1 ORDER BY dis_order, id'; //读出所有自定义扩展字段的id
				$fields_arr = M()->query($sql);
				$extend_field_str = ''; //生成扩展字段的内容字符串
				foreach ($fields_arr AS $val) {
					$extend_field_index = 'extend_field' . $val['id'];
					if (!empty($_POST[$extend_field_index])) {
						$temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];
						$extend_field_str.= " ('" . $_SESSION['user_id'] . "', '" . $val['id'] . "', '" . $temp_field_content . "'),";
						$ky_arr[$val['id']] = $temp_field_content;
					}
				}
				$extend_field_str = substr($extend_field_str, 0, -1);
				if ($extend_field_str) //插入注册扩展数据
				{
					$sql = 'INSERT INTO ' . M()->pre . 'reg_extend_info' . ' (`user_id`, `reg_field_id`, `content`) VALUES' . $extend_field_str;
					M()->query($sql);
				}
				/* 写入密码提示问题和答案 */
				if (!empty($passwd_answer) && !empty($sel_question)) {
					$sql = 'UPDATE ' . M()->pre . 'users' . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . $_SESSION['user_id'] . "'";
					M()->query($sql);
				}
				/* 把注册信息同时写入到users表里 */
				/*
				
				if($ky_arr){
				
					$nicheng=$ky_arr[1];
				
					$mobile_phone=$ky_arr[5];
				
					$tname=$ky_arr[7];
				
					$sql = 'UPDATE ' . M()->pre . 'users' . " SET `nicheng`='$nicheng', `mobile_phone`='$mobile_phone', `tname`='$tname'  WHERE `user_id`='" . $_SESSION['user_id'] . "'";
				
				                M()->query($sql);
				
				            }
				
				*/
				// 判断是否需要自动发送注册邮件
				if (C('member_email_validate') && C('send_verify_email')) {
					model('Users')->send_regiter_hash($_SESSION['user_id']);
				}
				$ucdata = empty(self::$user->ucdata) ? "" : self::$user->ucdata;
				show_message(sprintf(L('register_success'), $username . $ucdata), array(L('back_up_page'), L('profile_lnk')), array($this->back_act, url('index')), 'info');
			} else {
				ECTouch::err()->show(L('sign_up'), url('register'));
			}
			exit();
		}
		/* 取出注册扩展字段 */
		$sql = 'SELECT * FROM ' . M()->pre . 'reg_fields' . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';
		$extend_info_list = M()->query($sql);
		foreach ($extend_info_list as $key => $val) {
			if ($val['id'] >= 100) {
				unset($extend_info_list[$key]);
			}
		}
		$this->assign('extend_info_list', $extend_info_list);
		// 密码提示问题
		$this->assign('password_question', L('passwd_questions'));
		// 注册页面显示
		if (empty($this->back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
			$this->back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'c=user') ? url('index/index') : $GLOBALS['_SERVER']['HTTP_REFERER'];
		}
		// 验证码相关设置
		if (intval(C('captcha')) & CAPTCHA_REGISTER) {
			$this->assign('enabled_captcha', 1);
			$this->assign('rand', mt_rand());
		}
		// 短信开启
		if (intval(C('sms_signin')) > 0) {
			$this->assign('enabled_sms_signin', C('sms_signin'));
			// 随机code
			$_SESSION['sms_code'] = $sms_code = md5(mt_rand(1000, 9999));
			$this->assign('sms_code', $sms_code);
		}
		$this->assign('title', L('register'));
		$this->assign('back_act', $this->back_act);
		/* 是否关闭注册 */
		$this->assign('shop_reg_closed', C('shop_reg_closed'));
		$this->display('user_register.dwt');
	}
	/**
	 * 邮件验证
	 */
	public function validate_email() {
		$hash = I('get.hash');
		if ($hash) {
			$id = model('Users')->register_hash('decode', $hash);
			if ($id > 0) {
				$this->model->table('users')->data('is_validated = 1')->where('user_id = ' . $id)->update();
				$row = $this->model->table('users')->field('user_name, email')->where('user_id = ' . $id)->find();
				show_message(sprintf(L('validate_ok'), $row['user_name'], $row['email']), L('profile_lnk'), url('index'));
			}
		}
		show_message(L('validate_fail'));
	}
	/**
	 * 第三方登录
	 */
	public function third_login() {
		$type = I('get.type');
		$file = ROOT_PATH . 'plugins/connect/' . $type . '.php';
		if (file_exists($file)) {
			include_once ($file);
		} else {
			show_message(L('process_false'), L('relogin_lnk'), url('login', array('referer' => urlencode($this->back_act))), 'error');
		}
		$url = __URL__ . '/index.php?m=default&c=user&a=third_login&type=' . $type;
		$info = model('ClipsBase')->get_third_user_info($type);
		// 判断是否安装
		if (!$info) {
			show_message(L('no_register_auth'), L('relogin_lnk'), url('login', array('referer' => urlencode($this->back_act))), 'error');
		}
		$obj = new $type($info);
		if ($_GET['code'] && $_GET['code'] != '') {
			// 授权成功 返回登录
			if ($rs = $obj->call_back($info, $url, $_GET['code'], $type)) {
				$jump_url = empty($this->back_act) ? url('index') : $this->back_act;
				if (is_array($rs)) {
					$jump_url = $rs['url'];
				}
				$this->redirect($jump_url);
			} else {
				show_message(L('process_false'), L('relogin_lnk'), url('login', array('referer' => urlencode($this->back_act))), 'error');
			}
		} else {
			// 开始授权登录
			$url = $obj->act_login($info, $url);
			ecs_header("Location: " . $url . "\n");
			exit();
		}
	}
	/**
	 * 手机找回密码
	 */
	public function get_password_phone() {
		// 短信开启
		if (intval(C('sms_signin')) > 0) {
			// 手机找回密码处理
			if (IS_POST) {
				$mobile = isset($_POST['mobile']) ? in($_POST['mobile']) : '';
				$mobile_code = isset($_POST['mobile_code']) ? in($_POST['mobile_code']) : '';
				$sms_code = isset($_POST['sms_code']) ? in($_POST['sms_code']) : '';
				if ($sms_code != $_SESSION['sms_code']) {
					show_message(L('sms_code_error'), L('back_page_up'), url('get_password_phone'), 'error');
				}
				if ($mobile_code != $_SESSION['sms_mobile_code']) {
					show_message(L('mobile_code_error'), L('back_page_up'), url('get_password_phone'), 'error');
				}
				$where['mobile_phone'] = $mobile;
				$user_id = $this->model->table('users')->field('user_id')->where($where)->getOne();
				$this->assign('uid', $user_id);
				$this->assign('mobile', base64_encode($mobile));
				$this->display('user_reset_password.dwt');
				exit();
			}
			// 随机code
			$_SESSION['sms_code'] = $sms_code = md5(mt_rand(1000, 9999));
			$this->assign('title', L('get_password'));
			$this->assign('enabled_sms_signin', C('sms_signin'));
			$this->assign('sms_code', $sms_code);
			$this->display('user_get_password.dwt');
		} else {
			$this->redirect(url('get_password_email'));
		}
	}
	/**
	 * 邮件找回密码
	 */
	public function get_password_email() {
		if (isset($_GET['code']) && isset($_GET['uid'])) { // 从邮件处获得的act
			$code = in($_GET['code']);
			$uid = intval($_GET['uid']);
			// 判断链接的合法性
			$user_info = self::$user->get_profile_by_id($uid);
			if (empty($user_info) || ($user_info && md5($user_info['user_id'] . C('hash_code') . $user_info['reg_time']) != $code)) {
				show_message(L('parm_error'), L('back_home_lnk'), url('index/index'), 'info');
			}
			$this->assign('uid', $uid);
			$this->assign('code', $code);
			$this->assign('title', L('reset_password'));
			$this->display('user_reset_password.dwt');
		} else {
			// 验证码相关设置
			$captcha = intval(C('captcha'));
			if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
				$this->assign('enabled_captcha', 1);
				$this->assign('rand', mt_rand());
			}
			// 短信开启
			if (intval(C('sms_signin')) > 0) {
				$this->assign('enabled_sms_signin', C('sms_signin'));
			}
			$this->assign('title', L('get_password'));
			$this->display('user_get_password.dwt');
		}
	}
	/**
	 * 发送密码修改确认邮件
	 */
	public function send_pwd_email() {
		$captcha = intval(C('captcha'));
		if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
			if (empty($_POST['captcha'])) {
				show_message(L('invalid_captcha'), L('back_page_up'), url('get_password_email'), 'error');
			}
			// 检查验证码
			if ($_SESSION['ectouch_verify'] !== strtoupper($_POST['captcha'])) {
				show_message(L('invalid_captcha'), L('back_page_up'), url('get_password_email'), 'error');
			}
		}
		// 初始化会员用户名和邮件地址
		$user_name = !empty($_POST['user_name']) ? in($_POST['user_name']) : '';
		$email = !empty($_POST['email']) ? in($_POST['email']) : '';
		// 用户信息
		$user_info = self::$user->get_user_info($user_name);
		if ($user_info && $user_info['email'] == $email) {
			// 生成code
			$code = md5($user_info['user_id'] . C('hash_code') . $user_info['reg_time']);
			// 发送邮件的函数
			if (send_pwd_email($user_info['user_id'], $user_name, $email, $code)) {
				show_message(L('send_success') . $email, L('relogin_lnk'), url('login'), 'info');
			} else {
				// 发送邮件出错
				show_message(L('fail_send_password'), L('back_page_up'), url('get_password_email'), 'info');
			}
		} else {
			// 用户名与邮件地址不匹配
			show_message(L('username_no_email'), L('back_page_up'), url('get_password_email'), 'info');
		}
	}
	/**
	 * 安全问题找回密码
	 */
	public function get_password_question() {
		if (IS_POST) {
			$user_name = isset($_POST['user_name']) ? in($_POST['user_name']) : '';
			$passwd_answer = isset($_POST['passwd_answer']) ? in($_POST['passwd_answer']) : '';
			// 验证码检查
			$captcha = intval(C('captcha'));
			if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
				if (empty($_POST['captcha'])) {
					show_message(L('invalid_captcha'), L('back_retry_answer'), url('get_password_question'), 'error');
				}
				// 检查验证码
				if ($_SESSION['ectouch_verify'] !== strtoupper($_POST['captcha'])) {
					show_message(L('invalid_captcha'), L('back_retry_answer'), url('get_password_question'), 'error');
				}
			}
			if (empty($_POST['user_name'])) {
				show_message(L('no_passwd_question'), L('back_home_lnk'), url('index/index'), 'info');
			}
			// 取出会员密码问题和答案
			$where['user_name'] = $user_name;
			$user_question_arr = $this->model->table('users')->field('user_id, user_name, passwd_question, passwd_answer')->where($where)->find();
			// 如果没有设置密码问题，给出错误提示
			if (empty($user_question_arr['passwd_answer'])) {
				show_message(L('no_passwd_question'), L('back_retry_answer'), url('get_password_question'), 'info');
			}
			// 问题答案验证
			if (empty($_POST['passwd_answer']) || in($_POST['passwd_answer']) != $user_question_arr['passwd_answer']) {
				show_message(L('wrong_passwd_answer'), L('back_retry_answer'), url('get_password_question'), 'info');
			}
			$this->assign('uid', $user_question_arr['user_id']);
			$this->assign('question', base64_encode($user_question_arr['passwd_question']));
			$this->display('user_reset_password.dwt');
			exit();
		}
		// 验证码相关设置
		$captcha = intval(C('captcha'));
		if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
			$this->assign('enabled_captcha', 1);
			$this->assign('rand', mt_rand());
		}
		// 短信开启
		if (intval(C('sms_signin')) > 0) {
			$this->assign('enabled_sms_signin', C('sms_signin'));
		}
		$this->assign('title', L('get_password'));
		$this->assign('password_question', L('passwd_questions'));
		$this->display('user_get_password.dwt');
	}
	/**
	 * 修改密码
	 */
	public function edit_password() {
		// 修改密码处理
		if (IS_POST) {
			$old_password = isset($_POST['old_password']) ? in($_POST['old_password']) : null;
			$new_password = isset($_POST['new_password']) ? in($_POST['new_password']) : '';
			$user_id = isset($_POST['uid']) ? intval($_POST['uid']) : $this->user_id;
			$code = isset($_POST['code']) ? in($_POST['code']) : ''; // 邮件code
			$mobile = isset($_POST['mobile']) ? base64_decode(in($_POST['mobile'])) : ''; // 手机号
			$question = isset($_POST['question']) ? base64_decode(in($_POST['question'])) : ''; // 问题
			if (strlen($new_password) < 6) {
				show_message(L('passport_js.password_shorter'));
			}
			$user_info = self::$user->get_profile_by_id($user_id); // 论坛记录
			// 短信找回，邮件找回，问题找回，登录修改密码
			if ((!empty($mobile) && $user_info['mobile'] == $mobile) || ($user_info && (!empty($code) && md5($user_info['user_id'] . C('hash_code') . $user_info['reg_time']) == $code)) || (!empty($question) && $user_info['passwd_question'] == $question) || ($_SESSION['user_id'] > 0 && $_SESSION['user_id'] == $user_id && self::$user->check_user($_SESSION['user_name'], $old_password)) || ($_SESSION['user_id'] > 0 && $_SESSION['user_id'] == $user_id && $_SESSION['openid'] != null)) {
				if (self::$user->edit_user(array('username' => ((empty($code) && empty($mobile) && empty($question)) ? $_SESSION['user_name'] : $user_info['user_name']), 'old_password' => $old_password, 'password' => $new_password), empty($code) ? 0 : 1)) {
					$data['ec_salt'] = 0;
					$where['user_id'] = $user_id;
					$this->model->table('users')->data($data)->where($where)->update();
					self::$user->logout();
					show_message(L('edit_password_success'), L('relogin_lnk'), url('login'), 'info');
				} else {
					show_message(L('edit_password_failure'), L('back_page_up'), '', 'info');
				}
			} else {
				show_message(L('edit_password_failure'), L('back_page_up'), '', 'info');
			}
		}
		// 显示修改密码页面
		if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
			$this->assign('title', L('edit_password'));
			// 判断登录方式
			if ($_SESSION['openid'] != null) {
				$this->display('user_edit_password2.dwt');
			} elseif (model('Users')->is_third_user($_SESSION['user_id'])) {
				$this->assign('is_third', 1);
				$this->display('user_edit_password.dwt');
			} else {
				$this->display('user_edit_password.dwt');
			}
		} else {
			$this->redirect(url('login', array('referer' => urlencode(url($this->action)))));
		}
	}
	/**
	 * 退出
	 */
	public function logout() {
		if ((!isset($this->back_act) || empty($this->back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
			$this->back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'c=user') ? url('index') : $GLOBALS['_SERVER']['HTTP_REFERER'];
		} else {
			$this->back_act = url('login');
		}
		self::$user->logout();
		$ucdata = empty(self::$user->ucdata) ? "" : self::$user->ucdata;
		show_message(L('logout') . $ucdata, array(L('back_up_page'), L('back_home_lnk')), array($this->back_act, url('index/index')), 'info');
	}
	/**
	 * 清空浏览历史
	 */
	public function clear_history() {
		// ajax请求
		if (IS_AJAX && IS_AJAX) {
			setcookie('ECS[history]', '', 1);
			echo json_encode(array('status' => 1));
		} else {
			echo json_encode(array('status' => 0));
		}
	}
	/**
	 * 未登录验证
	 */
	private function check_login() {
		// 不需要登录的操作或自己验证是否登录（如ajax处理）的方法
		$without = array('login', 'register', 'get_password_phone', 'get_password_email', 'get_password_question', 'pwd_question_name', 'send_pwd_email', 'edit_password', 'check_answer', 'logout', 'clear_histroy', 'add_collection', 'third_login');
		// 未登录处理
		if (empty($_SESSION['user_id']) && !in_array($this->action, $without)) {
			$url = __HOST__ . $_SERVER['REQUEST_URI'];
			$this->redirect(url('login', array('referer' => urlencode($url))));
			exit();
		}
		// 已经登录，不能访问的方法
		$deny = array('login', 'register');
		if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0 && in_array($this->action, $deny)) {
			$this->redirect(url('index/index'));
			exit();
		}
	}
	/**
	 * 更新商品销量
	 */
	private function update_touch_goods($order) {
		$sql = 'select pay_status from ' . $this->model->pre . 'order_info where  order_id = "' . $order . '"';
		$pay_status = $this->model->query($sql);
		$pay_status = $pay_status[0];
		if ($pay_status == 2) {
			/* 统计时间段 */
			$period = C('top10_time');
			// 近一个月（30天）
			if ($period == 1) { // 一年
				$ext = " AND o.add_time > '" . local_strtotime('-1 years') . "'";
			} elseif ($period == 2) { // 半年
				$ext = " AND o.add_time > '" . local_strtotime('-6 months') . "'";
			} elseif ($period == 3) { // 三个月
				$ext = " AND o.add_time > '" . local_strtotime('-3 months') . "'";
			} elseif ($period == 4) { // 一个月
				$ext = " AND o.add_time > '" . local_strtotime('-1 months') . "'";
			} else {
				$ext = '';
			}
			$sql = 'select goods_id from ' . $this->model->pre . 'order_info where  order_id = "' . $order . '"';
			$arrGoodsid = $this->model->query($sql);
			$sql = 'select extension_code from ' . $this->model->pre . 'order_info where  order_id = "' . $order . '"';
			$extension_code = $this->model->query($sql);
			if ($extension_code == '') {
				foreach ($arrGoodsid as $key => $val) {
					/* 查询该商品销量 */
					$sql = 'SELECT IFNULL(SUM(g.goods_number), 0) ' . 'as count FROM ' . $this->pre . 'order_info AS o, ' . $this->pre . 'order_goods AS g ' . "WHERE o.order_id = g.order_id " . "  AND g.goods_id = '" . $val['goods_id'] . "' AND o.pay_status = '2' " . $ext;
					$res = $this->model->query($sql);
					$sales_count = $res[0]['count'];
					$nCount = $this->query('select COUNT(*) from ' . $this->model->pre . 'touch_goods where  goods_id = "' . $val['goods_id'] . '"');
					if ($nCount[0]['COUNT(*)'] == 0) {
						$this->model->query("INSERT INTO " . $this->model->pre . "touch_goods (`goods_id` ,`sales_volume` ) VALUES ( '" . $val['goods_id'] . "' , '0')");
					}
					$sql = 'update ' . $this->model->pre . 'touch_goods AS a set a.sales_volume = ' . $sales_count . " WHERE goods_id=" . $val['goods_id'];
					$this->model->query($sql);
				}
			}
		}
	}
	//代理页面展示
	public function apply_index() {
		$user_rank_id = model('ClipsBase')->get_user_rank($this->user_id);
		if ($rank = model('ClipsBase')->get_rank_info2($user_rank_id)) {
			$this->assign('rank_name', $rank['rank_name']);
			$this->assign('is_special', $rank['is_special']);
		}
		$this->assign('heyue_url', model('ClipsBase')->get_daili_info($this->user_id, 3));
		$this->display('user_apply_index.dwt');
	}
	/**
	 *  申请成为代理商
	 */
	public function apply_sale() {
		$apply_status = model('ClipsBase')->get_apply_status($this->user_id);
		//dump($apply_status);
		if ($apply_status['pay_status'] == '1' && $apply_status['is_apply'] == '0') {
			show_message("您的合伙人申请正在审核中，请耐心等待！", L('back_page_up'), '', 'info');
		}
		if ($apply_status['pay_status'] == '0') {
			$msg_info = "您于" . local_date(C('time_format'), (intval($apply_status['time']) - 28800)) . "提交过合伙人申请，是否继续申请？";
			$links = array("返回上一页", "<font color=red>继续申请并删除此前的申请</font>");
			$hrefs = array('javascript:history.back()', 'index.php?m=default&c=user&a=apply_form&del=1');
			show_message($msg_info, $links, $hrefs, 'info');
		}
		$user_rank = model('ClipsBase')->get_user_rank($this->user_id);
		if ($user_rank == '136' || $user_rank == '137' || $user_rank == '138' || $user_rank == '144') {
			$content = L('is_daili');
			show_message($content, L('back_page_up'), '', 'info');
		} else {
			session('daili', $_GET['daili']);
			$this->display('user_apply_sale.dwt');
		}
	}
	//填写代理商表单
	public function apply_form() {
		if ($_GET['del']) {
			$res = model('ClipsBase')->get_last_apply($this->user_id);
			if (!empty($res)) {
				model('ClipsBase')->del_last_apply($res['id']);
			}
		}
		$apply_status = model('ClipsBase')->get_apply_status($this->user_id);
		if ($apply_status['pay_status'] == '1' && $apply_status['is_apply'] == '0') {
			show_message("您的合伙人申请正在审核中，请耐心等待！", L('back_page_up'), '', 'info');
		}
		if ($apply_status['pay_status'] == '0') {
			$msg_info = "您于" . local_date(C('time_format'), (intval($apply_status['time']) - 28800)) . "提交过合伙人申请，是否继续申请？";
			$links = array("返回上一页", "<font color=red>继续申请并删除此前的申请</font>");
			$hrefs = array('javascript:history.back()', 'index.php?m=default&c=user&a=apply_form&del=1');
			show_message($msg_info, $links, $hrefs, 'info');
		}
		$user_rank = model('ClipsBase')->get_user_rank($this->user_id);
		if ($user_rank == '136' || $user_rank == '137' || $user_rank == '138' || $user_rank == '144') {
			$content = L('is_daili');
			show_message($content, L('back_page_up'), '', 'info');
		} else {
			if ($_SESSION['daili']) {
				$this->assign('daili', $_SESSION['daili']);
			}
			// 读出所有扩展字段的id
			$where['code'] = 'premium';
			$premium = $this->model->table('shop_config')->field('value')->where($where)->getOne();
			$this->assign('premium_value', $premium);
			$this->assign('premium', price_format($premium));
			$this->display('user_apply_form.dwt');
		}
	}
	//提交代理商表单
	public function apply_done() {
		if (IS_POST) {
			$apply_status = model('ClipsBase')->get_apply_status($this->user_id);
			if ($apply_status['pay_status'] == '1' && $apply_status['is_apply'] == '0') {
				show_message("您的合伙人申请正在审核中，请耐心等待！", L('back_page_up'), '', 'info');
			}
			if ($apply_status['pay_status'] == '0') {
				$msg_info = "您于" . local_date(C('time_format'), (intval($apply_status['time']) - 28800)) . "提交过合伙人申请，是否继续申请？";
				$links = array("返回上一页", "<font color=red>继续申请并删除此前的申请</font>");
				$hrefs = array('javascript:history.back()', 'index.php?m=default&c=user&a=apply_form&del=1');
				show_message($msg_info, $links, $hrefs, 'info');
			}
			$user_rank = model('ClipsBase')->get_user_rank($this->user_id);
			if ($user_rank == '136' || $user_rank == '137' || $user_rank == '138' || $user_rank == '144') {
				$content = L('is_daili');
				show_message($content, L('back_page_up'), '', 'info');
			} else {
				$data = $_POST;
				/*
				
				$upfile=$_FILES["sf_code_1"];
				
				$upfile2=$_FILES["sf_code_2"];  
				
				if($upfile&&$upfile2){
				
					//获取数组里面的值 
				
					$name=$upfile["name"];//上传文件的文件名 
				
					$type=$upfile["type"];//上传文件的类型  
				
					$tmp_name=$upfile["tmp_name"];//上传文件的临时存放路径 
				
					$change_name = time().'_'.$this->user_id.'_z.'.substr(strrchr($name, '.'), 1);
				
					
				
					$name2=$upfile2["name"];//上传文件的文件名 
				
					$type2=$upfile2["type"];//上传文件的类型  
				
					$tmp_name2=$upfile2["tmp_name"];//上传文件的临时存放路径 
				
					$change_name2 = time().'_'.$this->user_id.'_f.'.substr(strrchr($name2, '.'), 1);
				
					
				
					//判断是否为图片 
				
					switch ($type){ 
				
						case 'image/pjpeg':$okType=true; 
				
						break; 
				
						case 'image/jpeg':$okType=true; 
				
						break; 
				
						case 'image/gif':$okType=true; 
				
						break; 
				
						case 'image/png':$okType=true; 
				
						break; 
				
					}
				
					switch ($type2){ 
				
						case 'image/pjpeg':$okType2=true; 
				
						break; 
				
						case 'image/jpeg':$okType2=true; 
				
						break; 
				
						case 'image/gif':$okType2=true; 
				
						break; 
				
						case 'image/png':$okType2=true; 
				
						break; 
				
					}
				
					
				
					if($okType && $okType2){				
				
						$error=$upfile["error"];//上传后系统返回的值  
				
						$error2=$upfile2["error"];//上传后系统返回的值  
				
						//把上传的临时文件移动到up目录下面 
				
						move_uploaded_file($tmp_name,'./data/attached/sf_code/'.$change_name); 
				
						move_uploaded_file($tmp_name2,'./data/attached/sf_code/'.$change_name2);
				
						$destination="./data/attached/sf_code/".$change_name;
				
						$destination2="./data/attached/sf_code/".$change_name2;  
				
						if($error!=0 || $error2!=0 ){ 
				
							show_message('上传文件失败', L('back_page_up'), '', 'info');
				
						}
				
					}else{ 
				
						show_message('请上传jpg,gif,png等格式适合大小的图片！', L('back_page_up'), '', 'info'); 
				
					} 
				
				}
				
				*/
				$apply_order['user_id'] = $this->user_id;
				$apply_order['tname'] = $data['tname'];
				$apply_order['phone'] = $data['phone'];
				$apply_order['sf_code'] = $data['sfcode'];
				//$apply_order['daili_lev'] = $data['lev'];
				$apply_order['daili_amount'] = $data['premium'];
				//$apply_order['sf_code_1'] = $destination;
				//$apply_order['sf_code_2'] = $destination2;
				$apply_order['user_note'] = $data['user_note'];
				$apply_order['time'] = time();
				$apply_order['ktimes'] = 1;
				$apply_order['id'] = $this->model->table('apply_list')->data($apply_order)->insert();
				$apply_order['dl'] = 1;
				//logg($apply_order);
				/* 取得支付信息，生成支付代码 */
				if ($apply_order['daili_amount'] > 0) {
					$payment = model('Order')->payment_info(4);
					include_once (ROOT_PATH . 'plugins/payment/' . $payment['pay_code'] . '.php');
					$pay_obj = new $payment['pay_code']();
					$pay_online = $pay_obj->get_code($apply_order, unserialize_config($payment['pay_config']));
					$apply_order['pay_desc'] = $payment['pay_desc'];
					//dump($pay_online);
					$this->assign('pay_online', $pay_online);
				}
				$this->assign('daili_amount', price_format($data['premium'], false));
				$this->display('user_apply_done.dwt');
			}
		}
	}
	//追加入股金的界面
	public function add_stock() {
		if ($_GET['del']) {
			$res = model('ClipsBase')->get_last_apply($this->user_id);
			if (!empty($res)) {
				model('ClipsBase')->del_last_apply($res['id']);
			}
		}
		$user_info = model('ClipsBase')->get_user_default($this->user_id);
		$user_rank_id = model('ClipsBase')->get_user_rank($this->user_id);
		if ($user_info['done_stock'] <= 0 || $user_rank_id != '144') {
			show_message("您目前没有追加入股金的权限<br>请完成您的当前年度任务后申请", L('back_page_up'), '', 'info');
		}
		$this->assign('now_stock_times', $user_info['done_stock'] + 1);
		$apply_status = model('ClipsBase')->get_apply_status($this->user_id);
		if ($apply_status['pay_status'] == '1' && $apply_status['is_apply'] == '0') {
			show_message("您的追加申请正在审核中，请耐心等待！", L('back_page_up'), '', 'info');
		}
		if ($apply_status['pay_status'] == '0') {
			$msg_info = "您于" . local_date(C('time_format'), (intval($apply_status['time']) - 28800)) . "提交过追加申请，是否继续申请？";
			$links = array("返回上一页", "<font color=red>继续申请并删除此前的申请</font>");
			$hrefs = array('javascript:history.back()', 'index.php?m=default&c=user&a=add_stock&del=1');
			show_message($msg_info, $links, $hrefs, 'info');
		}
		$premium = model('ClipsBase')->get_shop_config_value('premium');
		$this->assign('premium_value', $premium);
		$this->assign('premium', price_format($premium));
		$sql = "SELECT tname,phone,sf_code FROM " . $this->model->pre . "apply_list WHERE is_apply = 1 AND user_id = '$this->user_id'";
		$apply_info = $this->model->getRow($sql);
		$this->assign('apply_info', $apply_info);
		$this->display('user_add_stock.dwt');
	}
	//提交追加入股金
	public function add_stock_done() {
		if (IS_POST) {
			$apply_status = model('ClipsBase')->get_apply_status($this->user_id);
			if ($apply_status['pay_status'] == '1' && $apply_status['is_apply'] == '0') {
				show_message("您的追加申请正在审核中，请耐心等待！", L('back_page_up'), '', 'info');
			}
			if ($apply_status['pay_status'] == '0') {
				$msg_info = "您于" . local_date(C('time_format'), (intval($apply_status['time']) - 28800)) . "提交过追加申请，是否继续申请？";
				$links = array("返回上一页", "<font color=red>继续申请并删除此前的申请</font>");
				$hrefs = array('javascript:history.back()', 'index.php?m=default&c=user&a=add_stock&del=1');
				show_message($msg_info, $links, $hrefs, 'info');
			}
			$data = $_POST;
			$apply_order['user_id'] = $this->user_id;
			$apply_order['tname'] = $data['tname'];
			$apply_order['phone'] = $data['phone'];
			$apply_order['sf_code'] = $data['sf_code'];
			//$apply_order['daili_lev'] = $data['lev'];
			$apply_order['daili_amount'] = $data['premium'];
			//$apply_order['sf_code_1'] = $destination;
			//$apply_order['sf_code_2'] = $destination2;
			$apply_order['user_note'] = $data['user_note'];
			$apply_order['time'] = time();
			$apply_order['ktimes'] = $data['now_stock_times'];
			$apply_order['id'] = $this->model->table('apply_list')->data($apply_order)->insert();
			$apply_order['dl'] = 1;
			/* 取得支付信息，生成支付代码 */
			if ($apply_order['daili_amount'] > 0) {
				$payment = model('Order')->payment_info(4);
				include_once (ROOT_PATH . 'plugins/payment/' . $payment['pay_code'] . '.php');
				$pay_obj = new $payment['pay_code']();
				$pay_online = $pay_obj->get_code($apply_order, unserialize_config($payment['pay_config']));
				$apply_order['pay_desc'] = $payment['pay_desc'];
				//dump($pay_online);
				$this->assign('pay_online', $pay_online);
			}
			$this->assign('daili_amount', price_format($data['premium'], false));
			$this->display('user_add_stock_done.dwt');
		}
	}
	//发放红包
	public function grant_bonus() {
		/*
		
		$bonus_type_id=$_GET['bid'];
		
		if(empty($bonus_type_id)){
		
			show_message("领取红包错误", L('back_page_up'), '', 'info');
		
		}
		
		*/
		$openid = session('openid');
		if (empty($openid)) {
			show_message("请在微信端领取", L('back_page_up'), '', 'info');
		}
		$uid = model('Base')->model->table('wechat_user')->field('ect_uid')->where('openid = "' . $openid . '"')->getOne();
		//检查该红包的领取记录
		$count = model('Base')->model->table('wechat_mobilebonus')->where('openid = "' . $openid . '" or uid = ' . $uid)->count();
		if ($count > 0) {
			show_message("您已领取过红包了", L('back_page_up'), '', 'info');
		} else {
			/* 生成红包序列号 */
			$num = M()->getOne("SELECT MAX(bonus_sn) FROM " . M()->pre . 'user_bonus');
			$num = $num ? floor($num / 10000) : 100000;
			$bonus_sn = ($num + 1) . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
			//发放红包
			$data['bonus_type_id'] = 2; //红包编号
			$data['bonus_sn'] = $bonus_sn;
			$data['user_id'] = $uid;
			$data['used_time'] = 0;
			$data['order_id'] = 0;
			$data['emailed'] = 0;
			$bid = model('Base')->model->table('user_bonus')->data($data)->insert();
			if ($bid) {
				//记录红包
				$data2['wechat_id'] = 2;
				$data2['uid'] = $uid;
				$data2['openid'] = $openid;
				$data2['mobile'] = $mobile;
				$data2['bonus_id'] = 2;
				$data2['bonus_id'] = $bonus_sn;
				$data2['issue_status'] = 1;
				$data2['addtime'] = time();
				model('Base')->model->table('wechat_mobilebonus')->data($data2)->insert();
				show_message("领取成功", L('back_page_up'), '', 'info');
			} else {
				show_message("领取失败", L('back_page_up'), '', 'info');
			}
		}
	}
	//银币转账
	public function transfer_coin() {
		// 获取剩余银币
		$sliv_amount = model('ClipsBase')->get_user_sliv2($this->user_id);
		if (empty($sliv_amount)) {
			$sliv_amount = 0;
		}
		if (IS_POST) {
			$tran_log = model('ClipsBase')->get_last_transfer_coin_log($this->user_id, $_POST['user_name']);
			if ($tran_log) {
				if (floatval(gmtime() - 60) < floatval($tran_log['change_time'])) {
					show_message("同一用户每分钟只能转账一次", "查看历史详情", url('account_detail3'), 'info');
				}
			}
			$user_nicheng = model('ClipsBase')->get_user_name($this->user_id);
			//获取传输来的值
			$user_name = $_POST['user_name'];
			$coin_number = $_POST['coin_number'];
			if (!filter_var($coin_number, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0))) || empty($user_name)) {
				show_message("参数错误，请重新输入", '', '', 'info');
			} else {
				if ((int)$coin_number > (int)$sliv_amount) {
					show_message("您的代金券不足，无法转账", '', '', 'info');
				} else {
					$user_info = model('ClipsBase')->get_user_tname($user_name);
					if ($user_info['user_id'] == $this->user_id) {
						show_message("不能给自己转账", '', '', 'info');
					}
					if ($user_info) {
						//给接收人加钱
						$info = "来自用户" . $user_nicheng . "的代金券转账";
						$result = model('ClipsBase')->log_account_change8($user_info['user_id'], 0, 0, 0, $coin_number, $info,19,1,$this->user_id);
						if ($result) {
							//给发送人减钱
							$info = "向用户" . $user_name . "代金券转账";
							$result2 = model('ClipsBase')->log_account_change8($this->user_id, 0, 0, 0, -$coin_number, $info,18,1,$user_info['user_id']);
							if ($result2) {
								show_message("转账成功", "查看详情", url('account_detail3'), 'info');
							} else {
								$log = "出现重大转账代金券故障，涉及流水id为" . $result;
								logg($log);
							}
						} else {
							show_message("转账失败", '', '', 'info');
						}
					} else {
						show_message("未找到该用户", '', '', 'info');
					}
				}
			}
		} else {
			$this->assign('sliv_amount', $sliv_amount);
			$this->display('user_transfer_coin.dwt');
		}
	}
	//余额转账
	public function transfer_surplus() {
		// 获取剩余余额
		$surplus_amount = model('ClipsBase')->get_user_surplus2($this->user_id);
		if (empty($surplus_amount)) {
			$surplus_amount = 0;
		}
		if (IS_POST) {
			$tran_log = model('ClipsBase')->get_last_transfer_surplus_log($this->user_id, $_POST['user_phone']);
			if ($tran_log) {
				if (floatval(gmtime() - 60) < floatval($tran_log['change_time'])) {
					show_message("同一用户每分钟只能转账一次", "查看历史详情", url('account_detail'), 'info');
				}
			}
			$user_nicheng = model('ClipsBase')->get_user_name($this->user_id);
			//获取传输来的值
			$user_phone = $_POST['user_phone'];
			$num = floatval($_POST['surplus_number']);
			$surplus_number = (float)sprintf("%.2f", substr(sprintf("%.3f", $num), 0, -1));
			if ($surplus_number <= 0 || empty($user_phone)) {
				show_message("参数错误，请重新输入", '', '', 'info');
			} else {
				if ($surplus_number > (float)$surplus_amount) {
					show_message("您的余额不足，无法转账", '', '', 'info');
				} else {
					$user_info = model('ClipsBase')->get_user_tname2($user_phone);
					if ($user_info['user_id'] == $this->user_id) {
						show_message("不能给自己转账", '', '', 'info');
					}
					if ($user_info) {						
						//给发送人减钱
						$info = "向用户" . $user_phone . "余额转账";
						$result2 = model('ClipsBase')->log_account_change8($this->user_id, -$surplus_number, 0, 0, 0, $info,8,1,$user_info['user_id']);
						if ($result2) {
							//给接收人加钱
							$info = "来自用户" . $user_nicheng . "的余额转账";
							$result = model('ClipsBase')->log_account_change8($user_info['user_id'], $surplus_number, 0, 0, 0, $info,9,1,$this->user_id,$result2);
							if ($result) {
								show_message("转账成功", "查看详情", url('account_detail'), 'info');
							} else {
								$log = "出现重大转账余额故障，涉及流水id为" . $result;
								logg($log);
							}
						} else { 							
							show_message("转账失败", '', '', 'info');
						}
						
						
					} else {
						show_message("未找到该用户", '', '', 'info');
					}
				}
			}
		} else {
			$this->assign('surplus_amount', $surplus_amount);
			$this->display('user_transfer_surplus.dwt');
		}
	}
	//检测用户名并输出姓名 
	public function select_username() {
		/* ------------------------------------------------------ */
		//-- 检查用户用户名并返回tname
		/* ------------------------------------------------------ */
		$user_phone = $_GET['user_phone'];
		$user_phone = model('ClipsBase')->get_user_tname2($user_phone);
		$result = array('error' => '', 'content' => '');
		if ($user_phone) {
			if (empty($user_phone['tname'])) {
				$result['error'] = "red";
				$result['content'] = "未核实到真实姓名，请谨慎转账";
			} else {
				$result['error'] = "blue";
				$result['content'] = "对方真实姓名：" . $user_phone['tname'];
			}
		} else {
			$result['error'] = "red";
			$result['content'] = "未找到该用户，无法转账";
		}
		die(json_encode($result));
	}
	//输出大礼包情况
	public function spree() {
		//获取数量
		$spree_number = array();
		$spree_number['no_check'] = model('ClipsBase')->get_user_spree_count($this->user_id, 1);
		$spree_number['is_check'] = model('ClipsBase')->get_user_spree_count($this->user_id, 2);
		$this->assign('spree_number', $spree_number);
		// 分页
		$filter['page'] = '{page}';
		$offset = $this->pageLimit(url('spree', $filter), 5);
		$offset_page = explode(',', $offset);
		$count = $this->model->table('user_spree')->where('user_id = ' . $this->user_id)->count();
		$spree = model('Users')->get_user_spree_list($this->user_id, $offset_page[1], $offset_page[0]);
		$this->assign('title', "我的大礼包");
		$this->assign('pager', $this->pageShow($count));
		$this->assign('spree', $spree);
		$this->display('user_spree.dwt');
	}
	//输出大礼包情况2
	public function spree2() {
		//获取数量
		$spree_number = array();
		$spree_number['no_check'] = model('ClipsBase')->get_user_spree_count($this->user_id, 1);
		$spree_number['is_check'] = model('ClipsBase')->get_user_spree_count($this->user_id, 2);
		$this->assign('spree_number', $spree_number);
		// 分页
		$filter['page'] = '{page}';
		$offset = $this->pageLimit(url('spree', $filter), 5);
		$offset_page = explode(',', $offset);
		$count = $this->model->table('user_spree')->where('user_id = ' . $this->user_id)->count();
		$spree = model('Users')->get_user_spree_list($this->user_id, $offset_page[1], $offset_page[0]);
		$this->assign('title', "我的大礼包");
		$this->assign('pager', $this->pageShow($count));
		$this->assign('spree', $spree);
		$this->display('user_spree2.dwt');
	}
	//出售礼包
	public function sell_spree() {
		if (IS_POST) {
			if (model('ClipsBase')->check_spree($_POST['spree_id'], $this->user_id)) { //检查礼包合法性
				$data['is_sell'] = 1;
				$data['sell_price'] = $_POST['sell_price'];
				$data['sell_beizhu'] = $_POST['sell_beizhu'];
				$data['send_time'] = gmtime();
				$where['id'] = $_POST['spree_id'];
				$rs = $this->model->table('user_spree')->data($data)->where($where)->update();
				if ($rs) {
					show_message("提交成功，等待系统审核", "返回", url('spree'), 'info');
				} else {
					show_message("操作失败", '', '', 'info');
				}
			}
		} else {
			$id = $_GET['id'];
			$lb_info_price = model('ClipsBase')->get_spree_price($id, $this->user_id); //获取礼包的基本信息和指导价
			if ($lb_info_price) {
				$this->assign('lb_info_price', $lb_info_price);
				$this->assign('title', "出售礼包");
				$this->display('user_sell_spree.dwt');
			} else {
				show_message("该礼包尚无指导价", '', '', 'info');
			}
		}
	}
	//取消出售礼包
	public function cacle_sell_spree() {
		if (IS_POST) {
			if (model('ClipsBase')->check_spree1($_POST['spree_id'], $this->user_id)) {
				$data['is_sell'] = 0;
				$data['sell_price'] = 0.00;
				$data['sell_beizhu'] = "";
				$where['id'] = $_POST['spree_id'];
				$rs = $this->model->table('user_spree')->data($data)->where($where)->update();
				if ($rs) {
					show_message("取消成功", "返回", url('spree'), 'info');
				} else {
					show_message("操作失败", '', '', 'info');
				}
			}
		} else {
			$id = $_GET['id'];
			$spree_info = model('ClipsBase')->check_spree1($id, $this->user_id);
			if ($spree_info) {
				$this->assign('spree_info', $spree_info);
				$this->assign('title', "取消出售礼包");
				$this->display('user_cacle_sell_spree.dwt');
			} else {
				show_message("未知错误", '', '', 'info');
			}
		}
	}
	//驳回后恢复礼包
	public function regain_sell_spree() {
		if (IS_POST) {
			if (model('ClipsBase')->check_spree3($_POST['spree_id'], $this->user_id)) {
				$data['is_sell'] = 0;
				$data['sell_price'] = 0.00;
				$data['sell_beizhu'] = "";
				$where['id'] = $_POST['spree_id'];
				$rs = $this->model->table('user_spree')->data($data)->where($where)->update();
				if ($rs) {
					show_message("操作成功，您可以再次出售该礼包", "返回", url('spree'), 'info');
				} else {
					show_message("操作失败", '', '', 'info');
				}
			}
		} else {
			$id = $_GET['id'];
			$spree_info = model('ClipsBase')->check_spree3($id, $this->user_id);
			if ($spree_info) {
				$this->assign('spree_info', $spree_info);
				$this->assign('title', "已驳回出售礼包");
				$this->display('user_regain_sell_spree.dwt');
			} else {
				show_message("未知错误", '', '', 'info');
			}
		}
	}
	//购买礼包
	public function buy_spree() {
		if (IS_POST) {
			$year = (int)$_POST['year'];
			$month = (int)$_POST['month'];
			$buy_price = floatval($_POST['buy_price']);
			if (empty($year) || empty($month) || empty($buy_price) || $month > 12 || $buy_price <= 0) {
				show_message("填写错误，请重新填写", '', '', 'info');
			} else {
				$data['user_id'] = $this->user_id;
				$data['year'] = $year;
				$data['month'] = $month;
				$data['trade_price'] = $buy_price;
				$data['trade_beizhu'] = htmlspecialchars(trim($_POST['buy_beizhu']));
				$data['send_time'] = gmtime();
				$sr_id = $this->model->table('spree_log')->data($data)->insert();
				if ($sr_id) {
					show_message("提交成功，等待审核，请保证您的余额充足", "返回", url('buy_spree_list'), 'info');
				} else {
					show_message("提交失败", '', '', 'info');
				}
			}
		} else {
			$this->display('user_buy_spree.dwt');
		}
	}
	//购买礼包列表
	public function buy_spree_list() {
		// 获取剩余余额
		$surplus_amount = model('ClipsBase')->get_user_surplus2($this->user_id);
		if (empty($surplus_amount)) {
			$surplus_amount = 0;
		}
		$this->assign('surplus_amount', $surplus_amount);
		// 分页
		$filter['page'] = '{page}';
		$offset = $this->pageLimit(url('buy_spree_list', $filter), 5);
		$offset_page = explode(',', $offset);
		$count = $this->model->table('spree_log')->where('user_id = ' . $this->user_id . ' AND trade_type = 0')->count();
		$buy_spree_list = model('Users')->get_user_buy_spree_list($this->user_id, $offset_page[1], $offset_page[0]);
		$this->assign('title', "购买大礼包记录");
		$this->assign('pager', $this->pageShow($count));
		$this->assign('buy_spree_list', $buy_spree_list);
		$this->display('user_buy_spree_list.dwt');
	}
	//取消收购礼包
	public function cancel_buy_spree() {
		$tr_id = $_GET['id'];
		if (model('ClipsBase')->check_buy_spree($tr_id, $this->user_id)) {
			$data['status'] = 2;
			$where['tr_id'] = $tr_id;
			$rs = $this->model->table('spree_log')->data($data)->where($where)->update();
			if ($rs) {
				show_message("取消成功", '', url('buy_spree_list'), 'info');
			} else {
				show_message("操作失败", '', '', 'info');
			}
		}
	}
	//出售礼包列表
	public function sell_spree_list() {
		// 分页
		$filter['page'] = '{page}';
		$offset = $this->pageLimit(url('sell_spree_list', $filter), 5);
		$offset_page = explode(',', $offset);
		$count = $this->model->table('spree_log')->where('user_id = ' . $this->user_id . ' AND trade_type = 1')->count();
		$sell_spree_list = model('Users')->get_user_sell_spree_list($this->user_id, $offset_page[1], $offset_page[0]);
		//dump($sell_spree_list);
		$this->assign('title', "出售大礼包记录");
		$this->assign('pager', $this->pageShow($count));
		$this->assign('sell_spree_list', $sell_spree_list);
		$this->display('user_sell_spree_list.dwt');
	}
	//根据年月获取礼包指导价
	public function get_zd_price() {
		$where['year'] = $_POST['year'];
		$where['month'] = $_POST['month'];
		$where['is_del'] = 0;
		$price = $this->model->table('spree_price')->field('price')->where($where)->getOne();
		$result = array("status" => 0, "info" => "");
		if ($price) {
			$result['status'] = 1;
			$result['info'] = $price;
		} else {
			$result['info'] = "该月礼包尚无指导价";
		}
		echo json_encode($result);
	}
}
