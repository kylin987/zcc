<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：SaleController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch用户中心
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class SaleController extends CommonController {

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
        //判断用户类型，不是分销用户跳转到user控制器中
		/*
        if ($info['user_rank'] != 100 && $info['user_rank'] != 101 && $this->user_id > 0){
            ecs_header("Location: ".url('user/index'));
        }
		*/
        // 如果是显示页面，对页面进行相应赋值
        assign_template();		
        $this->assign('action', $this->action);
        $this->assign('info', $info);
        
    }

    /**
     * 会员中心欢迎页
     */
    public function index() {
        // 分享二维码		
        $mobile_qr = 'data/sale/sale_qrcode_'.$this->user_id.'.png';
        if (!file_exists($mobile_qr)){
            //生成分享连接
            $shopurl = __HOST__.url('index/index',array('sale'=>$this->user_id));
            $this->assign('shopurl', $shopurl);
            $this->assign('domain', __HOST__);
            $this->assign('shopdesc', C('shop_desc'));
            
            // 生成二维码
            $mobile_url = __URL__; // 二维码内容
            $errorCorrectionLevel = 'L'; // 纠错级别：L、M、Q、H
            $matrixPointSize = 7; // 点的大小：1到10
            QRcode::png($shopurl, ROOT_PATH . $mobile_qr, $errorCorrectionLevel, $matrixPointSize, 2);
        }
        // 二维码路径赋值
        $this->assign('mobile_qr', $mobile_qr); 
        
        // 订单数量
        $count = $this->model->table('order_info')->where('parent_id = ' . $this->user_id)->count();
        $this->assign('order_count', $count);
        
        //我的粉丝数量
        $line_count = model('Sale')->get_line_count();
        $this->assign('line_count', $line_count);
        
        //分享产品
        $goods_count = model('Sale')->get_sale_goods_count();
        $this->assign('goods_count', $goods_count);
        
        
		// 用户等级
        if ($rank = model('ClipsBase')->get_rank_info()) {			
            $this->assign('rank_name', $rank['rank_name']);
        }		
        
        // 用户余额
        $surplus_amount = model('ClipsBase')->get_user_surplus($this->user_id);
        if (empty($surplus_amount)) {
            $surplus_amount = 0;
        }
        $this->assign('surplus_amount', price_format($surplus_amount, false));
        // 信息中心是否有新回复
        $sql = 'SELECT msg_id FROM ' . $this->model->pre . 'feedback WHERE parent_id IN (SELECT f.msg_id FROM ' . $this->model->pre . 'feedback f LEFT JOIN ' . $this->model->pre . 'touch_feedback t ON f.msg_id = t.msg_id WHERE f.parent_id = 0 and f.user_id = ' . $this->user_id . ' and t.msg_read = 0 ORDER BY msg_time DESC) ORDER BY msg_time DESC';
        $rs = $this->model->query($sql);
        if ($rs) {
            $this->assign('new_msg', 1);
        }
		$thumbname='./data/sale/tx/tx_'.$this->user_id.'.png';
		if(file_exists($thumbname)){
			$tx = 1;
		}
		$this->assign('user_id',$this->user_id);
        $this->assign('user_notice', C('user_notice'));
        $this->assign('title', L('user_center'));
		$this->assign('user_tx',$tx);
		//$this->assign('time', time());
        $this->display('user_sale.dwt');
    }
    /**
     * 未登录验证
     */
    private function check_login() {
        // 未登录处理
        if (empty($_SESSION['user_id'])) {
            $url = __HOST__ . $_SERVER['REQUEST_URI'];
            $this->redirect(url('user/login', array(
                'referer' => urlencode($url)
            )));
            exit();
        }
    }
    
    /**
     * 我要分享
     */
    public function to_sale(){
        //生成分享连接
        $shopurl = __HOST__.url('index/index',array('sale'=>$this->user_id));
        $this->assign('shopurl', $shopurl);
        $this->assign('domain', __HOST__);
        $this->assign('shopdesc', C('shop_desc'));
        
        // 生成二维码
        $mobile_url = __URL__; // 二维码内容
        $errorCorrectionLevel = 'L'; // 纠错级别：L、M、Q、H
        $matrixPointSize = 7; // 点的大小：1到10
        $mobile_qr = 'data/sale/sale_qrcode_'.$this->user_id.'.png';
        QRcode::png($shopurl, ROOT_PATH . $mobile_qr, $errorCorrectionLevel, $matrixPointSize, 2);
        // 二维码路径赋值
        $this->assign('mobile_qr', $mobile_qr);
        
        $this->assign('title','专属二维码');
        $this->display('user_sale_code.dwt');
    }
    
    /**
     * 我的粉丝
     */
    public function line(){
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
                for ($i = 1; $i <= $num; $i++) {
                    $count = 0;
                    if ($up_uid) {
                        $where = 'parent_id IN(' . $up_uid . ')';
                        $rs = $this->model->table('users')
                                ->field('user_id')
                                ->where($where)
                                ->select();
                        if (empty($rs)) {
                            $rs = array();
                        }
                        $up_uid = '';
                        foreach ($rs as $k => $v) {
                            $up_uid .= $up_uid ? ",'$v[user_id]'" : "'$v[user_id]'";
                            if ($i < $num) {
                                $all_uid .= ", '$v[user_id]'";
                            }
                            $count++;
                        }
                    }
                    $affdb[$i]['num'] = $count;
					//$affdb[$i]['down_uid'] = $up_uid;
                    $affdb[$i]['point'] = $share['item'][$i - 1]['level_point'];
                    $affdb[$i]['money'] = $share['item'][$i - 1]['level_money'];
					//dump($affdb);
                    
					$down_uid[$i]['uid']=$up_uid;
					
					
                }
            } else {
                // 推荐订单分成
                $sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";

                $sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
            }
			//dump($down_uid);
			$down_uid = model('Sale')->gsh_down($down_uid);
			//dump($down_uid);
            
        } else {
            // 单个商品推荐
            $this->assign('userid', $this->user_id);
            $this->assign('goodsid', $goodsid);

            $types = array(
                1,
                2,
                3,
                4,
                5
            );
            $this->assign('types', $types);

            $goods = model('Goods')->get_goods_info($goodsid);
            $goods['goods_img'] = get_image_path(0, $goods['goods_img']);
            $goods['goods_thumb'] = get_image_path(0, $goods['goods_thumb']);
            $goods['shop_price'] = price_format($goods['shop_price']);

            $this->assign('goods', $goods);
        }
		
		//dump($affdb);
		/*
		$zj[1]=0.055*0.38*100;
		$zj[2]=0.055*0.28*100;
		$zj[3]=0.055*0.2*100;
		
		$user_rank = model('ClipsBase')->get_user_rank($this->user_id);
		if ($rank = model('ClipsBase')->get_rank_info2($user_rank)) {
            $this->assign('rank_name', $rank['rank_name']);
			$this->assign('is_special', $rank['is_special']);
        }
		switch ($user_rank){
			case '128':
			$affdb[1]['zhuijia']=sprintf("%.2f",($zj[1]*5.5-$zj[1]))."%";			
			$affdb[2]['zhuijia']=sprintf("%.2f",($zj[2]*5.5-$zj[2]))."%";
			$affdb[3]['zhuijia']=sprintf("%.2f",($zj[3]*5.5-$zj[3]))."%";			
			break;
			case '129':
			$affdb[1]['zhuijia']=sprintf("%.2f",($zj[1]*7-$zj[1]))."%";			
			$affdb[2]['zhuijia']=sprintf("%.2f",($zj[2]*7-$zj[2]))."%";
			$affdb[3]['zhuijia']=sprintf("%.2f",($zj[3]*7-$zj[3]))."%";
			break;
			case '130':
			$affdb[1]['zhuijia']=sprintf("%.2f",($zj[1]*8.5-$zj[1]))."%";			
			$affdb[2]['zhuijia']=sprintf("%.2f",($zj[2]*8.5-$zj[2]))."%";
			$affdb[3]['zhuijia']=sprintf("%.2f",($zj[3]*8.5-$zj[3]))."%";
			break;
			case '131':
			$affdb[1]['zhuijia']=sprintf("%.2f",($zj[1]*10-$zj[1]))."%";			
			$affdb[2]['zhuijia']=sprintf("%.2f",($zj[2]*10-$zj[2]))."%";
			$affdb[3]['zhuijia']=sprintf("%.2f",($zj[3]*10-$zj[3]))."%";
			break;
			default:
			$affdb[1]['zhuijia']="0%";
			$affdb[2]['zhuijia']="0%";
			$affdb[3]['zhuijia']="0%";
		}
		//dump($affdb);
		*/
		$this->assign('affdb', $affdb);
		
		
        $size = I(C('page_size'), 5);
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $count = count($down_uid);
        $this->pageLimit(url('sale/line'), $size);
        $this->assign('pager', $this->pageShow($count));
        
        //获取用户粉丝
        $list = array_slice($down_uid, ($page-1)*$size,$size);	
		
			
        //模板赋值
        $this->assign('list',    $list);
        $this->assign('title','我的粉丝');
        $this->display('user_sale_offline.dwt');
    }
    
    /**
     * 获取全部分享订单
     */
    public function order_list() {
		$share = unserialize(C('affiliate'));
		$page = I('request.page', 1);
		$size = I(C('page_size'), 10);
		empty($share) && $share = array();
		if (empty($share['config']['separate_by'])) {
			// 推荐注册分成
			$affdb = array(); 
			$num = 3;
			$up_uid = "'$this->user_id'";
			$all_uid = "'$this->user_id'";
			for ($i = 1; $i <= $num; $i++) {
				$count = 0;
				if ($up_uid) {
					$where = 'parent_id IN(' . $up_uid . ')';
					$rs = $this->model->table('users')
							->field('user_id')
							->where($where)
							->select();
					if (empty($rs)) {
						$rs = array();
					}
					$up_uid = '';
					foreach ($rs as $k => $v) {
						$up_uid .= $up_uid ? ",'$v[user_id]'" : "'$v[user_id]'";
						if ($i < $num) {
							$all_uid .= ", '$v[user_id]'";
						}
						$count++;
					}
				}
				//dump($up_uid);
				/*
				$affdb[$i]['num'] = $count;
				$affdb[$i]['point'] = $share['item'][$i - 1]['level_point'];
				$affdb[$i]['money'] = $share['item'][$i - 1]['level_money'];
				$this->assign('affdb', $affdb);
				*/

				$sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";

				$sql = "SELECT o.*, a.log_id, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
			}
		} else {
			// 推荐订单分成
			$sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";

			$sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
		}

		$res = $this->model->query($sqlcount);
		//dump($res);
		$count = $res[0]['count'];
		
		$url_format = url('order_list', array(
			'page' => '{page}'
		));
		$limit = $this->pageLimit($url_format, 5);
		
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
				$rt[$k]['order_sn'] = substr($v['order_sn'], 0, strlen($v['order_sn']) - 5) . "***" . substr($v['order_sn'], - 2, 2);
				$rt[$k]['nickname'] = model('ClipsBase')->get_user_nc_tx($v['user_id'],2);
				$rt[$k]['is_pay'] = model('ClipsBase')->get_order_status($v['pay_status']);
				$rt[$k]['total_fee'] = floatval($v['goods_amount'])+floatval($v['shipping_fee'])+floatval($v['insure_fee'])+floatval($v['pay_fee'])+floatval($v['pack_fee'])+floatval($v['card_fee'])+floatval($v['tax'])-floatval($v['discount']);
				$rt[$k]['is_separate'] = $v['is_separate'] > 0 ? "<span style='font-weight:bold'>已分成</span>" : "<span style='color:red;font-weight:bold'>未分成</span>";
			}
		} else {
			$rt = array();
		}
		//dump($rt);
		
	
	
	
        
        $all_uid = explode(',',str_replace(array("'"," "),"",$all_uid));		
        if (I('get.uid') > 0){
			$is_down=0;
			foreach ($all_uid as $k=>$v){
				if(I('get.uid') == $v){
					$is_down=1;
					break;
				}
			}
			if($is_down==1){				
				$where = ' user_id ='.I('get.uid');
				$pay = 1;
				$size = I(C('page_size'), 5);
				$count = $this->model->table('order_info')->where($where)->count();
				$filter['page'] = '{page}';
				$filter['uid'] = I('get.uid');
				$offset = $this->pageLimit(url('order_list', $filter), $size);
				
				$offset_page = explode(',', $offset);
				//dump($offset_page);
				$rt = model('Sale')->get_user_orders2($pay, $offset_page[1], $offset_page[0],I('get.uid'));
				//dump($rt);
				$this->assign('pay', $pay);
			}	
        }
        
		
		
        $this->assign('title', L('order_list'));
        $this->assign('pager', $this->pageShow($count));
        $this->assign('orders_list', $rt);
        
        $this->display('user_sale_order.dwt');
    }
    
    /**
     * 分享订单详情
     */
    public function order_detail() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    
        // 订单详情
        $order = model('Sale')->get_order_detail($order_id, $this->user_id);
        
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

        // 订单 支付 配送 状态语言项
        $order['order_status'] = L('os.' . $order['order_status']);
        $order['pay_status'] = L('ps.' . $order['pay_status']);
        $order['shipping_status'] = L('ss.' . $order['shipping_status']);
      

        $this->assign('title', L('order_detail'));
        $this->assign('order', $order);
        $this->assign('goods_list', $goods_list);
        $this->display('sale_order_detail.dwt');
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
        $this->pageLimit(url('sale/account_detail'), $size);
        $this->assign('pager', $this->pageShow($count));
    
        $account_detail = model('Sale')->get_account_detail($this->user_id, $size, ($page-1)*$size);
    
        $this->assign('title', L('label_user_surplus'));
        $this->assign('surplus_amount', price_format($surplus_amount, false));
        $this->assign('account_log', $account_detail);
        $this->display('sale_account_detail.dwt');
    }
    
    
    /**
     *  会员充值和提现申请记录
     */
    public function  account_log(){
    
        $size = I(C('page_size'), 5);
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $count = $this->model->table('user_account')->field('COUNT(*)')->where("user_id = $this->user_id AND process_type ". db_create_in(array(SURPLUS_SAVE, SURPLUS_RETURN)))->getOne();
        $this->pageLimit(url('sale/account_log'), $size);
        $this->assign('pager', $this->pageShow($count));
    
        //获取剩余余额
        $surplus_amount = model('ClipsBase')->get_user_surplus($this->user_id);
        if (empty($surplus_amount))
        {
            $surplus_amount = 0;
        }
        //获取余额记录
        $account_log = model('ClipsBase')->get_account_log($this->user_id, $size, ($page-1)*$size);
    
        //模板赋值
        $this->assign('surplus_amount', price_format($surplus_amount, false));
        $this->assign('account_log',    $account_log);
        $this->assign('title', L('label_user_surplus'));
        $this->display('sale_account_log.dwt');
    }
    
    /**
     *  删除会员余额
     */
    public function cancel(){
    
        $id = I('get.id',0);
        if ($id == 0 || $this->user_id == 0)
        {
            ecs_header("Location: ".url('sale/account_log'));
            exit;
        }
    
        $result = model('ClipsBase')->del_user_account($id, $this->user_id);
        ecs_header("Location: ".url('sale/account_log'));
    }
    
    /**
     *  会员退款申请界面
     */
    public function account_raply(){
        // 获取剩余余额
        $surplus_amount = model('ClipsBase')->get_user_surplus($this->user_id);
        if (empty($surplus_amount)) {
            $surplus_amount = 0;
        }
        $this->assign('surplus_amount', price_format($surplus_amount, false));
        $this->assign('title', L('label_user_surplus'));
        $this->display('sale_account_raply.dwt');
    }
    
    /**
     *  会员预付款界面
     */
    public function account_deposit(){
        $this->assign('title', L('label_user_surplus'));
        $surplus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $account    = model('ClipsBase')->get_surplus_info($surplus_id);
    
        $this->assign('payment', model('ClipsBase')->get_online_payment_list(false));
        $this->assign('order',   $account);
        $this->display('sale_account_deposit.dwt');
    }
    
    /**
     *  对会员余额申请的处理
     */
    public function act_account()
    {
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        if ($amount <= 0)
        {
            show_message($_LANG['amount_gt_zero']);
        }
    
        /* 变量初始化 */
        $surplus = array(
            'user_id'      => $this->user_id,
            'rec_id'       => !empty($_POST['rec_id'])      ? intval($_POST['rec_id'])       : 0,
            'process_type' => isset($_POST['surplus_type']) ? intval($_POST['surplus_type']) : 0,
            'payment_id'   => isset($_POST['payment_id'])   ? intval($_POST['payment_id'])   : 0,
            'user_note'    => isset($_POST['user_note'])    ? trim($_POST['user_note'])      : '',
            'amount'       => $amount
        );
    
        /* 退款申请的处理 */
        if ($surplus['process_type'] == 1)
        {
            /* 判断是否有足够的余额的进行退款的操作 */
            $sur_amount = model('ClipsBase')->get_user_surplus($this->user_id);
            if ($amount > $sur_amount)
            {
                $content = L('surplus_amount_error');
                show_message($content, L('back_page_up'), '', 'info');
            }
    
            //插入会员账目明细
            $amount = '-'.$amount;
            $surplus['payment'] = '';
            $surplus['rec_id']  = model('ClipsBase')->insert_user_account($surplus, $amount);
    
            /* 如果成功提交 */
            if ($surplus['rec_id'] > 0)
            {
                $content = L('surplus_appl_submit');
                show_message($content, L('back_account_log'), url('sale/account_log'), 'info');
            }
            else
            {
                $content = $L('process_false');
                show_message($content, L('back_page_up'), '', 'info');
            }
        }
        /* 如果是会员预付款，跳转到下一步，进行线上支付的操作 */
        else
        {
            if ($surplus['payment_id'] <= 0)
            {
                show_message(L('select_payment_pls'));
            }
    
    
            //获取支付方式名称
            $payment_info = array();
            $payment_info = model('Order')->payment_info($surplus['payment_id']);
            $surplus['payment'] = $payment_info['pay_name'];
    
            if ($surplus['rec_id'] > 0)
            {
                //更新会员账目明细
                $surplus['rec_id'] = model('ClipsBase')->update_user_account($surplus);
            }
            else
            {
                //插入会员账目明细
                $surplus['rec_id'] = model('ClipsBase')->insert_user_account($surplus, $amount);
            }
    
            //取得支付信息，生成支付代码
            $payment = unserialize_config($payment_info['pay_config']);
    
            //生成伪订单号, 不足的时候补0
            $order = array();
            $order['order_sn']       = $surplus['rec_id'];
            $order['user_name']      = $_SESSION['user_name'];
            $order['surplus_amount'] = $amount;
    
            //计算支付手续费用
            $payment_info['pay_fee'] = pay_fee($surplus['payment_id'], $order['surplus_amount'], 0);
    
            //计算此次预付款需要支付的总金额
            $order['order_amount']   = $amount + $payment_info['pay_fee'];
    
            //记录支付log
            $order['log_id'] = model('ClipsBase')->insert_pay_log($surplus['rec_id'], $order['order_amount'], $type=PAY_SURPLUS, 0);
    
            /* 调用相应的支付方式文件 */
            include_once (ROOT_PATH . 'plugins/payment/' . $payment_info ['pay_code'] . '.php');
    
            /* 取得在线支付方式的支付按钮 */
            $pay_obj = new $payment_info ['pay_code'] ();
            $payment_info['pay_button'] = $pay_obj->get_code($order, $payment);
    
            /* 模板赋值 */
            $this->assign('payment', $payment_info);
            $this->assign('pay_fee', price_format($payment_info['pay_fee'], false));
            $this->assign('amount',  price_format($amount, false));
            $this->assign('order',   $order);
            $this->display('sale_act_account.dwt');
        }
    }
	
	//粉丝排行榜
	public function fans_top(){
		$sql= 'SELECT parent_id,COUNT( * ) AS count FROM ' . $this->model->pre . 'users GROUP BY parent_id ORDER BY count DESC LIMIT 21';	
        $rs = $this->model->query($sql);
		$xuhao=0;
		foreach($rs as $k=>$v){
			if($v['parent_id'] <> 0){
				$u_info=model('ClipsBase')->get_touxiang($v['parent_id']);
				$rs[$k]['nickname']=$u_info[0]['nickname'];
				$rs[$k]['headimg']=$u_info[0]['headimgurl'];
				$xuhao=$xuhao+1;
				$rs[$k]['xuhao']=$xuhao;
			}else{				
				unset($rs[$k]);	
			}
			
		}	
			
		//模板赋值		
        $this->assign('list',$rs);
        $this->assign('title','粉丝排行榜');
        $this->display('user_sale_fans.dwt');
	}
	
	
	
}
