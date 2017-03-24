<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ClipsBaseModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 用户基础模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ClipsBaseModel extends BaseModel {

    protected $table = '';

    /**
     *  获取指定用户的收藏商品列表
     * @access  public
     * @param   int     $user_id        用户ID
     * @param   int     $num            列表最大数量
     * @param   int     $start          列表其实位置
     * @return  array   $arr
     */
    public function get_collection_goods($user_id, $num = 10, $start = 0) {
        $sql = 'SELECT g.goods_id, g.goods_name, g.goods_thumb, g.market_price, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, " .
                'g.promote_price, g.promote_start_date,g.promote_end_date, c.rec_id, c.is_attention' .
                ' FROM ' . $this->pre . 'collect_goods AS c' .
                ' LEFT JOIN ' . $this->pre . 'goods AS g ' .
                'ON g.goods_id = c.goods_id ' .
                ' LEFT JOIN ' . $this->pre . 'member_price AS mp ' .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
                " WHERE c.user_id = '$user_id' ORDER BY c.rec_id DESC limit $start, $num";
        $res = $this->query($sql);

        $goods_list = array();
        if (is_array($res)) {
            foreach ($res as $row) {
                if ($row['promote_price'] > 0) {
                    $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }
                $goods_list[$row['goods_id']]['rec_id'] = $row['rec_id'];
                $goods_list[$row['goods_id']]['is_attention'] = $row['is_attention'];
                $goods_list[$row['goods_id']]['goods_id'] = $row['goods_id'];
                $goods_list[$row['goods_id']]['goods_name'] = $row['goods_name'];
                $goods_list[$row['goods_id']]['goods_thumb'] = get_image_path(0, $row['goods_thumb']);
                $goods_list[$row['goods_id']]['market_price'] = price_format($row['market_price']);
                $goods_list[$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
                $goods_list[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
                $goods_list[$row['goods_id']]['url'] = url('goods/index', array('id' => $row['goods_id']));
            }
        }

        return $goods_list;
    }

    /**
     *  查看此商品是否已进行过缺货登记
     * @access  public
     * @param   int     $user_id        用户ID
     * @param   int     $goods_id       商品ID
     * @return  int
     */
    public function get_booking_rec($user_id, $goods_id) {
        $this->table = 'booking_goods';
        $condition['user_id'] = $user_id;
        $condition['goods_id'] = $goods_id;
        $condition['is_dispose'] = 0;
        return $this->count($condition);
    }

    /**
     *  获取指定用户的留言
     * @access  public
     * @param   int     $user_id        用户ID
     * @param   int     $user_name      用户名
     * @param   int     $num            列表最大数量
     * @param   int     $start          列表其实位置
     * @return  array   $msg            留言及回复列表
     * @return  string  $order_id       订单ID
     */
    public function get_message_list($user_id, $user_name, $num, $start, $order_id = 0) {
        $this->table = 'feedback';
        /* 获取留言数据 */
        $condition['parent_id'] = 0;
        $condition['user_id'] = $user_id;
        if ($order_id) {
            $condition['order_id'] = $order_id;
        } else {
            $condition['order_id'] = 0;
            $condition['user_name'] = $_SESSION['user_name'];
        }
        $list = $this->select($condition, '*', 'msg_time DESC', $start . ',' . $num);

        $msg = array();
        if (is_array($list)) {
            foreach ($list as $vo) {
                $reply = array();

                $condition2['parent_id'] = $vo['msg_id'];
                $reply = $this->find($condition2, 'user_name, user_email, msg_time, msg_content');

                if ($reply) {
                    $msg[$vo['msg_id']]['re_user_name'] = $reply['user_name'];
                    $msg[$vo['msg_id']]['re_user_email'] = $reply['user_email'];
                    $msg[$vo['msg_id']]['re_msg_time'] = local_date(C('time_format'), $reply['msg_time']);
                    $msg[$vo['msg_id']]['re_msg_content'] = nl2br(htmlspecialchars($reply['msg_content']));
                }
                $msg[$vo['msg_id']]['url'] = url('user/del_msg', array('id' => $vo['msg_id'], 'order_id' => $vo['order_id']));
                $msg[$vo['msg_id']]['msg_content'] = nl2br(htmlspecialchars($vo['msg_content']));
                $msg[$vo['msg_id']]['msg_time'] = local_date(C('time_format'), $vo['msg_time']);
                $msg[$vo['msg_id']]['msg_type'] = $order_id ? $vo['user_name'] : L('type.' . $vo['msg_type']);
                $msg[$vo['msg_id']]['msg_title'] = nl2br(htmlspecialchars($vo['msg_title']));
                $msg[$vo['msg_id']]['message_img'] = $vo['message_img'];
                $msg[$vo['msg_id']]['order_id'] = $vo['order_id'];
            }
        }

        return $msg;
    }

    /**
     *  添加留言函数
     * @access  public
     * @param   array       $message
     * @return  boolen      $bool
     */
    public function add_message($message) {
        $upload_size_limit = C('upload_size_limit') == '-1' ? ini_get('upload_max_filesize') : C('upload_size_limit');
        $status = 1 - C('message_check');

        $last_char = strtolower($upload_size_limit{strlen($upload_size_limit) - 1});

        switch ($last_char) {
            case 'm':
                $upload_size_limit *= 1024 * 1024;
                break;
            case 'k':
                $upload_size_limit *= 1024;
                break;
        }

        if ($message['upload']) {
            if ($_FILES['message_img']['size'] / 1024 > $upload_size_limit) {
                ECTouch::err()->add(sprintf(L('upload_file_limit'), $upload_size_limit));
                return false;
            }
            $img_name = upload_file($_FILES['message_img'], 'feedbackimg');

            if ($img_name === false) {
                return false;
            }
        } else {
            $img_name = '';
        }

        if (empty($message['msg_title'])) {
            ECTouch::err()->add(L('msg_title_empty'));
            return false;
        }

        $message['msg_area'] = isset($message['msg_area']) ? intval($message['msg_area']) : 0;

        $data['msg_id'] = NULL;
        $data['parent_id'] = 0;
        $data['user_id'] = $message['user_id'];
        $data['user_name'] = $message['user_name'];
        $data['user_email'] = $message['user_email'];
        $data['msg_title'] = $message['msg_title'];
        $data['msg_type'] = $message['msg_type'];
        $data['msg_status'] = $status;
        $data['msg_content'] = $message['msg_content'];
        $data['msg_time'] = gmtime();
        $data['message_img'] = $img_name;
        $data['order_id'] = $message['order_id'];
        $data['msg_area'] = $message['msg_area'];
        $this->table = 'feedback';
        $this->insert($data);

        return true;
    }

    /**
     *  验证性的删除某个tag
     * @access  public
     * @param   int         $tag_words      tag的ID
     * @param   int         $user_id        用户的ID
     * @return  boolen      bool
     */
    public function delete_tag($tag_words, $user_id) {
        $this->table = 'tag';
        $condition['tag_words'] = $tag_words;
        $condition['user_id'] = $user_id;
        return $this->delete($condition);
    }

    /**
     *  获取某用户的缺货登记列表
     * @access  public
     * @param   int     $user_id        用户ID
     * @param   int     $num            列表最大数量
     * @param   int     $start          列表其实位置
     * @return  array   $booking
     */
    public function get_booking_list($user_id, $num, $start) {
        $booking = array();
        $sql = "SELECT bg.rec_id, bg.goods_id, bg.goods_number, bg.booking_time, bg.dispose_note, g.goods_name " .
                "FROM " . $this->pre . "booking_goods AS bg , " . $this->pre . "goods AS g" . " WHERE bg.goods_id = g.goods_id AND bg.user_id = '$user_id' ORDER BY bg.booking_time DESC limit " . $start . ',' . $num;
        $list = $this->query($sql);

        if (is_array($list)) {
            foreach ($list as $vo) {
                if (empty($vo['dispose_note'])) {
                    $vo['dispose_note'] = 'N/A';
                }
                $booking[] = array('rec_id' => $vo['rec_id'],
                    'goods_name' => $vo['goods_name'],
                    'goods_number' => $vo['goods_number'],
                    'booking_time' => local_date(C('date_format'), $vo['booking_time']),
                    'dispose_note' => $vo['dispose_note'],
                    'url' => url('goods/index', array('id' => $vo['goods_id'])));
            }
        }

        return $booking;
    }

    /**
     *  获取某用户的缺货登记列表
     * @access  public
     * @param   int     $goods_id    商品ID
     * @return  array   $info
     */
    public function get_goodsinfo($goods_id) {
        $info = array();
        $this->table = 'goods';
        $condition['goods_id'] = $goods_id;
        $info['goods_name'] = $this->field('goods_name', $condition);
        $info['goods_number'] = 1;
        $info['id'] = $goods_id;

        if (!empty($_SESSION['user_id'])) {
            $row = array();
            $sql = "SELECT ua.consignee, ua.email, ua.tel, ua.mobile " .
                    "FROM " . $this->pre . "user_address AS ua, " . $this->pre . "users AS u" .
                    " WHERE u.address_id = ua.address_id AND u.user_id = '$_SESSION[user_id]'";
            $row = $this->row($sql);
            $info['consignee'] = empty($row['consignee']) ? '' : $row['consignee'];
            $info['email'] = empty($row['email']) ? '' : $row['email'];
            $info['tel'] = empty($row['mobile']) ? (empty($row['tel']) ? '' : $row['tel']) : $row['mobile'];
        }

        return $info;
    }

    /**
     *  验证删除某个收藏商品
     * @access  public
     * @param   int         $booking_id     缺货登记的ID
     * @param   int         $user_id        会员的ID
     * @return  boolen      $bool
     */
    public function delete_booking($booking_id, $user_id) {
        $this->table = 'booking_goods';
        $condition['rec_id'] = $booking_id;
        $condition['user_id'] = $user_id;
        return $this->delete($condition);
    }

    /**
     * 添加缺货登记记录到数据表
     * @access  public
     * @param   array $booking
     * @return void
     */
    public function add_booking($booking) {
        $this->table = 'booking_goods';
        $data['user_id'] = $_SESSION['user_id'];
        $data['email'] = $booking['email'];
        $data['link_man'] = $booking['linkman'];
        $data['tel'] = $booking['tel'];
        $data['goods_id'] = $booking['goods_id'];
        $data['goods_desc'] = $booking['desc'];
        $data['goods_number'] = $booking['goods_amount'];
        $data['booking_time'] = gmtime();
        return $this->insert($data);
    }

    /**
     * 插入会员账目明细
     * @access  public
     * @param   array     $surplus  会员余额信息
     * @param   string    $amount   余额
     * @return  int
     */
    public function insert_user_account($surplus, $amount) {
        $this->table = 'user_account';
        $data['user_id'] = $surplus['user_id'];
        $data['admin_user'] = '';
        $data['amount'] = $amount;
        $data['add_time'] = gmtime();
        $data['paid_time'] = 0;
        $data['admin_note'] = '';
        $data['user_note'] = $surplus['user_note'];
        $data['process_type'] = $surplus['process_type'];
		$data['is_premium'] = $surplus['is_premium'];
		$data['s_id'] = $surplus['s_id'];
        $data['payment'] = $surplus['payment'];
        $data['is_paid'] = 0;
        return $this->insert($data);
    }
	/**
     * 插入会员金币账目明细
     * @access  public
     * @param   array     $surplus  会员余额信息
     * @param   string    $amount   余额
     * @return  int
     */
    public function insert_user_account2($surplus, $amount) {
        $this->table = 'user_account';
        $data['user_id'] = $surplus['user_id'];
        $data['admin_user'] = '';
        $data['gold_amount'] = $amount;
        $data['add_time'] = gmtime();
        $data['paid_time'] = 0;
        $data['admin_note'] = '';
        $data['user_note'] = $surplus['user_note'];
        $data['process_type'] = $surplus['process_type'];
        $data['payment'] = $surplus['payment'];
        $data['is_paid'] = 0;
        return $this->insert($data);
    }

    /**
     * 更新会员账目明细
     * @access  public
     * @param   array     $surplus  会员余额信息
     * @return  int
     */
    public function update_user_account($surplus) {
        $this->table = 'user_account';
        $data['amount'] = $surplus['amount'];
        $data['user_note'] = $surplus['user_note'];
        $data['payment'] = $surplus['payment'];
        $condition['id'] = $surplus['rec_id'];
        $this->update($condition, $data);

        return $surplus['rec_id'];
    }

    /**
     * 将支付LOG插入数据表
     * @access  public
     * @param   integer     $id         订单编号
     * @param   float       $amount     订单金额
     * @param   integer     $type       支付类型
     * @param   integer     $is_paid    是否已支付
     * @return  int
     */
    public function insert_pay_log($id, $amount, $type = PAY_SURPLUS, $is_paid = 0) {
        $this->table = 'pay_log';
        $data['order_id'] = $id;
        $data['order_amount'] = $amount;
        $data['order_type'] = $type;
        $data['is_paid'] = $is_paid;
        return $this->insert($data);
    }

    /**
     * 取得上次未支付的pay_lig_id
     * @access  public
     * @param   array     $surplus_id  余额记录的ID
     * @param   array     $pay_type    支付的类型：预付款/订单支付
     * @return  int
     */
    public function get_paylog_id($surplus_id, $pay_type = PAY_SURPLUS) {
        $this->table = 'pay_log';
        $condition['order_id'] = $surplus_id;
        $condition['order_type'] = $pay_type;
        $condition['is_paid'] = 0;
        return $this->field('log_id', $condition);
    }

    /**
     * 根据ID获取当前余额操作信息
     * @access  public
     * @param   int     $surplus_id  会员余额的ID
     * @return  int
     */
    public function get_surplus_info($surplus_id) {
        $this->table = 'user_account';
        $condition['id'] = $surplus_id;
        return $this->find($condition);
    }

    /**
     * 取得已安装的支付方式(其中不包括线下支付的)
     * @param   bool    $include_balance    是否包含余额支付（冲值时不应包括）
     * @return  array   已安装的配送方式列表
     */
    public function get_online_payment_list($include_balance = true) {
        $sql = 'SELECT pay_id, pay_code, pay_name, pay_fee, pay_desc ' .
                'FROM ' . $this->pre . "touch_payment WHERE enabled = 1 AND is_cod <> 1";
        if (!$include_balance) {
            $sql .= " AND pay_code <> 'balance' ";
        }
        $modules = M()->query($sql);
        //支付插件排序
        if (isset($modules)) {
            /* 将财付通提升至第二个显示 */
            foreach ($modules as $k => $v) {
                if ($v['pay_code'] == 'tenpay') {
                    $tenpay = $modules[$k];
                    unset($modules[$k]);
                    array_unshift($modules, $tenpay);
                }
            }
            /* 将快钱直连银行显示在快钱之后 */
            foreach ($modules as $k => $v) {
                if (strpos($v['pay_code'], 'kuaiqian') !== false) {
                    $tenpay = $modules[$k];
                    unset($modules[$k]);
                    array_unshift($modules, $tenpay);
                }
            }

            /* 将快钱提升至第一个显示 */
            foreach ($modules as $k => $v) {
                if ($v['pay_code'] == 'kuaiqian') {
                    $tenpay = $modules[$k];
                    unset($modules[$k]);
                    array_unshift($modules, $tenpay);
                }
            }
        }

        return $modules;
    }

    /**
     * 查询会员余额的操作记录
     * @access  public
     * @param   int     $user_id    会员ID
     * @param   int     $num        每页显示数量
     * @param   int     $start      开始显示的条数
     * @return  array
     */
    public function get_account_log($user_id, $num, $start) {
        $account_log = array();
        $sql = 'SELECT * FROM ' . $this->pre . "user_account WHERE user_id = '$user_id'" .
                " AND process_type " . db_create_in(array(SURPLUS_SAVE, SURPLUS_RETURN)) .
                " ORDER BY add_time DESC limit " . $start . ',' . $num;
        $list = $this->query($sql);

        if (is_array($list)) {
            foreach ($list as $vo) {
                $vo['add_time'] = local_date(C('date_format'), $vo['add_time']);
                $vo['admin_note'] = nl2br(htmlspecialchars($vo['admin_note']));
                $vo['short_admin_note'] = ($vo['admin_note'] > '') ? sub_str($vo['admin_note'], 30) : 'N/A';
                $vo['user_note'] = nl2br(htmlspecialchars($vo['user_note']));
                $vo['short_user_note'] = ($vo['user_note'] > '') ? sub_str($vo['user_note'], 30) : 'N/A';
                $vo['pay_status'] = ($vo['is_paid'] == 0) ? L('un_confirm') : L('is_confirm');
                $vo['amount'] = price_format(abs($vo['amount']), false);

                /* 会员的操作类型： 冲值，提现 */
                if ($vo['process_type'] == 0) {
                    $vo['type'] = L('surplus_type_0');
                } else {
                    $vo['type'] = L('surplus_type_1');
                }

                /* 支付方式的ID */
                $this->table = 'touch_payment';
                $condition['pay_name'] = $vo['payment'];
                $condition['enabled'] = 1;
                $pid = $this->field('pay_id', $condition);

                /* 如果是预付款而且还没有付款, 允许付款 */
                if (($vo['is_paid'] == 0) && ($vo['process_type'] == 0)) {
                    $vo['handle'] = '<a href="../../../../' . url('user/pay') . '&amp;id=' . $vo['id'] . '&amp;pid=' . $pid . '" class="btn btn-default">' . L('pay') . '</a>';
                }

                $account_log[] = $vo;
            }

            return $account_log;
        } else {
            return false;
        }
    }/**
     * 查询会员余额的操作记录
     * @access  public
     * @param   int     $user_id    会员ID
     * @param   int     $num        每页显示数量
     * @param   int     $start      开始显示的条数
     * @return  array
     */
    public function get_stock_log($user_id, $num, $start) {
        $account_log = array();
        $sql = 'SELECT * FROM ' . $this->pre . "stock_log WHERE user_id = '$user_id'" .
                " AND change_type " . db_create_in(array('6', '9')) .
                " ORDER BY change_time DESC limit " . $start . ',' . $num;
        $list = $this->query($sql);

        if (is_array($list)) {
            foreach ($list as $vo) {
                $vo['change_time'] = local_date(C('date_format'), $vo['change_time']);
                //$vo['change_desc'] = nl2br(htmlspecialchars($vo['change_desc']));
				if($vo['stock'] >= 0){
					$vo['stock'] = "+".$vo['stock'];
				}else{
					$vo['stock'] = "-".$vo['stock'];
				}
                

                
                if ($vo['change_type'] == 6) {
                    $vo['type'] = '分红';
                } else {
                    $vo['type'] = $vo['change_desc'];
                }
				
				$ktimes = $this->get_ktimes($vo['s_id']);
				if($ktimes == '1'){
					$vo['ktimes'] ="最新市值";
				}else{
					$vo['ktimes']="追".$ktimes."股";
				}

                $account_log[] = $vo;
            }

            return $account_log;
        } else {
            return false;
        }
    }
	
	public function get_ktimes($s_id){
		$this->table = 'user_stock';
        $condition['s_id'] = $s_id;
        return $this->field('ktimes', $condition);
	}
	 /**
     * 查询会员余额的操作记录
     * @access  public
     * @param   int     $user_id    会员ID
     * @param   int     $num        每页显示数量
     * @param   int     $start      开始显示的条数
     * @return  array
     */
    public function get_account_log2($user_id, $num, $start) {
        $account_log = array();
        $sql = 'SELECT * FROM ' . $this->pre . "user_account WHERE user_id = '$user_id'" .
                " AND process_type =1 ORDER BY add_time DESC limit " . $start . ',' . $num;
        $list = $this->query($sql);

        if (is_array($list)) {
            foreach ($list as $vo) {
                $vo['add_time'] = local_date(C('date_format'), $vo['add_time']);
                $vo['admin_note'] = nl2br(htmlspecialchars($vo['admin_note']));
                $vo['short_admin_note'] = ($vo['admin_note'] > '') ? sub_str($vo['admin_note'], 30) : 'N/A';
                $vo['user_note'] = nl2br(htmlspecialchars($vo['user_note']));
                $vo['short_user_note'] = ($vo['user_note'] > '') ? sub_str($vo['user_note'], 30) : 'N/A';
                $vo['pay_status'] = ($vo['is_paid'] == 0) ? L('un_confirm') : L('is_confirm');
                $vo['amount'] = price_format(abs($vo['amount']), false);
				$vo['gold_amount'] = price_format(abs($vo['gold_amount']), false);

                /* 会员的操作类型： 冲值，提现 */
                if ($vo['process_type'] == 0) {
                    $vo['type'] = L('surplus_type_0');
                } else {
                    $vo['type'] = L('surplus_type_1');
                }

                /* 支付方式的ID */
                $this->table = 'touch_payment';
                $condition['pay_name'] = $vo['payment'];
                $condition['enabled'] = 1;
                $pid = $this->field('pay_id', $condition);

                /* 如果是预付款而且还没有付款, 允许付款 */
                if (($vo['is_paid'] == 0) && ($vo['process_type'] == 0)) {
                    $vo['handle'] = '<a href="../../../../' . url('user/pay') . '&amp;id=' . $vo['id'] . '&amp;pid=' . $pid . '" class="btn btn-default">' . L('pay') . '</a>';
                }

                $account_log[] = $vo;
            }

            return $account_log;
        } else {
            return false;
        }
    }
    /**
     *  删除未确认的会员帐目信息
     * @access  public
     * @param   int         $rec_id     会员余额记录的ID
     * @param   int         $user_id    会员的ID
     * @return  boolen
     */
    public function del_user_account($rec_id, $user_id) {
        $this->table = 'user_account';
        $condition['is_paid'] = 0;
        $condition['id'] = $rec_id;
        $condition['user_id'] = $user_id;
        return $this->delete($condition);
    }

    /**
     * 查询会员余额的数量
     * @access  public
     * @param   int     $user_id        会员ID
     * @return  int
     */
    public function get_user_surplus($user_id) {
        $this->table = 'account_log';
        $condition['user_id'] = $user_id;
        return $this->field('SUM(user_money)', $condition);
    }
	/**
     * 查询会员金币的数量
     * @access  public
     * @param   int     $user_id        会员ID
     * @return  int
     */
    public function get_user_gold($user_id) {
        $this->table = 'account_log';
        $condition['user_id'] = $user_id;
        return $this->field('SUM(gold_coin)', $condition);
    }
	/**
     * 查询会员银币的数量
     * @access  public
     * @param   int     $user_id        会员ID
     * @return  int
     */
    public function get_user_sliv($user_id) {
        $this->table = 'account_log';
        $condition['user_id'] = $user_id;
        return $this->field('SUM(pay_points)', $condition);
    }
	/**
     * 查询会员积分的数量
     * @access  public
     * @param   int     $user_id        会员ID
     * @return  int
     */
    public function get_user_jifen($user_id) {
        $this->table = 'account_log';
        $condition['user_id'] = $user_id;
        return $this->field('SUM(rank_points)', $condition);
    }
	/**
     * 查询会员余额的数量
     * @access  public
     * @param   int     $user_id        会员ID
     * @return  int
     */
    public function get_user_surplus2($user_id) {
        $this->table = 'users';
        $condition['user_id'] = $user_id;
        return $this->field('user_money', $condition);
    }
	/**
     * 查询会员金币的数量
     * @access  public
     * @param   int     $user_id        会员ID
     * @return  int
     */
    public function get_user_gold2($user_id) {
        $this->table = 'users';
        $condition['user_id'] = $user_id;
        return $this->field('gold_coin', $condition);
    }
	/**
     * 查询会员银币的数量
     * @access  public
     * @param   int     $user_id        会员ID
     * @return  int
     */
    public function get_user_sliv2($user_id) {
        $this->table = 'users';
        $condition['user_id'] = $user_id;
        return $this->field('pay_points', $condition);
    }
	/**
     * 查询会员积分的数量
     * @access  public
     * @param   int     $user_id        会员ID
     * @return  int
     */
    public function get_user_jifen2($user_id) {
        $this->table = 'users';
        $condition['user_id'] = $user_id;
        return $this->field('rank_points', $condition);
    }
    /**
     * 查询会员的红包金额
     * @access  public
     * @param   integer     $user_id
     * @return  void
     */
    public function get_user_bonus($user_id = 0) {
        if ($user_id == 0) {
            $user_id = $_SESSION['user_id'];
        }

        $sql = "SELECT SUM(bt.type_money) AS bonus_value, COUNT(*) AS bonus_count " .
                "FROM " . $this->pre . "user_bonus AS ub, " . $this->pre . "bonus_type AS bt " .
                "WHERE ub.user_id = '$user_id' AND ub.bonus_type_id = bt.type_id AND ub.order_id = 0";
        $row = $this->row($sql);

        return $row;
    }

    /**
     * 获取用户中心默认页面所需的数据
     * @access  public
     * @param   int         $user_id            用户ID
     * @return  array       $info               默认页面所需资料数组
     */
    public function get_user_default($user_id) {
        $user_bonus = $this->get_user_bonus();

        $sql = "SELECT pay_points, user_money, gold_coin ,frozen_premium, credit_line, last_login, is_validated,user_rank,nicheng,u_headimg,next_double_time,done_stock FROM " . $this->pre . "users WHERE user_id = '$user_id'";
        $row = $this->row($sql);
        $info = array();
		//显示用户金币
		$info['gold_coin']=$row['gold_coin'];
		$info['frozen_premium']=$row['frozen_premium'];
		$info['done_stock']=$row['done_stock'];
        $info['username'] = stripslashes($_SESSION['user_name']);
        $info['shop_name'] = C('shop_name');
        $info['integral'] = $row['pay_points'] . C('silver_coin');
        /* 增加是否开启会员邮件验证开关 */
        $info['is_validate'] = (C('member_email_validate') && !$row['is_validated']) ? 0 : 1;
        $info['credit_line'] = $row['credit_line'];
        $info['formated_credit_line'] = price_format($info['credit_line'], false);
		//显示用户表的昵称和头像
		$info['nicheng']=$row['nicheng'];
		$info['u_headimg']=$row['u_headimg'];
		//显示用户翻倍时间
		$info['next_double_time'] = $row['next_double_time'] == 0 ? "无" : local_date(C('date_format'), $row['next_double_time']);
		
		
        //新增获取用户头像，昵称
        $u_row = '';
        if(class_exists('WechatController')){
            if (method_exists('WechatController', 'get_avatar')) {
                $u_row = call_user_func(array('WechatController', 'get_avatar'), $user_id);
            }
        }
        if ($u_row) {
            $info['nickname'] = $u_row['nickname'];
            $info['headimgurl'] = $u_row['headimgurl'];
        } else {
            $info['nickname'] = $info['username'];
            $info['headimgurl'] = __PUBLIC__ . '/images/get_avatar.png';
        }

        //如果$_SESSION中时间无效说明用户是第一次登录。取当前登录时间。
        $last_time = !isset($_SESSION['last_time']) ? $row['last_login'] : $_SESSION['last_time'];

        if ($last_time == 0) {
            $_SESSION['last_time'] = $last_time = gmtime();
        }

        $info['last_time'] = local_date(C('time_format'), $last_time);
        $info['surplus'] = price_format($row['user_money'], false);
        $info['bonus'] = sprintf(L('user_bonus_info'), $user_bonus['bonus_count'], price_format($user_bonus['bonus_value'], false));

        $this->table = 'order_info';
        $condition = "user_id = '" . $user_id . "' AND add_time > '" . local_strtotime('-1 months') . "'";
        $info['order_count'] = $this->count($condition);

        $condition = "user_id = '" . $user_id . "' AND shipping_time > '" . $last_time . "'" . order_query_sql('shipped');
        $info['shipped_order'] = $this->select($condition, 'order_id, order_sn');
        $info['user_rank'] = $row['user_rank'];

        return $info;
    }

    /**
     * 获得指定用户、商品的所有标记
     * @access  public
     * @param   integer $goods_id
     * @param   integer $user_id
     * @return  array
     */
    public function get_tags($goods_id = 0, $user_id = 0) {
        $where = '';
        if ($goods_id > 0) {
            $where .= " goods_id = '$goods_id'";
        }

        if ($user_id > 0) {
            if ($goods_id > 0) {
                $where .= " AND";
            }
            $where .= " user_id = '$user_id'";
        }

        if ($where > '') {
            $where = ' WHERE' . $where;
        }

        $sql = 'SELECT tag_id, user_id, tag_words, COUNT(tag_id) AS tag_count' .
                ' FROM ' . $this->pre . "tag$where GROUP BY tag_words";
        $arr = $this->query($sql);

        return $arr;
    }

    /**
     * 添加商品标签
     * @access  public
     * @param   integer     $id
     * @param   string      $tag
     * @return  void
     */
    public function add_tag($id, $tag) {
        $this->table = 'tag';
        if (empty($tag)) {
            return;
        }

        $arr = explode(',', $tag);

        foreach ($arr AS $val) {
            /* 检查是否重复 */
            $condition['user_id'] = $_SESSION['user_id'];
            $condition['goods_id'] = $id;
            $condition['tag_words'] = $val;
            $total = $this->count($condition);

            if ($total == 0) {
                $data['user_id'] = $_SESSION['user_id'];
                $data['goods_id'] = $id;
                $data['tag_words'] = $val;
                $this->insert($data);
            }
        }
    }

    /**
     * 取得用户等级信息
     * @access   public
     * @author   Xuan Yan
     * @return array
     */
    public function get_rank_info() {
        if (!empty($_SESSION['user_rank'])) {
            $sql = "SELECT rank_name, special_rank FROM " . $this->pre . "user_rank WHERE rank_id = '$_SESSION[user_rank]'";
            $row = $this->row($sql);
            if (empty($row)) {
                return array();
            }
            $rank_name = $row['rank_name'];
            if ($row['special_rank']) {
                return array('rank_name' => $rank_name);
            } else {
                $this->table = 'users';
                $condition['user_id'] = $_SESSION['user_id'];
                $user_rank = $this->field('rank_points', $condition);
                $this->table = 'user_rank';
                $sql = "SELECT rank_name,min_points FROM " . $this->pre . "user_rank WHERE min_points > '$user_rank' ORDER BY min_points ASC LIMIT 1";
                $rt = $this->row($sql);
                $next_rank_name = $rt['rank_name'];
                $next_rank = $rt['min_points'] - $user_rank;
                return array('rank_name' => $rank_name, 'next_rank_name' => $next_rank_name, 'next_rank' => $next_rank);
            }
        } else {
            return array();
        }
    }
	
	/**
     * 取得用户等级信息2
     * @access   public
     * @author   Xuan Yan
     * @return array
     */
    public function get_rank_info2($user_rank) {
        if (!empty($user_rank)) {
            $sql = "SELECT rank_name, special_rank FROM " . $this->pre . "user_rank WHERE rank_id = '$user_rank'";
            $row = $this->row($sql);
            if (empty($row)) {
                return array();
            }
            $rank_name = $row['rank_name'];
            if ($row['special_rank']) {
                return array('rank_name' => $rank_name,'is_special' => 1);
            } else {
                $this->table = 'users';
                $condition['user_id'] = $_SESSION['user_id'];
                $user_rank = $this->field('rank_points', $condition);
                $this->table = 'user_rank';
                $sql = "SELECT rank_name,min_points FROM " . $this->pre . "user_rank WHERE min_points > '$user_rank' ORDER BY min_points ASC LIMIT 1";
                $rt = $this->row($sql);
                $next_rank_name = $rt['rank_name'];
                $next_rank = $rt['min_points'] - $user_rank;
                return array('rank_name' => $rank_name, 'next_rank_name' => $next_rank_name, 'next_rank' => $next_rank,'is_special' => 0);
            }
        } else {
            return array();
        }
    }

    /**
     *  获取用户参与活动信息
     * @access  public
     * @param   int     $user_id        用户id
     * @return  array
     */
    public function get_user_prompt($user_id) {
        $prompt = array();
        $now = gmtime();
        /* 夺宝奇兵 */
        $sql = "SELECT act_id, goods_name, end_time " .
                "FROM " . $this->pre . "goods_activity WHERE act_type = '" . GAT_SNATCH . "'" .
                " AND (is_finished = 1 OR (is_finished = 0 AND end_time <= '$now'))";
        $res = $this->query($sql);

        if (is_array($res)) {
            foreach ($res as $row) {
                $act_id = $row['act_id'];
                $result = model('ActivityBase')->get_snatch_result($act_id);
                if (isset($result['order_count']) && $result['order_count'] == 0 && $result['user_id'] == $user_id) {
                    $prompt[] = array(
                        'text' => sprintf(L('your_snatch'), $row['goods_name'], $row['act_id']),
                        'add_time' => $row['end_time']
                    );
                }
                if (isset($auction['last_bid']) && $auction['last_bid']['bid_user'] == $user_id && $auction['order_count'] == 0) {
                    $prompt[] = array(
                        'text' => sprintf(L('your_auction'), $row['goods_name'], $row['act_id']),
                        'add_time' => $row['end_time']
                    );
                }
            }
        }

        /* 竞拍 */
        $sql = "SELECT act_id, goods_name, end_time " .
                "FROM " . $this->pre . "goods_activity WHERE act_type = '" . GAT_AUCTION . "'" .
                " AND (is_finished = 1 OR (is_finished = 0 AND end_time <= '$now'))";
        $res = $this->query($sql);
        if (is_array($res)) {
            foreach ($res as $row) {
                $act_id = $row['act_id'];
                $auction = model('GoodsBase')->auction_info($act_id);
                if (isset($auction['last_bid']) && $auction['last_bid']['bid_user'] == $user_id && $auction['order_count'] == 0) {
                    $prompt[] = array(
                        'text' => sprintf(L('your_auction'), $row['goods_name'], $row['act_id']),
                        'add_time' => $row['end_time']
                    );
                }
            }
        }

        /* 排序 */
        $cmp = create_function('$a, $b', 'if($a["add_time"] == $b["add_time"]){return 0;};return $a["add_time"] < $b["add_time"] ? 1 : -1;');
        usort($prompt, $cmp);

        /* 格式化时间 */
        foreach ($prompt as $key => $val) {
            $prompt[$key]['formated_time'] = local_date(C('time_format'), $val['add_time']);
        }

        return $prompt;
    }

    /**
     *  获取用户评论
     *
     * @access  public
     * @param   int     $user_id        用户id
     * @param   int     $page_size      列表最大数量
     * @param   int     $start          列表起始页
     * @return  array
     */
    public function get_comment_list($user_id, $page_size, $start) {
        $sql = "SELECT c.*, g.goods_name AS cmt_name, r.content AS reply_content, r.add_time AS reply_time " .
                " FROM " . $this->pre . "comment AS c " .
                " LEFT JOIN " . $this->pre . "comment AS r " .
                " ON r.parent_id = c.comment_id AND r.parent_id > 0 " .
                " LEFT JOIN " . $this->pre . "goods AS g " .
                " ON c.comment_type=0 AND c.id_value = g.goods_id " .
                " WHERE c.user_id='$user_id' limit " . $start . ',' . $page_size;
        $res = $this->query($sql);

        $comments = array();
        $to_article = array();
        if (is_array($res)) {
            foreach ($res as $row) {
                $row['formated_add_time'] = local_date(C('time_format'), $row['add_time']);
                if ($row['reply_time']) {
                    $row['formated_reply_time'] = local_date(C('time_format'), $row['reply_time']);
                }
                if ($row['comment_type'] == 1) {
                    $to_article[] = $row["id_value"];
                }
                $comments[] = $row;
            }
        }

        if ($to_article) {
            $sql = "SELECT article_id , title FROM " . $this->pre . "article WHERE " . db_create_in($to_article, 'article_id');
            $arr = $this->query($sql);
            $to_cmt_name = array();
            foreach ($arr as $row) {
                $to_cmt_name[$row['article_id']] = $row['title'];
            }

            foreach ($comments as $key => $row) {
                if ($row['comment_type'] == 1) {
                    $comments[$key]['cmt_name'] = isset($to_cmt_name[$row['id_value']]) ? $to_cmt_name[$row['id_value']] : '';
                }
            }
        }

        return $comments;
    }

    /**
     * 记录帐户变动
     * @param   int     $user_id        用户id
     * @param   float   $user_money     可用余额变动
     * @param   float   $frozen_money   冻结余额变动
     * @param   int     $rank_points    贡献点数变动
     * @param   int     $pay_points     消费积分变动
     * @param   string  $change_desc    变动说明
     * @param   int     $change_type    变动类型：参见常量文件
     * @return  void
     */
    function log_account_change($user_id, $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = ACT_OTHER,$ky_type = '') {
		//预置部分
		$account_log = array(
			'user_id'       => $user_id,
			'user_money'    => $user_money,
			'own_money'     => $user_money,
			'divi_money'    => $user_money, 
			'frozen_money'  => $frozen_money,
			'rank_points'   => $rank_points,
			'pay_points'    => $pay_points,
			'change_time'   => gmtime(),
			'change_desc'   => $change_desc,
			'change_type'   => $change_type
		);
		//判断变更类型 如果为2，表示优先变更分成余额
		if($ky_type == 2){
			if(floatval($user_money) >= 0){			
				$account_log['own_money'] = '';
				$k_sql = " divi_money = divi_money + ('$user_money'),";
			}else{
				//查询用户的账户情况
				$sql = "SELECT * FROM " . $this->pre . "users " .
						" WHERE user_id='$user_id'";
				$res = $this->row($sql);
				if(floatval($res['divi_money']) >= floatval($user_money)*-1){				
					$account_log['own_money'] = '';
					$k_sql = " divi_money = divi_money + ('$user_money'),";
				}else{				
					$account_log['own_money'] = floatval($res['divi_money'])-floatval($user_money)*-1;
					$account_log['divi_money'] = floatval($res['divi_money'])*-1;
					$k_sql = " own_money = own_money + ('$account_log[own_money]'), divi_money = '0.00',";
				}
			}		
		}else{
			if(floatval($user_money) >= 0){			
				$account_log['divi_money'] = '';
				$k_sql = " own_money = own_money + ('$user_money'),";
			}else{
				//查询用户的账户情况
				$sql = "SELECT * FROM " . $this->pre . "users " .
						" WHERE user_id='$user_id'";
				$res = $this->row($sql);
				if(floatval($res['own_money']) >= floatval($user_money)*-1){				
					$account_log['divi_money'] = '';
					$k_sql = " own_money = own_money + ('$user_money'),";
				}else{				
					$account_log['own_money'] = floatval($res['own_money'])*-1;
					$account_log['divi_money'] = floatval($res['own_money'])-floatval($user_money)*-1;
					$k_sql = " divi_money = divi_money + ('$account_log[divi_money]'), own_money = '0.00',";
				}
			}		
		}
		$this->table = 'account_log';
        $this->insert($account_log);
		/* 更新用户信息 */
		$sql = "UPDATE " . $this->pre .
                "users SET user_money = user_money + ('$user_money')," .
				$k_sql . 
                " frozen_money = frozen_money + ('$frozen_money')," .
                " rank_points = rank_points + ('$rank_points')," .
                " pay_points = pay_points + ('$pay_points')" .
                " WHERE user_id = '$user_id' LIMIT 1";
        $this->query($sql);	
		
		
    }
	function log_account_change3($user_id, $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = ACT_OTHER,$ky_type = '') {
		//预置部分
		$account_log = array(
			'user_id'       => $user_id,
			'user_money'    => $user_money,
			'own_money'     => $user_money,
			'divi_money'    => $user_money, 
			'frozen_money'  => $frozen_money,
			'rank_points'   => $rank_points,
			'pay_points'    => $pay_points,
			'change_time'   => gmtime(),
			'change_desc'   => $change_desc,
			'change_type'   => $change_type
		);
		//判断变更类型 如果为2，表示优先变更分成余额
		if($ky_type == 2){
			if(floatval($user_money) >= 0){			
				$account_log['own_money'] = '';
				$k_sql = " divi_money = divi_money + ('$user_money'),";
			}else{
				//查询用户的账户情况
				$sql = "SELECT * FROM " . $this->pre . "users " .
						" WHERE user_id='$user_id'";
				$res = $this->row($sql);
				if(floatval($res['divi_money']) >= floatval($user_money)*-1){				
					$account_log['own_money'] = '';
					$k_sql = " divi_money = divi_money + ('$user_money'),";
				}else{				
					$account_log['own_money'] = floatval($res['divi_money'])-floatval($user_money)*-1;
					$account_log['divi_money'] = floatval($res['divi_money'])*-1;
					$k_sql = " own_money = own_money + ('$account_log[own_money]'), divi_money = '0.00',";
				}
			}		
		}else{
			if(floatval($user_money) >= 0){			
				$account_log['divi_money'] = '';
				$k_sql = " own_money = own_money + ('$user_money'),";
			}else{
				//查询用户的账户情况
				$sql = "SELECT * FROM " . $this->pre . "users " .
						" WHERE user_id='$user_id'";
				$res = $this->row($sql);
				if(floatval($res['own_money']) >= floatval($user_money)*-1){				
					$account_log['divi_money'] = '';
					$k_sql = " own_money = own_money + ('$user_money'),";
				}else{				
					$account_log['own_money'] = floatval($res['own_money'])*-1;
					$account_log['divi_money'] = floatval($res['own_money'])-floatval($user_money)*-1;
					$k_sql = " divi_money = divi_money + ('$account_log[divi_money]'), own_money = '0.00',";
				}
			}		
		}
		$this->table = 'account_log';
        $this->insert($account_log);
		/* 更新用户信息 */
		$sql = "UPDATE " . $this->pre .
                "users SET user_money = user_money + ('$user_money')," .
				$k_sql . 
                " frozen_money = frozen_money + ('$frozen_money')," .
                " rank_points = rank_points + ('$rank_points')," .
                " pay_points = pay_points + ('$pay_points')" .
                " WHERE user_id = '$user_id' LIMIT 1";
        $this->query($sql);	
		return $account_log;
		
		
    }
	/**
     * 记录帐户变动
     * @param   int     $user_id        用户id
     * @param   float   $user_money     可用余额变动
     * @param   float   $frozen_money   冻结余额变动
     * @param   int     $rank_points    贡献点数变动
     * @param   int     $pay_points     消费积分变动
     * @param   string  $change_desc    变动说明
     * @param   int     $change_type    变动类型：参见常量文件
     * @return  void
     */
    function log_account_change2($user_id, $user_money = 0, $frozen_money = 0, $gold_coin = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = ACT_OTHER,$ky_type = '') {
        //预置部分
		$account_log = array(
			'user_id'       => $user_id,
			'user_money'    => $user_money,
			'own_money'     => $user_money,
			'divi_money'    => $user_money, 
			'frozen_money'  => $frozen_money,
			'gold_coin'     => $gold_coin,
			'rank_points'   => $rank_points,
			'pay_points'    => $pay_points,
			'change_time'   => gmtime(),
			'change_desc'   => $change_desc,
			'change_type'   => $change_type
		);
		//判断变更类型 如果为2，表示优先变更分成余额
		if($ky_type == 2){
			if(floatval($user_money) >= 0){			
				$account_log['own_money'] = '';
				$k_sql = " divi_money = divi_money + ('$user_money'),";
			}else{
				//查询用户的账户情况
				$sql = "SELECT * FROM " . $this->pre . "users " .
						" WHERE user_id='$user_id'";
				$res = $this->row($sql);
				if(floatval($res['divi_money']) >= floatval($user_money)*-1){				
					$account_log['own_money'] = '';
					$k_sql = " divi_money = divi_money + ('$user_money'),";
				}else{				
					$account_log['own_money'] = floatval($res['divi_money'])-floatval($user_money)*-1;
					$account_log['divi_money'] = floatval($res['divi_money'])*-1;
					$k_sql = " own_money = own_money + ('$account_log[own_money]'), divi_money = '0.00',";
				}
			}		
		}else{
			if(floatval($user_money) >= 0){			
				$account_log['divi_money'] = '';
				$k_sql = " own_money = own_money + ('$user_money'),";
			}else{
				//查询用户的账户情况
				$sql = "SELECT * FROM " . $this->pre . "users " .
						" WHERE user_id='$user_id'";
				$res = $this->row($sql);
				if(floatval($res['own_money']) >= floatval($user_money)*-1){				
					$account_log['divi_money'] = '';
					$k_sql = " own_money = own_money + ('$user_money'),";
				}else{				
					$account_log['own_money'] = floatval($res['own_money'])*-1;
					$account_log['divi_money'] = floatval($res['own_money'])-floatval($user_money)*-1;
					$k_sql = " divi_money = divi_money + ('$account_log[divi_money]'), own_money = '0.00',";
				}
			}		
		}
		$this->table = 'account_log';
        $this->insert($account_log);
		/* 更新用户信息 */
		$sql = "UPDATE " . $this->pre .
                "users SET user_money = user_money + ('$user_money')," .
				$k_sql . 
                " frozen_money = frozen_money + ('$frozen_money')," .
				" gold_coin = gold_coin + ('$gold_coin')," .
                " rank_points = rank_points + ('$rank_points')," .
                " pay_points = pay_points + ('$pay_points')" .
                " WHERE user_id = '$user_id' LIMIT 1";
        $this->query($sql);
		
		
    }
	function log_account_change8($user_id, $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = ACT_OTHER,$ky_type = '',$other_userid = '',$bef_logid = '') {
        //预置部分
		$account_log = array(
			'user_id'       => $user_id,
			'other_userid'  => $other_userid,
			'user_money'    => $user_money,
			'own_money'     => $user_money,
			'divi_money'    => $user_money, 
			'frozen_money'  => $frozen_money,
			'rank_points'   => $rank_points,
			'pay_points'    => $pay_points,
			'change_time'   => gmtime(),
			'change_desc'   => $change_desc,
			'change_type'   => $change_type
		);
		//判断变更类型 如果为2，表示优先变更分成余额
		if($ky_type == 2){
			if(floatval($user_money) >= 0){			
				$account_log['own_money'] = '';
				$k_sql = " divi_money = divi_money + ('$user_money'),";
			}else{
				//查询用户的账户情况
				$sql = "SELECT * FROM " . $this->pre . "users " .
						" WHERE user_id='$user_id'";
				$res = $this->row($sql);
				if(floatval($res['divi_money']) >= floatval($user_money)*-1){				
					$account_log['own_money'] = '';
					$k_sql = " divi_money = divi_money + ('$user_money'),";
				}else{				
					$account_log['own_money'] = floatval($res['divi_money'])-floatval($user_money)*-1;
					$account_log['divi_money'] = floatval($res['divi_money'])*-1;
					$k_sql = " own_money = own_money + ('$account_log[own_money]'), divi_money = '0.00',";
				}
			}		
		}else{
			if(floatval($user_money) >= 0){
				if($change_type == '9' && !empty($bef_logid)){
					$sql = "SELECT * FROM " . $this->pre . "account_log " .
						" WHERE log_id='$bef_logid'";
					$res = $this->row($sql);
					$account_log['own_money'] = floatval($res['own_money'])*-1;
					$account_log['divi_money'] = floatval($res['divi_money'])*-1;
					$k_sql = " divi_money = divi_money + ('$account_log[divi_money]'), own_money = own_money + ('$account_log[own_money]'),";
				}else{
					$account_log['divi_money'] = '';
					$k_sql = " own_money = own_money + ('$user_money'),";
				}				
			}else{
				//查询用户的账户情况
				$sql = "SELECT * FROM " . $this->pre . "users " .
						" WHERE user_id='$user_id'";
				$res = $this->row($sql);
				if(floatval($res['own_money']) >= floatval($user_money)*-1){				
					$account_log['divi_money'] = '';
					$k_sql = " own_money = own_money + ('$user_money'),";
				}else{				
					$account_log['own_money'] = floatval($res['own_money'])*-1;
					$account_log['divi_money'] = floatval($res['own_money'])-floatval($user_money)*-1;
					$k_sql = " divi_money = divi_money + ('$account_log[divi_money]'), own_money = '0.00',";
				}
			}		
		}
		$this->table = 'account_log';
        $log_id = $this->insert($account_log);
		/* 更新用户信息 */
		$sql = "UPDATE " . $this->pre .
                "users SET user_money = user_money + ('$user_money')," .
				$k_sql . 
                " frozen_money = frozen_money + ('$frozen_money')," .
                " rank_points = rank_points + ('$rank_points')," .
                " pay_points = pay_points + ('$pay_points')" .
                " WHERE user_id = '$user_id' LIMIT 1";
        $this->query($sql);
		return $log_id;
		
    }
	
	//取消订单专用log
	function log_account_change9($user_id, $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '',$order_id = '') {
        $sql = "SELECT * FROM " . $this->pre . "account_log " .
			" WHERE user_id='$user_id' AND change_desc LIKE '%支付订单：" .	$order_id . "'";
		$res = $this->query($sql);
		foreach($res as $k=>$v){
			$account_log = array(
				'user_id'       => $user_id,
				'user_money'    => $v['user_money']*-1,
				'own_money'     => $v['own_money']*-1,
				'divi_money'    => $v['divi_money']*-1, 
				'change_time'   => gmtime(),
				'change_desc'   => $change_desc,
				'change_type'   => '99'
			);
			$this->table = 'account_log';
			$this->insert($account_log);
			/* 更新用户信息 */
			$sql = "UPDATE " . $this->pre .
					"users SET user_money = user_money - ('$v[user_money]')," .
					" own_money = own_money - ('$v[own_money]')," .
					" divi_money = divi_money - ('$v[divi_money]')" .
					" WHERE user_id = '$user_id' LIMIT 1";
			$this->query($sql);
		}
    }
	
	//插入到ab_log表里
	function log_ab_change($order_id) {
		//根据order_id获得产品数量、所需代金券、价格
		$goods_info = $this->get_goods_info($order_id);
		if($goods_info)	{
			$affiliate = unserialize($this->get_shop_config_value('affiliate'));
			$level_money_all = $affiliate['config']['level_money_all'];
			foreach($goods_info as $k=>$v){
				$ab['all_pay_money'] = floatval($v['goods_amount'])+floatval($v['shipping_fee'])-floatval($v['integral_money']);
				$ab['user_id'] = $v['user_id'];
				$ab['goods_id'] = $v['goods_id'];
				$ab['goods_name'] = $v['goods_name'];
				$ab['goods_sn'] = $v['goods_sn'];
				$ab['goods_number'] = $v['goods_number'];
				$ab['goods_price'] = $v['goods_price'];
				$ab['pay_integral_money'] = floatval($v['integral'])*intval($v['goods_number']);
				$ab['order_id'] = $order_id;
				$ab['order_sn'] = $v['order_sn'];
				$ab['good_cost'] = floatval($v['good_cost'])*intval($v['goods_number']);
				$ab['op_cost'] = floatval($v['op_cost'])*intval($v['goods_number']);
				$ab['other_cost'] = floatval($v['other_cost'])*intval($v['goods_number']);
				$ab['shipping_fee'] = 0;
				if($k==0 && $v['shipping_fee']<>0){
					$ab['shipping_fee'] = floatval($v['shipping_fee']);
				}
				$ab['pay_goods_money'] = (floatval($v['goods_price']) - floatval($v['integral'])) * intval($v['goods_number']);
				$ab['profit_share'] = floatval($level_money_all)/100 * $ab['pay_goods_money'];
				$ab['profit'] = $ab['pay_goods_money']-$ab['good_cost']-$ab['op_cost']-$ab['other_cost']-$ab['profit_share'];
				$ab['change_time'] = gmtime();
				$this->table = 'ab_log';
				$this->insert($ab);
			}
		}
    }
	//根据order_id获得产品数量、所需代金券、价格
	function get_goods_info($order_id){		
		$sql = "SELECT o.goods_id,o.goods_name,o.goods_sn,o.goods_number,o.goods_price, g.integral,g.give_cash, g.good_cost, g.op_cost, g.other_cost,f.order_id,f.order_sn,f.goods_amount,f.shipping_fee,f.integral_money,f.confirm_time,f.user_id " .
                " FROM " . $this->pre . "order_goods AS o " .
                " LEFT JOIN " . $this->pre . "goods AS g " .
                " ON o.goods_id = g.goods_id " .
				" LEFT JOIN " . $this->pre . "order_info AS f " .
                " ON o.order_id = f.order_id " .
                " WHERE o.order_id='$order_id'";
        return $this->query($sql);
		
	}
	
	//更新用户任务明细
	function log_task($order_id){
		//根据order_id获得产品数量、所需代金券、价格
		$user_order_info = $this->get_order_and_user_info($order_id);
		if($user_order_info['order_amount'] == '0.00'){
			$task_value = floatval($user_order_info['goods_amount']) - floatval($user_order_info['integral_money']);
			/*   下面是给自己添加任务流水  */
			if($user_order_info['user_rank'] == '138' || $user_order_info['user_rank'] == '144'){
				//给购买人添加任务流水
				$task['user_id'] = $user_order_info['user_id'];
				$task['task_value'] = $task_value;
				$task['order_sn'] = $user_order_info['order_sn'];
				$task['is_self'] = 1;
				$task['change_time'] = gmtime();
				$this->table = 'task_log';
				$t_id = $this->insert($task);
				if($t_id){
					//更新用户任务额
                    $sql = "SELECT s_id FROM " .$this->pre .
                            "user_stock WHERE status = 0 AND user_id = '$task[user_id]'";
                    $res = $this->row($sql);
                    if($res){
    					$sql = "UPDATE " . $this->pre .
    							"user_stock SET now_assess_value = now_assess_value + ('$task_value') " .
    							" WHERE s_id = '$res[s_id]' LIMIT 1";
    					$this->query($sql);
                    }
				}
			}else{
                /*  下面是更新上级的流水    */    
                if($user_order_info['parent_id'] != 0){
                    $up_user_rankid = $this->get_user_rank($user_order_info['parent_id']);
                    if($up_user_rankid == '138' || $up_user_rankid == '144'){
                        //给购买人的上级添加任务流水
                        $task['user_id'] = $user_order_info['parent_id'];
                        $task['task_value'] = $task_value;
                        $task['order_sn'] = $user_order_info['order_sn'];
                        $task['is_self'] = 0;
                        $task['down_user_id'] = $user_order_info['user_id'];
                        $task['change_time'] = gmtime();
                        $this->table = 'task_log';
                        $t_id = $this->insert($task);
                        if($t_id){
                            //更新用户任务额
                            $sql = "SELECT s_id FROM " .$this->pre .
                                    "user_stock WHERE status = 0 AND user_id = '$task[user_id]'";
                            $res = $this->row($sql);
                            if($res){
                                $sql = "UPDATE " . $this->pre .
                                        "user_stock SET now_assess_value = now_assess_value + ('$task_value') " .
                                        " WHERE s_id = '$res[s_id]' LIMIT 1";
                                $this->query($sql);
                            }
                        }
                    }
                }

            }		
			
		}
	}
	
	//根据order_id获得订单信息及用户信息
	function get_order_and_user_info($order_id){
		$sql = "SELECT o.user_id,o.order_sn,o.goods_amount,o.shipping_fee,o.order_amount,o.integral_money, u.parent_id,u.user_rank  " .
                " FROM " . $this->pre . "order_info AS o " .
                " LEFT JOIN " . $this->pre . "users AS u " .
                " ON o.user_id = u.user_id " .
                " WHERE o.order_id='$order_id'";
        return $this->row($sql);
	}
    /**
     * 获取第三方登录配置信息 
     * @param type $type
     * @return type
     */
    function get_third_user_info($type) {
        $sql = "SELECT auth_config FROM " . $this->pre . "touch_auth WHERE `from` = '$type'";
        $info = $this->row($sql);
        if ($info) {
            $user = unserialize($info['auth_config']);
            $config = array();
            foreach ($user as $key => $value) {
                $config[$value['name']] = $value['value'];
            }
            return $config;
        }
    }
	//获取用户等级信息  $type=2获取等级名字
	public function get_user_rank($user_id,$type=''){
		/* 获取会员贡献点数 */
		$sql = "SELECT `user_id` , `rank_points` , `user_rank` FROM " . $this->pre ."users WHERE `user_id` = ".$user_id;
		$res = $this->row($sql);		
		
		
		$sql2 = "SELECT `rank_id` , `rank_name` , `min_points` , `max_points` , `special_rank` FROM " .$this->pre ."user_rank";
		$rank_info = $this->query($sql2);
		
		//判断是否为特殊等级
		foreach($rank_info as $k=>$v){
			if($res['user_rank']== $v['rank_id'] && $v['special_rank']==1){
				if($type==2){
					return $v['rank_name'];
				}else{	
					return $v['rank_id'];
				}	
			}
		}
		
		foreach($rank_info as $k=>$v){
			if($res['rank_points'] <= $v['max_points'] && $res['rank_points'] >= $v['min_points']){			
				if($type==2){
					return $v['rank_name'];
				}else{	
					return $v['rank_id'];
				}
			}
		}
	}
	//获取订单状态
	public function get_order_status($status){
		switch ($status) {
		   case 0:
			 return "未付款";
			 break;
		   case 1:
			 return "已付款";
			 break;
		   case 2:
			 return "已取消";
			 break;
		   case 3:
			 return "无效";
			 break;
		   case 5:
			 return "已付款";
			 break; 
		   default:
			 return "未确认";
		}
	}
	//获取用户的微信头像和昵称
	public function get_touxiang($uid,$type = ''){
		$sql = 'SELECT headimgurl,nickname FROM ' . $this->pre . "wechat_user WHERE ect_uid = " . $uid ;
		$res = $this->query($sql);
		if($type==1){
			return $res[0]['headimgurl'];
		}elseif($type==2){
			return $res[0]['nickname'];
		}else{
			return $res;
		}	
	}
	//获取用户表的头像和昵称
	public function get_user_biao($uid){
		$sql = 'SELECT u_headimg,nicheng FROM ' . $this->pre . "users WHERE user_id = " . $uid ;
		$res = $this->query($sql);
		return $res;
	}
	//获取用户的username
	public function get_user_name($uid){
		$sql = 'SELECT user_name FROM ' . $this->pre . "users WHERE user_id = " . $uid ;
		$res = $this->row($sql);
		return $res['user_name'];
	}
	//获取用户的头像或者昵称(type=1时返回头像，2返回昵称，3返回头像和昵称)2016-3-13	
	public function get_user_nc_tx($uid,$type = ''){
		$sql = 'SELECT u_headimg,nicheng,user_name FROM ' . $this->pre . "users WHERE user_id = " . $uid ;
		$res = $this->row($sql);
		$sql = 'SELECT headimgurl,nickname FROM ' . $this->pre . "wechat_user WHERE ect_uid = " . $uid ;
		$res2 = $this->row($sql);
		if($type==1){
			if($res['u_headimg']){
				return $res['u_headimg'];
			}else{
				return $res2['headimgurl'];
			}
		}elseif($type==2){
			if($res['nicheng']){
				return $res['nicheng'];
			}elseif($res2['nickname']){
				return $res2['nickname'];
			}else{
				return $res['user_name'];
			}
		}else{
			if($res['u_headimg']){
				$res3['headimg'] = $res['u_headimg'];
			}else{
				$res3['headimg'] = $res2['headimgurl'];
			}
			if($res['nicheng']){
				$res3['nicheng'] = $res['nicheng'];
			}else{
				$res3['nicheng'] = $res2['nickname'];
			}
			return $res3;
		}
	}
	//将用户的头像插入user表
	public function add_u_headimg($headimg,$u_id){
		$sql = "UPDATE " . $this->pre .
				"users SET u_headimg = '" . $headimg .				
				"' WHERE user_id = " . $u_id;
		$res=$this->query($sql);
		if($res){
			return $res;
		}else{
			return 0;
		}
	}
	//将用户的昵称插入user表
	public function add_nicheng($nicheng,$u_id){
		$sql = "UPDATE " . $this->pre .
				"users SET nicheng = '" . $nicheng .				
				"' WHERE user_id = " . $u_id;
		$res=$this->query($sql);
		if($res){
			return $res;
		}else{
			return 0;
		}
	}
	//把用户的推荐码插入推荐码表
	public function add_re_code($user_id,$re_code = ''){
		if(empty($re_code)){
			$re_code=encodeId($user_id);
		}
		$sql = 'SELECT user_id,re_code FROM ' . $this->pre . "re_code WHERE user_id = " . $user_id ;
		$res = $this->row($sql);
		if($res){
			return $res['re_code'];
		}else{
			$this->table = 're_code';
			$data['user_id'] = $user_id;
			$data['re_code'] = $re_code;
			$this->insert($data);
			return $re_code;
		}
	}
	//检查推荐码是否存在
	public function check_is_re_code($re_code){
		$sql = 'SELECT user_id,re_code FROM ' . $this->pre . "re_code WHERE re_code = '" . $re_code ."'" ;
		$res = $this->row($sql);
		if($res){
			return $re_code;
		}
	}
	
	
	/**
	 * 取得订单信息
	 * @param   int     $order_id   订单id（如果order_id > 0 就按id查，否则按sn查）
	 * @param   string  $order_sn   订单号
	 * @return  array   订单信息（金额都有相应格式化的字段，前缀是formated_）
	 */
	public function order_info($order_id, $order_sn = '')
	{
		/* 计算订单各种费用之和的语句 */
		$total_fee = " (goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee ";
		$order_id = intval($order_id);
		if ($order_id > 0)
		{
			$sql = "SELECT *, " . $total_fee . " FROM " . $this->pre . "order_info WHERE order_id = " . $order_id;
		}
		else
		{
			$sql = "SELECT *, " . $total_fee . " FROM " . $this->pre . "order_info WHERE order_sn = " . $order_sn;
		}
		$order = $this->row($sql);
	
		/* 格式化金额字段 */
		if ($order)
		{
			$order['formated_goods_amount']   = price_format($order['goods_amount'], false);
			$order['formated_discount']       = price_format($order['discount'], false);
			$order['formated_tax']            = price_format($order['tax'], false);
			$order['formated_shipping_fee']   = price_format($order['shipping_fee'], false);
			$order['formated_insure_fee']     = price_format($order['insure_fee'], false);
			$order['formated_pay_fee']        = price_format($order['pay_fee'], false);
			$order['formated_pack_fee']       = price_format($order['pack_fee'], false);
			$order['formated_card_fee']       = price_format($order['card_fee'], false);
			$order['formated_total_fee']      = price_format($order['total_fee'], false);
			$order['formated_money_paid']     = price_format($order['money_paid'], false);
			$order['formated_bonus']          = price_format($order['bonus'], false);
			$order['formated_integral_money'] = price_format($order['integral_money'], false);
			$order['formated_surplus']        = price_format($order['surplus'], false);
			$order['formated_order_amount']   = price_format(abs($order['order_amount']), false);
			//$order['formated_add_time']       = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);
		}
	
		return $order;
	}
	/**
	 * 取得某订单应该赠送的积分数
	 * @param   array   $order  订单
	 * @return  int     积分数
	 */
	public function integral_to_give($order_id)
	{
		
		$sql = "SELECT SUM(og.goods_number * IF(g.give_integral > -1, g.give_integral, og.goods_price)) AS custom_points, SUM(og.goods_number * IF(g.rank_integral > -1, g.rank_integral, og.goods_price)) AS rank_points " .
				"FROM " . $this->pre ."order_goods AS og, " .
						  $this->pre ."goods AS g " .
				"WHERE og.goods_id = g.goods_id " .
				"AND og.order_id = '$order_id' " .
				"AND og.goods_id > 0 " .
				"AND og.parent_id = 0 " .
				"AND og.is_gift = 0 AND og.extension_code != 'package_buy'";

		return $this->row($sql);
	}
	//获取付款log信息
	public function get_order_id($log_id){
		$sql = "SELECT * FROM " . $this->pre .
				"pay_log WHERE log_id = " . $log_id;
		return $this->row($sql);
	}
	
	//获取单人的赚取的总红包
	public function get_hb_count($user_id){
		$sql = "SELECT sum(gold_coin) as hb FROM " . $this->pre .
				"account_log WHERE gold_coin > 0 and user_id = " . $user_id;
		return $this->row($sql);
	}
	
	//更新用户的上级id
	public function update_upid($user_id,$up_id){
		if($up_id=='0'){
			return;
		}
		$this->table = 'users';
        $data['parent_id'] = $up_id;
        $condition['user_id'] = $user_id;
        $this->update($condition, $data);
	}
	
	//检查推荐码id
	public function check_upid($upid,$user_id){
		if($upid==$user_id){
			return "此推荐码无效，请返回重新填写！";
		}
        $sql = "SELECT * FROM " . $this->pre . "users WHERE user_id = " .$upid;    
        $rs = $this->row($sql);
        if(empty($rs)){
            return '此推荐码无效，请返回重新填写！';
		}else{
			$re_code=encodeId($upid);
			$t_re_code=$this->add_re_code($upid,$re_code);
			return;
		}	
	}
	//插入扫描推荐的信息
	public function add_qcode($user_id,$nickname){
		$wxinfo = get_def_config();
		$sql = "SELECT * FROM " . $this->pre .
				"wechat_qrcode WHERE scene_id = " . $user_id . " and wechat_id = " . $wxinfo['id'];
		$rs = $this->row($sql);
		if($rs){
			return $rs['id'];
		}else{
			$wxinfo = get_def_config();
			$data['wechat_id'] = $wxinfo['id'];
			$data['scene_id'] = $user_id;
			$data['username'] = $nickname;
			$data['expire_seconds'] = 0;
			$data['function'] = "扫码引荐";
			$data['status'] = 1;
			$data['type'] = 1;
			$this->table = 'wechat_qrcode';
			$count = $this->insert($data);
			return $count;
		}	
	}
	//获取用户申请代理的订单状态		//1=真实姓名  2=提交时间
	public function get_apply_status($user_id){
		$sql = "SELECT * FROM " . $this->pre .
				"apply_list WHERE user_id = " . $user_id . " and is_apply = 0 and is_del = 0 ORDER BY id";
		return $this->row($sql);		
	}
	//获取用户最近一条未付款的申请代理的订单
	public function get_last_apply($user_id){
		$sql = "SELECT * FROM " . $this->pre .
				"apply_list WHERE user_id = " . $user_id . " and is_apply = 0 AND pay_status = 0 and is_del = 0 ORDER BY id";
		return $this->row($sql);		
	}
	//删除指定的代理申请
	public function del_last_apply($id){
		$this->table = 'apply_list';
		$data['is_del'] = 1;
        $condition['id'] = $id;
        $this->update($condition, $data);
	}
	//获取用户申请代理订单的信息
	public function get_daili_info($user_id,$type){
		$sql = "SELECT * FROM " . $this->pre .
				"apply_list WHERE is_del = 0 AND pay_status = 1 AND is_apply =1 AND user_id = " . $user_id ." ORDER BY id LIMIT 0,1";
		$arr = $this->row($sql);	
		if($type ==1){
			return $arr['tname'];
		}elseif($type==2){
			return date('Y-m-d',$arr['time']);
		}elseif($type==3){
			return $arr['heyue_url'];
		}else{		
			return $arr;
		}			
	}
	//获取用户的微信信息
	public function get_user_wechatinfo($u_id){
		$wxinfo = get_def_config();
		if($wxinfo){	
			$sql = "SELECT * FROM " . $this->pre .
					"wechat_user WHERE ect_uid = " . $u_id . " and wechat_id = " . $wxinfo['id'];
		}else{
			return 0;
		}	
		return $this->row($sql);
	}
	
	//获取没有翻倍时间并且银币不等于0的用户信息
	public function get_double_user_info(){
		$sql = "SELECT user_id,pay_points FROM " . $this->pre . "users WHERE double_time = 0 and pay_points <> 0";
		return $this->query($sql);
	}
	//获取那些有double_time，但是没有next_double_time的用户,临时使用
	public function get_double_user_info_linshi(){
		$sql = "SELECT user_id,pay_points,double_time FROM " . $this->pre . "users WHERE double_time != 0 and next_double_time = 0";
		return $this->query($sql);
	}
	//获取有翻倍时间并且银币不等于0的用户信息
	public function get_double_user_info2($time){
		$sql = "SELECT user_id,pay_points,next_double_time,next_double_beishu FROM " . $this->pre . "users WHERE next_double_time <= " . $time . " and pay_points <> 0";
		return $this->query($sql);
	}
	
	//获取指定用户的第一次银币交易记录
	public function get_user_first_pay_points($user_id){
		$sql = "SELECT MIN(log_id) as old_log_id FROM " . $this->pre . "account_log WHERE user_id = " . $user_id . " and pay_points <> 0";
		$result = $this->row($sql);		
		if($result['old_log_id'] == ''){
			return gmtime();
		}else{
			$sql2 = "SELECT change_time FROM " . $this->pre . "account_log WHERE log_id = " . $result[old_log_id];
			$result2 = $this->row($sql2);	
			return $result2['change_time'];			
		}
	}
	//计算翻倍时间的秒数和倍数
	public function next_double_time(){	
		$sc =array();	
		$this->table = 'shop_config';
        $condition['code'] = "double_time";
        $sc['double_time'] = $this->field('value', $condition);
		$condition['code'] = "d_beishu";
		$sc['d_beishu'] = $this->field('value', $condition);
		
		return $sc;
	}
	
	//获取用户下次翻倍的时间
	public function get_user_next_double_time($user_id){
		$sql = 'SELECT value FROM ' . $this->pre . 'shop_config WHERE code = "double_time"';		
		$result = $this->row($sql);
		
		$sql2 = 'SELECT double_time FROM ' . $this->pre . 'users WHERE user_id = ' . $user_id;		
		$user_info = $this->row($sql2);
		if($user_info['double_time'] != 0){
			$next_time = floatval($result['value'])*86400 + $user_info['double_time'];
			return local_date(C('time_format'), $next_time);
		}else{
			return 0;
		}	
	}	
	
	//写入users表，用户的首次时间和下次时间，还有倍数
	public function update_user_double_time($user_id,$double_time){
		$sc = $this->next_double_time();
		$next_double_time = (int)$sc['double_time']*86400+(int)$double_time;
		$this->table = 'users';
        $data['double_time'] = $double_time;
		$data['next_double_time'] = $next_double_time;
		$data['next_double_beishu'] = $sc['d_beishu'];
        $condition['user_id'] = $user_id;
        $this->update($condition, $data);
	}
	//log翻倍记录，并更新翻倍次数
	public function log_double_pay_points($user_id,$pay_point,$d_beishu){
		$this->table = 'user_double_paypoints';
        $data['user_id'] = $user_id; 
        $data['old_paypoint'] = $pay_point;
        $data['new_paypoint'] = $pay_point*$d_beishu;
        $data['change_time'] = gmtime();
        $this->insert($data);
		
		$this->update_user_double_time($user_id,gmtime());
	}
	
	//获取所有积分超过500万的用户
	public function get_spree_user(){
		$sql = "SELECT user_id,pay_points FROM " . $this->pre . "users WHERE pay_points >= 5000000";
		return $this->query($sql);
	}
	
	//自动兑换500万积分到大礼包
	public function exchange_spree($user_id){
		$this->table = 'user_spree';
        $data['user_id'] = $user_id;
        $data['spree_sn'] = "LB".get_order_sn();
        $data['ex_time'] = gmtime();
        return $this->insert($data);
	}
	//生成礼包编号
	/*
	public function get_lb_sn(){
		do {
			$spree_sn = "LB".get_order_sn();
			$sql = "SELECT id FROM " . $this->pre . "user_spree WHERE spree_sn = '" . $spree_sn ."'";	
			$rs = $this->row($sql);	
			if($rs){
				$err_no = 0;
			}else{
				$err_no = 1;
			}
			
		} while ($err_no == 1);
		
		return $spree_sn;
	}
	*/
	//获取礼包信息
	public function get_spree_price($id,$user_id){
		$sql = "SELECT * FROM " . $this->pre . "user_spree WHERE is_check = 1 AND check_time <> 0 AND is_del = 0 AND is_sell = 0 AND id = " . $id . " AND user_id = " .$user_id;	
		$rs = $this->row($sql);
		if(empty($rs)){
			return false;
		}else{
			$spree_year =(int)date('Y',$rs['check_time']);
			$spree_month =(int)date('m',$rs['check_time']);
			$sql = "SELECT * FROM " . $this->pre . "spree_price WHERE is_del = 0 AND year = '" . $spree_year . "' AND month = '" .$spree_month."'";
			$price_rs = $this->row($sql);
			if(empty($price_rs)){
				return false;
			}else{
				$result['spree_id'] = $rs['id'];
				$result['spree_sn'] = $rs['spree_sn'];
				$result['spree_year'] = $spree_year;
				$result['spree_month'] = $spree_month;
				$result['spree_price'] = $price_rs['price'];
				return $result;
			}
		}
		
	}
	//售前检查礼包
	public function check_spree($id,$user_id){
		$sql = "SELECT id FROM " . $this->pre . "user_spree WHERE is_check = 1 AND check_time <> 0 AND is_del = 0 AND is_sell = 0 AND id = " . $id . " AND user_id = " .$user_id;	
		return $this->row($sql);
		
	}
	//取消出售时检查礼包
	public function check_spree1($id,$user_id){
		$sql = "SELECT * FROM " . $this->pre . "user_spree WHERE is_check = 1 AND check_time <> 0 AND is_del = 0 AND is_sell = 1 AND id = " . $id . " AND user_id = " .$user_id;	
		return $this->row($sql);
		
	}
	//查看驳回时检查礼包
	public function check_spree3($id,$user_id){
		$sql = "SELECT * FROM " . $this->pre . "user_spree WHERE is_check = 1 AND check_time <> 0 AND is_del = 0 AND is_sell = 3 AND id = " . $id . " AND user_id = " .$user_id;	
		return $this->row($sql);
		
	}
	//取消购买礼包时检查礼包
	public function check_buy_spree($tr_id,$user_id){
		$sql = "SELECT tr_id FROM " . $this->pre . "spree_log WHERE status = 0 AND tr_id = " . $tr_id . " AND user_id = " .$user_id;	
		return $this->row($sql);
	}
	
	//根据年月获取礼包指导价
	public function get_zd_price($year,$month){
		$sql = "SELECT * FROM " . $this->pre . "spree_price WHERE is_del = 0 AND year = '" . $year . "' AND month = '" .$month."'";
		return $this->row($sql);
	}
	
	//获取用户的礼包数量
	public function get_user_spree_count($user_id,$type=''){
		$this->table = 'user_spree';
        $condition['user_id'] = $user_id;
		if($type==1){
			$condition['is_check'] = 0;
		}elseif($type==2){
			$condition['is_check'] = 1;
			
		}else{
		
		}
		$condition['is_del'] = 0;
        return $this->count($condition);
	}
	
	//根据用户名获取用户的姓名
	public function get_user_tname($user_name){
		$sql = 'SELECT tname,user_id FROM ' . $this->pre . 'users WHERE user_name = "' . $user_name . '"';		
		return $this->row($sql);
	}
	//根据手机号获取用户的姓名
	public function get_user_tname2($user_phone){
		$sql = 'SELECT tname,user_id FROM ' . $this->pre . 'users WHERE mobile_phone = "' . $user_phone . '"';		
		return $this->row($sql);
	}
	
	//获取用的提现银行信息
	public function get_user_raply_info($user_id){
		$sql = 'SELECT raply_bank,raply_kaihu,raply_username,raply_number FROM ' . $this->pre . 'users WHERE user_id = "' . $user_id . '"';		
		return $this->row($sql);
	}
	//获取用户的代理区域
	public function get_proxy_area($user_id){
		$sql = 'SELECT proxy_area_id FROM ' . $this->pre . 'users WHERE user_id = "' . $user_id . '"';		
		$area_id = $this->row($sql);
		if($area_id['proxy_area_id'] == 0){
			return 0;
		}else{
			$sql = 'SELECT region_name FROM ' . $this->pre . 'region WHERE region_id = "' . $area_id[proxy_area_id] . '"';
			$region_name = $this->row($sql);
			return 	$region_name['region_name'];
		}
		
	}
	//访问数+1
	public function add_click($v = ''){
		$sql = 'SELECT value FROM ' . $this->pre . 'shop_config WHERE code = "click_number"';		
		$result = $this->row($sql);
		if($v){
			return $result['value'];
		}else{	
			$value = floatval($result['value']) + 1;
			$sql = "UPDATE " . $this->pre .
					"shop_config SET value = " . $value .                
					" WHERE code = 'click_number'";
			$this->query($sql);
		}	
		
		/*
		$sql = "UPDATE " . $this->pre .
                "shop_config SET value = value + 1" .                
                " WHERE code = click_number";
        $this->query($sql);
		*/
	}
	
	//获取指定时间内的纯利润
	public function get_Yesterday_profit($beginTime,$endTime){
		$sql = "SELECT SUM(profit) as all_profit,COUNT(k_id) as order_number FROM " . $this->pre . "ab_log WHERE change_time >= '" . $beginTime . "' AND change_time <= '" . $endTime . "'";
		return $this->row($sql);			
	}
	//获取指定时间内的纯利润(临时)
	public function get_before_profit($endTime){
		$sql = 'SELECT m_id,tj_time FROM ' . $this->pre . 'ac_log ORDER BY m_id DESC LIMIT 0,1';
		$result = $this->row($sql);
		//logg($result);
		$sql = "SELECT SUM(profit) as all_profit,COUNT(k_id) as order_number FROM " . $this->pre . "ab_log WHERE change_time >= '" . $result['tj_time'] . "' AND change_time <= '" . $endTime . "'";
		return $this->row($sql);			
	}
	//插入到市值流水并增加市值和纯利润
	public function add_ac_log($all_profit,$pe_ratios,$endYesterday){
		logg("add_ac_log被执行了");
		$this->table = 'ac_log';
        $condition['tj_time'] = $endYesterday;
        $m_id = $this->field('m_id', $condition);
		if(empty($m_id)){
			$data['tj_time'] = $endYesterday;
			$data['order_number'] = $all_profit['order_number'];
			$data['add_profit'] = $all_profit['all_profit'];
			$data['pe_ratios'] = $pe_ratios['value'];
			$data['add_market_value'] = floatval($all_profit['all_profit']) * floatval($pe_ratios['value']);
			
			
			
			$sql = 'SELECT value FROM ' . $this->pre . 'shop_config WHERE code = "market_value"';		
			$market_value = $this->row($sql);
			$new_market_value = floatval($market_value['value'])+floatval($data['add_market_value']);
			
			$this->change_shop_value('market_value',$data['add_market_value'],1);
			/*
			//累加上新增的市值
			$sql = "UPDATE " . $this->pre .
				"shop_config SET value = " . $new_market_value . 
				" WHERE code = 'market_value'";
			$this->query($sql);
			*/
			
			
			$sql = 'SELECT value FROM ' . $this->pre . 'shop_config WHERE code = "pure_profit"';		
			$pure_profit = $this->row($sql);
			$new_pure_profit = floatval($pure_profit['value'])+floatval($data['add_profit']);
			
			$this->change_shop_value('pure_profit',$data['add_profit'],1);
			/*
			//累加上新增的纯利润
			$sql = "UPDATE " . $this->pre .
				"shop_config SET value = " . $new_pure_profit .                
				" WHERE code = 'pure_profit'";
			$this->query($sql);
			*/
			
			
			$sql = 'SELECT value FROM ' . $this->pre . 'shop_config WHERE code = "no_dis_profit"';		
			$no_dis_profit = $this->row($sql);
			$new_no_dis_profit = floatval($no_dis_profit['value'])+floatval($data['add_profit']);	
			
			$this->change_shop_value('no_dis_profit',$data['add_profit'],1);
			/*
			//累加上新增的纯利润
			$sql = "UPDATE " . $this->pre .
				"shop_config SET value = " . $new_no_dis_profit .                
				" WHERE code = 'no_dis_profit'";
			$this->query($sql);
			*/
				
			$data['now_all_profit']	= $new_pure_profit;
			$data['now_market_value'] = $new_market_value;
			$data['now_no_dis_profit'] = $new_no_dis_profit;
        	$this->insert($data);	
			
			//写入到利润流水里
			$info = "纯利润结算";
			$this->log_profit($all_profit['all_profit'],0,$info);	
		}		
	}
	
	//获取需要结算任务的用户资料
	function get_user_assess($time){
		$sql = "SELECT s_id,user_id,user_premium,premium_time,next_assess_time,next_assess_value,now_assess_value,ktimes,stock FROM " . $this->pre . "user_stock WHERE status = 0 AND next_assess_time <> 0 AND next_assess_time <= " . $time;
		return $this->query($sql);
	}
	
	//对用户进行通过任务考核操作
	function log_task_change($s_id,$user_id,$next_assess_value,$now_assess_value){
        $month = (int)date("m",gmtime()+28800);
        $year = (int)date("Y",gmtime()+28800);
		$assess_value = $this->get_shop_config_value('assess_value');
        $next_assess_time = mktime(0,0,0,$month+1,1,$year);
        $year_assess_time = explode('-',$this->get_shop_config_value('year_assess_time'));
		$year_assess_value = ((intval($year_assess_time[0])-$year)*12+intval($year_assess_time[1])-$month)*floatval($assess_value);
		
		$this->table = 'user_stock';
		$data['next_assess_value'] = $assess_value;
		$data['now_assess_value'] = floatval($now_assess_value) - floatval($next_assess_value);
		$data['next_assess_time'] = $next_assess_time;
        $data['before_assess_time'] = gmtime();
        $data['year_assess_value'] = $year_assess_value;
        $condition['s_id'] = $s_id;
        $rs = $this->update($condition, $data);
		
		if($rs){
			$task['s_id'] = $s_id;
            $task['user_id'] = $user_id;
			$task['task_value'] = floatval($next_assess_value)*-1;
			$task['task_type'] = 1;
			$task['is_self'] = 1;
			$task['change_time'] = gmtime();
			$this->table = 'task_log';
			$t_id = $this->insert($task);
		}		
	}
	//对用户进行降级除权操作
	function down_user_lev($s_id,$user_id,$user_premium,$premium_time,$next_assess_value,$now_assess_value,$stock,$ktimes){
		//清理用户数据并把入股金+利息退回
		$day = floor((intval(gmtime())-intval($premium_time))/86400);		//用户总在股天数
		$day_interest = $this->get_shop_config_value('day_interest');		//每日利息
		
		$this->table = 'user_stock';
		$data['next_assess_value'] = 0.00;
		$data['now_assess_value'] = 0.00;
		$data['next_assess_time'] = 0;
		$data['user_premium'] = 0.00;
		$data['before_assess_time'] = gmtime();
		$data['status'] = 2;	
		
        $condition['s_id'] = $s_id;
        $rs = $this->update($condition, $data);
		
		$this->log_stock($s_id,$user_id,$stock*-1,'0.000000','',"考核失败",4);

        //修改用户资料
        $frozen_premium = $day * floatval($day_interest) + floatval($user_premium);
        if($ktimes == 1){
            $yuju = " user_rank = 0";
        }else{
			$done_times = intval($ktimes) -1;
			$yuju = " done_stock = '".$done_times."'";
		}
        $sql = "UPDATE " . $this->pre .
                "users SET frozen_premium = frozen_premium + ('$frozen_premium')," .
                 $yuju.
                " WHERE user_id = '$user_id' LIMIT 1";
        $this->query($sql);
        
		
		//清理任务数据
		if($rs){
			$task['s_id'] = $s_id;
            $task['user_id'] = $user_id;
			$task['task_value'] = floatval($now_assess_value)*-1;
			$task['task_type'] = 2;
			$task['is_self'] = 1;
			$task['change_time'] = gmtime();
			$this->table = 'task_log';
			$t_id = $this->insert($task);
		}
		//减去总入股金
		$this->change_shop_value('all_premium',$user_premium,3);
		//记录总入股金的变动流水
		$map['s_id'] = $s_id;
        $map['user_id'] = $user_id;
        $map['user_premium'] = floatval($user_premium)*-1;
        $map['beizhu'] = "考核失败";
        $map['premium_type'] = 1;
        $map['premium_time'] = gmtime();
        $this->table = 'ad_log';
        $this->insert($map);
		//利息从未分配利润里减去
		$this->change_shop_value('no_dis_profit',$day * floatval($day_interest),3);
		//写入到流水
		$info = $s_id."用户ID(".$user_id.")退股退息";
		$this->log_profit($day * floatval($day_interest)*-1,2,$info);
		
		//写入到退股记录
		$ky['s_id'] = $s_id;
		$ky['user_id'] = $user_id;
		$ky['stock'] = $stock;
		$ky['premium_value'] = $user_premium;
		$ky['premium_time'] = $premium_time;
		$ky['day_interest'] = $day_interest;
		$ky['days'] = $day;
		$ky['all_interest'] = $day * floatval($day_interest);
		$ky['all_value'] = floatval($user_premium)+ $ky['all_interest'];
		$ky['time'] = gmtime();
		$this->table = 'tg_log';
        $this->insert($ky);
		
	}
	
	//插入到利润的流水里,$value为变化值，$change_type为0是每日结算，1为分红，2为退息3为其他
	public function log_profit($value,$change_type,$change_desc=''){
		$data['change_type'] = $change_type;
		$data['no_dis_profit'] = $value;
		$data['change_time'] = gmtime();
		$data['change_desc'] = $change_desc;
		if($change_type == 0){
			$data['all_profit'] = $value;
		}
		$this->table = 'profit_log';
        $this->insert($data);
	}
	
	//获取用户转账代金券的记录
	//user_id是转账人的id
	//user_name是目标的用户名
	//备注：MAX函数并不能调用对应的change_time，需要再次查询或使用子查询
	public function get_last_transfer_coin_log($user_id,$user_name){
		$sql = 'SELECT MAX(log_id) as new_log_id FROM ' . $this->pre . 'account_log WHERE user_id = ' . $user_id . ' AND change_desc = "向用户' . $user_name . '代金券转账"';
		$result = $this->row($sql);
		if($result['new_log_id'] != ''){
			$sql2 = 'SELECT log_id,change_time FROM ' . $this->pre . 'account_log WHERE log_id = ' . $result['new_log_id'];
			$result2 = $this->row($sql2);
			return $result2;
		}else{
			return 0;
		}		
		
				
	}
	
	//获取用户转账余额的记录
	//user_id是转账人的id
	//user_name是目标的用户名
	//备注：MAX函数并不能调用对应的change_time，需要再次查询或使用子查询
	public function get_last_transfer_surplus_log($user_id,$user_name){
		$sql = 'SELECT MAX(log_id) as new_log_id FROM ' . $this->pre . 'account_log WHERE user_id = ' . $user_id . ' AND change_desc = "向用户' . $user_name . '余额转账"';
		$result = $this->row($sql);
		if($result['new_log_id'] != ''){
			$sql2 = 'SELECT log_id,change_time FROM ' . $this->pre . 'account_log WHERE log_id = ' . $result['new_log_id'];
			$result2 = $this->row($sql2);
			return $result2;
		}else{
			return 0;
		}		
		
				
	}
	
	//获取指定字段的商品设置的值
	public function get_shop_config_value($code){
		$sql = 'SELECT value FROM ' . $this->pre . 'shop_config WHERE code = "' . $code . '"';		
		$rs = $this->row($sql);
		return $rs['value'];
	}
	
	/*  更新系统设置的数值   */
	/*  code  是修改字段    */
	/*  value  是修改数值    */
	/*  type  1是增加 2是更新  3是减少 */
	function change_shop_value($code,$value,$type){
		if($type == 1 || $type == 3){
			$sql = 'SELECT value FROM ' . $this->pre .
				"shop_config WHERE code = '$code'";
			$old_value = $this->row($sql);
			$old_value = $old_value['value'];
			if($type == 1){
				$new_value = floatval($old_value) + floatval($value);
			}else{
				$new_value = floatval($old_value) - floatval($value);
			}	
		}else{
			$new_value = $value;
		}
		$sql = "UPDATE ".$this->pre. "shop_config SET value = '$new_value' WHERE code = '$code'";
		$this->query($sql);
	}
	
	//发送微信通知信息
	public function send_wechat_msg($type,$tz_id,$info,$other){
		//获取微信资料
		$wxinfo = get_def_config();			
		$config['token'] = $wxinfo['token'];
		$config['appid'] = $wxinfo['appid'];
		$config['appsecret'] = $wxinfo['appsecret'];
		$weObj = new Wechat($config);
		if($type==1){	
			//微信提醒上级//新增粉丝提醒 		
			$tz_openid=getOpenid($tz_id);
			//model('Users')->xierizhi("下面是上级OPENID");
			//model('Users')->xierizhi($tz_openid);									
			$msg2 = array(
				'touser' => $tz_openid,
				'msgtype' => 'text',
				'text' => array(
					'content' => $info
				)
			);			
			
		}elseif($type==2){
			//微信提醒上级	提交订单提醒
			$up_uid=getUpid($tz_id);	//获取上级ID		
			$tz_openid=getOpenid($up_uid);										
			$msg2 = array(
				'touser' => $tz_openid,
				'msgtype' => 'text',
				'text' => array(
					'content' => $info
				)
			);	
		}elseif($type==3){
			//微信提醒上级	金币分成提醒			
			$up_uid=getUpid2($tz_id);	//根据订单号获取上级ID		
			$tz_openid=getOpenid($up_uid);														
			$msg2 = array(
				'touser' => $tz_openid,
				'msgtype' => 'text',
				'text' => array(
					'content' => $info
				)
			);
		}elseif($type==4){
			//微信提醒管理员	
			$up_uid=1;	//获取上级ID		
			$tz_openid=getOpenid($up_uid);										
			$msg2 = array(
				'touser' => $tz_openid,
				'msgtype' => 'text',
				'text' => array(
					'content' => $info
				)
			);	
		}
		$weObj->sendCustomMessage($msg2);
	}
	
	
	/**
	 * 异步将远程链接上的内容(图片或内容)写到本地
	 * 
	 * @param unknown $url
	 *            远程地址
	 * @param unknown $saveName
	 *            保存在服务器上的文件名
	 * @param unknown $path
	 *            保存路径
	 * @return boolean
	 */
	public function put_file_from_url_content($url, $saveName, $path) {
		// 设置运行时间为无限制
		set_time_limit ( 0 );
		
		$url = trim ( $url );
		$curl = curl_init ();
		// 设置你需要抓取的URL
		curl_setopt ( $curl, CURLOPT_URL, $url );
		// 设置header
		curl_setopt ( $curl, CURLOPT_HEADER, 0 );
		// 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
		curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
		// 运行cURL，请求网页
		$file = curl_exec ( $curl );
		// 关闭URL请求
		curl_close ( $curl );
		// 将文件写入获得的数据
		$filename = $path . $saveName;
		$write = @fopen ( $filename, "w" );
		if ($write == false) {
			return false;
		}
		if (fwrite ( $write, $file ) == false) {
			return false;
		}
		if (fclose ( $write ) == false) {
			return false;
		}
	}
	
	//获取下级用户ID
	public function get_down_user($user_id){
		$sql = "SELECT user_id,parent_id FROM " . $this->pre . "users WHERE parent_id = '" . $user_id ."'";
        return $this->query($sql);
	}
	
	
	//获取用户的冻结入股金
	public function get_user_frozen_premium($user_id){
		$this->table = 'users';
        $condition['user_id'] = $user_id;
        return $this->field('frozen_premium', $condition);
	}
	
	//获取所有用户的股数及合伙人数量
	public function get_all_user_stock(){
		$sql = "SELECT SUM(stock) as all_stock,COUNT(user_id) as user_number FROM " . $this->pre . "user_stock WHERE stock > 0";
		$rs = $this->row($sql);
		return $rs;
	}
	
	//更新所有用户的股数及比例
	public function change_user_stock($step = ''){
		if($step == '1'){
            $change_desc = "结算前更新";
            $change_type = 1;
        }elseif($step == '2'){
            $change_desc = "结算更新";
            $change_type = 2;
        }elseif($step == '3'){
            $change_desc = "新合伙人进入更新";
            $change_type = 3;
        }elseif($step == '5'){
            $change_desc = "分红前更新";
            $change_type = 5;
        }elseif($step == '6'){
            $change_desc = "分红后更新";
            $change_type = 6;
        }elseif($step == '7'){
            $change_desc = "股权奖励前更新";
            $change_type = 7;
        }
		//获取总入股金+未分配利润
		$all_premium = $this->get_shop_config_value('all_premium');
		$pure_profit = $this->get_shop_config_value('pure_profit');
		$sum = $this->get_all_user_stock();
		if(abs(intval(floatval($all_premium)+floatval($pure_profit)) - intval($sum['all_stock'])) > 10 || $step == '3'){
			//获取用户当前的股份数
			$sql = "SELECT s_id,user_id,stock FROM " . $this->pre . "user_stock WHERE stock > 0";
			$rs = $this->query($sql);
			foreach($rs as $k=>$v){
				$proportion = floor(intval($v['stock'])/intval($sum['all_stock'])*100000)/100000;
				$new_stock = intval(intval($v['stock'])/intval($sum['all_stock'])*(floatval($all_premium)+floatval($pure_profit)));
				$change_stock = $new_stock - intval($v['stock']);
				if($change_stock <> '0'){
					//更新用户股数及比例
					$this->log_stock($v['s_id'],$v['user_id'],$change_stock,$proportion,'',$change_desc,$change_type);
				}	
			}
		}			
	}
	
	//更新new用户的股数
	public function change_new_user_stock(){
		//将未分配的入股金加到总得入股金里
		$no_compute_premium = $this->get_shop_config_value('no_compute_premium');
		if($no_compute_premium <> '0.00'){
			$this->change_shop_value('all_premium',$no_compute_premium,1);
			$this->change_shop_value('no_compute_premium','0.00',2);
			$data['time'] = gmtime();
			$data['add_premium'] = $no_compute_premium;
			$data['rs_premium'] = $this->get_shop_config_value('all_premium');
			$this->table = 'ae_log';
			$this->insert($data);

            //给新合伙人增加股份
            $sql = "SELECT s_id,user_id,user_premium,stock FROM " . $this->pre . "user_stock WHERE stock = 0 ";
            $new_users = $this->query($sql);
            if($new_users){
                foreach($new_users as $k=>$v){
                    $this->log_stock($v['s_id'],$v['user_id'],$v['user_premium'],'','',"首次增加",0);
                }   
            }
			
			//大更新
			$this->change_user_stock('3');
		}
		
	}
	
	//修改股数并添加流水里
	public function log_stock($s_id,$user_id,$stock = 0,$proportion = '',$exp = '',$change_desc = '', $change_type = ''){
		/* 插入帐户变动记录 */
        $account_log = array(
            's_id' => $s_id,
            'user_id' => $user_id,
            'stock' => $stock,
			'exp' => $exp,
            'change_time' => gmtime(),
            'change_desc' => $change_desc,
            'change_type' => $change_type
        );		
        $this->table = 'stock_log';
        $this->insert($account_log);
        /* 更新用户信息 */
		if(!empty($proportion)){
			$yuju = ", proportion = '$proportion'";
		}
        $sql = "UPDATE " . $this->pre .
                "user_stock SET stock = stock + ('$stock')," .
				"exp = exp + ('$exp')" .
				$yuju .
                " WHERE s_id = '$s_id' LIMIT 1";
        $this->query($sql);
	}

    //获取需要执行的分红
    public function get_fenhong(){
        $sql = "SELECT id,fh_value FROM " . $this->pre . "user_fenhong WHERE is_del = 0 AND is_do = 2";
        return $this->query($sql); 
    }

    //执行分红
    /*  id  任务ID   */ 
    /*  value  分红值  */
    public function do_fenhong($id,$fh_value){
        $sum = $this->get_all_user_stock();
        //获取用户当前的股份数
        $sql = "SELECT s_id,user_id,stock FROM " . $this->pre . "user_stock WHERE stock > 0";
        $rs = $this->query($sql);
        $sj_fh = 0;
        foreach($rs as $k=>$v){
            $user_fh = floor(intval($v['stock'])/intval($sum['all_stock'])*floatval($fh_value));
            $sj_fh+=$user_fh;
            //添加到用户余额里
			if($user_fh <> '0'){
				$change_desc = "平台分红";
				$this->log_account_change($v['user_id'],$user_fh,0,0,0,$change_desc,30,2);
			}            
        }
        //更新分红信息
        $this->table = 'user_fenhong';
        $data['do_result'] = $sj_fh;
        $data['do_time'] = gmtime();
        $data['is_do'] = 1;
        $condition['id'] = $id;
        $this->update($condition, $data);

        //更新未分配余额并写入流水
        $this->change_shop_value('no_dis_profit',$sj_fh,3);
        $this->log_profit($sj_fh*-1,1,"平台分红");
    }

    //获取所有用户中完成年任务的用户
    public function get_all_year_user(){
        $sql = "SELECT * FROM " . $this->pre . "user_stock WHERE status = 0 AND now_assess_value >= year_assess_value";
        $rs = $this->query($sql);
        return $rs;
    }
    //修改用户等级
    public function change_user_rank($user_id,$rank_id){
        $sql = "UPDATE " . $this->pre .
                "users SET user_rank = '" . $rank_id .              
                "' WHERE user_id = " . $user_id;
        $this->query($sql);
    }

    //获取用户的所有入股金情况
    public function get_user_stock($user_id){
        $sql = "SELECT * FROM " . $this->pre . "user_stock WHERE status <> 2 AND user_id = '" . $user_id . "' ORDER BY s_id";
        $rs = $this->query($sql);
        return $rs;
    }
	//获取用户的所有入股金的合计
    public function get_user_allstock($user_id){
        $sql = "SELECT SUM(stock) as user_allstock FROM " . $this->pre . "user_stock WHERE status <> 2 AND user_id = '" . $user_id . "'";
        $rs = $this->row($sql);
        return $rs['user_allstock'];
    }
	

    //获取入股金的价值
    public function get_last_stock($stock){
        if($stock == 0){
            return "0";
        }else{
            $market_value = $this->get_shop_config_value('market_value');
            $pure_profit = $this->get_shop_config_value('pure_profit');
            $all_premium = $this->get_shop_config_value('all_premium'); 
            $total_assets = floatval($pure_profit) + floatval($all_premium);
            $stock2 = intval(intval($stock)/$total_assets*floatval($market_value));     //占市值的资产部分
            $last_stock = $stock2 > intval($stock) ? $stock2:$stock;
            return $last_stock;
        }        
    }
	
	//执行完成年度任务的奖励
	public function do_jiangli($s_id,$user_id){
		//先往上奖励
		$up_id = getUpid($user_id);		//获取上级ID
		if($up_id <> '0' || !empty($up_id)){
			$up_rank_id = $this->get_user_rank($up_id,1);	//获取上级用户等级
			if($up_rank_id == '144'){
				//执行对上级的奖励
				$this->do_stock_reward($up_id,$user_id);
				//修改为已发放
				$this->change_stock_value($s_id,'',1,'');
			}else{
				//存储对上级的奖励
				$this->save_stock_reward($s_id);
			}
		}		
	}
	//执行完成年度任务的奖励（奖励给自己）
	public function do_jiangli2($s_id,$user_id){
		//执行对自己的奖励
		$this->do_stock_reward($user_id,$user_id);
		//修改为已发放
		$this->change_stock_value($s_id,'',1,'');				
	}
	
	//给标准合伙人用户发放股权奖励
	public function do_stock_reward($user_id,$down_id = ''){
		$this->change_user_stock('7');		//执行大更新
		$sum = $this->get_all_user_stock();
		$premium = $this->get_shop_config_value('premium');
		$reward_value = intval($premium)*0.1;
		//获取用户当前的股份数
		$sql = "SELECT s_id,user_id,stock FROM " . $this->pre . "user_stock WHERE stock > 0 ORDER BY exp";
		$rs = $this->query($sql);
		$a = 0;
		foreach($rs as $k=>$v){
			$user_exp = ceil(intval($v['stock'])/intval($sum['all_stock'])*$reward_value);
			if($a + $user_exp > $reward_value){
				$user_exp = $reward_value - $a;
			}
			$a+=$user_exp;
			//减去用户支出的股数
			$this->log_stock($v['s_id'],$v['user_id'],$user_exp*-1,'',$user_exp,"股权奖励支出",8);
			if($a >=$reward_value){
				break;
			}
		}
		//给奖励人发放股数
		$sql = "SELECT s_id,user_id,stock FROM " . $this->pre . "user_stock WHERE stock > 0 AND ktimes = 1 AND user_id ='".$user_id."'";
		$row = $this->row($sql);
		if($user_id == $down_id){
			$info = "用户自己带来的市值奖励";
		}else{
			$info = "会员".$down_id."带来的市值奖励";
		}		
		$this->log_stock($row['s_id'],$row['user_id'],$reward_value,'','',$info,9);
	}
	//存储对上级的奖励
	public function save_stock_reward($s_id){
		$this->table = 'user_stock';
        $data['up_bonus'] = 2;
        $condition['s_id'] = $s_id;
        $this->update($condition, $data);
	}
	
	//获取下级所有存储的股权奖励
	public function get_down_stock_reward($up_id){
		$sql = "SELECT s.s_id,s.user_id, u.parent_id  " .
                " FROM " . $this->pre . "user_stock AS s " .
                " LEFT JOIN " . $this->pre . "users AS u " .
                " ON u.user_id = s.user_id " .
                " WHERE s.up_bonus =2 AND u.parent_id='$up_id'";
        return $this->query($sql);
	}
	
	//修改user_stock后面3项的值
	public function change_stock_value($s_id,$status = '',$up_bonus = '',$exp = ''){
		$this->table = 'user_stock';
		if($status){
			$data['status'] = $status;
		}
		if($up_bonus){
			$data['up_bonus'] = $up_bonus;
		}
		if($exp){
			$data['exp'] = $exp;
		}        
        $condition['s_id'] = $s_id;
        $this->update($condition, $data);
	}
	
	//修改用户共计完成股数
	public function change_user_done_stock($user_id,$ktimes){
		$this->table = 'users';
        $data['done_stock'] = $ktimes;
        $condition['user_id'] = $user_id;
        $this->update($condition, $data);
	}
	
	
	//检测用户的当月任务是否完成
	public function check_month_task($user_id){
		$user_rank_id = $this->get_user_rank($user_id);
		if($user_rank_id == '138' || $user_rank_id == '144'){
			$sql = "SELECT s_id,next_assess_value,now_assess_value FROM " . $this->pre . "user_stock WHERE stock > 0 AND status = 0 AND user_id ='".$user_id."'";
			$row = $this->row($sql);
			if($row){
				if(floatval($row['now_assess_value']) < floatval($row['next_assess_value'])){
					return true;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	//获取用户上次提醒的时间
	public function get_last_notice($user_id){
		$sql = "SELECT n_time FROM " . $this->pre . "user_notice WHERE user_id = '" . $user_id . "'";
        $rs = $this->row($sql);
        return $rs['n_time'];
	}	
	//给用户以提醒
	public function notice_user($user_id){
		$data['user_id'] = $user_id;
        $data['n_time'] = gmtime();
        $this->table = 'user_notice';
        $this->insert($data);
	}
		
	public function update_order_money($order_sn,$own_money = '0.00',$divi_money = '0.00'){
		$sql = "UPDATE " . $this->pre .
                "order_info SET own_money = own_money + ('$own_money')," .
                " divi_money = divi_money + ('$divi_money')" .
                " WHERE order_sn = '$order_sn' LIMIT 1";
        $this->query($sql);
	}	
	
	//获取用户最新一条的退股的s_id
	public function get_user_back_stock($user_id){
		$sql = "SELECT s_id FROM " . $this->pre . "user_stock WHERE status = 2 AND user_id = '" . $user_id . "'";
        $rs = $this->row($sql);
        return $rs['s_id'];
	}
}

